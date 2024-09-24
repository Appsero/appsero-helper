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
    $url = get_appsero_api_url() . $route;

    $api_key = appsero_helper_connection_token();

    $args = [
        'method'      => $method,
        'timeout'     => 15,
        'redirection' => 5,
        'body'        => $body,
        'headers'     => [
            'user-agent' => 'Appsero/' . md5( esc_url( home_url() ) ) . ';',
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
    $url = get_appsero_api_url() . $route;

    $api_key = appsero_helper_connection_token();

    $args = [
        'timeout'     => 15,
        'redirection' => 5,
        'headers'     => [
            'user-agent' => 'Appsero/' . md5( esc_url( home_url() ) ) . ';',
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
 * Get active activations sites
 */
function appsero_get_active_sites_by_license( $key ) {
    $key = sanitize_text_field( $key );

    $appsero_license = appsero_get_license_by_key( $key );

    if ( ! $appsero_license ) {
        return [];
    }

    $active_sites = [];

    foreach ( (array) $appsero_license['activations'] as $activation ) {
        if ( isset( $activation['is_active'] ) && 1 == $activation['is_active'] ) {
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


/**
 * Get active activations sites
 */
function appsero_get_license_by_key( $key ) {
    $route = 'public/licenses/' . $key;

    // Send request to appsero server
    $response = appsero_helper_remote_get( $route );

    if ( wp_remote_retrieve_response_code( $response ) != 200 ) {
        return false;
    }

    $response_body = json_decode( wp_remote_retrieve_body( $response ), true );

    if ( empty( $response_body['data'] ) ) {
        return false;
    }

    return $response_body['data'];
}

/**
 * Get selling plugin
 */
function appsero_get_selling_plugin() {
    // If woocommerce and edd both are installed
    if ( class_exists( 'WooCommerce' ) && class_exists( 'Easy_Digital_Downloads' ) ) {
        $has_plugin = get_option( 'appsero_selling_plugin', '' );

        if ( $has_plugin === 'woo' ) {
            return 'woo';
        }

        if ( $has_plugin === 'edd' ) {
            return 'edd';
        }
    }

    if ( class_exists( 'WooCommerce' ) ) {
        return 'woo';
    }

    if ( class_exists( 'Easy_Digital_Downloads' ) ) {
        return 'edd';
    }

    return 'appsero';
}

/**
 * Appsero create customer
 */
function appsero_create_customer( $email, $first_name, $last_name ) {
    $exists = username_exists( $email );

    if ( $exists ) {
        return $exists;
    }

    $exists = email_exists( $email );

    if ( $exists ) {
        return $exists;
    }

    $random_password = wp_generate_password( 12, false );

    $userdata = [
        'user_pass'     => $random_password,
        'display_name'  => $first_name,
        'user_nicename' => $first_name,
        'first_name'    => $first_name,
        'last_name'     => $last_name,
        'user_login'    => $email,
        'user_email'    => $email,
        'role'          => 'subscriber',
    ];

    $user_id = wp_insert_user( $userdata );

    wp_send_new_user_notifications( $user_id, 'user' );

    return $user_id;
}

/**
 * Get appsero API URL
 */
function get_appsero_api_url() {
    $endpoint = apply_filters( 'appsero_endpoint', 'https://api.appsero.com' );

    return trailingslashit( $endpoint );
}

/**
 * Update customer data to appsero.
 * 
 * @param int $user_id
 * @return void
 */
function appsero_update_customer($user_id, $old) {
    $user = get_userdata($user_id);
    if (!$user) {
        return;
    }

    // Get billing country code
    $country_code = get_user_meta($user_id, 'billing_country', true);
    $state_code = get_user_meta($user_id, 'billing_state', true);
    
    // Prepare user data array
    $user_data = [
        'email'             => $user->user_email,
        'name'              => $user->display_name,
        'phone'             => get_user_meta($user_id, 'billing_phone', true),
        'image'             => get_user_meta($user_id, 'billing_image', true),
        'company'           => get_user_meta($user_id, 'billing_company', true),
        'address'           => get_user_meta($user_id, 'billing_address_1', true),
        'zip_code'          => get_user_meta($user_id, 'billing_postcode', true),
        'state'             => get_state_name($country_code, $state_code),
        'country'           => get_country_name($country_code),
    ];

    $url = 'public/users/' . $user_id . '/edit-customer';

    appsero_helper_remote_post($url, $user_data);
}

/**
 * Get full country name from country code.
 *
 * @param string $country_code
 * @return string|null
 */
function get_country_name($country_code) {
    if (class_exists('WooCommerce')) {
        $countries = WC()->countries->get_countries();
        return isset($countries[$country_code]) ? $countries[$country_code] : null;
    }

    return null; 
}

/**
 * Get full state name from state code.
 *
 * @param string $country_code
 * @param string $state_code
 * @return string|null
 */
function get_state_name($country_code, $state_code) {
    if (class_exists('WooCommerce')) {
        $states = WC()->countries->get_states($country_code);
        return isset($states[$state_code]) ? $states[$state_code] : null;
    }
    return null;
}
