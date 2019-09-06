<?php
namespace Appsero\Helper\Common;

use WP_Error;
use WP_REST_Response;

class Product {

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

}
