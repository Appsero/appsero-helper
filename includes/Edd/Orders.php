<?php
namespace Appsero\Helper\Edd;

use WP_Query;
use EDD_Payments_Query;
use EDD_Subscriptions_DB;
use Appsero\Helper\Traits\OrderHelper;
use Appsero\Helper\Edd\UseCases\SendRequestsHelper;

/**
 * Licenses
 */
class Orders {

    use OrderHelper, SendRequestsHelper;

    /**
     * Product id to get orders
     * @var [type]
     */
    public $download_id;

    /**
     * Get a collection of orders.
     *
     * @param WP_REST_Request $request Full details about the request.
     *
     * @return WP_Error|WP_REST_Response
     */
    public function get_items( $request ) {
        $this->download_id = $request->get_param( 'product_id' );
        $after             = $request->get_param( 'after' ); // After the date
        $per_page          = $request->get_param( 'per_page' );
        $current_page      = $request->get_param( 'page' );
        $offset            = ( $current_page - 1 ) * $per_page;

        global $wpdb;
        $order_ids    = $wpdb->get_col( $this->get_order_ids_query( $per_page, $offset, $after ) );
        $total_orders = $wpdb->get_var( 'SELECT FOUND_ROWS()' );

        $items = [];

        foreach ( $order_ids as $order_id ) {
            $items[] = $this->get_order_data( $order_id );
        }

        $response = rest_ensure_response( $items );

        $max_pages = ceil( $total_orders / (int) $per_page );

        $response->header( 'X-WP-Total', (int) $total_orders );
        $response->header( 'X-WP-TotalPages', (int) $max_pages );
        $response->header( 'X-WP-Orders-After', $after );

        return $response;
    }

    /**
     * Generate order data
     *
     * @param $order_id
     *
     * @return array
     */
    public function get_order_data( $order_id ) {
        $payment = is_numeric( $order_id ) ? edd_get_payment( $order_id ) : $order_id;

        $cart = $this->get_cart_details( $payment->cart_details );

        // Find variation ID
        if ( isset( $cart['item_number']['options']['price_id'] ) && $cart['item_number']['options']['price_id'] ) {
            $variation_id = $cart['item_number']['options']['price_id'];
        } else {
            $variation_id = '';
        }

        // Calculate fee for this item
        $fee = 0;
        if ( ! empty( $payment->subtotal ) && ! empty( $payment->fees_total ) ) {
            $fee = $payment->fees_total / $payment->subtotal;
            $fee = $this->number_format( $fee * $cart['subtotal'] );
        }

        $order_response = [
            'id'             => $payment->ID,
            'price'          => $cart['item_price'],
            'quantity'       => $cart['quantity'],
            'discount'       => $cart['discount'],
            'tax'            => $cart['tax'],
            'fee'            => $fee,
            'status'         => $this->get_order_status( $payment->status ),
            'ordered_at'     => $payment->date,
            'updated_at'     => get_post($payment->ID)->post_modified_gmt,
            'payment_method' => $this->format_payment_method( $payment->gateway ),
            'notes'          => $this->get_notes( $payment->ID ),
            'customer'       => $this->edd_customer_data( $payment ),
            'order_type'     => '',
            'subscription'   => [],
            'licenses'       => $this->get_order_licenses( $payment->ID, $this->download_id ),
            'variation_id'   => $variation_id,
        ];

        // Check if EDD Recurring Payments active and this order has subscription
        if ( class_exists( 'EDD_Recurring' ) ) {
            $order_response = $this->add_subscription_response( $order_response, $payment );
        }

        return apply_filters( 'appsero_edd_order', $order_response, $payment, $cart );
    }

    /**
     * Get cart of this product
     */
    private function get_cart_details( $carts ) {
        foreach( $carts as $cart ) {
            if ( $cart['id'] == $this->download_id ) {
                return $cart;
            }
        }
    }

