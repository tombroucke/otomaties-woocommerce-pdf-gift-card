<?php
namespace Otomaties\WooCommerce\Gift_Card;

class WC_Custom_Gift_Card_Fields
{

    private $fields = array();

    public function __construct()
    {

        $fields = array(
            array(
                'identifier' => '_gc_price',
                'type' => 'number',
                'maxlength' => 55,
                'label' => __('Price', 'otomaties-wc-giftcard'),
                'placeholder' => _x('Price', 'label', 'otomaties-wc-giftcard'),
                'required' => true,
                'display' => true,
                'value' => ( isset($_POST['gc_price']) ? $_POST['gc_price'] : '' )
            ),
            array(
                'identifier' => '_gc_sender',
                'type' => 'text',
                'maxlength' => 55,
                'label' => __('Your name', 'otomaties-wc-giftcard'),
                'placeholder' => _x('Your name', 'label', 'otomaties-wc-giftcard'),
                'required' => true,
                'value' => ( isset($_POST['_gc_sender']) ? $_POST['_gc_sender'] : '' )
            ),
            array(
                'identifier' => '_gc_recipient',
                'type' => 'text',
                'maxlength' => 55,
                'label' => __('Recipient name', 'otomaties-wc-giftcard'),
                'placeholder' => _x('Recipient name', 'label', 'otomaties-wc-giftcard'),
                'required' => true,
                'value' => ( isset($_POST['_gc_recipient']) ? $_POST['_gc_recipient'] : '' )
            ),
            array(
                'identifier' => '_gc_message',
                'type' => 'textarea',
                'maxlength' => 200,
                'label' => __('Message', 'otomaties-wc-giftcard'),
                'placeholder' => _x('Message', 'label', 'otomaties-wc-giftcard'),
                'required' => true,
                'value' => ( isset($_POST['_gc_message']) ? $_POST['_gc_message'] : '' )
            )
        );

        foreach ($fields as $field) {
            $field = new WC_Custom_Gift_Card_Field($field);
            $this->fields[$field['identifier']] = $field ;
        }
    }

    public function init()
    {

        add_action('woocommerce_before_add_to_cart_button', array( $this, 'display_gift_card_fields' ), 15);
        add_filter('woocommerce_add_to_cart_validation', array( $this, 'validate_gift_card_fields' ), 10, 3);
        add_filter('woocommerce_add_cart_item_data', array( $this, 'save_gift_card_fields_to_cart_item_data' ), 10, 3);
        add_filter('woocommerce_get_item_data', array( $this, 'set_gift_card_fields_display' ), 10, 2);
        add_action('woocommerce_checkout_create_order_line_item', array( $this, 'save_gift_card_fields_to_order' ), 10, 4);
    }

    public function get_fields($product = null)
    {
        $fields     = $this->fields;
        if ($product) {
            $min_price  = get_post_meta($product->get_ID(), 'gc_min_price', true);
            if (!$min_price && $min_price !== '0') {
                unset($fields['_gc_price']);
            } elseif ($fields['_gc_price']['value'] == '') {
                $fields['_gc_price']['value'] = $product->get_price();
            }
        }
        return apply_filters('gc_get_fields', $fields, $product);
    }

    public function display_gift_card_fields()
    {

        $product = wc_get_product(get_the_ID());

        if (!in_array($product->get_type(), Gift_Card_Controller::gift_card_products())) {
            return;
        }

        do_action('gc_before_display_fields');
        $output = '<div class="gc-info">';
        $fields = $this->get_fields($product);

        foreach ($fields as $key => $field) {
            if (!$field->display()) {
                continue;
            }
            $output .= '<div class="form-group">';
            $data = '';
            foreach ($field['data'] as $data_key => $data_value) {
                $data .= 'data-' . $data_key . '="' . $data_value . '"';
            }
            switch ($field['type']) {
                case 'hidden':
                case 'password':
                case 'email':
                case 'phone':
                case 'number':
                case 'text':
                    if (isset($field['label'])) {
                        $output .= sprintf('<label for="%s">%s%s</label>', $key, $field['label'], ( $field['required'] ? ' *' : '' ));
                    }
                    $output .= sprintf('<input type="%s" class="form-control input-text" name="%s" placeholder="%s%s" maxlength="%s" value="%s" %s %s>', $field['type'], $key, $field['placeholder'], ( $field['required'] ? ' *' : '' ), $field['maxlength'], $field['value'], ( $field['required'] ? 'required' : '' ), $data);
                    break;

                case 'textarea':
                    if (isset($field['label'])) {
                        $output .= sprintf('<label for="%s">%s%s</label>', $key, $field['label'], ( $field['required'] ? ' *' : '' ));
                    }
                    $output .= sprintf('<textarea class="form-control input-text" name="%s" id="" cols="30" rows="6" placeholder="%s%s" maxlength="%s" %s>%s</textarea>', $key, $field['placeholder'], ( $field['required'] ? ' *' : '' ), $field['maxlength'], ( $field['required'] ? 'required' : '' ), $field['value']);
                    break;
            }
            $output .= '</div>';
        }
        $output .= '</div>';
        echo $output;
        do_action('gc_after_display_fields');
    }

