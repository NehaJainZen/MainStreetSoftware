jQuery(document).on('click','.zen-coupon',function(){
	var coupon = jQuery(this).data('coupon');
	jQuery.ajax({
		url: ZCS.ajax_url,
		method: 'POST',
		data: {
			'coupon': coupon,
			'action': 'zen_apply_coupon',
		},
		success: function( response ){
			window.location.reload();
		}
	})
});