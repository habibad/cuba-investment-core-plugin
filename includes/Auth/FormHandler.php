<?php
/**
 * Cuba Investment Core - Form Submission Handler
 *
 * Handles standard synchronous POST submissions and AJAX/REST actions
 * with CSRF nonces, validation error management, and safe redirects.
 *
 * @package CubaInvestment\Core
 */

namespace CubaInvestment\Core\Auth;

use CubaInvestment\Core\Security\NonceManager;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class FormHandler {

    public static function init() {
        add_action( 'init', [ __CLASS__, 'process_form_submission' ] );
    }

    /**
     * Intercept and process auth POST submissions
     */
    public static function process_form_submission() {
        if ( 'POST' !== ( $_SERVER['REQUEST_METHOD'] ?? '' ) ) {
            return;
        }

        if ( ! isset( $_POST['cin_action'] ) ) {
            return;
        }

        $action = sanitize_key( $_POST['cin_action'] );

        // Verify CSRF Nonce
        if ( ! isset( $_POST['_cin_nonce'] ) || ! NonceManager::verify( sanitize_text_field( wp_unslash( $_POST['_cin_nonce'] ) ), $action ) ) {
            wp_die( esc_html__( 'Security token expired or invalid. Please refresh the page and try again.', 'cuba-investment-core' ), 403 );
        }

        switch ( $action ) {
            case 'cin_register_investor':
                self::handle_investor_registration();
                break;

            case 'cin_register_business_owner':
                self::handle_business_owner_registration();
                break;

            case 'cin_login':
                self::handle_login();
                break;

            case 'cin_forgot_password':
                self::handle_forgot_password();
                break;

            case 'cin_reset_password':
                self::handle_reset_password();
                break;

            case 'cin_resend_verification':
                self::handle_resend_verification();
                break;
        }
    }

    protected static function handle_investor_registration() {
        $result = AuthManager::register_investor( wp_unslash( $_POST ) );

        if ( is_wp_error( $result ) ) {
            set_transient( 'cin_auth_error_' . self::get_session_id(), $result->get_error_message(), 60 );
            wp_safe_redirect( home_url( '/register/investor/' ) );
            exit;
        }

        wp_safe_redirect( add_query_arg( [ 'status' => 'registered', 'email' => rawurlencode( $result['email'] ) ], home_url( '/register/investor/' ) ) );
        exit;
    }

    protected static function handle_business_owner_registration() {
        $result = AuthManager::register_business_owner( wp_unslash( $_POST ) );

        if ( is_wp_error( $result ) ) {
            set_transient( 'cin_auth_error_' . self::get_session_id(), $result->get_error_message(), 60 );
            wp_safe_redirect( home_url( '/register/business-owner/' ) );
            exit;
        }

        wp_safe_redirect( add_query_arg( [ 'status' => 'registered', 'email' => rawurlencode( $result['email'] ) ], home_url( '/register/business-owner/' ) ) );
        exit;
    }

    protected static function handle_login() {
        $username = isset( $_POST['username'] ) ? sanitize_text_field( wp_unslash( $_POST['username'] ) ) : '';
        $password = isset( $_POST['password'] ) ? (string) wp_unslash( $_POST['password'] ) : '';
        $remember = ! empty( $_POST['remember'] );

        $result = AuthManager::login( $username, $password, $remember );

        if ( is_wp_error( $result ) ) {
            set_transient( 'cin_auth_error_' . self::get_session_id(), $result->get_error_message(), 60 );

            // If pending verification, also pass email for quick resend trigger
            $extra = [];
            $data = $result->get_error_data();
            if ( ! empty( $data['email'] ) ) {
                $extra['resend_email'] = rawurlencode( $data['email'] );
            }

            wp_safe_redirect( add_query_arg( $extra, home_url( '/login/' ) ) );
            exit;
        }

        $redirect = ! empty( $_POST['redirect_to'] ) ? esc_url_raw( wp_unslash( $_POST['redirect_to'] ) ) : $result['redirect'];
        wp_safe_redirect( $redirect );
        exit;
    }

    protected static function handle_forgot_password() {
        $email = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
        $res   = AuthManager::request_password_reset( $email );

        wp_safe_redirect( add_query_arg( [ 'submitted' => 'true' ], home_url( '/forgot-password/' ) ) );
        exit;
    }

    protected static function handle_reset_password() {
        $key        = isset( $_POST['key'] ) ? sanitize_text_field( wp_unslash( $_POST['key'] ) ) : '';
        $login      = isset( $_POST['login'] ) ? sanitize_text_field( wp_unslash( $_POST['login'] ) ) : '';
        $password   = isset( $_POST['password'] ) ? (string) wp_unslash( $_POST['password'] ) : '';
        $confirm_pw = isset( $_POST['password_confirm'] ) ? (string) wp_unslash( $_POST['password_confirm'] ) : '';

        $res = AuthManager::execute_password_reset( $key, $login, $password, $confirm_pw );

        if ( is_wp_error( $res ) ) {
            set_transient( 'cin_auth_error_' . self::get_session_id(), $res->get_error_message(), 60 );
            wp_safe_redirect( add_query_arg( [ 'key' => $key, 'login' => rawurlencode( $login ) ], home_url( '/reset-password/' ) ) );
            exit;
        }

        wp_safe_redirect( add_query_arg( [ 'reset' => 'success' ], home_url( '/login/' ) ) );
        exit;
    }

    protected static function handle_resend_verification() {
        $email = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
        $res   = EmailVerification::resend( $email );

        wp_safe_redirect( add_query_arg( [ 'notice' => 'resent' ], home_url( '/verify-email/' ) ) );
        exit;
    }

    /**
     * Get unique session identifier for flash error transients
     *
     * @return string
     */
    public static function get_session_id() {
        $ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : 'unknown';
        return md5( $ip . ( $_SERVER['HTTP_USER_AGENT'] ?? '' ) );
    }

    /**
     * Retrieve and clear flash error message
     *
     * @return string
     */
    public static function get_flash_error() {
        $key = 'cin_auth_error_' . self::get_session_id();
        $msg = get_transient( $key );
        if ( $msg ) {
            delete_transient( $key );
            return (string) $msg;
        }
        return '';
    }
}
