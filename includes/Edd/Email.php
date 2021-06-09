<?php
namespace Appsero\Helper\Edd;

class Email {

    /**
     * Constructor
     */
    public function __construct() {
        add_action( 'edd_add_email_tags', [ $this, 'add_email_tags' ], 10, 1 );
    }

    /**
     * Add Email Tags
     */
    public function add_email_tags( $payment_id ) {
        $payment = new \EDD_Payment( $payment_id );
        edd_add_email_tag( 'appsero_license', 'Show License Key and Download Link', [ $this, 'show_license_and_download' ] );
    }

    /**
     * Show license and download
     */
    public function show_license_and_download( $payment_id ) {

        $payment = new \EDD_Payment( $payment_id );

        $send_license = 0;

        foreach ( $payment->downloads as $line_download ) {
            $key = '_appsero_order_license_for_product_' . $line_download['id'];
            $license = get_post_meta( $payment->ID, $key, true );

            if ( ( ! isset( $license['send_license'] ) || $license['send_license'] ) && isset( $license['status'] ) && 1 == $license['status'] ) {
                $send_license ++;
            }
        }

        if( ! $send_license )
            return '';

        $text_align  = is_rtl() ? 'right' : 'left';
        $margin_side = is_rtl() ? 'left' : 'right';


        $content = '
        <div style="margin-bottom: 40px">
            <h2>License and Download</h2>
            <table  class="td" cellspacing="0" cellpadding="6" style="width: 100%; font-family: \'Helvetica Neue\', Helvetica, Roboto, Arial, sans-serif;" border="1">
                <thead>
                <tr>
                    <th class="td" scope="col" style="text-align:' . $text_align .';">License Key</th>
                    <th>Expire Date</th>
                    <th>Download</th>
                </tr>
                </thead>
                <tbody>';

                foreach ( $payment->downloads as $line_download ) {
                    $content .= $this->license_and_download_item( $payment, $line_download );
                }

        $content .= '
                </tbody>
            </table>
        </div>';

        return $content;

    }

    /**
     * Show single license and download
     */
    private function license_and_download_item( $payment, $line_download ) {
        $key = '_appsero_order_license_for_product_' . $line_download['id'];
        $license = get_post_meta( $payment->ID, $key, true );

        if ( ( isset( $license['send_license']) && ! $license['send_license'] ) || ! isset( $license['status'] ) || 1 != $license['status'] ) {
            return '';
        }

        $download = new \EDD_Download( $line_download['id'] );
        $price_name = '';
        $text_align  = is_rtl() ? 'right' : 'left';

        if ( ! empty( $line_download['options']['price_id'] ) ) {
            $price_name = edd_get_price_option_name( $line_download['id'], $line_download['options']['price_id'], $payment->ID );
        }

        $td_style = "text-align:" . $text_align ."; vertical-align: middle; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif; word-wrap:break-word;";

        return '
        <tr>
            <td class="td" style="' . $td_style . '">' . esc_html( $license['key'] ) .'</td>
            <td class="td" style="' . $td_style . '">' .($license['expire_date'] ? date( 'M d, Y', strtotime( $license['expire_date'] ) ) : 'Lifetime') . '</td>
            <td class="td" style="' . $td_style . '">
                <a href="' . esc_url( $license['download_url'] ) . '" class="button">' . esc_html( sanitize_title( $download->get_name() . ' ' . $price_name ) ) . '.zip
                </a>
            </td>
        </tr>';
    }

}
