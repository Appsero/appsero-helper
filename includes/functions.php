<?php

/**
 * [appsero_api_collection_params description]
 *
 * @return [type] [description]
 */
function appsero_api_collection_params() {
    $params = [
        'page' => [
            'description'        => __( 'Current page of the collection.', 'appsero-helper' ),
            'type'               => 'integer',
            'default'            => 1,
            'sanitize_callback'  => 'absint',
            'validate_callback'  => 'rest_validate_request_arg',
            'minimum'            => 1,
        ],

        'per_page' => [
            'description'        => __( 'Maximum number of items to be returned in result set.', 'appsero-helper' ),
            'type'               => 'integer',
            'default'            => 10,
            'minimum'            => 1,
            'maximum'            => 100,
            'sanitize_callback'  => 'absint',
            'validate_callback'  => 'rest_validate_request_arg',
        ]
    ];

    return $params;
}

/**
 * Get license with activation patameters
 *
 * @return array
 */
function appsero_api_get_licenses_params() {
    $collection_params = appsero_api_collection_params();

    $license_param = [
        'product_id' => [
            'description'       => __( 'Unique identifier for the project.', 'appsero-helper' ),
            'type'              => 'integer',
            'sanitize_callback' => 'absint',
        ]
    ];

    return array_merge( $collection_params, $license_param );
}

/**
 * Parameters of add new activations
 *
 * @return array
 */
function appsero_api_update_or_create_activations_params() {
    $params = [
        'product_id' => [
            'description' => __( 'Unique identifier for the project.', 'appsero-helper' ),
            'type'        => 'integer',
        ],
        'license_key' => [
            'description'       => __( 'Unique identifier for the license.', 'appsero-helper' ),
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
        ],
        'site_url' => [
            'description'       => __( 'Site URL of active license.', 'appsero-helper' ),
            'type'              => 'string',
            'required'          => true,
            'validate_callback' => 'rest_validate_request_arg',
            'sanitize_callback' => 'sanitize_text_field',
        ],
        'status' => [
            'description'       => __( 'Status of a site.', 'appsero-helper' ),
            'type'              => 'integer',
            'default'           => null,
            'sanitize_callback' => 'absint',
            'validate_callback' => 'rest_validate_request_arg',
        ]
    ];

    return $params;
}

/**
 * Parameters of delete activations
 *
 * @return array
 */
function appsero_api_delete_activations_params() {
    $params = [
        'product_id' => [
            'description' => __( 'Unique identifier for the project.', 'appsero-helper' ),
            'type'        => 'integer',
        ],
        'license_key' => [
            'description'       => __( 'Unique identifier for the license.', 'appsero-helper' ),
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
        ],
        'site_url' => [
            'description'       => __( 'Site URL of active license.', 'appsero-helper' ),
            'type'              => 'string',
            'required'          => true,
            'validate_callback' => 'rest_validate_request_arg',
            'sanitize_callback' => 'sanitize_text_field',
        ],
    ];

    return $params;
}


/**
 * Parameters of change license status
 *
 * @return array
 */
function appsero_api_change_license_status_params() {
    $params = [
        'product_id' => [
            'description' => __( 'Unique identifier for the project.', 'appsero-helper' ),
            'type'        => 'integer',
        ],
        'license_key' => [
            'description'       => __( 'Unique identifier for the license.', 'appsero-helper' ),
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
        ],
        'status' => [
            'description'       => __( 'Status of license.', 'appsero-helper' ),
            'type'              => 'integer',
            'required'          => true,
            'validate_callback' => 'rest_validate_request_arg',
            'sanitize_callback' => 'absint',
        ],
    ];

    return $params;
}

/**
 * HTTP request function
 */
function appsero_helper_remote_post( $route, $body ) {
    $endpoint = apply_filters( 'appsero_endpoint', 'https://api.appsero.com' );
    $endpoint = trailingslashit( $endpoint );

    $url = $endpoint . $route;

    $api_key = defined( 'APPSERO_API_KEY' ) ? APPSERO_API_KEY : get_option( 'appsero_connection_token' );

    $args = [
        'method'      => 'POST',
        'timeout'     => 15,
        'redirection' => 5,
        'body'        => $body,
        'headers'     => [
            'user-agent' => 'AppSero/' . md5( esc_url( home_url() ) ) . ';',
            'Accept'     => 'application/json',
            'X-Api-Key'  => $api_key,
        ],
        'httpversion' => '1.0',
    ];

    return wp_remote_post( $url, $args );
}
