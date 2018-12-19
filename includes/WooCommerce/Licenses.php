<?php
namespace Appsero\Helper\WooCommerce;

/**
 * Licenses
 */
class Licenses {

    /**
     * Get a collection of licenses.
     *
     * @param WP_REST_Request $request Full details about the request.
     *
     * @return WP_Error|WP_REST_Response
     */
    public function get_items( $request ) {

        // if WooCommerce Software Addon Exists
        if ( class_exists( 'WC_Software' ) ) {
            # code...
        }

        // if WooCommerce API Manager Exists
        if ( class_exists( 'WooCommerce_API_Manager' ) ) {
            # code...
        }

        return rest_ensure_response( [] );
    }
}
