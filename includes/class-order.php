<?php
namespace Otomaties\WooCommerce\Gift_Card;

class WC_Custom_Gift_Card_Order{

	public function init(){

		add_action( 'admin_init', array( $this, 'download_gift_card' ) );
		add_filter( 'woocommerce_order_item_display_meta_key', array( $this, 'change_labels' ) );
		add_filter( 'woocommerce_order_item_display_meta_value', array( $this, 'change_values' ), 10, 3 );
		add_action( 'woocommerce_after_order_itemmeta', array($this,'download_link'), 10, 3 );
		add_filter( 'gc_meta_value_gc_message', array( $this, 'format_message' ) );

	}

	public function download_gift_card(){

		$item_id 	= filter_input(INPUT_GET, 'download_gift_card', FILTER_VALIDATE_INT);
		$order_id 	= filter_input(INPUT_GET, 'post', FILTER_VALIDATE_INT);

		if( $item_id && current_user_can( 'manage_woocommerce' ) ){
			$order = new \WC_Order($order_id);

			$items = $order->get_items();
			foreach ($items as $key => $item) {
				if( $key != $item_id ){
					continue;
				}
				$gc = new WC_Custom_Gift_Card($item);
				$pdf = new WC_Custom_Gift_Card_PDF($gc);
				if( get_option( 'gc_debug' ) && get_option( 'gc_debug' ) == 'yes' ) {
					$pdf->display();
				}
				else {
					$pdf->download();
				}
				die();
			}

		}

	}

	public function change_labels($meta_key){

		$fields_obj = new WC_Custom_Gift_Card_Fields();
		$fields 	= $fields_obj->get_fields();

		$other_labels = array(
			'_gc_coupon' => __('Coupon', 'otomaties-wc-giftcard'),
			'_gc_mailed' => __('Mailed', 'otomaties-wc-giftcard'),
			'_gc_expiration' => __('Expiration date', 'otomaties-wc-giftcard')
		);

		foreach ($other_labels as $key => $label) {
			$fields[$key] = array( 'label' => $label );
		}

		if( isset($fields[$meta_key]) && isset($fields[$meta_key]['label']) ){
			return $fields[$meta_key]['label'];
		}

		return $meta_key;

	}

	public function change_values($display_value, $meta, $order){

		if( $meta->key == '_gc_expiration' ){
			$display_value = date('d-m-Y', $meta->value);
		}
		elseif( $meta->key == '_gc_coupon' ){
			$display_value = sprintf( '<a target="_blank" href="%s">%s</a>', get_edit_post_link( $meta->value ), $meta->value );
		}
		return $display_value;

	}

	public function download_link($item_id, $item, $product){
		$url = add_query_arg( 'download_gift_card', $item_id, get_edit_post_link( get_the_ID() ) );
		if ( $item instanceof \WC_Order_Item_Product && ( wc_get_order_item_meta($item_id, '_gc_sender') || wc_get_order_item_meta($item_id, '_gc_receiver') )) {
			?>
			<a class="button button-secondary" href="<?php echo $url; ?>" target="_blank"><?php _e( 'Download gift card', 'otomaties-wc-giftcard' ); ?></a>
			<?php
		}

	}

	public function format_message( $value ) {
		return wpautop( $value );
	}
}
$wc_gift_card_order = new WC_Custom_Gift_Card_Order();
$wc_gift_card_order->init();
