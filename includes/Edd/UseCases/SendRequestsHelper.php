<?php
namespace Appsero\Helper\Edd\UseCases;

trait SendRequestsHelper {

    /**
     * Get license of an order
     */
    private function get_order_licenses( $payment_id, $download_id ) {
        if ( ! class_exists( 'EDD_SL_Download' ) ) return [];

        $purchased_download = new \EDD_SL_Download( $download_id );
        if ( ! $purchased_download->is_bundled_download() && ! $purchased_download->licensing_enabled() ) {
            return [];
        }

        $licenses = edd_software_licensing()->get_licenses_of_purchase( $payment_id );

        $items = [];

        if ( false !== $licenses ) {
            require_once ASHP_ROOT_PATH . 'includes/Edd/Licenses.php';

            $licensesObject = new \Appsero\Helper\Edd\Licenses();

            foreach( $licenses as $license ) {
                $items[] = $licensesObject->get_license_data( $license, true );
            }
        }

        return $items;
    }

}
