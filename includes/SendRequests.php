<?php
namespace Appsero\Helper;

use EDD_SL_License;
use EDD_SL_Download;

/**
 * SendRequests Class
 * Send request to appsero sever
 */
class SendRequests {

    use Traits\Hooker;

    public function __construct() {

        // EDD Hooks
        $this->action( 'edd_complete_download_purchase', 'edd_add_license', 20, 5 );

        $this->action( 'edd_sl_post_revoke_license', 'edd_cancel_order', 10, 2 );

        $this->action( 'edd_sl_post_delete_license', 'edd_cancel_order', 10, 2 );

        // Woo Hooks
        $this->action( 'woocommerce_order_status_completed', 'woo_add_license', 20, 2 );

        $this->action( 'woocommerce_order_status_changed', 'woo_status_changed', 20, 4 );

    }

    /**
     * Send request to add license
     */
    public function edd_add_license( $download_id = 0, $payment_id = 0, $type = 'default', $cart_item = [], $cart_index = 0 ) {
        // Bail if this cart item is for a renewal
        if( ! empty( $cart_item['item_number']['options']['is_renewal'] ) ) {
            return;
        }

        // Bail if this cart item is for an upgrade
        if( ! empty( $cart_item['item_number']['options']['is_upgrade'] ) ) {
            return;
        }

        $purchased_download = new EDD_SL_Download( $download_id );
        if ( ! $purchased_download->is_bundled_download() && ! $purchased_download->licensing_enabled() ) {
            return;
        }

        $licenses = edd_software_licensing()->get_licenses_of_purchase( $payment_id );

        if ( false !== $licenses ) {
            foreach( $licenses as $license ) {
                $this->send_add_license_request( $license );
            }
        }
    }

    /**
     * Send request to appsero server to add license
     * For EDD
     */
    public function send_add_license_request( $license ) {
        $status = ('active' == $license->status || 'inactive' == $license->status) ? 1 : 0;
        $expiration = $license->expiration ? date( 'Y-m-d H:i:s', (int) $license->expiration ) : '';

        $route = 'public/' . $license->download_id . '/add-license';

        $body = [
            'key'               => $license->key,
            'status'            => $status,
            'activation_limit'  => $license->activation_limit ?: '',
            'active_sites'      => (int) $license->activation_count,
            'expire_date'       => $expiration,
            'variation_source'  => (int) $license->price_id ?: '',
            'license_source'    => 'EDD',
        ];

        appsero_helper_remote_post( $route, $body );
    }

    /**
     * Hook trigger after cancel order
     * It will deactive a license on appsero server
     */
    public function edd_cancel_order( $license_id, $payment_id ) {
        $license = new EDD_SL_License( $license_id );

        $route = 'public/' . $license->download_id . '/cancel-order';

        $body = [
            'key'            => $license->key,
            'license_source' => 'EDD',
            'order_id'       => $payment_id,
        ];

        appsero_helper_remote_post( $route, $body );
    }

    /**
     * Woocommerce send request to AppSero
     */
    public function woo_add_license( $order_id, $order ) {

        // if WooCommerce Software Addon Exists
        if ( class_exists( 'WC_Software' ) ) {
            return $this->woo_sa_add_license( $order_id, $order );
        }

        // if WooCommerce API Manager Exists
        if ( class_exists( 'WooCommerce_API_Manager' ) ) {
            return $this->woo_api_add_license( $order_id, $order );
        }
    }

    /**
     * Send request to appsero server to add license
     * For Woo API
     */
    private function send_woo_api_add_license_request( $user_id, $license_key, $product_id ) {
        global $wpdb;
        $license_data = get_user_meta( $user_id, $wpdb->get_blog_prefix() . WC_AM_HELPERS()->user_meta_key_orders, true );

        if ( ! isset( $license_data[ $license_key ] ) ) {
            return false;
        }

        $license = $license_data[ $license_key ];

        $route = 'public/' . $product_id . '/add-license';

        $body = [
            'key'              => $license['api_key'],
            'status'           => 1,
            'activation_limit' => $license['_api_activations'] ?: '',
            'active_sites'     => 0,
            'expire_date'      => '',
            'variation_source' => (int) $license['variable_product_id'] ?: '',
            'license_source'   => 'Woo API',
        ];

        return appsero_helper_remote_post( $route, $body );
    }

