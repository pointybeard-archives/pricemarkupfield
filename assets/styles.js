function updateMarkupFromPercentage(value){
	actual_value = document.getElementsByClassName("actual-value")[0];
	markup_source_field_handle = document.getElementsByClassName("markup-source-field")[0].value;
	
	source = +(document.getElementsByName('fields['+markup_source_field_handle+']')[0].value);
	
	actual_value.value = source + (source * (+(value) / 100.0));
}	

function updatePercentageFromActual(value){
	percentage_value = document.getElementsByClassName("percentage")[0];
	markup_source_field_handle = document.getElementsByClassName("markup-source-field")[0].value;

	source = +(document.getElementsByName('fields['+markup_source_field_handle+']')[0].value);

	percentage_value.value = ((value / source) * 100) - 100;
}