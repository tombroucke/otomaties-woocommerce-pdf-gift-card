<?php
namespace Otomaties\WooCommerce\Gift_Card;

class WC_Custom_Gift_Card {

	private $order_item;
	private $order;
	private $index;

	public function __construct( $order_item, $index ) {
		$this->order_item = $order_item;
		$this->order = new \WC_Order( $order_item->get_order_id() );
		$this->index = $index;
	}

	public function index() {
		return $this->index;
	}

	public function get_order_id() {
		return $this->order->get_ID();
	}

	public function get_order_item() {
		return $this->order_item;
	}

	public function get_product_id() {
		return $this->order_item->get_product_id();
	}

	public function get_email_recipient() {
		return get_post_meta( $this->get_order_id(), '_billing_first_name', true );
	}

	public function get_sender() {
		return $this->get( '_gc_sender' );
	}

	public function get_amount() {
		return round( $this->order_item->get_total(), 2 );
	}

	public function get_amount_incl_tax() {
		return round( $this->order_item->get_total() + $this->order_item->get_total_tax(), 2 );
	}

	public function get_recipient() {
		return $this->get( '_gc_recipient' );
	}

	public function get_message() {
		return $this->get( '_gc_message' );
	}

	public function get_expiration() {
		return $this->get( '_gc_expiration' );
	}

	public function get_coupon() {
		$coupon = $this->get( '_gc_coupon_' . $this->index() );
		if( !$coupon ) {
			$coupon = $this->get( '_gc_coupon' );
		}
		return $coupon;
	}

	public function pdf() {
		return new WC_Custom_Gift_Card_PDF( $this );
	}

	public function set( $param, $value ) {
		$this->order_item->update_meta_data( $param, $value );
		$this->order_item->save();
	}

	public function get( $param ) {
		return $this->order_item->get_meta( $param );
	}
}
