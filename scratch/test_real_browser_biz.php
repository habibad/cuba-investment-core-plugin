<?php
require_once dirname( __DIR__, 4 ) . '/wp-load.php';

use CubaInvestment\Core\Common\Constants;

$user_id = 35; // rahmananik523
wp_set_current_user( $user_id );
$auth_cookie = wp_generate_auth_cookie( $user_id, time() + 3600, 'logged_in' );
$cookies = LOGGED_IN_COOKIE . '=' . $auth_cookie;

echo "=== REAL BROWSER SIMULATION: Business Profile GET then POST ===\n";

// 1. GET page to extract nonce
$ch = curl_init();
curl_setopt_array( $ch, [
    CURLOPT_URL            => home_url( '/business-owner/business-profile/' ),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_COOKIE         => $cookies,
] );
$html = curl_exec( $ch );
curl_close( $ch );

if ( ! preg_match( '/name="_cin_nonce"\s+value="([^"]+)"/', $html, $m ) ) {
    die( "[FAIL] Could not find _cin_nonce in rendered HTML!\n" );
}

$nonce = $m[1];
echo "Extracted nonce: {$nonce}\n";

// 2. POST with extracted nonce
$post_data = [
    'cin_action'                  => 'cin_update_business_profile',
    '_cin_nonce'                  => $nonce,
    'company_name'                => 'Caribe Agroindustrial S.R.L.',
    'company_sector'              => 'agriculture',
    'company_location_province'   => 'Matanzas',
    'company_country'             => 'Cuba',
    'company_website'             => 'https://caribeagro.cu',
    'company_description'         => 'Leading organic agricultural processing and packaging facility in Matanzas.',
    'company_products_services'   => 'Organic fruit pulps, cold storage, packaging.',
    'company_year_established'    => 2021,
    'company_stage'               => 'growth',
    'company_legal_type'          => 'mipyme_private',
    'company_market_description'  => 'Hotels and local retail markets.',
    'seeking_investment'          => 1,
    'seeking_partners'            => 1,
    'collaboration_interests'     => 'Looking for export partners and solar equipment financing.',
    'business_visibility'         => 'verified_investors',
    'show_phone_privacy'          => 'connections_only',
    'show_financials_privacy'     => 'verified_investors',
];

$ch = curl_init();
curl_setopt_array( $ch, [
    CURLOPT_URL            => home_url( '/business-owner/business-profile/' ),
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
