<?php

namespace ASHP;

use WP_Query;
use WP_Error;
use WP_REST_Response;
use WP_REST_Controller;

/**
 * This calss is responsible for /projects API endpoint
 */
class REST_Projects_Controller extends WP_REST_Controller {

    protected $settings;

    public function __construct() {
        $this->namespace = 'appsero/v1';
        $this->rest_base = '/projects';

        $this->settings = get_option( '_ashp_settings', [] );
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
     * Retrieves a collection of items.
     *
     * @param WP_REST_Request $request Full data about the request.
     * @return WP_Error|WP_REST_Response Response object on success, or WP_Error object on failure.
     */
    public function get_items( $request ) {

        if ( empty( $this->settings['marketplace_type'] ) ) {
            return new WP_Error( 'invalid-marketplace', 'Marketplace type not set', array( 'status' => 400 ) );
        }

        switch ( $this->settings['marketplace_type'] ) {
            case 'edd':
                return $this->get_edd_items( $request );
                break;
        }

        print_r($request);
    }

    /**
     * EDD projects
     */
    private function get_edd_items( $request ) {

        $query_args = [
            'post_type'      => 'download',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
        ];

        // Filter with query parameters
        // $registered = $this->get_collection_params();
        /*if ( isset( $registered['page'] ) ) {
            $query_args['paged'] = $request->get_param( 'page' );
        }*/

        $posts_query  = new WP_Query();
        $query_result = $posts_query->query( $query_args );

        // return empty response if no project found
        if ( empty( $query_result ) ) {
            return new WP_REST_Response( [
                'items'       => [],
                'total_items' => 0
            ] );
        }

        $meta = $this->get_edd_items_meta( $query_result );

        $data = [
            'items'       => [],
            'total_items' => $posts_query->found_posts
        ];

        foreach ( $query_result as $post ) {
            $has_variation = isset( $meta['variation'][ $post->ID ] ) ? $meta['variation'][ $post->ID ] : false;
            $variations    = isset( $meta['variable_prices'][ $post->ID ] ) ? $meta['variable_prices'][ $post->ID ] : [];
            $price         = isset( $meta['price'][ $post->ID ] ) ? $meta['price'][ $post->ID ] : 0;

            $data['items'][] = [
                'id'            => $post->ID,
                'name'          => $post->post_title,
                'slug'          => $post->post_name,
                'description'   => $post->post_content,
                'has_variation' => $has_variation,
                'variations'    => $variations,
                'price'         => $price,
            ];
        }

        return rest_ensure_response( $data );
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

    /**
     * EDD post meta data
     * @param  [type] $query_result [description]
     * @return [type]               [description]
     */
    private function get_edd_items_meta( $query_result ) {
        if ( empty( $query_result ) ) {
            return [];
        }

        $ids = [];
        foreach ( $query_result as $post ) {
            $ids[] = $post->ID;
        }

        global $wpdb;
        $query = "SELECT post_id, meta_value FROM {$wpdb->postmeta} WHERE post_id IN (" . implode( ',', $ids ) . ") AND meta_key = '_variable_pricing' ";
        $results = $wpdb->get_results( $query, ARRAY_A );

        $meta_data = [
            'variation' => [],
            'variable_prices' => [],
            'price' => [],
        ];

        foreach ( $results as $item ) {
            $meta_data['variation'][ $item['post_id'] ] = !! $item['meta_value'];
        }

        $query = "SELECT post_id, meta_value FROM {$wpdb->postmeta} WHERE post_id IN (" . implode( ',', $ids ) . ") AND meta_key = 'edd_variable_prices' ";
        $results = $wpdb->get_results( $query, ARRAY_A );

        foreach ( $results as $item ) {
            $meta_data['variable_prices'][ $item['post_id'] ] = maybe_unserialize( $item['meta_value'] );
        }

        $query = "SELECT post_id, meta_value FROM {$wpdb->postmeta} WHERE post_id IN (" . implode( ',', $ids ) . ") AND meta_key = 'edd_price' ";
        $results = $wpdb->get_results( $query, ARRAY_A );

        foreach ( $results as $item ) {
            $meta_data['price'][ $item['post_id'] ] = $item['meta_value'];
        }

        return $meta_data;
    }

    /**
     * Retrieves a single project license.
     *
     * @param WP_REST_Request $request Full details about the request.
     * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
     */
    public function get_item( $request ) {

        if ( empty( $this->settings['marketplace_type'] ) ) {
            return new WP_Error( 'invalid-marketplace', 'Marketplace type not set', array( 'status' => 400 ) );
        }

        // Filter with query parameters
        $registered = $this->get_collection_params();
        if ( isset( $registered['page'] ) ) {
            $current_page = $request->get_param( 'page' );
        }

        switch ( $this->settings['marketplace_type'] ) {
            case 'edd':
                return $this->get_edd_item( $request, $current_page );
                break;
        }

    }

    /**
     * Get EDD license details of a specific project
     */
    public function get_edd_item( $request, $current_page ) {

        $download_id = $request->get_param( 'id' );

        $per_page = 20;
        $offset = ( $current_page - 1 ) * $per_page;

        global $wpdb;
        $query       = "SELECT SQL_CALC_FOUND_ROWS * FROM {$wpdb->prefix}edd_licenses WHERE download_id = {$download_id} ";
        $query      .= " ORDER BY id ASC LIMIT {$per_page} OFFSET {$offset}";
        $results     = $wpdb->get_results( $query, ARRAY_A );
        $total_items = $wpdb->get_var('SELECT FOUND_ROWS()');

        // return empty if no license found
        if ( empty( $results ) ) {
            return new WP_REST_Response( [
                'items'        => [],
                'total_items'  => 0,
                'current_page' => 1,
                'per_page'     => $per_page,
                'last_page'    => 1,
            ] );
        }

        $last_page = ceil( $total_items / $per_page );

        if ( $current_page > $last_page ) {
            return new WP_Error( 'invalid-page-number', 'Use correct page number.', array( 'status' => 400 ) );
        }

        // Get meta data
        $meta_data = $this->get_edd_item_meta( $download_id );

        $data = [
            'items'        => [],
            'total_items'  => $total_items,
            'current_page' => $current_page,
            'per_page'     => $per_page,
            'last_page'    => $last_page,
        ];

        $activations = $this->get_edd_activations( $results );

        foreach ( $results as $license ) {
            $expiration = $license['expiration'] ? date( 'Y-m-d H:i:s', $license['expiration'] ) : null;

            $data['items'][] = [
                'id'               => $license['id'],
                'license_key'      => $license['license_key'],
                'status'           => $license['status'],
                'date_created'     => $license['date_created'],
                'expiration'       => $expiration,
                'activation_limit' => $this->get_edd_activation_limit( $license, $meta_data ),
                'activations'      => isset( $activations[ $license['id'] ] ) ? $activations[ $license['id'] ] : [],
            ];
        }

        return rest_ensure_response( $data );
    }

    /**
     * Get meta data of edd license
     */
    private function get_edd_item_meta( $post_id ) {
        global $wpdb;
        $query = "SELECT meta_key, meta_value FROM {$wpdb->postmeta} WHERE post_id = {$post_id} AND meta_key IN ";
        $query .= "('edd_variable_prices', '_variable_pricing', '_edd_sl_limit')";
        $results = $wpdb->get_results( $query, ARRAY_A );

        $meta_data = [];

        foreach ( $results as $post_meta ) {
            $meta_data[ $post_meta['meta_key'] ] = maybe_unserialize( $post_meta['meta_value'] );
        }

        return $meta_data;
    }

    /**
     * Get EDD activation limit
     */
    private function get_edd_activation_limit( $license, $meta_data ) {
        if (
            isset( $meta_data['_variable_pricing'], $meta_data['edd_variable_prices'] ) &&
            $meta_data['_variable_pricing'] && isset( $meta_data['edd_variable_prices'][ $license['price_id'] ] )
        ) {

            return (int) $meta_data['edd_variable_prices'][ $license['price_id'] ]['license_limit'];

        } else if ( ! isset( $meta_data['_variable_pricing'] ) || ! $meta_data['_variable_pricing'] ) {

            return (int) $meta_data['_edd_sl_limit'];
        }

        return null;
    }

    /**
     * Get activation sites of EDD
     */
    private function get_edd_activations( $results ) {
        if ( empty( $results ) )
            return [];

        $ids = [];
        $activations = [];

        foreach ( $results as $license ) {
            $ids[] = $license['id'];
            $activations[ $license['id'] ] = [];
        }

        global $wpdb;
        $query = "SELECT * FROM {$wpdb->prefix}edd_license_activations WHERE license_id IN (" . implode( ',', $ids ) . ") ";
        $results = $wpdb->get_results( $query, ARRAY_A );

        foreach ( $results as $item ) {
            $activations[ $item['license_id'] ][] = [
                'site_url'  => esc_url( $item['site_name'] ),
                'is_active' => !! $item['activated'],
                'is_local'  => !! $item['is_local'],
            ];
        }

        return $activations;
    }

}
