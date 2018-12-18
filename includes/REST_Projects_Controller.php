<?php

namespace AppseroHelper;

/**
 * This calss is responsible for /projects API endpoint
 */
class REST_Projects_Controller extends \WP_REST_Controller {

    public function __construct() {

        $this->namespace = 'appsero/v1';
        $this->rest_base = '/projects';
    }

    /**
     * Register projects routes.
     */
    public function register_routes() {

        // @route /projects
        register_rest_route( $this->namespace, $this->rest_base, array(
            array(
                'methods'             => 'GET',
                'callback'            => array( $this, 'get_items' ),
                'permission_callback' => array( $this, 'get_items_permissions_check' ),
                // 'args' => $this->get_collection_params()
            )
        ) );

        // @route /projects/{id}/licenses
        register_rest_route( $this->namespace, $this->rest_base . '/(?P<id>[\d]+)/licenses', array(
            array(
                'methods'             => 'GET',
                'callback'            => array( $this, 'get_item' ),
                'permission_callback' => array( $this, 'get_items_permissions_check' ),
                'args' => $this->get_collection_params()
            ),
            'args' => array(
                'id' => array(
                    'description' => 'Unique identifier for the project.',
                    'type'        => 'integer',
                ),
            ),
        ) );

    }

    /**
     * Checks if request has access to get items.
     *
     * @param WP_REST_Request $request Full data about the request.
     * @return WP_Error|bool True if the request has read access, WP_Error object otherwise.
     */
    public function get_items_permissions_check( $request ) {
        return true; // Set true for now
    }

    /**
     * Get the query params for collections
     *
     * @return array
     */
    public function get_collection_params() {
        return array(
            'page' => array(
                'description'       => 'Current page of the collection.',
                'type'              => 'integer',
                'default'           => 1,
                'sanitize_callback' => 'absint',
                'validate_callback' => 'rest_validate_request_arg',
                'minimum'           => 1,
            )
        );
    }

}
