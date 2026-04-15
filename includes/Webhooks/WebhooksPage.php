<?php
namespace Appsero\Helper\Webhooks;

class WebhooksPage {

    /**
     * @var Repository
     */
    private $repository;

    /**
     * @var string
     */
    private $message = '';

    /**
     * @var string
     */
    private $message_type = '';

    /**
     * Available webhook events
     */
    private static $available_events = [
        'user.created' => 'User Created',
    ];

    public function __construct() {
        $this->repository = new Repository();
    }

    /**
     * Handle form submissions (add/delete)
     */
    public function handle_post() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        // Handle delete
        if ( isset( $_GET['action'] ) && $_GET['action'] === 'delete_webhook' && isset( $_GET['webhook_id'] ) ) {
            if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( $_GET['_wpnonce'], 'delete_webhook_' . $_GET['webhook_id'] ) ) {
                return;
            }

            $this->repository->delete( sanitize_text_field( $_GET['webhook_id'] ) );
            $this->message      = 'Webhook deleted.';
            $this->message_type = 'success';

            return;
        }

        // Handle add
        if ( ! isset( $_POST['appsero_webhook_submit'] ) ) {
            return;
        }

        if ( ! isset( $_POST['appsero_webhook_nonce'] ) || ! wp_verify_nonce( $_POST['appsero_webhook_nonce'], 'appsero_add_webhook' ) ) {
            $this->message      = 'Unauthorized request.';
            $this->message_type = 'error';

            return;
        }

        $name   = isset( $_POST['webhook_name'] ) ? sanitize_text_field( $_POST['webhook_name'] ) : '';
        $url    = isset( $_POST['webhook_url'] ) ? esc_url_raw( $_POST['webhook_url'] ) : '';
        $secret = isset( $_POST['webhook_secret'] ) ? sanitize_text_field( $_POST['webhook_secret'] ) : '';
        $events = isset( $_POST['webhook_events'] ) ? array_map( 'sanitize_text_field', (array) $_POST['webhook_events'] ) : [];

        if ( empty( $name ) || empty( $url ) || empty( $events ) ) {
            $this->message      = 'Name, URL, and at least one event are required.';
            $this->message_type = 'error';

            return;
        }

        $this->repository->create( [
            'name'   => $name,
            'url'    => $url,
            'secret' => $secret,
            'events' => $events,
        ] );

        $this->message      = 'Webhook added.';
        $this->message_type = 'success';
    }

    /**
     * Render the webhooks section
     */
    public function render() {
        $webhooks = $this->repository->get_all();
        $page_url = admin_url( 'options-general.php?page=appsero_helper' );
        ?>

        <div class="appsero-widget" style="margin-top: 20px;">
            <h2>Webhooks</h2>
            <p>Configure URLs to receive HTTP POST notifications when events occur.</p>

            <?php if ( ! empty( $this->message ) ) : ?>
            <div class="notice notice-<?php echo esc_attr( $this->message_type ); ?> is-dismissible" style="max-width: 852px;">
                <p><?php echo esc_html( $this->message ); ?></p>
            </div>
            <?php endif; ?>

            <?php if ( ! empty( $webhooks ) ) : ?>
            <table class="widefat striped" style="max-width: 852px; margin-bottom: 20px;">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>URL</th>
                        <th>Events</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $webhooks as $webhook ) :
                        $delete_url = wp_nonce_url(
                            add_query_arg( [
                                'action'     => 'delete_webhook',
                                'webhook_id' => $webhook['id'],
                            ], $page_url ),
                            'delete_webhook_' . $webhook['id']
                        );
                    ?>
                    <tr>
                        <td><?php echo esc_html( $webhook['name'] ); ?></td>
                        <td><code><?php echo esc_html( $webhook['url'] ); ?></code></td>
                        <td><?php echo esc_html( implode( ', ', $webhook['events'] ) ); ?></td>
                        <td><?php echo $webhook['status'] ? 'Active' : 'Inactive'; ?></td>
                        <td>
                            <a href="<?php echo esc_url( $delete_url ); ?>"
                               onclick="return confirm('Delete this webhook?');"
                               style="color: #a00;">Delete</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>

            <h3>Add Webhook</h3>
            <form method="post" autocomplete="off">
                <table class="form-table">
                    <tbody>
                        <tr>
                            <th scope="row"><label for="webhook_name">Name</label></th>
                            <td>
                                <input type="text" id="webhook_name" name="webhook_name" class="regular-text" placeholder="e.g. Notify Slack" required />
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="webhook_url">URL</label></th>
                            <td>
                                <input type="url" id="webhook_url" name="webhook_url" class="regular-text" placeholder="https://example.com/webhook" required />
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="webhook_secret">Secret</label></th>
                            <td>
                                <input type="text" id="webhook_secret" name="webhook_secret" class="regular-text" placeholder="Optional HMAC secret" />
                                <p class="description">Used to sign payloads with HMAC-SHA256. Sent as <code>X-Appsero-Signature</code> header.</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Events</th>
                            <td>
                                <?php foreach ( self::$available_events as $key => $label ) : ?>
                                <label style="display: block; margin-bottom: 5px;">
                                    <input type="checkbox" name="webhook_events[]" value="<?php echo esc_attr( $key ); ?>" />
                                    <?php echo esc_html( $label ); ?>
                                </label>
                                <?php endforeach; ?>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <?php wp_nonce_field( 'appsero_add_webhook', 'appsero_webhook_nonce' ); ?>
                <?php submit_button( 'Add Webhook', 'secondary', 'appsero_webhook_submit' ); ?>
            </form>
        </div>
        <?php
    }
}
