<?php
defined( 'ABSPATH' ) || exit;
/*
 * This file included to initialize the classes.
 * Last updated by : Mark Lopez
 * Last updated on : 29-09-2022
 */
class ZCS_Init {

    public function init(){
        new ZCS_Settings();
        new ZCS_Membership();
        new ZCS_Subscription();
        new ZCS_Coupon();
        new ZCS_Product();
    }

}