<?php
/**
 * Cuba Investment Core - Nonce & CSRF Protection
 *
 * @package CubaInvestment\Core
 */

namespace CubaInvestment\Core\Security;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class NonceManager {

    const ACTION_AUTH       = 'cin_auth_action';
    const ACTION_PROFILE    = 'cin_profile_action';
    const ACTION_OPPORTUNITY= 'cin_opportunity_action';
    const ACTION_INQUIRY    = 'cin_inquiry_action';
    const ACTION_MESSAGE    = 'cin_message_action';
    const ACTION_DOCUMENT   = 'cin_document_action';
    const ACTION_ADMIN      = 'cin_admin_action';

    /**
     * Create nonce for action
     *
     * @param string $action
     * @return string
     */
    public static function create( $action ) {
        return wp_create_nonce( $action );
    }

    /**
     * Verify nonce for action
     *
     * @param string $nonce
     * @param string $action
     * @return bool
     */
    public static function verify( $nonce, $action ) {
        if ( empty( $nonce ) ) {
            return false;
        }

        return (bool) wp_verify_nonce( $nonce, $action );
    }

    /**
     * Output hidden nonce field
     *
     * @param string $action
     * @param string $name
     * @return void
     */
    public static function field( $action, $name = '_cin_nonce' ) {
        wp_nonce_field( $action, $name );
    }
}
