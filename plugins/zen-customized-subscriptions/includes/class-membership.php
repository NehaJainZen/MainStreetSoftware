<?php
defined( 'ABSPATH' ) || exit;
/*
 * This file included to manage memberships.
 * Last updated by : Mark Lopez
 * Last updated on : 29-09-2022
 */
class ZCS_Membership {

    public function __construct(){
        add_action('wc_memberships_user_membership_created', array( $this, 'update_membership_renew_date'), 10, 2 );
        add_action('wc_memberships_user_membership_status_changed', array( $this, 'change_membership'), 10, 3);
        add_filter( 'cron_schedules', array( $this, 'add_every_five_minutes_schedule' ) );

        if ( ! wp_next_scheduled( 'every_five_minutes_action' ) ) {
            wp_schedule_event( time(), 'every_five_minutes', 'every_five_minutes_action' );
        }

        add_action('every_five_minutes_action', array( $this, 'activate_memberships' ) );

        add_filter( 'woocommerce_cart_needs_payment', '__return_false' );
        add_filter( 'woocommerce_order_needs_payment', '__return_false' );
    }

    public function update_membership_renew_date( $membership_plan, $data ){
        if( !empty( $membership_plan ) ){
            $expire_required_plans = get_option('customized_subscription_plans_need_expire');
            if( in_array( $membership_plan->get_id(), $expire_required_plans ) ){
                $theresold = get_option('customized_subscription_theresold_months');
                $year = intval( date('m') ) > 8 ? date('Y') + 1 : date('Y');
                $date = $year . '-08-08 07:00:00';
                $today = new DateTime();
                $end = new DateTime( $date );
                $interval = $today->diff($end);
                $diffInMonths  = $interval->m;
                if( $diffInMonths > $theresold ){
                    update_post_meta( $data['user_membership_id'], '_end_date', $date );
                    $order_id = get_post_meta( $data['user_membership_id'], '_order_id', true );
                    $subscription_id = $this->get_subscription_id( $order_id );
                    if( $subscription_id !== false ){
                        update_post_meta( $subscription_id, '_schedule_next_payment', $date );
                    }
                }
            }

            // $user_membership = wc_memberships_get_user_membership( $data['user_membership_id'] );
            // // if( $user_membership->is_expired() ){

            // //     $failed_membership = get_option('customized_subscription_failed_renewal_plan');
            // //     $lapsed_membership = get_option('customized_subscription_lapsed_renewal_plan');
            // //     if( $user_membership->get_plan_id() == $failed_membership || $user_membership->get_plan_id() == $lapsed_membership ){
            // //         $post = get_post( $user_membership->get_id() );
            // //         $created = new DateTime( $post->post_date_gmt );
            // //         $now = new DateTime();
            // //         $seconds = abs( $now - $created );
            // //         if( $seconds < 60 ){
            // //             print("<pre>");print_r($user_membership);print("</pre>");
            // //             $user_membership->set_end_date( '2022-10-10 13:23:27' );
            // //             $user_membership->activate_membership();
            // //             update_post_meta( $data['user_membership_id'], '_end_date', '2022-10-10 13:23:27' );
            // //             wp_update_post( array(
            // //                 'ID' => $data['user_membership_id'],
            // //                 'post_status' => 'wcm-active',
            // //             ));
            // //             // print("<pre>");print_r($user_membership);print("</pre>");
            // //             // die("####");
            // //         }
            // //     }
            // // }
        }
    }

    public function change_membership( $user_membership, $old_status, $new_status ){
        global $wpdb;
        $actual_expired = strtotime( $user_membership->post->post_date_gmt )<= current_time( 'timestamp', 'gmt' ) - 300;
        if( !$actual_expired ){
            return;
        }
        
        $failed_membership = get_option('customized_subscription_failed_renewal_plan');
        $lapsed_membership = get_option('customized_subscription_lapsed_renewal_plan');
        if( $old_status == 'active' && $new_status == 'expired' ){
            if( $user_membership->get_plan_id() == $failed_membership ){
                wc_memberships_create_user_membership(
                    array(
                        'plan_id' => $lapsed_membership,
                        'user_id' => $user_membership->get_user_id()
                    )
                );
            } else if ( $user_membership->get_plan_id() != $failed_membership && $user_membership->get_plan_id() != $lapsed_membership ){
                $membership = wc_memberships_create_user_membership(
                    array(
                        'plan_id' => $failed_membership,
                        'user_id' => $user_membership->get_user_id()
                    )
                );
            }
        }

        if( $new_status == 'active' ){
            if( $user_membership->get_plan_id() != $failed_membership && $user_membership->get_plan_id() != $lapsed_membership ){
                if( wc_memberships_is_user_active_member( $user_membership->get_user_id(), $failed_membership ) ){
                    // expire failed membership

                    $failed_active = wc_memberships_get_user_membership( $user_membership->get_user_id(), $failed_membership );
                    update_post_meta( $failed_active->get_id(), '_end_date', current_time('Y-m-d H:i:s') );
                    // $updated = wp_update_post( array(
                    //     'ID' => $failed_active->get_id(),
                    //     'post_status' => 'wcm-expired',
                    // ));
                    $updated = $wpdb->update( $wpdb->posts, array( 'post_status' => 'wcm-expired'), array( 'ID' => $failed_active->get_id() ) );

                }

                if( wc_memberships_is_user_active_member( $user_membership->get_user_id(), $lapsed_membership ) ){
                    // expire lapsed membership

                    $lapsed_active = wc_memberships_get_user_membership( $user_membership->get_user_id(), $lapsed_membership );
                    update_post_meta( $lapsed_active->get_id(), '_end_date', current_time('Y-m-d H:i:s') );
                    // wp_update_post( array(
                    //     'ID' => $lapsed_active->get_id(),
                    //     'post_status' => 'wcm-expired',
                    // ));

                    $updated = $wpdb->update( $wpdb->posts, array( 'post_status' => 'wcm-expired'), array( 'ID' => $lapsed_active->get_id() ) );
                }
            }
        }
    }

    private function get_subscription_id( $order_id ){
        global $wpdb;

        $query = "SELECT ID FROM {$wpdb->posts} WHERE post_parent = " . $order_id;
        $subscription_id = $wpdb->get_var( $query );
        if( empty( $subscription_id ) ){
            $subscription_id = false;
        }
        return $subscription_id;
    }

    public function add_every_five_minutes_schedule( $schedules ){
        $schedules['every_five_minutes'] = array(
            'interval'  => 60 * 5,
            'display'   => __( 'Every 5 Minutes', 'zen-customized-subscriptions' )
        );
        return $schedules;
    }

    public function activate_memberships(){
        global $wpdb;

        $failed_membership = get_option('customized_subscription_failed_renewal_plan');
        $lapsed_membership = get_option('customized_subscription_lapsed_renewal_plan');

        $query = "SELECT ID, post_author FROM {$wpdb->posts} WHERE post_type = 'wc_user_membership' AND post_status = 'wcm-expired' AND post_date >= '" . date('Y-m-d H:i:s', strtotime( current_time( 'Y-m-d H:i:s' ) ) - 300 ) . "' AND post_parent IN (" . $failed_membership . ", " . $lapsed_membership  .")";
        $results = $wpdb->get_results( $query );
        foreach( $results as $membership ){
            $count_other_memberships = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'wc_user_membership' AND post_status = 'wcm-active' AND post_author = " . $membership->post_author );
            if( $count_other_memberships <= 0 ){
                $wpdb->update( $wpdb->posts, array( 'post_status' => 'wcm-active' ), array( 'ID' => $membership->ID ) );
            }
        }
    }
}