<?php
/**
 * Cuba Investment Core - Email Verification System
 *
 * @package CubaInvestment\Core
 */

namespace CubaInvestment\Core\Auth;

use CubaInvestment\Core\Common\Logger;
use CubaInvestment\Core\Common\Mailer;
use CubaInvestment\Core\API\Middleware\RateLimiter;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class EmailVerification {

    /**
     * Token validity in seconds (24 hours)
     */
    const TOKEN_LIFETIME = 86400;

    /**
     * Generate and store verification token for user
     *
     * @param int $user_id
     * @return string Raw token (to send via email only, never stored raw)
     */
    public static function create_token( $user_id ) {
        $raw_token = bin2hex( random_bytes( 32 ) ); // 64 hex characters
        $token_hash = hash( 'sha256', $raw_token );
        $expires_at = time() + self::TOKEN_LIFETIME;

        update_user_meta( $user_id, '_cin_verification_token_hash', $token_hash );
        update_user_meta( $user_id, '_cin_verification_token_expires', $expires_at );
        update_user_meta( $user_id, '_cin_verification_sent_at', current_time( 'mysql' ) );

        return $raw_token;
    }

    /**
     * Build verification URL
     *
     * @param int    $user_id
     * @param string $raw_token
     * @return string
     */
    public static function get_verification_url( $user_id, $raw_token ) {
        return add_query_arg(
            [
                'uid'   => (int) $user_id,
                'token' => $raw_token,
            ],
            home_url( '/verify-email/' )
        );
    }

    /**
     * Verify email with token
     *
     * @param int    $user_id
     * @param string $raw_token
     * @return true|\WP_Error
     */
    public static function verify( $user_id, $raw_token ) {
        $user = get_userdata( $user_id );
        if ( ! $user ) {
            return new \WP_Error( 'invalid_user', __( 'Invalid verification link.', 'cuba-investment-core' ) );
        }

        $stored_hash = get_user_meta( $user_id, '_cin_verification_token_hash', true );
        $expires_at  = (int) get_user_meta( $user_id, '_cin_verification_token_expires', true );

        if ( empty( $stored_hash ) || empty( $expires_at ) ) {
            return new \WP_Error( 'token_already_used', __( 'This verification link is invalid or has already been used.', 'cuba-investment-core' ) );
        }

        if ( time() > $expires_at ) {
            return new \WP_Error( 'token_expired', __( 'Your email verification link has expired. Please request a new verification email.', 'cuba-investment-core' ) );
        }

        $calculated_hash = hash( 'sha256', $raw_token );
        if ( ! hash_equals( $stored_hash, $calculated_hash ) ) {
            Logger::warning( 'Invalid email verification token attempted', [ 'user_id' => $user_id ] );
            return new \WP_Error( 'invalid_token', __( 'Invalid email verification token.', 'cuba-investment-core' ) );
        }

        // Token is valid! Mark verified and activate account
        update_user_meta( $user_id, '_cin_email_verified', 1 );
        update_user_meta( $user_id, '_cin_email_verified_at', current_time( 'mysql' ) );
        update_user_meta( $user_id, '_cin_account_status', 'active' );

        // Delete token to ensure single-use
        delete_user_meta( $user_id, '_cin_verification_token_hash' );
        delete_user_meta( $user_id, '_cin_verification_token_expires' );

        Logger::audit( 'email_verified', 'User email successfully verified', [ 'user_id' => $user_id ] );

        // Send welcome email
        Mailer::send_welcome_verified_email( $user_id );

        return true;
    }

    /**
     * Resend verification email with rate limiting & anti-enumeration protection
     *
     * @param string $email
     * @return array [ 'success' => bool, 'message' => string ]
     */
    public static function resend( $email ) {
        $email = sanitize_email( $email );

        // Rate limit: max 3 attempts per hour per IP/email
        if ( ! RateLimiter::check( 'resend_verification', 3, 3600 ) ) {
            return [
                'success' => false,
                'message' => __( 'Too many resend requests. Please wait an hour before trying again.', 'cuba-investment-core' ),
            ];
        }

        $neutral_message = __( 'If an unverified account exists for this email address, a new verification link has been sent.', 'cuba-investment-core' );

        if ( ! is_email( $email ) ) {
            return [ 'success' => true, 'message' => $neutral_message ];
        }

        $user = get_user_by( 'email', $email );
        if ( ! $user ) {
            // Anti-enumeration: neutral response
            return [ 'success' => true, 'message' => $neutral_message ];
        }

        $status = get_user_meta( $user->ID, '_cin_account_status', true );
        $verified = (bool) get_user_meta( $user->ID, '_cin_email_verified', true );

        if ( $verified || 'active' === $status ) {
            // Already active
            return [
                'success' => true,
                'message' => __( 'This account is already verified. You may proceed to log in.', 'cuba-investment-core' ),
            ];
        }

        // Generate fresh token
        $token = self::create_token( $user->ID );
        $role  = Permissions::is_investor( $user->ID ) ? 'investor' : 'business_owner';

        // Dispatch email
        Mailer::send_verification_email( $user->ID, $token, $role );

        Logger::audit( 'verification_resent', 'Verification email resent', [ 'user_id' => $user->ID ] );

        return [ 'success' => true, 'message' => $neutral_message ];
    }
}
