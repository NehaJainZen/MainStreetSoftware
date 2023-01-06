jQuery(function($){
	if( $(".qc-errors").length > 0 ){
		$("#message").remove();
	}

	$("input#bulk_edit").attr('disabled','disabled');
	$("#bulk-edit-legend").parent().remove();
	$(".inline-edit-col-right").remove();
	Inputmask("datetime", {
        inputFormat: "mm-dd-yyyy HH:MM",
        placeholder: "_",
        leapday: "-02-29",
        alias: "mm.tt.jjjj",
        hourFormat: 12,
        oncomplete: function(){
        	$("input#bulk_edit").removeAttr('disabled');
        },
        onincomplete: function(){
        	$("input#bulk_edit").attr('disabled','disabled');
        }
    }).mask("#subscription_end_date_bulk_edit");

	$("p.search-box").after('<input type="hidden" name="sub-end-date" id="sub-end-date">');
});

jQuery(document).on('change','#subscription_end_date_bulk_edit',function(){
	jQuery("#sub-end-date").val(jQuery(this).val());
});