<?php
namespace Appsero\Helper;

use WP_Error;
use WP_REST_Response;

class Common_Api {

    /**
     * Store product id in wp_options
     *
     * @return WP_Error|WP_REST_Response
     */
    public function connect_products( $request ) {
        $product_id = $request->get_param( 'product_id' );
        $option_name = 'appsero_connected_products';

        if ( ! is_numeric( $product_id ) ) {
            return new WP_Error( 'invalid-format', 'Product ID must be integer.', [ 'status' => 400 ] );
        }

        $connected = get_option( $option_name, [] );

        if ( ! is_array( $connected ) ) {
            $connected = [];
        }

        if ( ! in_array( $product_id, $connected ) ) {
            array_push( $connected, intval( $product_id ) );
        }

        update_option( $option_name, $connected, false );

        return new WP_REST_Response( [
            'success' => true,
        ] );
    }

    /**
     * Remove product id from wp options
     *
     * @return WP_Error|WP_REST_Response
     */
    public function disconnect_products( $request ) {
        $product_id = $request->get_param( 'product_id' );
        $option_name = 'appsero_connected_products';

        if ( ! is_numeric( $product_id ) ) {
            return new WP_Error( 'invalid-format', 'Product ID must be integer.', [ 'status' => 400 ] );
        }

        $connected = get_option( $option_name, [] );

        if ( is_array( $connected ) ) {
            $index = array_search( $product_id, $connected );

            if ( $index !== false ) {
                unset( $connected[ $index ] );

                update_option( $option_name, $connected, false );
            }
        }

        return new WP_REST_Response( [
            'success' => true,
        ] );
    }

    /**
     * Update navite appsero licenses activations
     */
    public function update_native_license_activations( $request ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'appsero_licenses';
        $source_id = intval( $request->get_param( 'source_id' ) );

        $appsero_license = $wpdb->get_row( "SELECT * FROM {$table_name} WHERE `source_id` = " . $source_id . " LIMIT 1", ARRAY_A );

        $data = [
            'status'           => $request->get_param( 'status' ),
            'activation_limit' => $request->get_param( 'activation_limit' ),
            'expire_date'      => $request->get_param( 'expire_date' ),
            'activations'      => json_encode( $request->get_param( 'activations' ) ),
        ];

        if ( $appsero_license ) {
            $wpdb->update( $table_name, $data, [
                'id' => $appsero_license['id']
            ]);
        }

        return new WP_REST_Response( [
            'success' => true,
        ] );
    }

}
