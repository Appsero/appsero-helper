<?php
namespace Appsero\Helper\Edd;

use WP_Query;
use EDD_Payments_Query;

/**
 * Licenses
 */
class Orders {

    /**
     * Product id to get orders
     * @var [type]
     */
    protected $download_id;

    /**
     * Get a collection of orders.
     *
     * @param WP_REST_Request $request Full details about the request.
     *
     * @return WP_Error|WP_REST_Response
     */
    public function get_items( $request ) {
        $per_page    = $request->get_param( 'per_page' );
        $paged       = $request->get_param( 'page' );
        $this->download_id = $request->get_param( 'product_id' );

        $args = [
            'download' => $this->download_id,
            'fields'   => 'ids',
            'number'   => $per_page,
            'page'     => $paged,
            'orderby'  => 'ID',
            'order'    => 'ASC',
        ];

        list( $order_ids, $total_posts ) = $this->edd_get_payments( $args );

        $items = [];

        foreach ( $order_ids as $order_id ) {
            $items[] = $this->get_order_data( $order_id );
        }

        $response = rest_ensure_response( $items );

        $max_pages   = ceil( $total_posts / (int) $per_page );

        $response->header( 'X-WP-Total', (int) $total_posts );
        $response->header( 'X-WP-TotalPages', (int) $max_pages );

        return $response;
    }

    /**
     * Generate order data
     * @return array
     */
    private function get_order_data( $order_id ) {
        $payment = edd_get_payment( $order_id );

        $cart = $this->get_cart_details( $payment->cart_details );

        return [
            'id'         => $payment->ID,
            'price'      => $cart['price'],
            'quantity'   => $cart['quantity'],
            'status'     => $this->get_order_status( $payment->status ),
            'customer' => [
                'id'      => (int) $payment->user_info['id'],
                'email'   => $payment->user_info['email'],
                'name'    => $payment->user_info['first_name'] .' '. $payment->user_info['last_name'],
                'address' => $payment->address['line1'] .' '. $payment->address['line2'],
                'zip'     => $payment->address['zip'],
                'state'   => $payment->address['state'],
                'country' => $payment->address['country'],
            ],
            'ordered_at' => $payment->date,
        ];
    }

    /**
     * Extend EDD_Payments_Query class to get necessary output
     *
     * @return  array
     */
    private function edd_get_payments( $args ) {
        $payments = new EDD_Payments_Query( $args );

        $payments->orderby();
        $payments->per_page();
        $payments->page();
        $payments->download();
        $payments->post__in();

        $query = new WP_Query();
        $query_result = $query->query( $payments->args );

        return [ $query_result, $query->found_posts ];
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
     * Tranform status
     */
    private function get_order_status( $status ) {
        if ( 'publish' == $status ) {
            return 2;
        } else if ( 'pending' == $status || 'processing' == $status ) {
            return 1;
        } else {
            return 3;
        }
    }
}
