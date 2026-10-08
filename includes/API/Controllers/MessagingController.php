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

    public static function get_conversations() {
        global $wpdb;

        $user_id = get_current_user_id();
        $table   = Constants::get_table_name( Constants::TABLE_CONVERSATIONS );

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
                $partner = get_userdata( $partner_id );

                $results[] = [
                    'id'              => (int) $c->id,
                    'connection_id'   => (int) $c->connection_id,
                    'partner_id'      => $partner_id,
                    'partner_name'    => $partner ? $partner->display_name : __( 'Member', 'cuba-investment-core' ),
                    'last_message_at' => $c->last_message_at,
                    'status'          => $c->status,
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

        $messages = MessagingService::get_messages( $convo_id );

        return rest_ensure_response( [
            'success'  => true,
            'count'    => count( $messages ),
            'messages' => $messages,
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

        return rest_ensure_response( [
            'success'    => true,
            'message_id' => $message_id,
        ] );
    }
}
