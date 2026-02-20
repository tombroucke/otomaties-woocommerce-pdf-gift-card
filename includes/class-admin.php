<?php

namespace Otomaties\WooCommerce\GiftCard;

class GiftCardAdmin
{
    public function init()
    {
        // Download gift card
        add_action('admin_init', [$this, 'downloadGiftCard']);

        // Product gift card settings
        add_filter('woocommerce_get_sections_products', [$this, 'giftCardSection']);
        add_filter('woocommerce_get_settings_products', [$this, 'giftCardAllSettings'], 10, 2);

        // Coupon gift card panels
        add_filter('woocommerce_coupon_data_tabs', [$this, 'giftCardCouponDataTabs']);
        add_action('woocommerce_coupon_data_panels', [$this, 'giftCartCouponDataPanel'], 10, 2);
        add_action('woocommerce_coupon_options_save', [$this, 'saveGiftCartCouponDataPanel'], 10, 2);

        add_filter('manage_shop_coupon_posts_custom_column', [$this, 'addGiftCardIcon'], 10, 2);

        // Print gift card from coupon
        add_action('add_meta_boxes', [$this, 'addGiftCardMetaBox']);
        add_action('add_meta_boxes', [$this, 'addOrderMetaBox']);
    }

    public function downloadGiftCard()
    {
        $downloadGiftCard = filter_input(INPUT_GET, 'download_gift_card', FILTER_VALIDATE_INT);
        $couponId = filter_input(INPUT_GET, 'coupon_id', FILTER_VALIDATE_INT);

        if (! $downloadGiftCard || ! $couponId || ! current_user_can('manage_woocommerce')) {
            return;
        }

        $giftCardCoupon = new GiftCardCoupon($couponId);
        if (get_option('gc_debug') && get_option('gc_debug') == 'yes') {
            $giftCardCoupon->pdf()->display();
        } else {
            $giftCardCoupon->pdf()->download();
        }
        exit();
    }

    public function giftCardSection($sections)
    {
        $sections['gift_card'] = __('Gift Card', 'otomaties-wc-giftcard');

        return $sections;
    }

    public function giftCardAllSettings($settings, $current_section)
    {
        if ($current_section == 'gift_card') {
            $settings = [];
            $settings[] = [
                'name' => __('Gift Card Settings', 'otomaties-wc-giftcard'),
                'type' => 'title',
            ];
            $settings[] = [
                'name' => __('Debug gift card', 'otomaties-wc-giftcard'),
                'desc_tip' => __('Check this box if you are designing your gift card. ', 'otomaties-wc-giftcard'),
                'id' => 'gc_debug',
                'type' => 'checkbox',
                'desc' => __('Display gift card in browser instead of downloading.', 'otomaties-wc-giftcard'),
            ];

            $settings[] = [
                'type' => 'sectionend',
                'id' => 'gift_card',
            ];

            return $settings;
        }

        return $settings;
    }

    public function giftCardCouponDataTabs($couponDataTabs): array
    {
        $coupon = new \WC_Coupon(get_the_ID());
        if (apply_filters('gc_hide_coupon_boxes', true) && (get_post_type(get_the_ID()) != 'shop_coupon' || ! $this->isGiftCard($coupon))) {
            return $couponDataTabs;
        }

        $couponDataTabs['gift_card'] = [
            'label' => __('Gift Card', 'otomaties-wc-giftcard'),
            'target' => 'gift_card_coupon_data',
            'class' => 'gift_card_coupon_data',
        ];

        return $couponDataTabs;
    }

    public function giftCartCouponDataPanel($couponId, $coupon)
    {
        echo '<div id="gift_card_coupon_data" class="panel woocommerce_options_panel">';
        $fields = new GiftCardFields;

        foreach ($fields->getFields() as $field) {
            $inputFunction = 'woocommerce_wp_text_input';
            switch ($field['type']) {
                case 'textarea':
                    $inputFunction = 'woocommerce_wp_textarea_input';
                    break;
            }
            $inputFunction(
                [
                    'id' => $field['identifier'],
                    'label' => $field['label'],
                    'placeholder' => $field['placeholder'],
                    'description' => $field['instructions'],
                    'value' => wp_unslash($coupon->get_meta($field['identifier'])),
                    'desc_tip' => true,
                    'type' => 'text',
                    'class' => '',
                ]
            );
        }
        echo '</div>';
    }

