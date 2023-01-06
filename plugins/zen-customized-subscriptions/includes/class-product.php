<?php
defined( 'ABSPATH' ) || exit;
/*
 * This file included to manage products.
 * Last updated by : Mark Lopez
 * Last updated on : 28-10-2022
 */
class ZCS_Product {
    public function __construct(){
        add_action('acf/init', array( $this, 'register_settings_page') );
        add_filter('woocommerce_add_to_cart_validation', array( $this, 'restrict_product_group'), 10, 3 );
    }

    public function register_settings_page(){
        if( function_exists('acf_add_options_page') ) {

            // Register options page.
            acf_add_options_page(array(
                'page_title'    => __('Restricted Groups', 'zen-customized-subscriptions'),
                'menu_title'    => __('Restricted Groups', 'zen-customized-subscriptions'),
                'menu_slug'     => 'restricted-groups',
                'capability'    => 'manage_options',
                'parent_slug'   => 'edit.php?post_type=product'
            ));
        }

        if( function_exists('acf_add_local_field_group') ):

        acf_add_local_field_group(array(
            'key' => 'group_635b9208dd8f1',
            'title' => 'Restricted Groups',
            'fields' => array(
                array(
                    'key' => 'field_635b9209e88b3',
                    'label' => 'Groups',
                    'name' => 'groups',
                    'type' => 'repeater',
                    'instructions' => 'Users will be able to add only one of the products from each group in a single cart.',
                    'required' => 0,
                    'conditional_logic' => 0,
                    'wrapper' => array(
                        'width' => '',
                        'class' => '',
                        'id' => '',
                    ),
                    'layout' => 'table',
                    'pagination' => 0,
                    'min' => 0,
                    'max' => 0,
                    'collapsed' => '',
                    'button_label' => 'Add Group',
                    'rows_per_page' => 20,
                    'sub_fields' => array(
                        array(
                            'key' => 'field_635b923fe88b4',
                            'label' => 'Products',
                            'name' => 'products',
                            'type' => 'post_object',
                            'instructions' => '',
                            'required' => 1,
                            'conditional_logic' => 0,
                            'wrapper' => array(
                                'width' => '',
                                'class' => '',
                                'id' => '',
                            ),
                            'post_type' => array(
                                0 => 'product',
                            ),
                            'taxonomy' => '',
                            'return_format' => 'id',
                            'multiple' => 1,
                            'allow_null' => 0,
                            'ui' => 1,
                            'parent_repeater' => 'field_635b9209e88b3',
                        ),
                    ),
                ),
            ),
            'location' => array(
                array(
                    array(
                        'param' => 'options_page',
                        'operator' => '==',
                        'value' => 'restricted-groups',
                    ),
                ),
            ),
            'menu_order' => 0,
            'position' => 'normal',
            'style' => 'seamless',
            'label_placement' => 'top',
            'instruction_placement' => 'label',
            'hide_on_screen' => '',
            'active' => true,
            'description' => '',
            'show_in_rest' => 0,
        ));

        endif;
    }

    public function restrict_product_group( $valid, $product_id, $quantity ){
        $restricted_groups = get_field('groups','options');
        $is_restricted = false;
        $product_group = array();
        foreach( $restricted_groups as $restricted_group ){
            if( in_array( $product_id, $restricted_group['products'] ) ){
                $is_restricted = true;
                $product_group = $restricted_group['products'];
                break;
            }
        }

        if( $is_restricted ){
            $is_valid = true;
            if( WC()->cart->get_cart_contents_count() > 0 ){
                foreach( WC()->cart->get_cart_contents() as $item ){
                    if( in_array( $item['product_id'], $product_group ) ){
                        $is_valid = false;
                        $product_name = $item['data']->get_name();
                    }
                }
            }

            if( !$is_valid ){
                $valid = false;
                wc_add_notice( sprintf(__( 'You can\'t add this product because %s product is already in your cart. These products contain duplicate software. Please remove %s from your cart first.', 'woocommerce' ), $product_name, $product_name ), 'error' );
            }
        }

        return $valid;
    }
}