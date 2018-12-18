<?php
/**
 * Plugin Name: AppSero Helper
 * Plugin URI: https://wedevs.com
 * Description: Helper plugin to connect WP marketplace to AppSero
 * Author: Tareq Hasan
 * Author URI: https://tareq.co
 * Version: 1.0.0
 *
 * AppSero Helper is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * any later version.
 *
 * AppSero Helper is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with AppSero Helper. If not, see <http://www.gnu.org/licenses/>.
 *
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * AppSero_Helper class
 *
 * @class AppSero_Helper The class that holds the entire AppSero_Helper plugin
 */
class AppSero_Helper {

    /**
     * Plugin version
     *
     * @var string
     */
    public $version = '1.0.0';

    /**
     * The single instance of the class.
     */
    protected static $_instance = null;

    /**
     * Constructor for the AppSero_Helper class
     *
     * Sets up all the appropriate hooks and actions
     * within our plugin.
     *
     * @uses is_admin()
     * @uses add_action()
     */
    public function __construct() {

        add_action( 'plugins_loaded', array( $this, 'define_constants' ) );
    }

    /**
     * Initializes the AppSero_Helper() class
     *
     * Checks for an existing AppSero_Helper() instance
     * and if it doesn't find one, creates it.
     */
    public static function instance() {

        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    /**
     * Include the required files
     *
     * @return void
     */
    public function includes() {
        if ( ! ASHP_MARKET_TYPE ) {
            add_action( 'admin_notices', array( $this, 'activate_notice' ) );
        } else {

            require_once ASHP_ROOT_PATH . 'includes/REST_Projects_Controller.php';
            require_once ASHP_ROOT_PATH . 'includes/REST_Projects_Activations_Controller.php';
            require_once ASHP_ROOT_PATH . 'includes/' . ASHP_MARKET_TYPE . '/load.php';

            // Run hooks
            $this->init_hooks();
        }
    }

    /**
     * Initialize the hooks
     *
     * @return void
     */
    public function init_hooks() {

        add_action( 'rest_api_init', array( $this, 'register_routes' ) );

    }

    /**
     * Define the constants
     *
     * @return void
     */
    public function define_constants() {
        define( 'ASHP_ROOT_PATH', plugin_dir_path( __FILE__ ) );

        if ( class_exists( 'Easy_Digital_Downloads' ) ) {
            define( 'ASHP_MARKET_TYPE', 'EDD' );
        } elseif ( class_exists( 'WC_Software' ) ) {
            define( 'ASHP_MARKET_TYPE', 'WOOSA' );
        } elseif ( class_exists( 'WooCommerce' ) ) { // Maybe will change later
            define( 'ASHP_MARKET_TYPE', 'WOOAPI' );
        } else {
            define( 'ASHP_MARKET_TYPE', null );
        }

        // Include necessary files
        $this->includes();
    }

    /**
     * Admin notice for no EDD or WC
     */
    public function activate_notice() {
        $needed  = '<a href="https://woocommerce.com/" target="_blank">WooCommerce</a> or ';
        $needed .= '<a href="https://easydigitaldownloads.com/" target="_blank">Easy Digital Downloads</a>';
        echo '<div class="notice notice-error"><p>Metorik Helper requires ' . $needed . ' to be installed and active.</p></div>';
    }

    /**
     * EDD route hooks
     */
    public function register_routes() {
        $projects_class = 'AppseroHelper\\' . ASHP_MARKET_TYPE . '\\Projects_Controller';
        if ( class_exists( $projects_class ) ) {
            $projects = new $projects_class();
            $projects->register_routes();
        }

        $activations_class = 'AppseroHelper\\' . ASHP_MARKET_TYPE . '\\Activations_Controller';
        if ( class_exists( $activations_class ) ) {
            $activations = new $activations_class();
            $activations->register_routes();
        }
    }

} // AppSero_Helper

$appsero_helper =  AppSero_Helper::instance();
