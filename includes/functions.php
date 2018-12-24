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
        'project_id' => [
            'description' => 'Unique identifier for the project.',
            'type'        => 'integer',
        ],
        'license_id' => [
            'description' => 'Unique identifier for the license.',
            'type'        => 'integer',
        ],
        'site_url' => [
            'description'       => 'Site URL of active license.',
            'type'              => 'string',
            'required'          => true,
            'validate_callback' => 'rest_validate_request_arg',
            'sanitize_callback' => 'sanitize_text_field',
        ],
        'status' => [
            'description'       => 'Status of a site.',
            'type'              => 'integer',
            'default'           => null,
            'sanitize_callback' => 'absint',
            'validate_callback' => 'rest_validate_request_arg',
            'enum'              => [ 0, 1 ]
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
        'project_id' => [
            'description' => 'Unique identifier for the project.',
            'type'        => 'integer',
        ],
        'license_id' => [
            'description' => 'Unique identifier for the license.',
            'type'        => 'integer',
        ],
        'site_url' => [
            'description'       => 'Site URL of active license.',
            'type'              => 'string',
            'required'          => true,
            'validate_callback' => 'rest_validate_request_arg',
            'sanitize_callback' => 'sanitize_text_field',
        ],
    ];

    return $params;
}
