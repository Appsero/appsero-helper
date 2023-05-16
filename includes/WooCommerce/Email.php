<?php
namespace Appsero\Helper\WooCommerce;

class Email {

    /**
     * Constructor
     */
    public function __construct() {
        add_action( 'woocommerce_email_after_order_table', [ $this, 'show_license_and_download' ], 9, 4 );
    }

    /**
     * Show license and download
     */
    public function show_license_and_download( $order, $sent_to_admin, $plain_text, $email ) {

        if ( $sent_to_admin || ! $order || $email->id !== 'customer_completed_order' )
            return '';

        $licenses = [];
        if (! did_action('woocommerce_order_status_changed')) {
            $request = new SendRequests();
            $request->order_status_changed( $order->get_id(), '', '', $order);
        }

        foreach ( $order->get_items( 'line_item' ) as $item_id => $item ) {
            $key = '_appsero_order_license_for_product_' . $item->get_product_id();

            $license = get_post_meta( $order->get_id(), $key, true );

            if ( ( ! isset( $license['send_license'] ) || $license['send_license'] ) && isset( $license['status'] ) ) {
                $license['item_name'] = $item->get_name();
                $licenses[] = $license;
            }
        }

        if( ! count( $licenses ) )
            return '';

        $text_align  = is_rtl() ? 'right' : 'left';
        $margin_side = is_rtl() ? 'left' : 'right';

        ?>
        <div style="margin-bottom: 40px">
            <h2>License and Download</h2>
            <table  class="td" cellspacing="0" cellpadding="6" style="width: 100%; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif;" border="1">
                <thead>
                <tr>
                    <th class="td" scope="col" style="text-align:<?php echo esc_attr( $text_align ); ?>;">License Key</th>
                    <th>Expire Date</th>
                    <th>Download</th>
                </tr>
                </thead>
                <tbody>
                <?php
                foreach ( $licenses as $license ) :

                    ?>
                    <tr>
                        <td class="td" style="text-align:<?php echo esc_attr( $text_align ); ?>; vertical-align: middle; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif; word-wrap:break-word;">
                            <?php echo $license['key']; ?>
                        </td>
                        <td class="td" style="text-align:<?php echo esc_attr( $text_align ); ?>; vertical-align: middle; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif; word-wrap:break-word;">
                            <?php echo $license['expire_date'] ? date( 'M d, Y', strtotime( $license['expire_date'] ) ) : 'Lifetime'; ?>
                        </td>
                        <td class="td" style="text-align:<?php echo esc_attr( $text_align ); ?>; vertical-align: middle; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif; word-wrap:break-word;">
                            <a href="<?php echo $license['download_url']; ?>" class="button" title="<?php echo sanitize_title( $license['item_name'] ); ?>.zip">
                                Download
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

}
