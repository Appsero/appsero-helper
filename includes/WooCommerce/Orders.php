<?php
namespace Appsero\Helper\WooCommerce;

use WC_Order;
use Appsero\Helper\Traits\OrderHelper;
use Appsero\Helper\WooCommerce\UseCases\SendRequestsHelper;

/**
 * Orders API
 */
class Orders {

    use OrderHelper, SendRequestsHelper;

    /**
     * Product id to manage cart item
     * @var integer
     */
    public $product_id;

    /**
     * Get a collection of orders.
     *
     * @param WP_REST_Request $request Full details about the request.
     *
     * @return WP_Error|WP_REST_Response
     */
    public function get_items( $request ) {
        $per_page         = $request->get_param( 'per_page' );
        $after            = $request->get_param( 'after' );
        $current_page     = $request->get_param( 'page' );
        $this->product_id = $request->get_param( 'product_id' );
        $offset           = ( $current_page - 1 ) * $per_page;

        list( $order_ids, $total_orders ) = $this->get_orders_ids( $per_page, $offset, $after );

        $items = [];

        foreach ( $order_ids as $order_id ) {
            $order_array = $this->get_order_data( $order_id );
            if ( ! empty( $order_array ) ) {
                $items[] = $order_array;
            }
        }

        $response = rest_ensure_response( $items );

        $max_pages = ceil( $total_orders / (int) $per_page );

        $response->header( 'X-WP-Total', (int) $total_orders );
        $response->header( 'X-WP-TotalPages', (int) $max_pages );
        $response->header( 'X-WP-Orders-After', $after );

        return $response;
    }

    /**
     * Get order IDs
     */
    private function get_orders_ids( $limit, $offset, $after = 0 ) {
        global $wpdb;
        $orders_statuses = array_keys( wc_get_order_statuses() );
        $orders_statuses = implode( "', '", $orders_statuses );

        $query = "SELECT SQL_CALC_FOUND_ROWS DISTINCT woi.order_id
            FROM {$wpdb->prefix}woocommerce_order_itemmeta as woim,
                 {$wpdb->prefix}woocommerce_order_items as woi,
                 {$wpdb->prefix}posts as p
            WHERE woi.order_item_id = woim.order_item_id
            AND woi.order_id = p.ID
            AND p.post_status IN ( '{$orders_statuses}' )
            AND p.post_type = 'shop_order' ";

        if ( !empty($after) ) {
            $query .= " AND p.post_modified_gmt >= '{$after}' ";
        }

        $query .= " AND woim.meta_key = '_product_id'
            AND woim.meta_value = '{$this->product_id}'
            ORDER BY woi.order_item_id ASC LIMIT {$limit} OFFSET {$offset}";

        $orders_ids = $wpdb->get_col( $query );
        $count = $wpdb->get_var('SELECT FOUND_ROWS()');

        return [ $orders_ids, $count ];
    }

