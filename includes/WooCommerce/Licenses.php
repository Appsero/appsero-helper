<?php
namespace Appsero\Helper\WooCommerce;

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
            # code...
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
             $this->get_license_data( $key );
        }

        $response = rest_ensure_response( $this->licenses );

        $max_pages = ceil( $total_items / $per_page );
        $response->header( 'X-WP-Total', (int) $total_items );
        $response->header( 'X-WP-TotalPages', (int) $max_pages );

        return $response;
    }

    /**
     * Get single license data
     *
     * @return array|false
     */
    protected function get_license_data( $license_key ) {

        global $wpdb;

        $query = "SELECT meta_value FROM {$wpdb->usermeta} WHERE meta_key = 'wp_wc_am_orders' ";
        $query .= " AND meta_value LIKE '%{$license_key}%' ";

        $license_data = $wpdb->get_var( $query );
        $license_data = maybe_unserialize( $license_data );

        if ( ! isset( $license_data[ $license_key ] ) ) {
            return false;
        }

        $license = $license_data[ $license_key ];
// $this->licenses[] = $license;
// print_r($license);
// return;
        // $license = new EDD_SL_License( $id );
        // $expiration = $license->expiration ? date( 'Y-m-d H:i:s', (int) $license->expiration ) : null;
        // $results = $wpdb->get_results( $query, ARRAY_A );

        $activations = $this->get_activations( $license['user_id'], $license['order_key'] );

        $this->licenses[] = [
            'key'              => $license['api_key'],
            'status'           => 1,
            'created_at'       => $license['_purchase_time'],
            'expire_date'      => null,
            'activation_limit' => $license['_api_activations'] ?: null,
            'activations'      => $activations,
            'variation_source' => (int) $license['variable_product_id'] ?: null,
            'active_sites'     => count( $activations ),
        ];
    }

    /**
     * get active sites
     *
     * @param int $id
     *
     * @return array
     */
    protected function get_activations( $user_id, $order_key ) {

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

}
