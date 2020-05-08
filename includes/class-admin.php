<?php
namespace Otomaties\WooCommerce\Gift_Card;

class WC_Custom_Gift_Card_Admin{

	public function init(){
		add_filter( 'woocommerce_get_sections_products', array( $this, 'gift_card_settings' ) );
		add_filter( 'woocommerce_get_settings_products', array( $this, 'gift_card_all_settings' ), 10, 2 );

	}

	public function gift_card_settings( $sections ) {

		$sections['gift_card'] = __( 'Gift Card', 'otomaties-wc-giftcard' );
		return $sections;
		
	}

	public function gift_card_all_settings( $settings, $current_section ) {

		if ( $current_section == 'gift_card' ) {
			$settings = array();

			$settings[] = array( 'name' => __( 'Gift Card Settings', 'otomaties-wc-giftcard' ), 'type' => 'title' );

			$settings[] = array(
				'name'     => __( 'Debug gift card', 'otomaties-wc-giftcard' ),
				'desc_tip' => __( 'Check this box if you are designing your gift card. ', 'otomaties-wc-giftcard' ),
				'id'       => 'gc_debug',
				'type'     => 'checkbox',
				'desc'     => __( 'Display gift card in browser instead of downloading.', 'otomaties-wc-giftcard' ),
			);

			$settings[] = array( 'type' => 'sectionend', 'id' => 'gift_card' );
			return $settings;
		}

		return $settings;
	}
}
$wc_gift_card_admin = new WC_Custom_Gift_Card_Admin();
$wc_gift_card_admin->init();

