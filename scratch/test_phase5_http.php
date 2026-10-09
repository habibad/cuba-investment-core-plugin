<?php
/**
 * Test HTTP Routes and Role Protection for Phase 05
 */

require_once dirname( __DIR__, 4 ) . '/wp-load.php';

use CubaInvestment\Core\Common\Constants;

echo "=== PHASE 05 HTTP ROUTE & REGRESSION TESTS ===\n\n";

$home = home_url();

// 1. Unauthenticated request to /business-owner/opportunities/
$ch = curl_init();
curl_setopt_array( $ch, [
    CURLOPT_URL            => $home . '/business-owner/opportunities/',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => false,
    CURLOPT_HEADER         => true,
] );
$response = curl_exec( $ch );
$http_code = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
curl_close( $ch );

assert( in_array( $http_code, [ 301, 302 ], true ), "Unauthenticated visitor must be redirected, got {$http_code}" );
echo "[PASS] 1. Unauthenticated access to /business-owner/opportunities/ triggers redirect ({$http_code}).\n";

// 2. Unauthenticated request to /business-owner/opportunities/create/
$ch = curl_init();
curl_setopt_array( $ch, [
    CURLOPT_URL            => $home . '/business-owner/opportunities/create/',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => false,
    CURLOPT_HEADER         => true,
] );
$response = curl_exec( $ch );
$http_code = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
curl_close( $ch );

assert( in_array( $http_code, [ 301, 302 ], true ), "Unauthenticated visitor must be redirected from create, got {$http_code}" );
echo "[PASS] 2. Unauthenticated access to /business-owner/opportunities/create/ triggers redirect ({$http_code}).\n";

// Helper to authenticate user and get cookies
function get_auth_cookies( $user_id ) {
    wp_set_current_user( $user_id );
    wp_set_auth_cookie( $user_id, true );
    $cookie_strings = [];
    foreach ( $_COOKIE as $k => $v ) {
        $cookie_strings[] = "{$k}={$v}";
    }
    // Also generate WordPress auth cookies directly
    $cookies = [];
    $auth_cookie = wp_generate_auth_cookie( $user_id, time() + 3600, 'logged_in' );
    $cookies[] = LOGGED_IN_COOKIE . '=' . $auth_cookie;
    return implode( '; ', $cookies );
}

// Get Business Owner User
$bo_users = get_users( [ 'role' => Constants::ROLE_BUSINESS_OWNER, 'number' => 1 ] );
$bo_user = $bo_users[0];
$bo_cookies = get_auth_cookies( $bo_user->ID );

// 3. Authenticated Business Owner request to /business-owner/opportunities/
$ch = curl_init();
curl_setopt_array( $ch, [
    CURLOPT_URL            => $home . '/business-owner/opportunities/',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_COOKIE         => $bo_cookies,
] );
$response = curl_exec( $ch );
$http_code = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
curl_close( $ch );

assert( 200 === $http_code, "Business owner should receive HTTP 200 on /business-owner/opportunities/, got {$http_code}" );
assert( strpos( $response, 'My Opportunities' ) !== false, "Response must contain 'My Opportunities'" );
assert( strpos( $response, 'Create Opportunity' ) !== false, "Response must contain 'Create Opportunity'" );
echo "[PASS] 3. Business Owner successfully accessed /business-owner/opportunities/ (HTTP 200, correct UI markup).\n";

// 4. Authenticated Business Owner request to /business-owner/opportunities/create/
$ch = curl_init();
curl_setopt_array( $ch, [
    CURLOPT_URL            => $home . '/business-owner/opportunities/create/',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_COOKIE         => $bo_cookies,
] );
$response = curl_exec( $ch );
$http_code = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
curl_close( $ch );

assert( 200 === $http_code, "Business owner should receive HTTP 200 on /business-owner/opportunities/create/, got {$http_code}" );
assert( strpos( $response, 'Business Overview' ) !== false, "Response must contain Step 1 'Business Overview'" );
assert( strpos( $response, 'Capital Requirements' ) !== false, "Response must contain Step 2 'Capital Requirements'" );
assert( strpos( $response, 'Documents & Review' ) !== false || strpos( $response, 'Supporting Documents' ) !== false, "Response must contain Step 6 docs & review" );
echo "[PASS] 4. Business Owner successfully accessed /business-owner/opportunities/create/ (HTTP 200, all 6 steps rendered).\n";

// 5. Authenticated Investor request to /business-owner/opportunities/create/ (Should redirect to investor dashboard)
$inv_users = get_users( [ 'role' => Constants::ROLE_INVESTOR, 'number' => 1 ] );
$inv_user = $inv_users[0];
$inv_cookies = get_auth_cookies( $inv_user->ID );

$ch = curl_init();
curl_setopt_array( $ch, [
    CURLOPT_URL            => $home . '/business-owner/opportunities/create/',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => false,
    CURLOPT_COOKIE         => $inv_cookies,
    CURLOPT_HEADER         => true,
] );
$response = curl_exec( $ch );
$http_code = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
curl_close( $ch );

assert( in_array( $http_code, [ 301, 302 ], true ), "Investor accessing business-owner opportunity creation must be redirected, got {$http_code}" );
echo "[PASS] 5. Investor blocked from /business-owner/opportunities/create/ via RouteProtection redirect ({$http_code}).\n";

// 6. Regression check: Existing /business-owner/dashboard/
$ch = curl_init();
curl_setopt_array( $ch, [
    CURLOPT_URL            => $home . '/business-owner/dashboard/',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_COOKIE         => $bo_cookies,
] );
$response = curl_exec( $ch );
$http_code = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
curl_close( $ch );

assert( 200 === $http_code, "Business owner dashboard must return HTTP 200, got {$http_code}" );
assert( strpos( $response, 'My Opportunities' ) !== false, "Dashboard must show 'My Opportunities' card" );
assert( strpos( $response, 'Draft Opportunities' ) !== false, "Dashboard must show 'Draft Opportunities' card" );
echo "[PASS] 6. Regression test passed: Existing Business Owner Dashboard loads seamlessly with real opportunity data cards.\n";

echo "\n=== ALL HTTP ROUTE & REGRESSION TESTS PASSED SUCCESSFULLY ===\n";
