<?php
/**
 * Cuba Investment Core - Roles and Capabilities
 *
 * @package CubaInvestment\Core
 */

namespace CubaInvestment\Core\Auth;

use CubaInvestment\Core\Common\Constants;
use CubaInvestment\Core\Common\Logger;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Roles {

    /**
     * Register roles and capabilities
     */
    public static function register() {
        // 1. Investor Role
        add_role(
            Constants::ROLE_INVESTOR,
            __( 'Investor', 'cuba-investment-core' ),
            self::get_investor_capabilities()
        );

        // 2. Business Owner Role
        add_role(
            Constants::ROLE_BUSINESS_OWNER,
            __( 'Business Owner', 'cuba-investment-core' ),
            self::get_business_owner_capabilities()
        );

        // 3. Grant all capabilities to Administrator
        self::grant_admin_capabilities();

        Logger::info( 'Roles and capabilities registered' );
    }

    /**
     * Investor Capabilities
     *
     * @return array
     */
    public static function get_investor_capabilities() {
        return [
            'read'                             => true,
            Constants::CAP_ACCESS_INVESTOR_PORTAL => true,
            Constants::CAP_VIEW_OPPORTUNITIES     => true,
            Constants::CAP_SEND_INQUIRIES         => true,
            Constants::CAP_MANAGE_CONNECTIONS     => true,
            Constants::CAP_SEND_MESSAGES          => true,
            Constants::CAP_VIEW_DOCUMENTS         => true,
        ];
    }

    /**
     * Business Owner Capabilities
     *
     * @return array
     */
    public static function get_business_owner_capabilities() {
        return [
            'read'                             => true,
            'upload_files'                     => true,
            Constants::CAP_ACCESS_BUSINESS_PORTAL => true,
            Constants::CAP_SUBMIT_OPPORTUNITY     => true,
            Constants::CAP_EDIT_OWN_OPPORTUNITY   => true,
            Constants::CAP_DELETE_OWN_OPPORTUNITY => true,
            Constants::CAP_VIEW_INQUIRIES         => true,
            Constants::CAP_MANAGE_CONNECTIONS     => true,
            Constants::CAP_SEND_MESSAGES          => true,
            Constants::CAP_UPLOAD_DOCUMENTS       => true,
        ];
    }

    /**
     * Grant capabilities to WordPress Administrator
     */
    public static function grant_admin_capabilities() {
        $admin = get_role( 'administrator' );
        if ( ! $admin ) {
            return;
        }

        $all_caps = [
            Constants::CAP_ACCESS_INVESTOR_PORTAL,
            Constants::CAP_ACCESS_BUSINESS_PORTAL,
            Constants::CAP_VIEW_OPPORTUNITIES,
            Constants::CAP_SUBMIT_OPPORTUNITY,
            Constants::CAP_EDIT_OWN_OPPORTUNITY,
            Constants::CAP_DELETE_OWN_OPPORTUNITY,
            Constants::CAP_SEND_INQUIRIES,
            Constants::CAP_VIEW_INQUIRIES,
            Constants::CAP_MANAGE_CONNECTIONS,
            Constants::CAP_SEND_MESSAGES,
            Constants::CAP_VIEW_DOCUMENTS,
            Constants::CAP_UPLOAD_DOCUMENTS,
            Constants::CAP_REVIEW_OPPORTUNITIES,
            Constants::CAP_PUBLISH_OPPORTUNITIES,
            Constants::CAP_MODERATE_PLATFORM,
            Constants::CAP_VIEW_AUDIT_LOGS,
        ];

        foreach ( $all_caps as $cap ) {
            $admin->add_cap( $cap );
        }
    }

    /**
     * Remove custom capabilities from Administrator on deactivation
     */
    public static function remove_admin_capabilities() {
        $admin = get_role( 'administrator' );
        if ( ! $admin ) {
            return;
        }

        $all_caps = [
            Constants::CAP_ACCESS_INVESTOR_PORTAL,
            Constants::CAP_ACCESS_BUSINESS_PORTAL,
            Constants::CAP_VIEW_OPPORTUNITIES,
            Constants::CAP_SUBMIT_OPPORTUNITY,
            Constants::CAP_EDIT_OWN_OPPORTUNITY,
            Constants::CAP_DELETE_OWN_OPPORTUNITY,
            Constants::CAP_SEND_INQUIRIES,
            Constants::CAP_VIEW_INQUIRIES,
            Constants::CAP_MANAGE_CONNECTIONS,
            Constants::CAP_SEND_MESSAGES,
            Constants::CAP_VIEW_DOCUMENTS,
            Constants::CAP_UPLOAD_DOCUMENTS,
            Constants::CAP_REVIEW_OPPORTUNITIES,
            Constants::CAP_PUBLISH_OPPORTUNITIES,
            Constants::CAP_MODERATE_PLATFORM,
            Constants::CAP_VIEW_AUDIT_LOGS,
        ];

        foreach ( $all_caps as $cap ) {
            $admin->remove_cap( $cap );
        }
    }

    /**
     * Unregister roles (used when purging)
     */
    public static function unregister() {
        remove_role( Constants::ROLE_INVESTOR );
        remove_role( Constants::ROLE_BUSINESS_OWNER );
        self::remove_admin_capabilities();
    }
}
