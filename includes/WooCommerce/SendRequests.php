<?php
namespace Appsero\Helper\WooCommerce;

use Appsero\Helper\Traits\Hooker;

/**
 * SendRequests Class
 * Send request to appsero sever
 */
class SendRequests {

    use Hooker;
    use UseCases\SendRequestsHelper;

    public function __construct() {
        // Add or Update order with license
        $this->action( 'woocommerce_order_status_changed', 'order_status_changed', 20, 4 );

        $this->action( 'before_delete_post', 'delete_order', 8, 1 );
    }

    /**
     * Order status chnage
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

                $orderData['licenses'] = $this->get_order_licenses( $order, $ordersObject->product_id, $wooItem );

                $route = 'public/' . $ordersObject->product_id . '/update-order';

                $api_response = appsero_helper_remote_post( $route, $orderData );
                $response = json_decode( wp_remote_retrieve_body( $api_response ), true );

                if ( isset( $response['license'] ) ) {
                    $this->create_appsero_license( $response['license'], $orderData, $ordersObject->product_id );
                }
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
     * Create appsero license from response
     */
    private function create_appsero_license( $license, $orderData, $product_id ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'appsero_licenses';

        $appsero_license = $wpdb->get_row( "SELECT * FROM {$table_name} WHERE `source_id` = " . $license['id'] . " LIMIT 1", ARRAY_A );

        if ( $appsero_license ) {
            // Update
            $wpdb->update( $table_name, [
                'product_id'       => $product_id,
                'variation_id'     => $orderData['variation_id'] ? $orderData['variation_id'] : null,
                'order_id'         => $orderData['id'],
                'user_id'          => $orderData['customer']['id'],
                'key'              => $license['key'],
                'status'           => $license['status'],
                'activation_limit' => $license['activation_limit'],
                'expire_date'      => $license['expire_date']['date'],
            ], [
                'id' => $appsero_license['id']
            ]);
        } else {
            // Create
            $wpdb->insert( $table_name, [
                'product_id'       => $product_id,
                'variation_id'     => $orderData['variation_id'] ? $orderData['variation_id'] : null,
                'order_id'         => $orderData['id'],
                'user_id'          => $orderData['customer']['id'],
                'key'              => $license['key'],
                'status'           => $license['status'],
                'activation_limit' => $license['activation_limit'],
                'expire_date'      => $license['expire_date']['date'],
                'source_id'        => $license['id'],
            ] );
        }
    }

}
