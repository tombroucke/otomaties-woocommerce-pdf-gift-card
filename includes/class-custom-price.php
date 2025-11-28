<?php

namespace Otomaties\WooCommerce\GiftCard;

class GiftCardCustomPrice
{
    public function init()
    {
        add_action('woocommerce_before_calculate_totals', [$this, 'customPrice']);
    }

    public function customPrice($cart_object)
    {
        foreach ($cart_object->cart_contents as $key => $cart_item) {
            if (isset($cart_item['_gc_price'])) {
                $cart_item['data']->set_price($cart_item['_gc_price']);
            }
        }
    }
}

(new GiftCardCustomPrice)->init();
