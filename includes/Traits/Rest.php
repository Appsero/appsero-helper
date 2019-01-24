<?php
namespace Appsero\Helper\Traits;

use Appsero\Helper\SettingsPage;
use WP_REST_Controller;
use WP_REST_Server;
use WP_Error;

/**
 * Rest Controller class
 */
trait Rest {

    /**
     * Register GET route
     *
     * @since 1.0.0
     *
     * @param  string $endpoint
     * @param  string $callback
     * @param  string $permission
     *
     * @return void
     */
    protected function get( $endpoint, $callback, $args = [] ) {
        $this->register_route( 'get', $endpoint, $callback, $args );
    }

    /**
     * Register POST route
     *
     * @since 1.0.0
     *
     * @param  string $endpoint
     * @param  string $callback
     * @param  string $permission
     *
     * @return void
     */
    protected function post( $endpoint, $callback, $args = [] ) {
        $this->register_route( 'post', $endpoint, $callback, $args );
    }

    /**
     * Register DELETE route
     *
     * @since 1.0.0
     *
     * @param  string $endpoint
     * @param  string $callback
     * @param  string $permission
     *
     * @return void
     */
    protected function delete( $endpoint, $callback, $args = [] ) {
        $this->register_route( 'delete', $endpoint, $callback, $args );
    }

    /**
     * Register route endpoints
     *
     * @since 1.0.0
     *
     * @param  string $method
     * @param  string $endpoint
     * @param  string $callback
     * @param  string $permission
     *
     * @return void
     */
    protected function register_route( $method, $endpoint, $callback, $args = [] ) {
        switch ( $method ) {
            case 'post':
                $methods = WP_REST_Server::CREATABLE;
                break;

            case 'delete':
                $methods = WP_REST_Server::DELETABLE;
                break;

            case 'get':
            default:
                $methods = WP_REST_Server::READABLE;
                break;
        }

        register_rest_route( $this->namespace, $endpoint, [
            [
                'methods'             => $methods,
                'callback'            => $callback,
                'permission_callback' => [ $this, 'permission_check' ],
                'args'                => $args
            ]
        ] );
    }

    /**
     * API Permission Check
     *
     * Only a valid API key should be able to perform the requests
     *
     * @param  \WP_REST_Request $request
     *
     * @return bool
     */
    public function permission_check( $request ) {
        $secret  = $request->get_header( 'X-Api-Key' );

        if ( empty( $secret ) ) {
            return false;
        }

        $api_key = false;

        if ( defined( 'APPSERO_API_KEY' ) ) {
            $api_key = APPSERO_API_KEY;
        } else {
            $connection = get_option( SettingsPage::$connection_key, null );
            $api_key    = isset( $connection['token'] ) ? $connection['token'] : false;
        }

        if ( $api_key && trim( $secret ) == trim( $api_key ) ) {
            return true;
        }

        return false;
    }
}
