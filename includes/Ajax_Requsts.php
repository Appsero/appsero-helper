<?php
namespace Appsero\Helper;

class Ajax_Requsts {

    /**
     * Constructor
     */
    public function __construct() {

        add_action( 'wp_ajax_appsero_remove_activation', [ $this, 'remove_activation' ] );

    }

    /**
     * Remove activation
     */
    public function remove_activation() {
        if ( ! isset( $_POST['source_id'], $_POST['activation_id'], $_POST['product_id'], $_POST['license_id'] ) ) {
            wp_send_json_error();
        }

        $route = 'public/licenses/' . $_POST['source_id'] . '/activations/' . $_POST['activation_id'];

        $body = [
            'user_id'    => get_current_user_id(),
        ];

        $response = appsero_helper_remote_post( $route, $body, 'PATCH' );
        // $response_code = wp_remote_retrieve_response_code( $response );
        $response = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( isset( $response['success'] ) && $response['success'] ) {
            // Delete local DB record
            $license = get_appsero_license( $_POST['license_id'] );

            $new_activations = array_map( function ( $activation ) {
                if ( $_POST['activation_id'] == $activation['id'] ) {
                    $activation['is_active'] = 0;
                }

                return $activation;
            }, $license['activations'] );

            update_appsero_license( $license['id'], [
                'activations' => json_encode( $new_activations ),
            ] );

            wp_send_json_success();
        }

        wp_send_json_error();
    }

}
