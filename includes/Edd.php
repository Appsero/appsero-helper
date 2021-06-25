<?php
namespace Appsero\Helper;

/**
 * API Class
 */
class Edd {

    public function __construct() {
        require_once __DIR__ . '/Edd/UseCases/SendRequestsHelper.php';
        require_once __DIR__ . '/Edd/SendRequests.php';
        require_once __DIR__ . '/Edd/MyAccountPage.php';
        require_once __DIR__ . '/Edd/ThankYouPage.php';
        require_once __DIR__ . '/Edd/Email.php';

        // Initialize Edd requests hooks
        new Edd\SendRequests();

        // EDD My Account page
        new Edd\MyAccountPage();

        // EDD Thank You page
        new EDD\ThankYouPage();

        // EDD Purchase Email
        new Edd\Email();
    }

    /**
     * Products REST API Class
     *
     * @return Edd\Downloads
     */
    public function products() {
        require_once __DIR__ . '/Edd/Downloads.php';

        return new Edd\Downloads();
    }

    /**
     * Orders REST API Class
     *
     * @return Edd\Orders
     */
    public function orders() {
        require_once __DIR__ . '/Edd/Orders.php';

        return new Edd\Orders();
    }

    /**
     * Licenses REST API Class
     *
     * @return Edd\Licenses
     */
    public function licenses() {
        require_once __DIR__ . '/Edd/Licenses.php';

        return new Edd\Licenses();
    }

    /**
     * Activations REST API Class
     *
     * @return Edd\Activations
     */
    public function activations() {
        require_once __DIR__ . '/Edd/Activations.php';

        return new Edd\Activations();
    }

    /**
     * Subscriptions REST API Class
     *
     * @return Edd\Subscriptions
     */
    public function subscriptions() {
        require_once __DIR__ . '/Edd/Subscriptions.php';

        return new Edd\Subscriptions();
    }
}
