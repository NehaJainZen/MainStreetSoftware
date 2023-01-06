<?php
/**
 * Plugin Name: Zen customized subscription
 * Plugin URI: https://zen.agency
 * Description: It allows to customize the subscriptions
 * Version: 1.0.0
 * Author: Zen Agency
 * Author URI: https://zen.agency
 * Text Domain: zen-customized-subscriptions
 *
 */

defined( 'ABSPATH' ) || exit;


if ( ! defined( 'ZCS_PLUGIN_FILE' ) ) {
    define( 'ZCS_PLUGIN_FILE', __FILE__ );
}

if ( ! defined( 'ZCS_PLUGIN_URL') ) {
    define( 'ZCS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}

/**
 * Check if WooCommerce is active
 **/
if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {

    require __DIR__ . '/includes/Autoloader.php';

    if(!ZCS_Autoloader::init()) {
        return; 
    }

    $init = new ZCS_Init();
    $init->init();
}