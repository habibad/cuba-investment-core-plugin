<?php
/**
 * Cuba Investment Core - Opportunity REST Controller
 *
 * @package CubaInvestment\Core
 */

namespace CubaInvestment\Core\API\Controllers;

use CubaInvestment\Core\Common\Constants;
use CubaInvestment\Core\Services\OpportunityService;
use CubaInvestment\Core\Services\EntitlementService;
use CubaInvestment\Core\Auth\Permissions;
use CubaInvestment\Core\Models\Opportunity;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class OpportunityController {

    public static function register_routes() {
        register_rest_route( Constants::API_NAMESPACE, '/opportunities', [
            [
                'methods'             => 'GET',
                'callback'            => [ __CLASS__, 'get_items' ],
                'permission_callback' => '__return_true', // Public discovery
            ],
            [
                'methods'             => 'POST',
                'callback'            => [ __CLASS__, 'create_item' ],
                'permission_callback' => [ __CLASS__, 'can_create_opportunity' ],
            ],
        ] );

        register_rest_route( Constants::API_NAMESPACE, '/opportunities/(?P<id>\d+)', [
            [
                'methods'             => 'GET',
                'callback'            => [ __CLASS__, 'get_item' ],
                'permission_callback' => '__return_true',
            ],
        ] );

        register_rest_route( Constants::API_NAMESPACE, '/opportunities/(?P<id>\d+)/review', [
            [
                'methods'             => 'POST',
                'callback'            => [ __CLASS__, 'review_item' ],
                'permission_callback' => [ __CLASS__, 'can_review_opportunities' ],
            ],
        ] );
    }

    public static function can_create_opportunity() {
        $user_id = get_current_user_id();
        if ( ! $user_id ) {
            return false;
        }

        return user_can( $user_id, Constants::CAP_SUBMIT_OPPORTUNITY ) || Permissions::is_admin_or_reviewer( $user_id );
    }

    public static function can_review_opportunities() {
        return Permissions::is_admin_or_reviewer();
    }

    public static function get_items( \WP_REST_Request $request ) {
        $params = $request->get_params();

        $args = [
            'sector'   => isset( $params['sector'] ) ? sanitize_key( $params['sector'] ) : '',
            'province' => isset( $params['province'] ) ? sanitize_key( $params['province'] ) : '',
            'stage'    => isset( $params['stage'] ) ? sanitize_key( $params['stage'] ) : '',
            'search'   => isset( $params['search'] ) ? sanitize_text_field( $params['search'] ) : '',
            'limit'    => isset( $params['limit'] ) ? absint( $params['limit'] ) : 12,
            'page'     => isset( $params['page'] ) ? absint( $params['page'] ) : 1,
            'status'   => 'publish',
        ];

        // If author requests own listings, allow seeing pending/draft
        if ( ! empty( $params['author'] ) && get_current_user_id() === (int) $params['author'] ) {
            $args['author'] = (int) $params['author'];
            $args['status'] = [ 'publish', 'pending', 'draft' ];
        }

        $items = OpportunityService::get_opportunities( $args );

        return rest_ensure_response( [
            'success'       => true,
            'count'         => count( $items ),
            'opportunities' => $items,
        ] );
    }

    public static function get_item( \WP_REST_Request $request ) {
        $id   = (int) $request['id'];
        $post = get_post( $id );

        if ( ! $post || $post->post_type !== Constants::POST_TYPE_OPPORTUNITY ) {
            return new \WP_Error( 'not_found', __( 'Opportunity not found.', 'cuba-investment-core' ), [ 'status' => 404 ] );
        }

        $opp = new Opportunity( $post );

        return rest_ensure_response( [
            'success'     => true,
            'opportunity' => $opp->to_array(),
        ] );
    }

    public static function create_item( \WP_REST_Request $request ) {
        $user_id = get_current_user_id();

        // Entitlement check
        if ( ! EntitlementService::can_perform( $user_id, 'submit_opportunity' ) ) {
            return new \WP_Error( 'quota_exceeded', __( 'Listing quota limit reached for current free tier.', 'cuba-investment-core' ), [ 'status' => 403 ] );
        }

        $params  = $request->get_json_params() ?: $request->get_body_params();
        $post_id = OpportunityService::submit_opportunity( $user_id, $params );

        if ( is_wp_error( $post_id ) ) {
            return $post_id;
        }

        $opp = new Opportunity( $post_id );

        return rest_ensure_response( [
            'success'     => true,
            'message'     => __( 'Business listing submitted for initial review.', 'cuba-investment-core' ),
            'opportunity' => $opp->to_array(),
        ] );
    }

    public static function review_item( \WP_REST_Request $request ) {
        $id     = (int) $request['id'];
        $params = $request->get_json_params() ?: $request->get_body_params();
        $action = isset( $params['action'] ) ? sanitize_key( $params['action'] ) : '';
        $notes  = isset( $params['notes'] ) ? sanitize_textarea_field( $params['notes'] ) : '';

        if ( 'approve' === $action ) {
            $res = OpportunityService::approve_listing( $id );
        } elseif ( 'revision' === $action ) {
            $res = OpportunityService::request_revisions( $id, $notes );
        } else {
            return new \WP_Error( 'invalid_action', __( 'Review action must be approve or revision.', 'cuba-investment-core' ), [ 'status' => 400 ] );
        }

        if ( is_wp_error( $res ) ) {
            return $res;
        }

        return rest_ensure_response( [
            'success' => true,
            'message' => __( 'Listing review status updated successfully.', 'cuba-investment-core' ),
        ] );
    }
}
