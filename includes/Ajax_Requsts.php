<?php
namespace Appsero\Helper;

class Ajax_Requsts {

    /**
     * Constructor
     */
    public function __construct() {
        // Remove activation from license page
        add_action( 'wp_ajax_appsero_remove_activation', [ $this, 'remove_activation' ] );

        // Appsero plugin choose; woo or edd
        add_action( 'wp_ajax_appsero_set_selling_plugin', [ $this, 'set_selling_plugin' ] );

        // Create Appsero pages
        add_action( 'wp_ajax_appsero_create_shortcode_pages', [ $this, 'create_shortcode_pages' ] );

        // Create order in Appsero so that user can view them
        add_action( 'wp_ajax_create_in_appsero_for_view', [ $this, 'create_in_appsero_for_view' ] );
    }

    /**
     * Remove activation
     */
    public function remove_activation() {
        check_ajax_referer( 'appsero-store-myaccount', 'security' );

        if ( ! isset( $_POST['source_id'], $_POST['activation_id'], $_POST['product_id'] ) ) {
            wp_send_json_error();
        }

        $route = 'public/licenses/' . sanitize_text_field( wp_unslash( $_POST['source_id'] ) );
        $route .= '/activations/' . sanitize_text_field( wp_unslash( $_POST['activation_id'] ) );

        $body = [
            'user_id' => get_current_user_id(),
        ];

        $response = appsero_helper_remote_post( $route, $body, 'PATCH' );
        // $response_code = wp_remote_retrieve_response_code( $response );
        $response = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( isset( $response['success'] ) && $response['success'] ) {
            wp_send_json_success();
        }

        wp_send_json_error();
    }

    /**
     * Set selling plugin if both plugin installed
     */
    public function set_selling_plugin() {
        check_ajax_referer( 'appsero-selling-plugin', 'security' );

        if ( ! empty( $_GET['selected'] ) ) {
            update_option( 'appsero_selling_plugin', sanitize_text_field( wp_unslash( $_GET['selected'] ) ) );
        }

        wp_safe_redirect( wp_get_referer() );
        exit;
    }

    /**
     * Create appsero pages
     */
    public function create_shortcode_pages() {
        check_ajax_referer( 'appsero-create-pages', 'security' );

        update_option( 'appsero_shortcode_pages_created_at', date( 'Y-m-d H:i:s' ) );

        if ( isset( $_GET['cancel'] ) ) {
            wp_safe_redirect( wp_get_referer() );
            exit;
        }

        wp_insert_post( [
            'post_title'   => 'Licenses',
            'post_content' => '[appsero_licenses]',
            'post_status'  => 'publish',
            'post_type'    => 'page',
            'meta_input'   => [
                'appsero_post_state' => 'Appsero Licenses'
            ],
        ] );

        wp_insert_post( [
            'post_title'   => 'Orders',
            'post_content' => '[appsero_orders]',
            'post_status'  => 'publish',
            'post_type'    => 'page',
            'meta_input'   => [
                'appsero_post_state' => 'Appsero Orders'
            ],
        ] );

        wp_insert_post( [
            'post_title'   => 'Downloads',
            'post_content' => '[appsero_downloads]',
            'post_status'  => 'publish',
            'post_type'    => 'page',
            'meta_input'   => [
                'appsero_post_state' => 'Appsero Downloads'
            ],
        ] );

        wp_insert_post( [
            'post_title'   => 'My Account',
            'post_content' => '[appsero_my_account]',
            'post_status'  => 'publish',
            'post_type'    => 'page',
            'meta_input'   => [
                'appsero_post_state' => 'Appsero My Account'
            ],
        ] );

        wp_safe_redirect( admin_url( 'edit.php?post_type=page&appsero=pages_created' ) );
        exit;
    }

    /**
     * Sync the order to Appsero if needed and redirect the admin to it.
     *
     * The redirect carries the store connection token, so only store managers
     * may reach it, and only from the order screen (nonce bound to the order).
     */
    public function create_in_appsero_for_view() {
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_die( esc_html__( 'You are not allowed to view this order on Appsero.', 'appsero-helper' ), 403 );
        }

        $order_id   = isset( $_GET['order_id'] ) ? absint( $_GET['order_id'] ) : 0;
        $product_id = isset( $_GET['product_id'] ) ? absint( $_GET['product_id'] ) : 0;

        if ( ! $order_id || ! $product_id ) {
            wp_die( esc_html__( 'No order found.', 'appsero-helper' ), 400 );
        }

        check_admin_referer( 'appsero-view-order-' . $order_id );

        $order = wc_get_order( $order_id );

        if ( ! is_a( $order, 'WC_Abstract_Order' ) ) {
            wp_die( esc_html__( 'Could not find any order associated with your given order ID.', 'appsero-helper' ), 404 );
        }

        $license = get_post_meta( $order->get_id(), '_appsero_order_license_for_product_' . $product_id, true );

        // Order not on Appsero yet, send it first
        if ( ! isset( $license['source_id'] ) ) {
            $request = new \Appsero\Helper\WooCommerce\SendRequests();
            $request->order_status_changed( $order->get_id(), null, null, $order );
        }

        $url = sprintf(
            '%spublic/view-orders/%d/products/%d?store_token=%s',
            get_appsero_api_url(),
            $order->get_id(),
            $product_id,
            rawurlencode( appsero_helper_connection_token() )
        );

        wp_redirect( $url );
        exit;
    }

}
