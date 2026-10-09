<?php
/**
 * Cuba Investment Core - Database Migrations Coordinator
 *
 * @package CubaInvestment\Core
 */

namespace CubaInvestment\Core\Database;

use CubaInvestment\Core\Common\Constants;
use CubaInvestment\Core\Common\Logger;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Migrations {

    /**
     * Run all migrations and create or upgrade tables
     *
     * @return bool True if successful
     */
    public static function run() {
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $queries = Schema::get_schema_queries();

        foreach ( $queries as $sql ) {
            dbDelta( $sql );
        }

        update_option( Constants::OPTION_DB_VERSION, Constants::DB_VERSION );

        if ( ! get_option( Constants::OPTION_INSTALLED_AT ) ) {
            update_option( Constants::OPTION_INSTALLED_AT, current_time( 'mysql' ) );
        }

        Logger::info( 'Database migrations executed successfully', [
            'version' => Constants::DB_VERSION,
        ] );

        return self::verify_tables();
    }

    /**
     * Verify that all required custom tables exist in the database
     *
     * @return bool
     */
    public static function verify_tables() {
        global $wpdb;

        $tables = [
            Constants::TABLE_INQUIRIES,
            Constants::TABLE_CONNECTIONS,
            Constants::TABLE_CONVERSATIONS,
            Constants::TABLE_MESSAGES,
            Constants::TABLE_NOTIFICATIONS,
            Constants::TABLE_SUBSCRIPTIONS,
            Constants::TABLE_AUDIT_LOGS,
            Constants::TABLE_SAVED_OPPORTUNITIES,
        ];

        $missing = [];

        foreach ( $tables as $suffix ) {
            $table_name = Constants::get_table_name( $suffix );
            $found = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) );
            if ( $found !== $table_name ) {
                $missing[] = $table_name;
            }
        }

        if ( ! empty( $missing ) ) {
            Logger::error( 'Missing custom database tables after migration', [
                'missing_tables' => $missing,
            ] );
            return false;
        }

        return true;
    }

    /**
     * Drop all custom tables (Used strictly during uninstall if requested)
     */
    public static function drop_tables() {
        global $wpdb;

        $tables = [
            Constants::TABLE_INQUIRIES,
            Constants::TABLE_CONNECTIONS,
            Constants::TABLE_CONVERSATIONS,
            Constants::TABLE_MESSAGES,
            Constants::TABLE_NOTIFICATIONS,
            Constants::TABLE_SUBSCRIPTIONS,
            Constants::TABLE_AUDIT_LOGS,
            Constants::TABLE_SAVED_OPPORTUNITIES,
        ];

        foreach ( $tables as $suffix ) {
            $table_name = Constants::get_table_name( $suffix );
            $wpdb->query( "DROP TABLE IF EXISTS {$table_name}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        }

        delete_option( Constants::OPTION_DB_VERSION );
        delete_option( Constants::OPTION_INSTALLED_AT );

        Logger::info( 'Custom database tables dropped during purge' );
    }
}
