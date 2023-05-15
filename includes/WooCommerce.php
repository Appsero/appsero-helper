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
        require_once __DIR__ . '/WooCommerce/OrderHooks.php';
        require_once __DIR__ . '/WooCommerce/MyAccountPage.php';
        require_once __DIR__ . '/WooCommerce/ThankYouPage.php';
        require_once __DIR__ . '/WooCommerce/MetaBox.php';
        require_once __DIR__ . '/WooCommerce/Email.php';

        // Initialize WooCommerce requests hooks
//        new WooCommerce\SendRequests();
        new WooCommerce\OrderHooks();

        // WooCommerce My Account page
        new WooCommerce\MyAccountPage();

        // WooCommerce Thank You page
        new WooCommerce\ThankYouPage();

        // WooCommerce meta boxes
        new WooCommerce\MetaBox();

        // Woocommerce Email
        new WooCommerce\Email();
    }

    /**
     * Products REST API Class
     *
     * @return WooCommerce\Products
     */
    public function products() {
        require_once __DIR__ . '/WooCommerce/Products.php';

        return new WooCommerce\Products();
    }

    /**
     * Orders REST API Class
     *
     * @return WooCommerce\Orders
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
     * @return WooCommerce\Subscriptions
     */
    public function subscriptions() {
        require_once __DIR__ . '/WooCommerce/Subscriptions.php';

        return new WooCommerce\Subscriptions();
    }
}
