<?php
/**
 * Include all classes here
 */

require_once __DIR__ . '/Option_Page.php';
require_once __DIR__ . '/REST_Projects_Controller.php';

/**
 * Instantiate all admin classes
 */
new ASHP\Option_Page();

/**
 * Register routes hook
 */
function register_ashp_rest_routes() {

    $projects = new ASHP\REST_Projects_Controller();
    $projects->register_routes();

}

add_action( 'rest_api_init', 'register_ashp_rest_routes' );
