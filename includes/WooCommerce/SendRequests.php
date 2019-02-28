<?php
namespace Appsero\Helper\WooCommerce;

use Appsero\Helper\Traits\Hooker;

/**
 * SendRequests Class
 * Send request to appsero sever
 */
class SendRequests {

    use Hooker;

    public function __construct() {

        // Add or Update order with license
        $this->action( 'woocommerce_order_status_changed', 'order_status_changed', 20, 4 );
    }

    /**
     * Order status chnage
     */
    public function order_status_changed( $order_id, $status_from, $status_to, $order ) {
        require_once __DIR__ . '/Orders.php';

        foreach( $order->get_items( 'line_item' ) as $wooItem ) {
            $ordersObject = new Orders();
            $ordersObject->product_id = $wooItem->get_product_id();
            $orderData = $ordersObject->get_order_data( $order );
            $orderData['licenses'] = $this->get_order_licenses( $order, $ordersObject->product_id, $wooItem );

            $route = 'public/' . $ordersObject->product_id . '/update-order';

            appsero_helper_remote_post( $route, $orderData );
        }

    }

    /**
     * Get licenses of active add-on
     */
    private function get_order_licenses( $order, $product_id, $wooItem ) {
        require_once __DIR__ . '/Licenses.php';

        $licensesObject = new Licenses();
        $status = $order->get_status() == 'completed' ? 1 : 0;

        // if WooCommerce Software Addon Exists
        if ( class_exists( 'WC_Software' ) ) {
            return $this->woo_sa_licenses( $order, $product_id, $licensesObject, $status );
        }

        // if WooCommerce API Manager Exists
        if ( class_exists( 'WooCommerce_API_Manager' ) ) {
            return $this->woo_api_licenses( $order, $product_id, $licensesObject, $status, $wooItem );
        }

        return [];
    }

    /**
     * WooCommerce SA license
     */
    private function woo_sa_licenses( $order, $product_id, $licensesObject ) {
        global $wpdb;

        $order_id = $order->get_id();
        $software_id = get_post_meta( $product_id, '_software_product_id', true);

        $query = "SELECT * FROM {$wpdb->wc_software_licenses} WHERE `order_id` = {$order_id} AND `software_product_id` = '{$software_id}' ";
        $licenses = $wpdb->get_results( $query, ARRAY_A );

        foreach ( $licenses as $license ) {
            $licensesObject->get_woo_sa_license_data( $license, false, $status );
        }

        return $licensesObject->licenses;
    }

    /**
     * WooCommerce API licenses
     */
    private function woo_api_licenses( $order, $product_id, $licensesObject, $status, $wooItem ) {
        global $wpdb;

        $order_id = $order->get_id();
        $api_keys_exist = WC_AM_HELPERS()->order_api_keys_exist( $order_id );

        if ( $api_keys_exist && WC_AM_HELPERS()->is_api( $product_id ) ) {
            $quantity = $wooItem->get_quantity();
            $user_id = $order->get_user_id();

            for ( $loop = 0; $loop < $quantity; $loop++ ) {
                $metakey = '_api_license_key_' . $loop;
                $license_key = get_post_meta( $order_id, $metakey, true );
                $license_data = get_user_meta( $user_id, $wpdb->get_blog_prefix() . WC_AM_HELPERS()->user_meta_key_orders, true );

                if ( ! empty( $license_key ) && ! empty( $license_data ) ) {
                    $licensesObject->get_woo_api_license_data( $license_key, false, $status, $license_data );
                }
            } // End for
        }

        return $licensesObject->licenses;
    }

}
