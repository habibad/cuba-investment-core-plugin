<?php
/**
 * Cuba Investment Core - Opportunity REST Controller
 *
 * @package CubaInvestment\Core
 */

namespace CubaInvestment\Core\API\Controllers;

use CubaInvestment\Core\Common\Constants;
use CubaInvestment\Core\Services\OpportunityService;
use CubaInvestment\Core\Services\SavedOpportunityService;
use CubaInvestment\Core\Services\EntitlementService;
use CubaInvestment\Core\Auth\Permissions;
use CubaInvestment\Core\Models\Opportunity;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class OpportunityController {

    public static function register_routes() {
        // Saved Opportunities routes
        register_rest_route( Constants::API_NAMESPACE, '/opportunities/saved', [
            [
                'methods'             => 'GET',
                'callback'            => [ __CLASS__, 'get_saved_opportunities' ],
                'permission_callback' => 'is_user_logged_in',
            ],
        ] );

        register_rest_route( Constants::API_NAMESPACE, '/opportunities/(?P<id>\d+)/save', [
            [
                'methods'             => 'POST',
                'callback'            => [ __CLASS__, 'save_opportunity' ],
                'permission_callback' => 'is_user_logged_in',
            ],
            [
                'methods'             => 'DELETE',
                'callback'            => [ __CLASS__, 'unsave_opportunity' ],
                'permission_callback' => 'is_user_logged_in',
            ],
            [
                'methods'             => 'GET',
                'callback'            => [ __CLASS__, 'check_saved_opportunity' ],
                'permission_callback' => 'is_user_logged_in',
            ],
        ] );

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

        // Owner's listings route
        register_rest_route( Constants::API_NAMESPACE, '/opportunities/my', [
            [
                'methods'             => 'GET',
                'callback'            => [ __CLASS__, 'get_my_opportunities' ],
                'permission_callback' => [ __CLASS__, 'can_manage_opportunities' ],
            ],
        ] );

        // Draft save & autosave
        register_rest_route( Constants::API_NAMESPACE, '/opportunities/draft', [
            [
                'methods'             => 'POST',
                'callback'            => [ __CLASS__, 'save_draft' ],
                'permission_callback' => [ __CLASS__, 'can_manage_opportunities' ],
            ],
        ] );

        register_rest_route( Constants::API_NAMESPACE, '/opportunities/(?P<id>\d+)', [
            [
                'methods'             => 'GET',
                'callback'            => [ __CLASS__, 'get_item' ],
                'permission_callback' => '__return_true',
            ],
            [
                'methods'             => 'DELETE',
                'callback'            => [ __CLASS__, 'delete_opportunity' ],
                'permission_callback' => [ __CLASS__, 'can_manage_opportunities' ],
            ],
        ] );

        // Submit for review
        register_rest_route( Constants::API_NAMESPACE, '/opportunities/(?P<id>\d+)/submit', [
            [
                'methods'             => 'POST',
                'callback'            => [ __CLASS__, 'submit_opportunity' ],
                'permission_callback' => [ __CLASS__, 'can_manage_opportunities' ],
            ],
        ] );

        // Document upload, delete, and download
        register_rest_route( Constants::API_NAMESPACE, '/opportunities/(?P<id>\d+)/documents', [
            [
                'methods'             => 'POST',
                'callback'            => [ __CLASS__, 'upload_document' ],
                'permission_callback' => [ __CLASS__, 'can_manage_opportunities' ],
            ],
        ] );

        register_rest_route( Constants::API_NAMESPACE, '/opportunities/(?P<id>\d+)/documents/(?P<doc_id>[a-zA-Z0-9_-]+)', [
            [
                'methods'             => 'DELETE',
                'callback'            => [ __CLASS__, 'delete_document' ],
                'permission_callback' => [ __CLASS__, 'can_manage_opportunities' ],
            ],
            [
                'methods'             => 'GET',
                'callback'            => [ __CLASS__, 'download_document' ],
                'permission_callback' => [ __CLASS__, 'can_download_document' ],
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

    public static function can_manage_opportunities() {
        $user_id = get_current_user_id();
        if ( ! $user_id ) {
            return false;
        }

        if ( ! Permissions::is_account_active( $user_id ) ) {
            return false;
        }

        return Permissions::is_business_owner( $user_id ) || Permissions::is_admin_or_reviewer( $user_id );
    }

    public static function can_download_document( \WP_REST_Request $request ) {
        $user_id = get_current_user_id();
        if ( ! $user_id ) {
            return false;
        }

        $opp_id = (int) $request['id'];
        return \CubaInvestment\Core\Security\DocumentAccess::user_can_download( $user_id, $opp_id );
    }

    public static function get_my_opportunities( \WP_REST_Request $request ) {
        $user_id = get_current_user_id();
        $params  = $request->get_params();

        $args = [
            'status' => isset( $params['status'] ) ? sanitize_key( $params['status'] ) : 'all',
            'search' => isset( $params['search'] ) ? sanitize_text_field( $params['search'] ) : '',
            'page'   => isset( $params['page'] ) ? absint( $params['page'] ) : 1,
            'limit'  => isset( $params['limit'] ) ? absint( $params['limit'] ) : 10,
        ];

        $data = OpportunityService::get_owner_opportunities( $user_id, $args );

        return rest_ensure_response( [
            'success' => true,
            'data'    => $data,
        ] );
    }

    public static function save_draft( \WP_REST_Request $request ) {
        $user_id = get_current_user_id();
        $params  = $request->get_json_params() ?: $request->get_body_params();

        $opportunity_id = isset( $params['opportunity_id'] ) ? absint( $params['opportunity_id'] ) : ( isset( $params['id'] ) ? absint( $params['id'] ) : 0 );

        $post_id = OpportunityService::save_draft( $user_id, $params, $opportunity_id );

        if ( is_wp_error( $post_id ) ) {
            return $post_id;
        }

        $opp = new Opportunity( $post_id );

        return rest_ensure_response( [
            'success'        => true,
            'opportunity_id' => $post_id,
            'message'        => __( 'Draft saved successfully.', 'cuba-investment-core' ),
            'opportunity'    => $opp->to_array(),
            'saved_at'       => current_time( 'mysql' ),
        ] );
    }

    public static function submit_opportunity( \WP_REST_Request $request ) {
        $user_id        = get_current_user_id();
        $opportunity_id = (int) $request['id'];
        $params         = $request->get_json_params() ?: $request->get_body_params();

        $result = OpportunityService::submit_for_review( $user_id, $opportunity_id, $params );

        if ( is_wp_error( $result ) ) {
            return $result;
        }

        $opp = new Opportunity( $opportunity_id );

        return rest_ensure_response( [
            'success'     => true,
            'message'     => __( 'Opportunity Submitted Successfully. Your opportunity has been submitted for review. You can monitor its status from My Opportunities.', 'cuba-investment-core' ),
            'opportunity' => $opp->to_array(),
        ] );
    }

    public static function delete_opportunity( \WP_REST_Request $request ) {
        $user_id        = get_current_user_id();
        $opportunity_id = (int) $request['id'];

        $result = OpportunityService::delete_draft( $user_id, $opportunity_id );

        if ( is_wp_error( $result ) ) {
            return $result;
        }

        return rest_ensure_response( [
            'success' => true,
            'message' => __( 'Draft opportunity removed successfully.', 'cuba-investment-core' ),
        ] );
    }

    public static function upload_document( \WP_REST_Request $request ) {
        $user_id        = get_current_user_id();
        $params         = $request->get_params();
        $opportunity_id = isset( $params['id'] ) ? absint( $params['id'] ) : ( isset( $params['opportunity_id'] ) ? absint( $params['opportunity_id'] ) : 0 );

        if ( ! $opportunity_id ) {
            return new \WP_Error( 'invalid_id', __( 'Opportunity ID is required for document upload.', 'cuba-investment-core' ), [ 'status' => 400 ] );
        }

        $files = $request->get_file_params();
        $file  = $files['document'] ?? $files['file'] ?? null;

        if ( ! $file ) {
            return new \WP_Error( 'no_file', __( 'No file provided for upload.', 'cuba-investment-core' ), [ 'status' => 400 ] );
        }

        $doc = OpportunityService::upload_document( $user_id, $opportunity_id, $file );

        if ( is_wp_error( $doc ) ) {
            return $doc;
        }

        return rest_ensure_response( [
            'success'  => true,
            'document' => $doc,
            'message'  => __( 'Document uploaded securely.', 'cuba-investment-core' ),
        ] );
    }

    public static function delete_document( \WP_REST_Request $request ) {
        $user_id        = get_current_user_id();
        $opportunity_id = (int) $request['id'];
        $doc_id         = sanitize_text_field( $request['doc_id'] );

        $result = OpportunityService::delete_document( $user_id, $opportunity_id, $doc_id );

        if ( is_wp_error( $result ) ) {
            return $result;
        }

        return rest_ensure_response( [
            'success' => true,
            'message' => __( 'Document removed successfully.', 'cuba-investment-core' ),
        ] );
    }

    public static function download_document( \WP_REST_Request $request ) {
        $user_id        = get_current_user_id();
        $opportunity_id = (int) $request['id'];
        $doc_id         = sanitize_text_field( $request['doc_id'] );

        OpportunityService::stream_document( $user_id, $opportunity_id, $doc_id );
        exit;
    }

    /**
     * Get investor's saved opportunities
     */
    public static function get_saved_opportunities( \WP_REST_Request $request ) {
        $user_id = get_current_user_id();
        $limit   = absint( $request->get_param( 'limit' ) ) ?: 20;
        $offset  = absint( $request->get_param( 'offset' ) ) ?: 0;

        $items = SavedOpportunityService::get_saved( $user_id, $limit, $offset );
        $count = SavedOpportunityService::count( $user_id );

        return rest_ensure_response( [
            'success' => true,
            'count'   => $count,
            'items'   => $items,
        ] );
    }

    /**
     * Save an opportunity
     */
    public static function save_opportunity( \WP_REST_Request $request ) {
        $user_id = get_current_user_id();
        $opp_id  = (int) $request['id'];

        $result = SavedOpportunityService::save( $user_id, $opp_id );

        if ( is_wp_error( $result ) ) {
            return $result;
        }

        return rest_ensure_response( [
            'success' => true,
            'message' => __( 'Opportunity saved to your portfolio.', 'cuba-investment-core' ),
            'is_saved'=> true,
            'saved'   => true,
            'count'   => SavedOpportunityService::count( $user_id ),
        ] );
    }

    /**
     * Remove saved opportunity
     */
    public static function unsave_opportunity( \WP_REST_Request $request ) {
        $user_id = get_current_user_id();
        $opp_id  = (int) $request['id'];

        $result = SavedOpportunityService::remove( $user_id, $opp_id );

        if ( is_wp_error( $result ) ) {
            return $result;
        }

        return rest_ensure_response( [
            'success' => true,
            'message' => __( 'Opportunity removed from your saved list.', 'cuba-investment-core' ),
            'is_saved'=> false,
            'saved'   => false,
            'count'   => SavedOpportunityService::count( $user_id ),
        ] );
    }

    /**
     * Check if opportunity is saved
     */
    public static function check_saved_opportunity( \WP_REST_Request $request ) {
        $user_id = get_current_user_id();
        $opp_id  = (int) $request['id'];

        $is_saved = SavedOpportunityService::is_saved( $user_id, $opp_id );

        return rest_ensure_response( [
            'success' => true,
            'is_saved'=> $is_saved,
        ] );
    }
}
