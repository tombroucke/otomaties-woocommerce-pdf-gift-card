<?php
namespace Otomaties\WooCommerce\Gift_Card;

class WC_Custom_Gift_Card_Order{

	public function init(){

		add_action( 'admin_init', array( $this, 'download_gift_card' ) );
		add_filter( 'woocommerce_order_item_display_meta_key', array( $this, 'change_labels' ) );
		add_filter( 'woocommerce_order_item_display_meta_value', array( $this, 'change_values' ), 10, 3 );
		add_action( 'woocommerce_after_order_itemmeta', array($this,'download_link'), 10, 3 );
		add_filter( 'woocommerce_order_item_get_formatted_meta_data', array( $this, 'custom_meta' ), 10, 2 );
		add_filter(	'woocommerce_hidden_order_itemmeta', array( $this, 'hide_hidden_meta' ), 10, 1);
		add_filter( 'gc_meta_value_gc_message', array( $this, 'shorten_message' ) );

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
		$fields['_gc_expiration'] = array(
			'label' => __('Expiration date', 'otomaties-wc-giftcard')
		);
		if( isset($fields[$meta_key]) && isset($fields[$meta_key]['label']) ){
			return $fields[$meta_key]['label'];
		}
		return $meta_key;

	}

	public function change_values($display_value, $meta, $order){

		if( $meta->key == '_gc_expiration' ){
			$display_value = date('d-m-Y', $meta->value);
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

	public function hide_hidden_meta($arr){
		$fields_obj = new WC_Custom_Gift_Card_Fields();
		$fields 	= $fields_obj->get_fields();
		foreach ($fields as $key => $field) {
			$arr[] = $key;
		}
    	return $arr;
	}

	public function custom_meta($formatted_meta, $item){

		if( get_class($item) != 'WC_Order_Item_Product' ){
			return $formatted_meta;
		}
		$product_id = $item->get_product_id();
		$product = wc_get_product( $product_id );
		if( !in_array( $product->get_type(), Gift_Card_Controller::gift_card_products() ) ){
			return $formatted_meta;
		}
		$fields_obj = new WC_Custom_Gift_Card_Fields($product);
		$fields 	= $fields_obj->get_fields($product);
		foreach ($fields as $key => $field) {
			if( !is_admin() && ( !$field->display() || !wc_get_order_item_meta($item->get_ID(), $key) ) ){
				continue;
			}
			$new_meta = (object) array(
				'key' => ltrim($key, '_'),
				'value' => wc_get_order_item_meta($item->get_ID(), $key),
				'display_key' => $field['label'],
				'display_value' => apply_filters( 'gc_meta_value_' . ltrim($key, '_') , wc_get_order_item_meta($item->get_ID(), $key) )
			);
			if( $key == '_gc_price' ){
				$new_meta->display_value = wc_price(wc_get_order_item_meta($item->get_ID(), '_gc_price'));
			}
			array_push($formatted_meta, $new_meta);
		}
		return $formatted_meta;
	}

	public function shorten_message( $value ) {
		return wp_trim_words( $value, 4, ' ...' );
	}
}
$wc_gift_card_order = new WC_Custom_Gift_Card_Order();
$wc_gift_card_order->init();
