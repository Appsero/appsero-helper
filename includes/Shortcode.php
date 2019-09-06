<?php
namespace Appsero\Helper;

class Shortcode {

    /**
     * Constructor
     */
    public function __construct() {
        add_shortcode( 'appsero_licenses', [ $this, 'appsero_licenses' ] );

        add_shortcode( 'appsero_orders', [ $this, 'appsero_orders' ] );
    }

    /**
     * Output of appsero licenses
     */
    public function appsero_licenses( $attr, $content = null ) {
        require_once ASHP_ROOT_PATH . 'includes/LicensesRenderer.php';

        $renderer = new \Appsero\Helper\LicensesRenderer();

        return $renderer->show();
    }

    /**
     * Output of appsero licenses
     */
    public function appsero_orders() {
        require_once ASHP_ROOT_PATH . 'includes/OrdersRenderer.php';

        $renderer = new \Appsero\Helper\OrdersRenderer();

        return $renderer->show();
    }

}
