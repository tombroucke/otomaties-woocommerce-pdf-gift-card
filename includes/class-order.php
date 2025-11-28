<?php

namespace Otomaties\WooCommerce\GiftCard;

class GiftCardOrder
{
    public function init()
    {
        add_filter('woocommerce_order_item_display_meta_key', [$this, 'changeLabels']);
        add_filter('woocommerce_order_item_display_meta_value', [$this, 'changeValues'], 10, 3);
        add_action('woocommerce_after_order_itemmeta', [$this, 'downloadLink'], 10, 3);
        add_filter('gc_meta_value_gc_message', [$this, 'formatMessage']);
    }

    public function changeLabels($meta_key)
    {
        $fields = (new GiftCardFields)->getFields();

        $other_labels = [
            '_gc_coupon' => __('Coupon', 'otomaties-wc-giftcard'),
            '_gc_mailed' => __('Mailed', 'otomaties-wc-giftcard'),
            '_gc_expiration' => __('Expiration date', 'otomaties-wc-giftcard'),
        ];

        foreach ($other_labels as $key => $label) {
            $fields[$key] = ['label' => $label];
        }

        if (isset($fields[$meta_key]) && isset($fields[$meta_key]['label'])) {
            return $fields[$meta_key]['label'];
        }

        if (strpos($meta_key, '_gc_coupon') !== false) {
            return $fields['_gc_coupon']['label'];
        }

        return $meta_key;
    }

    public function changeValues($display_value, $meta, $order)
    {

        if ($meta->key == '_gc_expiration') {
            $display_value = date('d-m-Y', $meta->value);
        } elseif (strpos($meta->key, '_gc_coupon') !== false) {
            $display_value = sprintf(
                '<a target="_blank" href="%s">%s</a>',
                get_edit_post_link($meta->value),
                $meta->value
            );
        }

        return $display_value;
    }

    public function downloadLink($item_id, $item, $product)
    {
        $order = new \WC_Order(get_the_ID());
        if ($order->get_status() == 'completed' && $item instanceof \WC_Order_Item_Product) {
            for ($i = 0; $i < $item->get_quantity(); $i++) {
                if (! wc_get_order_item_meta($item_id, '_gc_coupon_'.$i)) {
                    continue;
                }
                $couponId = wc_get_order_item_meta($item_id, '_gc_coupon_'.$i);
                $url = add_query_arg(
                    [
                        'download_gift_card' => true,
                        'coupon_id' => $couponId,
                    ],
                    get_edit_post_link($order->get_ID())
                );
                printf(
                    '<a href="%s" class="button button-secondary" target="_blank">%s</a>',
                    esc_url($url),
                    __('Download gift card', 'otomaties-wc-giftcard')
                );
            }
        }
    }

    public function formatMessage($value)
    {
        return wpautop($value);
    }
}

(new GiftCardOrder)->init();
