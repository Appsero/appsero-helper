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
        $this->get( '/licenses/(?P<product_id>[\d]+)', [ $licenses, 'get_items' ], $this->licenses_params() );
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
     * URL and query parameter of licenses endpoint
     * @return array
     */
    private function licenses_params() {
        $collection_params = appsero_api_collection_params();

        $license_param = [
            'product_id' => [
                'description'       => __( 'Unique identifier for the project.', 'appsero-helper' ),
                'type'              => 'integer',
                'sanitize_callback' => 'absint',
            ]
        ];

        return array_merge( $collection_params, $license_param );
    }

}
