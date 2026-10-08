<?php
/**
 * Cuba Investment Core - REST API Rate Limiter
 *
 * @package CubaInvestment\Core
 */

namespace CubaInvestment\Core\API\Middleware;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class RateLimiter {

    /**
     * Check if request exceeds rate limit
     *
     * @param string $action Action key
     * @param int    $max_requests Max requests per window
     * @param int    $window_seconds Window in seconds (default 3600 = 1 hour)
     * @return bool True if allowed, False if rate limited
     */
    public static function check( $action, $max_requests = 60, $window_seconds = 3600 ) {
        $user_id = get_current_user_id();
        $ip      = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : 'unknown';

        $identifier = $user_id ? "user_{$user_id}" : "ip_" . md5( $ip );
        $transient_key = "cin_rate_{$action}_{$identifier}";

        $current = (int) get_transient( $transient_key );

        if ( $current >= $max_requests ) {
            return false;
        }

        set_transient( $transient_key, $current + 1, $window_seconds );
        return true;
    }
}
