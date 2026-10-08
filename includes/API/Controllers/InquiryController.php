<?php
/**
 * Cuba Investment Core - Inquiry REST Controller
 *
 * @package CubaInvestment\Core
 */

namespace CubaInvestment\Core\API\Controllers;

use CubaInvestment\Core\Common\Constants;
use CubaInvestment\Core\Services\InquiryService;
use CubaInvestment\Core\Services\EntitlementService;
use CubaInvestment\Core\Auth\Permissions;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class InquiryController {

    public static function register_routes() {
        register_rest_route( Constants::API_NAMESPACE, '/inquiries', [
            [
                'methods'             => 'GET',
                'callback'            => [ __CLASS__, 'get_items' ],
                'permission_callback' => 'is_user_logged_in',
            ],
            [
                'methods'             => 'POST',
                'callback'            => [ __CLASS__, 'create_item' ],
                'permission_callback' => [ __CLASS__, 'can_send_inquiry' ],
            ],
        ] );

        register_rest_route( Constants::API_NAMESPACE, '/inquiries/(?P<id>\d+)/respond', [
            [
                'methods'             => 'POST',
                'callback'            => [ __CLASS__, 'respond_item' ],
                'permission_callback' => 'is_user_logged_in',
            ],
        ] );
    }

    public static function can_send_inquiry() {
        $user_id = get_current_user_id();
        return $user_id && ( user_can( $user_id, Constants::CAP_SEND_INQUIRIES ) || Permissions::is_admin_or_reviewer( $user_id ) );
    }

    public static function get_items() {
        $user_id = get_current_user_id();
        $user    = get_userdata( $user_id );
        $role    = in_array( Constants::ROLE_BUSINESS_OWNER, (array) $user->roles, true ) ? 'business_owner' : 'investor';

        $inquiries = InquiryService::get_for_user( $user_id, $role );

        return rest_ensure_response( [
            'success'   => true,
            'role'      => $role,
            'count'     => count( $inquiries ),
            'inquiries' => $inquiries,
        ] );
    }

    public static function create_item( \WP_REST_Request $request ) {
        $user_id = get_current_user_id();

        // Check entitlement
        if ( ! EntitlementService::can_perform( $user_id, 'send_inquiry' ) ) {
            return new \WP_Error( 'quota_exceeded', __( 'Inquiry monthly limit reached under free launch tier.', 'cuba-investment-core' ), [ 'status' => 403 ] );
        }

        $params = $request->get_json_params() ?: $request->get_body_params();
        $opp_id = isset( $params['opportunity_id'] ) ? absint( $params['opportunity_id'] ) : 0;

        if ( ! $opp_id ) {
            return new \WP_Error( 'missing_opportunity', __( 'Opportunity ID is required.', 'cuba-investment-core' ), [ 'status' => 400 ] );
        }

        $result = InquiryService::create_inquiry( $user_id, $opp_id, $params );

        if ( is_wp_error( $result ) ) {
            return $result;
        }

        return rest_ensure_response( [
            'success'    => true,
            'message'    => __( 'Your introduction inquiry has been submitted directly to the business owner.', 'cuba-investment-core' ),
            'inquiry_id' => $result,
        ] );
    }

    public static function respond_item( \WP_REST_Request $request ) {
        $user_id    = get_current_user_id();
        $inquiry_id = (int) $request['id'];
        $params     = $request->get_json_params() ?: $request->get_body_params();
        $action     = isset( $params['action'] ) ? sanitize_key( $params['action'] ) : '';
        $note       = isset( $params['note'] ) ? sanitize_textarea_field( $params['note'] ) : '';

        if ( ! in_array( $action, [ 'accept', 'decline' ], true ) ) {
            return new \WP_Error( 'invalid_action', __( 'Action must be accept or decline.', 'cuba-investment-core' ), [ 'status' => 400 ] );
        }

        $result = InquiryService::respond( $inquiry_id, $user_id, $action, $note );

        if ( is_wp_error( $result ) ) {
            return $result;
        }

        return rest_ensure_response( [
            'success' => true,
            'message' => ( 'accept' === $action )
                ? __( 'Inquiry accepted. Direct connection and messaging initialized.', 'cuba-investment-core' )
                : __( 'Inquiry declined.', 'cuba-investment-core' ),
        ] );
    }
}
