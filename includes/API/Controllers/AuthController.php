<?php
/**
 * Cuba Investment Core - Auth REST Controller
 *
 * @package CubaInvestment\Core
 */

namespace CubaInvestment\Core\API\Controllers;

use CubaInvestment\Core\Common\Constants;
use CubaInvestment\Core\Auth\AuthManager;
use CubaInvestment\Core\Auth\EmailVerification;
use CubaInvestment\Core\Services\ProfileService;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AuthController {

    public static function register_routes() {
        register_rest_route( Constants::API_NAMESPACE, '/auth/register-investor', [
            'methods'             => 'POST',
            'callback'            => [ __CLASS__, 'register_investor' ],
            'permission_callback' => '__return_true',
        ] );

        register_rest_route( Constants::API_NAMESPACE, '/auth/register-business-owner', [
            'methods'             => 'POST',
            'callback'            => [ __CLASS__, 'register_business_owner' ],
            'permission_callback' => '__return_true',
        ] );

        register_rest_route( Constants::API_NAMESPACE, '/auth/login', [
            'methods'             => 'POST',
            'callback'            => [ __CLASS__, 'login' ],
            'permission_callback' => '__return_true',
        ] );

        register_rest_route( Constants::API_NAMESPACE, '/auth/resend-verification', [
            'methods'             => 'POST',
            'callback'            => [ __CLASS__, 'resend_verification' ],
            'permission_callback' => '__return_true',
        ] );

        register_rest_route( Constants::API_NAMESPACE, '/auth/verify-email', [
            'methods'             => 'POST',
            'callback'            => [ __CLASS__, 'verify_email' ],
            'permission_callback' => '__return_true',
        ] );

        register_rest_route( Constants::API_NAMESPACE, '/auth/forgot-password', [
            'methods'             => 'POST',
            'callback'            => [ __CLASS__, 'forgot_password' ],
            'permission_callback' => '__return_true',
        ] );

        register_rest_route( Constants::API_NAMESPACE, '/auth/reset-password', [
            'methods'             => 'POST',
            'callback'            => [ __CLASS__, 'reset_password' ],
            'permission_callback' => '__return_true',
        ] );

        register_rest_route( Constants::API_NAMESPACE, '/auth/me', [
            'methods'             => 'GET',
            'callback'            => [ __CLASS__, 'me' ],
            'permission_callback' => 'is_user_logged_in',
        ] );
    }

    public static function register_investor( \WP_REST_Request $request ) {
        $params = $request->get_json_params() ?: $request->get_body_params();
        $result = AuthManager::register_investor( $params );

        if ( is_wp_error( $result ) ) {
            return $result;
        }

        return rest_ensure_response( $result );
    }

    public static function register_business_owner( \WP_REST_Request $request ) {
        $params = $request->get_json_params() ?: $request->get_body_params();
        $result = AuthManager::register_business_owner( $params );

        if ( is_wp_error( $result ) ) {
            return $result;
        }

        return rest_ensure_response( $result );
    }

    public static function login( \WP_REST_Request $request ) {
        $params   = $request->get_json_params() ?: $request->get_body_params();
        $username = isset( $params['username'] ) ? sanitize_text_field( $params['username'] ) : ( isset( $params['email'] ) ? sanitize_email( $params['email'] ) : '' );
        $password = isset( $params['password'] ) ? (string) $params['password'] : '';
        $remember = ! empty( $params['remember'] );

        $result = AuthManager::login( $username, $password, $remember );

        if ( is_wp_error( $result ) ) {
            return $result;
        }

        return rest_ensure_response( $result );
    }

    public static function resend_verification( \WP_REST_Request $request ) {
        $params = $request->get_json_params() ?: $request->get_body_params();
        $email  = isset( $params['email'] ) ? sanitize_email( $params['email'] ) : '';

        $result = EmailVerification::resend( $email );

        return rest_ensure_response( $result );
    }

    public static function verify_email( \WP_REST_Request $request ) {
        $params = $request->get_json_params() ?: $request->get_body_params();
        $token  = isset( $params['token'] ) ? sanitize_text_field( $params['token'] ) : '';
        $uid    = isset( $params['uid'] ) ? absint( $params['uid'] ) : 0;

        $result = EmailVerification::verify( $uid, $token );

        if ( is_wp_error( $result ) ) {
            return $result;
        }

        return rest_ensure_response( [
            'success'          => true,
            'already_verified' => ( 'already_verified' === $result ),
            'message'          => ( 'already_verified' === $result )
                ? __( 'Account already verified.', 'cuba-investment-core' )
                : __( 'Email verified successfully.', 'cuba-investment-core' ),
        ] );
    }

    public static function forgot_password( \WP_REST_Request $request ) {
        $params = $request->get_json_params() ?: $request->get_body_params();
        $email  = isset( $params['email'] ) ? sanitize_email( $params['email'] ) : '';

        $result = AuthManager::request_password_reset( $email );

        return rest_ensure_response( $result );
    }

    public static function reset_password( \WP_REST_Request $request ) {
        $params     = $request->get_json_params() ?: $request->get_body_params();
        $key        = isset( $params['key'] ) ? sanitize_text_field( $params['key'] ) : '';
        $login      = isset( $params['login'] ) ? sanitize_text_field( $params['login'] ) : '';
        $password   = isset( $params['password'] ) ? (string) $params['password'] : '';
        $confirm_pw = isset( $params['password_confirm'] ) ? (string) $params['password_confirm'] : '';

        $result = AuthManager::execute_password_reset( $key, $login, $password, $confirm_pw );

        if ( is_wp_error( $result ) ) {
            return $result;
        }

        return rest_ensure_response( [
            'success' => true,
            'message' => __( 'Password reset successful. You may now log in.', 'cuba-investment-core' ),
        ] );
    }

    public static function me() {
        $user_id = get_current_user_id();
        $profile = ProfileService::get_profile( $user_id );

        return rest_ensure_response( [
            'success' => true,
            'user'    => $profile,
        ] );
    }
}
