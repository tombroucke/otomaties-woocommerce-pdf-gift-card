<?php
class WC_Product_Gift_Card extends \WC_Product_Simple
{
    protected $product_type = 'gift_card';

    public function __construct($product)
    {
        add_action('woocommerce_gift_card_add_to_cart', 'woocommerce_simple_add_to_cart', 30);
        $this->product_type = 'gift_card';
        parent::__construct($product);
    }

    public function get_type()
    {
        return 'gift_card';
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
            $this->get_permalink()
        ) : $this->get_permalink();

        return apply_filters('woocommerce_product_add_to_cart_url', $url, $this);
    }

    public static function adminCustomJs()
    {

        if (get_post_type() != 'product') {
            return;
        }
        ?>
        <script type='text/javascript'>
            jQuery( document ).ready( function($) {
                // Price tab
                $('.product_data_tabs .general_tab').addClass('show_if_gift_card').show();
                $('#general_product_data .pricing').addClass('show_if_gift_card').show();

                // Inventory tab
                $('.inventory_options').addClass('show_if_gift_card').show();
                $('#inventory_product_data ._manage_stock_field').addClass('show_if_gift_card').show();
                $('#inventory_product_data ._sold_individually_field').parent().addClass('show_if_gift_card').show();
                $('#inventory_product_data ._sold_individually_field').addClass('show_if_gift_card').show();
            });
        </script>
        <?php
    }

    public static function productCustomPriceField()
    {
        $expiration = get_post_meta(get_the_ID(), 'gc_expiration', true);
        ?>
        <p class="form-field gc_min_price_field show_if_gift_card">
            <label for="gc_min_price"><?php _e('Minimum price', 'otomaties-wc-giftcard'); ?></label>
            <input type="text" class="short" style="" name="gc_min_price" id="gc_min_price" value="<?php echo get_post_meta(get_the_ID(), 'gc_min_price', true); ?>" placeholder="">
            <span class="description"><?php _e('Leave this field blank to disable price input.', 'otomaties-wc-giftcard'); ?></span>
        </p>
        <p class=" form-field gc_expiration_field show_if_gift_card show_if_gift_card_variable">
            <label for="gc_expiration"><?php _e('Expiration date', 'otomaties-wc-giftcard') ?></label>
            <select style="" id="gc_expiration" name="gc_expiration" class="select short">
                <option value="none" <?php echo  $expiration == 'none' || ! $expiration ? 'selected' : ''; ?>><?php _e('None', 'otomaties-wc-giftcard'); ?></option>
                <option value="6_months" <?php echo  $expiration == '6_months' ? 'selected' : ''; ?>><?php _e('6 months', 'otomaties-wc-giftcard'); ?></option>
                <option value="1_year" <?php echo  $expiration == '1_year' ? 'selected' : ''; ?>><?php _e('1 Year', 'otomaties-wc-giftcard'); ?></option>
                <option value="1_5_year" <?php echo  $expiration == '1_5_year' ? 'selected' : ''; ?>><?php _e('1.5 Year', 'otomaties-wc-giftcard'); ?></option>
                <option value="2_years" <?php echo  $expiration == '2_years' ? 'selected' : ''; ?>><?php _e('2 Years', 'otomaties-wc-giftcard'); ?></option>
                <option value="2_5_years" <?php echo  $expiration == '2_5_years' ? 'selected' : ''; ?>><?php _e('2.5 Years', 'otomaties-wc-giftcard'); ?></option>
            </select>
            <span class="description"><?php _e('Gift card expires in ...', 'otomaties-wc-giftcard'); ?></span>
        </p>
        <?php
    }

    public static function saveProductCustomPriceField($post_id)
    {

        $custom_fields = [
            'gc_min_price',
            'gc_expiration',
        ];

        foreach ($custom_fields as $key => $custom_field) {
            if (isset($_POST[$custom_field])) {
                update_post_meta($post_id, $custom_field, esc_html($_POST[$custom_field]));
            } else {
                delete_post_meta($post_id, $custom_field);
            }
        }
    }

    public static function custom_price($price, $product)
    {
        $min_price = get_post_meta($product->get_ID(), 'gc_min_price', true);
        if ($product->get_type() == 'gift_card' && $min_price !== false) {
            if (is_single()) {
                return '<p class="price"><span class="woocommerce-Price-amount amount">€0,00</span></p>';
            } else {
                return '';
            }
        }

        return $price;
    }
}

add_action('admin_footer', ['WC_Product_Gift_Card', 'adminCustomJs']);
add_action('woocommerce_product_options_general_product_data', ['WC_Product_Gift_Card', 'productCustomPriceField']);
add_action('woocommerce_process_product_meta', ['WC_Product_Gift_Card', 'saveProductCustomPriceField']);
