<?php

namespace ASHP;

use WP_Query;
use WP_Error;
use WP_REST_Response;
use WP_REST_Controller;

/**
 * This calss is responsible for license activation API endpoint
 */
class REST_Projects_Activations_Controller extends WP_REST_Controller {

    protected $settings;

    public function __construct() {
        $this->namespace = 'appsero/v1';
        $this->rest_base = '/projects/(?P<project_id>[\d]+)/licenses/(?P<license_id>[\d]+)/activations';

        $this->settings = get_option( '_ashp_settings', [] );
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
     * Add a new activation.
     *
     * @param WP_REST_Request $request Full data about the request.
     * @return WP_Error|WP_REST_Response Response object on success, or WP_Error object on failure.
     */
    public function add_activation( $request ) {

        if ( empty( $this->settings['marketplace_type'] ) ) {
            return new WP_Error( 'invalid-marketplace', 'Marketplace type not set', array( 'status' => 400 ) );
        }

        switch ( $this->settings['marketplace_type'] ) {
            case 'edd':
                return $this->add_edd_activation( $request );
                break;
        }

        print_r($request);
    }

    /**
     * Add activation for EDD projects
     */
    private function add_edd_activation( $request ) {

        $download_id = $request->get_param( 'project_id' );
        $license_id  = $request->get_param( 'license_id' );

        global $wpdb;

        // retrieve license
        $query = "SELECT * FROM {$wpdb->prefix}edd_licenses WHERE id = {$license_id} AND download_id = {$download_id} ";
        $license = $wpdb->get_row( $query, ARRAY_A );

        if ( ! $license ) {
            return new WP_Error( 'invalid-license', 'License not found.', array( 'status' => 404 ) );
        }

        // Get metadata of a project
        $query  = "SELECT meta_key, meta_value FROM {$wpdb->postmeta} WHERE post_id = {$download_id} ";
        $query .= " AND meta_key IN ('_variable_pricing', 'edd_variable_prices', '_edd_sl_limit') ";
        $metas  = $wpdb->get_results( $query, ARRAY_A );
        $meta   = array_column( $metas, 'meta_value', 'meta_key' );

        // if variable product
        $activation_limit = 0;
        if ( ! isset( $meta['_variable_pricing'] ) || empty( $meta['_variable_pricing'] ) ) {
            $activation_limit = (int) $meta['_edd_sl_limit'];
        } else {
            $variable_prices = maybe_unserialize( $meta['edd_variable_prices'] );
            if ( isset( $variable_prices[ $license['price_id'] ] ) ) {
                $activation_limit = (int) $variable_prices[ $license['price_id'] ]['license_limit'];
            }
        }

        // retrieve active sites count
        $query = "SELECT COUNT(site_id) as `count` FROM {$wpdb->prefix}edd_license_activations WHERE license_id = {$license_id} AND activated = 1 AND is_local = 0";
        $active_sites = $wpdb->get_row( $query, ARRAY_A );

        if ( $active_sites['count'] >= $activation_limit ) {
            return new WP_Error( 'activation-limit-exceeded', 'Activation limit exceeded.', array( 'status' => 400 ) );
        }

        $site_added = $this->add_edd_activation_site( $request->get_param( 'site_url' ), $license['id'] );

        if ( $site_added ) {
            return new WP_REST_Response( [
                'success' => true,
            ] );
        }

        return new WP_Error( 'unknown-error', 'EDD could not add site.', array( 'status' => 400 ) );
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
                'sanitize_callback' => 'sanitize_key',
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

    /**
     * Add URL to EDD database table
     */
    private function add_edd_activation_site( $site_url, $license_id ) {

        $site = trailingslashit( edd_software_licensing()->clean_site_url( $site_url ) );
        if ( empty( $site ) || '/' === $site ) {
            return false;
        }

        $args = array(
            'site_name'  => $site,
            'license_id' => $license_id,
            'activated'  => array( 0, 1 ),
            'fields'     => 'site_id',
        );

        $is_local = edd_software_licensing()->is_local_url( $site );

        $exists = edd_software_licensing()->activations_db->get_activations( $args );

        if ( empty( $exists ) ) {
            $added = edd_software_licensing()->activations_db->insert( array(
                'site_name'  => $site,
                'license_id' => $license_id,
                'activated'  => 1,
                'is_local'   => $is_local ? 1 : 0,
            ), 'site_activation' );
        } else {
            $added = edd_software_licensing()->activations_db->update( $exists[0], array( 'activated' => 1 ) );
        }

        return ! empty( $added );
    }

}