    /**
     * Transform status similar to WooCommerce
     *
     * @param $status
     *
     * @return string
     */
    private function get_order_status( $status ) {
        switch ( $status ) {
            case 'publish':
            case 'edd_subscription':
                return 'completed';

            case 'pending':
                return 'pending';

            case 'processing':
                return 'processing';

            case 'refunded':
                return 'refunded';

            case 'failed':
                return 'failed';

            case 'abandoned':
            case 'revoked':
                return 'cancelled';
            case 'complete':
                return 'completed';

            default:
                return $status;
        }
    }

    /**
     * Format getway string
     */
    private function format_payment_method( $gateway ) {
        switch ( $gateway ) {
            case 'paypal':
                return 'PayPal Standard';

            case 'manual':
                return 'Test Payment';

            case 'amazon':
                return 'Amazon';

            default:
                return $gateway;
        }
    }

    /**
     * Get order notes
     *
     * @return array
     */
    private function get_notes( $id ) {
        $notes = edd_get_payment_notes( $id );

        $items = [];

        foreach ( $notes as $note ) {
            if ( ! empty( $note->user_id ) ) {
                $user     = get_userdata( $note->user_id );
                $added_by = $user ? $user->display_name : 'Unknown';
            } else {
                $added_by = __( 'EDD Bot', 'easy-digital-downloads' );
            }

            $items[] = [
                'id'         => (int) $note->comment_ID,
                'message'    => $note->comment_content,
                'added_by'   => $added_by,
                'created_at' => $note->comment_date,
            ];
        }

        return $items;
    }

    /**
     * Get subscription for order
     */
    private function get_subscription( $customer_id, $parent_payment ) {
        $subscription = ( new EDD_Subscriptions_DB() )->get_subscriptions( [
            'customer_id'       => $customer_id,
            'product_id'        => $this->download_id,
            'parent_payment_id' => $parent_payment,
        ] );

        if ( ! empty( $subscription ) ) {
            return array_shift( $subscription );
        }

        return false;
    }

    /**
     * Format subscription data
     */
    private function format_subscription_data( $subscription ) {
        require_once __DIR__ . '/Subscriptions.php';
        return ( new Subscriptions() )->get_subscription_data( $subscription, false );
    }

    /**
     * Add subscription response to order array
     */
    private function add_subscription_response( $order_response, $payment ) {
        // Check this product is renewal
        if ( 'edd_subscription' == $payment->status && ! empty( $payment->parent_payment ) ) {
            $order_response['order_type'] = 'renewal';

            $subscription = $this->get_subscription( $order_response['customer']['id'], $payment->parent_payment );

            if ( $subscription ) {
                $order_response['subscription'] = $this->format_subscription_data( $subscription );
            }
        } else {
            $subscription = $this->get_subscription( $order_response['customer']['id'], $order_response['id'] );

            if ( $subscription ) {
                $order_response['order_type']   = 'parent';
                $order_response['subscription'] = $this->format_subscription_data( $subscription );
            }
        }

        return $order_response;
    }

    /**
     * SQL query of get order ids
     */
    private function get_order_ids_query( $per_page, $offset, $after ) {
        global $wpdb;

        $query = "SELECT SQL_CALC_FOUND_ROWS `ID` FROM {$wpdb->posts}
                  WHERE `post_type` = 'edd_payment' AND
                  `ID` IN (
                      SELECT `post_id` FROM {$wpdb->postmeta}
                      WHERE `meta_value` LIKE '%{s:2:\"id\";i:%d;s:8:\"quantity\";i%'
                  )
                  AND `post_status` IN (
                      'abandoned', 'edd_subscription', 'failed', 'pending', 'processing', 'publish', 'refunded', 'revoked'
                  )
                  AND `post_modified_gmt` >= '{$after}'
                  ORDER BY `ID` ASC LIMIT %d OFFSET %d;";

        return $wpdb->prepare( $query, $this->download_id, $per_page, $offset );
    }
}
