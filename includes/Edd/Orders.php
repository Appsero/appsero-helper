<?php
namespace Appsero\Helper\Edd;

/**
 * Licenses
 */
class Orders {

    /**
     * Get a collection of orders.
     *
     * @param WP_REST_Request $request Full details about the request.
     *
     * @return WP_Error|WP_REST_Response
     */
    public function get_items( $request ) {
        return rest_ensure_response( [] );
    }
}