    public function validate_gift_card_fields($passed, $product_id, $quantity)
    {

        $product    = wc_get_product($product_id);

        if (!in_array($product->get_type(), Gift_Card_Controller::gift_card_products())) {
            return $passed;
        }

        $fields         = $this->get_fields($product);
        $min_price      = get_post_meta($product->get_ID(), 'gc_min_price', true);

        foreach ($fields as $key => $field) {
            if (( isset($field['required']) && $field['required'] ) && empty($_POST[$key])) {
                $passed = false;
                wc_add_notice(sprintf(__('%s is required.', 'otomaties-wc-giftcard'), $field['label']), 'error');
            }
        }

        if ($min_price && isset($_POST['_gc_price']) && $_POST['_gc_price'] < $min_price) {
            $passed = false;
            wc_add_notice(sprintf(__('The minimum price for %s is %s', 'otomaties-wc-giftcard'), $product->get_name(), wc_price($min_price)), 'error');
        }
        return $passed;
    }

    public function save_gift_card_fields_to_cart_item_data($cart_item_data, $product_id, $variation_id)
    {

        $product    = wc_get_product($product_id);
        $fields     = $this->get_fields($product);

        if (!in_array($product->get_type(), Gift_Card_Controller::gift_card_products())) {
            return $cart_item_data;
        }

        foreach ($fields as $key => $field) {
            if (!empty($_POST[$key])) {
                $cart_item_data[$key] = $_POST[$key];
            }
        }

        $expiration = get_post_meta($product->get_ID(), 'gc_expiration', true);
        $date_offset = '+' . str_replace('_', ' ', $expiration);

        $cart_item_data['_gc_expiration'] = strtotime($date_offset);

        return $cart_item_data;
    }

    public function set_gift_card_fields_display($item_data, $cart_item)
    {

        $product    = wc_get_product($cart_item['product_id']);
        $fields     = $this->get_fields($product);

        if (!in_array($product->get_type(), Gift_Card_Controller::gift_card_products())) {
            return $item_data;
        }

        foreach ($fields as $key => $field) {
            if (isset($cart_item[$key]) && ( !isset($field['display']) || $field['display'] ) && isset($field['label'])) {
                $value = esc_html($cart_item[$key]);
                if ($key == '_gc_price') {
                    $value = wc_price((float)$value);
                }
                if ($key == '_gc_message') {
                    $value = wp_trim_words($value, apply_filters('gc_message_preview_length', 8), '...');
                }
                $item_data[] = array(
                    'key'     => $field['label'],
                    'value'   => $value,
                    'display' => '',
                );
            }
        }
        return $item_data;
    }

    public function save_gift_card_fields_to_order($item, $cart_item_key, $values, $order)
    {
        $product_id = $item->get_product_id();
        $product = wc_get_product($product_id);

        if (!in_array($product->get_type(), Gift_Card_Controller::gift_card_products())) {
            return;
        }

        $fields = $this->get_fields($product);
        $fields['_gc_expiration'] = '';

        foreach ($fields as $key => $field) {
            if (!empty($values[$key])) {
                $item->add_meta_data($key, $values[$key]);
            }
        }
    }
}
$wc_gift_card_fields = new WC_Custom_Gift_Card_Fields();
$wc_gift_card_fields->init();
