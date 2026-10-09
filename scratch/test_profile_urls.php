<?php
require_once dirname( __DIR__, 4 ) . '/wp-load.php';

use CubaInvestment\Core\Common\Constants;

$urls = [
    '/investor/profile/',
    '/dashboard/investor/profile/',
    '/business-owner/profile/',
    '/business-owner/business-profile/',
    '/dashboard/business/profile/',
    '/dashboard/business/business-profile/',
];

echo "=== TESTING PROFILE URL RESOLUTION ===\n\n";

// Test with Business Owner: rahmananik523 (ID 35)
$user_id = 35;
wp_set_current_user( $user_id );
wp_set_auth_cookie( $user_id, true );
$auth_cookie = wp_generate_auth_cookie( $user_id, time() + 3600, 'logged_in' );
$cookies = LOGGED_IN_COOKIE . '=' . $auth_cookie;

foreach ( $urls as $path ) {
    $ch = curl_init();
    curl_setopt_array( $ch, [
        CURLOPT_URL            => home_url( $path ),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_COOKIE         => $cookies,
        CURLOPT_HEADER         => true,
    ] );
    $resp = curl_exec( $ch );
    $code = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
    $header_size = curl_getinfo( $ch, CURLINFO_HEADER_SIZE );
    $headers = substr( $resp, 0, $header_size );
    $body = substr( $resp, $header_size );
    curl_close( $ch );

    $redirect_url = '';
    if ( preg_match( '/^Location:\s*(.*)$/mi', $headers, $m ) ) {
        $redirect_url = trim( $m[1] );
    }

    echo "Path: {$path} -> HTTP {$code}";
    if ( $redirect_url ) {
        echo " -> Redirect: {$redirect_url}";
    } else {
        // Check title
        if ( preg_match( '/<title>(.*?)<\/title>/is', $body, $m ) ) {
            echo " -> Title: " . trim( $m[1] );
        }
    }
    echo "\n";
}