    /**
     * Generate order data
     * @return array
     */
    public function get_order_data( $order_id, $cart = null ) {
        $order = is_numeric( $order_id ) ? wc_get_order( $order_id ) : $order_id;

        if ( ! is_a( $order, 'WC_Abstract_Order' ) ) {
            return false;
        }

        $order_data  = $order->get_data();
        $order_total = (float) $order->get_total();

        if ( ! is_a( $cart, 'WC_Order_Item_Product' ) ) {
            $cart = $this->get_cart_details( $order_data['line_items'] );
        }

        $total    = $this->number_format( $cart->get_total() );
        $subtotal = $this->number_format( $cart->get_subtotal() );
        $quantity = $cart->get_quantity();
        $price    = $this->number_format( $subtotal / $quantity );
        $tax      = $this->number_format( $cart->get_total_tax() );
        $discount = ( $subtotal > $total ) ? ( $subtotal - $total ) : 0;
        $discount = $this->number_format( $discount );

        // Calculate fee for this item
        $fee       = 0;
        $total_fee = $this->get_total_fee( $order_data['fee_lines'] );
        if ( ! empty( $order_total ) && ! empty( $total_fee ) ) {
            $order_total -= $total_fee;
            $cart_total  = $total + $tax;
            $fee         = $total_fee / $order_total;
            $fee         = $this->number_format( $fee * $cart_total );
        }

        // Varation ID
        $variation_id = $cart->get_variation_id();

        $order_response = [
            'id'             => $order_data['id'],
            'price'          => $price,
            'quantity'       => $quantity,
            'discount'       => $discount,
            'tax'            => $tax,
            'fee'            => $fee,
            'status'         => $order_data['status'],
            'ordered_at'     => $order_data['date_created']->date( 'Y-m-d H:i:s' ),
            'updated_at'     => date('Y-m-d H:i:s', $order_data['date_modified'] instanceof \WC_DateTime ? $order_data['date_modified']->getTimestamp() : time() ),
            'payment_method' => $order_data['payment_method_title'],
            'notes'          => $this->get_woocommerce_notes( $order_data['id'] ),
            'customer'       => $this->woocommerce_customer( $order_data ),
            'order_type'     => '',
            'subscription'   => [],
            'licenses'       => [],
            'variation_id'   => $variation_id ? $variation_id : '',
        ];

        // Check if WooCommerce subscription active and this order has subscription
        if ( function_exists( 'wcs_order_contains_subscription' ) && wcs_order_contains_subscription( $order, [ 'any' ] ) ) {
            $this->subscription = null;
            $order_response = $this->add_subscription_response( $order_response, $order, $order_data );
        }

        $order_response['licenses'] = $this->get_order_licenses( $order );

        return apply_filters( 'appsero_woocommerce_order', $order_response, $order_data, $cart );
    }

    /**
     * Get cart of this product
     */
    private function get_cart_details( $carts ) {
        foreach( $carts as $cart ) {
            if ( $cart->get_product_id() == $this->product_id ) {
                return $cart;
            }
        }
    }

    /**
     * Get total fees
     */
    private function get_total_fee( $fee_lines ) {
        if ( empty( $fee_lines ) )
            return 0;

        $fees = 0;
        foreach( $fee_lines as $fee ) {
            $fees += $fee->get_total();
        }

        return $fees;
    }

    /**
     * Get order type
     */
    private function get_order_type( $order_id, $subscription_id ) {
        global $wpdb;
        $query = "SELECT * FROM $wpdb->postmeta WHERE post_id = {$order_id}
                  AND (
                    meta_key = '_subscription_renewal'
                    OR meta_key = '_subscription_resubscribe'
                    OR meta_key = '_subscription_switch'
                  )
                  AND meta_value = {$subscription_id}
                  LIMIT 1";
        $result = $wpdb->get_row( $query, ARRAY_A );

        if ( empty( $result ) ) {
            return 'parent';
        }

        return str_replace( '_subscription_', '', $result['meta_key'] );
    }

    /**
     * Add subscription data to order array
     */
    private function add_subscription_response( $order_response, $order, $order_data ) {
        $subscriptions = wcs_get_subscriptions_for_order( $order, [
            'product_id' => $this->product_id,
            'order_type' => 'any',
        ] );

        if ( ! empty( $subscriptions ) && count( $subscriptions ) == 1 ) {
            $subscription = array_shift( $subscriptions );
            if ( wcs_is_subscription( $subscription ) ) {
                $this->subscription = $subscription;
                require_once __DIR__ . '/Subscriptions.php';
                $subscription_data = ( new Subscriptions() )->get_subscription_data( $subscription, false );
                $order_response['subscription'] = $subscription_data;
                $order_response['order_type'] = $this->get_order_type( $order_data['id'], $subscription_data['id'] );
            }
        }

        return $order_response;
    }

    /**
     * Get licenses of active add-on
     */
    private function get_order_licenses( $order ) {
        require_once __DIR__ . '/Licenses.php';

        $licensesObject = new Licenses();

        foreach( $order->get_items( 'line_item' ) as $wooItem ) {
            $this->get_order_item_licenses( $order, $this->product_id, $licensesObject, $wooItem );
        }

        return $licensesObject->licenses;
    }

}
