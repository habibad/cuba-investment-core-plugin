<?php
/**
 * Cuba Investment Core - Connection REST Controller
 *
 * @package CubaInvestment\Core
 */

namespace CubaInvestment\Core\API\Controllers;

use CubaInvestment\Core\Common\Constants;
use CubaInvestment\Core\Services\ConnectionService;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class ConnectionController {

    public static function register_routes() {
        register_rest_route( Constants::API_NAMESPACE, '/connections', [
            [
                'methods'             => 'GET',
                'callback'            => [ __CLASS__, 'get_items' ],
                'permission_callback' => 'is_user_logged_in',
            ],
        ] );
    }

    public static function get_items() {
        $user_id = get_current_user_id();
        $connections = ConnectionService::get_connections_for_user( $user_id );

        return rest_ensure_response( [
            'success'     => true,
            'count'       => count( $connections ),
            'connections' => $connections,
        ] );
    }
}
