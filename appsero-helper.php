<?php
/**
 * Plugin Name: Appsero Helper
 * Plugin URI: https://github.com/Appsero/appsero-helper
 * Description: Helper plugin to connect WordPress to Appsero
 * Author: Appsero
 * Author URI: https://appsero.com
 * Version: 1.0.0
 *
 * Appsero Helper is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * any later version.
 *
 * Appsero Helper is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Appsero Helper. If not, see <http://www.gnu.org/licenses/>.
 *
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
        $this->define_constants();

        add_action( 'plugins_loaded', [ $this, 'init_plugin' ] );

        add_action( 'admin_init', [ $this, 'register_setting' ] );

        add_action( 'wp_ajax_connect_with_appsero', [ $this, 'connect_with_appsero' ] );
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
        require_once __DIR__ . '/includes/Api.php';
        require_once __DIR__ . '/includes/SendRequests.php';

        if ( class_exists( 'WooCommerce' ) ) {

            require_once __DIR__ . '/includes/WooCommerce.php';
            $client = new Appsero\Helper\WooCommerce();

        } else if ( class_exists( 'Easy_Digital_Downloads' ) ) {

            require_once __DIR__ . '/includes/Edd.php';
            $client = new Appsero\Helper\Edd();
        }

        // Initialize API hooks
        new Appsero\Helper\Api( $client );

        // Initialize request hooks
        new Appsero\Helper\SendRequests();
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
     * Settings field for API token
     *
     * @return void
     */
    public function register_setting() {
        if ( 'options-general.php' != $GLOBALS['pagenow'] )
            return;

        add_settings_section(
            'appsero_helper_setting_section',
            'AppSero Helper',
            [ $this, 'setting_section_callback' ],
            'general'
        );

        add_settings_field(
            'appsero_connection_token',
            'Token',
            [ $this, 'setting_field_callback' ],
            'general',
            'appsero_helper_setting_section',
            [ 'label_for' => 'appsero_connection_token' ]
        );
    }

    /**
     * Settings field html output
     */
    public function setting_field_callback( $args ) {
        $value = get_option( 'appsero_connection_token', '' );
        ?>
        <div class="appsero_token_field">
            <input id="appsero_connection_token" type="text" value="<?php echo $value; ?>" class="regular-text code" />
            <button class="button" type="button" id="appsero_connect_button" style="margin: 1px 10px 0 10px;">Connect</button>
            <span class="spinner is-active" style="display: none"></span>
            <span class="feedback-message"></span>
        </div>
        <?php
    }

    /**
     * Settigs section
     */
    public function setting_section_callback() {
        ?>
        <script type="text/javascript">
            jQuery( function() {
                jQuery( '#appsero_connect_button' ).click( function() {
                    var spinner = jQuery( '.appsero_token_field span.spinner' );
                    var feedback = jQuery( '.appsero_token_field span.feedback-message' );
                    spinner.show();
                    feedback.text('');

                    var token = jQuery( '#appsero_connection_token' ).val();
                    var data = {
                        'action': 'connect_with_appsero',
                        'token': token
                    };

                    jQuery.post(ajaxurl, data, function( response ) {
                        spinner.hide();
                        if ( response.success ) {
                            feedback.css( 'color', '#4CAF50' );
                        } else  {
                            feedback.css( 'color', '#ff4d4d' );
                        }
                        feedback.text( response.message );
                    } );
                } );
            } );
        </script>
        <?php
    }

    /**
     * Connect with appsero server
     * Handle HTTP request for connect site using token
     */
    public function connect_with_appsero() {
        if ( empty( $_POST['token'] ) ) {
            wp_send_json( [
                'success' => false,
                'message' => 'Token is required.'
            ] );
        }

        $response = appsero_helper_remote_post( 'public/connect-helper', [
            'token'      => $_POST['token'],
            'url'        => esc_url( home_url() ),
            'api_prefix' => rest_get_url_prefix(),
        ] );

        if ( is_wp_error( $response ) ) {
            wp_send_json( [
                'success' => false,
                'message' => $response->get_error_message(),
            ] );
        }

        if ( 200 == $response['response']['code'] ) {
            update_option( 'appsero_connection_token', $_POST['token'], false );
        }

        $response_array = json_decode( $response['body'], true );

        if ( isset( $response_array['success'] ) ) {
            wp_send_json( $response_array );
        }

        wp_send_json( [
            'success' => false,
            'message' => 'Unknown error occurred.',
        ] );
    }

} // AppSero_Helper

Appsero_Helper::instance();
