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
        if ( class_exists( 'WC_Software' ) || class_exists( 'WooCommerce_API_Manager' ) ) {
            return;
        }

        $order = wc_get_order( $order_id );

        if ( ! $order ) {
            return;
        }

        $licenses = array();

        foreach ( $order->get_items( 'line_item' ) as $item_id => $item ) {
            $key = '_appsero_order_license_for_product_' . $item->get_product_id();

            $license = get_post_meta( $order->get_id(), $key, true );

            if ( isset( $license['status'] ) && $license['status'] == 1 ) {
                $license['item_name'] = $item->get_name();
                $licenses[] = $license;
            }
        }

        if( ! count( $licenses ) )
            return '';

        wp_enqueue_style( 'ashp-my-account' );
        wp_enqueue_script( 'ashp-my-account' );
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
                        foreach ( $licenses as $license ) :
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
                                <a href="<?php echo $license['download_url']; ?>" class="button">
                                    <?php echo sanitize_title( $license['item_name'] ); ?>.zip
                                </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php
                            if (! isset( $license['bundle_products'] ) ) {
                                continue;
                            }
                            $this->show_bundle_products( $license );
                        endforeach;
                        ?>
                </tbody>
            </table>
        </section>
        <?php
    }

    /**
     * Show bundle products for the bundle
     */
    private function show_bundle_products( $products ) {
        ?>
        <tr>
            <th colspan="3" style="text-align: center">
                Products in <?php echo $products['item_name']?>
            </th>
        </tr>
        <?php
        foreach ($products['bundle_products'] as $product) {
            ?>
            <tr>
                <td colspan="2"><?php echo $product['name'] ; ?></td>
                <td>
                    <?php if (! empty($product['download_url'])): ?>
                        <a href="<?php echo esc_url( $product['download_url'] ); ?>" class="button">
                            <?php echo esc_html( sanitize_title( $product['slug'] ) ); ?>.zip
                        </a>
                    <?php endif; ?>
                </td>
            </tr>
            <?php
        }
    }

}
