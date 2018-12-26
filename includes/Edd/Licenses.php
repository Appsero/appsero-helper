<?php
namespace Appsero\Helper\Edd;

use EDD_SL_License;

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
        $download_id = $request->get_param( 'product_id' );

        if ( ! class_exists( 'EDD_SL_License' ) ) {
            return rest_ensure_response( [] );
        }

        $per_page     = $request->get_param( 'per_page' );
        $current_page = $request->get_param( 'page' );
        $offset       = ( $current_page - 1 ) * $per_page;

        // `edd_software_licensing()->licenses_db->get_licenses()` not fulfill my need
        global $wpdb;
        $query       = "SELECT SQL_CALC_FOUND_ROWS id FROM {$wpdb->prefix}edd_licenses WHERE download_id = {$download_id} ";
        $query      .= " ORDER BY id ASC LIMIT {$per_page} OFFSET {$offset}";
        $results     = $wpdb->get_col( $query, 0 );
        $total_items = $wpdb->get_var( 'SELECT FOUND_ROWS()' );

        foreach( $results as $id ) {
            $license = new EDD_SL_License( $id );
            $status     = ( 'active' == $license->status ? 1 : ( 'inactive' == $license->status ? 0 : 2 ) );
            $expiration = $license->expiration ? date( 'Y-m-d H:i:s', (int) $license->expiration ) : null;

            $licenses[] = [
                'source_identifier' => $license->id,
                'key'               => $license->license_key,
                'status'            => $status,
                'created_at'        => $license->date_created,
                'expire_date'       => $expiration,
                'activation_limit'  => $license->activation_limit ?: null,
                'activations'       => $this->filter_active_sites( $license->sites ),
                'variation_source'  => (int) $license->price_id ?: null,
                'active_sites'      => (int) $license->activation_count,
            ];
        }

        $response = rest_ensure_response( $licenses );

        $max_pages = ceil( $total_items / $per_page );
        $response->header( 'X-WP-Total', (int) $total_items );
        $response->header( 'X-WP-TotalPages', (int) $max_pages );

        return $response;
    }

    /**
     * Filter domain, remove end "/"
     *
     * @param array $sites
     * @uses  untrailingslashit()
     *
     * @return array
     */
    private function filter_active_sites( $sites ) {
        if ( empty( $sites ) || ! is_array( $sites ) )
            return [];

        foreach( $sites as $site ) {
            $domains[] = untrailingslashit( $site );
        }

        return $domains;
    }


}
