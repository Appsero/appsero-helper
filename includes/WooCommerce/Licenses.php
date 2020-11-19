<?php
namespace Appsero\Helper\WooCommerce;

use WP_REST_Response;

/**
 * Licenses
 */
class Licenses {

    /**
     * Store each license information
     * @var array
     */
    public $licenses = [];

    /**
     * Get a collection of licenses.
     *
     * @param WP_REST_Request $request Full details about the request.
     *
     * @return WP_Error|WP_REST_Response
     */
    public function get_items( $request ) {
        $product_id   = $request->get_param( 'product_id' );
        $per_page     = $request->get_param( 'per_page' );
        $current_page = $request->get_param( 'page' );
        $offset       = ( $current_page - 1 ) * $per_page;

        // if WooCommerce Software Addon Exists
        if ( class_exists( 'WC_Software' ) ) {
            return $this->woo_sa_licenses( $product_id, $per_page, $current_page, $offset );
        }

        // if WooCommerce API Manager Exists
        if ( class_exists( 'WooCommerce_API_Manager' ) ) {
            // If version 1.*
            if ( function_exists( 'WC_AM_HELPERS' ) ) {
                return $this->woo_legacy_api_manager_licenses( $product_id, $per_page, $current_page, $offset );
            }

            // If version above 2.*
            if ( version_compare( WCAM()->version, '2.0.0', '>=' ) ) {
                return $this->woo_api_manager_licenses( $product_id, $per_page, $current_page, $offset );
            }
        }

        return rest_ensure_response( [] );
    }

    /**
     * Generate license data for WooCommerce API Manager
     *
     * @param integer $product_id
     * @param integer $per_page
     * @param integer $current_page
     *
     * @return WP_Error|WP_REST_Response
     */
    private function woo_legacy_api_manager_licenses( $product_id, $per_page, $current_page, $offset ) {
        global $wpdb;
        $table_order_itemmeta = $wpdb->prefix . 'woocommerce_order_itemmeta';
        $table_order_items    = $wpdb->prefix . 'woocommerce_order_items';

        $itemmetaQuery  = " SELECT `{$table_order_itemmeta}`.`order_item_id` FROM `{$table_order_itemmeta}` ";
        $itemmetaQuery .= " WHERE `meta_key` = '_product_id' AND `meta_value` = {$product_id} ";

        $itemsQuery   = " SELECT SQL_CALC_FOUND_ROWS postmeta.meta_value AS license_key from {$table_order_items} AS order_items ";
        $itemsQuery  .= " LEFT JOIN {$wpdb->posts} AS posts ON order_items.order_id = posts.ID ";
        $itemsQuery  .= " LEFT JOIN {$wpdb->postmeta} AS postmeta ON posts.ID = postmeta.post_id ";
        $itemsQuery  .= " WHERE `order_item_id` IN ( {$itemmetaQuery} ) ";
        $itemsQuery  .= " AND posts.post_status = 'wc-completed' ";
        $itemsQuery  .= " AND postmeta.meta_key LIKE '%_api_license_key_%' ";
        $itemsQuery  .= " ORDER BY order_items.order_id ASC LIMIT {$per_page} OFFSET {$offset} ";

        $results     = $wpdb->get_col( $itemsQuery, 0 );
        $total_items = $wpdb->get_var( 'SELECT FOUND_ROWS()' );

        foreach( $results as $key ) {
             $this->get_woo_legacy_api_license_data( $key );
        }

        $response = rest_ensure_response( $this->licenses );

        $max_pages = ceil( $total_items / $per_page );
        $response->header( 'X-WP-Total', (int) $total_items );
        $response->header( 'X-WP-TotalPages', (int) $max_pages );

        return $response;
    }

    /**
     * Get single license data for Woo API
     *
     * @return array|false
     */
    public function get_woo_legacy_api_license_data( $license_key, $needActivations = true, $status = null, $license_data = null ) {

        global $wpdb;

        if ( empty( $license_data ) ) {
            $meta_key = $wpdb->get_blog_prefix() . WC_AM_HELPERS()->user_meta_key_orders;

            $query = "SELECT meta_value FROM {$wpdb->usermeta} WHERE meta_key = '{$meta_key}' ";
            $query .= " AND meta_value LIKE '%{$license_key}%' ";

            $license_data = $wpdb->get_var( $query );
            $license_data = maybe_unserialize( $license_data );
        }

        if ( ! isset( $license_data[ $license_key ] ) ) {
            return false;
        }

        $license = $license_data[ $license_key ];

        $activations = $needActivations ? $this->get_woo_legacy_api_activations( $license['user_id'], $license['order_key'] ) : [];

        $this->licenses[] = [
            'key'              => $license['api_key'],
            'status'           => ( null === $status ) ? 1 : $status,
            'created_at'       => $license['_purchase_time'],
            'expire_date'      => '',
            'activation_limit' => $license['_api_activations'] ?: '',
            'activations'      => $activations,
            'variation_source' => (int) $license['variable_product_id'] ?: '',
            'active_sites'     => $this->get_active_sites_count( $activations ),
            'license_source'   => 'Woo API',
        ];
    }

