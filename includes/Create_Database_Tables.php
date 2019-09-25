<?php
namespace Appsero\Helper;

class Create_Database_Tables {

    /**
     * Constructor
     */
    public function __construct() {

        // $this->create_licenses_table();
    }

    /**
     * Create the licenses table
     */
    private function create_licenses_table() {
        global $wpdb;

        $table_name = $wpdb->prefix . 'appsero_licenses';

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS `{$table_name}` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `product_id` BIGINT(20) NULL DEFAULT NULL,
            `variation_id` BIGINT(20) NULL DEFAULT NULL,
            `order_id` BIGINT(20) NULL DEFAULT NULL,
            `user_id` BIGINT(20) NOT NULL,
            `key` VARCHAR(255) NOT NULL,
            `status` TINYINT(1) NULL DEFAULT '1',
            `activation_limit` SMALLINT(5) NULL DEFAULT '0',
            `expire_date` DATETIME NULL DEFAULT NULL,
            `activations` LONGTEXT NULL DEFAULT NULL,
            `source_id` BIGINT(20) NOT NULL,
            `store_type` VARCHAR(50) NOT NULL,
            `meta` TEXT NULL DEFAULT NULL,
            PRIMARY KEY (`id`)
        ) {$charset_collate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        dbDelta( $sql );
    }

}
