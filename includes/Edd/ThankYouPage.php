<?php
namespace Appsero\Helper\EDD;

class ThankYouPage {

    /**
     * Constructor
     */
    public function __construct() {
        add_action( 'edd_payment_receipt_after_table', [ $this, 'show_license_and_download' ], 10, 2 );
    }

    /**
     * Show license and download
     */
    public function show_license_and_download( $payment_post, $receipt_args ) {
        $payment = new \EDD_Payment( $payment_post->ID );
        ?>
            <h3>License and Download</h3>
            <table class="edd-table">
                <thead>
                    <tr>
                        <th>License Key</th>
                        <th>Expire Date</th>
                        <th>Download</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                        foreach ( $payment->downloads as $line_download ) {
                            $this->license_and_download_item( $payment, $line_download );
                        }
                    ?>
                </tbody>
            </table>
        <?php
    }


    /**
     * Show single license and download
     */
    private function license_and_download_item( $payment, $line_download ) {
        $key = '_appsero_order_license_for_product_' . $line_download['id'];
        $license = get_post_meta( $payment->ID, $key, true );
        $download = new \EDD_Download( $line_download['id'] );
        $price_name = '';

        if ( ! isset( $license['status'] ) || 1 != $license['status'] ) {
            return;
        }

        if ( ! empty( $line_download['options']['price_id'] ) ) {
            $price_name = edd_get_price_option_name( $line_download['id'], $line_download['options']['price_id'], $payment->ID );
        }
        ?>
        <tr>
            <td><?php echo esc_html( $license['key'] ); ?></td>
            <td><?php echo $license['expire_date'] ? date( 'M d, Y', strtotime( $license['expire_date'] ) ) : 'Lifetime'; ?></td>
            <td>
                <a href="<?php echo esc_url( $license['download_url'] ); ?>" class="button">
                    <?php echo esc_html( sanitize_title( $download->get_name() . ' ' . $price_name ) ); ?>.zip
                </a>
            </td>
        </tr>
        <?php
    }

}
