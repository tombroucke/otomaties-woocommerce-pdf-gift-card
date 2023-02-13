<?php
namespace Otomaties\WooCommerce\GiftCard;

use setasign\Fpdi\Fpdi;

class GiftCardPDF
{

    private $gift_card;
    private $pdf;
    private $properties;
    private $filepath;
    private $filename = '';

    public function __construct(GiftCardBase $gift_card)
    {

        $this->properties = apply_filters(
            'gc_pdf_properties',
            array(
                'orientation' => 'L',
                'unit' => 'mm',
                'width' => 210,
                'height' => 100,
                'template' => __DIR__ . '/../public/static/gift_card.pdf',
            ),
            $gift_card
        );

        $this->gift_card    = $gift_card;
        $this->init();
    }

    public function init()
    {
        // Set upload dir
        $wp_upload_dir      = wp_upload_dir();
        $upload_dir         = trailingslashit($wp_upload_dir['basedir']) . 'gift_cards/';
        $this->filename     = $this->gift_card->filename();
        if (! file_exists($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        $this->filepath     = $upload_dir . $this->filename;

        // Set font path
        if (! defined('FPDF_FONTPATH')) {
            define('FPDF_FONTPATH', apply_filters('gc_pdf_fontpath', dirname(__FILE__) . '/../fpdf-fonts'));
        }
        $this->createPdf();
    }

    private function createPdf()
    {

        $this->pdf = new Fpdi(
            $this->properties['orientation'],
            $this->properties['unit'],
            array( $this->properties['width'], $this->properties['height'] )
        );
        $this->pdf->AddFont('Lato', '', 'Lato-Regular.php');
        $this->pdf->AddFont('Lato', 'B', 'Lato-Bold.php');
        $this->pdf->SetAutoPageBreak(false, 0);

        do_action('gc_pdf_before_pdf', $this->pdf);
        $pagecount = $this->pdf->setSourceFile($this->properties['template']);
        for ($pageNo = 1; $pageNo <= $pagecount; $pageNo++) {
            $tplIdx = $this->pdf->importPage($pageNo);

            $this->pdf->AddPage('L');
            $this->pdf->useTemplate($tplIdx, 0, 0, $this->properties['width'], $this->properties['height'], true);
            $this->pdf->SetFont('Arial');
            $this->pdf->SetFontSize(11);

            $default_field = apply_filters('gc_pdf_default_field', array(
                'font' => array( 'Lato', '', 10 ),
                'x' => -1,
                'y' => -1,
                'width' => 80,
                'height' => 5,
                'value' => '',
                'border' => 0,
                'newline' => 1,
                'color' => array( 35, 31, 32 ),
                'align' => 'L',
                'margin_top' => 0,
            ));

            $pdf_fields = array(
                'data' => array(
                    'x' => 13,
                    'y' => 15,
                    'fields' => array(
                        'amount' => array(
                            'font' => array( 'Lato', 'B', 25 ),
                            'value' => str_replace(
                                ',00',
                                '',
                                html_entity_decode(wp_strip_all_tags(wc_price($this->gift_card->amount())))
                            ),
                            'color' => array( 209, 32, 39 ),
                        ),
                    ),
                ),
                'content' => array(
                    'x' => 40,
                    'y' => 48,
                    'fields' => array(
                        'sender' => array(
                            'value' => $this->gift_card->sender(),
                        ),
                        'recipient' => array(
                            'value' => $this->gift_card->recipient(),
                            'margin_top' => 2.7,
                        ),
                        'message' => array(
                            'value' => preg_replace(
                                "/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/",
                                "\n",
                                $this->gift_card->message()
                            ),
                            'margin_top' => 2.7,
                        ),
                    ),
                ),
                'extra_information' => array(
                    'x' => 13,
                    'y' => 88,
                    'fields' => array(
                        'valid_untill' => array(
                            'value' => $this->gift_card->expiration() ?  sprintf(
                                __('Valid untill %s', 'otomaties-wc-giftcard'),
                                date('d/m/Y', $this->gift_card->expiration())
                            ) : '',
                            'font' => array( 'Lato', '', 8 ),
                        ),
                    ),
                ),
            );

            if (apply_filters('gc_create_coupon', true, $this->gift_card->item())) {
                $pdf_fields['content']['fields']['coupon'] = array(
                    'value' => $this->gift_card->couponCode(),
                    'margin_top' => 2.7,
                );
            }

            $field_groups = apply_filters('gc_pdf_fields', $pdf_fields, $this->gift_card);
            foreach ($field_groups as $key => $field_group) {
                $this->pdf->setXY($field_group['x'], $field_group['y']);

                foreach ($field_group['fields'] as $key => $field) {
                    $field = wp_parse_args($field, $default_field);

                    $y = $this->pdf->getY();
                    $x = ( $field['x'] > -1 ? $field['x'] : $field_group['x'] );
                    $y = ( $field['y'] > -1 ? $field['y'] : $y + $field['margin_top'] );
                    $this->pdf->setXY($x, $y);

                    call_user_func_array(array( $this->pdf, 'SetTextColor' ), $field['color']);
                    call_user_func_array(array( $this->pdf, 'SetFont' ), $field['font']);

                    $this->pdf->MultiCell(
                        $field['width'],
                        $field['height'],
                        iconv('UTF-8', 'windows-1252', $field['value']),
                        $field['border'],
                        $field['align']
                    );
                }
            }
        }
    }

    public function display()
    {

        $this->pdf->Output($this->filename, 'I');
    }

    public function download()
    {

        $this->pdf->Output('D', $this->filename);
    }

    public function file()
    {
        $this->pdf->Output($this->filepath, 'F');
        return $this->filepath;
    }
}
