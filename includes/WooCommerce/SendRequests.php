<?php
namespace Appsero\Helper\WooCommerce;

use Appsero\Helper\Traits\Hooker;

/**
 * SendRequests Class
 * Send request to appsero sever
 */
class SendRequests {

    use UseCases\SendRequestsHelper;

    public function receive_order_status_changed( $order_id, $status_from, $status_to, $order ) {
        if (did_action('woocommerce_email_after_order_table')) {
            return;
        }
        $this->order_status_changed( $order_id, $status_from, $status_to, $order );
    }

    /**
     * Order status change
     */
    public function order_status_changed( $order_id, $status_from, $status_to, $order ) {
        require_once __DIR__ . '/Orders.php';

        $connected = get_option( 'appsero_connected_products', [] );

        foreach( $order->get_items( 'line_item' ) as $wooItem ) {
            $ordersObject = new Orders();
            $ordersObject->product_id = $wooItem->get_product_id();

            // Check the product is connected with appsero
            if ( in_array( $ordersObject->product_id, $connected ) ) {
                $orderData = $ordersObject->get_order_data( $order, $wooItem );
                $this->subscription = $ordersObject->subscription;

                $orderData['licenses'] = $this->get_order_licenses( $order, $ordersObject->product_id, $wooItem );

                $route = 'public/' . $ordersObject->product_id . '/update-order';

                $api_response = appsero_helper_remote_post( $route, $orderData );
                $response = json_decode( wp_remote_retrieve_body( $api_response ), true );

                $this->save_license_response( $response, $order_id, $ordersObject->product_id );
            }
        }
    }

    /**
     * Get licenses of active add-on
     */
    private function get_order_licenses( $order, $product_id, $wooItem ) {
        require_once __DIR__ . '/Licenses.php';

        $licensesObject = new Licenses();

        return $this->get_order_item_licenses( $order, $product_id, $licensesObject, $wooItem );
    }

    /**
     * Delete order
     */
    public function delete_order( $order_id ) {
        // We check if the global post type isn't order and just return
        global $post_type;
        if ( $post_type != 'shop_order' ) return;

        $order     = wc_get_order( $order_id );
        $connected = get_option( 'appsero_connected_products', [] );

        foreach ( $order->get_items( 'line_item' ) as $wooItem ) {
            $product_id = $wooItem->get_product_id();

            // Check the product is connected with appsero
            if ( in_array( $product_id, $connected ) ) {
                $route = 'public/' . $product_id . '/delete-order/' . $order_id;

                appsero_helper_remote_post( $route, [] );
            }
        }
    }

    /**
     * Save order license
     */
    private function save_license_response( $response, $order_id, $product_id ) {
        if ( isset( $response['data'] ) && isset( $response['data']['source_id'] ) ) {
            $key = '_appsero_order_license_for_product_' . $product_id;

            update_post_meta( $order_id, $key, [
                'source_id'    => $response['data']['source_id'],
                'key'          => $response['data']['key'],
                'status'       => $response['data']['status'],
                'download_url' => $response['data']['download_url'],
                'expire_date'  => $response['data']['expire_date'],
                'send_license' => isset( $response['data']['send_license'] ) ? $response['data']['send_license'] : true,
            ] );
        }
    }

}
