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
        $per_page          = $request->get_param( 'per_page' );
        $paged             = $request->get_param( 'page' );
        $this->download_id = $request->get_param( 'product_id' );

        $args = [
            'download' => $this->download_id,
            'fields'   => 'ids',
            'number'   => $per_page,
            'page'     => $paged,
            'orderby'  => 'ID',
            'order'    => 'ASC',
        ];

        list( $order_ids, $total_orders ) = $this->edd_get_payments( $args );

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
     * Generate order data
     * @return array
     */
    private function get_order_data( $order_id ) {
        $payment = edd_get_payment( $order_id );

        $cart = $this->get_cart_details( $payment->cart_details );

        // Calculate fee for this item
        $fee = 0;
        if ( ! empty( $payment->subtotal ) && ! empty( $payment->fees_total ) ) {
            $fee = $payment->fees_total / $payment->subtotal;
            $fee = $this->number_format( $fee * $cart['subtotal'] );
        }

        return [
            'id'             => $payment->ID,
            'price'          => $cart['item_price'],
            'quantity'       => $cart['quantity'],
            'discount'       => $cart['discount'],
            'tax'            => $cart['tax'],
            'fee'            => $fee,
            'status'         => $this->get_order_status( $payment->status ),
            'ordered_at'     => $payment->date,
            'payment_method' => $this->format_payment_method( $payment->gateway ),
            'notes'          => $this->get_notes( $payment->ID ),
            'customer'       => [
                'id'       => (int) $payment->user_info['id'],
                'email'    => $payment->user_info['email'],
                'name'     => $payment->user_info['first_name'] .' '. $payment->user_info['last_name'],
                'address'  => $payment->address['line1'] .' '. $payment->address['line2'],
                'zip_code' => $payment->address['zip'],
                'state'    => edd_get_state_name( $payment->address['country'], $payment->address['state'] ),
                'country'  => edd_get_country_name( $payment->address['country'] ),
            ],
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
     * Transform status similar to WooCommerce
     *
     * @param $status
     *
     * @return string
     */
    private function get_order_status( $status ) {
        switch ( $status ) {
            case 'publish':
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
                return 'cancelled';

            case 'revoked':
                return 'cancelled';

            default:
                return 'on-hold';
        }
    }

    /**
     * Format float value
     */
    private function number_format( $number ) {
        return floatval( number_format( $number, 2, ".", "" ) );
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

}
