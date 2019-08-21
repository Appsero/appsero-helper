<?php
namespace Appsero\Helper;

class Shortcode {

    /**
     * Constructor
     */
    public function __construct() {

        add_shortcode( 'appsero_licenses', [ $this, 'appsero_licenses' ] );
    }

    /**
     * Output of appsero licenses
     */
    public function appsero_licenses( $attr, $content = null ) {
        require_once ASHP_ROOT_PATH . 'includes/LicensesRenderer.php';

        $renderer = new \Appsero\Helper\LicensesRenderer();

        return $renderer->show();
    }

}
