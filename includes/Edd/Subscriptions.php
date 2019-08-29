<?php
namespace Appsero\Helper\Edd;

use EDD_Subscription;
use Appsero\Helper\Traits\OrderHelper;

/**
 * Subscriptions
 */
class Subscriptions {

    use OrderHelper;

    public function get_items( $request ) {
        if ( ! class_exists( 'EDD_Recurring' ) ) {
            return new WP_Error( 'subscription_plugin_missing', 'Easy Digital Downloads - Recurring Payments plugin not found.', [ 'status' => 400 ] );
        }

        $product_id = $request->get_param( 'product_id' );
        $per_page     = $request->get_param( 'per_page' );
        $current_page = $request->get_param( 'page' );
        $offset       = ( $current_page - 1 ) * $per_page;

        global $wpdb;
        $table_name  = $wpdb->prefix . 'edd_subscriptions';
        $query = $wpdb->prepare( "SELECT SQL_CALC_FOUND_ROWS `id` FROM {$table_name} WHERE
                                `product_id` = {$product_id} ORDER BY `id` ASC LIMIT %d OFFSET %d;",
                                absint( $per_page ), absint( $offset ) );

        $items = $wpdb->get_col( $query );
        $total_items = $wpdb->get_var( 'SELECT FOUND_ROWS()' );
        $subscriptions = [];

        foreach ( $items as $item ) {
            $subscriptions[] = $this->get_subscription_data( $item );
        }

        $response = rest_ensure_response( $subscriptions );

        $max_pages = ceil( $total_items / $per_page );
        $response->header( 'X-WP-Total', (int) $total_items );
        $response->header( 'X-WP-TotalPages', (int) $max_pages );

        return $response;
    }


    /**
     * Prepare subscription data for response
     */
    public function get_subscription_data( $subscription_id, $individual = true ) {
        if ( empty( $subscription_id ) )
            return;

        $subscription = is_numeric( $subscription_id ) ? new EDD_Subscription( $subscription_id ) : $subscription_id;

        $subscription_response = [
            'id'                => $subscription->id,
            'start_date'        => $subscription->created,
            'last_ordered_date' => $this->get_last_ordered_date( $subscription ),
            'next_payment_date' => ( 'active' == $subscription->status ) ? $subscription->expiration : '',
            'end_date'          => ( 'cancelled' != $subscription->status && 'active' != $subscription->status ) ? $subscription->expiration : '',
            'cancelled_at'      => ( 'cancelled' == $subscription->status ) ? $subscription->expiration : '',
            'amount'            => $subscription->recurring_amount,
            'status'            => $subscription->status,
            'notes'             => '',
        ];

        return $subscription_response;
    }

    /**
     * Get last ordered date
     */
    private function get_last_ordered_date( $subscription ) {
        global $wpdb;

        $query = "SELECT post_date_gmt FROM {$wpdb->posts} WHERE `post_parent` = {$subscription->parent_payment_id}
                  AND `ID` IN ( SELECT `post_id` FROM {$wpdb->postmeta} WHERE `meta_key` = 'subscription_id'
                  AND meta_value = {$subscription->id} ) AND `post_type` = 'edd_payment'
                  ORDER BY `ID` DESC LIMIT 1;";
        $last_renewal_date = $wpdb->get_var( $query );

        if ( empty( $last_renewal_date ) ) {
            $query = "SELECT post_date_gmt FROM {$wpdb->posts} WHERE `ID` = {$subscription->parent_payment_id} LIMIT 1;";
            $last_renewal_date = $wpdb->get_var( $query );
        }

        return $last_renewal_date;
    }
}
