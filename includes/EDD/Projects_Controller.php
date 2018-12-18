<?php

namespace AppseroHelper\EDD;

use WP_Query;
use WP_Error;
use WP_REST_Response;
use AppseroHelper\REST_Projects_Controller;

/**
 * This calss is responsible for /projects API response
 */
class Projects_Controller extends REST_Projects_Controller {

    /**
     * Retrieves a collection of items.
     *
     * @param WP_REST_Request $request Full data about the request.
     * @return WP_Error|WP_REST_Response Response object on success, or WP_Error object on failure.
     */
    public function get_items( $request ) {

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
            $has_variation    = isset( $meta['variation'][ $post->ID ] ) ? $meta['variation'][ $post->ID ] : false;
            $variations       = isset( $meta['variable_prices'][ $post->ID ] ) ? $meta['variable_prices'][ $post->ID ] : [];
            $price            = isset( $meta['price'][ $post->ID ] ) ? $meta['price'][ $post->ID ] : 0;
            $license_limit = isset( $meta['license_limit'][ $post->ID ] ) ? $meta['license_limit'][ $post->ID ] : 0;

            $data['items'][] = [
                'id'            => $post->ID,
                'name'          => $post->post_title,
                'slug'          => $post->post_name,
                'description'   => $post->post_content,
                'has_variation' => $has_variation,
                'variations'    => $variations,
                'price'         => $price,
                'license_limit' => (int) $license_limit,
            ];
        }

        return rest_ensure_response( $data );
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
            'variation'       => [],
            'variable_prices' => [],
            'price'           => [],
            'license_limit'   => [],
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
        $meta_data['price'] = array_column( $results, 'meta_value', 'post_id' );

        $query = "SELECT post_id, meta_value FROM {$wpdb->postmeta} WHERE post_id IN (" . implode( ',', $ids ) . ") AND meta_key = '_edd_sl_limit' ";
        $results = $wpdb->get_results( $query, ARRAY_A );
        $meta_data['license_limit'] = array_column( $results, 'meta_value', 'post_id' );

        return $meta_data;
    }

    /**
     * Retrieves a single project license.
     *
     * @param WP_REST_Request $request Full details about the request.
     * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
     */
    public function get_item( $request ) {

        // Filter with query parameters
        $registered = $this->get_collection_params();
        $current_page = isset( $registered['page'] ) ? $request->get_param( 'page' ) : 1;

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

        // Get meta data
        $meta_data = $this->get_edd_item_meta( $download_id );

        $data = [
            'items'        => [],
            'total_items'  => (int) $total_items,
            'current_page' => $current_page,
            'per_page'     => $per_page,
            'last_page'    => $last_page,
        ];

        $activations = $this->get_edd_activations( $results );

        foreach ( $results as $license ) {
            $expiration       = $license['expiration'] ? date( 'Y-m-d H:i:s', $license['expiration'] ) : null;
            $status           = ( 'active' == $license['status'] ? 1 : ( 'inactive' == $license['status'] ? 0 : 2 ) );
            $item_activations = isset( $activations['all'][ $license['id'] ] ) ? $activations['all'][ $license['id'] ] : [];
            $active_sites     = isset( $activations['active_sites'][ $license['id'] ] ) ? $activations['active_sites'][ $license['id'] ] : 0;

            $data['items'][] = [
                'source_identifier' => (int) $license['id'],
                'key'               => $license['license_key'],
                'status'            => $status,
                'created_at'        => $license['date_created'],
                'expire_date'       => $expiration,
                'activation_limit'  => $this->get_edd_activation_limit( $license, $meta_data ),
                'activations'       => $item_activations,
                'variation_source'  => (int) $license['price_id'] ?: null,
                'active_sites'      => (int) $active_sites,
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

        $query  = "SELECT COUNT(site_id) as `active_sites`, license_id FROM {$wpdb->prefix}edd_license_activations ";
        $query .= " WHERE license_id IN (" . implode( ',', $ids ) . ") AND activated = 1 AND is_local = 0 GROUP BY license_id";
        $active_sites = $wpdb->get_results( $query, ARRAY_A );
        $active_sites = wp_list_pluck( $active_sites, 'active_sites', 'license_id' );

        return [
            'all'          => $activations,
            'active_sites' => $active_sites,
        ];
    }

}
