<?php
namespace Appsero\Helper\Edd;

use EDD_Payment;
use EDD_SL_License;
use EDD_SL_Download;
use Appsero\Helper\Traits\Hooker;

/**
 * SendRequests Class
 * Send request to appsero sever
 */
class SendRequests {

    use Hooker;

    /**
     * Constructor of EDD SendRequests class
     */
    public function __construct() {

        // EDD add new order and license
        $this->action( 'edd_complete_download_purchase', 'add_new_order_and_license', 20, 5 );

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
        $payment = new EDD_Payment( $payment_id );

        $this->add_or_update_order_and_license( $payment, $download_id );
    }

    /**
     * EDD update order and license
     */
    public function update_order_and_license( $payment_id, $new_status, $old_status ) {

        if ( 'pending' == $old_status && 'publish' == $new_status ) {
            return;
        }

        $payment = new EDD_Payment( $payment_id );

        foreach ( $payment->downloads as $download ) {
            $this->add_or_update_order_and_license( $payment, $download['id'] );
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

        appsero_helper_remote_post( $route, $order );
    }

    /**
     * Get license of an order
     */
    private function get_order_licenses( $payment_id, $download_id ) {
        if ( ! class_exists( 'EDD_SL_Download' ) ) return [];

        $purchased_download = new EDD_SL_Download( $download_id );
        if ( ! $purchased_download->is_bundled_download() && ! $purchased_download->licensing_enabled() ) {
            return [];
        }

        $licenses = edd_software_licensing()->get_licenses_of_purchase( $payment_id );

        $items = [];

        if ( false !== $licenses ) {
            require_once __DIR__ . '/Licenses.php';

            $licensesObject = new Licenses();

            foreach( $licenses as $license ) {
                $items[] = $licensesObject->get_license_data( $license, false );
            }
        }

        return $items;
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
        // TODO: Write code to send request
    }
}
