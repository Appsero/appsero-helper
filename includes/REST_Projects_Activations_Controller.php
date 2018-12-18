<?php

namespace AppseroHelper;

/**
 * This calss is responsible for license activation API endpoint
 */
class REST_Projects_Activations_Controller extends \WP_REST_Controller {

    public function __construct() {

        $this->namespace = 'appsero/v1';
        $this->rest_base = '/projects/(?P<project_id>[\d]+)/licenses/(?P<license_id>[\d]+)/activations';

    }

    /**
     * Register projects routes.
     */
    public function register_routes() {

        // @route /projects
        register_rest_route( $this->namespace, $this->rest_base, array(
            array(
                'methods'             => 'POST',
                'callback'            => array( $this, 'add_activation' ),
                'permission_callback' => array( $this, 'activation_permission_check' ),
                'args' => $this->add_activation_params()
            ),
            'args' => $this->add_activation_route_args(),
        ) );

        // @route /projects/{id}/licenses
        /*register_rest_route( $this->namespace, $this->rest_base . '/(?P<id>[\d]+)/licenses', array(
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
        ) );*/

    }

    /**
     * Checks if request has access to get items.
     *
     * @param WP_REST_Request $request Full data about the request.
     * @return WP_Error|bool True if the request has read access, WP_Error object otherwise.
     */
    public function activation_permission_check( $request ) {
        return true; // Set true for now
    }

    /**
     * Get the query params for collections
     *
     * @return array
     */
    public function add_activation_params() {
        return array(
            'site_url' => array(
                'description'       => 'Site URL of active license.',
                'type'              => 'string',
                'required'           => true,
                'validate_callback' => 'rest_validate_request_arg',
            )
        );
    }

    /**
     * Get route arguments
     *
     * @return  array
     */
    private function add_activation_route_args() {
        return array(
            'project_id' => array(
                'description' => 'Unique identifier for the project.',
                'type'        => 'integer',
            ),
            'license_id' => array(
                'description' => 'Unique identifier for the license.',
                'type'        => 'integer',
            ),
        );
    }

}
