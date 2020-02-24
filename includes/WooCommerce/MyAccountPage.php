<?php
namespace Appsero\Helper\WooCommerce;

class MyAccountPage {

    /**
     * Constructor
     */
    public function __construct() {

        // IF Woo SA and Woo API not installed then show our page
        if ( ! class_exists( 'WC_Software' ) && ! class_exists( 'WooCommerce_API_Manager' ) ) {
            add_filter( 'woocommerce_account_menu_items', [ $this, 'account_menu_items' ] );

            add_action( 'init', [ $this, 'custom_endpoints' ] );

            add_filter( 'query_vars', [ $this, 'custom_query_vars' ] );

            remove_action( 'woocommerce_account_downloads_endpoint', 'woocommerce_account_downloads', 10 );
            add_action( 'woocommerce_account_downloads_endpoint', [ $this, 'downloads_content' ], 20 );

            add_action( 'woocommerce_account_my-licenses_endpoint', [ $this, 'my_licenses_content' ] );

            add_filter( 'the_title', [ $this, 'my_licenses_title' ] );
        }

    }

    /**
     * Account menu items
     * `Licenses` menu, set after `Downloads`
     *
     * @param array $items
     * @return array
     */
    public function account_menu_items( $items ) {
        $new_items = [
            'my-licenses' => 'Licenses'
        ];

        $position = array_search( 'downloads', array_keys( $items ) ) + 1;

        $result = array_slice( $items, 0, $position, true );

        $result += $new_items;

        $result += array_slice( $items, $position, count( $items ) - $position, true );

        return $result;
    }

    /**
     * Register new endpoint to use inside My Account page.
     */
    public function custom_endpoints() {
        add_rewrite_endpoint( 'my-licenses', EP_ROOT | EP_PAGES );
    }

    /**
     * Add new query var.
     *
     * @param array $vars
     * @return array
     */
    public function custom_query_vars( $vars ) {
        $vars[] = 'my-licenses';

        return $vars;
    }

    /**
     * Licenses page HTML content.
     */
    public function my_licenses_content() {
        require_once ASHP_ROOT_PATH . 'includes/Renderer/LicensesRenderer.php';

        $renderer = new \Appsero\Helper\Renderer\LicensesRenderer();

        echo $renderer->show();
    }

    /*
     * Change licenses page title.
     *
     * @param string $title
     * @return string
     */
    public function my_licenses_title( $title ) {
        global $wp_query;

        $is_endpoint = isset( $wp_query->query_vars['my-licenses'] );

        if ( $is_endpoint && ! is_admin() && is_main_query() && in_the_loop() && is_account_page() ) {
            // New page title.
            $title = 'My Licenses';

            remove_filter( 'the_title', [ $this, 'my_licenses_title' ] );
        }

        return $title;
    }

    /**
     * Downloads page content
     */
    public function downloads_content() {
        require_once ASHP_ROOT_PATH . 'includes/Renderer/DownloadsRenderer.php';

        $renderer = new \Appsero\Helper\Renderer\DownloadsRenderer();

        echo $renderer->show();
    }
}
