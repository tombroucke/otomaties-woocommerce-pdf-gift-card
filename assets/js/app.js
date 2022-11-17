import {domReady} from '@roots/sage/client';

import FormValidator from '@tombroucke/otomaties-form-validator';
import Polyglot from 'node-polyglot';

/**
 * app.main
 */
const main = async (err) => {
	if (err) {
		// handle hmr errors
		console.error(err);
	}

	// application code

	const priceInput = document.querySelector('input[name="_gc_price"]');

	// calculate totals on priceInput change
	if(priceInput) {
		priceInput.addEventListener('change', (e) => {
			calculateTotals(parseFloat(e.target.value));
		});
	}

	function calculateTotals(price) {
		const summary = document.querySelector('[class*="product-type-gift_card"]').querySelector('.product-summary .woocommerce-Price-amount');
		if(isNaN(price)) {
			price = 0;
		}
		if (summary) {
			summary.innerHTML = '<span class="woocommerce-Price-currencySymbol">€</span>' + price.toFixed(2).replace('.',',');
		} else {
			console.error('Could not find summary element');
		}
	}

	const cartForm = document.querySelector('[class*="product-type-gift_card"]').querySelector('form.cart');
	if (cartForm) {
		var polyglot = new Polyglot();
		polyglot.extend({
			'This field is required': gift_card_vars.strings.fieldRequired,
			'Please enter a valid e-mailaddress': gift_card_vars.strings.validEmail,
			'Please select an option': gift_card_vars.strings.selectOption,
		})
		new FormValidator(cartForm, polyglot);
	}
};

/**
 * Initialize
 *
 * @see https://webpack.js.org/api/hot-module-replacement
 */
domReady(main);
import.meta.webpackHot?.accept(main);