    public function saveGiftCartCouponDataPanel($postId, $coupon)
    {

        $gcFields = array_filter($_POST, function ($key) {
            return strpos($key, '_gc') === 0 && ! empty($_POST[$key]);
        }, ARRAY_FILTER_USE_KEY);

        if (empty($gcFields)) {
            return;
        }

        $coupon->update_meta_data('_gc', true);
        $fields = new GiftCardFields;
        foreach ($fields->getFields() as $field) {
            if (isset($_POST[$field['identifier']])) {
                $coupon->update_meta_data($field['identifier'], wc_clean($_POST[$field['identifier']]));
            }
        }
        $coupon->save();
    }

    public function addGiftCardMetaBox()
    {
        $coupon = new \WC_Coupon(get_the_ID());
        if (apply_filters('gc_hide_coupon_boxes', true) && (get_post_type(get_the_ID()) != 'shop_coupon' || ! $this->isGiftCard($coupon))) {
            return;
        }

        add_meta_box(
            'gift_card',
            __('Gift Card', 'otomaties-wc-giftcard'),
            [$this, 'giftCardMetaBoxContent'],
            'shop_coupon',
            'side',
            'high'
        );
    }

    public function addOrderMetaBox()
    {
        $coupon = new \WC_Coupon(get_the_ID());
        if ((get_post_type(get_the_ID()) != 'shop_coupon' || ! $this->isGiftCard($coupon))) {
            return;
        }

        add_meta_box(
            'gift_card_order',
            __('Order', 'otomaties-wc-giftcard'),
            [$this, 'giftCardOrderMetaBoxContent'],
            'shop_coupon',
            'side',
            'high'
        );
    }

    public function addGiftCardIcon($column, $postId)
    {
        if ($column !== 'type') {
            return;
        }

        $coupon = new \WC_Coupon($postId);
        if ($this->isGiftCard($coupon)) {
            echo '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" height="1.2em">
  <path stroke-linecap="round" stroke-linejoin="round" d="M21 11.25v8.25a1.5 1.5 0 0 1-1.5 1.5H5.25a1.5 1.5 0 0 1-1.5-1.5v-8.25M12 4.875A2.625 2.625 0 1 0 9.375 7.5H12m0-2.625V7.5m0-2.625A2.625 2.625 0 1 1 14.625 7.5H12m0 0V21m-8.625-9.75h18c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125h-18c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z" />
</svg>
';
        }
    }

    public function giftCardMetaBoxContent($coupon)
    {
        $url = add_query_arg(
            [
                'download_gift_card' => true,
                'coupon_id' => $coupon->ID,
            ],
            admin_url('admin.php')
        );

        printf(
            '<a href="%s" class="button button-primary" target="_blank">%s</a>',
            esc_url($url),
            __('Download gift card', 'otomaties-wc-giftcard')
        );
    }

    public function giftCardOrderMetaBoxContent($coupon)
    {
        $coupon = new GiftCardCoupon($coupon->ID);
        $order = $coupon->order();

        if ($order) {
            printf(
                '<p>%s</p>',
                sprintf(__('This gift card was ordered by %s in order #%s', 'otomaties-wc-giftcard'), $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(), $order->get_id())
            );
            printf(
                '<a href="%s" class="button button-secondary" target="_blank">%s</a>',
                esc_url(get_edit_post_link($order->get_id())),
                __('View order', 'otomaties-wc-giftcard')
            );
        } else {
            echo __('No order found', 'otomaties-wc-giftcard');
        }
    }

    private function isGiftCard(\WC_Coupon $coupon)
    {
        return $coupon->get_meta('_gc') ? true : false;
    }
}

(new GiftCardAdmin)->init();
