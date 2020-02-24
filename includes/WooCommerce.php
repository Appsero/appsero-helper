<?php
namespace Appsero\Helper;

/**
 * API Class
 */
class WooCommerce {

    /**
     * Constructor
     */
    public function __construct() {
        require_once __DIR__ . '/WooCommerce/UseCases/SendRequestsHelper.php';
        require_once __DIR__ . '/WooCommerce/SendRequests.php';
        require_once __DIR__ . '/WooCommerce/MyAccountPage.php';
        require_once __DIR__ . '/WooCommerce/ThankYouPage.php';

        // Initialize WooCommerce requests hooks
        new WooCommerce\SendRequests();

        // WooCommerce My Account page
        new WooCommerce\MyAccountPage();

        // WooCommerce Thank You page
        new WooCommerce\ThankYouPage();
    }

    /**
     * Products REST API Class
     *
     * @return WooCommerce\Downloads
     */
    public function products() {
        require_once __DIR__ . '/WooCommerce/Products.php';

        return new WooCommerce\Products();
    }

    /**
     * Licenses REST API Class
     *
     * @return WooCommerce\Licenses
     */
    public function orders() {
        require_once __DIR__ . '/WooCommerce/Orders.php';

        return new WooCommerce\Orders();
    }

    /**
     * Licenses REST API Class
     *
     * @return WooCommerce\Licenses
     */
    public function licenses() {
        require_once __DIR__ . '/WooCommerce/Licenses.php';

        return new WooCommerce\Licenses();
    }

    /**
     * Activations REST API Class
     *
     * @return WooCommerce\Activations
     */
    public function activations() {
        require_once __DIR__ . '/WooCommerce/Activations.php';

        return new WooCommerce\Activations();
    }

    /**
     * Subscriptions REST API Class
     *
     * @return WooCommerce\Activations
     */
    public function subscriptions() {
        require_once __DIR__ . '/WooCommerce/Subscriptions.php';

        return new WooCommerce\Subscriptions();
    }
}
