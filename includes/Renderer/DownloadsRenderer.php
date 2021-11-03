<?php
namespace Appsero\Helper\Renderer;

class DownloadsRenderer {

    /**
     * Show orders of user
     */
    public function show() {
        wp_enqueue_style( 'ashp-my-account' );
        wp_enqueue_script( 'ashp-my-account' );

        // If user not logged in
        if ( ! is_user_logged_in() ) {
            return '<div class="appsero-notice notice-error">You must logged in to get downloads.</div>';
        }

        ob_start();

        do_action( 'before_appsero_myaccount_download_table' );
        ?>
        <div class="appsero-downloads">
            <?php
                $downloads = $this->get_downloads();
                if ( count( $downloads ) > 0 ) :
            ?>
            <table class="appsero-order-table appsero-downloads-table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Version</th>
                        <th>Version Date</th>
                        <th>Download</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                        foreach ( $downloads as $download ) {
                            $this->single_download_output( $download );
                        }
                    ?>
                </tbody>
            </table>
            <?php else: ?>
                <div class="appsero-notice notice-info">No downloads found.</div>
            <?php endif; ?>
        </div>
        <?php

        do_action( 'after_appsero_myaccount_download_table' );

        return ob_get_clean();
    }

    /**
     * Get user downloads
     */
    private function get_downloads() {
        $user_id = get_current_user_id();
        $route   = 'public/users/' . $user_id . '/downloads';

        // Send request to appsero server
        $response = appsero_helper_remote_get( $route );

        if ( wp_remote_retrieve_response_code( $response ) != 200 ) {
            return [];
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        return isset( $body['data'] ) ? $body['data'] : [];
    }

    /**
     * Single download row
     */
    private function single_download_output( $download ) {
        ?>
        <tr>
            <td>
                <?php echo $download['name']; ?>
                <?php if( $download['has_variations'] ) { echo '<br/><small>' . $download['variation_name'] . '</small>'; } ?>
            </td>
            <td><?php echo $download['version']; ?></td>
            <td><?php echo $download['release_date']; ?></td>
            <td><a href="<?php echo $download['download_url']; ?>">Download</a></td>
        </tr>
        <?php
    }

}
