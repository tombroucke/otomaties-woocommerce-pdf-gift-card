<?php
namespace Otomaties\WooCommerce\GiftCard;

interface GiftCardBase {
    public function pdf();

    public function expiration() : ?\DateTime;

    public function filename() : string;

    public function amount() : float;

    public function sender() : string;

    public function recipient() : string;

    public function message() : string;

    public function item();

    public function couponCode() : string;
}
