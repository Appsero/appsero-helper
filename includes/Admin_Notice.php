<?php

namespace Appsero\Helper;

class Admin_Notice {

    /**
     * Constructor
     */
    public function __construct() {
        // If EDD and Woo both install
        if ( class_exists( 'WooCommerce' ) && class_exists( 'Easy_Digital_Downloads' ) ) {
            add_action( 'admin_notices', [ $this, 'edd_and_woo_both_install_error' ] );
        }

        // If EDD and Woo no one is install
        if ( ! class_exists( 'WooCommerce' ) && ! class_exists( 'Easy_Digital_Downloads' ) ) {
            add_action( 'admin_notices', [ $this, 'create_pages_notice' ] );

            add_action( 'admin_notices', [ $this, 'pages_created_success_notice' ] );
        }

        add_action( 'admin_notices', [ $this, 'no_product_warning' ] );

    }

    /**
     * EDD and Woo both install error message
     */
    public function edd_and_woo_both_install_error() {
        $has_plugin = get_option( 'appsero_selling_plugin', '' );

        if ( $has_plugin ) {
            return;
        }

        $security = wp_create_nonce( 'appsero-selling-plugin' );
        $action_url = admin_url( 'admin-ajax.php' );
        $action_url .= '?action=appsero_set_selling_plugin';
        $action_url .= '&security=' . $security . '&selected=%s';
        ?>
        <div class="notice notice-error">
            <p>You have installed both WooCommerce and Easy Digital Downloads, Please choose your selling plugin.</p>
            <p><a href="<?php echo esc_url( sprintf( $action_url, 'woo' ) ); ?>" class="button">WooCommerce</a> or <a href="<?php echo esc_url( sprintf( $action_url, 'edd' ) ); ?>" class="button">Easy Digital Downloads</a></p>
        </div>
        <?php
    }

    /**
     * Create appsero pages
     */
    public function create_pages_notice() {
        $created_at = get_option( 'appsero_shortcode_pages_created_at', '' );

        if ( $created_at ) {
            return;
        }

        $security = wp_create_nonce( 'appsero-create-pages' );
        $url = admin_url( 'admin-ajax.php' );
        $url .= '?action=appsero_create_shortcode_pages';
        $url .= '&security=' . $security
        ?>
        <div class="notice notice-info">
            <p>Show Orders, Licenses and Downloads from Appsero pages. Do you want to create Appsero pages?</p>
            <p><a href="<?php echo esc_url( $url ); ?>" class="button">Create Pages</a> <a href="<?php echo esc_url( $url ); ?>&cancel=true" class="button button-link-delete">Cancel</a></p>
        </div>
        <?php
    }

    /**
     * Show notice for successfully pages created
     */
    public function pages_created_success_notice() {
        if ( isset( $_GET['appsero'] ) && 'pages_created' === $_GET['appsero'] ) :
        ?>
            <div class="notice is-dismissible notice-success">
                <p>Appsero pages has been created successfully.</p>
            </div>
        <?php
        endif;
    }

    /**
     * Show no product warning
     */
    public function no_product_warning() {
        global $pagenow;

        if ( 'post-new.php' == $pagenow && isset( $_GET['post_type'] ) ) {
            return;
        }

        $plugin = appsero_get_selling_plugin();

        if ( $plugin === 'woo' ) {
            $products = get_posts( [
                'fields'      => 'ids',
                'post_type'   => 'product',
                'post_status' => 'publish',
            ] );

            if ( ! empty( $products ) ) {
                return;
            }
            ?>
            <div class="notice notice-warning">
                <p>No product found in your WooCommerce store, Please create product to connect with Appsero.</p>
                <p><a href="<?php echo esc_url( admin_url() ); ?>post-new.php?post_type=product" class="button">Create Product</a></p>
            </div>
            <?php
        }

        if ( $plugin === 'edd' ) {
            $downloads = get_posts( [
                'fields'      => 'ids',
                'post_type'   => 'download',
                'post_status' => 'publish',
            ] );

            if ( ! empty( $downloads ) ) {
                return;
            }
            ?>
            <div class="notice notice-warning">
                <p>No product found in your Easy Digital Downloads store, Please create product to connect with Appsero.</p>
                <p><a href="<?php echo esc_url( admin_url() ); ?>post-new.php?post_type=download" class="button">Create Product</a></p>
            </div>
            <?php
        }
    }

}
