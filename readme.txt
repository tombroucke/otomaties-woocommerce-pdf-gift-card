=== Otomaties Woocommerce Pdf Gift Card ===
Contributors: Tom Broucke
Donate link: https://example.com/
Tags: comments, spam
Requires at least: 4.5
Tested up to: 4.9.9
Stable tag: 0.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Sell PDF gift cards through WooCommerce with a custom design.

=== Description ===

Customise the PDF gift card in your custom theme or plugin.

add_filter('gc_get_fields', function ($fields) {
    unset($fields['_gc_message']);
    return $fields;
});

add_filter('gc_pdf_properties', function ($properties) {
    $properties['template'] = App\asset_dir( 'files/gift_card.pdf' );
    return $properties;
});

add_filter('gc_pdf_fields', function ($fields, $gift_card) {
    $fields['data']['x'] = 29;
    $fields['data']['y'] = 58;
    $fields['content']['x'] = 29;
    $fields['content']['y'] = 65.5;
    $fields['content']['fields']['recipient']['x'] = 95;
    $fields['content']['fields']['recipient']['y'] = 65.5;
    $fields['content']['fields']['coupon'] =  array(
        'value' => get_the_title($gift_card->get_coupon()),
        'margin_top' => 2.7,
        'x' => 95,
        'y' => 58
    );
    $fields['extra_information']['x'] = 12;
    $fields['extra_information']['y'] = 74;
    $fields['extra_information']['fields']['valid_untill']['font'] = array( 'Lato', '', 7);
    $fields['extra_information']['fields']['valid_untill']['align'] = 'R';
    $fields['extra_information']['fields']['valid_untill']['width'] = 132;
    unset($fields['data']['fields']['amount']['color']);
    unset($fields['data']['fields']['amount']['font']);
    unset($fields['content']['fields']['message']);
    unset($fields['content']['fields']['message']);
    return $fields;
}, 99, 2);