<?php
namespace Otomaties\WooCommerce\Gift_Card;

class WC_Custom_Gift_Card_Handle_Checkout{

	public function init(){

		add_action( 'woocommerce_order_status_completed', array( $this, 'create_gift_card' ), 10);
		add_action( 'woocommerce_thankyou', array( $this, 'thankyou_create_gift_card' ), 10, 1 );

	}

	public function create_gift_card( $order_id ){


		$order = new \WC_Order($order_id);
		$files = array();
		$gcs = array();
		$autocomplete = true;

		if( !apply_filters( 'gc_email_gift_card', true, $order ) ){
			return;
		}

		foreach ($order->get_items() as $key => $item) {
			$product_id = $item->get_product_id();
			$product = wc_get_product($product_id);
			if( !in_array( $product->get_type(), Gift_Card_Controller::gift_card_products() ) ){
				$autocomplete = false;
				continue;
			}
			$gc = new WC_Custom_Gift_Card($item);
			$pdf = new WC_Custom_Gift_Card_PDF($gc);

			if( !$gc->get('_gc_mailed') ){
	        	array_push( $files, $pdf->file() );
	        	array_push( $gcs, $gc);
			}

		}

		if( !empty($files) ){
			$message = 'Inhoud e-mail';
	        add_filter('wp_mail_content_type', array( $this, 'set_html_content_type' ));
	        add_filter( 'wp_mail_from_name', array( $this, 'from_name' ) );
	        $mailed = wp_mail($gc->get_email(), __('Your gift card', 'otomaties-wc-giftcard'), $message, '', $files);
	        if( $mailed ){
	        	foreach ($gcs as $key => $gc) {
	        		$gc->set('_gc_mailed', true);
	        	}
	        }
	        remove_filter('wp_mail_content_type', array( $this, 'set_html_content_type' ));
	        remove_filter( 'wp_mail_from_name', array( $this, 'from_name' ) );
		}

		if( $autocomplete ){
        	$order->update_status( 'completed' );
		}


	}

    public function set_html_content_type($content_type)
    {
        return 'text/html';
    }

    public function from_name() {
    	return apply_filters( 'gc_email_from_name', get_bloginfo('name', 'display' ) );
    }

    public function thankyou_create_gift_card($order_id){
    	if( !apply_filters( 'gc_create_gift_card_thankyou_page', false, $order_id ) ) {
    		return;
    	}

    	if ( ! $order_id ){
	    	return;
    	}

	    $order = wc_get_order( $order_id );

	    if ( ( 'bacs' == get_post_meta($order_id, '_payment_method', true) ) || ( 'cod' == get_post_meta($order_id, '_payment_method', true) ) || ( 'cheque' == get_post_meta($order_id, '_payment_method', true) ) ) {
	        return;
	    }
	    $this->create_gift_card($order_id);
    }
}
$wc_gift_card_checkout = new WC_Custom_Gift_Card_Handle_Checkout();
$wc_gift_card_checkout->init();
