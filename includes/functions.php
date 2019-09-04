<?php

/**
 * Collection default parameters
 *
 * @return array
 */
function appsero_api_collection_params() {
    $params = [
        'page' => [
            'description'        => __( 'Current page of the collection.', 'appsero-helper' ),
            'type'               => 'integer',
            'default'            => 1,
            'sanitize_callback'  => 'absint',
            'minimum'            => 1,
        ],

        'per_page' => [
            'description'        => __( 'Maximum number of items to be returned in result set.', 'appsero-helper' ),
            'type'               => 'integer',
            'default'            => 10,
            'minimum'            => 1,
            'maximum'            => 100,
            'sanitize_callback'  => 'absint',
        ]
    ];

    return $params;
}

/**
 * Get collection patameters with product_id
 *
 * @return array
 */
function appsero_api_params_with_product_id() {
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
            'description'       => __( 'Unique hash for the license.', 'appsero-helper' ),
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
        ],
        'site_url' => [
            'description'       => __( 'Site URL of active license.', 'appsero-helper' ),
            'type'              => 'string',
            'required'          => true,
            'sanitize_callback' => 'sanitize_text_field',
        ],
        'status' => [
            'description'       => __( 'Status of a site.', 'appsero-helper' ),
            'type'              => 'integer',
            'default'           => null,
            'sanitize_callback' => 'absint',
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
            'sanitize_callback' => 'absint',
        ],
    ];

    return $params;
}

/**
 * HTTP request function
 */
function appsero_helper_remote_post( $route, $body, $method = 'POST' ) {
    $endpoint = apply_filters( 'appsero_endpoint', 'https://api.appsero.com' );
    $endpoint = trailingslashit( $endpoint );

    $url = $endpoint . $route;

    $api_key = appsero_helper_connection_token();

    $args = [
        'method'      => $method,
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

/**
 * Appsero API GET request
 */
function appsero_helper_remote_get( $route ) {
    $endpoint = apply_filters( 'appsero_endpoint', 'https://api.appsero.com' );
    $endpoint = trailingslashit( $endpoint );

    $url = $endpoint . $route;

    $api_key = appsero_helper_connection_token();

    $args = [
        'timeout'     => 15,
        'redirection' => 5,
        'headers'     => [
            'user-agent' => 'AppSero/' . md5( esc_url( home_url() ) ) . ';',
            'Accept'     => 'application/json',
            'X-Api-Key'  => $api_key,
        ],
        'httpversion' => '1.0',
    ];

    return wp_remote_get( $url, $args );
}

/**
 * Get API key
 */
function appsero_helper_connection_token() {
    $api_key = false;

    if ( defined( 'APPSERO_API_KEY' ) ) {
        $api_key = APPSERO_API_KEY;
    } else {
        $connection = get_option( \Appsero\Helper\SettingsPage::$connection_key, null );
        $api_key    = isset( $connection['token'] ) ? $connection['token'] : false;
    }

    return $api_key;
}

/**
 * Get appsero license
 */
function get_appsero_license( $id ) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'appsero_licenses';

    $sql = "
        SELECT * FROM {$table_name}
        WHERE `id` = {$id}
        LIMIT 1
    ";

    $license = $wpdb->get_row( $sql, ARRAY_A );

    $license['activations'] = json_decode( $license['activations'], true );

    return $license;
}

/**
 * Update appsero license
 */
function update_appsero_license( $id, $data ) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'appsero_licenses';

    $result = $wpdb->update( $table_name, $data, [ 'id' => $id ] );

    return false !== $result;
}

/**
 * Get active activations sites
 */
function appsero_get_active_sites_by_license( $key ) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'appsero_licenses';

    $key = sanitize_text_field( $key );

    $appsero_license = $wpdb->get_row( "SELECT * FROM {$table_name} WHERE `key` = '" . $key . "' LIMIT 1", ARRAY_A );

    if ( ! $appsero_license ) {
        return [];
    }

    $activations = json_decode( $appsero_license['activations'], true );
    $activations = ( ! is_array( $activations ) ) ? [] : $activations;

    $active_sites = [];

    foreach ( $activations as $activation ) {
        if ( boolval( $activation['is_active'] ) ) {
            $active_sites[] = $activation['site_url'];
        }
    }

    sort( $active_sites );

    return $active_sites;
}

/**
 * Convert full name to first name last name
 */
function appsero_split_name( $name ) {
    $name       = trim( $name );
    $last_name  = ( strpos( $name, ' ' ) === false ) ? '' : preg_replace( '#.*\s([\w-]*)$#', '$1', $name );
    $first_name = trim( preg_replace( '#'.$last_name.'#', '', $name ) );

    return [ $first_name, $last_name ];
}

/**
 * sanitize expire date data
 */
function sanitize_expire_date_field( $date ) {
    $date = sanitize_text_field( $date );

    if ( preg_match( "/^\d{4}\-[0-1][1-9]\-\d{2}.*$/", $date ) ) {
        return $date;
    }

    return null;
}

/**
 * Validate object type of data in rest
 */
function appsero_object_validate_callback( $value, $request, $param = '' ) {
    $attributes = $request->get_attributes();
    $args = $attributes['args'][ $param ];

    foreach ( $args['properties'] as $key => $property ) {
        if ( isset( $property['required'] ) && $property['required'] && ! isset( $value[ $key ] ) ) {
            return new WP_Error(
                $param . '.' . $key,
                $param . ' ' . $key . ' not found.'
            );
        }
    }

    return true;
}
