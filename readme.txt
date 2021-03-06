=== Otomaties Woocommerce Pdf Gift Card ===
Contributors: tompoezie
Donate link: https://example.com/
Tags: woocommerce, coupon, gift card, 
Requires at least: 5.0
Tested up to: 5.4.1
Requires PHP: 7.2
Stable tag: 1.2.6
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Sell PDF gift cards through WooCommerce with a custom design.

== Description ==

Customise the PDF gift card in your custom theme or plugin.

Disable custom message
\`add_filter('gc_get_fields', function ($fields) {
    unset($fields['_gc_message']);
    return $fields;
});\`

Add your own pdf background
\`add_filter('gc_pdf_properties', function ($properties) {
    $properties['template'] = App\asset_dir( 'files/gift_card.pdf' );
    return $properties;
});\`

Customize fields
\`add_filter('gc_pdf_fields', function ($fields, $gift_card) {
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
}, 99, 2);\`

== Changelog ==

*** Otomaties WooCommerce PDF Gift Card ***

= 1.2.6 =
* Added FR translation

= 1.2.5 =
* Better way to display meta in backend, without duplicating gift card meta

= 1.2.4 =
* Check if WooCommerce is active to avoid fatal error

= 1.2.3 =
* Default maxlength
* Trim message in cart & checkout, not in admin

= 1.2.2 =
* Bug fix
* Check if user can manage_woocommerce before downloading gift card
* Add settings page in WooCommerce->settings->products->gift card to enable debugging
* Remove empty lines from message

= 1.2.1 =
* Bug fix

= 1.2.0 =
* Initial release
