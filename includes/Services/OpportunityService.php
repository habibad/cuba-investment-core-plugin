<?php
/**
 * Cuba Investment Core - Opportunity Service
 *
 * @package CubaInvestment\Core
 */

namespace CubaInvestment\Core\Services;

use CubaInvestment\Core\Common\Constants;
use CubaInvestment\Core\Common\Logger;
use CubaInvestment\Core\Models\Opportunity;
use CubaInvestment\Core\Security\Sanitizer;
use CubaInvestment\Core\Security\DocumentAccess;
use CubaInvestment\Core\Auth\Permissions;
use CubaInvestment\Core\Services\EntitlementService;
use CubaInvestment\Core\Services\NotificationService;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class OpportunityService {

    /**
     * Query published/active opportunities
     *
     * @param array $args Filter parameters
     * @return array Array of formatted Opportunity arrays
     */
    public static function get_opportunities( array $args = [] ) {
        $tax_query = [];

        if ( ! empty( $args['sector'] ) && 'all' !== $args['sector'] ) {
            $tax_query[] = [
                'taxonomy' => Constants::TAX_SECTOR,
                'field'    => 'slug',
                'terms'    => sanitize_key( $args['sector'] ),
            ];
        }

        if ( ! empty( $args['province'] ) ) {
            $tax_query[] = [
                'taxonomy' => Constants::TAX_PROVINCE,
                'field'    => 'slug',
                'terms'    => sanitize_key( $args['province'] ),
            ];
        }

        if ( ! empty( $args['stage'] ) ) {
            $tax_query[] = [
                'taxonomy' => Constants::TAX_STAGE,
                'field'    => 'slug',
                'terms'    => sanitize_key( $args['stage'] ),
            ];
        }

        $query_args = [
            'post_type'      => Constants::POST_TYPE_OPPORTUNITY,
            'post_status'    => isset( $args['status'] ) ? $args['status'] : 'publish',
            'posts_per_page' => isset( $args['limit'] ) ? (int) $args['limit'] : 20,
            'paged'          => isset( $args['page'] ) ? (int) $args['page'] : 1,
            'orderby'        => 'date',
            'order'          => 'DESC',
        ];

        if ( ! empty( $args['search'] ) ) {
            $query_args['s'] = sanitize_text_field( $args['search'] );
        }

        if ( ! empty( $tax_query ) ) {
            $query_args['tax_query'] = $tax_query;
        }

        if ( ! empty( $args['author'] ) ) {
            $query_args['author'] = (int) $args['author'];
        }

        $query = new \WP_Query( $query_args );
        $results = [];

        if ( $query->have_posts() ) {
            foreach ( $query->posts as $post ) {
                $opp = new Opportunity( $post );
                $results[] = $opp->to_array();
            }
        }

        return $results;
    }

    /**
     * Submit a new business opportunity (Business Owner action)
     *
     * @param int $author_id
     * @param array $data
     * @return int|\WP_Error
     */
    public static function submit_opportunity( $author_id, array $data ) {
        $title       = isset( $data['title'] ) ? sanitize_text_field( $data['title'] ) : '';
        $description = isset( $data['description'] ) ? wp_kses_post( $data['description'] ) : '';
        $company     = isset( $data['company_name'] ) ? sanitize_text_field( $data['company_name'] ) : $title;

        if ( empty( $title ) || strlen( $title ) < 5 ) {
            return new \WP_Error( 'invalid_title', __( 'A descriptive business opportunity title is required.', 'cuba-investment-core' ), [ 'status' => 400 ] );
        }

        if ( empty( $description ) || strlen( $description ) < 20 ) {
            return new \WP_Error( 'invalid_description', __( 'Please provide a comprehensive description of the business and funding goals.', 'cuba-investment-core' ), [ 'status' => 400 ] );
        }

        // Create Post with 'pending' status for review
        $post_id = wp_insert_post( [
            'post_title'   => $title,
            'post_content' => $description,
            'post_excerpt' => isset( $data['excerpt'] ) ? sanitize_text_field( $data['excerpt'] ) : wp_trim_words( $description, 35 ),
            'post_status'  => 'pending',
            'post_type'    => Constants::POST_TYPE_OPPORTUNITY,
            'post_author'  => $author_id,
        ] );

        if ( is_wp_error( $post_id ) ) {
            Logger::error( 'Opportunity submission failed', [ 'author_id' => $author_id, 'error' => $post_id->get_error_message() ] );
            return $post_id;
        }

        // Meta Parameters
        update_post_meta( $post_id, '_cin_company_name', $company );
        update_post_meta( $post_id, '_cin_capital_sought', isset( $data['capital_sought'] ) ? Sanitizer::amount( $data['capital_sought'] ) : 0 );
        update_post_meta( $post_id, '_cin_minimum_investment', isset( $data['minimum_investment'] ) ? Sanitizer::amount( $data['minimum_investment'] ) : 0 );
        update_post_meta( $post_id, '_cin_currency', isset( $data['currency'] ) ? Sanitizer::currency( $data['currency'] ) : 'USD' );
        update_post_meta( $post_id, '_cin_ownership_structure', isset( $data['ownership_structure'] ) ? Sanitizer::legal_structure( $data['ownership_structure'] ) : 'mipyme_private' );
        update_post_meta( $post_id, '_cin_partnership_type', isset( $data['partnership_type'] ) ? sanitize_text_field( $data['partnership_type'] ) : '' );
        update_post_meta( $post_id, '_cin_operating_history', isset( $data['operating_history'] ) ? sanitize_text_field( $data['operating_history'] ) : '' );
        update_post_meta( $post_id, '_cin_capital_purpose', isset( $data['capital_purpose'] ) ? sanitize_textarea_field( $data['capital_purpose'] ) : '' );
        update_post_meta( $post_id, '_cin_info_source', 'Information supplied by the business owner' );
        update_post_meta( $post_id, '_cin_review_status', Constants::STATUS_PENDING_REVIEW );

        if ( ! empty( $data['highlights'] ) && is_array( $data['highlights'] ) ) {
            update_post_meta( $post_id, '_cin_highlights', Sanitizer::highlights( $data['highlights'] ) );
        }

        // Taxonomies
        if ( ! empty( $data['sector'] ) ) {
            wp_set_object_terms( $post_id, sanitize_key( $data['sector'] ), Constants::TAX_SECTOR );
        }
        if ( ! empty( $data['province'] ) ) {
            wp_set_object_terms( $post_id, sanitize_key( $data['province'] ), Constants::TAX_PROVINCE );
        }
        if ( ! empty( $data['stage'] ) ) {
            wp_set_object_terms( $post_id, sanitize_key( $data['stage'] ), Constants::TAX_STAGE );
        }

        Logger::audit( 'opportunity_submitted', 'New business opportunity submitted for review', [
            'post_id'   => $post_id,
            'author_id' => $author_id,
        ] );

        return $post_id;
    }

    /**
     * Admin review action: Approve and publish listing
     *
     * @param int $post_id
     * @return bool|\WP_Error
     */
    public static function approve_listing( $post_id ) {
        $post = get_post( $post_id );
        if ( ! $post || $post->post_type !== Constants::POST_TYPE_OPPORTUNITY ) {
            return new \WP_Error( 'not_found', __( 'Listing not found.', 'cuba-investment-core' ) );
        }

        wp_update_post( [
            'ID'          => $post_id,
            'post_status' => 'publish',
        ] );

        update_post_meta( $post_id, '_cin_review_status', Constants::STATUS_APPROVED );

        // Send Notification to Business Owner
        NotificationService::send(
            $post->post_author,
            'listing_approved',
            __( 'Your listing has been approved and published!', 'cuba-investment-core' ),
            sprintf( __( 'Your business profile "%s" has passed quality review and is now visible to investors.', 'cuba-investment-core' ), get_the_title( $post ) ),
            get_permalink( $post_id )
        );

        Logger::audit( 'opportunity_approved', 'Listing approved by admin', [ 'post_id' => $post_id ] );

        return true;
    }

    /**
     * Admin review action: Request revisions
     *
     * @param int $post_id
     * @param string $notes Feedback notes
     * @return bool|\WP_Error
     */
    public static function request_revisions( $post_id, $notes = '' ) {
        $post = get_post( $post_id );
        if ( ! $post || $post->post_type !== Constants::POST_TYPE_OPPORTUNITY ) {
            return new \WP_Error( 'not_found', __( 'Listing not found.', 'cuba-investment-core' ) );
        }

        update_post_meta( $post_id, '_cin_review_status', Constants::STATUS_REVISION_REQUESTED );
        if ( ! empty( $notes ) ) {
            update_post_meta( $post_id, '_cin_review_notes', sanitize_textarea_field( $notes ) );
        }

        // Notify Business Owner
        NotificationService::send(
            $post->post_author,
            'listing_revision_requested',
            __( 'Revisions requested for your business listing', 'cuba-investment-core' ),
            sprintf( __( 'Our review team requested updates for "%s": %s', 'cuba-investment-core' ), get_the_title( $post ), $notes ),
            home_url( '/dashboard/business' )
        );

        Logger::audit( 'opportunity_revision_requested', 'Revisions requested for listing', [ 'post_id' => $post_id ] );

        return true;
    }

    /**
     * Save or update an opportunity draft (incomplete data allowed)
     *
     * @param int   $author_id
     * @param array $data
     * @param int   $opportunity_id
     * @return int|\WP_Error
     */
    public static function save_draft( $author_id, array $data, $opportunity_id = 0 ) {
        if ( ! $author_id ) {
            return new \WP_Error( 'unauthorized', __( 'Authentication required to save opportunity draft.', 'cuba-investment-core' ), [ 'status' => 401 ] );
        }

        if ( ! Permissions::is_business_owner( $author_id ) && ! Permissions::is_admin_or_reviewer( $author_id ) ) {
            return new \WP_Error( 'forbidden_role', __( 'Only registered Business Owners can create or edit opportunities.', 'cuba-investment-core' ), [ 'status' => 403 ] );
        }

        if ( ! Permissions::is_account_active( $author_id ) ) {
            return new \WP_Error( 'account_inactive', __( 'Your account is not active.', 'cuba-investment-core' ), [ 'status' => 403 ] );
        }

        // If editing existing
        if ( $opportunity_id > 0 ) {
            $post = get_post( $opportunity_id );
            if ( ! $post || $post->post_type !== Constants::POST_TYPE_OPPORTUNITY ) {
                return new \WP_Error( 'not_found', __( 'Opportunity not found.', 'cuba-investment-core' ), [ 'status' => 404 ] );
            }

            if ( (int) $post->post_author !== (int) $author_id && ! Permissions::is_admin_or_reviewer( $author_id ) ) {
                return new \WP_Error( 'forbidden', __( 'You do not have permission to edit this opportunity.', 'cuba-investment-core' ), [ 'status' => 403 ] );
            }

            $current_review_status = get_post_meta( $opportunity_id, '_cin_review_status', true ) ?: $post->post_status;
            if ( ! in_array( $current_review_status, [ 'draft', Constants::STATUS_DRAFT, Constants::STATUS_REVISION_REQUESTED ], true ) && ! Permissions::is_admin_or_reviewer( $author_id ) ) {
                return new \WP_Error( 'not_editable', __( 'Submitted or published opportunities cannot be edited directly.', 'cuba-investment-core' ), [ 'status' => 400 ] );
            }

            $post_id = $opportunity_id;
            $title = isset( $data['title'] ) && trim( $data['title'] ) !== '' ? sanitize_text_field( $data['title'] ) : $post->post_title;
            $description = isset( $data['description'] ) ? wp_kses_post( $data['description'] ) : $post->post_content;

            wp_update_post( [
                'ID'           => $post_id,
                'post_title'   => $title,
                'post_content' => $description,
                'post_excerpt' => isset( $data['excerpt'] ) ? sanitize_text_field( $data['excerpt'] ) : wp_trim_words( $description, 35 ),
                'post_status'  => 'draft',
            ] );
        } else {
            // Creating new draft
            $title = ! empty( $data['title'] ) ? sanitize_text_field( $data['title'] ) : __( 'Untitled Opportunity Draft', 'cuba-investment-core' );
            $description = ! empty( $data['description'] ) ? wp_kses_post( $data['description'] ) : '';

            $post_id = wp_insert_post( [
                'post_title'   => $title,
                'post_content' => $description,
                'post_excerpt' => isset( $data['excerpt'] ) ? sanitize_text_field( $data['excerpt'] ) : wp_trim_words( $description, 35 ),
                'post_status'  => 'draft',
                'post_type'    => Constants::POST_TYPE_OPPORTUNITY,
                'post_author'  => $author_id,
            ] );

            if ( is_wp_error( $post_id ) ) {
                Logger::error( 'Failed to create opportunity draft', [ 'author_id' => $author_id, 'error' => $post_id->get_error_message() ] );
                return $post_id;
            }

            update_post_meta( $post_id, '_cin_created_at', current_time( 'mysql' ) );
        }

        // Save metadata fields
        if ( isset( $data['company_name'] ) ) {
            update_post_meta( $post_id, '_cin_company_name', sanitize_text_field( $data['company_name'] ) );
        }
        if ( isset( $data['country'] ) ) {
            update_post_meta( $post_id, '_cin_country', sanitize_text_field( $data['country'] ) );
        } else {
            if ( ! get_post_meta( $post_id, '_cin_country', true ) ) {
                update_post_meta( $post_id, '_cin_country', 'Cuba' );
            }
        }
        if ( isset( $data['city'] ) || isset( $data['province'] ) ) {
            $city_val = sanitize_text_field( $data['city'] ?? $data['province'] );
            update_post_meta( $post_id, '_cin_city', $city_val );
        }
        if ( isset( $data['products_services'] ) ) {
            update_post_meta( $post_id, '_cin_products_services', sanitize_textarea_field( $data['products_services'] ) );
        }
        if ( isset( $data['operating_history'] ) ) {
            update_post_meta( $post_id, '_cin_operating_history', sanitize_textarea_field( $data['operating_history'] ) );
        }
        if ( isset( $data['business_stage'] ) || isset( $data['stage'] ) ) {
            update_post_meta( $post_id, '_cin_business_stage', sanitize_text_field( $data['business_stage'] ?? $data['stage'] ) );
        }
        if ( isset( $data['website'] ) ) {
            update_post_meta( $post_id, '_cin_website', esc_url_raw( $data['website'] ) );
        }
        if ( isset( $data['capital_sought'] ) ) {
            update_post_meta( $post_id, '_cin_capital_sought', Sanitizer::amount( $data['capital_sought'] ) );
        }
        if ( isset( $data['minimum_investment'] ) ) {
            update_post_meta( $post_id, '_cin_minimum_investment', Sanitizer::amount( $data['minimum_investment'] ) );
        }
        if ( isset( $data['currency'] ) ) {
            update_post_meta( $post_id, '_cin_currency', Sanitizer::currency( $data['currency'] ) );
        }
        if ( isset( $data['use_of_funds'] ) || isset( $data['capital_purpose'] ) ) {
            $use = sanitize_textarea_field( $data['use_of_funds'] ?? $data['capital_purpose'] );
            update_post_meta( $post_id, '_cin_use_of_funds', $use );
            update_post_meta( $post_id, '_cin_capital_purpose', $use );
        }
        if ( isset( $data['expected_impact'] ) ) {
            update_post_meta( $post_id, '_cin_expected_impact', sanitize_textarea_field( $data['expected_impact'] ) );
        }
        if ( isset( $data['partnership_structure'] ) ) {
            update_post_meta( $post_id, '_cin_partnership_structure', sanitize_textarea_field( $data['partnership_structure'] ) );
        }
        if ( isset( $data['funding_stage'] ) ) {
            update_post_meta( $post_id, '_cin_funding_stage', sanitize_text_field( $data['funding_stage'] ) );
        }
        if ( isset( $data['capital_notes'] ) ) {
            update_post_meta( $post_id, '_cin_capital_notes', sanitize_textarea_field( $data['capital_notes'] ) );
        }
        if ( isset( $data['revenue_info'] ) ) {
            update_post_meta( $post_id, '_cin_revenue_info', sanitize_textarea_field( $data['revenue_info'] ) );
        }
        if ( isset( $data['milestones'] ) ) {
            update_post_meta( $post_id, '_cin_milestones', sanitize_textarea_field( $data['milestones'] ) );
        }
        if ( isset( $data['target_market'] ) ) {
            update_post_meta( $post_id, '_cin_target_market', sanitize_textarea_field( $data['target_market'] ) );
        }
        if ( isset( $data['key_partnerships'] ) ) {
            update_post_meta( $post_id, '_cin_key_partnerships', sanitize_textarea_field( $data['key_partnerships'] ) );
        }
        if ( isset( $data['business_assets'] ) ) {
            update_post_meta( $post_id, '_cin_business_assets', sanitize_textarea_field( $data['business_assets'] ) );
        }
        if ( isset( $data['licences_permits'] ) ) {
            update_post_meta( $post_id, '_cin_licences_permits', sanitize_textarea_field( $data['licences_permits'] ) );
        }
        if ( isset( $data['current_projects'] ) ) {
            update_post_meta( $post_id, '_cin_current_projects', sanitize_textarea_field( $data['current_projects'] ) );
        }
        if ( isset( $data['additional_operations'] ) ) {
            update_post_meta( $post_id, '_cin_additional_operations', sanitize_textarea_field( $data['additional_operations'] ) );
        }
        if ( isset( $data['management_overview'] ) ) {
            update_post_meta( $post_id, '_cin_management_overview', sanitize_textarea_field( $data['management_overview'] ) );
        }
        if ( isset( $data['key_team_members'] ) ) {
            update_post_meta( $post_id, '_cin_key_team_members', sanitize_textarea_field( $data['key_team_members'] ) );
        }
        if ( isset( $data['ownership_structure'] ) ) {
            update_post_meta( $post_id, '_cin_ownership_structure', Sanitizer::legal_structure( $data['ownership_structure'] ) );
        }
        if ( isset( $data['existing_liabilities'] ) ) {
            update_post_meta( $post_id, '_cin_existing_liabilities', sanitize_textarea_field( $data['existing_liabilities'] ) );
        }
        if ( isset( $data['operational_risks'] ) ) {
            update_post_meta( $post_id, '_cin_operational_risks', sanitize_textarea_field( $data['operational_risks'] ) );
        }
        if ( isset( $data['financial_risks'] ) ) {
            update_post_meta( $post_id, '_cin_financial_risks', sanitize_textarea_field( $data['financial_risks'] ) );
        }
        if ( isset( $data['regulatory_risks'] ) ) {
            update_post_meta( $post_id, '_cin_regulatory_risks', sanitize_textarea_field( $data['regulatory_risks'] ) );
        }
        if ( isset( $data['material_risks'] ) ) {
            update_post_meta( $post_id, '_cin_material_risks', sanitize_textarea_field( $data['material_risks'] ) );
        }
        if ( isset( $data['partnership_type'] ) ) {
            update_post_meta( $post_id, '_cin_partnership_type', sanitize_text_field( $data['partnership_type'] ) );
        }
        if ( isset( $data['partnership_types'] ) && is_array( $data['partnership_types'] ) ) {
            $types = array_map( 'sanitize_text_field', $data['partnership_types'] );
            update_post_meta( $post_id, '_cin_partnership_types', $types );
        }
        if ( isset( $data['investment_interest'] ) ) {
            update_post_meta( $post_id, '_cin_investment_interest', sanitize_textarea_field( $data['investment_interest'] ) );
        }
        if ( isset( $data['strategic_expertise'] ) ) {
            update_post_meta( $post_id, '_cin_strategic_expertise', sanitize_textarea_field( $data['strategic_expertise'] ) );
        }
        if ( isset( $data['bizdev_support'] ) ) {
            update_post_meta( $post_id, '_cin_bizdev_support', sanitize_textarea_field( $data['bizdev_support'] ) );
        }
        if ( isset( $data['partner_characteristics'] ) ) {
            update_post_meta( $post_id, '_cin_partner_characteristics', sanitize_textarea_field( $data['partner_characteristics'] ) );
        }
        if ( isset( $data['additional_partnership'] ) ) {
            update_post_meta( $post_id, '_cin_additional_partnership', sanitize_textarea_field( $data['additional_partnership'] ) );
        }
        if ( isset( $data['declaration_confirmed'] ) ) {
            $decl = ! empty( $data['declaration_confirmed'] ) ? 1 : 0;
            update_post_meta( $post_id, '_cin_declaration_confirmed', $decl );
            if ( $decl ) {
                update_post_meta( $post_id, '_cin_declaration_timestamp', current_time( 'mysql' ) );
            }
        }

        // Taxonomies
        if ( ! empty( $data['sector'] ) ) {
            wp_set_object_terms( $post_id, sanitize_key( $data['sector'] ), Constants::TAX_SECTOR );
        }
        if ( ! empty( $data['province'] ) || ! empty( $data['city'] ) ) {
            $prov = sanitize_key( $data['province'] ?? $data['city'] );
            wp_set_object_terms( $post_id, $prov, Constants::TAX_PROVINCE );
        }
        if ( ! empty( $data['stage'] ) || ! empty( $data['business_stage'] ) ) {
            $stg = sanitize_key( $data['stage'] ?? $data['business_stage'] );
            wp_set_object_terms( $post_id, $stg, Constants::TAX_STAGE );
        }

        update_post_meta( $post_id, '_cin_review_status', Constants::STATUS_DRAFT );
        update_post_meta( $post_id, '_cin_updated_at', current_time( 'mysql' ) );

        Logger::audit( 'draft_saved', 'Opportunity draft saved', [
            'post_id'   => $post_id,
            'author_id' => $author_id,
        ] );

        return $post_id;
    }

    /**
     * Submit an opportunity draft for admin review
     *
     * @param int   $author_id
     * @param int   $opportunity_id
     * @param array $data Optional data updates to save before submitting
     * @return true|\WP_Error
     */
    public static function submit_for_review( $author_id, $opportunity_id, array $data = [] ) {
        if ( ! $author_id || ! $opportunity_id ) {
            return new \WP_Error( 'invalid_params', __( 'Invalid submission parameters.', 'cuba-investment-core' ), [ 'status' => 400 ] );
        }

        if ( ! Permissions::is_business_owner( $author_id ) && ! Permissions::is_admin_or_reviewer( $author_id ) ) {
            return new \WP_Error( 'forbidden_role', __( 'Only authorized Business Owners can submit opportunities.', 'cuba-investment-core' ), [ 'status' => 403 ] );
        }

        if ( ! Permissions::is_account_active( $author_id ) ) {
            return new \WP_Error( 'account_inactive', __( 'Your account is not active. Submission blocked.', 'cuba-investment-core' ), [ 'status' => 403 ] );
        }

        $post = get_post( $opportunity_id );
        if ( ! $post || $post->post_type !== Constants::POST_TYPE_OPPORTUNITY ) {
            return new \WP_Error( 'not_found', __( 'Opportunity not found.', 'cuba-investment-core' ), [ 'status' => 404 ] );
        }

        if ( (int) $post->post_author !== (int) $author_id && ! Permissions::is_admin_or_reviewer( $author_id ) ) {
            return new \WP_Error( 'forbidden', __( 'You do not have permission to submit this opportunity.', 'cuba-investment-core' ), [ 'status' => 403 ] );
        }

        // Quota check
        if ( ! EntitlementService::can_perform( $author_id, 'submit_opportunity' ) ) {
            return new \WP_Error( 'quota_exceeded', __( 'Opportunity submission quota reached for current tier.', 'cuba-investment-core' ), [ 'status' => 403 ] );
        }

        // If extra data passed, save it first
        if ( ! empty( $data ) ) {
            $saved = self::save_draft( $author_id, $data, $opportunity_id );
            if ( is_wp_error( $saved ) ) {
                return $saved;
            }
        }

        // Refresh post after saving
        $post = get_post( $opportunity_id );

        // Validate all required fields
        $title       = get_the_title( $post );
        $content     = $post->post_content;
        $company     = get_post_meta( $opportunity_id, '_cin_company_name', true );
        $country     = get_post_meta( $opportunity_id, '_cin_country', true ) ?: 'Cuba';
        $city        = get_post_meta( $opportunity_id, '_cin_city', true );
        $products    = get_post_meta( $opportunity_id, '_cin_products_services', true );
        $capital     = (float) get_post_meta( $opportunity_id, '_cin_capital_sought', true );
        $currency    = get_post_meta( $opportunity_id, '_cin_currency', true );
        $use_of_fund = get_post_meta( $opportunity_id, '_cin_use_of_funds', true ) ?: get_post_meta( $opportunity_id, '_cin_capital_purpose', true );
        $declared    = (bool) get_post_meta( $opportunity_id, '_cin_declaration_confirmed', true );

        $missing = [];
        if ( empty( $title ) || strlen( trim( $title ) ) < 5 || $title === __( 'Untitled Opportunity Draft', 'cuba-investment-core' ) ) {
            $missing[] = __( 'Opportunity Title (minimum 5 characters)', 'cuba-investment-core' );
        }
        if ( empty( $company ) ) {
            $missing[] = __( 'Business Name', 'cuba-investment-core' );
        }
        $sectors = wp_get_post_terms( $opportunity_id, Constants::TAX_SECTOR );
        if ( empty( $sectors ) || is_wp_error( $sectors ) ) {
            $missing[] = __( 'Business Sector / Industry', 'cuba-investment-core' );
        }
        if ( empty( $country ) ) {
            $missing[] = __( 'Business Country', 'cuba-investment-core' );
        }
        if ( empty( $city ) ) {
            $missing[] = __( 'City / Region', 'cuba-investment-core' );
        }
        if ( empty( $content ) || strlen( trim( $content ) ) < 20 ) {
            $missing[] = __( 'Business Description (minimum 20 characters)', 'cuba-investment-core' );
        }
        if ( empty( $products ) ) {
            $missing[] = __( 'Products or Services description', 'cuba-investment-core' );
        }
        if ( $capital <= 0 ) {
            $missing[] = __( 'Valid Capital Sought amount greater than 0', 'cuba-investment-core' );
        }
        if ( empty( $currency ) ) {
            $missing[] = __( 'Capital Currency', 'cuba-investment-core' );
        }
        if ( empty( $use_of_fund ) ) {
            $missing[] = __( 'Use of Funds description', 'cuba-investment-core' );
        }
        if ( ! $declared ) {
            $missing[] = __( 'Authorized submission declaration confirmation', 'cuba-investment-core' );
        }

        if ( ! empty( $missing ) ) {
            return new \WP_Error(
                'missing_required_fields',
                sprintf( __( 'Please complete all required fields before submission: %s', 'cuba-investment-core' ), implode( ', ', $missing ) ),
                [ 'missing' => $missing, 'status' => 400 ]
            );
        }

        // Transition to Under Review
        wp_update_post( [
            'ID'          => $opportunity_id,
            'post_status' => 'pending',
        ] );

        update_post_meta( $opportunity_id, '_cin_review_status', Constants::STATUS_PENDING_REVIEW );
        update_post_meta( $opportunity_id, '_cin_submitted_at', current_time( 'mysql' ) );

        Logger::audit( 'opportunity_submitted', 'Opportunity submitted for review', [
            'post_id'   => $opportunity_id,
            'author_id' => $author_id,
        ] );

        return true;
    }

    /**
     * Get all opportunities owned by author with status counts
     *
     * @param int   $author_id
     * @param array $args
     * @return array
     */
    public static function get_owner_opportunities( $author_id, array $args = [] ) {
        global $wpdb;

        $status = isset( $args['status'] ) ? sanitize_key( $args['status'] ) : 'all';
        $search = isset( $args['search'] ) ? sanitize_text_field( $args['search'] ) : '';
        $page   = isset( $args['page'] ) ? max( 1, (int) $args['page'] ) : 1;
        $limit  = isset( $args['limit'] ) ? max( 1, (int) $args['limit'] ) : 10;

        // Calculate counts by status for this author
        $counts_raw = $wpdb->get_results( $wpdb->prepare(
            "SELECT post_status, meta.meta_value as review_status, COUNT(*) as cnt
             FROM {$wpdb->posts} p
             LEFT JOIN {$wpdb->postmeta} meta ON (p.ID = meta.post_id AND meta.meta_key = '_cin_review_status')
             WHERE p.post_author = %d AND p.post_type = %s AND p.post_status NOT IN ('trash', 'auto-draft')
             GROUP BY p.post_status, meta.meta_value",
            $author_id,
            Constants::POST_TYPE_OPPORTUNITY
        ), ARRAY_A );

        $counts = [
            'all'            => 0,
            'draft'          => 0,
            'pending_review' => 0,
            'pending'        => 0,
            'publish'        => 0,
            'paused'         => 0,
            'closed'         => 0,
            'archived'       => 0,
        ];

        foreach ( $counts_raw as $row ) {
            $cnt = (int) $row['cnt'];
            $st  = $row['review_status'] ?: $row['post_status'];
            $counts['all'] += $cnt;

            if ( 'draft' === $st || 'draft' === $row['post_status'] ) {
                $counts['draft'] += $cnt;
            } elseif ( 'pending_review' === $st || 'pending' === $row['post_status'] ) {
                $counts['pending_review'] += $cnt;
                $counts['pending']        += $cnt;
            } elseif ( 'publish' === $st || 'publish' === $row['post_status'] ) {
                $counts['publish'] += $cnt;
            } elseif ( isset( $counts[ $st ] ) ) {
                $counts[ $st ] += $cnt;
            }
        }

        // Build WP_Query args for items
        $query_args = [
            'post_type'      => Constants::POST_TYPE_OPPORTUNITY,
            'author'         => $author_id,
            'posts_per_page' => $limit,
            'paged'          => $page,
            'orderby'        => 'modified',
            'order'          => 'DESC',
        ];

        if ( 'all' === $status ) {
            $query_args['post_status'] = [ 'draft', 'pending', 'publish', 'private' ];
        } elseif ( 'draft' === $status ) {
            $query_args['post_status'] = 'draft';
        } elseif ( in_array( $status, [ 'pending_review', 'under_review', 'pending' ], true ) ) {
            $query_args['post_status'] = 'pending';
        } elseif ( in_array( $status, [ 'publish', 'published' ], true ) ) {
            $query_args['post_status'] = 'publish';
        } else {
            $query_args['post_status'] = [ 'draft', 'pending', 'publish', 'private' ];
            $query_args['meta_query'] = [
                [
                    'key'   => '_cin_review_status',
                    'value' => $status,
                ],
            ];
        }

        if ( ! empty( $search ) ) {
            $query_args['s'] = $search;
        }

        $query = new \WP_Query( $query_args );
        $items = [];

        if ( $query->have_posts() ) {
            foreach ( $query->posts as $p ) {
                $opp = new Opportunity( $p );
                $items[] = $opp->to_array();
            }
        }

        return [
            'items'        => $items,
            'total'        => (int) $query->found_posts,
            'total_pages'  => (int) $query->max_num_pages,
            'current_page' => $page,
            'counts'       => $counts,
        ];
    }

    /**
     * Get single opportunity model with permission check
     *
     * @param int $opportunity_id
     * @param int $user_id
     * @return Opportunity|null
     */
    public static function get_opportunity( $opportunity_id, $user_id = 0 ) {
        $post = get_post( $opportunity_id );
        if ( ! $post || $post->post_type !== Constants::POST_TYPE_OPPORTUNITY ) {
            return null;
        }

        // If draft, only author or admin can view
        if ( 'draft' === $post->post_status && $user_id ) {
            if ( (int) $post->post_author !== (int) $user_id && ! Permissions::is_admin_or_reviewer( $user_id ) ) {
                return null;
            }
        }

        return new Opportunity( $post );
    }

    /**
     * Delete an un-submitted draft
     *
     * @param int $author_id
     * @param int $opportunity_id
     * @return true|\WP_Error
     */
    public static function delete_draft( $author_id, $opportunity_id ) {
        if ( ! $author_id || ! $opportunity_id ) {
            return new \WP_Error( 'invalid_params', __( 'Invalid request parameters.', 'cuba-investment-core' ), [ 'status' => 400 ] );
        }

        $post = get_post( $opportunity_id );
        if ( ! $post || $post->post_type !== Constants::POST_TYPE_OPPORTUNITY ) {
            return new \WP_Error( 'not_found', __( 'Opportunity not found.', 'cuba-investment-core' ), [ 'status' => 404 ] );
        }

        if ( (int) $post->post_author !== (int) $author_id && ! Permissions::is_admin_or_reviewer( $author_id ) ) {
            return new \WP_Error( 'forbidden', __( 'You do not have permission to delete this opportunity.', 'cuba-investment-core' ), [ 'status' => 403 ] );
        }

        $review_status = get_post_meta( $opportunity_id, '_cin_review_status', true ) ?: $post->post_status;
        if ( 'draft' !== $post->post_status && 'draft' !== $review_status ) {
            return new \WP_Error( 'cannot_delete_submitted', __( 'Only draft opportunities can be deleted directly.', 'cuba-investment-core' ), [ 'status' => 400 ] );
        }

        $trashed = wp_trash_post( $opportunity_id );
        if ( ! $trashed ) {
            return new \WP_Error( 'delete_failed', __( 'Could not remove draft.', 'cuba-investment-core' ), [ 'status' => 500 ] );
        }

        Logger::audit( 'draft_deleted', 'Opportunity draft removed', [
            'post_id'   => $opportunity_id,
            'author_id' => $author_id,
        ] );

        return true;
    }

    /**
     * Upload supporting document for opportunity
     *
     * @param int   $author_id
     * @param int   $opportunity_id
     * @param array $file $_FILES['document']
     * @return array|\WP_Error
     */
    public static function upload_document( $author_id, $opportunity_id, array $file ) {
        if ( ! $author_id || ! $opportunity_id ) {
            return new \WP_Error( 'invalid_params', __( 'Invalid parameters for document upload.', 'cuba-investment-core' ), [ 'status' => 400 ] );
        }

        $post = get_post( $opportunity_id );
        if ( ! $post || $post->post_type !== Constants::POST_TYPE_OPPORTUNITY ) {
            return new \WP_Error( 'not_found', __( 'Opportunity not found.', 'cuba-investment-core' ), [ 'status' => 404 ] );
        }

        if ( (int) $post->post_author !== (int) $author_id && ! Permissions::is_admin_or_reviewer( $author_id ) ) {
            return new \WP_Error( 'forbidden', __( 'You do not own this opportunity.', 'cuba-investment-core' ), [ 'status' => 403 ] );
        }

        if ( ! isset( $file['error'] ) || $file['error'] !== UPLOAD_ERR_OK ) {
            return new \WP_Error( 'upload_failed', __( 'File upload failed. Please try again.', 'cuba-investment-core' ), [ 'status' => 400 ] );
        }

        // Validate file size (max 10MB)
        $max_size = 10 * 1024 * 1024;
        if ( $file['size'] > $max_size ) {
            return new \WP_Error( 'file_too_large', __( 'File size exceeds maximum allowed 10MB.', 'cuba-investment-core' ), [ 'status' => 400 ] );
        }

        // Validate extension
        $ext = strtolower( pathinfo( $file['name'], PATHINFO_EXTENSION ) );
        $allowed_exts = [ 'pdf', 'docx', 'xlsx', 'png', 'jpg', 'jpeg' ];
        if ( ! in_array( $ext, $allowed_exts, true ) ) {
            return new \WP_Error( 'invalid_file_type', __( 'Invalid file type. Allowed: PDF, DOCX, XLSX, PNG, JPG.', 'cuba-investment-core' ), [ 'status' => 400 ] );
        }

        // Validate MIME type
        $finfo = finfo_open( FILEINFO_MIME_TYPE );
        $mime_type = finfo_file( $finfo, $file['tmp_name'] );
        finfo_close( $finfo );

        $allowed_mimes = [
            'application/pdf',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'image/png',
            'image/jpeg',
            'image/pjpeg',
        ];

        if ( ! in_array( $mime_type, $allowed_mimes, true ) ) {
            return new \WP_Error( 'invalid_mime', __( 'File MIME type validation failed.', 'cuba-investment-core' ), [ 'status' => 400 ] );
        }

        // Move to protected directory
        $protected_dir = DocumentAccess::get_protected_dir();
        $safe_filename = wp_unique_filename( $protected_dir, 'doc_' . $opportunity_id . '_' . sanitize_file_name( $file['name'] ) );
        $target_path   = trailingslashit( $protected_dir ) . $safe_filename;

        $moved = false;
        if ( is_uploaded_file( $file['tmp_name'] ) ) {
            $moved = move_uploaded_file( $file['tmp_name'], $target_path );
        } else {
            $moved = @rename( $file['tmp_name'], $target_path ) || @copy( $file['tmp_name'], $target_path );
        }

        if ( ! $moved ) {
            return new \WP_Error( 'move_failed', __( 'Could not securely save uploaded document.', 'cuba-investment-core' ), [ 'status' => 500 ] );
        }

        // Create doc object
        $doc_id = 'cin_doc_' . wp_generate_password( 8, false );
        $doc = [
            'id'          => $doc_id,
            'name'        => sanitize_text_field( $file['name'] ),
            'file_name'   => $safe_filename,
            'file_size'   => (int) $file['size'],
            'file_type'   => $mime_type,
            'uploaded_at' => current_time( 'mysql' ),
        ];

        $documents = get_post_meta( $opportunity_id, '_cin_documents', true );
        if ( ! is_array( $documents ) ) {
            $documents = [];
        }
        $documents[] = $doc;
        update_post_meta( $opportunity_id, '_cin_documents', $documents );

        Logger::audit( 'document_uploaded', 'Supporting document uploaded', [
            'post_id'   => $opportunity_id,
            'doc_id'    => $doc_id,
            'author_id' => $author_id,
        ] );

        return $doc;
    }

    /**
     * Delete a supporting document from opportunity
     *
     * @param int    $author_id
     * @param int    $opportunity_id
     * @param string $doc_id
     * @return true|\WP_Error
     */
    public static function delete_document( $author_id, $opportunity_id, $doc_id ) {
        if ( ! $author_id || ! $opportunity_id || ! $doc_id ) {
            return new \WP_Error( 'invalid_params', __( 'Invalid parameters.', 'cuba-investment-core' ), [ 'status' => 400 ] );
        }

        $post = get_post( $opportunity_id );
        if ( ! $post || $post->post_type !== Constants::POST_TYPE_OPPORTUNITY ) {
            return new \WP_Error( 'not_found', __( 'Opportunity not found.', 'cuba-investment-core' ), [ 'status' => 404 ] );
        }

        if ( (int) $post->post_author !== (int) $author_id && ! Permissions::is_admin_or_reviewer( $author_id ) ) {
            return new \WP_Error( 'forbidden', __( 'You do not own this opportunity.', 'cuba-investment-core' ), [ 'status' => 403 ] );
        }

        $documents = get_post_meta( $opportunity_id, '_cin_documents', true );
        if ( ! is_array( $documents ) ) {
            return new \WP_Error( 'doc_not_found', __( 'Document not found.', 'cuba-investment-core' ), [ 'status' => 404 ] );
        }

        $updated_docs = [];
        $found = false;
        $protected_dir = DocumentAccess::get_protected_dir();

        foreach ( $documents as $doc ) {
            if ( $doc['id'] === $doc_id ) {
                $found = true;
                $filepath = trailingslashit( $protected_dir ) . $doc['file_name'];
                if ( file_exists( $filepath ) ) {
                    @unlink( $filepath );
                }
            } else {
                $updated_docs[] = $doc;
            }
        }

        if ( ! $found ) {
            return new \WP_Error( 'doc_not_found', __( 'Document not found.', 'cuba-investment-core' ), [ 'status' => 404 ] );
        }

        update_post_meta( $opportunity_id, '_cin_documents', $updated_docs );

        Logger::audit( 'document_deleted', 'Supporting document removed', [
            'post_id'   => $opportunity_id,
            'doc_id'    => $doc_id,
            'author_id' => $author_id,
        ] );

        return true;
    }

    /**
     * Safely stream a document download to authorized user
     *
     * @param int    $user_id
     * @param int    $opportunity_id
     * @param string $doc_id
     */
    public static function stream_document( $user_id, $opportunity_id, $doc_id ) {
        if ( ! DocumentAccess::user_can_download( $user_id, $opportunity_id ) ) {
            wp_die( esc_html__( 'Unauthorized to access this document.', 'cuba-investment-core' ), 403 );
        }

        $documents = get_post_meta( $opportunity_id, '_cin_documents', true );
        if ( ! is_array( $documents ) ) {
            wp_die( esc_html__( 'Document not found.', 'cuba-investment-core' ), 404 );
        }

        $target_doc = null;
        foreach ( $documents as $d ) {
            if ( $d['id'] === $doc_id ) {
                $target_doc = $d;
                break;
            }
        }

        if ( ! $target_doc ) {
            wp_die( esc_html__( 'Document record not found.', 'cuba-investment-core' ), 404 );
        }

        $protected_dir = DocumentAccess::get_protected_dir();
        $file_path     = trailingslashit( $protected_dir ) . $target_doc['file_name'];

        if ( ! file_exists( $file_path ) ) {
            wp_die( esc_html__( 'Physical file is missing from secure storage.', 'cuba-investment-core' ), 404 );
        }

        // Clean any output buffers
        while ( ob_get_level() ) {
            ob_end_clean();
        }

        header( 'Content-Description: File Transfer' );
        header( 'Content-Type: ' . $target_doc['file_type'] );
        header( 'Content-Disposition: attachment; filename="' . basename( $target_doc['name'] ) . '"' );
        header( 'Content-Transfer-Encoding: binary' );
        header( 'Expires: 0' );
        header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
        header( 'Pragma: public' );
        header( 'Content-Length: ' . filesize( $file_path ) );

        readfile( $file_path );
        exit;
    }
}
