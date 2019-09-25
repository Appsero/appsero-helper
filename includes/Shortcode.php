<?php
namespace Appsero\Helper;

class Shortcode {

    /**
     * Constructor
     */
    public function __construct() {
        add_shortcode( 'appsero_licenses', [ $this, 'appsero_licenses' ] );

        add_shortcode( 'appsero_orders', [ $this, 'appsero_orders' ] );

        add_shortcode( 'appsero_downloads', [ $this, 'appsero_downloads' ] );
    }

    /**
     * Output of appsero licenses
     */
    public function appsero_licenses( $attr, $content = null ) {
        require_once ASHP_ROOT_PATH . 'includes/Renderer/LicensesRenderer.php';

        $renderer = new \Appsero\Helper\Renderer\LicensesRenderer();

        return $renderer->show();
    }

    /**
     * Output of appsero licenses
     */
    public function appsero_orders() {
        require_once ASHP_ROOT_PATH . 'includes/Renderer/OrdersRenderer.php';

        $renderer = new \Appsero\Helper\Renderer\OrdersRenderer();

        return $renderer->show();
    }

    /**
     * Output of appsero licenses
     */
    public function appsero_downloads() {
        require_once ASHP_ROOT_PATH . 'includes/Renderer/DownloadsRenderer.php';

        $renderer = new \Appsero\Helper\Renderer\DownloadsRenderer();

        return $renderer->show();
    }

}
