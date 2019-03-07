<?php
/**
 * Plugin Name: Appsero Helper
 * Plugin URI: https://wordpress.org/plugins/appsero-helper
 * Description: Helper plugin to connect WordPress store to AppSero
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
        add_action( 'activated_plugin', [ $this, 'helper_activation' ], 12, 1 );

        $this->define_constants();

        add_action( 'plugins_loaded', [ $this, 'init_plugin' ] );

        add_action( 'wp_ajax_connect_with_appsero', [ $this, 'connect_with_appsero' ] );

        // Add settings page for set API key
        require_once __DIR__ . '/includes/SettingsPage.php';

        new Appsero\Helper\SettingsPage();
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
     * Define the constants
     *
     * @return void
     */
    public function define_constants() {
        define( 'ASHP_VERSION', $this->version );
        define( 'ASHP_ROOT_PATH', plugin_dir_path( __FILE__ ) );
    }

    /**
     * Initialize the plugin
     *
     * @return void
     */
    public function init_plugin() {
        $this->includes();
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

            // Initialize WooCommerce API hooks
            require_once __DIR__ . '/includes/WooCommerce.php';
            $client = new Appsero\Helper\WooCommerce();

            // Initialize WooCommerce requests hooks
            require_once __DIR__ . '/includes/WooCommerce/SendRequests.php';
            new Appsero\Helper\WooCommerce\SendRequests();

        } else if ( class_exists( 'Easy_Digital_Downloads' ) ) {

            // Initialize Edd API hooks
            require_once __DIR__ . '/includes/Edd.php';
            $client = new Appsero\Helper\Edd();

            // Initialize Edd requests hooks
            require_once __DIR__ . '/includes/Edd/SendRequests.php';
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
     * Run this function after activate the plugin
     *
     * @uses plugin_basename()
     * @uses wp_redirect()
     * @uses admin_url()
     */
    public function helper_activation( $plugin ) {
        if( $plugin == plugin_basename( __FILE__ ) ) {
            wp_redirect( admin_url( 'options-general.php?page=appsero_helper' ) );
            exit;
        }
    }

} // AppSero_Helper

Appsero_Helper::instance();
