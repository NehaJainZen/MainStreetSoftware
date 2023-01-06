<?php

/**
 * Plugin Name: Product Service Update
 * Description: This plugin adds functionality to update the product services
 * Author: Mark Lopez
 * Text Domain: zen-product-service-update
 */

defined("ABSPATH") || exit();

/**
 * Add Product Service Update Template
 * Author: Mark Lopez
 * Date: 30-11-2022
 */
add_filter('theme_page_templates', 'zen_add_service_update_page_template');
function zen_add_service_update_page_template($templates) {
	$templates['template-service-update.php'] = 'Product Service Update Template';
	return $templates;
}

/**
 * Redirect page to Product Service Update Template
 * Author: Mark Lopez
 * Date: 30-11-2022
 */
add_filter('page_template', 'zen_redirect_service_update_page_template', 10, 1);
function zen_redirect_service_update_page_template($template) {
	global $post;
	$page_template = get_post_meta( $post->ID, '_wp_page_template', true );
	if ('template-service-update.php' == basename($page_template)){
    
    	$template = plugin_dir_path(__FILE__) . 'templates/template-service-update.php';
	}
	return $template;
}

/**
 * Generate and add keycode to the form
 * Author: Mark Lopez
 * Date: 30-11-2022
 */
add_filter( 'gform_entry_meta', 'zen_generate_keycode_entry_meta', 10, 2 );
function zen_generate_keycode_entry_meta( $entry_meta, $form_id ) {
    // data will be stored with the meta key named score
    // label - entry list will use Score as the column header
    // is_numeric - used when sorting the entry list, indicates whether the data should be treated as numeric when sorting
    // is_default_column - when set to true automatically adds the column to the entry list, without having to edit and add the column for display
    // update_entry_meta_callback - indicates what function to call to update the entry meta upon form submission or editing an entry
    $entry_meta['keycode'] = array(
        'label' => 'KeyCode',
        'is_numeric' => false,
        'update_entry_meta_callback' => 'zen_generate_keycode',
        'is_default_column' => true
    );
 
    return $entry_meta;
}

/**
 * Generate unique keycode
 * Author: Mark Lopez
 * Date: 30-11-2022
 */
function zen_generate_keycode( $key, $lead, $form ) {
    $bytes = random_bytes(4);
	$byte1 = random_bytes(4);
	$byte2 = random_bytes(2);
	$byte3 = random_bytes(2);
	$byte4 = random_bytes(2);
	$byte5 = random_bytes(6);
	
    return sprintf('%08s-%04s-%04s-%04s-%12s', bin2hex($byte1), bin2hex($byte2), bin2hex($byte3), bin2hex($byte4), bin2hex($byte5));
}

/**
 * Add product updates new menu in my account page
 * Author: Mark Lopez
 * Date: 30-11-2022
 */
add_filter ( 'woocommerce_account_menu_items', 'zen_add_product_updates_menu_myaccount', 40 );
function zen_add_product_updates_menu_myaccount( $menu_links ){
	$menu_links['product-updates'] = __('Product Updates', 'qc-product-service-update');
	// $menu_links = array_slice( $menu_links, 0, 5, true ) 
	// + array( 'product-updates' => 'Product Updates' )
	// + array_slice( $menu_links, 5, NULL, true );
	return $menu_links;
}

/**
 * Register endpoint for the product updates menu
 * Author: Mark Lopez
 * Date: 30-11-2022
 */
add_action( 'init', 'zen_add_product_updates_endpoint' );
function zen_add_product_updates_endpoint() {
	add_rewrite_endpoint( 'product-updates', EP_PAGES );
}

/**
 * Add content for the new product updats page in My Account
 * Author: Mark Lopez
 * Date: 30-11-2022
 */
