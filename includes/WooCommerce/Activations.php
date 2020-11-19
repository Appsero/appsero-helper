<?php
namespace Appsero\Helper\WooCommerce;

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

        $site_added = false;

        // if WooCommerce Software Addon Exists
        if ( class_exists( 'WC_Software' ) ) {
            $site_added = $this->update_or_create_woo_sa_ativation( $request, $product_id, $license_key );
        }

        // if WooCommerce API Manager Exists
        if ( class_exists( 'WooCommerce_API_Manager' ) ) {
            // If version 1.*
            if ( function_exists( 'WC_AM_HELPERS' ) ) {
                $site_added = $this->update_or_create_legacy_woo_api_activation( $request, $product_id, $license_key );
            }

            // If version above 2.*
            if ( version_compare( WCAM()->version, '2.0.0', '>=' ) ) {
                $site_added = $this->update_or_create_woo_api_activation( $request, $product_id, $license_key );
            }
        }

        if ( is_wp_error( $site_added ) ) {
            return $site_added;
        } else if ( $site_added ) {
            return new WP_REST_Response( [
                'success' => true,
            ] );
        }

        return new WP_Error( 'unknown-error', 'Could not add site.', [ 'status' => 400 ] );
    }

    /**
     * Persistent Woo API activations data
     *
     * @return boolean
     */
    private function process_legacy_woo_api_update_or_create( $site_url, $license, $status, $current_activations ) {
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

        // if WooCommerce Software Addon Exists
        if ( class_exists( 'WC_Software' ) ) {
            $this->delete_woo_sa_ativation( $request, $product_id, $license_key );
        }

        // if WooCommerce API Manager Exists
        if ( class_exists( 'WooCommerce_API_Manager' ) ) {
            // If version 1.*
            if ( function_exists( 'WC_AM_HELPERS' ) ) {
                $this->delete_legacy_woo_api_ativation( $request, $product_id, $license_key );
            }

            // If version above 2.*
            if ( version_compare( WCAM()->version, '2.0.0', '>=' ) ) {
                $this->delete_woo_api_ativation( $request, $product_id, $license_key );
            }
        }

        return new WP_REST_Response( [
            'success' => true,
        ] );
    }

    /**
     * Get license data
     */
    protected function get_legacy_woo_api_license( $license_key ) {
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
    protected function get_legacy_woo_api_current_activations( $user_id, $order_key, $site_url ) {
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

    /**
     * Update or create for Woo API V1
     *
     * @param int $product_id
     * @param int $license_key
     *
     * @return
     */
    private function update_or_create_legacy_woo_api_activation( $request, $product_id, $license_key ) {
        $license = $this->get_legacy_woo_api_license( $license_key );

        if ( ! isset( $license['parent_product_id'] ) || $product_id !== $license['parent_product_id'] ) {
            return new WP_Error( 'invalid-license', 'License not found.', [ 'status' => 404 ] );
        }

        $site_url = $request->get_param( 'site_url' );
        $site_url = WC_AM_HELPERS()->esc_url_raw_no_scheme( $site_url );
        $site_url = $this->clean_url( $site_url );

        $current_activations = $this->get_legacy_woo_api_current_activations( $license['user_id'], $license['order_key'], $site_url );

        if ( ! empty( $license['_api_activations'] ) && count( $current_activations ) >= $license['_api_activations'] ) {
            return new WP_Error( 'activation-limit-exceeded', 'Activation limit exceeded.', [ 'status' => 400 ] );
        }

        $status = $request->get_param( 'status' );
        $status = ( $status === null ) ? 1 : $status;

        return $this->process_legacy_woo_api_update_or_create( $site_url, $license, $status, $current_activations );
    }

    /**
     * Update or create for Woo SA
     * @param int $product_id
     * @param int $license_key
     *
     * @return
     */
    private function update_or_create_woo_sa_ativation( $request, $product_id, $license_key ) {
        global $wpdb;

        $license = $this->get_woo_sa_license( $product_id, $license_key );

        if ( empty( $license ) ) {
            return new WP_Error( 'invalid-license', 'License not found.', [ 'status' => 404 ] );
        }

        $site_url = $request->get_param( 'site_url' );
        $site_url = $this->clean_url( $site_url );

        if ( $this->is_limit_exceed( $license, $site_url ) ) {
            return new WP_Error( 'activation-limit-exceeded', 'Activation limit exceeded.', [ 'status' => 400 ] );
        }

        $status = $request->get_param( 'status' );
        $status = ( $status === null ) ? 1 : $status;

        return $this->process_woo_sa_update_or_create( $site_url, $license, $status );
    }

    /**
     * Is Woo SA license activation limit exceed
     */
    private function is_limit_exceed( $license, $site_url ) {
        $limit = $license['activations_limit'];

        // retrieve active sites count
        global $wpdb;
        $query  = "SELECT COUNT(activation_id) FROM {$wpdb->wc_software_activations} WHERE key_id = %s ";
        $query .= " AND activation_active = 1 AND activation_platform <> '%s' ";

        $active_sites = $wpdb->get_var( $wpdb->prepare( $query, $license['key_id'], $site_url ) );

        if ( $limit > 0 && $active_sites >= $limit ) {
            return true;
        }

        return false;
    }

    /**
     * Persistent Woo SA activations data
     *
     * @return boolean
     */
    private function process_woo_sa_update_or_create( $site_url, $license, $status ) {
        global $wpdb;

        $exists = $this->get_exist_activation( $license['key_id'], $site_url );

        if ( empty( $exists ) ) {
            // Create new
            $insert = [
                'key_id'              => $license['key_id'],
                'instance'            => $site_url,
                'activation_time'     => current_time( 'mysql' ),
                'activation_active'   => $status,
                'activation_platform' => '',
            ];

            $format = [ '%d', '%s', '%s', '%d', '%s' ];

            $wpdb->insert( $wpdb->wc_software_activations, $insert, $format );

            return $wpdb->insert_id;
        } else {
            // Update status
            $wpdb->update(
                $wpdb->wc_software_activations,
                [ 'activation_active' => $status ],
                [ 'activation_id'     => $exists['activation_id'] ],
                [ '%d' ],
                [ '%d' ]
            );

            return true;
        }
    }

    /**
     * Delete activation for Woo API
     *
     * @return void
     */
    private function delete_legacy_woo_api_ativation( $request, $product_id, $license_key ) {
        $license = $this->get_legacy_woo_api_license( $license_key );

        if ( ! isset( $license['parent_product_id'] ) || $product_id !== $license['parent_product_id'] ) {
            return new WP_Error( 'invalid-license', 'License not found.', [ 'status' => 404 ] );
        }

        $site_url = $request->get_param( 'site_url' );
        $site_url = WC_AM_HELPERS()->esc_url_raw_no_scheme( $site_url );
        $site_url = $this->clean_url( $site_url );

        $current_activations = $this->get_legacy_woo_api_current_activations( $license['user_id'], $license['order_key'], $site_url );

        if ( empty( $current_activations ) ) {
            delete_user_meta( $license['user_id'], $this->woo_api_activations_key );
        } else {
            update_user_meta( $license['user_id'], $this->woo_api_activations_key, $current_activations );
        }
    }


    /**
     * Delete activation for Woo SA
     *
     * @return void
     */
    private function delete_woo_sa_ativation( $request, $product_id, $license_key ) {
        global $wpdb;

        $license = $this->get_woo_sa_license( $product_id, $license_key );

        if ( empty( $license ) ) {
            return new WP_Error( 'invalid-license', 'License not found.', [ 'status' => 404 ] );
        }

        $site_url = $request->get_param( 'site_url' );
        $site_url = $this->clean_url( $site_url );

        $exists = $this->get_exist_activation( $license['key_id'], $site_url );

        if ( empty( $exists ) ) {
            return new WP_Error( 'invalid-url', 'URL not found.', [ 'status' => 404 ] );
        }

        $wpdb->delete(
            $wpdb->wc_software_activations,
            [ 'activation_id' => $exists['activation_id'] ],
            [ '%d' ]
        );
    }

    /**
     * Get Woo SA license
     *
     * @return array|null
     */
    private function get_woo_sa_license( $product_id, $license_key ) {
        global $wpdb;

        $sub_select = $wpdb->prepare( "SELECT `meta_value` FROM {$wpdb->postmeta} WHERE `meta_key` = '_software_product_id' AND `post_id` = %d", $product_id );

        $query  = "SELECT * FROM {$wpdb->wc_software_licenses} ";
        $query .= " WHERE `software_product_id` = ({$sub_select}) ";
        $query .= " AND `license_key` = %s ";
        return $wpdb->get_row( $wpdb->prepare( $query, $license_key ), ARRAY_A );
    }

    /**
     * Get existing activation
     *
     * @return  array|null
     */
    private function get_exist_activation( $key_id, $site_url ) {
        global $wpdb;

        $query  = "SELECT * FROM {$wpdb->wc_software_activations}  ";
        $query .= " WHERE key_id = %s AND activation_platform = '%s' ";
        return $wpdb->get_row( $wpdb->prepare( $query, $key_id, $site_url ), ARRAY_A );
    }

    /**
     * Save WooCommerce API manager V2 activation site
     */
    private function update_or_create_woo_api_activation( $request, $product_id, $license_key ) {
        $resource = $this->get_woo_api_resource( $product_id, $license_key );

        if ( empty( $resource ) ) {
            return new WP_Error( 'invalid-license', 'License not found.', [ 'status' => 404 ] );
        }

        $site_url = $request->get_param( 'site_url' );
        $site_url = $this->clean_url( $site_url );

        $exists = $this->is_woo_api_activation_exists( $resource, $site_url );

        if ( $exists === false ) {
            if ( $resource->activations_purchased_total <= $resource->activations_total ) {
                return new WP_Error( 'activation-limit-exceeded', 'Activation limit exceeded.', [ 'status' => 400 ] );
            }

            $ip = gethostbyname( $site_url );

            $request = [
                'api_key'    => $license_key,
                'user_ip'    => ( $ip == $site_url ) ? '127.0.0.1' : $ip,
                'instance'   => md5( $site_url ),
                'object'     => $site_url,
                'product_id' => $resource->product_id,
            ];

            // Add new activation
            WC_AM_API_ACTIVATION_DATA_STORE()->add_api_key_activation( $resource->user_id, [ $resource ], $request );
        } else {
            global $wpdb;
            $table_name = $wpdb->prefix . WC_AM_USER()->get_api_activation_table_name();

            // Update old activation site
            $wpdb->update( $table_name,
                [
                    'instance' => md5( $site_url ),
                    'object'   => $site_url,
                ],
                [ 'activation_id' => $exists->activation_id ]
            );
        }

        return true;
    }

    /**
     * Get API resocurce for WooCommerce API manager V2
     */
    private function get_woo_api_resource( $product_id, $api_key ) {
        global $wpdb;

        $table_name = $wpdb->prefix . WC_AM_USER()->get_api_resource_table_name();

        $sql = "
            SELECT * FROM {$table_name}
            WHERE ( master_api_key = %s OR product_order_api_key = %s )
            AND ( product_id = %d OR parent_id = %d )
            LIMIT 1
        ";

        // Get the API resource
        return $wpdb->get_row( $wpdb->prepare( $sql, $api_key, $api_key, $product_id, $product_id ) );
    }

    /**
     * Check if activation exists with this license
     */
    private function is_woo_api_activation_exists( $resource, $site_url ) {
        global $wpdb;

        $table_name = $wpdb->prefix . WC_AM_USER()->get_api_activation_table_name();
        $site_url = "%" . $wpdb->esc_like( $site_url ) . "%";

        $sql = "
            SELECT * FROM {$table_name}
            WHERE object LIKE %s
            AND product_order_api_key = %s
            AND product_id = %d
            ORDER BY object ASC LIMIT 1
        ";

        $query = $wpdb->prepare( $sql, $site_url, $resource->product_order_api_key, $resource->product_id );

        $activation = $wpdb->get_row( $wpdb->remove_placeholder_escape( $query ) );

        if ( empty( $activation ) ) {
            return false;
        }

        return $activation;
    }

    /**
     * Delete activation of WooCommerce API manager V2
     * @param $request
     * @param $product_id
     * @param $license_key
     */
    private function delete_woo_api_ativation( $request, $product_id, $license_key ) {
        $resource = $this->get_woo_api_resource( $product_id, $license_key );

        if ( empty( $resource ) ) {
            return new WP_Error( 'invalid-license', 'License not found.', [ 'status' => 404 ] );
        }

        $site_url = $request->get_param( 'site_url' );
        $site_url = $this->clean_url( $site_url );

        $exists = $this->is_woo_api_activation_exists( $resource, $site_url );

        if ( false !== $exists ) {
            WC_AM_API_ACTIVATION_DATA_STORE()->delete_api_key_activation_by_instance_id( $exists->instance );

            // Delete cache
            $trans_hash = md5( $exists->api_key . $exists->instance . $exists->product_id );

            $trans_keys = array(
                'wc_am_api_status_func_data_' . $trans_hash,
                'wc_am_api_status_func_top_level_data_' . $trans_hash,
                'wc_am_api_information_func_response_active_' . $trans_hash,
                'wc_am_api_information_func_data_active_' . $trans_hash,
                'wc_am_api_information_func_top_level_data_active_' . $trans_hash,
                'wc_am_api_update_func_response_active_' . $trans_hash,
                'wc_am_api_update_func_data_active_' . $trans_hash,
                'wc_am_api_update_func_top_level_data_active_' . $trans_hash
            );

            WC_AM_CACHE_HANDLER()->queue_delete_transient( $trans_keys );
        }
    }

}
