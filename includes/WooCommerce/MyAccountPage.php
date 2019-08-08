<?php
namespace Appsero\Helper\WooCommerce;

class MyAccountPage {

    /**
     * Constructor of EDD MyAccountPage class
     */
    public function __construct() {

        // IF Woo SA and Woo API not installed then show our page
        if ( ! class_exists( 'WC_Software' ) && ! class_exists( 'WooCommerce_API_Manager' ) ) {
            add_filter( 'woocommerce_account_menu_items', [ $this, 'account_menu_items' ] );

            add_action( 'init', [ $this, 'custom_endpoints' ] );

            add_filter( 'query_vars', [ $this, 'custom_query_vars' ] );

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
        wp_enqueue_style( 'ashp-my-account' );
        wp_enqueue_script( 'ashp-my-account' );
        ?>
        <div class="appsero-licenses">

            <?php
                foreach ( $this->get_licenses() as $license ):

                $product = wc_get_product( $license['product_id'] );

                list( $expires_on, $activations ) = $this->getActivationsAndExpires( $license );
            ?>
            <div class="appsero-license" data-showing="0"
                data-sourceid="<?php echo $license['source_id']; ?>"
                data-productid="<?php echo $license['product_id']; ?>"
                data-licenseid="<?php echo $license['id']; ?>"
            >
                <div class="license-header">
                    <div class="license-product-info">
                        <div class="license-product-title">
                            <h2><?php echo $product->get_name(); ?></h2>
                            <p class="h3">Variation</p>
                        </div>
                        <div class="license-product-expire">
                            <h4>Expires On</h4>
                            <p class="h3"><?php echo $expires_on; ?></p>
                        </div>
                        <div class="license-product-activation">
                            <h4>Activations Remaining</h4>
                            <p class="h3"><?php echo $license['activation_limit'] - count( $activations ); ?></p>
                        </div>
                    </div>
                    <div class="license-toggle-info">
                        <i class="fas fa-angle-down"></i>
                    </div>
                </div>
                <div class="license-key-activations">
                    <div class="appsero-license-key">
                        <p><strong>Key</strong> <span class="license-key-code"><?php echo $license['key']; ?></span></p>
                    </div>
                    <div class="appsero-activations">
                        <h4>Activations</h4>

                        <?php foreach ( $activations as $activation ) : ?>
                        <div class="appsero-activation-item">
                            <span><?php echo $activation['site_url']; ?></span>
                            <a href="#" data-activationid="<?php echo $activation['id']; ?>" class="remove-activation-button">Remove</a>
                        </div>
                        <?php endforeach; ?>

                    </div>
                </div>
            </div>
            <?php endforeach; ?>

        </div>
        <?php
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
     * Licenses of an user
     */
    private function get_licenses() {
        $user_id = get_current_user_id();

        $order_ids = wc_get_orders( [
            'customer' => get_current_user_id(),
            'return'   => 'ids',
            'paginate' => false,
            'limit'    => -1,
        ] );

        // First try to get licenses from WP table
        $licenses = $this->get_stored_licenses( $user_id, $order_ids );

        // If no data found then get from appsero API
        if ( empty( $licenses ) ) {
            $response = $this->get_appsero_licenses( $user_id, $order_ids );
        }

        return $licenses;
    }

    /**
     * Get licenses from WP database
     */
    private function get_stored_licenses( $user_id, $order_ids ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'appsero_licenses';
        $sql = "
            SELECT * FROM {$table_name}
            WHERE `user_id` = {$user_id}
            AND `order_id` IN ( " . implode( $order_ids, ',' ) . " )
        ";
        return $wpdb->get_results( $sql, ARRAY_A );
    }

    /**
     * Get license from appsero API
     */
    private function get_appsero_licenses( $user_id, $order_ids ) {
        $query = http_build_query( [ 'orders_id' => $order_ids ] );

        $route = 'public/users/' . $user_id . '/licenses?' . $query;

        // Send request to appsero server
        $response = appsero_helper_remote_get( $route );

        if ( wp_remote_retrieve_response_code( $response ) != 200 ) {
            return [];
        }

        $response = json_decode( wp_remote_retrieve_body( $response ), true );

        // Store licenses
        if ( isset( $response['data'] ) && ! empty( $response['data'] ) ) {
            $this->store_appsero_licenses( $response['data'] );

            // Get newly stored licenses
            return $this->get_stored_licenses( $user_id, $order_ids );
        }

        return [];
    }

    /**
     * Store licenses that are received from appsero
     */
    private function store_appsero_licenses( $licenses ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'appsero_licenses';

        foreach ( $licenses as $license ) {
            $wpdb->insert( $table_name, [
                'product_id'       => $license['product_id'],
                'variation_id'     => $license['variation_id'],
                'order_id'         => $license['order_id'],
                'user_id'          => $license['user_id'],
                'key'              => $license['key'],
                'status'           => $license['status'],
                'activation_limit' => $license['activation_limit'],
                'expire_date'      => $license['expire_date'],
                'activations'      => json_encode( $license['activations'] ),
                'source_id'        => $license['source_id'],
            ] );
        }
    }

    /**
     * Prpare activation and expires
     */
    private function getActivationsAndExpires( $license ) {
        if ( empty( $license['expire_date'] ) ) {
            $expires_on = 'Unlimited';
        } else {
            $date_time = \DateTime::createFromFormat( 'Y-m-d H:i:s', $license['expire_date'] );
            $expires_on = $date_time->format( 'M jS, Y' );
        }

        if ( is_array( $license['activations'] ) ) {
            $activations = $license['activations'];
        } else {
            $activations = json_decode( $license['activations'], true );
            $activations = ( ! is_array( $activations ) ) ? [] : $activations;
        }

        return [ $expires_on, $activations ];
    }

}
