<?php
namespace Appsero\Helper\Edd;

use EDD_Payment;
use EDD_SL_License;
use EDD_SL_Download;
use Appsero\Helper\Traits\Hooker;
use Appsero\Helper\NativeLicense;
use Appsero\Helper\Edd\UseCases\SendRequestsHelper;

/**
 * SendRequests Class
 * Send request to appsero sever
 */
class SendRequests {

    use Hooker, SendRequestsHelper;

    /**
     * Constructor of EDD SendRequests class
     */
    public function __construct() {
        // TODO: edd_complete_purchase
        // EDD add new order and license
        //$this->action( 'edd_complete_download_purchase', 'add_new_order_and_license', 20, 5 );
        $this->action( 'edd_complete_purchase', 'complete_purchase', 20, 3 );
        $this->action( 'edd_subscription_post_create', 'subscription_post_create', 20, 2 );

        // EDD update order and license status
        $this->action( 'edd_update_payment_status', 'update_order_and_license', 20, 3 );

        // Cancel order on payment deletion
        $this->action( 'edd_payment_delete', 'delete_order_and_license', 20, 1 );

        // Update license when an item is removed from a payment
        $this->action( 'edd_remove_download_from_payment', 'delete_order_and_license', 20, 2 );
    }

    /**
     * Send request to add order with license
     */
    public function add_new_order_and_license( $download_id = 0, $payment_id = 0, $type = 'default', $cart_item = [], $cart_index = 0 ) {
        $connected = get_option( 'appsero_connected_products', [] );

        // Check the product is connected with appsero
        if ( in_array( $download_id, $connected ) ) {
            $payment = new EDD_Payment( $payment_id );

            $this->add_or_update_order_and_license( $payment, $download_id );
        }
    }

    /**
     * EDD update order and license
     */
    public function update_order_and_license( $payment_id, $new_status, $old_status ) {

        if ( 'pending' == $old_status && 'publish' == $new_status ) {
            return;
        }

        $payment = new EDD_Payment( $payment_id );
        $connected = get_option( 'appsero_connected_products', [] );

        foreach ( $payment->downloads as $download ) {
            // Check the product is connected with appsero
            if ( in_array( $download['id'], $connected ) ) {
                $this->add_or_update_order_and_license( $payment, $download['id'] );
            }
        }
    }

    /**
     * Update or create order and license
     */
    private function add_or_update_order_and_license( $payment, $download_id ) {
        require_once __DIR__ . '/Orders.php';

        $ordersObject = new Orders();
        $ordersObject->download_id = $download_id;
        $order = $ordersObject->get_order_data( $payment );

        $order['licenses'] = $this->get_order_licenses( $payment->ID, $download_id );

        $route = 'public/' . $download_id . '/update-order';

        $api_response = appsero_helper_remote_post( $route, $order );
        $response = json_decode( wp_remote_retrieve_body( $api_response ), true );

        $this->save_license_response( $response, $payment->ID, $download_id );
    }

    /**
     * Cancel order and license on delete order
     */
    public function delete_order_and_license( $payment_id, $download_id = null ) {
        $payment = new EDD_Payment( $payment_id );

        if ( empty( $download_id ) ) {
            foreach ( $payment->downloads as $download ) {
                $this->send_delete_order_and_license_request( $payment, $download['id'] );
            }
        } else {
            $this->send_delete_order_and_license_request( $payment, $download_id );
        }
    }

    /**
     * Send Delete request
     */
    private function send_delete_order_and_license_request( $payment, $download_id ) {
        $connected = get_option( 'appsero_connected_products', [] );

        // Check the product is connected with appsero
        if ( in_array( $download_id, $connected ) ) {
            $route = 'public/' . $download_id . '/delete-order/' . $payment->ID;

            appsero_helper_remote_post( $route, [] );
        }
    }

    /**
     * Save order license
     */
    private function save_license_response( $response, $payment_id, $download_id ) {
        if ( isset( $response['data'] ) && isset( $response['data']['source_id'] ) ) {
            $key = '_appsero_order_license_for_product_' . $download_id;

            update_post_meta( $payment_id, $key, [
                'source_id'    => $response['data']['source_id'],
                'key'          => $response['data']['key'],
                'status'       => $response['data']['status'],
                'download_url' => $response['data']['download_url'],
                'expire_date'  => $response['data']['expire_date'],
                'send_license' => isset( $response['data']['send_license'] ) ? $response['data']['send_license'] : true,
            ] );
        }
    }

    /**
     * Complete Purchase
     */
    public function complete_purchase( $payment_id, $payment, $customer ) {
        $connected = get_option( 'appsero_connected_products', [] );

        foreach ( $payment->downloads as $download ) {
            // Check the product is connected with appsero
            if ( in_array( $download['id'], $connected ) ) {
                $this->add_or_update_order_and_license( $payment, $download['id'] );
            }
        }
    }

    /**
     * After EDD subscription setup
     */
    public function subscription_post_create( $subscription_id, $args ) {
        $connected = get_option( 'appsero_connected_products', [] );

        // Check the product is connected with appsero
        if ( in_array( $args['product_id'], $connected ) ) {
            $payment = new EDD_Payment( $args['parent_payment_id'] );
            $this->add_or_update_order_and_license( $payment, $args['product_id'] );
        }
    }

}
