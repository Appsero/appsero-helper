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
        $products = $this->app->products();
        $orders   = $this->app->orders();
        $licenses = $this->app->licenses();

        $this->get( '/status', [ $this, 'app_status' ] );

        $this->get( '/products', [ $products, 'get_items' ], appsero_api_collection_params() );
        $this->get( '/orders', [ $orders, 'get_items' ], appsero_api_collection_params() );
        $this->get( '/licenses', [ $licenses, 'get_items' ], appsero_api_collection_params() );
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