    /**
     * Get active sites for Woo API V1
     *
     * @param int $id
     *
     * @return array
     */
    protected function get_woo_legacy_api_activations( $user_id, $order_key ) {

        $activations = WC_AM_HELPERS()->get_users_activation_data( $user_id, $order_key );;

        if( empty( $activations ) ) {
            return [];
        }

        foreach ( $activations as $site ) {
            $domain = $this->prepare_domain_name( $site['activation_domain'] );

            if ( ! $domain ) {
                continue;
            }

            $domains[] = [
                'site_url'  => $domain,
                'is_active' => !! $site['activation_active'],
                'is_local'  => false,
            ];
        }

        return $domains;
    }

    /**
     * Change status of a license
     * Activate, Deactivate, Disable
     *
     * @param WP_REST_Request $request Full details about the request.
     *
     * @return WP_Error|WP_REST_Response
     */
    public function change_status( $request ) {
        $product_id  = $request->get_param( 'product_id' );
        $license_key = $request->get_param( 'license_key' );

        return new WP_REST_Response( [
            'success' => true,
            'message' => 'License updated successfully.',
        ] );
    }

    /**
     * Get the count of active siyes
     * @return int
     */
    protected function get_active_sites_count( $activations ) {
        $site_count = 0;

        foreach ( $activations as $activation ) {
            if ( $activation['is_active'] ) {
                $site_count++;
            }
        }

        return $site_count;
    }

    /**
     * Generate license data for WooCommerce Software Add-on
     *
     * @param integer $product_id
     * @param integer $per_page
     * @param integer $current_page
     *
     * @return WP_Error|WP_REST_Response
     */
    private function woo_sa_licenses( $product_id, $per_page, $current_page, $offset ) {
        global $wpdb;

        $sub_select = $wpdb->prepare( "SELECT `meta_value` FROM {$wpdb->postmeta} WHERE `meta_key` = '_software_product_id' AND `post_id` = %d", $product_id );

        $query  = "SELECT SQL_CALC_FOUND_ROWS * FROM {$wpdb->wc_software_licenses} ";
        $query .= " WHERE `software_product_id` = ({$sub_select}) ";
        $query .= " ORDER BY `key_id` ASC LIMIT %d OFFSET %d ";
        $items = $wpdb->get_results( $wpdb->prepare( $query, $per_page, $offset ), ARRAY_A );
        $total_items = $wpdb->get_var( 'SELECT FOUND_ROWS()' );

        foreach ( $items as $item ) {
            $this->get_woo_sa_license_data( $item, $product_id );
        }

        $response = rest_ensure_response( $this->licenses );

        $max_pages = ceil( $total_items / $per_page );
        $response->header( 'X-WP-Total', (int) $total_items );
        $response->header( 'X-WP-TotalPages', (int) $max_pages );

        return $response;
    }

    /**
     * Get single license data for Woo SA
     *
     * @return void
     */
    public function get_woo_sa_license_data( $item, $product_id, $status = null, $order = null ) {
        $activations = $this->get_woo_sa_activations( $item['key_id'] );

        $license = [
            'key'              => $item['license_key'],
            'status'           => ( null === $status ) ? 1 : $status,
            'created_at'       => $item['created'],
            'expire_date'      => $this->get_woo_sa_license_expire_date( $product_id, $item, $order ),
            'activation_limit' => empty( $item['activations_limit'] ) ? '' : intval( $item['activations_limit'] ),
            'activations'      => $activations,
            'variation_source' => $this->get_woo_sa_order_variation_source( $product_id, $item, $order ),
            'active_sites'     => $this->get_active_sites_count( $activations ),
            'license_source'   => 'Woo SA',
        ];

        $this->licenses[] = apply_filters( 'appsero_woo_sa_license', $license, $item );
    }

    /**
     * Get active sites for Woo SA
     *
     * @param int $id
     *
     * @return array
     */
    protected function get_woo_sa_activations( $key_id ) {
        global $wpdb;

        $query  = "SELECT * FROM {$wpdb->wc_software_activations} ";
        $query .= "WHERE `key_id` = %d ORDER BY `activation_id` ASC ";

        $activations = $wpdb->get_results( $wpdb->prepare( $query, $key_id ), ARRAY_A );

        if( empty( $activations ) ) {
            return [];
        }

        foreach ( $activations as $site ) {
            $domain = $this->prepare_domain_name( $site['instance'] );

            if ( ! $domain ) {
                continue;
            }

            $domains[] = [
                'site_url'  => $domain,
                'is_active' => !! $site['activation_active'],
                'is_local'  => false,
            ];
        }

        return $domains;
    }

