<?php
namespace Otomaties\WooCommerce\GiftCard;

class GiftCardProduct
{

    public function init()
    {
        add_filter('woocommerce_product_supports', array( $this, 'removeAjaxButton' ), 10, 3);
        add_filter('woocommerce_is_virtual', array( $this, 'virtualGiftCard' ), 10, 2);
    }

    public function removeAjaxButton($supports, $feature, $product)
    {

        if (in_array($product->get_type(), GiftCardController::giftCardProducts()) && $feature == 'ajax_add_to_cart') {
            return false;
        }
        return $supports;
    }

    public function virtualGiftCard($virtual, $product)
    {
        if (apply_filters('gc_virtual_gift_card', true) && ( $product->get_type() == 'gift_card' || $product->get_type() == 'gift_card_variable' )) {
            return true;
        }
        return $virtual;
    }
}

(new GiftCardProduct())->init();
