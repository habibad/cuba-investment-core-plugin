<?php
/**
 * Cuba Investment Core - Messaging REST Controller
 *
 * @package CubaInvestment\Core
 */

namespace CubaInvestment\Core\API\Controllers;

use CubaInvestment\Core\Common\Constants;
use CubaInvestment\Core\Services\MessagingService;
use CubaInvestment\Core\Auth\Permissions;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class MessagingController {

    public static function register_routes() {
        register_rest_route( Constants::API_NAMESPACE, '/conversations', [
            [
                'methods'             => 'GET',
                'callback'            => [ __CLASS__, 'get_conversations' ],
                'permission_callback' => 'is_user_logged_in',
            ],
        ] );

        register_rest_route( Constants::API_NAMESPACE, '/conversations/unread-count', [
            [
                'methods'             => 'GET',
                'callback'            => [ __CLASS__, 'get_total_unread_count' ],
                'permission_callback' => 'is_user_logged_in',
            ],
        ] );

        register_rest_route( Constants::API_NAMESPACE, '/conversations/(?P<id>\d+)/messages', [
            [
                'methods'             => 'GET',
                'callback'            => [ __CLASS__, 'get_messages' ],
                'permission_callback' => 'is_user_logged_in',
            ],
            [
                'methods'             => 'POST',
                'callback'            => [ __CLASS__, 'send_message' ],
                'permission_callback' => 'is_user_logged_in',
            ],
        ] );
    }

    public static function get_total_unread_count() {
        global $wpdb;
        $user_id = get_current_user_id();
        $t_msg   = Constants::get_table_name( Constants::TABLE_MESSAGES );
        $count   = (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$t_msg} WHERE recipient_user_id = %d AND is_read = 0",
            $user_id
        ) );

        return rest_ensure_response( [
            'success'      => true,
            'unread_count' => $count,
        ] );
    }

    public static function get_conversations() {
        global $wpdb;

        $user_id = get_current_user_id();
        $table   = Constants::get_table_name( Constants::TABLE_CONVERSATIONS );
        $t_msg   = Constants::get_table_name( Constants::TABLE_MESSAGES );
        $t_conn  = Constants::get_table_name( Constants::TABLE_CONNECTIONS );

        $convos = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$table}
                 WHERE (participant_one_id = %d OR participant_two_id = %d)
                   AND status = %s
                 ORDER BY last_message_at DESC",
                $user_id,
                $user_id,
                Constants::CONVERSATION_ACTIVE
            )
        );

        $results = [];
        if ( ! empty( $convos ) ) {
            foreach ( $convos as $c ) {
                $partner_id = ( (int) $c->participant_one_id === (int) $user_id ) ? (int) $c->participant_two_id : (int) $c->participant_one_id;
                $partner    = get_userdata( $partner_id );
                $part_meta  = get_user_meta( $partner_id );

                $first_n = $part_meta['first_name'][0] ?? ( $partner ? $partner->first_name : '' );
                $last_n  = $part_meta['last_name'][0] ?? ( $partner ? $partner->last_name : '' );
                $full_n  = trim( "{$first_n} {$last_n}" ) ?: ( $partner ? $partner->display_name : __( 'Member', 'cuba-investment-core' ) );

                // Compute initials
                $initials = strtoupper( substr( $first_n ?: ( $partner ? $partner->user_login : 'U' ), 0, 1 ) . substr( $last_n ?: '', 0, 1 ) );
                if ( empty( $initials ) ) {
                    $initials = 'CIN';
                }

                // Unread count for current user in this convo
                $unread = (int) $wpdb->get_var( $wpdb->prepare(
                    "SELECT COUNT(*) FROM {$t_msg} WHERE conversation_id = %d AND recipient_user_id = %d AND is_read = 0",
                    $c->id,
                    $user_id
                ) );

                // Last message snippet
                $last_msg = $wpdb->get_row( $wpdb->prepare(
                    "SELECT message_body, sender_user_id, created_at FROM {$t_msg} WHERE conversation_id = %d ORDER BY created_at DESC LIMIT 1",
                    $c->id
                ) );

                // Related connection & opportunity
                $opp_title = '';
                if ( ! empty( $c->connection_id ) ) {
                    $opp_id = $wpdb->get_var( $wpdb->prepare(
                        "SELECT opportunity_id FROM {$t_conn} WHERE id = %d",
                        $c->connection_id
                    ) );
                    if ( $opp_id ) {
                        $opp = get_post( $opp_id );
                        if ( $opp ) {
                            $opp_title = get_the_title( $opp );
                        }
                    }
                }

                $results[] = [
                    'id'                   => (int) $c->id,
                    'connection_id'        => (int) $c->connection_id,
                    'partner_id'           => $partner_id,
                    'partner_name'         => $full_n,
                    'partner_initials'     => $initials,
                    'partner_avatar'       => $part_meta['_cin_avatar_url'][0] ?? '',
                    'unread_count'         => $unread,
                    'opportunity_title'    => $opp_title,
                    'last_message_preview' => $last_msg ? wp_trim_words( $last_msg->message_body, 12, '...' ) : __( 'No messages yet', 'cuba-investment-core' ),
                    'last_message_at'      => $c->last_message_at,
                    'last_message_time'    => $last_msg ? human_time_diff( strtotime( $last_msg->created_at ), current_time( 'timestamp' ) ) . ' ago' : '',
                    'status'               => $c->status,
                ];
            }
        }

        return rest_ensure_response( [
            'success'       => true,
            'count'         => count( $results ),
            'conversations' => $results,
        ] );
    }

    public static function get_messages( \WP_REST_Request $request ) {
        $user_id  = get_current_user_id();
        $convo_id = (int) $request['id'];

        if ( ! Permissions::can_access_conversation( $user_id, $convo_id ) ) {
            return new \WP_Error( 'unauthorized', __( 'Access denied to this conversation.', 'cuba-investment-core' ), [ 'status' => 403 ] );
        }

        // Mark unread messages as read
        MessagingService::mark_read( $convo_id, $user_id );

        $raw_messages = MessagingService::get_messages( $convo_id );
        $formatted    = [];

        foreach ( $raw_messages as $m ) {
            $is_mine = ( (int) $m['sender_user_id'] === (int) $user_id );
            $sender  = get_userdata( $m['sender_user_id'] );

            $formatted[] = [
                'id'             => (int) $m['id'],
                'sender_user_id' => (int) $m['sender_user_id'],
                'sender_name'    => $sender ? $sender->display_name : __( 'Member', 'cuba-investment-core' ),
                'is_mine'        => $is_mine,
                'message_body'   => esc_html( $m['message_body'] ),
                'is_read'        => (bool) $m['is_read'],
                'created_at'     => $m['created_at'],
                'formatted_time' => date_i18n( 'g:i A', strtotime( $m['created_at'] ) ),
                'formatted_date' => date_i18n( 'M j, Y', strtotime( $m['created_at'] ) ),
            ];
        }

        return rest_ensure_response( [
            'success'  => true,
            'count'    => count( $formatted ),
            'messages' => $formatted,
        ] );
    }

    public static function send_message( \WP_REST_Request $request ) {
        global $wpdb;

        $user_id  = get_current_user_id();
        $convo_id = (int) $request['id'];

        if ( ! Permissions::can_access_conversation( $user_id, $convo_id ) ) {
            return new \WP_Error( 'unauthorized', __( 'Access denied to this conversation.', 'cuba-investment-core' ), [ 'status' => 403 ] );
        }

        $table = Constants::get_table_name( Constants::TABLE_CONVERSATIONS );
        $convo = $wpdb->get_row(
            $wpdb->prepare( "SELECT participant_one_id, participant_two_id FROM {$table} WHERE id = %d", $convo_id )
        );

        $recipient_id = ( (int) $convo->participant_one_id === (int) $user_id ) ? (int) $convo->participant_two_id : (int) $convo->participant_one_id;

        $params = $request->get_json_params() ?: $request->get_body_params();
        $body   = isset( $params['message'] ) ? (string) $params['message'] : '';

        $message_id = MessagingService::send_message( $convo_id, $user_id, $recipient_id, $body );

        if ( is_wp_error( $message_id ) ) {
            return $message_id;
        }

        $now = current_time( 'mysql' );
        return rest_ensure_response( [
            'success'    => true,
            'message_id' => $message_id,
            'message'    => [
                'id'             => $message_id,
                'sender_user_id' => $user_id,
                'is_mine'        => true,
                'message_body'   => esc_html( $body ),
                'created_at'     => $now,
                'formatted_time' => date_i18n( 'g:i A', strtotime( $now ) ),
                'formatted_date' => date_i18n( 'M j, Y', strtotime( $now ) ),
            ],
        ] );
    }
}
