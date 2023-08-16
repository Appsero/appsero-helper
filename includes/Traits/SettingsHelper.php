<?php
namespace Appsero\Helper\Traits;

trait SettingsHelper {

    /**
     * Appsero page CSS and JS
     */
    public function appsero_page_scripts() {
        $version = filemtime( ASHP_ROOT_PATH . 'assets/css/settings-page.css' );
        $jsversion = filemtime( ASHP_ROOT_PATH . 'assets/js/settings-page.js' );

        wp_enqueue_style( 'appsero_settings_page_style', ASHP_ROOT_URL . 'assets/css/settings-page.css', [], $version );

        wp_enqueue_script( 'appsero_settings_page', ASHP_ROOT_URL . 'assets/js/settings-page.js', [ 'jquery' ], $jsversion );
    }

    /**
     * Show error modal if user tries to connect appsero in localserver
     */
    private function showLocalSiteError() {
        ?>
        <div class="appsero-modal" id="appsero-local-error">
            <div class="appsero-modal-content">
                <span class="appsero-modal-close">&times;</span>
                <div style="margin: 20px 20px 20px 0px">
                    You are using <b>Appsero Helper</b> in local server. <b>Appsero Helper</b> will not function properly in local server.
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Show notice on admin panel
     *
     * @return void
     */
    public function not_connected_notice() {
        $appsero_helper_url = esc_url( admin_url( 'options-general.php?page=appsero_helper' ) );
        ?>
        <div class="notice notice-warning">
            <p>
                You have not connected your website with <a href="<?php echo $appsero_helper_url; ?>">Appsero Helper</a>.
                Please <a href="<?php echo $appsero_helper_url; ?>">connect</a> using API key.
            </p>
        </div>
        <?php
    }

    /**
     * Selling plugin list
     */
    private function selling_plugins() {
        $plugins = [
            'fastspring' => 'Appsero With FastSpring',
            'paddle'     => 'Appsero With Paddle',
        ];

        if ( class_exists( 'WooCommerce' ) ) {
            $plugins['woo'] = 'WooCommerce';
        }

        if ( class_exists( 'Easy_Digital_Downloads' ) ) {
            $plugins['edd'] = 'Easy Digital Downloads';
        }

        return $plugins;
    }

    /**
     * Check whether it is local server
     */
    private function is_local()
    {
        $local_ips = [ '127.0.0.1', '::1' ];
        $isLocal   = false;

        if ( in_array( $_SERVER['REMOTE_ADDR'], $local_ips ) )
            $isLocal = true;

        return apply_filters( 'appsero_is_local', $isLocal );
    }

    /**
     * Check if the selling method is affiliate enabled
     */
    private function isAffiliatable( $selling_plugin ) {
        return $selling_plugin === 'fastspring' || $selling_plugin === 'paddle';
    }

    /**
     * Get Selling Plugin Name
     */
    private function showSellingPluginName() {
        $selling_plugin = get_option( 'appsero_selling_plugin', '' );

        echo '<span class="appsero-sp-name">' . ucfirst($selling_plugin) . '</span>';
    }

}
