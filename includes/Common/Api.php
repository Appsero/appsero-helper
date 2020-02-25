<?php
namespace Appsero\Helper\Common;

use WP_Error;
use WP_REST_Response;
use Appsero\Helper\Traits\Hooker;
use Appsero\Helper\Traits\Rest;

class Api {

    use Hooker;
    use Rest;

    /**
     * Constructor
     */
    public function __construct() {
        $this->action( 'rest_api_init', 'api_init' );
    }

    /**
     * Register common routes
     */
    public function api_init() {
        $product_class = $this->get_product_class();
        $user_class    = $this->get_user_class();

        // Site status
        $this->get( '/status', [ $this, 'app_status' ] );

        // Connect Appsero projects with WP store
        $this->post( '/products/connect', [ $product_class, 'connect_products' ] );

        // Disconnect Appsero projects with WP store
        $this->post( '/products/disconnect', [ $product_class, 'disconnect_products' ] );

        // Create FastSpring license
        $this->post( '/native-users', [ $user_class, 'create_native_user' ], $user_class->create_native_user_params() );

    }

    /**
     * Public app status
     *
     * @return \WP_REST_Response
     */
    public function app_status() {

        return rest_ensure_response( [
            'version' => ASHP_VERSION,
            'php'     => phpversion(),
        ] );
    }

    /**
     * Get project class
     */
    private function get_product_class() {
        require_once __DIR__ . '/Product.php';

        return new Product();
    }

    /**
     * Get license class
     */
    private function get_user_class() {
        require_once __DIR__ . '/User.php';

        return new User();
    }
}
