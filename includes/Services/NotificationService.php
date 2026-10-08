<?php
/**
 * Cuba Investment Core - Notification Service
 *
 * @package CubaInvestment\Core
 */

namespace CubaInvestment\Core\Services;

use CubaInvestment\Core\Common\Constants;
use CubaInvestment\Core\Common\Logger;
use CubaInvestment\Core\Models\Notification;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class NotificationService {

    /**
     * Send in-app notification and optional email
     *
     * @param int    $user_id Recipient user ID
     * @param string $type Notification type key
     * @param string $title Short title
     * @param string $content Detailed body
     * @param string $action_url Link URL
     * @param bool   $send_email Whether to also trigger wp_mail
     * @return int|\WP_Error Notification ID or error
     */
    public static function send( $user_id, $type, $title, $content, $action_url = '', $send_email = false ) {
        global $wpdb;

        $user = get_userdata( $user_id );
        if ( ! $user ) {
            return new \WP_Error( 'invalid_user', __( 'Recipient user does not exist.', 'cuba-investment-core' ) );
        }

        $table = Constants::get_table_name( Constants::TABLE_NOTIFICATIONS );

        $inserted = $wpdb->insert(
            $table,
            [
                'user_id'    => (int) $user_id,
                'type'       => sanitize_key( $type ),
                'title'      => sanitize_text_field( $title ),
                'content'    => sanitize_textarea_field( $content ),
                'action_url' => esc_url_raw( $action_url ),
                'is_read'    => 0,
                'created_at' => current_time( 'mysql' ),
            ],
            [ '%d', '%s', '%s', '%s', '%s', '%d', '%s' ]
        );

        if ( ! $inserted ) {
            Logger::error( 'Failed to write notification to database', [ 'user_id' => $user_id, 'type' => $type ] );
            return new \WP_Error( 'db_error', __( 'Could not send notification.', 'cuba-investment-core' ) );
        }

        $notification_id = $wpdb->insert_id;

        // Send email if requested
        if ( $send_email && is_email( $user->user_email ) ) {
            $site_name = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );
            $subject   = sprintf( '[%s] %s', $site_name, $title );
            $message   = sprintf(
                "Hello %s,\n\n%s\n\n%s\n\nBest regards,\nCuba Investment Network",
                $user->display_name,
                $content,
                ! empty( $action_url ) ? "View details: " . esc_url( $action_url ) : ''
            );

            wp_mail( $user->user_email, $subject, $message );
        }

        return $notification_id;
    }

    /**
     * Get user notifications
     *
     * @param int $user_id
     * @param int $limit
     * @return array
     */
    public static function get_for_user( $user_id, $limit = 20 ) {
        global $wpdb;

        $table = Constants::get_table_name( Constants::TABLE_NOTIFICATIONS );

        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$table} WHERE user_id = %d ORDER BY created_at DESC LIMIT %d",
                $user_id,
                $limit
            )
        );

        $results = [];
        if ( ! empty( $rows ) ) {
            foreach ( $rows as $row ) {
                $item = new Notification( $row );
                $results[] = $item->to_array();
            }
        }

        return $results;
    }

    /**
     * Get unread notifications count
     *
     * @param int $user_id
     * @return int
     */
    public static function get_unread_count( $user_id ) {
        global $wpdb;

        $table = Constants::get_table_name( Constants::TABLE_NOTIFICATIONS );

        $count = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$table} WHERE user_id = %d AND is_read = 0",
                $user_id
            )
        );

        return (int) $count;
    }

    /**
     * Mark notification as read
     *
     * @param int $notification_id
     * @param int $user_id
     * @return bool
     */
    public static function mark_as_read( $notification_id, $user_id ) {
        global $wpdb;

        $table = Constants::get_table_name( Constants::TABLE_NOTIFICATIONS );

        $updated = $wpdb->update(
            $table,
            [
                'is_read' => 1,
                'read_at' => current_time( 'mysql' ),
            ],
            [
                'id'      => (int) $notification_id,
                'user_id' => (int) $user_id,
            ],
            [ '%d', '%s' ],
            [ '%d', '%d' ]
        );

        return false !== $updated;
    }
}
