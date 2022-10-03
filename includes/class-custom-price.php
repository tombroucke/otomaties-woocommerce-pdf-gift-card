<?php
namespace Otomaties\WooCommerce\Gift_Card;

class WC_Custom_Gift_Card_Custom_Price
{

    public function init()
    {
        add_action('woocommerce_before_calculate_totals', array( $this, 'add_custom_price' ));
    }

    public function add_custom_price($cart_object)
    {
        foreach ($cart_object->cart_contents as $key => $cart_item) {
            if (isset($cart_item['_gc_price'])) {
                $cart_item['data']->set_price($cart_item['_gc_price']);
            }
        }
    }
}
$wc_gift_card_custom_price = new WC_Custom_Gift_Card_Custom_Price();
$wc_gift_card_custom_price->init();
