<?php
namespace Appsero\Helper\WooCommerce;

class MetaBox {

    /**
     * Constructor
     */
    public function __construct() {
        add_action( 'add_meta_boxes', [ $this, 'register_meta_boxes' ] );
    }

    /**
     * Register meta all boxes
     */
    public function register_meta_boxes() {
        add_meta_box( 'woocommerce-order-view-on-appsero', 'Appsero', [ $this, 'view_on_appsero_callback' ], 'shop_order', 'side' );
    }

    /**
     * Output of `View on Appsero` metabox
     */
    public function view_on_appsero_callback() {
        global $post;

        $order = wc_get_order( $post->ID );

        if ( ! is_a( $order, 'WC_Abstract_Order' ) ) {
            return null;
        }

        $connected = get_option( 'appsero_connected_products', [] );
        $url       = get_appsero_api_url();
        $api_key   = appsero_helper_connection_token();

        $this->load_styles();

        foreach ( $order->get_items( 'line_item' ) as $wooItem ) {
            $product_id = $wooItem->get_product_id();

            // Check the product is connected with appsero
            if ( in_array( $product_id, $connected ) ) {
                $this->show_button( $order, $product_id, $url, $api_key );
            }
        }
    }

    /**
     * CSS for metabox
     */
    private function load_styles() {
        ?>
        <style type="text/css">
            #woocommerce-order-view-on-appsero h2.hndle,
            #woocommerce-order-view-on-appsero .handlediv {
                display: none;
            }
            a.view-on-appsero-button {
                display: flex;
                align-items: center;
                justify-content: space-between;
                text-decoration: none;
                padding-top: 6px;
            }
            a.view-on-appsero-button img {
                max-width: 32px;
                margin-right: 10px;
            }
            .view-on-appsero-button-left {
                display: table;
            }
            .view-on-appsero-button-left span {
                font-weight: bold;
                font-size: 14px;
                display: table-cell;
                vertical-align: middle;
            }
            a.view-on-appsero-button span.dashicons {
                font-size: 28px;
                height: 28px;
                width: 28px;
            }
        </style>
        <?php
    }

    /**
     * Show the button
     */
    private function show_button( $order, $product_id, $api_url, $api_key ) {
        $license = get_post_meta( $order->get_id(), '_appsero_order_license_for_product_' . $product_id, true );

        if ( isset( $license['source_id'] ) ) {
            $label = 'View on Appsero';
            $url   = sprintf( '%spublic/view-orders/%d/products/%d?store_token=%s', $api_url, $order->get_id(), $product_id, $api_key );
        } else {
            $label = 'Create in Appsero';
            $url   = admin_url( sprintf( 'admin-ajax.php?action=create_in_appsero_for_view&order_id=%s&product_id=%s', $order->get_id(), $product_id ) );
        }

        $html = '<div class="view-on-appsero-button-left">';
        $html .= '<img src="' . ASHP_ROOT_URL . 'assets/images/appsero-icon.png">';
        $html .= '<span>' . $label . '</span></div>';
        $html .= '<span class="dashicons dashicons-arrow-right-alt2"></span>';

        printf( '<a href="%s" class="view-on-appsero-button" target="_blank">%s</a>', $url, $html );
    }

}
