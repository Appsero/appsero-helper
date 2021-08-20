<?php
namespace Appsero\Helper\AffiliateWP;

use Affiliate_WP_Base;

/**
 * Paddle_Integration class
 *
 * Responsible manage AffiliateWP referral creation
 */
class Paddle_Integration extends Affiliate_WP_Base {

    /**
     * Order data
     */
    private $order;

    /**
     * Checkout Data
     */
    private $checkout;

    /**
     * Checkout Data
     */
    private $checkout_data;

	/**
	 * The context for referrals. This refers to the integration that is being used.
	 */
	public $context = 'paddle';

    /**
     * Run necessary hooks here
     */
    public function init() {
        add_action( 'wp_ajax_appsero_affwp_paddle_completed', [ $this, 'order_completed' ] );
        add_action( 'wp_ajax_nopriv_appsero_affwp_paddle_completed', [ $this, 'order_completed' ] );
    }

    /**
     * Run this function when order is completed
     */
    public function order_completed() {
        if( empty($_POST['checkout_id']) || ! $this->was_referred() ) {
            return;
        }

        $checkout_id = $_POST['checkout_id'];

        $customer = $this->get_paddle_customer( $checkout_id );

        if ( empty($customer['email']) ) {
            $this->log( 'No email address was found' );
            return;
        }

        // Customers cannot refer themselves
        if ( $this->is_affiliate_email( $customer['email'] ) ) {
            $this->log( "Referral not created because affiliate's own account was used." );
            return;
        }

        if ( ! $this->affiliate_id ) {
            $this->log( 'No referral id found, skipping.' );
            return;
        }

        $product_total = $this->order['total'];
        $amount      = $this->calculate_referral_amount( $product_total, $checkout_id );
        $description = $this->checkout['title'] . ' - ' . $this->order['formatted_total'];
        $reference   = $this->order['is_subscription'] ? $this->order['subscription_order_id'] : $this->order['order_id'];

        $products = [];

        foreach( $this->checkout_data['lockers'] as $locker ) {
            $products[] = $locker['product_id'];
        }

        if( isset($this->order['product_id']) ) {
            $products[] = $this->order['product_id'];
        }

        $this->insert_pending_referral( $amount, $reference, $description, $products );
        $this->complete_referral( $reference );

        wp_send_json_success();
    }

	/**
	 * Runs the check necessary to confirm this plugin is active.
	 */
	public function plugin_is_active() {
        $selling_plugin = get_option( 'appsero_selling_plugin', '' );

        if ( 'paddle' !== $selling_plugin ) {
            return false;
        }

        $affiliate = get_option( 'appsero_affiliate_wp_settings', [] );

        return ! empty( $affiliate['enable_affiliates'] );
    }

    /**
     * Get Paddle customer
     */
    private function get_paddle_customer( $checkout_id ) {
        $userinfo = get_option( 'appsero_affiliate_wp_settings', [] );

        if ( empty( $userinfo['paddle_vendor_id'] ) || empty( $userinfo['paddle_vendor_auth_code'] ) ) {
            return false;
        }

        $sandbox = ! empty( $userinfo['paddle_sandbox'] );

        $checkout = $this->fetch_paddle_api_order( $checkout_id, $sandbox );

        if ( !$checkout || ! $checkout['order'] ) {
            return false;
        }

        $this->checkout_data = $checkout;
        $this->order = $checkout['order'];
        $this->checkout = $checkout['checkout'];

        return $checkout['order']['customer'];
    }

    /**
     * Fetch Paddle data from API
     */
    private function fetch_paddle_api_order( $checkout_id, $sandbox = false ) {

        if( $sandbox ) {
            $url = 'https://sandbox-checkout.paddle.com/api/1.0/order';
        } else {
            $url = 'https://checkout.paddle.com/api/1.0/order';
        }

        $endpoint = $url . '?checkout_id=' . $checkout_id;

        $response = wp_remote_get( $endpoint );

        if ( is_wp_error( $response ) ) {
            return false;
        }

        if ( 200 != $response['response']['code'] ) {
            $this->log( 'Error connecting with Paddle.' );
            return false;
        }

        $response_body = wp_remote_retrieve_body( $response );

        return json_decode( $response_body, true );
    }

}
