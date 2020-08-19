<?php
namespace Appsero\Helper\WooCommerce\UseCases;

trait SendRequestsHelper {

    /**
     * Set subscription object if has
     *
     * @var WC_Subscription|null
     */
    public $subscription;

    /**
     * Get licenses of an order
     */
    private function get_order_item_licenses( $order, $product_id, $licensesObject, $wooItem ) {

        $status = $order->get_status() == 'completed' ? 1 : 0;

        // if WooCommerce Software Addon Exists
        if ( class_exists( 'WC_Software' ) ) {
            return $this->woo_sa_licenses( $order, $product_id, $licensesObject, $status );
        }

        // if WooCommerce API Manager Exists
        if ( class_exists( 'WooCommerce_API_Manager' ) ) {
            // If version 1.*
            if ( function_exists( 'WC_AM_HELPERS' ) ) {
                return $this->woo_legacy_api_licenses( $order, $product_id, $licensesObject, $status, $wooItem );
            }

            // If version above 2.*
            if ( version_compare( WCAM()->version, '2.0.0', '>=' ) ) {
                return $this->woo_api_licenses( $order, $product_id, $licensesObject );
            }
        }

        return [];
    }

    /**
     * WooCommerce SA license
     */
    private function woo_sa_licenses( $order, $product_id, $licensesObject, $status ) {
        global $wpdb;

        // Get parent order ID
        if ( function_exists( 'wcs_is_subscription' ) && wcs_is_subscription( $this->subscription ) ) {
            $order_id = $this->subscription->get_parent_id();
        } else {
            $order_id = $order->get_id();
        }

        $software_id = get_post_meta( $product_id, '_software_product_id', true);

        $query = "SELECT * FROM {$wpdb->wc_software_licenses} WHERE `order_id` = {$order_id} AND `software_product_id` = '{$software_id}' ";
        $licenses = $wpdb->get_results( $query, ARRAY_A );

        foreach ( $licenses as $license ) {
            $licensesObject->get_woo_sa_license_data( $license, $product_id, $status, $order );
        }

        return $licensesObject->licenses;
    }

    /**
     * WooCommerce API licenses for V1
     */
    private function woo_legacy_api_licenses( $order, $product_id, $licensesObject, $status, $wooItem ) {
        global $wpdb;

        $order_id = $order->get_id();
        $api_keys_exist = WC_AM_HELPERS()->order_api_keys_exist( $order_id );

        if ( $api_keys_exist && WC_AM_HELPERS()->is_api( $product_id ) ) {
            $quantity = $wooItem->get_quantity();
            $user_id = $order->get_user_id();

            for ( $loop = 0; $loop < $quantity; $loop++ ) {
                $metakey = '_api_license_key_' . $loop;
                $license_key = get_post_meta( $order_id, $metakey, true );
                $license_data = get_user_meta( $user_id, $wpdb->get_blog_prefix() . WC_AM_HELPERS()->user_meta_key_orders, true );

                if ( ! empty( $license_key ) && ! empty( $license_data ) ) {
                    $licensesObject->get_woo_legacy_api_license_data( $license_key, true, $status, $license_data );
                }
            } // End for
        }

        return $licensesObject->licenses;
    }

    /**
     * Get WooCommerce API manager licenses for a specific product
     */
    private function woo_api_licenses( $order, $product_id, $licensesObject ) {
        global $wpdb;

        $table_name = $wpdb->prefix . WC_AM_USER()->get_api_resource_table_name();
        $order_id   = $order->get_id();

        $sql = "
            SELECT * FROM {$table_name}
            WHERE order_id = %d
            AND product_id = %d
        ";

        $resources = $wpdb->get_results( $wpdb->prepare( $sql, $order_id, $product_id ), ARRAY_A );

        foreach( $resources as $resource ) {
             $licensesObject->generate_woo_api_license_data( $resource, true );
        }

        return $licensesObject->licenses;
    }

}
