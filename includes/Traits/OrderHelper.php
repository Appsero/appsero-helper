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

        if ( $order_data['customer_id'] ) {
            $user_id = $order_data['customer_id'];
        } else {
            $user_id = appsero_create_customer(
                $order_data['billing']['email'],
                $order_data['billing']['first_name'],
                $order_data['billing']['last_name']
            );
        }

        $user = get_userdata( $user_id );

        $first_name = empty( $user->user_firstname ) ? $order_data['billing']['first_name'] : $user->user_firstname;
        $last_name = empty( $user->user_lastname ) ? $order_data['billing']['last_name'] : $user->user_lastname;

        return [
            'id'       => $user ? $user->ID : $user_id,
            'email'    => $user ? $user->user_email : $order_data['billing']['email'],
            'name'     => $first_name . ' ' . $last_name,
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

    /**
     * Generate EDD customer data
     *
     * @return array
     */
    private function edd_customer_data( $payment ) {
        if ( function_exists( 'edd_software_licensing' ) && $payment->customer_id ) {
            $user_id = $payment->customer_id;
        } else {
            $user_id = appsero_create_customer(
                $payment->user_info['email'],
                $payment->user_info['first_name'],
                $payment->user_info['last_name']
            );
        }

        $user = get_userdata( $user_id );

        return [
            'id'       => (int) $user_id,
            'email'    => ! $user ? $user->user_email : $payment->user_info['email'],
            'name'     => $payment->user_info['first_name'] .' '. $payment->user_info['last_name'],
            'address'  => $payment->address['line1'] .' '. $payment->address['line2'],
            'zip'      => $payment->address['zip'],
            'state'    => edd_get_state_name( $payment->address['country'], $payment->address['state'] ),
            'country'  => edd_get_country_name( $payment->address['country'] ),
        ];
    }

    /**
     * Format float value
     */
    private function number_format( $number ) {
        return floatval( number_format( $number, 2, ".", "" ) );
    }

}
