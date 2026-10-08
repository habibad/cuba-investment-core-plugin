<?php
/**
 * Cuba Investment Core - Entitlement REST Controller
 *
 * @package CubaInvestment\Core
 */

namespace CubaInvestment\Core\API\Controllers;

use CubaInvestment\Core\Common\Constants;
use CubaInvestment\Core\Services\EntitlementService;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class EntitlementController {

    public static function register_routes() {
        register_rest_route( Constants::API_NAMESPACE, '/entitlements', [
            [
                'methods'             => 'GET',
                'callback'            => [ __CLASS__, 'get_entitlements' ],
                'permission_callback' => 'is_user_logged_in',
            ],
        ] );
    }

    public static function get_entitlements() {
        $user_id      = get_current_user_id();
        $tier         = EntitlementService::get_tier( $user_id );
        $entitlements = EntitlementService::get_entitlements( $user_id );

        $data = [
            'tier'         => $tier,
            'is_free'      => true,
            'entitlements' => $entitlements,
            'usage'        => [
                'submit_opportunity' => EntitlementService::get_usage( $user_id, 'submit_opportunity' ),
                'send_inquiry'       => EntitlementService::get_usage( $user_id, 'send_inquiry' ),
            ],
        ];

        return rest_ensure_response( [
            'success' => true,
            'data'    => $data,
        ] );
    }
}