    /**
     * WooCommerce API manager version 2 get licenses of a product
     *
     * @return WP_Error|WP_REST_Response
     */
    private function woo_api_manager_licenses( $product_id, $per_page, $current_page, $offset ) {
        global $wpdb;
        $table_name = $wpdb->prefix . WC_AM_USER()->get_api_resource_table_name();

        $sql = "
            SELECT SQL_CALC_FOUND_ROWS * FROM {$table_name}
            WHERE parent_id = %d
            ORDER BY api_resource_id ASC
            LIMIT %d OFFSET %d
        ";

        $resources = $wpdb->get_results( $wpdb->prepare( $sql, $product_id, $per_page, $offset ), ARRAY_A );
        $total_items = $wpdb->get_var( 'SELECT FOUND_ROWS()' );

        foreach( $resources as $resource ) {
             $this->generate_woo_api_license_data( $resource );
        }

        $response = rest_ensure_response( $this->licenses );

        $max_pages = ceil( $total_items / $per_page );
        $response->header( 'X-WP-Total', (int) $total_items );
        $response->header( 'X-WP-TotalPages', (int) $max_pages );

        return $response;
    }

    /**
     * Prepare WooCommerce API manager V2 license data
     */
    public function generate_woo_api_license_data( $resource, $needActivations = true ) {
        $activation_limit = empty( $resource['activations_purchased_total'] ) ? '' : intval( $resource['activations_purchased_total'] );
        $created_at       =  empty( $resource['access_granted'] ) ? '' : get_date_from_gmt( gmdate( 'Y-m-d H:i:s', $resource['access_granted'] ) );
        $expire_date      =  empty( $resource['access_expires'] ) ? '' : get_date_from_gmt( gmdate( 'Y-m-d H:i:s', $resource['access_expires'] ) );

        $activations = $needActivations ? $this->get_woo_api_activations( $resource ) : [];

        $license = [
            'key'              => $resource['product_order_api_key'],
            'status'           => empty( $resource['active'] ) ? 0 : 1,
            'created_at'       => $created_at,
            'expire_date'      => $expire_date,
            'activation_limit' => $activation_limit,
            'activations'      => $activations,
            'variation_source' => empty( $resource['variation_id'] ) ? '' : intval( $resource['variation_id'] ),
            'active_sites'     => $resource['activations_total'],
            'license_source'   => 'Woo API',
        ];

        $this->licenses[] = apply_filters( 'appsero_woo_api_license', $license, $resource );
    }

    /**
     * Get WooCommerce API manager V2 license activations
     */
    private function get_woo_api_activations( $resource ) {

        $activations = WC_AM_API_ACTIVATION_DATA_STORE()->get_total_activations_resources_for_api_key_by_product_id(
            $resource['product_order_api_key'], $resource['product_id']
        );

        if ( false === $activations ) {
            return [];
        }

        foreach ( $activations as $site ) {
            $domain = $this->prepare_domain_name( $site->object );

            if ( ! $domain ) {
                continue;
            }

            $domains[] = [
                'site_url'  => $domain,
                'is_active' => true,
                'is_local'  => $this->is_local_ip_address( $site->ip_address ),
            ];
        }

        return $domains;
    }

    /**
     * Prepare domain name
     * Remove unuecessary portions
     */
    private function prepare_domain_name( $domain ) {
        $remove_protocols = [ 'http://', 'https://' ];
        $domain = str_replace( $remove_protocols, '', $domain );

        return untrailingslashit( $domain );
    }

    /**
     * Check IP address is local or not
     *
     * @return boolean
     */
    private function is_local_ip_address( $ip_address ) {
        if ( $ip_address == '127.0.0.1' ) {
            return true;
        }

        return ( ! filter_var( $ip_address, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) );
    }

    /**
     * Find expire data of license
     */
    private function get_woo_sa_license_expire_date( $product_id, $item, $order ) {
        // Check if WooCommerce Subscription active
        if ( ! function_exists( 'wcs_get_subscriptions_for_order' ) ) {
            return '';
        }

        if ( ! is_a( $order, 'WC_Abstract_Order' ) ) {
            $order = wc_get_order( $item['order_id'] );
        }

        $subscriptions = wcs_get_subscriptions_for_order( $order, [
            'product_id' => $product_id,
            'order_type' => 'any',
        ] );

        // If no subscription found
        if ( count( $subscriptions ) < 1 ) {
            return '';
        }

        $subscription = array_shift( $subscriptions );

        if ( ! wcs_is_subscription( $subscription ) ) {
            return '';
        }

        return $subscription->get_date( 'next_payment' );
    }

    /**
     * Get variation id for order
     */
    private function get_woo_sa_order_variation_source( $product_id, $item, $order ) {
        if ( ! is_a( $order, 'WC_Abstract_Order' ) ) {
            $order = wc_get_order( $item['order_id'] );
        }

        $order_data = $order->get_data();
        $cart       = $this->get_cart_details( $product_id, $order_data['line_items'] );

        if ( $cart ) {
            $variation_id = $cart->get_variation_id();
            return $variation_id ? $variation_id : '';
        }

        return '';
    }

    /**
     * Get cart of this product
     */
    private function get_cart_details( $product_id, $carts ) {
        foreach( $carts as $cart ) {
            if ( $cart->get_product_id() == $product_id ) {
                return $cart;
            }
        }
    }

}
