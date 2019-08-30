<?php
/**
 * Plugin Name: Appsero Helper
 * Plugin URI: https://wordpress.org/plugins/appsero-helper
 * Description: Helper plugin to connect WordPress store to Appsero
 * Author: Appsero
 * Author URI: https://appsero.com
 * Version: 1.0.1
 * Text Domain: appsero-helper
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Appsero_Helper class
 *
 * @class Appsero_Helper The class that holds the entire Appsero_Helper plugin
 */
class Appsero_Helper {

    /**
     * Plugin version
     *
     * @var string
     */
    public $version = '1.0.1';

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

        register_activation_hook( __FILE__, [ $this, 'activation_hook' ] );
        register_deactivation_hook( __FILE__, [ $this, 'activation_and_deactivation_hook' ] );

        $this->define_constants();

        add_action( 'plugins_loaded', [ $this, 'init_plugin' ] );

        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_scripts' ] );

        $this->immediate_classes();
    }

    /**
     * Initializes the Appsero_Helper() class
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
     * Define the constants
     *
     * @return void
     */
    public function define_constants() {
        define( 'ASHP_VERSION', $this->version );
        define( 'ASHP_ROOT_PATH', plugin_dir_path( __FILE__ ) );
        define( 'ASHP_ROOT_URL', plugins_url( '/', __FILE__ ) );
    }

    /**
     * Initialize the plugin
     *
     * @return void
     */
    public function init_plugin() {
        // Require API classes and Initialize
        $this->includes();

        // Initialize My Account page functionality
        $this->init_user_licenses_page();
    }

    /**
     * Include the required files
     *
     * @return void
     */
    public function includes() {

        if ( ! class_exists( 'WooCommerce' ) && ! class_exists( 'Easy_Digital_Downloads' ) ) {
            add_action( 'admin_notices', array( $this, 'dependency_error' ) );
            return;
        }

        require_once __DIR__ . '/includes/functions.php';
        require_once __DIR__ . '/includes/Traits/Hooker.php';
        require_once __DIR__ . '/includes/Traits/Rest.php';
        require_once __DIR__ . '/includes/Traits/OrderHelper.php';
        require_once __DIR__ . '/includes/Api.php';

        if ( class_exists( 'WooCommerce' ) ) {
            // Include class files
            require_once __DIR__ . '/includes/WooCommerce/UseCases/SendRequestsHelper.php';
            require_once __DIR__ . '/includes/WooCommerce/SendRequests.php';
            require_once __DIR__ . '/includes/WooCommerce.php';

            // Initialize WooCommerce API hooks
            $client = new Appsero\Helper\WooCommerce();

            // Initialize WooCommerce requests hooks
            new Appsero\Helper\WooCommerce\SendRequests();

        } else if ( class_exists( 'Easy_Digital_Downloads' ) ) {
            // Include class files
            require_once __DIR__ . '/includes/Edd/UseCases/SendRequestsHelper.php';
            require_once __DIR__ . '/includes/Edd/SendRequests.php';
            require_once __DIR__ . '/includes/Edd.php';

            // Initialize Edd API hooks
            $client = new Appsero\Helper\Edd();

            // Initialize Edd requests hooks
            new Appsero\Helper\Edd\SendRequests();
        }

        // Initialize API hooks
        new Appsero\Helper\Api( $client );

    }

    /**
     * Admin notice for no EDD or WC
     */
    public function dependency_error() {

        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $woo = wp_nonce_url( self_admin_url( 'update.php?action=install-plugin&plugin=woocommerce' ), 'install-plugin_woocommerce' );
        $edd = wp_nonce_url( self_admin_url( 'update.php?action=install-plugin&plugin=easy-digital-downloads' ), 'install-plugin_easy-digital-downloads' );

        $needed  = '<a href="https://woocommerce.com/" target="_blank">WooCommerce</a> or ';
        $needed .= '<a href="https://easydigitaldownloads.com/" target="_blank">Easy Digital Downloads</a>';

        $install = sprintf( '<p><a href="%s" class="button">Install WooCommerce</a> or <a href="%s" class="button">Install Easy Digital Downloads</a></p>', $woo, $edd );

        echo '<div class="notice notice-error">';
        echo '<p>Appsero Helper requires ' . $needed . ' to be installed and active.</p>';

        if ( current_user_can( 'install_plugins' ) ) {
            echo $install;
        }

        echo '</div>';
    }

    /**
     * Run my account page funcitonality
     */
    private function init_user_licenses_page() {
        if ( class_exists( 'WooCommerce' ) ) {
            require_once __DIR__ . '/includes/WooCommerce/MyAccountPage.php';

            new Appsero\Helper\WooCommerce\MyAccountPage();
        }

        if ( class_exists( 'Easy_Digital_Downloads' ) ) {
            require_once __DIR__ . '/includes/Edd/MyAccountPage.php';

            new Appsero\Helper\Edd\MyAccountPage();
        }
    }

    /**
     * Plugin activation and deactivation hook
     */
    public function activation_and_deactivation_hook() {
        // Flush rewrite rules on plugin activation
        add_rewrite_endpoint( 'my-licenses', EP_ROOT | EP_PAGES );

        flush_rewrite_rules();
    }

    /**
     * Enqueue CSS and JS
     */
    public function enqueue_scripts() {
        wp_register_style( 'ashp-my-account', ASHP_ROOT_URL . 'assets/css/my-account.css' );

        wp_register_script( 'ashp-my-account', ASHP_ROOT_URL . 'assets/js/my-account.js' );
    }

    /**
     * Activation Hook
     */
    public function activation_hook() {
        // Create tables
        $this->create_tables();

        // Run common functionality
        $this->activation_and_deactivation_hook();
    }

    /**
     * Create Database Tables
     */
    private function create_tables() {

        require_once __DIR__ . '/includes/Create_Database_Tables.php';

        new Appsero\Helper\Create_Database_Tables();
    }

    /**
     * Run class on Appsero_Helper instantiate
     */
    private function immediate_classes() {

        // Add settings page for set API key
        require_once __DIR__ . '/includes/SettingsPage.php';

        new Appsero\Helper\SettingsPage();

        // Manage ajax requests
        require_once __DIR__ . '/includes/Ajax_Requsts.php';

        new Appsero\Helper\Ajax_Requsts();

        // Manage shortcode
        require_once __DIR__ . '/includes/Shortcode.php';

        new Appsero\Helper\Shortcode();
    }

} // Appsero_Helper

Appsero_Helper::instance();
