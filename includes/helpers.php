<?php
/**
 * Cuba Investment Core - Global Helper & Template Functions
 *
 * @package CubaInvestment\Core
 */

use CubaInvestment\Core\Auth\Permissions;
use CubaInvestment\Core\Services\ProfileService;
use CubaInvestment\Core\Services\OpportunityService;
use CubaInvestment\Core\Services\NotificationService;
use CubaInvestment\Core\Services\EntitlementService;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! function_exists( 'cin_is_investor' ) ) {
    /**
     * Check if user is an investor
     *
     * @param int|null $user_id
     * @return bool
     */
    function cin_is_investor( $user_id = null ) {
        return Permissions::is_investor( $user_id );
    }
}

if ( ! function_exists( 'cin_is_business_owner' ) ) {
    /**
     * Check if user is a business owner
     *
     * @param int|null $user_id
     * @return bool
     */
    function cin_is_business_owner( $user_id = null ) {
        return Permissions::is_business_owner( $user_id );
    }
}

if ( ! function_exists( 'cin_get_user_profile' ) ) {
    /**
     * Get structured user profile
     *
     * @param int|null $user_id
     * @return array
     */
    function cin_get_user_profile( $user_id = null ) {
        $user_id = $user_id ?: get_current_user_id();
        return ProfileService::get_profile( $user_id );
    }
}

if ( ! function_exists( 'cin_get_unread_notifications_count' ) ) {
    /**
     * Get unread notification count
     *
     * @param int|null $user_id
     * @return int
     */
    function cin_get_unread_notifications_count( $user_id = null ) {
        $user_id = $user_id ?: get_current_user_id();
        return NotificationService::get_unread_count( $user_id );
    }
}

if ( ! function_exists( 'cin_user_has_entitlement' ) ) {
    /**
     * Check user entitlement
     *
     * @param string $feature
     * @param int|null $user_id
     * @return bool
     */
    function cin_user_has_entitlement( $feature, $user_id = null ) {
        $user_id = $user_id ?: get_current_user_id();
        return EntitlementService::has_feature( $user_id, $feature );
    }
}

if ( ! function_exists( 'cin_get_opportunities' ) ) {
    /**
     * Retrieve published opportunities
     *
     * @param array $args
     * @return array
     */
    function cin_get_opportunities( $args = [] ) {
        return OpportunityService::get_opportunities( $args );
    }
}
