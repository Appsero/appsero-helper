<?php
namespace Appsero\Helper\WooCommerce;

use Appsero\Helper\RestController;
use WP_Query;

/**
 * Products
 */
class Products {

    /**
     * Get a collection of posts.
     *
     * @param WP_REST_Request $request Full details about the request.
     *
     * @return WP_Error|WP_REST_Response
     */
    public function get_items( $request ) {

        $products   = [];
        $query_args = [
            'post_type'      => 'product',
            'posts_per_page' => $request->get_param( 'per_page' ),
            'paged'          => $request->get_param( 'page' ),
        ];

        $posts_query  = new WP_Query();
        $query_result = $posts_query->query( $query_args );

        foreach ( $query_result as $product ) {
            $products[] = $this->get_product_data( $product );
        }

        $response    = rest_ensure_response( $products );

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
    private function get_product_data( $item ) {
        $product = wc_get_product( $item->ID );

        return [
            'title'         => $product->get_name(),
            'id'            => $product->get_id(),
            'price'         => $product->get_price(),
            'permalink'     => $product->get_permalink(),
            'type'          => $product->get_type(),
            'has_variation' => 'variable' == $product->get_type(),
            'total_sales'   => $product->get_total_sales(),
        ];
    }
}
