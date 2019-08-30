<?php
namespace Appsero\Helper;

use Appsero\Helper\Traits\Hooker;
use Appsero\Helper\Traits\Rest;

/**
 * API Class
 */
class Api {

    use Hooker;
    use Rest;

    /**
     * The client object
     *
     * @var Object
     */
    private $app;

    /**
     * REST Namespace
     *
     * @var string
     */
    public $namespace = 'appsero/v1';

    /**
     * [__construct description]
     *
     * @param Object $client
     */
    function __construct( $client ) {
        $this->app = $client;

        $this->action( 'rest_api_init', 'init_api' );
    }

    /**
     * Initialize REST API
     *
     * @return void
     */
    public function init_api() {
        $products      = $this->app->products();
        $orders        = $this->app->orders();
        $licenses      = $this->app->licenses();
        $activations   = $this->app->activations();
        $subscriptions = $this->app->subscriptions();
        $common        = $this->common_api();

        $this->get( '/status', [ $this, 'app_status' ] );

        // Get all projects with pagination
        $this->get( '/products', [ $products, 'get_items' ], appsero_api_collection_params() );

        // Get licenses with activations in pagination
        $this->get(
            '/products/(?P<product_id>[\d]+)/licenses',
            [ $licenses, 'get_items' ],
            appsero_api_params_with_product_id()
        );

        // Change license status; active, deactive, disable
        $this->post(
            '/products/(?P<product_id>[\d]+)/licenses/(?P<license_key>.+)/change-status',
            [ $licenses, 'change_status' ],
            appsero_api_change_license_status_params()
        );

        // Add or Update activation
        $this->post(
            '/products/(?P<product_id>[\d]+)/licenses/(?P<license_key>.+)/activations',
            [ $activations, 'update_or_create_item' ],
            appsero_api_update_or_create_activations_params()
        );

        // Delete activation
        $this->delete(
            '/products/(?P<product_id>[\d]+)/licenses/(?P<license_key>.+)/activations',
            [ $activations, 'delete_item' ],
            appsero_api_delete_activations_params()
        );

        // Get orders of specific product
        $this->get(
            '/products/(?P<product_id>[\d]+)/orders',
            [ $orders, 'get_items' ],
            appsero_api_params_with_product_id()
        );

        // Get subscriptions of specific product
        $this->get(
            '/products/(?P<product_id>[\d]+)/subscriptions',
            [ $subscriptions, 'get_items' ],
            appsero_api_params_with_product_id()
        );

        // Connect Appsero projects with WP store
        $this->post( '/products/connect', [ $common, 'connect_products' ] );

        // Disconnect Appsero projects with WP store
        $this->post( '/products/disconnect', [ $common, 'disconnect_products' ] );

        // Disconnect Appsero projects with WP store
        $this->post( '/native-licenses/(?P<source_id>[\d]+)/activations', [ $common, 'update_native_license_activations' ] );
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
     * connect_products
     */
    private function common_api() {
        // Initialize common API class
        require_once __DIR__ . '/Common_Api.php';

        return new Common_Api();
    }
}
