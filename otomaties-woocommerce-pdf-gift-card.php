<?php
/**
 * Plugin Name:     Woocommerce PDF Gift Card
 * Description:     Sell PDF gift cards through WooCommerce with a custom design.
 * Author:          Tom Broucke
 * Author URI:      https://tombroucke.be
 * Text Domain:     otomaties-wc-giftcard
 * Domain Path:     /languages
 * Version:           2.3.0
 *
 * @package         Core
 */

namespace Otomaties\WooCommerce\Gift_Card;

if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

class Gift_Card_Controller
{
    private static $instance = null;

    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self;
        }

        return self::$instance;
    }

    private function __construct()
    {
        $this->includes();
        $this->init();
    }

    private function includes()
    {

        if (file_exists(dirname(__FILE__) . '/vendor')) {
            include 'vendor/autoload.php';
        }

        include 'includes/class-assets.php';
        include 'includes/class-admin.php';
        include 'includes/class-custom-price.php';
        include 'includes/class-field.php';
        include 'includes/class-fields.php';
        include 'includes/class-gift-card.php';
        include 'includes/class-gift-card-pdf.php';
        include 'includes/class-gift-card-product.php';
        include 'includes/class-handle-checkout.php';
        include 'includes/class-order.php';
        include 'includes/class-product-gift-card.php';
        include 'includes/class-product-gift-card-variable.php';
    }

    private function init()
    {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts' ));
        add_filter('product_type_selector', array( $this, 'add_custom_products' ));

        add_filter('woocommerce_data_stores', function ($stores) {
            $stores['product-gift_card_variable'] = 'WC_Product_Variable_Data_Store_CPT';
            return $stores;
        });
    }

    public static function load_textdomain()
    {
        load_plugin_textdomain('otomaties-wc-giftcard', false, plugin_basename(dirname(__FILE__)) . '/languages');
    }

    public function enqueue_scripts()
    {
        if (is_singular('product')) {
            $product = wc_get_product(get_the_ID());
            if ($product->is_type('gift_card') || $product->is_type('gift_card_variable')) {
                $assets = new Assets();
                $assets->bundle('app')->enqueue()->localize('gift_card_vars', [
                    'strings' => [
                        'fieldRequired' => __('This field is required', 'otomaties-wc-giftcard'),
                        'validEmail' => __('Please enter a valid e-mailaddress', 'otomaties-wc-giftcard'),
                        'selectOption' => __('Please select an option', 'otomaties-wc-giftcard'),
                    ]
                ]);
            }
        }
    }

    public function add_custom_products($types)
    {
        $types[ 'gift_card' ] = __('Gift card', 'otomaties-wc-giftcard');
        $types[ 'gift_card_variable' ] = __('Variable Gift card', 'otomaties-wc-giftcard');
        return $types;
    }

    public static function gift_card_products()
    {
        return apply_filters('gc_product_types', array( 'gift_card', 'gift_card_variable' ));
    }
}

if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
    add_action('init', array( 'Otomaties\WooCommerce\Gift_Card\\Gift_Card_Controller', 'get_instance' ), 10);
    Gift_Card_Controller::load_textdomain();
}
