<?php
namespace Otomaties\WooCommerce\GiftCard;

class GiftCardHandleCheckout
{

    public function init()
    {

        add_action('woocommerce_payment_complete_order_status', array( $this, 'autocompletePaidOrder' ), 10, 3);
        add_action('woocommerce_order_status_completed', array( $this, 'createGiftCard' ), 10);
        add_filter('woocommerce_coupon_get_usage_limit', array( $this, 'customUsageLimit' ), 10, 2);
        add_action('woocommerce_order_status_pending', array( $this, 'updateCouponAmount' ));
        add_action('woocommerce_order_status_completed', array( $this, 'updateCouponAmount' ));
        add_action('woocommerce_order_status_processing', array( $this, 'updateCouponAmount' ));
        add_action('woocommerce_order_status_on-hold', array( $this, 'updateCouponAmount' ));
        add_action('woocommerce_order_status_cancelled', array( $this, 'updateCouponAmount' ));
    }

    public function autocompletePaidOrder($status, $order_id, $order)
    {

        $autocomplete = true;
        foreach ($order->get_items() as $key => $item) {
            $product_id = $item->get_product_id();
            $product = wc_get_product($product_id);
            if (! $product || ! in_array($product->get_type(), GiftCardController::giftCardProducts())) {
                $autocomplete = false;
            }
        }
        return $autocomplete ? apply_filters('gc_autocomplete_gc_orders', 'completed', $status, $order_id, $order) : $status;
    }

    public function createGiftCard($order_id)
    {
        $order = wc_get_order($order_id);
        $files = array();
        $gift_cards = array();

        foreach ($order->get_items() as $key => $item) {
            $product_id = $item->get_product_id();
            $product = wc_get_product($product_id);

            if (! in_array($product->get_type(), GiftCardController::giftCardProducts())) {
                continue;
            }

            for ($i=0; $i < $item->get_quantity(); $i++) {
                if (apply_filters('gc_create_coupon', true, $item) && ! wc_get_order_item_meta($key, '_gc_coupon_' . $i, true)) {
                    $coupon_code = apply_filters(
                        'gc_coupon_code',
                        strtolower(str_pad(substr(wc_get_order_item_meta($key, '_gc_recipient', true), 0, 5), 5, 'X') . '_' . substr(md5(uniqid((string)rand(), true)), 0, 5))
                    );
                    $amount = round(( $item->get_total() + $item->get_total_tax() ) / $item->get_quantity(), 2);
                    $discount_type = 'fixed_cart';

                    $coupon = array(
                        'post_title'    => $coupon_code,
                        'post_content'  => '',
                        'post_status'   => 'publish',
                        'post_type'     => 'shop_coupon',
                    );

                    $new_coupon_id = wp_insert_post($coupon);
                    wc_update_order_item_meta($key, '_gc_coupon_' . $i, (string)$new_coupon_id);

                    $fields = new GiftCardFields();
                    foreach ($fields->getFields() as $field) {
                        update_post_meta($new_coupon_id, $field['identifier'], wc_get_order_item_meta($key, $field['identifier'], true));
                    }

                    update_post_meta($new_coupon_id, '_gc', true);
                    update_post_meta($new_coupon_id, 'discount_type', $discount_type);
                    update_post_meta($new_coupon_id, 'coupon_amount', $amount);
                    update_post_meta($new_coupon_id, 'individual_use', 'no');
                    update_post_meta($new_coupon_id, 'product_ids', '');
                    update_post_meta($new_coupon_id, 'exclude_product_ids', '');
                    update_post_meta($new_coupon_id, 'usage_limit', '-1'); // Needs to be -1, so it can be used in multiple orders
                    update_post_meta($new_coupon_id, 'date_expires', wc_get_order_item_meta($key, '_gc_expiration', true));
                    update_post_meta($new_coupon_id, 'apply_before_tax', 'yes');
                    update_post_meta($new_coupon_id, 'free_shipping', 'no');

                    $gift_card = new GiftCardCoupon($new_coupon_id);
                    array_push($gift_cards, $gift_card);
                }
            }

            foreach ($gift_cards as $key => $gift_card) {
                if (! $gift_card->get('_gc_mailed')) {
                    array_push($files, $gift_card->pdf()->file());
                }
            }
        }

        if (! empty($files) && apply_filters('gc_email_gift_card', true, $order)) {
            $message = '<p>' . __('Dear,', 'otomaties-wc-giftcard') . '</p>';
            $message .= '<p>' . _n(__('Your gift card is attached to this e-mail.', 'otomaties-wc-giftcard'), __('Your gift cards are attached to this e-mail.', 'otomaties-wc-giftcard'), count($files), 'otomaties-wc-giftcard') . '</p>';
            $message .= '<p>' . __('Kind regards,', 'otomaties-wc-giftcard') . '<br />' . apply_filters('gc_email_sender_name', get_bloginfo('name')) . '</p>';
            add_filter('wp_mail_content_type', array( $this, 'setHtmlContentType' ));
            add_filter('wp_mail_from_name', array( $this, 'fromName' ));
            $mailed = wp_mail($order->get_billing_email(), _n(__('Your gift card', 'otomaties-wc-giftcard'), __('Your gift cards', 'otomaties-wc-giftcard'), count($files), 'otomaties-wc-giftcard'), apply_filters('gc_email_message', $message), '', $files);
            if ($mailed) {
                foreach ($gift_cards as $key => $gc) {
                    $gc->set('_gc_mailed', true);
                }
            }
            remove_filter('wp_mail_content_type', array( $this, 'setHtmlContentType' ));
            remove_filter('wp_mail_from_name', array( $this, 'fromName' ));
        }
    }

    public function setHtmlContentType($content_type)
    {
        return 'text/html';
    }

    public function fromName()
    {
        return apply_filters('gc_email_from_name', get_bloginfo('name', 'display'));
    }

    public function customUsageLimit($value, $coupon)
    {
        $gc = get_post_meta($coupon->get_ID(), '_gc', true);
        if ($gc) {
            $value = false;
        }
        return $value;
    }

    public function updateCouponAmount($order_id)
    {
        $order = wc_get_order($order_id);
        foreach ($order->get_items('coupon') as $coupon_item_id => $coupon_item) {
            $coupon = new \WC_Coupon($coupon_item->get_code());
            $gc       = get_post_meta($coupon->get_ID(), '_gc', true);
            $gc_orders = get_post_meta($coupon->get_ID(), '_used_in_order', false);
            if (!$order->has_status('cancelled') && $gc && !in_array($order_id, $gc_orders)) {
                $coupon_used = $coupon_item->get_discount() + $coupon_item->get_discount_tax();
                $coupon->set_amount($coupon->get_amount() - $coupon_used);
                $coupon->save();
                add_post_meta($coupon->get_ID(), '_used_in_order', $order_id);
            }
        }
    }
}

(new GiftCardHandleCheckout())->init();
