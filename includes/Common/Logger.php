<?php
/**
 * Cuba Investment Core - Structured Logging & Audit Trail
 *
 * @package CubaInvestment\Core
 */

namespace CubaInvestment\Core\Common;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Logger {

    const LEVEL_DEBUG   = 'DEBUG';
    const LEVEL_INFO    = 'INFO';
    const LEVEL_WARNING = 'WARNING';
    const LEVEL_ERROR   = 'ERROR';
    const LEVEL_AUDIT   = 'AUDIT';

    /**
     * Log message with context
     *
     * @param string $level Log level
     * @param string $message Main message
     * @param array  $context Context data
     */
    public static function log( $level, $message, array $context = [] ) {
        $sanitized_context = self::redact_sensitive_data( $context );

        $log_entry = sprintf(
            '[CIN %s] [%s] %s %s',
            gmdate( 'Y-m-d H:i:s' ),
            strtoupper( $level ),
            $message,
            ! empty( $sanitized_context ) ? wp_json_encode( $sanitized_context ) : ''
        );

        // Always log errors and warnings to PHP error log if WP_DEBUG is enabled
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            error_log( $log_entry ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
        }

        // Write audit level entries to database audit table
        if ( self::LEVEL_AUDIT === $level || self::LEVEL_ERROR === $level ) {
            self::write_to_audit_table( $level, $message, $sanitized_context );
        }
    }

    public static function debug( $message, array $context = [] ) {
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            self::log( self::LEVEL_DEBUG, $message, $context );
        }
    }

    public static function info( $message, array $context = [] ) {
        self::log( self::LEVEL_INFO, $message, $context );
    }

    public static function warning( $message, array $context = [] ) {
        self::log( self::LEVEL_WARNING, $message, $context );
    }

    public static function error( $message, array $context = [] ) {
        self::log( self::LEVEL_ERROR, $message, $context );
    }

    public static function audit( $action, $message, array $context = [] ) {
        $context['action'] = sanitize_key( $action );
        self::log( self::LEVEL_AUDIT, $message, $context );
    }

    /**
     * Redact sensitive fields (passwords, tokens, cookies, auth headers)
     *
     * @param array $data Input data array
     * @return array
     */
    protected static function redact_sensitive_data( array $data ) {
        $sensitive_keys = [
            'password', 'pass', 'pwd', 'secret', 'token', 'access_token',
            'refresh_token', 'api_key', 'auth_key', 'cookie', 'credit_card',
            'card_number', 'cvv', 'pin', 'ssn', 'tax_id'
        ];

        $sanitized = [];
        foreach ( $data as $key => $value ) {
            $key_lower = strtolower( (string) $key );

            $is_sensitive = false;
            foreach ( $sensitive_keys as $s_key ) {
                if ( false !== strpos( $key_lower, $s_key ) ) {
                    $is_sensitive = true;
                    break;
                }
            }

            if ( $is_sensitive ) {
                $sanitized[ $key ] = '***REDACTED***';
            } elseif ( is_array( $value ) ) {
                $sanitized[ $key ] = self::redact_sensitive_data( $value );
            } else {
                $sanitized[ $key ] = $value;
            }
        }

        return $sanitized;
    }

    /**
     * Write audit record to DB table if table exists
     *
     * @param string $level Log level
     * @param string $message Message
     * @param array  $context Context
     */
    protected static function write_to_audit_table( $level, $message, array $context ) {
        global $wpdb;

        $table_name = Constants::get_table_name( Constants::TABLE_AUDIT_LOGS );

        // Avoid recursion if table doesn't exist
        static $table_exists = null;
        if ( null === $table_exists ) {
            $table_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) ) === $table_name;
        }

        if ( ! $table_exists ) {
            return;
        }

        $user_id    = ! empty( $context['user_id'] ) ? absint( $context['user_id'] ) : get_current_user_id();
        $ip_address = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '0.0.0.0';
        $user_agent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';
        $action     = isset( $context['action'] ) ? sanitize_text_field( $context['action'] ) : 'general';

        $wpdb->insert(
            $table_name,
            [
                'user_id'    => $user_id,
                'level'      => sanitize_text_field( $level ),
                'action'     => $action,
                'message'    => sanitize_text_field( $message ),
                'context'    => wp_json_encode( $context ),
                'ip_address' => $ip_address,
                'user_agent' => substr( $user_agent, 0, 255 ),
                'created_at' => current_time( 'mysql' ),
            ],
            [ '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s' ]
        );
    }
}
