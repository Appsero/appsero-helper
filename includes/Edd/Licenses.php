<?php
namespace Appsero\Helper\Edd;

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

        if ( ! function_exists( 'edd_software_licensing' ) ) {
            return rest_ensure_response( [] );
        }

        $results = edd_software_licensing()->licenses_db->get_licenses( [
            'download_id' => $download_id,
        ] );

        foreach( $results as $license ) {
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

        // print_r( $results );

        return rest_ensure_response( $licenses );
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
