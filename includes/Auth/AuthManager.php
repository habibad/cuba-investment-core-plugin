<?php
/**
 * Cuba Investment Core - Authentication Manager
 *
 * Implements native WordPress registration, authentication, password recovery,
 * and session lifecycle for Investors and Business Owners.
 *
 * @package CubaInvestment\Core
 */

namespace CubaInvestment\Core\Auth;

use CubaInvestment\Core\Common\Constants;
use CubaInvestment\Core\Common\Logger;
use CubaInvestment\Core\Common\Mailer;
use CubaInvestment\Core\Security\Sanitizer;
use CubaInvestment\Core\API\Middleware\RateLimiter;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AuthManager {

    /**
     * Validate password strength (min 8 chars, mixed letters and digits)
     *
     * @param string $password
     * @return true|\WP_Error
     */
    public static function validate_password_strength( $password ) {
        if ( strlen( $password ) < 8 ) {
            return new \WP_Error( 'password_too_short', __( 'Password must be at least 8 characters long.', 'cuba-investment-core' ) );
        }

        if ( ! preg_match( '/[A-Za-z]/', $password ) || ! preg_match( '/[0-9]/', $password ) ) {
            return new \WP_Error( 'password_too_simple', __( 'Password must contain both letters and at least one number.', 'cuba-investment-core' ) );
        }

        return true;
    }

    /**
     * Register an Investor
     *
     * @param array $data
     * @return array|\WP_Error
     */
    public static function register_investor( array $data ) {
        // 1. Honeypot check (hidden field to trap automated spambots)
        if ( ! empty( $data['website_hp'] ) ) {
            // Silently act as success to confuse bot
            return [ 'success' => true, 'user_id' => 0, 'email' => '' ];
        }

        // 2. Rate limiting (max 5 registrations per hour per IP)
        if ( ! RateLimiter::check( 'reg_investor', 5, 3600 ) ) {
            return new \WP_Error( 'rate_limited', __( 'Too many registration attempts from this connection. Please try again later.', 'cuba-investment-core' ), [ 'status' => 429 ] );
        }

        // 3. Extract and sanitize fields
        $first_name = isset( $data['first_name'] ) ? sanitize_text_field( trim( $data['first_name'] ) ) : '';
        $last_name  = isset( $data['last_name'] ) ? sanitize_text_field( trim( $data['last_name'] ) ) : '';
        $email      = isset( $data['email'] ) ? sanitize_email( trim( $data['email'] ) ) : '';
        $password   = isset( $data['password'] ) ? (string) $data['password'] : '';
        $confirm_pw = isset( $data['password_confirm'] ) ? (string) $data['password_confirm'] : '';
        $country    = isset( $data['country'] ) ? sanitize_text_field( trim( $data['country'] ) ) : '';
        $terms      = ! empty( $data['terms_agree'] );
        $privacy    = ! empty( $data['privacy_agree'] );
        $marketing  = ! empty( $data['marketing_opt_in'] );

        // 4. Validate Required Fields
        if ( empty( $first_name ) || strlen( $first_name ) < 2 ) {
            return new \WP_Error( 'missing_first_name', __( 'Please provide your first name (minimum 2 characters).', 'cuba-investment-core' ) );
        }
        if ( empty( $last_name ) || strlen( $last_name ) < 2 ) {
            return new \WP_Error( 'missing_last_name', __( 'Please provide your last name (minimum 2 characters).', 'cuba-investment-core' ) );
        }
        if ( empty( $email ) || ! is_email( $email ) ) {
            return new \WP_Error( 'invalid_email', __( 'Please provide a valid email address.', 'cuba-investment-core' ) );
        }
        if ( email_exists( $email ) ) {
            return new \WP_Error( 'email_exists', __( 'An account with this email address already exists. Please log in or use password recovery.', 'cuba-investment-core' ) );
        }
        if ( empty( $country ) ) {
            return new \WP_Error( 'missing_country', __( 'Please select your country of residence.', 'cuba-investment-core' ) );
        }
        if ( ! $terms ) {
            return new \WP_Error( 'terms_required', __( 'You must agree to the Terms of Service to create an account.', 'cuba-investment-core' ) );
        }
        if ( ! $privacy ) {
            return new \WP_Error( 'privacy_required', __( 'You must acknowledge the Privacy Policy to create an account.', 'cuba-investment-core' ) );
        }

        // 5. Password Validation
        $pw_check = self::validate_password_strength( $password );
        if ( is_wp_error( $pw_check ) ) {
            return $pw_check;
        }
        if ( $password !== $confirm_pw ) {
            return new \WP_Error( 'password_mismatch', __( 'The password confirmation does not match the password entered.', 'cuba-investment-core' ) );
        }

        // 6. Generate unique username from email
        $username = sanitize_user( current( explode( '@', $email ) ), true );
        if ( empty( $username ) || username_exists( $username ) ) {
            $username = 'cin_' . wp_generate_password( 8, false );
        }

        // 7. Create WordPress User
        $user_id = wp_insert_user( [
            'user_login'   => $username,
            'user_pass'    => $password,
            'user_email'   => $email,
            'first_name'   => $first_name,
            'last_name'    => $last_name,
            'display_name' => "{$first_name} {$last_name}",
            'role'         => Constants::ROLE_INVESTOR,
        ] );

        if ( is_wp_error( $user_id ) ) {
            Logger::error( 'Investor registration user creation failed', [ 'email' => $email, 'error' => $user_id->get_error_message() ] );
            return $user_id;
        }

        // 8. Save User Metadata & Legal Consent
        update_user_meta( $user_id, '_cin_user_type', 'investor' );
        update_user_meta( $user_id, '_cin_country_of_residence', $country );
        update_user_meta( $user_id, '_cin_marketing_opt_in', $marketing ? 1 : 0 );
        update_user_meta( $user_id, '_cin_terms_accepted_at', current_time( 'mysql' ) );
        update_user_meta( $user_id, '_cin_terms_version', '1.0' );
        update_user_meta( $user_id, '_cin_privacy_accepted_at', current_time( 'mysql' ) );
        update_user_meta( $user_id, '_cin_registered_at', current_time( 'mysql' ) );

        // Account status: Pending Verification
        update_user_meta( $user_id, '_cin_account_status', 'pending_verification' );
        update_user_meta( $user_id, '_cin_email_verified', 0 );

        // Free Launch Membership Initialization
        self::initialize_free_membership( $user_id );

        // 9. Generate Verification Token and Send Email
        $token = EmailVerification::create_token( $user_id );
        Mailer::send_verification_email( $user_id, $token, 'investor' );

        // 10. Audit Logging
        Logger::audit( 'investor_registered', 'Investor registered and verification email sent', [
            'user_id' => $user_id,
            'email'   => $email,
            'country' => $country,
        ] );

        return [
            'success'            => true,
            'user_id'            => $user_id,
            'email'              => $email,
            'verification_token' => $token,
            'message'            => __( 'Registration successful. A verification link has been sent to your email address.', 'cuba-investment-core' ),
        ];
    }

    /**
     * Register a Business Owner
     *
     * @param array $data
     * @return array|\WP_Error
     */
    public static function register_business_owner( array $data ) {
        // 1. Honeypot check
        if ( ! empty( $data['website_hp'] ) ) {
            return [ 'success' => true, 'user_id' => 0, 'email' => '' ];
        }

        // 2. Rate limiting
        if ( ! RateLimiter::check( 'reg_business', 5, 3600 ) ) {
            return new \WP_Error( 'rate_limited', __( 'Too many registration attempts from this connection. Please try again later.', 'cuba-investment-core' ), [ 'status' => 429 ] );
        }

        // 3. Extract and sanitize fields
        $first_name        = isset( $data['first_name'] ) ? sanitize_text_field( trim( $data['first_name'] ) ) : '';
        $last_name         = isset( $data['last_name'] ) ? sanitize_text_field( trim( $data['last_name'] ) ) : '';
        $email             = isset( $data['email'] ) ? sanitize_email( trim( $data['email'] ) ) : '';
        $password          = isset( $data['password'] ) ? (string) $data['password'] : '';
        $confirm_pw        = isset( $data['password_confirm'] ) ? (string) $data['password_confirm'] : '';
        $business_name     = isset( $data['business_name'] ) ? sanitize_text_field( trim( $data['business_name'] ) ) : '';
        $business_location = isset( $data['business_location'] ) ? sanitize_text_field( trim( $data['business_location'] ) ) : '';
        $terms             = ! empty( $data['terms_agree'] );
        $privacy           = ! empty( $data['privacy_agree'] );

        // 4. Validate Required Fields
        if ( empty( $first_name ) || strlen( $first_name ) < 2 ) {
            return new \WP_Error( 'missing_first_name', __( 'Please provide your first name (minimum 2 characters).', 'cuba-investment-core' ) );
        }
        if ( empty( $last_name ) || strlen( $last_name ) < 2 ) {
            return new \WP_Error( 'missing_last_name', __( 'Please provide your last name (minimum 2 characters).', 'cuba-investment-core' ) );
        }
        if ( empty( $email ) || ! is_email( $email ) ) {
            return new \WP_Error( 'invalid_email', __( 'Please provide a valid email address.', 'cuba-investment-core' ) );
        }
        if ( email_exists( $email ) ) {
            return new \WP_Error( 'email_exists', __( 'An account with this email address already exists. Please log in or use password recovery.', 'cuba-investment-core' ) );
        }
        if ( empty( $business_name ) || strlen( $business_name ) < 2 ) {
            return new \WP_Error( 'missing_business_name', __( 'Please enter the name of your business or registered enterprise.', 'cuba-investment-core' ) );
        }
        if ( empty( $business_location ) ) {
            return new \WP_Error( 'missing_business_location', __( 'Please select or enter the location / province of your business.', 'cuba-investment-core' ) );
        }
        if ( ! $terms ) {
            return new \WP_Error( 'terms_required', __( 'You must agree to the Terms of Service to create an account.', 'cuba-investment-core' ) );
        }
        if ( ! $privacy ) {
            return new \WP_Error( 'privacy_required', __( 'You must acknowledge the Privacy Policy to create an account.', 'cuba-investment-core' ) );
        }

        // 5. Password Validation
        $pw_check = self::validate_password_strength( $password );
        if ( is_wp_error( $pw_check ) ) {
            return $pw_check;
        }
        if ( $password !== $confirm_pw ) {
            return new \WP_Error( 'password_mismatch', __( 'The password confirmation does not match the password entered.', 'cuba-investment-core' ) );
        }

        // 6. Generate unique username
        $username = sanitize_user( current( explode( '@', $email ) ), true );
        if ( empty( $username ) || username_exists( $username ) ) {
            $username = 'cin_' . wp_generate_password( 8, false );
        }

        // 7. Create WordPress User
        $user_id = wp_insert_user( [
            'user_login'   => $username,
            'user_pass'    => $password,
            'user_email'   => $email,
            'first_name'   => $first_name,
            'last_name'    => $last_name,
            'display_name' => "{$first_name} {$last_name}",
            'role'         => Constants::ROLE_BUSINESS_OWNER,
        ] );

        if ( is_wp_error( $user_id ) ) {
            Logger::error( 'Business Owner registration user creation failed', [ 'email' => $email, 'error' => $user_id->get_error_message() ] );
            return $user_id;
        }

        // 8. Save User Metadata & Legal Consent
        update_user_meta( $user_id, '_cin_user_type', 'business_owner' );
        update_user_meta( $user_id, '_cin_company_name', $business_name );
        update_user_meta( $user_id, '_cin_business_name', $business_name );
        update_user_meta( $user_id, '_cin_company_location_province', Sanitizer::province( $business_location ) );
        update_user_meta( $user_id, '_cin_business_location', Sanitizer::province( $business_location ) );
        update_user_meta( $user_id, '_cin_terms_accepted_at', current_time( 'mysql' ) );
        update_user_meta( $user_id, '_cin_terms_version', '1.0' );
        update_user_meta( $user_id, '_cin_privacy_accepted_at', current_time( 'mysql' ) );
        update_user_meta( $user_id, '_cin_registered_at', current_time( 'mysql' ) );

        // Account status: Pending Verification
        update_user_meta( $user_id, '_cin_account_status', 'pending_verification' );
        update_user_meta( $user_id, '_cin_email_verified', 0 );

        // Free Launch Membership Initialization
        self::initialize_free_membership( $user_id );

        // 9. Generate Verification Token and Send Email
        $token = EmailVerification::create_token( $user_id );
        Mailer::send_verification_email( $user_id, $token, 'business_owner' );

        // 10. Audit Logging
        Logger::audit( 'business_owner_registered', 'Business owner registered and verification email sent', [
            'user_id'       => $user_id,
            'email'         => $email,
            'business_name' => $business_name,
        ] );

        return [
            'success'            => true,
            'user_id'            => $user_id,
            'email'              => $email,
            'verification_token' => $token,
            'message'            => __( 'Registration successful. A verification link has been sent to your email address.', 'cuba-investment-core' ),
        ];
    }

    /**
     * Authenticate user with credentials, rate limiting, and verification check
     *
     * @param string $username_or_email
     * @param string $password
     * @param bool   $remember
     * @return array|\WP_Error
     */
    public static function login( $username_or_email, $password, $remember = false ) {
        $username_or_email = sanitize_text_field( trim( $username_or_email ) );
        $password          = (string) $password;

        if ( empty( $username_or_email ) || empty( $password ) ) {
            return new \WP_Error( 'missing_fields', __( 'Please provide both your email/username and password.', 'cuba-investment-core' ) );
        }

        // Rate Limiting (max 5 failed attempts per 15 minutes per IP)
        $client_ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : 'unknown';
        $lock_key  = 'cin_login_fails_' . md5( $client_ip );
        $fails     = (int) get_transient( $lock_key );

        if ( $fails >= 5 ) {
            Logger::warning( 'Login blocked due to rate limit threshold', [ 'ip' => $client_ip ] );
            return new \WP_Error(
                'rate_limited',
                __( 'Too many unsuccessful login attempts. For security reasons, please wait 15 minutes before trying again.', 'cuba-investment-core' ),
                [ 'status' => 429 ]
            );
        }

        // Authenticate with WordPress
        $user = wp_authenticate( $username_or_email, $password );

        if ( is_wp_error( $user ) ) {
            if ( 'account_suspended' === $user->get_error_code() ) {
                return $user;
            }

            // Increment failed attempt counter (expires in 15 mins)
            set_transient( $lock_key, $fails + 1, 900 );
            Logger::warning( 'Failed login credentials attempt', [ 'identifier' => substr( $username_or_email, 0, 3 ) . '***' ] );

            return new \WP_Error(
                'invalid_credentials',
                __( 'Invalid email address, username, or password. Please try again or use the password reset link.', 'cuba-investment-core' ),
                [ 'status' => 401 ]
            );
        }

        // Check account verification status for Investors & Business Owners
        $status = get_user_meta( $user->ID, '_cin_account_status', true );

        if ( 'pending_verification' === $status ) {
            return new \WP_Error(
                'pending_verification',
                __( 'Your account is pending email verification. Please click the link sent to your email address, or request a new verification email.', 'cuba-investment-core' ),
                [
                    'status' => 403,
                    'email'  => $user->user_email,
                ]
            );
        }

        if ( 'suspended' === $status || 'disabled' === $status ) {
            Logger::warning( 'Blocked login attempt from suspended account', [ 'user_id' => $user->ID ] );
            return new \WP_Error(
                'account_suspended',
                __( 'Your account access has been suspended. Please contact platform support for assistance.', 'cuba-investment-core' ),
                [ 'status' => 403 ]
            );
        }

        // Clear failed attempts counter on success
        delete_transient( $lock_key );

        // Establish WordPress secure auth session
        wp_set_current_user( $user->ID );
        wp_set_auth_cookie( $user->ID, (bool) $remember );

        // Determine destination redirect based on role
        $redirect = self::get_user_dashboard_url( $user );

        Logger::audit( 'user_login', 'User logged in successfully', [
            'user_id' => $user->ID,
            'role'    => current( (array) $user->roles ),
        ] );

        return [
            'success'  => true,
            'user_id'  => $user->ID,
            'role'     => current( (array) $user->roles ),
            'redirect' => $redirect,
        ];
    }

    /**
     * Get authorized dashboard URL based on user role
     *
     * @param \WP_User|int $user
     * @return string
     */
    public static function get_user_dashboard_url( $user ) {
        if ( is_numeric( $user ) ) {
            $user = get_userdata( $user );
        }

        if ( ! $user ) {
            return home_url( '/' );
        }

        $roles = (array) $user->roles;

        if ( in_array( Constants::ROLE_INVESTOR, $roles, true ) ) {
            return home_url( '/investor/dashboard/' );
        }

        if ( in_array( Constants::ROLE_BUSINESS_OWNER, $roles, true ) ) {
            return home_url( '/business-owner/dashboard/' );
        }

        if ( in_array( 'administrator', $roles, true ) ) {
            return home_url( '/business-owner/dashboard/' );
        }

        return home_url( '/dashboard/' );
    }

    /**
     * Initialize free launch membership for user
     *
     * @param int $user_id
     */
    public static function initialize_free_membership( $user_id ) {
        global $wpdb;

        update_user_meta( $user_id, '_cin_membership_tier', Constants::TIER_LAUNCH );
        update_user_meta( $user_id, '_cin_membership_status', 'active' );
        update_user_meta( $user_id, '_cin_membership_started_at', current_time( 'mysql' ) );

        // Insert subscription record into wp_cin_subscriptions
        $table = Constants::get_table_name( Constants::TABLE_SUBSCRIPTIONS );
        $now   = current_time( 'mysql' );

        $wpdb->insert(
            $table,
            [
                'user_id'    => (int) $user_id,
                'tier_slug'  => Constants::TIER_LAUNCH,
                'status'     => 'active',
                'starts_at'  => $now,
                'expires_at' => null, // No auto-expiry for launch period
                'features'   => wp_json_encode( [ 'launch_free' => true, 'cost' => 0 ] ),
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [ '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s' ]
        );
    }

    /**
     * Request password reset link (anti-enumeration protected)
     *
     * @param string $email
     * @return array [ 'success' => bool, 'message' => string ]
     */
    public static function request_password_reset( $email ) {
        $email = sanitize_email( trim( $email ) );

        // Rate limit: max 4 requests per hour per IP
        if ( ! RateLimiter::check( 'forgot_password', 4, 3600 ) ) {
            return [
                'success' => false,
                'message' => __( 'Too many password reset requests. Please wait a while before requesting again.', 'cuba-investment-core' ),
            ];
        }

        $neutral_message = __( 'If an account exists for this email address, password reset instructions have been sent.', 'cuba-investment-core' );

        if ( empty( $email ) || ! is_email( $email ) ) {
            return [ 'success' => true, 'message' => $neutral_message ];
        }

        $user = get_user_by( 'email', $email );
        if ( ! $user ) {
            // Anti-enumeration: neutral response
            return [ 'success' => true, 'message' => $neutral_message ];
        }

        // Generate native WordPress password reset key
        $key = get_password_reset_key( $user );
        if ( is_wp_error( $key ) ) {
            Logger::error( 'Password reset key generation error', [ 'user_id' => $user->ID ] );
            return [ 'success' => true, 'message' => $neutral_message ];
        }

        // Send branded password reset email
        Mailer::send_password_reset_email( $user->ID, $key );

        Logger::audit( 'password_reset_sent', 'Password reset email dispatched', [ 'user_id' => $user->ID ] );

        return [ 'success' => true, 'message' => $neutral_message ];
    }

    /**
     * Execute password reset with validated key
     *
     * @param string $key
     * @param string $login
     * @param string $new_password
     * @param string $confirm_password
     * @return true|\WP_Error
     */
    public static function execute_password_reset( $key, $login, $new_password, $confirm_password ) {
        $key   = sanitize_text_field( trim( $key ) );
        $login = sanitize_text_field( trim( $login ) );

        if ( empty( $key ) || empty( $login ) ) {
            return new \WP_Error( 'invalid_key', __( 'Invalid or expired password reset link.', 'cuba-investment-core' ) );
        }

        // Password strength
        $pw_check = self::validate_password_strength( $new_password );
        if ( is_wp_error( $pw_check ) ) {
            return $pw_check;
        }

        if ( $new_password !== $confirm_password ) {
            return new \WP_Error( 'password_mismatch', __( 'New password and password confirmation do not match.', 'cuba-investment-core' ) );
        }

        // Verify key with WordPress native API
        $user = check_password_reset_key( $key, $login );
        if ( is_wp_error( $user ) ) {
            Logger::warning( 'Password reset attempt with invalid/expired key', [ 'login' => $login ] );
            return new \WP_Error( 'invalid_key', __( 'This password reset link is invalid or has expired. Please request a new one.', 'cuba-investment-core' ) );
        }

        // Reset password securely
        reset_password( $user, $new_password );

        Logger::audit( 'password_reset_completed', 'Password successfully reset', [ 'user_id' => $user->ID ] );

        return true;
    }

    /**
     * Logout user securely
     */
    public static function logout() {
        $user_id = get_current_user_id();
        wp_logout();

        if ( $user_id ) {
            Logger::audit( 'user_logout', 'User logged out', [ 'user_id' => $user_id ] );
        }

        wp_safe_redirect( add_query_arg( 'loggedout', 'true', home_url( '/login/' ) ) );
        exit;
    }
}
