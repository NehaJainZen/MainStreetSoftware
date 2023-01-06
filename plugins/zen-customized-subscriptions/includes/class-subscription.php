<?php
defined( 'ABSPATH' ) || exit;
/*
 * This file included to manage subscriptions.
 * Last updated by : Mark Lopez
 * Last updated on : 03-10-2022
 */
class ZCS_Subscription {

    public function __construct(){
        add_filter('bulk_actions-edit-shop_subscription', array( $this, 'add_bulk_action'), 11, 1 );
        add_action('bulk_edit_custom_box', array( $this, 'add_quick_edit_fields'), 10, 2 );
        add_action('admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts') );
        add_action('save_post', array( $this, 'save_bulk_date'), 10, 1 );
        add_action( 'admin_notices', array( $this, 'print_errors') );
        add_filter( 'woocommerce_subscriptions_product_price_string', array( $this, 'change_signup_fee_label'), 10, 3);
    }

    public function add_bulk_action( $bulk_actions ){
        $b_action['edit'] = __('Edit Date','zen-customized-subscriptions');
        return $b_action + $bulk_actions;
    }

    public function add_quick_edit_fields( $column_name, $post_type ){
        if( 'shop_subscription' !== $post_type ){
            return;
        }

        if( 'next_payment_date' === $column_name ){
            ?><fieldset class="inline-edit-col-left" style="width: 50%">
                <div class="inline-edit-col">
                    <label>
                        <span class="title" style="width: 22em"><?php _e('Subscription End Date (MM-DD-YYYY HH:MM)','zen-customized-subscriptions'); ?></span>
                        <input type="text" name="subscription_end_date" id="subscription_end_date_bulk_edit" placeholder="MM-DD-YYYYY HH:MM">
                    </label>
                </div>
            </fieldset><?php
        }
    }

    public function admin_enqueue_scripts(){
        $screen = get_current_screen();
        if( !empty( $screen ) && $screen->base == 'edit' && $screen->id == 'edit-shop_subscription' ){
            wp_enqueue_script( 'zen-inputmask', ZCS_PLUGIN_URL . 'assets/js/inputmask.js', array('jquery'), '1.0.0' );
            wp_enqueue_script( 'zen-custom', ZCS_PLUGIN_URL . 'assets/js/custom.js', array('jquery','zen-inputmask'), '1.0.0' );
        }
    }

    public function save_bulk_date( $post_id ){

        if( !empty( $_REQUEST['sub-end-date'] ) ){
            $invalid = false;

            $date = $_REQUEST['sub-end-date'];
            $date = explode(' ', $date );
            $time = $date[1];
            $date = $date[0];

            $date = explode('-', $date);
            $time = explode(':', $time);
            if( empty( $date[0] ) || !is_numeric( $date[0] ) ){
                $invalid = true;
                $err[] = __('Invalid or missing year','zen-customized-subscriptions');
            } 

            if( empty( $date[1] ) || !is_numeric( $date[1] ) ){
                $invalid = true;
                $err[] = __('Invalid or missing month','zen-customized-subscriptions');
            } 

            if( empty( $date[2] ) || !is_numeric( $date[2] ) ){
                $invalid = true;
                $err[] = __('Invalid or missing date','zen-customized-subscriptions');
            } 

            if( empty( $time[0] ) || !is_numeric( $time[0] ) ){
                $invalid = true;
                $err[] = __('Invalid or missing hours','zen-customized-subscriptions');
            } 

            if( empty( $time[1] ) || !is_numeric( $time[1] ) ){
                $invalid = true;
                $err[] = __('Invalid or missing minutes','zen-customized-subscriptions');
            }

            if( $invalid ){
                setcookie( 'err', implode(', ', $err), time() + 60 );
            } else {
                $datetime = strtotime( $date[2] . '-' . $date[0] . '-' . $date[1] . ' ' . $time[0] . ':' . $time[1] . ':00') + 25200;
                update_post_meta( $post_id, '_schedule_next_payment', date('Y-m-d H:i:s', $datetime ) );
            }
        }
    }

    public function print_errors(){
        if( isset( $_COOKIE['err'] ) && !empty( $_COOKIE['err'] ) ){
            ?><div class="error notice qc-errors">
                <p><?php echo $_COOKIE['err']; ?></p>
            </div><?php
            unset( $_COOKIE['err'] );
            setcookie('err', null, -1); 
        }
    }

    public function change_signup_fee_label( $subscription_string, $product, $include ){
        return str_replace('sign-up fee','initial cost',$subscription_string);
    }
}