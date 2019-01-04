<?php
namespace Appsero\Helper\Edd;

use WP_Error;
use WP_REST_Response;

/**
 * Activations class
 * Responsible for add, update and delete activation
 */
class Activations {

    /**
     * Add/Edit ativation
     */
    public function update_or_create_item( $request ) {
        $download_id = $request->get_param( 'product_id' );
        $license_key = $request->get_param( 'license_key' );

        $license = edd_software_licensing()->get_license( $license_key, true );
        if ( $download_id !== $license->download_id ) {
            return new WP_Error( 'invalid-license', 'License not found.', array( 'status' => 404 ) );
        }

        $site_url = $request->get_param( 'site_url' );
        $site_url = trailingslashit( edd_software_licensing()->clean_site_url( $site_url ) );

        // `$license->is_at_limit()` not fulfill my need
        if ( $this->is_limit_exceed( $license, $site_url ) ) {
            return new WP_Error( 'activation-limit-exceeded', 'Activation limit exceeded.', array( 'status' => 400 ) );
        }

        $status = $request->get_param( 'status' );
        $status = ( $status === null ) ? 1 : $status;

        // `$license->add_site( $site_url )` not fulfill my need
        $site_added = $this->process_update_or_create( $site_url, $license->id, $status );
        if ( $site_added ) {
            return new WP_REST_Response( [
                'success' => true,
            ] );
        }

        return new WP_Error( 'unknown-error', 'EDD could not add site.', array( 'status' => 400 ) );
    }

    /**
     * Is activation limit exceed
     */
    private function is_limit_exceed( $license, $site_url ) {
        $limit = $license->activation_limit;

        // retrieve active sites count
        global $wpdb;
        $query  = "SELECT COUNT(site_id) as `count` FROM {$wpdb->prefix}edd_license_activations WHERE license_id = {$license->id} ";
        $query .= " AND activated = 1 AND is_local = 0 AND site_name <> '{$site_url}' ";
        $active_sites = $wpdb->get_row( $query, ARRAY_A );

        if ( $limit > 0 && $active_sites['count'] >= $limit ) {
            return true;
        }

        return false;
    }

    /**
     * Persistent activations data
     *
     * @return boolean
     */
    private function process_update_or_create( $site, $license_id, $status ) {
        $args = array(
            'site_name'  => $site,
            'license_id' => $license_id,
            'activated'  => [ 0, 1 ],
            'fields'     => 'site_id',
        );

        $is_local = edd_software_licensing()->is_local_url( $site );

        $exists = edd_software_licensing()->activations_db->get_activations( $args );

        if ( empty( $exists ) ) {
            $added = edd_software_licensing()->activations_db->insert( [
                'site_name'  => $site,
                'license_id' => $license_id,
                'activated'  => $status,
                'is_local'   => $is_local ? 1 : 0,
            ], 'site_activation' );
        } else {
            $added = edd_software_licensing()->activations_db->update( $exists[0], [ 'activated' => $status ] );
        }

        return ! empty( $added );
    }

    /**
     * Delete activation
     */
    public function delete_item( $request ) {
        $download_id = $request->get_param( 'product_id' );
        $license_key = $request->get_param( 'license_key' );

        $license = edd_software_licensing()->get_license( $license_key, true );
        if ( $download_id !== $license->download_id ) {
            return new WP_Error( 'invalid-license', 'License not found.', array( 'status' => 404 ) );
        }

        $site_url = $request->get_param( 'site_url' );
        $site_url = trailingslashit( edd_software_licensing()->clean_site_url( $site_url ) );

        $exists = edd_software_licensing()->activations_db->get_activations( [
            'site_name'  => $site_url,
            'license_id' => $license->id,
            'fields'     => 'site_id',
        ] );

        if ( empty( $exists ) ) {
            return new WP_Error( 'invalid-url', 'URL not found.', array( 'status' => 404 ) );
        }

        $deleted = edd_software_licensing()->activations_db->delete( $exists[0] );

        if ( $deleted ) {
            return new WP_REST_Response( [
                'success' => true,
            ] );
        }

        return new WP_Error( 'unknown-error', 'EDD could not delete site.', array( 'status' => 400 ) );
    }
}
