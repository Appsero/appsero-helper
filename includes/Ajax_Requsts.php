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
    }

    /**
     * Remove activation
     */
    public function remove_activation() {
        if ( ! isset( $_POST['source_id'], $_POST['activation_id'], $_POST['product_id'] ) ) {
            wp_send_json_error();
        }

        $route = 'public/licenses/' . $_POST['source_id'] . '/activations/' . $_POST['activation_id'];

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
            update_option( 'appsero_selling_plugin', sanitize_text_field( $_GET['selected'] ) );
        }

        wp_safe_redirect( wp_get_referer() );
        exit;
    }

}
