<?php
namespace Appsero\Helper\Renderer;

class OrdersRenderer {

    /**
     * View invoice option show or not
     *
     * @var boolean
     */
    protected $view_invoice;

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

        do_action( 'before_appsero_myaccount_order_table' );
        ?>
        <div class="appsero-orders">
            <?php
                $orders = $this->get_orders();
                $this->view_invoice = $this->is_invoice_url($orders);
                if ( count( $orders ) > 0 ) :
            ?>
            <table class="appsero-order-table">
                <thead>
                    <tr>
                        <th>Order</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Total</th>
                        <?php if ( $this->view_invoice ) : ?>
                            <th>Actions</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php
                        foreach ( $orders as $order ) {
                            $this->single_order_output( $order );
                        }
                    ?>
                </tbody>
            </table>
            <?php else: ?>
                <div class="appsero-notice notice-info">No orders found.</div>
            <?php endif; ?>
        </div>
        <?php

        do_action( 'after_appsero_myaccount_order_table' );

        return ob_get_clean();
    }

    /**
     * Single order row
     */
    private function single_order_output( $order ) {
        ?>
        <tr>
            <td>#<?php echo $order['id']; ?></td>
            <td><?php echo $order['ordered_at']; ?></td>
            <td><?php echo $order['status']; ?></td>
            <td><?php
                echo isset( $order['currency'] ) ? $order['currency'] : '';
                echo $order['total'];
            ?></td>
            <?php if ($this->view_invoice) : ?>
                <td>
                    <?php if ($order['invoice_url']): ?>
                        <a href="<?php echo $order['invoice_url']; ?>">View Invoice</a>
                    <?php else: ?>
                        <span>N/A</span>
                    <?php endif; ?>
                </td>
            <?php endif; ?>
        </tr>
        <?php
    }

    /**
     * Get orders from appsero API
     */
    private function get_orders() {
        $user_id = get_current_user_id();
        $route   = 'public/users/' . $user_id . '/orders';

        // Send request to appsero server
        $response = appsero_helper_remote_get( $route );

        if ( wp_remote_retrieve_response_code( $response ) != 200 ) {
            return [];
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        return isset( $body['data'] ) ? $body['data'] : [];
    }

    /**
     * Count invoice urls to show the action tab
     *
     * @param array $orders
     * @return boolean
     */
    private function is_invoice_url($orders)
    {
        $invoice_urls = array_filter($orders, function($order) {
            return $order['invoice_url'];
        });
        return count($invoice_urls);
    }
}
