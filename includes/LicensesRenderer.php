<?php
namespace Appsero\Helper;

class LicensesRenderer {

    public function show() {

        wp_enqueue_style( 'ashp-my-account' );
        wp_enqueue_script( 'ashp-my-account' );

        // If user not logged in
        if ( ! is_user_logged_in() ) {
            return '<div class="appsero-notice notice-error">You must logged in to get licenses.</div>';
        }

        ob_start();
        ?>
        <div class="appsero-licenses">

            <?php
                $licenses = $this->get_licenses();

                if ( count( $licenses ) > 0 ) :
                foreach ( $licenses as $license ) :

                $product = wc_get_product( $license['product_id'] );

                list( $expires_on, $activations ) = $this->get_activations_and_expires( $license );
            ?>
            <div class="appsero-license" data-showing="0"
                data-sourceid="<?php echo $license['source_id']; ?>"
                data-productid="<?php echo $license['product_id']; ?>"
                data-licenseid="<?php echo $license['id']; ?>"
            >
                <div class="license-header">
                    <div class="license-product-info">
                        <div class="license-product-title">
                            <h2><?php echo $product->get_name(); ?></h2>
                            <p class="h3">Variation</p>
                        </div>
                        <div class="license-product-expire">
                            <h4>Expires On</h4>
                            <p class="h3"><?php echo $expires_on; ?></p>
                        </div>
                        <div class="license-product-activation">
                            <h4>Activations Remaining</h4>
                            <p class="h3"><?php echo $license['activation_limit'] - count( $activations ); ?></p>
                        </div>
                    </div>
                    <div class="license-toggle-info">
                        <i class="fas fa-angle-down"></i>
                    </div>
                </div>

                <?php $this->print_activations( $license, $activations ); ?>

            </div>
            <?php
                endforeach;
                else:
            ?>
                <div class="appsero-notice notice-info">No licenses found.</div>
            <?php endif; ?>

        </div>
        <?php

        return ob_get_clean();
    }

    /**
     * Print activations
     */
    private function print_activations( $license, $activations ) {
        ?>
        <div class="license-key-activations">
            <div class="appsero-license-key">
                <p><strong>Key</strong> <span class="license-key-code"><?php echo $license['key']; ?></span></p>
            </div>
            <div class="appsero-activations">
                <?php if ( count( $activations ) > 0 ) : ?>
                <h4>Activations</h4>

                <?php foreach ( $activations as $activation ) : ?>
                <div class="appsero-activation-item">
                    <span><?php echo $activation['site_url']; ?></span>
                    <a href="#" data-activationid="<?php echo $activation['id']; ?>" class="remove-activation-button">Remove</a>
                </div>
                <?php endforeach; ?>

                <?php else: ?>
                    <p style="margin: 0;">No activations found.</p>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Licenses of an user
     */
    private function get_licenses() {
        $user_id = get_current_user_id();

        $order_ids = wc_get_orders( [
            'customer' => get_current_user_id(),
            'return'   => 'ids',
            'paginate' => false,
            'limit'    => -1,
        ] );

        // First try to get licenses from WP table
        $licenses = $this->get_stored_licenses( $user_id, $order_ids );

        // If no data found then get from appsero API
        if ( empty( $licenses ) ) {
            $licenses = $this->get_appsero_licenses( $user_id, $order_ids );
        }

        return $licenses;
    }

    /**
     * Get licenses from WP database
     */
    private function get_stored_licenses( $user_id, $order_ids ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'appsero_licenses';
        $sql = "
            SELECT * FROM {$table_name}
            WHERE `user_id` = {$user_id}
            AND `order_id` IN ( " . implode( $order_ids, ',' ) . " )
        ";
        return $wpdb->get_results( $sql, ARRAY_A );
    }

    /**
     * Prpare activation and expires
     */
    private function get_activations_and_expires( $license ) {
        if ( empty( $license['expire_date'] ) ) {
            $expires_on = 'Unlimited';
        } else {
            $date_time = \DateTime::createFromFormat( 'Y-m-d H:i:s', $license['expire_date'] );
            $expires_on = $date_time->format( 'M jS, Y' );
        }

        if ( is_array( $license['activations'] ) ) {
            $activations = $license['activations'];
        } else {
            $activations = json_decode( $license['activations'], true );
            $activations = ( ! is_array( $activations ) ) ? [] : $activations;
        }

        return [ $expires_on, $activations ];
    }

    /**
     * Get license from appsero API
     */
    private function get_appsero_licenses( $user_id, $order_ids ) {
        $query = http_build_query( [ 'orders_id' => $order_ids ] );

        $route = 'public/users/' . $user_id . '/licenses?' . $query;

        // Send request to appsero server
        $response = appsero_helper_remote_get( $route );

        if ( wp_remote_retrieve_response_code( $response ) != 200 ) {
            return [];
        }

        $response = json_decode( wp_remote_retrieve_body( $response ), true );

        // Store licenses
        if ( isset( $response['data'] ) && ! empty( $response['data'] ) ) {
            $this->store_appsero_licenses( $response['data'] );

            // Get newly stored licenses
            return $this->get_stored_licenses( $user_id, $order_ids );
        }

        return [];
    }

    /**
     * Store licenses that are received from appsero
     */
    private function store_appsero_licenses( $licenses ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'appsero_licenses';

        foreach ( $licenses as $license ) {
            $wpdb->insert( $table_name, [
                'product_id'       => $license['product_id'],
                'variation_id'     => $license['variation_id'],
                'order_id'         => $license['order_id'],
                'user_id'          => $license['user_id'],
                'key'              => $license['key'],
                'status'           => $license['status'],
                'activation_limit' => $license['activation_limit'],
                'expire_date'      => $license['expire_date'],
                'activations'      => json_encode( $license['activations'] ),
                'source_id'        => $license['source_id'],
            ] );
        }
    }

}
