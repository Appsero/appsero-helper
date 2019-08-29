<?php
namespace Appsero\Helper;

class SettingsPage {

    /**
     * Error message of form request
     *
     * @var string
     */
    protected $error;

    /**
     * Success message on form submit
     *
     * @var string
     */
    protected $success;

    /**
     * Token value form database
     *
     * @var array
     */
    protected $connection;

    /**
     * Batabase option name
     *
     * @var string
     */
    public static $connection_key = 'appsero_connection';

    /**
     * Constructor for the SettingsPage class
     */
    public function __construct() {
        if ( is_admin() ) {
            $this->connection = get_option( self::$connection_key, null );
            if (
                ( null === $this->connection || empty( $this->connection['token'] ) )
                && ( ! isset( $_GET['page'] ) || 'appsero_helper' != $_GET['page'] )
            ) {
                add_action( 'admin_notices', [ $this, 'not_connected_notice' ] );
            }
        }

        add_action( 'admin_menu', [ $this, 'admin_menu' ] );
    }

    /**
     * Generate the `Appsero Helper` menu
     * @return void
     */
    public function admin_menu() {
        add_options_page(
            'Appsero Helper',
            'Appsero Helper',
            'manage_options',
            'appsero_helper',
            [ $this, 'page_output' ]
        );
    }

    /**
     * HTML output of the `Appsero Helper` page
     */
    public function page_output() {
        if ( isset( $_POST['submit'] ) ) {
            $this->connection = $this->connect_with_appsero( $_POST );
        }

        $token = isset( $this->connection['token'] ) ? $this->connection['token'] : '';

        if ( $this->connection && isset( $this->connection['status'] ) && 'connected' == $this->connection['status'] ) {
            $action      = 'Disconnect';
            $button_type = 'link-delete';
        } else {
            $action      = 'Connect';
            $button_type = 'primary';
        }
        ?>
        <div class="wrap">
            <h1>Appsero Helper</h1>

            <?php if ( ! empty( $this->error ) ) : ?>
            <div class="notice notice-error is-dismissible" style="max-width: 745px;">
                <p><?php echo $this->error; ?></p>
            </div>
            <?php endif; ?>

            <?php if ( ! empty( $this->success ) ) : ?>
            <div class="notice notice-success is-dismissible" style="max-width: 745px;">
                <p><?php echo $this->success; ?></p>
            </div>
            <?php endif; ?>

            <br />

            <div class="widget open" style="max-width: 800px; margin: 0;">
                <div class="widget-top">
                    <div class="widget-title"><h3>Connect With Appsero</h3></div>
                </div>
                <div class="widget-inside" style="display: block; padding: 5px 15px;">
                    <p>Create an API key on Appsero `API Key` page under top right navigation to connect this store with Appsero.</p>
                    <form method="post" autocomplete="off">
                        <input type="text" value="<?php echo $token; ?>" class="regular-text code"
                            placeholder="API key" name="token"
                            <?php echo ( 'Disconnect' == $action ) ? 'readonly="readonly"' : ''; ?>
                        />
                        <input type="hidden" name="_action" value="<?php echo $action; ?>">
                        <p>
                            <button type="submit" name="submit" class="button button-<?php echo $button_type; ?>">
                                <?php echo $action; ?>
                            </button>
                        </p>
                    </form>
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
                You have not connected with Appsero in order to work <a href="<?php echo $appsero_helper_url; ?>">Apsero Helper</a>,
                Please <a href="<?php echo $appsero_helper_url; ?>">connect</a> using API key.
            </p>
        </div>
        <?php
    }

    /**
     * Connect with appsero server
     */
    public function connect_with_appsero( $form ) {
        if ( empty( $form['token'] ) ) {
            $this->error = 'Token Is Required.';
            return $form;
        }

        if ( 'Disconnect' == $form['_action'] ) {
            $option_value = [
                'token'  => '',
                'status' => 'disconnected',
            ];
            update_option( self::$connection_key, $option_value, false );
            $this->success = 'Disconnected Successfully.';
            return $option_value;
        }

        $response = appsero_helper_remote_post( 'public/connect-helper', [
            'token'      => $form['token'],
            'url'        => esc_url( home_url() ),
            'api_prefix' => rest_get_url_prefix(),
        ] );

        if ( is_wp_error( $response ) ) {
            $this->error = $response->get_error_message();
            return $form;
        }

        $option_value = [
            'token'  => $form['token'],
            'status' => 'connected',
        ];

        if ( 200 == $response['response']['code'] ) {
            update_option( self::$connection_key, $option_value, false );
        }

        $response_array = json_decode( $response['body'], true );

        if ( isset( $response_array['success'] ) ) {
            if ( $response_array['success'] ) {
                $this->success = $response_array['message'];
                return $option_value;
            }

            $this->error = $response_array['error'];
            return $form;
        }

        $this->error = 'Unknown Error Occurred.';

        return $form;
    }

}
