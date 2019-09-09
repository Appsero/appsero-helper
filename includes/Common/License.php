<?php
namespace Appsero\Helper\Common;

use WP_Error;
use WP_REST_Response;

class License {

    /**
     * Update navite appsero licenses activations
     */
    public function update_native_license_activations( $request ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'appsero_licenses';
        $source_id = absint( $request->get_param( 'source_id' ) );

        $appsero_license = $wpdb->get_row( "SELECT * FROM {$table_name} WHERE `source_id` = " . $source_id . " LIMIT 1", ARRAY_A );

        $data = [
            'status'           => absint( $request->get_param( 'status' ) ),
            'activation_limit' => absint( $request->get_param( 'activation_limit' ) ),
            'expire_date'      => $request->get_param( 'expire_date' ),
            'activations'      => json_encode( $request->get_param( 'activations' ) ),
        ];

        if ( $appsero_license ) {
            $wpdb->update( $table_name, $data, [
                'id' => $appsero_license['id']
            ]);
        }

        return new WP_REST_Response( [
            'success' => true,
        ] );
    }

    /**
     * Create native licnese
     */
    public function create_native_license( $request ) {
        require_once ASHP_ROOT_PATH . '/includes/NativeLicense.php';

        $license = new \Appsero\Helper\NativeLicense();

        return $license->create( $request );
    }

    /**
     * Parameters of create native license
     */
    public function create_native_license_params() {
        return [
            'id' => [
                'description'       => __( 'Unique identifier of the license.', 'appsero-helper' ),
                'type'              => 'integer',
                'sanitize_callback' => 'absint',
                'required'          => true,
            ],
            'key' => [
                'description'       => __( 'Unique hash for the license.', 'appsero-helper' ),
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'required'          => true,
            ],
            'activation_limit' => [
                'description'       => __( 'Limit for license activation limit.', 'appsero-helper' ),
                'type'              => 'integer',
                'required'          => false,
                'default'           => 0,
                'sanitize_callback' => 'absint',
            ],
            'status' => [
                'description'       => __( 'Status of a license.', 'appsero-helper' ),
                'type'              => 'integer',
                'required'          => true,
                'sanitize_callback' => 'absint',
            ],
            'expire_date' => [
                'description'       => __( 'License expire date and time.', 'appsero-helper' ),
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_expire_date_field',
                'required'          => true,
            ],
            'product_name' => [
                'description'       => __( 'Product name for license.', 'appsero-helper' ),
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'required'          => true,
            ],
            'variation_name' => [
                'description'       => __( 'Variation name for license.', 'appsero-helper' ),
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'required'          => false,
                'default'           => '',
            ],
            'customer' => [
                'description'       => __( 'License customer information.', 'appsero-helper' ),
                'type'              => 'object',
                'required'          => true,
                'properties'        => [
                    'email' => [
                        'description'       => __( 'License customer email address.', 'appsero-helper' ),
                        'type'              => 'string',
                        'sanitize_callback' => 'sanitize_text_field',
                        'required'          => true,
                        'format'            => 'email',
                    ],
                    'name' => [
                        'description'       => __( 'License customer name.', 'appsero-helper' ),
                        'type'              => 'string',
                        'sanitize_callback' => 'sanitize_text_field',
                        'required'          => true,
                    ],
                ],
                'validate_callback' => 'appsero_object_validate_callback',
            ],
        ];
    }

}
