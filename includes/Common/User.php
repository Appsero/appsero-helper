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
        $name = appsero_split_name( $customer['name'] );

        return appsero_create_customer(
            $customer['email'],
            $name[0],
            $name[1]
        );
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
