<?php
namespace Appsero\Helper\Common;

use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

class User {

    /**
     * Create native license
     */
    public function create_native_user( WP_REST_Request $request ) {
        $requested = $request->get_params();

        $user_id = $this->customer_first_or_create( $requested );

        return new WP_REST_Response( [
            'success' => true,
            'user_id' => $user_id,
        ] );
    }

    /**
     * Get user id or create new
     */
    private function customer_first_or_create( $customer ) {
        $exists = email_exists( $customer['email'] );

        if ( $exists ) {
            return $exists;
        }

        $name            = appsero_split_name( $customer['name'] );
        $random_password = wp_generate_password( 12, false );

        $userdata = [
            'user_pass'     => $random_password,
            'display_name'  => $name[0],
            'user_nicename' => $name[0],
            'first_name'    => $name[0],
            'last_name'     => $name[1],
            'user_login'    => $customer['email'],
            'user_email'    => $customer['email'],
            'role'          => 'subscriber',
        ];

        $user_id = wp_insert_user( $userdata );

        wp_send_new_user_notifications( $user_id, 'user' );

        return $user_id;
    }

    /**
     * User create requeset parameters
     */
    public function create_native_user_params() {

        return [
            'email' => [
                'description'       => __( 'Customer email address.', 'appsero-helper' ),
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'required'          => true,
                'format'            => 'email',
            ],
            'name' => [
                'description'       => __( 'Customer name.', 'appsero-helper' ),
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'required'          => true,
            ],
        ];
    }

}
