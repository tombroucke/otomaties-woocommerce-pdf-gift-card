<?php
/**
 * Plugin Name:     Woocommerce PDF Gift Card
 * Description:     Sell PDF gift cards through WooCommerce with a custom design.
 * Author:          Tom Broucke
 * Author URI:      https://tombroucke.be
 * Text Domain:     otomaties-wc-giftcard
 * Domain Path:     /languages
 * Version:         1.0.0
 *
 * @package         Core
 */

namespace Otomaties\WooCommerce\Gift_Card;

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
        include 'vendor/autoload.php';

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

    private function init() {
    	add_action( 'wp_enqueue_scripts', array($this, 'enqueue_scripts' ) );
        add_filter( 'product_type_selector', array( $this, 'add_custom_products' ) );

        add_filter( 'woocommerce_data_stores', function ($stores) {
            $stores['product-gift_card_variable'] = 'WC_Product_Variable_Data_Store_CPT';
            return $stores;
        });
    }

    public static function load_textdomain()
    {
        load_plugin_textdomain('otomaties-wc-giftcard', false, plugin_basename(dirname(__FILE__)) . '/languages');
    }

    public function enqueue_scripts(){
    	wp_enqueue_script( 'otomaties-wc-giftcard', plugins_url( 'assets/woocommerce-pdf-gift-card.js', __FILE__ ) );
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

add_action('init', array( 'Otomaties\WooCommerce\Gift_Card\\Gift_Card_Controller', 'get_instance' ), 10);
Gift_Card_Controller::load_textdomain();
