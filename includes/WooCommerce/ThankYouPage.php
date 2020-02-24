<?php
namespace Appsero\Helper\WooCommerce;

class ThankYouPage {

    /**
     * Constructor
     */
    public function __construct() {
        add_action( 'woocommerce_thankyou', [ $this, 'show_license_and_download' ], 9, 1 );
    }

    /**
     * Show license and download
     */
    public function show_license_and_download( $order_id ) {
        $order = wc_get_order( $order_id );

        if ( ! $order ) {
            return;
        }
        ?>
        <section class="woocommerce-order-details">
            <h2 class="woocommerce-order-details__title">License and Download</h2>
            <table class="woocommerce-table woocommerce-table--order-downloads shop_table shop_table_responsive order_details">
                <thead>
                    <tr>
                        <th>License Key</th>
                        <th>Expire Date</th>
                        <th>Download</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                        foreach ( $order->get_items( 'line_item' ) as $item_id => $item ) :
                            $key = '_appsero_order_license_for_product_' . $item->get_product_id();

                            $license = get_post_meta( $order_id, $key, true );

                            if ( 1 != $license['status'] ) {
                                continue;
                            }
                    ?>
                    <tr>
                        <td><?php echo $license['key']; ?></td>
                        <td><?php echo date( 'M d, Y', strtotime( $license['expire_date'] ) ); ?></td>
                        <td>
                            <a href="<?php echo $license['download_url']; ?>" class="button">
                                <?php echo sanitize_title( $item->get_name() ); ?>.zip
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>
        <?php
    }

}
