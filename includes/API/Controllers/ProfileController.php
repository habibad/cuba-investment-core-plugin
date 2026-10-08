<?php
/**
 * Cuba Investment Core - Profile Controller
 *
 * @package CubaInvestment\Core
 */

namespace CubaInvestment\Core\API\Controllers;

use CubaInvestment\Core\Common\Constants;
use CubaInvestment\Core\Services\ProfileService;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class ProfileController {

    public static function register_routes() {
        register_rest_route( Constants::API_NAMESPACE, '/profile', [
            [
                'methods'             => 'GET',
                'callback'            => [ __CLASS__, 'get_profile' ],
                'permission_callback' => 'is_user_logged_in',
            ],
            [
                'methods'             => 'POST',
                'callback'            => [ __CLASS__, 'update_profile' ],
                'permission_callback' => 'is_user_logged_in',
            ],
        ] );
    }

    public static function get_profile() {
        $user_id = get_current_user_id();
        $profile = ProfileService::get_profile( $user_id );

        return rest_ensure_response( [
            'success' => true,
            'profile' => $profile,
        ] );
    }

    public static function update_profile( \WP_REST_Request $request ) {
        $user_id = get_current_user_id();
        $params  = $request->get_json_params() ?: $request->get_body_params();

        $updated = ProfileService::update_profile( $user_id, $params );

        if ( is_wp_error( $updated ) ) {
            return $updated;
        }

        return rest_ensure_response( [
            'success' => true,
            'message' => __( 'Profile updated successfully.', 'cuba-investment-core' ),
            'profile' => ProfileService::get_profile( $user_id ),
        ] );
    }
}
