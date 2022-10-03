<?php
namespace Otomaties\WooCommerce\Gift_Card;

class WC_Custom_Gift_Card_Field implements \ArrayAccess
{

    private $args;

    public function __construct($args)
    {

        $defaults = array(
            'identifier' => '',
            'type' => 'text',
            'maxlength' => 9999,
            'label' => null,
            'placeholder' => '',
            'required' => false,
            'value' => '',
            'display' => true,
            'data' => array()
        );
        $this->args = wp_parse_args($args, $defaults);
    }

    public function __toString()
    {
        return $this->args['identifier'];
    }

    public function offsetSet(mixed $offset, mixed $value) : void
    {
        if (is_null($offset)) {
            $this->args[] = $value;
        } else {
            $this->args[$offset] = $value;
        }
    }

    public function offsetExists(mixed $offset) : bool
    {
        return isset($this->args[$offset]);
    }

    public function offsetUnset(mixed $offset) : void
    {
        unset($this->args[$offset]);
    }

    public function offsetGet(mixed $offset) : mixed
    {
        return isset($this->args[$offset]) ? $this->args[$offset] : null;
    }

    public function display()
    {
        return $this->args['display'];
    }
}
