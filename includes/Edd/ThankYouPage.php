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
        wp_enqueue_style( 'ashp-my-account' );
        wp_enqueue_script( 'ashp-my-account' );

        $licenses = array();

        foreach ( $payment->downloads as $line_download ) {
            $key = '_appsero_order_license_for_product_' . $line_download['id'];
            $license = get_post_meta( $payment->ID, $key, true );

            if ( isset( $license['status'] ) && $license['status'] == 1 ) {

                $download = new \EDD_Download( $line_download['id'] );
                $price_name = '';

                if ( ! empty( $line_download['options']['price_id'] ) ) {
                    $price_name = edd_get_price_option_name( $line_download['id'], $line_download['options']['price_id'], $payment->ID );
                }

                $license['item_name'] = $download->get_name() . ' ' . $price_name;
                $licenses[] = $license;
            }
        }

        if( ! count( $licenses ) )
            return '';

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
                        foreach ( $licenses as $license ) {
                            $this->license_and_download_item( $license );
                        }
                    ?>
                </tbody>
            </table>
        <?php
    }


    /**
     * Show single license and download
     */
    private function license_and_download_item( $license ) {

        ?>
        <tr>
            <td>
                <span class="tooltip">
                    <span class="license-key-code"><?php echo esc_html( $license['key'] ); ?></span>
                    <span class="tooltiptext">Click to Copy</span>
                </span>
            </td>
            <td><?php echo $license['expire_date'] ? date( 'M d, Y', strtotime( $license['expire_date'] ) ) : 'Lifetime'; ?></td>
            <td>
                <?php if (! empty($license['download_url'])): ?>
                    <a href="<?php echo esc_url( $license['download_url'] ); ?>" class="button">
                        <?php echo esc_html( sanitize_title( $license['item_name'] ) ); ?>.zip
                    </a>
                <?php endif; ?>
            </td>
        </tr>
        <?php
    }

}
