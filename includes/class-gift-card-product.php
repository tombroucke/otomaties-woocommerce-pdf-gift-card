<?php
namespace Otomaties\WooCommerce\Gift_Card;

class WC_Gift_Card_Product
{

    public function init()
    {
        add_filter('woocommerce_product_supports', array( $this, 'remove_ajax_button' ), 10, 3);
        add_filter('woocommerce_is_virtual', array( $this, 'virtual_gift_card' ), 10, 2);
    }

    public function remove_ajax_button($supports, $feature, $product)
    {

        if (in_array($product->get_type(), Gift_Card_Controller::gift_card_products()) && $feature == 'ajax_add_to_cart') {
            return false;
        }
        return $supports;
    }

    public function virtual_gift_card($virtual, $product)
    {
        if (apply_filters('gc_virtual_gift_card', true) && ( $product->get_type() == 'gift_card' || $product->get_type() == 'gift_card_variable' )) {
            return true;
        }
        return $virtual;
    }
}

$wc_gift_card_product = new WC_Gift_Card_Product();
$wc_gift_card_product->init();
