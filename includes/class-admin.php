<?php
namespace Otomaties\WooCommerce\GiftCard;

class GiftCardAdmin
{

    public function init()
    {
        // Download gift card
        add_action('admin_init', array( $this, 'downloadGiftCard' ));

        // Product gift card settings
        add_filter('woocommerce_get_sections_products', array( $this, 'giftCardSection' ));
        add_filter('woocommerce_get_settings_products', array( $this, 'giftCardAllSettings' ), 10, 2);

        // Coupon gift card panels
        add_filter('woocommerce_coupon_data_tabs', array( $this, 'giftCardCouponDataTabs' ));
        add_action('woocommerce_coupon_data_panels', array( $this, 'giftCartCouponDataPanel'), 10, 2);
        add_action('woocommerce_coupon_options_save', array($this, 'saveGiftCartCouponDataPanel'), 10, 2);

        // Print gift card from coupon
        add_action('add_meta_boxes', array($this, 'addGiftCardMetaBox'));
    }

    public function downloadGiftCard()
    {
        $downloadGiftCard = filter_input(INPUT_GET, 'download_gift_card', FILTER_VALIDATE_INT);
        $couponId = filter_input(INPUT_GET, 'coupon_id', FILTER_VALIDATE_INT);

        if (!$downloadGiftCard || !$couponId || !current_user_can('manage_woocommerce')) {
            return;
        }

        $giftCardCoupon = new GiftCardCoupon($couponId);
        if (get_option('gc_debug') && get_option('gc_debug') == 'yes') {
            $giftCardCoupon->pdf()->display();
        } else {
            $giftCardCoupon->pdf()->download();
        }
        die();
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
                'type' => 'title'
            ];
            $settings[] = [
                'name'     => __('Debug gift card', 'otomaties-wc-giftcard'),
                'desc_tip' => __('Check this box if you are designing your gift card. ', 'otomaties-wc-giftcard'),
                'id'       => 'gc_debug',
                'type'     => 'checkbox',
                'desc'     => __('Display gift card in browser instead of downloading.', 'otomaties-wc-giftcard'),
            ];

            $settings[] = [
                'type' => 'sectionend',
                'id' => 'gift_card'
            ];
            return $settings;
        }

        return $settings;
    }

    public function giftCardCouponDataTabs($couponDataTabs)
    {
        $couponDataTabs['gift_card'] = array(
            'label'  => __('Gift Card', 'otomaties-wc-giftcard'),
            'target' => 'gift_card_coupon_data',
            'class'  => 'gift_card_coupon_data',
        );
        return $couponDataTabs;
    }

    public function giftCartCouponDataPanel($couponId, $coupon)
    {
        echo '<div id="gift_card_coupon_data" class="panel woocommerce_options_panel">';
        $fields = new GiftCardFields();
        foreach ($fields->getFields() as $field) {
            $inputFunction = 'woocommerce_wp_text_input';
            switch ($field['type']) {
                case 'textarea':
                    $inputFunction = 'woocommerce_wp_textarea_input';
                    break;
            }
            $inputFunction(
                array(
                    'id'                => $field['identifier'],
                    'label'             => $field['label'],
                    'placeholder'       => $field['placeholder'],
                    'description'       => $field['instructions'],
                    'value'             => wp_unslash($coupon->get_meta($field['identifier'])),
                    'desc_tip'          => true,
                    'type'              => 'text',
                    'class'             => '',
                )
            );
        }
        echo '</div>';
    }

    public function saveGiftCartCouponDataPanel($postId, $coupon)
    {

        $gcFields = array_filter($_POST, function ($key) {
            return strpos($key, '_gc') === 0 && !empty($_POST[$key]);
        }, ARRAY_FILTER_USE_KEY);

        if (empty($gcFields)) {
            return;
        }

        $coupon->update_meta_data('_gc', true);
        $fields = new GiftCardFields();
        foreach ($fields->getFields() as $field) {
            if (isset($_POST[$field['identifier']])) {
                $coupon->update_meta_data($field['identifier'], wc_clean($_POST[$field['identifier']]));
            }
        }
        $coupon->save();
    }

    public function addGiftCardMetaBox()
    {
        if (get_post_type(get_the_ID()) != 'shop_coupon' || !get_post_meta(get_the_ID(), '_gc', true)) {
            return;
        }

        add_meta_box(
            'gift_card',
            __('Gift Card', 'otomaties-wc-giftcard'),
            array($this, 'giftCardMetaBoxContent'),
            'shop_coupon',
            'side',
            'high'
        );
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
}

(new GiftCardAdmin())->init();