    /**
     * Send add license request to AppSero server
     */
    private function woo_api_add_license( $order_id, $order ) {
        if ( ! WC_AM_SUBSCRIPTION()->is_subscription_renewal_order( $order_id ) ) {
            // $order = wc_get_order( $order_id );
            $api_keys_exist = WC_AM_HELPERS()->order_api_keys_exist( $order_id );

            $order_items = $order->get_items();

            if ( $api_keys_exist && count( $order_items ) > 0 && $order->has_downloadable_item() ) {
                foreach ( $order_items as $item ) {
                    $product_id = $item->get_product_id();

                    if ( WC_AM_HELPERS()->is_api( $product_id ) ) {
                        $quantity = $item->get_quantity();

                        for ( $loop = 0; $loop < $quantity; $loop++ ) {
                            $metakey = '_api_license_key_' . $loop;
                            $license_key = get_post_meta( $order->get_id(), $metakey, true);
                            if ( ! empty( $license_key ) ) {
                                $this->send_woo_api_add_license_request( $order->get_user_id(), $license_key, $product_id );
                            }
                        } // End for
                    }

                } // End foreach
            }
        } // End if
    }

    /**
     * Woocommerce status change
     */
    public function woo_status_changed( $order_id, $status_from, $status_to, $order ) {
        $haystack = [ 'on-hold', 'cancelled', 'refunded', 'failed' ];

        if ( 'completed' == $status_from && in_array( $status_to, $haystack ) ) {
            // if WooCommerce Software Addon Exists
            if ( class_exists( 'WC_Software' ) ) {
                return $this->woo_sa_cancel_order( $order_id, $order );
            }

            // if WooCommerce API Manager Exists
            if ( class_exists( 'WooCommerce_API_Manager' ) ) {
                return $this->woo_api_cancel_order( $order_id, $order );
            }
        }
    }

    /**
     * Send request to appsero server to add license
     * For Woo SA
     */
    private function woo_sa_add_license( $order_id, $order ) {
        global $wpdb;

        $order_items = $order->get_items();
        if ( sizeof( $order_items ) == 0 ) {
            return;
        }

        foreach ( $order_items as $order_item ) {
            $product_id  = $order_item->get_product_id();
            $software_id = get_post_meta( $product_id, '_software_product_id', true);

            $query = "SELECT * FROM {$wpdb->wc_software_licenses} WHERE `order_id` = {$order_id} AND `software_product_id` = '{$software_id}' ";
            $license = $wpdb->get_row( $query, ARRAY_A );

            if ( ! empty( $license ) ) {
                $this->send_woo_sa_add_license_request( $product_id, $license );
            }
        }
    }

    /**
     * Woo SA send add license request
     */
    private function send_woo_sa_add_license_request( $product_id, $license ) {
        $route = 'public/' . $product_id . '/add-license';

        $body = [
            'key'              => $license['license_key'],
            'status'           => 1,
            'activation_limit' => $license['activations_limit'] ?: '',
            'active_sites'     => 0,
            'expire_date'      => '',
            'variation_source' => '',
            'license_source'   => 'Woo SA',
        ];

        appsero_helper_remote_post( $route, $body );
    }

    /**
     * Woo SA cancel order
     * It will deactive a license on appsero server
     */
    private function woo_sa_cancel_order( $order_id, $order ) {
        global $wpdb;

        $order_items = $order->get_items();
        if ( sizeof( $order_items ) == 0 ) {
            return;
        }

        foreach ( $order_items as $order_item ) {
            $product_id  = $order_item->get_product_id();
            $software_id = get_post_meta( $product_id, '_software_product_id', true);

            $query   = "SELECT * FROM {$wpdb->wc_software_licenses} WHERE `order_id` = {$order_id} AND `software_product_id` = '{$software_id}' ";
            $license = $wpdb->get_row( $query, ARRAY_A );

            if ( ! empty( $license ) ) {
                $route = 'public/' . $product_id . '/cancel-order';

                $body = [
                    'key'            => $license['license_key'],
                    'license_source' => 'Woo SA',
                    'order_id'       => $order_id,
                ];

                appsero_helper_remote_post( $route, $body );
            }
        } // End foreach
    }

    /**
     * Woo SA cancel order
     * It will deactive a license on appsero server
     */
    private function woo_api_cancel_order( $order_id, $order ) {
        $user_id     = $order->get_user_id();
        $user_orders = WC_AM_HELPERS()->get_users_data( $user_id );

        if ( ! empty( $user_orders ) ) {
            foreach ( $user_orders as $order_key => $data ) {
                if ( $data['order_id'] == $order_id ) {
                    $route = 'public/' . $data['parent_product_id'] . '/cancel-order';

                    $body = [
                        'key'            => $data['api_key'],
                        'license_source' => 'Woo API',
                        'order_id'       => $order_id,
                    ];

                    appsero_helper_remote_post( $route, $body );
                }
            } // End foreach
        } // End if
    }


}
