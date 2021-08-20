<?php
namespace Appsero\Helper\AffiliateWP;

use Affiliate_WP_Base;

/**
 * FastSpring_Integration class
 *
 * Responsible manage AffiliateWP referral creation
 */
class FastSpring_Integration extends Affiliate_WP_Base {

    /**
     * Order data
     */
    private $order;

	/**
	 * The context for referrals. This refers to the integration that is being used.
	 */
	public $context = 'fastspring';

    /**
     * Run necessary hooks here
     */
    public function init() {
        add_action( 'wp_ajax_appsero_affwp_fastspring_completed', [ $this, 'order_completed' ] );
        add_action( 'wp_ajax_nopriv_appsero_affwp_fastspring_completed', [ $this, 'order_completed' ] );
    }

    /**
     * Run this function when order is completed
     */
    public function order_completed() {
        $order_id = isset( $_POST['id'] ) ? $_POST['id'] : '';

        if ( ! $this->was_referred() ) {
            return;
        }

        $customer_email = $this->get_fastspring_customer( $order_id );

        if ( ! $customer_email ) {
            $this->log( 'No email address was found' );
            return;
        }

        // Customers cannot refer themselves
        if ( $this->is_affiliate_email( $customer_email ) ) {
            $this->log( "Referral not created because affiliate's own account was used." );
            return;
        }

        $product_total = $this->order['totalInPayoutCurrency'];

        if ( ! $this->affiliate_id ) {
            $this->log( 'No referral id found, skipping.' );
            return;
        }

        $amount      = $this->calculate_referral_amount( $product_total, $order_id );
        $description = $this->order['items'][0]['display'] . ' - ' . $this->order['items'][0]['subtotalDisplay'];
        $reference   = $this->order['reference'];
        $products    = $this->order['items'][0]['product'];

        $this->insert_pending_referral( $amount, $reference, $description, $products );
        $this->complete_referral( $reference );

        wp_send_json_success();
    }

	/**
	 * Runs the check necessary to confirm this plugin is active.
	 */
	public function plugin_is_active() {
        $selling_plugin = get_option( 'appsero_selling_plugin', '' );

        if ( 'fastspring' !== $selling_plugin ) {
            return false;
        }

        $affiliate = get_option( 'appsero_affiliate_wp_settings', [] );

        return ! empty( $affiliate['enable_affiliates'] );
    }

    /**
     * Get FastSpring order and customer details
     */
    private function get_fastspring_customer( $order_id ) {
        $userinfo = get_option( 'appsero_affiliate_wp_settings', [] );

        if ( empty( $userinfo['fastspring_username'] ) || empty( $userinfo['fastspring_password'] ) ) {
            return false;
        }

        $order = $this->fetch_fastspring_api_order( $userinfo['fastspring_username'], $userinfo['fastspring_password'], $order_id );

        if ( ! $order ) {
            return false;
        }

        $this->order = $order;

        return $order['customer']['email'];
    }

    /**
     * Fetch FastSpring data from API
     */
    private function fetch_fastspring_api_order( $username, $password, $order_id ) {
        $endpoint = 'https://api.fastspring.com/orders/' . $order_id;

        $response = wp_remote_get( $endpoint, [
            'headers' => [
                'Authorization' => 'Basic ' . base64_encode( $username . ':' . $password ),
            ],
        ] );

        if ( is_wp_error( $response ) ) {
            return false;
        }

        if ( 401 == $response['response']['code'] ) {
            $this->log( 'Unauthorized: Either username or password is incorrect.' );
            return false;
        }

        if ( 200 != $response['response']['code'] ) {
            return false;
        }

        $response_body = wp_remote_retrieve_body( $response );

        $json = json_decode( $response_body, true );

        return $json;
    }

}
