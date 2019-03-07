<?php
namespace Appsero\Helper\WooCommerce;

use WP_Error;
use WP_Query;
use WCS_Admin_Post_Types;
use Appsero\Helper\Traits\OrderHelper;

/**
 * Subscriptions
 */
class Subscriptions {

    use OrderHelper;

    public function get_items( $request ) {
        if ( ! class_exists( 'WC_Subscriptions' ) ) {
            return new WP_Error( 'subscription_plugin_missing', 'WooCommerce Subscriptions plugin not found.', [ 'status' => 400 ] );
        }

        $product_id = $request->get_param( 'product_id' );

        $query_args = [
            'post_type'      => 'shop_subscription',
            'posts_per_page' => $request->get_param( 'per_page' ),
            'paged'          => $request->get_param( 'page' ),
            'fields'         => 'ids',
            'orderby'        => 'ID',
            'order'          => 'ASC',
            'post_status' => [ 'any' ]
        ];

        $subscriptions_for_product = wcs_get_subscriptions_for_product( [ $product_id, 0 ] );
        $query_args = WCS_Admin_Post_Types::set_post__in_query_var( $query_args, $subscriptions_for_product );

        $posts_query   = new WP_Query();
        $query_result  = $posts_query->query( $query_args );
        $subscriptions = [];

        foreach ( $query_result as $post_id ) {
            $subscriptions[] = $this->get_subscription_data( $post_id );
        }

        $response = rest_ensure_response( $subscriptions );

        $total_posts = $posts_query->found_posts;
        $max_pages   = ceil( $total_posts / (int) $query_args['posts_per_page'] );

        $response->header( 'X-WP-Total', (int) $total_posts );
        $response->header( 'X-WP-TotalPages', (int) $max_pages );

        return $response;
    }

    /**
     * Prepare subscription data for response
     */
    public function get_subscription_data( $post_id, $individual = true ) {
        if ( empty( $post_id ) )
            return;

        $subscription = is_numeric( $post_id ) ? wcs_get_subscription( $post_id ) : $post_id;
        $subscription_data  = $subscription->get_data();

        $subscription_response = [
            'id'                => $subscription_data['id'],
            'start_date'        => $this->format_date( $subscription_data['schedule_start'] ),
            'last_ordered_date' => $this->format_date( $subscription->get_date_paid() ),
            'next_payment_date' => $this->format_date( $subscription_data['schedule_next_payment'] ),
            'end_date'          => $subscription->get_date( 'end_date' ) ?: '',
            'cancelled_at'      => $subscription->get_date( 'cancelled' ) ?: '',
            'amount'            => $subscription_data['total'],
            'status'            => $subscription_data['status'],
            'notes'             => $this->get_woocommerce_notes( $subscription_data['id'] ),
        ];

        if ( $individual ) {
            $subscription_response['customer'] = $this->woocommerce_customer( $subscription_data );
            $subscription_response['orders'] = $subscription->get_related_orders();
        }

        return $subscription_response;
    }

    /**
     * Format date
     */
    private function format_date( $dateObject ) {
        if ( empty( $dateObject ) ) {
            return '';
        }

        return $dateObject->date( 'Y-m-d H:i:s' );
    }

}