add_action( 'woocommerce_account_product-updates_endpoint', 'zen_product_updates_endpoint_content' );
function zen_product_updates_endpoint_content() {
	$user = wp_get_current_user();
	$country_code = get_user_meta($user->ID, 'billing_country', true);
	$form_id = 1;
	$search_criteria = array(
		'status' => 'active',
	    'field_filters' => array(
	        array(
	            'key'   => '6',
	            'value' => $country_code
	        )
	    )
	);
	//Pulls only the most recent entry
	$paging = array( 'offset' => 0, 'page_size' => 1 );
	// Getting the entries
	$result = GFAPI::get_entries( $form_id, $search_criteria, null, $paging );
	$memberships = wc_memberships_get_user_active_memberships();
	//print_r($memberships);
	if(empty($memberships)) {
		?><p><?php _e('You don\'t have an active memberships.', 'zen-product-service-update'); ?></p><?php
		return;
	}
	?><table class="widefat striped">
		<tr>
			<th><?php _e('Update', 'zen-product-service-update'); ?></th>
			<th><?php _e('KeyCode', 'zen-product-service-update'); ?></th>
			<th><?php _e('Status', 'zen-product-service-update'); ?></th>
		</tr><?php
			foreach ($result as $key => $entry) {
				if($entry[9] <= current_time('Y-m-d') && $entry[10] >= current_time('Y-m-d')){
					?><tr>
						<td><?php echo $entry[4]; ?></td>
						<td><?php echo $entry['keycode']; ?></td>
						<td>
							<?php
							$user_id = get_current_user_id();
							$key = $entry['keycode'];
							$product_download_status = get_user_meta( $user_id, $key, true );
							if($product_download_status=="" || $product_download_status==false){
								echo "Not download"; 
						
							}else{
							echo "Downloaded"; 
							}
							?></td>
					</tr><?php			
				}
			}
		
	?></table><?php
}
/**
 * 
 * Register Rest Routes
 * 
 */

include_once(plugin_dir_path(__FILE__) . '/api/pus-api.php');

function pus_register_user_routes(){
	$controller = new pus_User_Controller();
	$controller->register_routes();
}

add_action('rest_api_init','pus_register_user_routes');


// Add custom columns to Admin users list
add_action('manage_users_columns', 'add_custom_users_columns', 10, 1 );
function add_custom_users_columns( $columns ) {
    unset($columns['posts']);

    $columns['update_status'] = __('Update Status');
	
    return $columns;
}

// fetching the verification status, thanks to LoicTheAztec
add_filter('manage_users_custom_column',  'add_data_to_custom_users_columns', 10, 3);
function add_data_to_custom_users_columns( $value, $column_name, $user_id ) {
	global $wp;
	$current_slug = add_query_arg( array(), $wp->request );
    if ( 'update_status' == $column_name ) {
		$country_code = get_user_meta($user_id, 'billing_country', true);
		$form_id = 1;
		$search_criteria = array(
			'status' => 'active',
	    	'field_filters' => array(
	        	array(
	            	'key'   => '6',
	            	'value' => $country_code
	        	)
	    	)
		);
		//Pulls only the most recent entry
		$paging = array( 'offset' => 0, 'page_size' => 1 );
		// Getting the entries
		$entry = GFAPI::get_entries( $form_id, $search_criteria, null, $paging );
		$memberships = wc_memberships_get_user_active_memberships($user_id);
		//print_r($memberships); 
		if(empty($memberships)) {
		$value = '<span class="na" style="color:grey;">You don\'t have an active memberships.';
		}
		else{
			$key = $entry[0]['keycode'];
			$product_download_status = get_user_meta( $user_id, $key, true );
			if($product_download_status!="" || $product_download_status=='true'){
				$value = '<span style="color:green;font-weight:bold;">Downloaded</span><br><a class="update_status" data-key="'.$key.'" data-id="'.$user_id.'">Reset</a>';
			} else {
				$value = '<span class="na" style="color:grey;"><em>Not Downloaded</em></span>';
			}
		}
    return $value;
	}
}
function update_pus_status(  ){
     delete_user_meta( $_REQUEST['userId'], $_REQUEST['key']);
     wp_die(); // this is required to terminate immediately and return a proper response
 }

//Hook Function into WP_Ajax for Admin
add_action('wp_ajax_update_pus_status', 'update_pus_status');

// Function to add JS code
function update_pus_code() {
?>
    <script type="text/javascript">
	jQuery(document).ready(function($) {
			$('.update_status').click(function(){
			var userId = $(this).data('id');
			var key = $(this).data('key');
			var data = {
				action: 'update_pus_status',
				userId: userId,
				key: key
			};
				
			$.post(ajaxurl, data, function(response) {
				location.reload();
			});
		  });

	  });
    </script>
<?php
}
add_action( 'admin_footer', 'update_pus_code' );