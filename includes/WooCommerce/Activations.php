<?php
namespace Appsero\Helper\Edd;

use WP_Error;
use WP_REST_Response;

/**
 * Activations class
 * Responsible for add, update and delete activation
 */
class Activations {

    protected $woo_api_activations_key;

    /**
     * Add/Edit ativation
     *
     * @return WP_Error|WP_REST_Response
     */
    public function update_or_create_item( $request ) {
        $product_id  = $request->get_param( 'product_id' );
        $license_key = $request->get_param( 'license_key' );

        $license = $this->get_license( $license_key );

        if ( ! isset( $license['parent_product_id'] ) || $product_id !== $license['parent_product_id'] ) {
            return new WP_Error( 'invalid-license', 'License not found.', [ 'status' => 404 ] );
        }

        $site_url = $request->get_param( 'site_url' );
        $site_url = WC_AM_HELPERS()->esc_url_raw_no_scheme( $site_url );
        $site_url = $this->clean_url( $site_url );

        $current_activations = $this->get_current_activations( $license['user_id'], $license['order_key'], $site_url );

        if ( count( $current_activations ) >= $license['_api_activations'] ) {
            return new WP_Error( 'activation-limit-exceeded', 'Activation limit exceeded.', [ 'status' => 400 ] );
        }

        $status = $request->get_param( 'status' );
        $status = ( $status === null ) ? 1 : $status;

        $site_added = $this->process_update_or_create( $site_url, $license, $status, $current_activations );
        if ( $site_added ) {
            return new WP_REST_Response( [
                'success' => true,
            ] );
        }

        return new WP_Error( 'unknown-error', 'Woo API could not add site.', [ 'status' => 400 ] );
    }

    /**
     * Persistent activations data
     *
     * @return boolean
     */
    private function process_update_or_create( $site_url, $license, $status, $current_activations ) {
        $software_title = ( empty( $license['_api_software_title_var'] ) ) ? $license['_api_software_title_parent'] : $license['_api_software_title_var'];
        if ( empty( $software_title ) ) {
            $software_title = $license['software_title'];
        }

        $current_activations[] = [
            'order_key'         => $license['api_key'],
            'instance'          => uniqid(),
            'product_id'        => $software_title,
            'activation_time'   => current_time( 'mysql' ),
            'activation_active' => $status,
            'activation_domain' => $site_url,
            'software_version'  => $license['current_version'],
        ];

        update_user_meta( $license['user_id'], $this->woo_api_activations_key, $current_activations );

        return true;
    }

    /**
     * Delete activation
     *
     * @return WP_Error|WP_REST_Response
     */
    public function delete_item( $request ) {
        $product_id  = $request->get_param( 'product_id' );
        $license_key = $request->get_param( 'license_key' );

        $license = $this->get_license( $license_key );

        if ( ! isset( $license['parent_product_id'] ) || $product_id !== $license['parent_product_id'] ) {
            return new WP_Error( 'invalid-license', 'License not found.', [ 'status' => 404 ] );
        }

        $site_url = $request->get_param( 'site_url' );
        $site_url = WC_AM_HELPERS()->esc_url_raw_no_scheme( $site_url );
        $site_url = $this->clean_url( $site_url );

        $current_activations = $this->get_current_activations( $license['user_id'], $license['order_key'], $site_url );

        if ( empty( $current_activations ) ) {
            delete_user_meta( $license['user_id'], $this->woo_api_activations_key );
        } else {
            update_user_meta( $license['user_id'], $this->woo_api_activations_key, $current_activations );
        }

        return new WP_REST_Response( [
            'success' => true,
        ] );
    }

    /**
     * Get license data
     */
    protected function get_license( $license_key ) {
        global $wpdb;

        $meta_key = $wpdb->get_blog_prefix() . WC_AM_HELPERS()->user_meta_key_orders;

        $query  = "SELECT meta_value FROM {$wpdb->usermeta} WHERE meta_key = '{$meta_key}' ";
        $query .= " AND meta_value LIKE '%{$license_key}%' ";

        $license_data = $wpdb->get_var( $query );
        $license_data = maybe_unserialize( $license_data );

        if ( ! isset( $license_data[ $license_key ] ) ) {
            return false;
        }

        return $license_data[ $license_key ];
    }

    /**
     * Get activations of this order
     */
    protected function get_current_activations( $user_id, $order_key, $site_url ) {
        global $wpdb;
        $this->woo_api_activations_key = $wpdb->get_blog_prefix() . WC_AM_HELPERS()->user_meta_key_activations . $order_key;
        $activations = get_user_meta( $user_id, $this->woo_api_activations_key, true );

        if ( empty( $activations ) || ! is_array( $activations ) ) {
            return [];
        }

        foreach ( $activations as $key => $activation ) {
            if ( $this->clean_url( $activation['activation_domain'] ) == $site_url ) {
                // Delete the activation data array
                unset( $activations[ $key ] );

                // Re-index the numerical array keys:
                $activations = array_values( $activations );

                break;
            }
        }

        return $activations;
    }

    /**
     * Clean URL
     */
    private function clean_url( $site_url ) {
        $remove_protocols = [ 'http://', 'https://' ];
        $domain = str_replace( $remove_protocols, '', $site_url );
        return untrailingslashit( $domain );
    }
}
