jQuery(function($){
	
$('.actual-value').change(function() {  
	source_field_handle = $(".markup-source-field").val();
	cost_price = $('[name="fields['+source_field_handle+']"]').val();
	
	$(this).siblings('.percentage').val(((this.value / cost_price) * 100) - 100);
});

$('.percentage').change(function() {  
	source_field_handle = $(".markup-source-field").val();
	cost_price = $('[name="fields['+source_field_handle+']"]').val();

	$(this).siblings('.actual-value').val(+(cost_price) + (+(cost_price) * (+(this.value) / 100.0)));
});

});