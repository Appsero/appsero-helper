<?php
/**
 * Plugin Name: Appsero Helper
 * Plugin URI: https://wordpress.org/plugins/appsero-helper
 * Description: Helper plugin to connect WordPress store to Appsero
 * Author: Appsero
 * Author URI: https://appsero.com
 * Version: 1.3.2
 * Text Domain: appsero-helper
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Appsero_Helper class
 *
 * @class Appsero_Helper The class that holds the entire Appsero_Helper plugin
 */
final class Appsero_Helper {

    /**
     * Plugin version
     *
     * @var string
     */
    public $version = '1.3.2';

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

        $this->immediate_load();

        add_action( 'plugins_loaded', [ $this, 'init_plugin' ] );

        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_scripts' ] );

        $this->invokeRequiredHooks();

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

        // Dashbaord specific functionality
        if ( is_admin() ) {
            require_once __DIR__ . '/includes/Admin_Notice.php';

            new Appsero\Helper\Admin_Notice();
        }

        // Require API classes and Initialize
        $this->woo_and_edd_includes();
    }

    /**
     * Run necessary hooks
     */
    public function invokeRequiredHooks() {      
        // Update customer data to sync with Appsero
        add_action( 'profile_update', 'appsero_update_customer', 10, 2 );
    }

    /**
     * Include the required files
     *
     * @return void
     */
    public function woo_and_edd_includes() {
        require_once __DIR__ . '/includes/Traits/OrderHelper.php';
        require_once __DIR__ . '/includes/Api.php';

        $client = $this->get_selling_client();

        if ( ! $client ) {
            return;
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
        echo '<p>Appsero Helper requires ' . wp_kses_post( $needed ) . ' to be installed and active.</p>';

        if ( current_user_can( 'install_plugins' ) ) {
            echo wp_kses_post( $install );
        }

        echo '</div>';
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
        wp_register_style( 'ashp-my-account', ASHP_ROOT_URL . 'assets/css/my-account.css', [], $this->version );

        wp_register_script( 'ashp-my-account', ASHP_ROOT_URL . 'assets/js/my-account.js', [ 'jquery' ], $this->version );

        wp_localize_script( 'ashp-my-account', 'appseroHelper', [
            'ajaxUrl'    => admin_url( 'admin-ajax.php' ),
            'ajaxNonce' => wp_create_nonce( 'appsero-store-myaccount' ),
        ] );
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
    private function immediate_load() {
        // Helpers
        $this->include_helpers();

        // Add settings page for set API key
        require_once __DIR__ . '/includes/SettingsPage.php';

        new Appsero\Helper\SettingsPage();

        // Manage ajax requests
        require_once __DIR__ . '/includes/Ajax_Requsts.php';

        new Appsero\Helper\Ajax_Requsts();

        // Manage shortcode
        require_once __DIR__ . '/includes/Shortcode.php';

        new Appsero\Helper\Shortcode();

        // Common API
        require_once __DIR__ . '/includes/Common/Api.php';

        new Appsero\Helper\Common\Api();

        // Filters
        require_once __DIR__ . '/includes/Common/Filter_Hook.php';

        new Appsero\Helper\Common\Filter_Hook();

        // Include AffiliateWP classes
        $this->affiliate_wp_includes();
    }

    /**
     * Include and return woocommerce api client
     */
    private function get_woocommerce_api_client() {
        // Include class files
        require_once __DIR__ . '/includes/WooCommerce.php';

        // Initialize WooCommerce API hooks
        return new Appsero\Helper\WooCommerce();
    }

    /**
     * Include and return edd api client
     */
    private function get_edd_api_client() {
        // Include class files
        require_once __DIR__ . '/includes/Edd.php';

        // Initialize Edd API hooks
        return new Appsero\Helper\Edd();
    }

    /**
     * Get selling client to build API
     */
    private function get_selling_client() {
        $plugin = appsero_get_selling_plugin();

        if ( $plugin === 'woo' ) {
            return $this->get_woocommerce_api_client();
        }

        if ( $plugin === 'edd' ) {
            return $this->get_edd_api_client();
        }

        return false;
    }

    /**
     * Load AffiliateWP classes
     */
    private function affiliate_wp_includes() {
        // Include class files
        require_once __DIR__ . '/includes/AffiliateWP/Handle.php';

        // Instantiate classes
        new Appsero\Helper\AffiliateWP\Handle();
    }

    /**
     * Load helper classes
     */
    private function include_helpers() {
        require_once __DIR__ . '/includes/Traits/Hooker.php';
        require_once __DIR__ . '/includes/Traits/Rest.php';
        require_once __DIR__ . '/includes/Traits/SettingsHelper.php';
        require_once __DIR__ . '/includes/functions.php';
    }

} // Appsero_Helper

Appsero_Helper::instance();
