<?php
namespace Appsero\Helper\Edd;

use WP_Query;

/**
 * Licenses
 */
class Downloads {

    /**
     * Get a collection of licenses.
     *
     * @param WP_REST_Request $request Full details about the request.
     *
     * @return WP_Error|WP_REST_Response
     */
    public function get_items( $request ) {

        $products   = [];
        $query_args = [
            'post_type'      => 'download',
            'posts_per_page' => $request->get_param( 'per_page' ),
            'paged'          => $request->get_param( 'page' ),
        ];

        $posts_query  = new WP_Query();
        $query_result = $posts_query->query( $query_args );

        foreach ( $query_result as $download ) {
            $downloads[] = $this->get_download_data( $download );
        }

        $response    = rest_ensure_response( $downloads );

        $page        = (int) $query_args['paged'];
        $total_posts = $posts_query->found_posts;
        $max_pages   = ceil( $total_posts / (int) $query_args['posts_per_page'] );

        $response->header( 'X-WP-Total', (int) $total_posts );
        $response->header( 'X-WP-TotalPages', (int) $max_pages );

        return $response;
    }

    /**
     * Get standard product data that applies to every product type
     *
     * @since 2.1
     * @param WC_Product|int $product
     *
     * @return array
     */
    private function get_download_data( $item ) {
        $download = edd_get_download( $item->ID );

        return [
            'title'         => $download->get_name(),
            'id'            => $download->get_ID(),
            'price'         => $download->get_price(),
            'permalink'     => get_permalink( $item->ID ),
            'type'          => $download->get_type(),
            'has_variation' => $download->has_variable_prices(),
            'total_sales'   => $download->get_sales(),
            'variations'    => $this->get_variations( $download ),
        ];
    }

    /**
     * Get valiations of a project
     *
     * @param EDD_Download $download
     *
     * @return array
     */
    private function get_variations( $download ) {
        $the_prices = [];
        $result = $download->get_prices();

        foreach( $result as $key => $price ) {
            $prices[ strval( $key ) ] = [
                'id'               => (int) $key,
                'name'             => $price['name'],
                'amount'           => $price['amount'],
                'activation_limit' => $price['license_limit'],
                'recurring'        => $price['recurring'],
                'trial_quantity'   => $price['trial-quantity'],
                'trial_unit'       => $price['trial-unit'],
                'period'           => $price['period'],
                'times'            => $price['times'],
                'signup_fee'       => $price['signup_fee'],
            ];
        }

        return $prices;
    }

}
