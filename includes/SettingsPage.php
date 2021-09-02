<?php
namespace Appsero\Helper;

use Appsero\Helper\Traits\SettingsHelper;

class SettingsPage {

    use SettingsHelper;

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
        $appsero_page = add_options_page(
            'Appsero Helper',
            'Appsero Helper',
            'manage_options',
            'appsero_helper',
            [ $this, 'page_output' ]
        );

        add_action( $appsero_page, [ $this, 'appsero_page_scripts' ] );
    }

    /**
     * HTML output of the `Appsero Helper` page
     */
    public function page_output() {
        if ( isset( $_POST['apikey_submit'] ) ) {
            $this->connection = $this->connect_with_appsero( $_POST );
        }

        if ( isset( $_POST['settings_submit'] ) ) {
            $this->save_settings_fields( $_POST );
        }

        $token = isset( $this->connection['token'] ) ? $this->connection['token'] : '';
        $button_class = '';

        if ( $this->connection && isset( $this->connection['status'] ) && 'connected' == $this->connection['status'] ) {
            $action      = 'Disconnect';
            $button_class = 'disconnect-button';
        } else {
            $action = 'Connect';
        }

        $selling_plugin = get_option( 'appsero_selling_plugin', '' );
        ?>
        <div class="wrap">
            <h1>Appsero Helper</h1>

            <?php if ( ! empty( $this->error ) ) : ?>
            <div class="notice notice-error is-dismissible" style="max-width: 852px;">
                <p><?php echo $this->error; ?></p>
            </div>
            <?php endif; ?>

            <?php if ( ! empty( $this->success ) ) : ?>
            <div class="notice notice-success is-dismissible" style="max-width: 852px;">
                <p><?php echo $this->success; ?></p>
            </div>
            <?php endif; ?>

            <br />

            <div class="appsero-widget">
                <div class="appsero-widget-logo">
                    <img src="<?php echo ASHP_ROOT_URL; ?>assets/images/appsero-logo.png" alt="Appsero Logo">
                </div>
                <p class="darker-text">
                    Create an API key on Appsero from the `<strong>API Key</strong>` page from left navigation pane or by adding a new product.<br>
                    Activate Appsero with the API key to connect this store with Appsero dashboard.
                </p>
                <form class="apikey-input-fields" method="post" autocomplete="off">
                    <div class="apikey-input-key">
                        <svg enable-background="new 0 0 512 512" version="1.1" viewBox="0 0 512 512" xml:space="preserve" xmlns="http://www.w3.org/2000/svg" width="22">
                            <path d="m463.75 48.251c-64.336-64.336-169.01-64.335-233.35 1e-3 -43.945 43.945-59.209 108.71-40.181 167.46l-185.82 185.82c-2.813 2.813-4.395 6.621-4.395 10.606v84.858c0 8.291 6.709 15 15 15h84.858c3.984 0 7.793-1.582 10.605-4.395l21.211-21.226c3.237-3.237 4.819-7.778 4.292-12.334l-2.637-22.793 31.582-2.974c7.178-0.674 12.847-6.343 13.521-13.521l2.974-31.582 22.793 2.651c4.233 0.571 8.496-0.85 11.704-3.691 3.193-2.856 5.024-6.929 5.024-11.206v-27.929h27.422c3.984 0 7.793-1.582 10.605-4.395l38.467-37.958c58.74 19.043 122.38 4.929 166.33-39.046 64.336-64.335 64.336-169.01 0-233.35zm-42.435 106.07c-17.549 17.549-46.084 17.549-63.633 0s-17.549-46.084 0-63.633 46.084-17.549 63.633 0 17.548 46.084 0 63.633z"></path>
                        </svg>
                        <input type="text" value="<?php echo $token; ?>"
                            placeholder="Paste your API key" name="token"
                            <?php echo ( 'Disconnect' == $action ) ? 'readonly="readonly"' : ''; ?>
                        />
                        <input type="hidden" name="_action" value="<?php echo $action; ?>">
                    </div>
                    <button type="submit" name="<?php echo ( $this->is_local() && $action == 'Connect' ? 'local_submit' : 'apikey_submit' ); ?>" class="<?php echo $button_class; ?>"><?php echo $action; ?></button>
                </form>

                <form method="post" autocomplete="off" class="appsero-settings-form">
                    <h2 class="title">Settings</h2>
                    <table class="form-table">
                        <tbody>
                            <tr>
                                <th scope="row"><label>Selling Plugin</label></th>
                                <td>
                                    <select name="selling_plugin">
                                        <option value="">Choose Plugin</option>

                                        <?php foreach( $this->selling_plugins() as $key => $option ) : ?>
                                        <option value="<?php echo $key; ?>" <?php selected( $selling_plugin, $key ); ?> ><?php echo $option; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <h2>Configure My Account page on Appsero Dashboard</h2>
                    <p>Copy the link of Appsero/EDD/WooCommerce My Account page and update it on your Appsero Dashboard. Go to your <strong>Product > Email > Email Branding</strong> and paste the my account page link their.</p>
                    <p>You can create Appsero my account page using <code>[appsero_my_account]</code> shortcode.</p>
                    <br>

                    <div class="appsero-fastspring-fields <?php echo $selling_plugin === 'fastspring' ? '' : 'display-none'; ?>">
                        <h2>Configure Redirect After Successful Purchase</h2>
                        <?php $appsero = get_option( 'appsero_general_settings', [] ); ?>
                        <p>
                            <label>
                                <input type="checkbox" name="redirect_purchases" <?php checked( true, ! empty( $appsero['redirect_purchases'] ) ) ?> />
                                Enable users to redirect to a Thank You page after successful purchases
                            </label>
                        </p>
                        <table class="form-table redirect-purchases-fields <?php echo empty( $appsero['redirect_purchases'] ) ? 'display-none' : ''; ?>">
                            <tbody>
                                <tr>
                                    <th scope="row"><label>FastSpring Storefront Path</label></th>
                                    <td>
                                        <input name="fastspring_storefront_path" type="text" value="<?php echo empty($appsero['storefront_path']) ? '' : $appsero['storefront_path']; ?>" class="regular-text" placeholder="Enter the value of data-storefront">
                                        <p class="description">Enter the value of data-storefront, e.g. store.onfastspring.com/popup</p>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><label>Thank You Page</label></th>
                                    <td>
                                        <select name="thank_you_page">
                                            <option value="">Select Thank You Page</option>

                                            <?php
                                                $thankyou_page = empty( $appsero['thank_you_page'] ) ? '' : $appsero['thank_you_page'];
                                                foreach( get_pages() as $page ) :
                                            ?>
                                            <option value="<?php echo $page->ID; ?>" <?php selected( $thankyou_page, $page->ID ); ?> ><?php echo $page->post_title; ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <p class="description">After the order has been completed, the user will be redirected to this page.</p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <br>
                    </div>

                    <div class="appsero-fastspring-fields appsero-paddle-fields <?php echo $this->isAffiliatable($selling_plugin) ? '' : 'display-none'; ?>">
                        <h2>Configure Affiliates with <?php $this->showSellingPluginName(); ?> sales</h2>
                        <?php $affiliate = get_option( 'appsero_affiliate_wp_settings', '' ); ?>
                        <p>
                            <label>
                                <input type="checkbox" name="enable_affiliates" <?php checked( true, ! empty( $affiliate['enable_affiliates'] ) ) ?> />
                                Enable Affiliates with <?php $this->showSellingPluginName(); ?> sales
                            </label>
                        </p>

                        <?php if ( ! class_exists( 'Affiliate_WP' ) ): ?>
                            <p>You do not have AffiliateWP plugin installed. <a href="https://affiliatewp.com/" target="_blank">Install AffiliateWP</a> to enable affiliation from your FastSpring sales.</p>
                        <?php endif; ?>

                        <table class="form-table affiliate-wp-fields <?php echo empty( $affiliate['enable_affiliates'] ) ? 'display-none' : ''; ?>">
                            <tbody>
                            <tr>
                                <th scope="row"><label>Affiliate Area Page</label></th>
                                <td>
                                    <select name="affiliate_area_page">
                                        <option value="">Select Affiliate Area Page</option>

                                        <?php
                                        $affiliate_page = empty( $affiliate['affiliate_area_page'] ) ? '' : $affiliate['affiliate_area_page'];
                                        foreach( get_pages() as $page ) :
                                            ?>
                                            <option value="<?php echo $page->ID; ?>" <?php selected( $affiliate_page, $page->ID ); ?> ><?php echo $page->post_title; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <p class="description">Select the same page that is selected as Affiliate Area page of AffiliateWP plugin.</p>
                                </td>
                            </tr>
                            <tr class="appsero-fastspring-fields <?php echo $selling_plugin === 'fastspring' ? '' : 'display-none'; ?>">
                                <th scope="row"><label>FastSpring API Username</label></th>
                                <td>
                                    <input name="fastspring_username" type="text" value="<?php echo empty($affiliate['fastspring_username']) ? '' : $affiliate['fastspring_username']; ?>" class="regular-text" placeholder="Enter FastSpring API username">
                                    <p class="description">Enter FastSpring API username from your API credentials</p>
                                </td>
                            </tr>
                            <tr class="appsero-fastspring-fields <?php echo $selling_plugin === 'fastspring' ? '' : 'display-none'; ?>">
                                <th scope="row"><label>FastSpring API Password</label></th>
                                <td>
                                    <input name="fastspring_password" type="password" value="<?php echo empty($affiliate['fastspring_password']) ? '' : $affiliate['fastspring_password']; ?>" class="regular-text" placeholder="Enter FastSpring API password">
                                    <p class="description">Enter FastSpring API password from your API credentials</p>
                                </td>
                            </tr>
                            <tr class="appsero-paddle-fields <?php echo $selling_plugin === 'paddle' ? '' : 'display-none'; ?>">
                                <th scope="row"><label>Paddle Vendor ID</label></th>
                                <td>
                                    <input name="paddle_vendor_id" type="text" value="<?php echo empty($affiliate['paddle_vendor_id']) ? '' : $affiliate['paddle_vendor_id']; ?>" class="regular-text" placeholder="Enter Paddle Vendor ID">
                                    <p class="description">Enter Paddle Vendor ID from your API credentials</p>
                                </td>
                            </tr>
                            <tr class="appsero-paddle-fields <?php echo $selling_plugin === 'paddle' ? '' : 'display-none'; ?>">
                                <th scope="row"><label>Paddle Vendor Auth Code</label></th>
                                <td>
                                    <input name="paddle_vendor_auth_code" type="password" value="<?php echo empty($affiliate['paddle_vendor_auth_code']) ? '' : $affiliate['paddle_vendor_auth_code']; ?>" class="regular-text" placeholder="Paddle Vendor Auth Code">
                                    <p class="description">Enter Paddle Vendor Auth Code from your API credentials</p>
                                </td>
                            </tr>
                            <tr class="appsero-paddle-fields <?php echo $selling_plugin === 'paddle' ? '' : 'display-none'; ?>">
                                <th scope="row"><label>Paddle Connection Mode</label></th>
                                <td>
                                    <select name="paddle_sandbox" class="regular-text">
                                        <option value="0" <?php echo empty($affiliate['paddle_sandbox']) ? 'selected' : ''; ?> >Live/Production</option>
                                        <option value="1" <?php echo empty($affiliate['paddle_sandbox']) ? '' : 'selected'; ?> >Test/Sandbox</option>
                                    </select>
                                    <p class="description">Select Paddle Connection Mode.</p>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </div>

                    <h2>Documentation & Support</h2>
                    <p>Check <a href="https://appsero.com/docs/" target="_blank">Appsero Docs</a> to explore all the features of Appsero with in-depth tutorials. If you need any help, email to support@appsero.com</p>

                    <?php submit_button( 'Save Settings', 'primary', 'settings_submit' ); ?>
                </form>

            </div>
        </div>

        <?php
        if( $this->is_local() ) {
            $this->showLocalSiteError();
        }
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

    /**
     * Save settings field
     */
    private function save_settings_fields( $post ) {
        if ( isset( $post['selling_plugin'] ) ) {
            update_option( 'appsero_selling_plugin', sanitize_text_field( $post['selling_plugin'] ) );
        }

        if ( isset( $post['thank_you_page'] ) ) {
            $fastspring = [
                'storefront_path'    => sanitize_text_field( $post['fastspring_storefront_path'] ),
                'thank_you_page'     => sanitize_text_field( $post['thank_you_page'] ),
                'redirect_purchases' => ! empty( $post['redirect_purchases'] ),
            ];

            update_option( 'appsero_general_settings', $fastspring );
        }

        if ( $post['selling_plugin'] == 'fastspring' && isset( $post['fastspring_username'], $post['fastspring_password'] ) ) {
            $userinfo = [
                'fastspring_username' => sanitize_text_field( $post['fastspring_username'] ),
                'fastspring_password' => sanitize_text_field( $post['fastspring_password'] ),
                'enable_affiliates'   => ! empty( $post['enable_affiliates'] ),
                'affiliate_area_page' => sanitize_text_field( $post['affiliate_area_page'] ),
            ];

            update_option( 'appsero_affiliate_wp_settings', $userinfo, false );
        }

        if ( $post['selling_plugin'] == 'paddle' && isset( $post['paddle_vendor_id'], $post['paddle_vendor_auth_code'] ) ) {
            $userinfo = [
                'paddle_vendor_id' => sanitize_text_field( $post['paddle_vendor_id'] ),
                'paddle_vendor_auth_code' => sanitize_text_field( $post['paddle_vendor_auth_code'] ),
                'paddle_sandbox' => ! empty( $post['paddle_sandbox'] ),
                'enable_affiliates'   => ! empty( $post['enable_affiliates'] ),
                'affiliate_area_page' => sanitize_text_field( $post['affiliate_area_page'] ),
            ];

            update_option( 'appsero_affiliate_wp_settings', $userinfo, false );
        }

        $this->success = "Saved Successfully.";
    }

}
