<?php
require_once dirname( __DIR__, 4 ) . '/wp-load.php';

use CubaInvestment\Core\Common\Constants;
use CubaInvestment\Core\Security\NonceManager;

$user_id = 35; // rahmananik523 (cin_business_owner)
wp_set_current_user( $user_id );
wp_set_auth_cookie( $user_id, true );
$auth_cookie = wp_generate_auth_cookie( $user_id, time() + 3600, 'logged_in' );
$cookies = LOGGED_IN_COOKIE . '=' . $auth_cookie;

echo "=== SIMULATING HTTP POST TO /business-owner/profile/ ===\n";

$nonce = NonceManager::create( 'cin_update_business_owner_profile' );

$post_data = [
    'cin_action'           => 'cin_update_business_owner_profile',
    '_cin_nonce'           => $nonce,
    'first_name'           => 'Rahman Test',
    'last_name'            => 'Anik Updated',
    'country_of_residence' => 'Cuba',
    'phone_number'         => '+53 5 999 8888',
    'founder_bio'          => 'Updated bio from HTTP POST simulation test.',
];

$ch = curl_init();
curl_setopt_array( $ch, [
    CURLOPT_URL            => home_url( '/business-owner/profile/' ),
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => http_build_query( $post_data ),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => false,
    CURLOPT_COOKIE         => $cookies,
    CURLOPT_HEADER         => true,
] );
$response = curl_exec( $ch );
$code = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
$header_size = curl_getinfo( $ch, CURLINFO_HEADER_SIZE );
$headers = substr( $response, 0, $header_size );
$body = substr( $response, $header_size );
curl_close( $ch );

echo "HTTP Code: {$code}\n";
if ( preg_match( '/^Location:\s*(.*)$/mi', $headers, $m ) ) {
    echo "Redirect Location: " . trim( $m[1] ) . "\n";
} else {
    if ( preg_match( '/<div class="wp-die-message">(.*?)<\/div>/is', $body, $m2 ) ) {
        echo "WP-DIE MESSAGE: " . strip_tags( trim( $m2[1] ) ) . "\n";
    } else {
        echo "Body: " . substr( $body, 0, 500 ) . "\n";
    }
}
