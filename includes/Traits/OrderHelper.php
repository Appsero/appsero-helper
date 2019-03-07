<?php
namespace Appsero\Helper\Traits;

/**
 * Order helper trait
 */
trait OrderHelper {

    /**
     * Generate customer data
     * @param array $order_data
     * @return array
     */
    private function woocommerce_customer( $order_data ) {
        if ( ! function_exists( 'wc' ) )
            return [];

        return [
            'id'       => $order_data['customer_id'],
            'email'    => $order_data['billing']['email'],
            'name'     => $order_data['billing']['first_name'] .' '. $order_data['billing']['last_name'],
            'address'  => $order_data['billing']['address_1'] .' '. $order_data['billing']['address_2'],
            'zip'      => $order_data['billing']['postcode'],
            'state'    => $this->get_state( $order_data['billing']['country'], $order_data['billing']['state'] ),
            'country'  => $this->get_country( $order_data['billing']['country'] ),
        ];
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


    /**
     * Get woocommerce order notes
     *
     * @return array
     */
    private function get_woocommerce_notes( $id ) {
        $notes = wc_get_order_notes( [
            'order_id' => $id,
        ] );

        $items = [];

        foreach ( $notes as $note ) {
            $items[] = [
                'id'         => $note->id,
                'message'    => $note->content,
                'added_by'   => ( $note->added_by == 'system' ) ? 'Woo Bot' : ucfirst( $note->added_by ),
                'created_at' => $note->date_created->date( 'Y-m-d H:i:s' ),
            ];
        }

        return $items;
    }
}
