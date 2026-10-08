<?php
/**
 * Cuba Investment Core - Notification REST Controller
 *
 * @package CubaInvestment\Core
 */

namespace CubaInvestment\Core\API\Controllers;

use CubaInvestment\Core\Common\Constants;
use CubaInvestment\Core\Services\NotificationService;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class NotificationController {

    public static function register_routes() {
        register_rest_route( Constants::API_NAMESPACE, '/notifications', [
            [
                'methods'             => 'GET',
                'callback'            => [ __CLASS__, 'get_items' ],
                'permission_callback' => 'is_user_logged_in',
            ],
        ] );

        register_rest_route( Constants::API_NAMESPACE, '/notifications/(?P<id>\d+)/read', [
            [
                'methods'             => 'POST',
                'callback'            => [ __CLASS__, 'mark_read' ],
                'permission_callback' => 'is_user_logged_in',
            ],
        ] );
    }

    public static function get_items() {
        $user_id       = get_current_user_id();
        $notifications = NotificationService::get_for_user( $user_id );
        $unread_count  = NotificationService::get_unread_count( $user_id );

        return rest_ensure_response( [
            'success'       => true,
            'unread_count'  => $unread_count,
            'notifications' => $notifications,
        ] );
    }

    public static function mark_read( \WP_REST_Request $request ) {
        $user_id = get_current_user_id();
        $id      = (int) $request['id'];

        NotificationService::mark_as_read( $id, $user_id );

        return rest_ensure_response( [
            'success'      => true,
            'unread_count' => NotificationService::get_unread_count( $user_id ),
        ] );
    }
}
