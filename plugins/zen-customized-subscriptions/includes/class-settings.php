<?php
defined( 'ABSPATH' ) || exit;
/*
 * This file is integrated for settings.
 * Last updated by : Mark Lopez
 * Last updated on : 29-09-2022
 */
class ZCS_Settings {

    const slug = 'customized_subscription';

    public function __construct(){
        add_filter('woocommerce_settings_tabs_array', __CLASS__ . '::add_settings_tab',50);
        add_action( 'woocommerce_settings_tabs_'. self::slug, __CLASS__ . '::settings_tab' );
        add_action( 'woocommerce_update_options_' . self::slug, __CLASS__ . '::update_settings' );
    }

    public static function add_settings_tab( $settings_tabs ){
        $settings_tabs[self::slug] = __('Customized Subscription','zen-customized-subscription');
        return $settings_tabs;
    }

    public static function settings_tab(){
        woocommerce_admin_fields( self::get_settings() );
    }

    public static function update_settings() {
        woocommerce_update_options( self::get_settings() );
    }

    public static function get_settings(){
        $membership_plans = array();
        foreach( wc_memberships_get_membership_plans() as $plan_id => $plan ){
            $membership_plans[$plan_id] = $plan->get_name();
        }

        $settings = array(
            array(
                'name' => __('Customized Subscription Settings','zen-customized-subscription'),
                'type' => 'title',
                'desc' => __('Apply the settings for customized subscription', 'zen-customized-subscription'),
                'desc_tip' => true,
                'id'  => self::slug . '_settings'
            ),
            array(
                'name' => __('Membership plan to expire on 8 August','zen-customized-subscription'),
                'type' => 'multiselect',
                'desc' => __('Select the plans you want to set for expire on 8 August every year','zen-customized-subscription'),
                'desc_tip' => true,
                'id' => self::slug . '_plans_need_expire',
                'options' => $membership_plans,
            ),
            array(
                'name' => __('Theresold months','zen-customized-subscription'),
                'type' => 'number',
                'desc' => __('Theresold months to check before setting the expiry date of 8 August','zen-customized-subscription'),
                'desc_tip' => true,
                'id' => self::slug . '_theresold_months',
                'custom_attributes' => array(
                    'min' => 0,
                    'max' => 11
                )
            ),
            array(
                'name' => __('Failed renewal plan','zen-customized-subscription'),
                'type' => 'select',
                'desc' => __('Please select failed renewal plan to apply when other plan is expired','zen-customized-subscription'),
                'desc_tip' => true,
                'id' => self::slug . '_failed_renewal_plan',
                'options' => array_replace( array('' => __('Please Select','zen-customized-subscription')), $membership_plans)
            ),
            array(
                'name' => __('Lapsed renewal plan','zen-customized-subscription'),
                'type' => 'select',
                'desc' => __('Please select lapsed renewal plan to apply when failed plan is expired','zen-customized-subscription'),
                'desc_tip' => true,
                'id' => self::slug . '_lapsed_renewal_plan',
                'options' => array_replace( array('' => __('Please Select','zen-customized-subscription')), $membership_plans)
            ),
            array(
                'type' => 'sectionend',
                'id' => self::slug . '_settings'
            ),
        );
        return apply_filters( 'zen_' . self::slug . '_settings', $settings );
    }
}