<?php
namespace Appsero\Helper;

class OrdersRenderer {

    /**
     * Show orders of user
     */
    public function show() {
        wp_enqueue_style( 'ashp-my-account' );
        wp_enqueue_script( 'ashp-my-account' );

        // If user not logged in
        if ( ! is_user_logged_in() ) {
            return '<div class="appsero-notice notice-error">You must logged in to get orders.</div>';
        }

        ob_start();
        ?>
        <div class="appsero-orders">
            <table class="appsero-order-table">
                <thead>
                    <tr>
                        <th>Order</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Total</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                        $orders = $this->get_orders();

                        foreach ( $orders as $order ) {

                            $this->single_order_output( $order );

                        }
                    ?>

                    <tr>
                        <td>#123</td>
                        <td>August 30, 2019</td>
                        <td>Completed</td>
                        <td>0.00</td>
                        <td><a href="#">View Invoice</a></td>
                    </tr>
                    <tr>
                        <td>#123</td>
                        <td>August 30, 2019</td>
                        <td>Completed</td>
                        <td>0.00</td>
                        <td><a href="#">View Invoice</a></td>
                    </tr>
                </tbody>
            </table>
        </div>
        <?php

        return ob_get_clean();
    }

    /**
     * Single order row
     */
    private function single_order_output( $order ) {
        ?>
        <tr>
            <td>#123</td>
            <td>August 30, 2019</td>
            <td>Completed</td>
            <td>0.00</td>
            <td><a href="#">View Invoice</a></td>
        </tr>
        <?php
    }

    /**
     * Get orders from appsero API
     */
    private function get_orders() {
        $route = 'public/users/' . $user_id . '/orders';

        // Send request to appsero server
        $response = appsero_helper_remote_get( $route );

        if ( wp_remote_retrieve_response_code( $response ) != 200 ) {
            return [];
        }

        return json_decode( wp_remote_retrieve_body( $response ), true );
    }
}
