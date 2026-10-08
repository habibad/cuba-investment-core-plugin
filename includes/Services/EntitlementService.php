<?php
/**
 * Cuba Investment Core - Membership & Entitlement Service
 *
 * Designed strictly for the launch version: 100% FREE.
 * Implements an extensible feature entitlement engine that decouples
 * business capabilities from billing gateways, supporting future premium tiers
 * via filterable providers without any current payment processing dependencies.
 *
 * @package CubaInvestment\Core
 */

namespace CubaInvestment\Core\Services;

use CubaInvestment\Core\Common\Constants;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class EntitlementService {

    /**
     * Launch free tier configuration
     *
     * @var array
     */
    protected static $launch_free_features = [
        'view_opportunities'    => [ 'enabled' => true, 'quota' => -1 ], // -1 = unlimited
        'submit_opportunity'    => [ 'enabled' => true, 'quota' => 5 ],  // Up to 5 active listings
        'send_inquiry'          => [ 'enabled' => true, 'quota' => 25 ], // Up to 25 inquiries/month
        'direct_connections'    => [ 'enabled' => true, 'quota' => -1 ], // Unlimited connections
        'private_messaging'     => [ 'enabled' => true, 'quota' => -1 ], // Unlimited direct messages
        'view_pitch_documents'  => [ 'enabled' => true, 'quota' => -1 ], // Accessible to connected investors
    ];

    /**
     * Get user's current membership tier
     *
     * @param int $user_id
     * @return string
     */
    public static function get_tier( $user_id ) {
        if ( ! $user_id ) {
            return Constants::TIER_FREE;
        }

        $tier = get_user_meta( $user_id, '_cin_membership_tier', true );
        if ( empty( $tier ) ) {
            $tier = Constants::TIER_LAUNCH;
        }

        /**
         * Filter user membership tier for future extension plugins
         *
         * @param string $tier Tier slug
         * @param int    $user_id User ID
         */
        return apply_filters( 'cin_user_membership_tier', $tier, $user_id );
    }

    /**
     * Get all feature entitlements for a user
     *
     * @param int $user_id
     * @return array
     */
    public static function get_entitlements( $user_id ) {
        $tier = self::get_tier( $user_id );

        // By default during Launch Phase, all users enjoy full Launch Free capabilities
        $entitlements = self::$launch_free_features;

        /**
         * Filter user entitlements (future premium addons can override quotas or features)
         *
         * @param array  $entitlements
         * @param int    $user_id
         * @param string $tier
         */
        return apply_filters( 'cin_user_entitlements', $entitlements, $user_id, $tier );
    }

    /**
     * Check if user has a feature enabled
     *
     * @param int $user_id
     * @param string $feature_slug
     * @return bool
     */
    public static function has_feature( $user_id, $feature_slug ) {
        $entitlements = self::get_entitlements( $user_id );

        if ( ! isset( $entitlements[ $feature_slug ] ) ) {
            return false;
        }

        return (bool) $entitlements[ $feature_slug ]['enabled'];
    }

    /**
     * Check if user can perform a feature action under current quota
     *
     * @param int $user_id
     * @param string $feature_slug
     * @return bool
     */
    public static function can_perform( $user_id, $feature_slug ) {
        if ( ! self::has_feature( $user_id, $feature_slug ) ) {
            return false;
        }

        $entitlements = self::get_entitlements( $user_id );
        $quota        = (int) $entitlements[ $feature_slug ]['quota'];

        // -1 represents unlimited
        if ( -1 === $quota ) {
            return true;
        }

        $usage = self::get_usage( $user_id, $feature_slug );

        $allowed = $usage < $quota;

        return apply_filters( 'cin_can_perform_feature', $allowed, $user_id, $feature_slug, $usage, $quota );
    }

    /**
     * Calculate current monthly usage for quota tracking
     *
     * @param int $user_id
     * @param string $feature_slug
     * @return int
     */
    public static function get_usage( $user_id, $feature_slug ) {
        global $wpdb;

        $first_of_month = gmdate( 'Y-m-01 00:00:00' );

        switch ( $feature_slug ) {
            case 'submit_opportunity':
                // Active listings owned by user
                $count = (int) $wpdb->get_var(
                    $wpdb->prepare(
                        "SELECT COUNT(*) FROM {$wpdb->posts}
                         WHERE post_author = %d AND post_type = %s AND post_status IN ('publish', 'pending')",
                        $user_id,
                        Constants::POST_TYPE_OPPORTUNITY
                    )
                );
                return $count;

            case 'send_inquiry':
                // Inquiries sent this month
                $table = Constants::get_table_name( Constants::TABLE_INQUIRIES );
                $count = (int) $wpdb->get_var(
                    $wpdb->prepare(
                        "SELECT COUNT(*) FROM {$table}
                         WHERE investor_user_id = %d AND created_at >= %s",
                        $user_id,
                        $first_of_month
                    )
                );
                return $count;

            default:
                return 0;
        }
    }
}
