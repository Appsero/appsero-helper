<?php

namespace Appsero\Helper\WooCommerce;

use Appsero\Helper\Traits\Hooker;

class OrderHooks {
    use Hooker;

    public function __construct() {
        require_once __DIR__ . '/SendRequests.php';
        $sendRequest = new SendRequests();
        // Add or Update order with license
        add_action( 'woocommerce_order_status_changed', [ $sendRequest, 'receive_order_status_changed'], 20, 4 );
        add_action( 'before_delete_post', [ $sendRequest, 'delete_order'], 8, 1 );
    }
}