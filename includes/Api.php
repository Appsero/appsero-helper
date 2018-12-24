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
        $products    = $this->app->products();
        $orders      = $this->app->orders();
        $licenses    = $this->app->licenses();
        $activations = $this->app->activations();

        $this->get( '/status', [ $this, 'app_status' ] );

        $this->get( '/products', [ $products, 'get_items' ], appsero_api_collection_params() );
        $this->get( '/products/(?P<product_id>[\d]+)/licenses', [ $licenses, 'get_items' ], appsero_api_get_licenses_params() );

        // $this->get( '/orders', [ $orders, 'get_items' ], appsero_api_collection_params() );

        $this->post(
            '/projects/(?P<project_id>[\d]+)/licenses/(?P<license_id>[\d]+)/activations',
            [ $activations, 'update_or_create_item' ],
            appsero_api_update_or_create_activations_params()
        );

        $this->delete(
            '/projects/(?P<project_id>[\d]+)/licenses/(?P<license_id>[\d]+)/activations',
            [ $activations, 'delete_item' ],
            appsero_api_delete_activations_params()
        );

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

}
