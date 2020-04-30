jQuery(document).ready(function($){
	$('input[name="_gc_price"]').change(function(){
		calculate_totals();
	});
	function calculate_totals(){

		if( !$('input[name="_gc_price"]').length ){
			return;
		}

		var total = parseFloat($('input[name="_gc_price"]').val());
		if( !total ){
			total = 0;
		}

		$('.woocommerce-Price-amount', '.single-product .summary').html('<span class="woocommerce-Price-currencySymbol">€</span>' + total.toFixed(2).replace('.',','));
	}
	calculate_totals();
})