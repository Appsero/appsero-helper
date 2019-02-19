<?php
namespace Appsero\Helper\WooCommerce;

use WC_Order;

/**
 * Licenses
 */
class Orders {

    protected $product_id;

    /**
     * Get a collection of orders.
     *
     * @param WP_REST_Request $request Full details about the request.
     *
     * @return WP_Error|WP_REST_Response
     */
    public function get_items( $request ) {
        $per_page         = $request->get_param( 'per_page' );
        $current_page     = $request->get_param( 'page' );
        $this->product_id = $request->get_param( 'product_id' );
        $offset           = ( $current_page - 1 ) * $per_page;

        list( $order_ids, $total_orders ) = $this->get_orders_ids( $per_page, $offset );

        $items = [];

        foreach ( $order_ids as $order_id ) {
            $items[] = $this->get_order_data( $order_id );
        }

        $response = rest_ensure_response( $items );

        $max_pages = ceil( $total_orders / (int) $per_page );

        $response->header( 'X-WP-Total', (int) $total_orders );
        $response->header( 'X-WP-TotalPages', (int) $max_pages );

        return $response;
    }

    /**
     * Get order IDs
     */
    private function get_orders_ids( $limit, $offset ) {
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
            AND woim.meta_key = '_product_id'
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
    private function get_order_data( $order_id ) {
        $order = new WC_Order( $order_id );

        $order_data  = $order->get_data();
        $order_total = (float) $order->get_total();

        $cart     = $this->get_cart_details( $order_data['line_items'] );
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

        return [
            'id'             => $order_data['id'],
            'price'          => $price,
            'quantity'       => $quantity,
            'discount'       => $discount,
            'tax'            => $tax,
            'fee'            => $fee,
            'status'         => $order_data['status'],
            'ordered_at'     => $order_data['date_created']->date( 'Y-m-d H:i:s' ),
            'payment_method' => $order_data['payment_method_title'],
            'customer'       => [
                'id'       => $order_data['customer_id'],
                'email'    => $order_data['billing']['email'],
                'name'     => $order_data['billing']['first_name'] .' '. $order_data['billing']['last_name'],
                'address'  => $order_data['billing']['address_1'] .' '. $order_data['billing']['address_2'],
                'zip_code' => $order_data['billing']['postcode'],
                'state'    => $this->get_state( $order_data['billing']['country'], $order_data['billing']['state'] ),
                'country'  => $this->get_country( $order_data['billing']['country'] ),
            ],
        ];
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
     * Format float value
     */
    private function number_format( $number ) {
        return floatval( number_format( $number, 2, ".", "" ) );
    }

    /**
     * Get country name
     */
    private function get_country( $code ) {
        $countries = wc()->countries->get_countries();

        if ( isset( $countries[ $code ] ) ) {
            return $countries[ $code ];
        }

        return $code;
    }

    /**
     * Get state name
     */
    private function get_state( $country, $code ) {
        $states = wc()->countries->get_states( $country );

        if ( isset( $states[ $code ] ) ) {
            return $states[ $code ];
        }

        return $code;
    }
}
