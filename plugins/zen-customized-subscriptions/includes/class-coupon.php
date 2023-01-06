<?php
defined( 'ABSPATH' ) || exit;
/*
 * This file included to manage coupons.
 * Last updated by : Mark Lopez
 * Last updated on : 03-10-2022
 */
class ZCS_Coupon {

	public function __construct(){
		add_action('woocommerce_coupon_options',array($this, 'add_coupon_settings_field') );
		add_action('woocommerce_coupon_options_save', array( $this,'save_coupon_settings'), 10, 2 );
		add_filter('woocommerce_coupon_is_valid', array( $this, 'is_valid_coupon'), 10, 3 );
		add_action('woocommerce_before_cart', array( $this, 'apply_coupon') );
		add_action('woocommerce_removed_coupon', array( $this, 'manage_removed_coupon' ), 10, 1 );
		add_action('woocommerce_add_to_cart', array( $this, 'add_coupon_to_cart'), 10, 2 );
		add_action('woocommerce_after_cart_table', array( $this, 'show_coupons') );
		add_action('wp_enqueue_scripts', array( $this, 'enqueue_scripts') );
		add_action('wp_ajax_zen_apply_coupon', array( $this, 'ajax_apply_coupon') );
	}

	public function add_coupon_settings_field(){
		woocommerce_wp_checkbox( array(
			'id' => 'lapsed_membership_coupon',
			'label' => 'Allow this coupon only for lapsed membership users',
		) );
	}

	public function save_coupon_settings( $post_id, $coupon ){
		if( isset( $_POST['lapsed_membership_coupon'] ) && !empty( $_POST['lapsed_membership_coupon'] ) ){
			$coupon->update_meta_data( 'lapsed_membership_coupon', 'yes' );
		} else {
			$coupon->update_meta_data( 'lapsed_membership_coupon', 'no' );
		}
		$coupon->save();
	}

	public function is_valid_coupon( $valid, $coupon, $discounts ){
		$lapsed_membership_coupon = $coupon->get_meta( 'lapsed_membership_coupon' );
		if( $lapsed_membership_coupon == 'yes' ){
			if( !is_user_logged_in() ){
				$valid = false;
			} else {
				$lapsed_membership = get_option('customized_subscription_lapsed_renewal_plan');
				if( !wc_memberships_is_user_active_member( get_current_user_id() , $lapsed_membership ) ){
					$valid = false;
				}
			}
		}
		return $valid;
	}

	public function apply_coupon(){
		if( WC()->session->__isset( 'zen_auto_apply_coupons') ){
			foreach( WC()->session->get( 'zen_auto_apply_coupons') as $coupon ){
				if( !WC()->cart->has_discount( $coupon ) ){
					WC()->cart->apply_coupon( $coupon );
				}
			}
		}
	}

	public function manage_removed_coupon( $coupon_code ){

		if( !empty( $coupon_code ) ){
			$coupons = WC()->session->get('zen_auto_apply_coupons');
			if( is_array( $coupons ) && !empty( $coupons ) ){
				$pos = array_search( strtolower( $coupon_code ), $coupons );
				if( $pos !== false ){
					unset( $coupons[$pos] );
				}
				WC()->session->set('zen_auto_apply_coupons', $coupons );
			}
		}
	}

	public function add_coupon_to_cart( $cart_item_key, $product_id ){
		$lapsed_membership = get_option('customized_subscription_lapsed_renewal_plan');
		if( !wc_memberships_is_user_active_member( get_current_user_id(), $lapsed_membership ) ){
			return;
		}

		$coupons = $this->get_applicable_coupons( $product_id );
		foreach( $coupons as $coupon ){
			if( !WC()->session->__isset( 'zen_auto_apply_coupons') ) {
				$_coupons = array();
			} else {
				$_coupons = WC()->session->get('zen_auto_apply_coupons');
			}

			if( !is_array( $coupons ) ){
				$_coupons = array();
			}

			$_coupons[] = strtolower( $coupon->post_title );
			WC()->session->set( 'zen_auto_apply_coupons', $_coupons );
		}
	}

	public function show_coupons(){
		$lapsed_membership = get_option('customized_subscription_lapsed_renewal_plan');
		if( !wc_memberships_is_user_active_member( get_current_user_id(), $lapsed_membership ) ){
			return;
		}

		$coupons = $this->get_applicable_coupons();

		?><div class="zen-coupons"><?php 
			foreach( $coupons as $coupon ){
				if( !WC()->cart->has_discount( $coupon->post_title ) ){
					$applicable_product_ids = get_post_meta( $coupon->ID, 'product_ids', true );
					$applicable_product_ids = explode(',', $applicable_product_ids);
					$applicable = false;
					foreach( WC()->cart->get_cart_contents() as $item ){
						if( in_array( $item['product_id'], $applicable_product_ids ) || in_array( $item['variation_id'], $applicable_product_ids ) ){
							$applicable = true;
							break;
						}
					}
					if( $applicable ){
						$coupon_amount = get_post_meta( $coupon->ID, 'coupon_amount', true );
						$applicable_products = get_post_meta( $coupon->ID, 'product_ids', true );
						$applicable_products = explode( ',', $applicable_products );
						$coupon_discounts = array();
						foreach( $applicable_products as $product_id ){
							$product = wc_get_product( $product_id );
							$coupon_discounts[] = sprintf( __('Get discount of %s on %s','zen-customized-subscriptions'), wc_price( $coupon_amount ), $product->get_title() );
						}
						?><div class="zen-coupon coupon-<?php echo strtolower( $coupon->post_title ); ?>" data-coupon="<?php echo strtolower( $coupon->post_title ); ?>">
							<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" class="coupon-image">
								<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z">
								</path>
							</svg>
							<div class="coupon-details">
								<span class="coupon"><?php echo $coupon->post_title; ?></span><?php
								if( !empty( $coupon_discounts ) ){
									?><div class="discount-details"><?php echo implode(',', $coupon_discounts); ?></div><?php
								}
							?></div>
						</div><?php
					}
				}
			}
		?></div><?php
	}

	private function get_applicable_coupons( $product_id = null ){
		global $wpdb;
		$query = "SELECT ID, post_title FROM {$wpdb->posts} p LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = 'lapsed_membership_coupon' {joins} WHERE p.post_type = 'shop_coupon' AND p.post_status = 'publish' AND pm.meta_value = 'yes' {additional_where}";
		if( is_null( $product_id ) ){
			$query = str_replace("{joins}", "", $query);
			$query = str_replace("{additional_where}", "", $query);
		} else {
			$query = str_replace("{joins}","LEFT JOIN {$wpdb->postmeta} pr ON p.ID = pr.post_id AND pr.meta_key = 'product_ids'", $query);
			$query = str_replace("{additional_where}","AND pr.meta_value LIKE '%" . $product_id . "%'", $query);
		}
		return $wpdb->get_results( $query );
	}

	public function enqueue_scripts(){
		if( is_cart() ){
			wp_register_script('zen-coupons', ZCS_PLUGIN_URL . 'assets/js/coupon.js', array('jquery'), '1.0.0' );
			wp_localize_script('zen-coupons', 'ZCS', array( 'ajax_url' => admin_url('admin-ajax.php') ) );
			wp_enqueue_script('zen-coupons');
			wp_enqueue_style('zen-coupons', ZCS_PLUGIN_URL . 'assets/css/coupon.css', array(), '1.0.0' );
		}
	}

	public function ajax_apply_coupon(){
		if( isset( $_POST['coupon'] ) ){
			WC()->cart->apply_coupon( wc_clean( $_POST['coupon'] ) );
			die();
		}
	}
}