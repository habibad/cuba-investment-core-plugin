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
}
