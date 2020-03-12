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

        wp_safe_redirect( admin_url( 'edit.php?post_type=page&appsero=pages_created' ) );
        exit;
    }

}
