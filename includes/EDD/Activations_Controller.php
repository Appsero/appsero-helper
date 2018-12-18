<?php

namespace AppseroHelper\EDD;

use WP_Query;
use WP_Error;
use WP_REST_Response;
use AppseroHelper\REST_Projects_Activations_Controller;

/**
 * This calss is responsible for license activation API response
 */
class Activations_Controller extends REST_Projects_Activations_Controller {

    /**
     * Add a new activation.
     *
     * @param WP_REST_Request $request Full data about the request.
     * @return WP_Error|WP_REST_Response Response object on success, or WP_Error object on failure.
     */
    public function add_activation( $request ) {
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

        $site_url = trailingslashit( edd_software_licensing()->clean_site_url( $request->get_param( 'site_url' ) ) );
        if ( empty( $site_url ) || '/' === $site_url ) {
            return new WP_Error( 'invalid-url', 'Invalid URL provided.', array( 'status' => 400 ) );
        }

        // retrieve active sites count
        $query  = "SELECT COUNT(site_id) as `count` FROM {$wpdb->prefix}edd_license_activations WHERE license_id = {$license_id} ";
        $query .= " AND activated = 1 AND is_local = 0 AND site_name <> '{$site_url}' ";
        $active_sites = $wpdb->get_row( $query, ARRAY_A );

        if ( $active_sites['count'] >= $activation_limit ) {
            return new WP_Error( 'activation-limit-exceeded', 'Activation limit exceeded.', array( 'status' => 400 ) );
        }

        $site_added = $this->add_edd_activation_site( $site_url, $license['id'] );

        if ( $site_added ) {
            return new WP_REST_Response( [
                'success' => true,
            ], 201 );
        }

        return new WP_Error( 'unknown-error', 'EDD could not add site.', array( 'status' => 400 ) );
    }

    /**
     * Add URL to EDD database table
     */
    private function add_edd_activation_site( $site, $license_id ) {

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
