<?php

class WDDP_DatabaseSetup {

    const WDDP_DATABASE_NAME = "wddp_dogpension";
    public static function install() {
        global $wpdb;

        $table_name = $wpdb->prefix . self::WDDP_DATABASE_NAME;
        $charset_collate = $wpdb->get_charset_collate();

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $sql = "
        CREATE TABLE {$table_name} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            order_id BIGINT(20) DEFAULT NULL,
            status VARCHAR(50) NOT NULL DEFAULT 'pending_customer',
            first_name VARCHAR(100),
            last_name VARCHAR(100),
            email VARCHAR(255),
            phone VARCHAR(50),
            address TEXT,
            postal_code VARCHAR(20),
            city VARCHAR(100),
            dropoff_date DATE,
            pickup_date DATE,
            dropoff_time VARCHAR(50),
            pickup_time VARCHAR(50),
            dog_names LONGTEXT,
            dog_data LONGTEXT,
            price DECIMAL(10,2),
            notes TEXT,
            rejection_reason TEXT,
            change_log LONGTEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) {$charset_collate};
        ";

        dbDelta( $sql );
    }
}
