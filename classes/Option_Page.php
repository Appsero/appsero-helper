<?php

namespace ASHP;

/**
 * This class is responsible for settings page
 *
 * @url /wp-admin/options-general.php?page=ashp_settings
 */
class Option_Page {

    public $settings = [];

    public function __construct() {

        add_action( 'admin_menu', array( $this, 'admin_menu' ) );

    }

    public function admin_menu() {

        add_options_page(
            'AppSero Helper Settings',
            'AppSero Helper',
            'manage_options',
            'ashp_settings',
            array( $this, 'settings_page' )
        );

    }

    public function settings_page() {

        if ( isset( $_POST['_wpnonce'] ) && wp_verify_nonce( $_POST['_wpnonce'], 'ashp' ) ) {
            $this->save_settings( $_POST );
        }

        $this->settings = get_option( '_ashp_settings', [] );

        include ASHP_ROOT_PATH . 'templates/settings-page.php';

    }

    public function save_settings( $data ) {
        unset( $data['_wpnonce'] );
        unset( $data['submit'] );

        update_option( '_ashp_settings', $data, false );
    }

    public function checked( $value, $name ) {
        $name = isset( $this->settings[ $name ] ) ? $this->settings[ $name ] : '';
        selected( $value, $name );
    }

}
