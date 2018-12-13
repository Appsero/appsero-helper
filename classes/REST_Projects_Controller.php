<?php

namespace ASHP;

use WP_Query;
use WP_Error;
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

        register_rest_route( $this->namespace, $this->rest_base, array(
            array(
                'methods'             => 'GET',
                'callback'            => array( $this, 'get_items' ),
                'permission_callback' => array( $this, 'get_items_permissions_check' ),
                // 'args' => $this->get_collection_params()
            )
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

        $meta = $this->get_edd_meta( $query_result );

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
    private function get_edd_meta( $query_result ) {
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

}
