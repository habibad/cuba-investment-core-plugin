<?php
/**
 * Cuba Investment Core - Private Messaging Service
 *
 * @package CubaInvestment\Core
 */

namespace CubaInvestment\Core\Services;

use CubaInvestment\Core\Auth\Permissions;
use CubaInvestment\Core\Common\Constants;
use CubaInvestment\Core\Common\Logger;
use CubaInvestment\Core\Models\Message;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class MessagingService {

    /**
     * Get or create a conversation for a connection
     *
     * @param int $connection_id
     * @param int $user_a
     * @param int $user_b
     * @return int Conversation ID
     */
    public static function get_or_create_conversation( $connection_id, $user_a, $user_b ) {
        global $wpdb;

        $table = Constants::get_table_name( Constants::TABLE_CONVERSATIONS );

        $existing = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM {$table}
                 WHERE connection_id = %d
                    OR ((participant_one_id = %d AND participant_two_id = %d)
                     OR (participant_one_id = %d AND participant_two_id = %d))",
                $connection_id,
                $user_a, $user_b,
                $user_b, $user_a
            )
        );

        if ( $existing ) {
            return (int) $existing;
        }

        $now = current_time( 'mysql' );

        $wpdb->insert(
            $table,
            [
                'connection_id'      => (int) $connection_id,
                'participant_one_id' => (int) $user_a,
                'participant_two_id' => (int) $user_b,
                'status'             => Constants::CONVERSATION_ACTIVE,
                'last_message_at'    => $now,
                'created_at'         => $now,
            ],
            [ '%d', '%d', '%d', '%s', '%s', '%s' ]
        );

        return (int) $wpdb->insert_id;
    }

    /**
     * Send a private message
     *
     * @param int $conversation_id
     * @param int $sender_id
     * @param int $recipient_id
     * @param string $message_body
     * @return int|\WP_Error Message ID or error
     */
    public static function send_message( $conversation_id, $sender_id, $recipient_id, $message_body ) {
        global $wpdb;

        $clean_body = sanitize_textarea_field( trim( $message_body ) );
        if ( empty( $clean_body ) ) {
            return new \WP_Error( 'empty_message', __( 'Message body cannot be empty.', 'cuba-investment-core' ), [ 'status' => 400 ] );
        }

        // Verify sender and recipient accounts are active
        if ( ! Permissions::is_account_active( $sender_id ) ) {
            return new \WP_Error( 'account_inactive', __( 'Your account is suspended or inactive.', 'cuba-investment-core' ), [ 'status' => 403 ] );
        }
        if ( ! Permissions::is_account_active( $recipient_id ) ) {
            return new \WP_Error( 'recipient_inactive', __( 'The recipient account is suspended or inactive.', 'cuba-investment-core' ), [ 'status' => 403 ] );
        }

        // Verify connection exists and is active
        if ( ! Permissions::are_connected( $sender_id, $recipient_id ) ) {
            return new \WP_Error( 'not_connected', __( 'You can only message active connections.', 'cuba-investment-core' ), [ 'status' => 403 ] );
        }

        $t_messages = Constants::get_table_name( Constants::TABLE_MESSAGES );
        $t_convos   = Constants::get_table_name( Constants::TABLE_CONVERSATIONS );

        // Verify conversation exists and sender/recipient belong to it
        $convo = $wpdb->get_row(
            $wpdb->prepare( "SELECT * FROM {$t_convos} WHERE id = %d", (int) $conversation_id )
        );
        if ( ! $convo ) {
            return new \WP_Error( 'not_found', __( 'Conversation not found.', 'cuba-investment-core' ), [ 'status' => 404 ] );
        }
        $is_p1 = ( (int) $convo->participant_one_id === (int) $sender_id && (int) $convo->participant_two_id === (int) $recipient_id );
        $is_p2 = ( (int) $convo->participant_two_id === (int) $sender_id && (int) $convo->participant_one_id === (int) $recipient_id );
        if ( ! $is_p1 && ! $is_p2 ) {
            return new \WP_Error( 'forbidden', __( 'You are not an authorized participant in this conversation.', 'cuba-investment-core' ), [ 'status' => 403 ] );
        }

        $now = current_time( 'mysql' );

        $inserted = $wpdb->insert(
            $t_messages,
            [
                'conversation_id'   => (int) $conversation_id,
                'sender_user_id'    => (int) $sender_id,
                'recipient_user_id' => (int) $recipient_id,
                'message_body'      => $clean_body,
                'is_read'           => 0,
                'created_at'        => $now,
            ],
            [ '%d', '%d', '%d', '%s', '%d', '%s' ]
        );

        if ( ! $inserted ) {
            return new \WP_Error( 'db_error', __( 'Failed to dispatch message.', 'cuba-investment-core' ) );
        }

        $message_id = $wpdb->insert_id;

        // Update conversation last_message_at
        $wpdb->update(
            $t_convos,
            [ 'last_message_at' => $now ],
            [ 'id' => (int) $conversation_id ],
            [ '%s' ],
            [ '%d' ]
        );

        // Notify recipient
        $sender = get_userdata( $sender_id );
        NotificationService::send(
            $recipient_id,
            'new_message',
            __( 'New Private Message', 'cuba-investment-core' ),
            sprintf( __( '%s sent you a direct message.', 'cuba-investment-core' ), $sender ? $sender->display_name : 'Your connection' ),
            home_url( '/dashboard/messages' )
        );

        return $message_id;
    }

    /**
     * Get messages in a conversation
     *
     * @param int $conversation_id
     * @param int $limit
     * @return array
     */
    public static function get_messages( $conversation_id, $limit = 50 ) {
        global $wpdb;

        $table = Constants::get_table_name( Constants::TABLE_MESSAGES );

        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$table} WHERE conversation_id = %d ORDER BY created_at ASC LIMIT %d",
                $conversation_id,
                $limit
            )
        );

        $results = [];
        if ( ! empty( $rows ) ) {
            foreach ( $rows as $row ) {
                $msg = new Message( $row );
                $results[] = $msg->to_array();
            }
        }

        return $results;
    }

    /**
     * Mark conversation messages as read for a recipient
     *
     * @param int $conversation_id
     * @param int $recipient_user_id
     * @return bool
     */
    public static function mark_read( $conversation_id, $recipient_user_id ) {
        global $wpdb;

        $table = Constants::get_table_name( Constants::TABLE_MESSAGES );

        $updated = $wpdb->update(
            $table,
            [
                'is_read' => 1,
                'read_at' => current_time( 'mysql' ),
            ],
            [
                'conversation_id'   => (int) $conversation_id,
                'recipient_user_id' => (int) $recipient_user_id,
                'is_read'           => 0,
            ],
            [ '%d', '%s' ],
            [ '%d', '%d', '%d' ]
        );

        return false !== $updated;
    }

    /**
     * Get total unread messages count for a user across all conversations
     *
     * @param int $user_id
     * @return int
     */
    public static function get_unread_count( $user_id ) {
        global $wpdb;

        $user_id = absint( $user_id );
        if ( ! $user_id ) {
            return 0;
        }

        $table = Constants::get_table_name( Constants::TABLE_MESSAGES );

        return (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$table} WHERE recipient_user_id = %d AND is_read = 0",
                $user_id
            )
        );
    }
}

