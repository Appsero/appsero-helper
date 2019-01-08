<?php
namespace Appsero\Helper\WooCommerce;

use WP_REST_Response;

/**
 * Licenses
 */
class Licenses {

    protected $licenses = [];

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
            return $this->woo_api_manager_licenses( $product_id, $per_page, $current_page, $offset );
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
    private function woo_api_manager_licenses( $product_id, $per_page, $current_page, $offset ) {
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
             $this->get_woo_api_license_data( $key );
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
    protected function get_woo_api_license_data( $license_key ) {

        global $wpdb;

        $meta_key = $wpdb->get_blog_prefix() . WC_AM_HELPERS()->user_meta_key_orders;

        $query = "SELECT meta_value FROM {$wpdb->usermeta} WHERE meta_key = '{$meta_key}' ";
        $query .= " AND meta_value LIKE '%{$license_key}%' ";

        $license_data = $wpdb->get_var( $query );
        $license_data = maybe_unserialize( $license_data );

        if ( ! isset( $license_data[ $license_key ] ) ) {
            return false;
        }

        $license = $license_data[ $license_key ];

        $activations = $this->get_woo_api_activations( $license['user_id'], $license['order_key'] );

        $this->licenses[] = [
            'key'              => $license['api_key'],
            'status'           => 1,
            'created_at'       => $license['_purchase_time'],
            'expire_date'      => '',
            'activation_limit' => $license['_api_activations'] ?: '',
            'activations'      => $activations,
            'variation_source' => (int) $license['variable_product_id'] ?: '',
            'active_sites'     => $this->get_active_sites_count( $activations ),
        ];
    }

    /**
     * Get active sites for Woo API
     *
     * @param int $id
     *
     * @return array
     */
    protected function get_woo_api_activations( $user_id, $order_key ) {

        $activations = WC_AM_HELPERS()->get_users_activation_data( $user_id, $order_key );;

        if( empty( $activations ) ) {
            return [];
        }

        foreach ( $activations as $site ) {
            $remove_protocols = [ 'http://', 'https://' ];
            $domain = str_replace( $remove_protocols, '', $site['activation_domain'] );
            $domain = untrailingslashit( $domain );

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
            $this->get_woo_sa_license_data( $item );
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
     * @return array|false
     */
    protected function get_woo_sa_license_data( $item ) {
        $activations = $this->get_woo_sa_activations( $item['key_id'] );

        $this->licenses[] = [
            'key'              => $item['license_key'],
            'status'           => 1,
            'created_at'       => $item['created'],
            'expire_date'      => '',
            'activation_limit' => $item['activations_limit'] ?: '',
            'activations'      => $activations,
            'variation_source' => '',
            'active_sites'     => $this->get_active_sites_count( $activations ),
        ];
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
            $remove_protocols = [ 'http://', 'https://' ];
            $domain = str_replace( $remove_protocols, '', $site['activation_platform'] );
            $domain = untrailingslashit( $domain );

            $domains[] = [
                'site_url'  => $domain,
                'is_active' => !! $site['activation_active'],
                'is_local'  => false,
            ];
        }

        return $domains;
    }

}
