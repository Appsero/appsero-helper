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
