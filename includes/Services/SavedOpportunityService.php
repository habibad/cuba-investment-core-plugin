<?php
/**
 * Cuba Investment Core - Saved Opportunities Service
 *
 * Provides persistent database bookmarking for investors.
 *
 * @package CubaInvestment\Core
 */

namespace CubaInvestment\Core\Services;

use CubaInvestment\Core\Common\Constants;
use CubaInvestment\Core\Common\Logger;
use CubaInvestment\Core\Auth\Permissions;
use CubaInvestment\Core\Models\Opportunity;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SavedOpportunityService {

    /**
     * Save an opportunity for an investor
     *
     * @param int $user_id
     * @param int $opportunity_id
     * @return bool|\WP_Error
     */
    public static function save( $user_id, $opportunity_id ) {
        global $wpdb;

        $user_id        = absint( $user_id );
        $opportunity_id = absint( $opportunity_id );

        if ( ! $user_id ) {
            return new \WP_Error( 'unauthorized', __( 'Please log in to save opportunities.', 'cuba-investment-core' ), [ 'status' => 401 ] );
        }

        if ( ! Permissions::is_account_active( $user_id ) ) {
            return new \WP_Error( 'account_inactive', __( 'Your account is suspended or inactive.', 'cuba-investment-core' ), [ 'status' => 403 ] );
        }

        if ( ! Permissions::is_investor( $user_id ) && ! Permissions::is_admin_or_reviewer( $user_id ) ) {
            return new \WP_Error( 'forbidden', __( 'Only investors can save opportunities.', 'cuba-investment-core' ), [ 'status' => 403 ] );
        }

        $post = get_post( $opportunity_id );
        if ( ! $post || $post->post_type !== Constants::POST_TYPE_OPPORTUNITY || 'publish' !== $post->post_status ) {
            return new \WP_Error( 'not_found', __( 'The opportunity is unavailable or not yet published.', 'cuba-investment-core' ), [ 'status' => 404 ] );
        }

        $table = Constants::get_table_name( Constants::TABLE_SAVED_OPPORTUNITIES );

        // Check if already saved
        $existing = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM {$table} WHERE user_id = %d AND opportunity_id = %d",
                $user_id,
                $opportunity_id
            )
        );

        if ( $existing ) {
            self::sync_user_meta_cache( $user_id );
            return true;
        }

        $now = current_time( 'mysql' );
        $inserted = $wpdb->insert(
            $table,
            [
                'user_id'        => $user_id,
                'opportunity_id' => $opportunity_id,
                'created_at'     => $now,
            ],
            [ '%d', '%d', '%s' ]
        );

        if ( false === $inserted ) {
            Logger::error( 'Failed to save opportunity in database', [ 'user_id' => $user_id, 'opp_id' => $opportunity_id ] );
            return new \WP_Error( 'db_error', __( 'Failed to bookmark opportunity.', 'cuba-investment-core' ) );
        }

        self::sync_user_meta_cache( $user_id );

        Logger::audit( 'opportunity_saved', 'Investor bookmarked opportunity', [
            'user_id'        => $user_id,
            'opportunity_id' => $opportunity_id,
        ] );

        return true;
    }

    /**
     * Remove an opportunity from saved listings
     *
     * @param int $user_id
     * @param int $opportunity_id
     * @return bool|\WP_Error
     */
    public static function remove( $user_id, $opportunity_id ) {
        global $wpdb;

        $user_id        = absint( $user_id );
        $opportunity_id = absint( $opportunity_id );

        if ( ! $user_id ) {
            return new \WP_Error( 'unauthorized', __( 'Please log in to manage bookmarks.', 'cuba-investment-core' ), [ 'status' => 401 ] );
        }

        if ( ! Permissions::is_account_active( $user_id ) ) {
            return new \WP_Error( 'account_inactive', __( 'Your account is suspended or inactive.', 'cuba-investment-core' ), [ 'status' => 403 ] );
        }

        $table = Constants::get_table_name( Constants::TABLE_SAVED_OPPORTUNITIES );

        $deleted = $wpdb->delete(
            $table,
            [
                'user_id'        => $user_id,
                'opportunity_id' => $opportunity_id,
            ],
            [ '%d', '%d' ]
        );

        self::sync_user_meta_cache( $user_id );

        Logger::audit( 'opportunity_unsaved', 'Investor removed bookmarked opportunity', [
            'user_id'        => $user_id,
            'opportunity_id' => $opportunity_id,
        ] );

        return true;
    }

    /**
     * Check if an opportunity is saved by a user
     *
     * @param int $user_id
     * @param int $opportunity_id
     * @return bool
     */
    public static function is_saved( $user_id, $opportunity_id ) {
        global $wpdb;

        $user_id        = absint( $user_id );
        $opportunity_id = absint( $opportunity_id );

        if ( ! $user_id || ! $opportunity_id ) {
            return false;
        }

        $table = Constants::get_table_name( Constants::TABLE_SAVED_OPPORTUNITIES );

        $count = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$table} WHERE user_id = %d AND opportunity_id = %d",
                $user_id,
                $opportunity_id
            )
        );

        return (int) $count > 0;
    }

    /**
     * Get list of saved opportunities for an investor
     * Only returns published opportunities to prevent data leaks.
     *
     * @param int $user_id
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public static function get_saved( $user_id, $limit = 20, $offset = 0 ) {
        global $wpdb;

        $user_id = absint( $user_id );
        $limit   = absint( $limit ) ?: 20;
        $offset  = absint( $offset );

        if ( ! $user_id ) {
            return [];
        }

        $table = Constants::get_table_name( Constants::TABLE_SAVED_OPPORTUNITIES );

        // Secure join with posts table ensuring only published items return
        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT s.opportunity_id, s.created_at as saved_at, p.post_title, p.post_name, p.post_modified
                 FROM {$table} s
                 INNER JOIN {$wpdb->posts} p ON s.opportunity_id = p.ID
                 WHERE s.user_id = %d
                   AND p.post_status = 'publish'
                   AND p.post_type = %s
                 ORDER BY s.created_at DESC
                 LIMIT %d OFFSET %d",
                $user_id,
                Constants::POST_TYPE_OPPORTUNITY,
                $limit,
                $offset
            )
        );

        $results = [];
        if ( ! empty( $rows ) ) {
            foreach ( $rows as $row ) {
                $opp_id = (int) $row->opportunity_id;
                $opp    = OpportunityService::get_opportunity( $opp_id );

                if ( ! $opp ) {
                    $company  = get_post_meta( $opp_id, '_cin_company_name', true ) ?: $row->post_title;
                    $sector   = get_post_meta( $opp_id, '_cin_sector', true ) ?: 'General';
                    $location = get_post_meta( $opp_id, '_cin_company_city', true ) ?: 'Cuba';
                    $capital  = get_post_meta( $opp_id, '_cin_capital_required', true ) ?: 0;
                    $currency = get_post_meta( $opp_id, '_cin_currency', true ) ?: 'USD';

                    $results[] = [
                        'id'             => $opp_id,
                        'title'          => $row->post_title,
                        'slug'           => $row->post_name,
                        'url'            => home_url( '/opportunity/' . $row->post_name . '/' ),
                        'business_name'  => $company,
                        'sector'         => $sector,
                        'location'       => $location,
                        'capital_sought' => (float) $capital,
                        'currency'       => $currency,
                        'last_updated'   => date_i18n( 'F Y', strtotime( $row->post_modified ) ),
                        'saved_at'       => $row->saved_at,
                    ];
                } else {
                    $results[] = [
                        'id'             => $opp_id,
                        'title'          => $opp['title'],
                        'slug'           => $opp['slug'],
                        'url'            => home_url( '/opportunity/' . $opp['slug'] . '/' ),
                        'business_name'  => ! empty( $opp['company_name'] ) ? $opp['company_name'] : $opp['title'],
                        'sector'         => ! empty( $opp['sector_name'] ) ? $opp['sector_name'] : 'Diversified',
                        'location'       => ! empty( $opp['province'] ) ? $opp['province'] . ', Cuba' : 'Cuba',
                        'capital_sought' => (float) ( $opp['capital_required'] ?? 0 ),
                        'currency'       => $opp['currency'] ?? 'USD',
                        'last_updated'   => ! empty( $opp['updated_at'] ) ? date_i18n( 'F Y', strtotime( $opp['updated_at'] ) ) : date_i18n( 'F Y', strtotime( $row->post_modified ) ),
                        'saved_at'       => $row->saved_at,
                    ];
                }
            }
        }

        return $results;
    }

    /**
     * Get count of saved published opportunities for a user
     *
     * @param int $user_id
     * @return int
     */
    public static function count( $user_id ) {
        global $wpdb;

        $user_id = absint( $user_id );
        if ( ! $user_id ) {
            return 0;
        }

        $table = Constants::get_table_name( Constants::TABLE_SAVED_OPPORTUNITIES );

        $count = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*)
                 FROM {$table} s
                 INNER JOIN {$wpdb->posts} p ON s.opportunity_id = p.ID
                 WHERE s.user_id = %d
                   AND p.post_status = 'publish'
                   AND p.post_type = %s",
                $user_id,
                Constants::POST_TYPE_OPPORTUNITY
            )
        );

        return (int) $count;
    }

    /**
     * Sync user meta cache for quick dashboard metrics
     *
     * @param int $user_id
     */
    public static function sync_user_meta_cache( $user_id ) {
        global $wpdb;

        $table = Constants::get_table_name( Constants::TABLE_SAVED_OPPORTUNITIES );
        $ids   = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT s.opportunity_id
                 FROM {$table} s
                 INNER JOIN {$wpdb->posts} p ON s.opportunity_id = p.ID
                 WHERE s.user_id = %d
                   AND p.post_status = 'publish'",
                $user_id
            )
        );

        update_user_meta( $user_id, '_cin_saved_opportunities', array_map( 'absint', (array) $ids ) );
    }
}
