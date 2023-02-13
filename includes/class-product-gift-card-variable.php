<?php
class WC_Product_Gift_Card_Variable extends \WC_Product_Variable
{
    protected $product_type = 'gift_card_variable';

    protected $supports = [];

    public function __construct($product)
    {
        add_action('woocommerce_gift_card_variable_add_to_cart', 'woocommerce_variable_add_to_cart', 30);
        parent::__construct($product);
        $this->product_type = 'gift_card_variable';
        unset($this->supports['ajax_add_to_cart']);
    }

    public function get_type()
    {
        return 'gift_card_variable';
    }

    public function add_to_cart_text()
    {
        return apply_filters(
            'woocommerce_product_add_to_cart_text',
            $this->is_purchasable() ? __('Choose', 'otomaties-wc-giftcard') : __('Read more', 'otomaties-wc-giftcard'),
            $this
        );
    }

    public function add_to_cart_url()
    {
        $url = $this->is_purchasable() ? remove_query_arg(
            'added-to-cart',
            add_query_arg(
                array(
                    'variation_id' => $this->get_id(),
                    'add-to-cart'  => $this->get_parent_id(),
                ),
                $this->get_permalink()
            )
        ) : $this->get_permalink();
        return apply_filters('woocommerce_product_add_to_cart_url', $url, $this);
    }

    public function is_purchasable()
    {
        return true;
    }

    public static function adminCustomJs()
    {

        if ('product' != get_post_type()) {
            return;
        }
        ?>
        <script type='text/javascript'>
            jQuery( document ).ready( function($) {
                // Price tab
                $('.product_data_tabs .general_tab').addClass('show_if_gift_card_variable').show();
                $('#general_product_data .pricing').addClass('show_if_gift_card_variable').show();
                $('.product_data_tabs .variations_tab').addClass('show_if_gift_card_variable').show();

                // Inventory tab
                $('.inventory_options').addClass('show_if_gift_card_variable').show();
                $('#inventory_product_data ._manage_stock_field').addClass('show_if_gift_card_variable').show();
                $('#inventory_product_data ._sold_individually_field').parent().addClass('show_if_gift_card_variable').show();
                $('#inventory_product_data ._sold_individually_field').addClass('show_if_gift_card_variable').show();
                $('#inventory_product_data ._sold_individually_field').addClass('show_if_gift_card_variable').show();
                $('#product_attributes .enable_variation').addClass('show_if_gift_card_variable').show();
            });
        </script>
        <?php
    }
}

add_action('admin_footer', ['WC_Product_Gift_Card_Variable', 'adminCustomJs']);
