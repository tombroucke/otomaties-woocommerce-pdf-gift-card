<?php
namespace Otomaties\WooCommerce\GiftCard;

class GiftCardCoupon implements GiftCardBase
{

    private $coupon;
    private $orderItem;

    public function __construct($coupon)
    {
        if ($coupon instanceof \WC_Coupon) {
            $this->coupon = $coupon;
        } elseif (is_numeric($coupon)) {
            $this->coupon = new \WC_Coupon($coupon);
        }
    }

    public function item()
    {
        return $this->coupon;
    }

    public function get($key)
    {
        return $this->coupon->get_meta($key);
    }

    public function set($key, $value)
    {
        return $this->coupon->update_meta_data($key, $value);
    }

    public function pdf()
    {
        return new GiftCardPDF($this);
    }

    public function expiration() : ?string
    {
        return $this->coupon->get_date_expires();
    }

    public function filename() : string
    {
        return __('gift_card', 'otomaties-wc-giftcard') . '-' . $this->coupon->get_ID() . '.pdf';
    }

    public function amount() : float
    {
        $amount = $this->coupon->get_amount();
        if (!$amount) {
            $this->legacyGet('amount');
        }
        return $this->coupon->get_amount() ?? 0;
    }

    public function sender() : string
    {
        $sender = $this->get('_gc_sender');
        if (!$sender) {
            $sender = $this->legacyGet('_gc_sender');
        }
        return $sender ?? '';
    }

    public function recipient() : string
    {
        $recipient = $this->get('_gc_recipient');
        if (!$recipient) {
            $recipient = $this->legacyGet('_gc_recipient');
        }
        return $recipient ?? '';
    }

    public function message() : string
    {
        $message = $this->get('_gc_message');
        if (!$message) {
            $message = $this->legacyGet('_gc_message');
        }
        return $message ?? '';
    }

    public function couponCode(): string
    {
        return $this->coupon->get_code();
    }

    private function orderItem()
    {
        global $wpdb;
        $meta_value = $this->coupon->get_ID();
        $results = $wpdb->get_results( $wpdb->prepare( "SELECT order_item_id FROM {$wpdb->prefix}woocommerce_order_itemmeta WHERE meta_value = %s", $meta_value ), ARRAY_A );

        if (count($results) > 0) {
            $order_item_id = $results[0]['order_item_id'];
            $order_item = new \WC_Order_Item_Product($order_item_id);
            return $order_item;
        }
    }

    /**
     * Our legacy coupons don't have the meta data stored in the coupon object, but in the order item.
     *
     * @param string $key
     * @return void
     */
    private function legacyGet(string $key)
    {
        if (!$this->orderItem) {
            $this->orderItem = $this->orderItem();
        }
        return $this->orderItem ? wc_get_order_item_meta($this->orderItem->get_ID(), $key, true) : null;
    }
}
