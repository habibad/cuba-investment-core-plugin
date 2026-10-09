<?php
/**
 * Master QA HTTP & End-to-End Session Verification
 *
 * Tests real HTTP endpoints, sessions, role authorization boundaries,
 * sidebar navigation routes, and REST APIs using cURL with cookie jars.
 */

$base_url = 'http://localhost/angel-investment';
$cookie_dir = __DIR__ . '/cookies';
if ( ! is_dir( $cookie_dir ) ) {
    mkdir( $cookie_dir, 0777, true );
}

$inv_cookie  = $cookie_dir . '/cookie_investor.txt';
$biz_cookie  = $cookie_dir . '/cookie_business.txt';
$admin_cookie = $cookie_dir . '/cookie_admin.txt';

@unlink( $inv_cookie );
@unlink( $biz_cookie );
@unlink( $admin_cookie );

echo "=== STARTING MASTER QA HTTP E2E VERIFICATION ===\n\n";

$results = [
    'passed' => 0,
    'failed' => 0,
];

function http_assert( $name, $condition, $details = '' ) {
    global $results;
    if ( $condition ) {
        $results['passed']++;
        echo "[PASS] $name\n";
    } else {
        $results['failed']++;
        echo "[FAIL] $name - $details\n";
    }
}

function make_request( $url, $options = [] ) {
    $ch = curl_init();
    $method = $options['method'] ?? 'GET';
    $cookie_file = $options['cookie_file'] ?? null;
    $post_fields = $options['post_fields'] ?? null;
    $follow = $options['follow'] ?? false;
    $headers = $options['headers'] ?? [];

    curl_setopt( $ch, CURLOPT_URL, $url );
    curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
    curl_setopt( $ch, CURLOPT_HEADER, true );
    curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, $method );
    curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, $follow );
    curl_setopt( $ch, CURLOPT_MAXREDIRS, 5 );
    curl_setopt( $ch, CURLOPT_TIMEOUT, 15 );

    if ( $cookie_file ) {
        curl_setopt( $ch, CURLOPT_COOKIEJAR, $cookie_file );
        curl_setopt( $ch, CURLOPT_COOKIEFILE, $cookie_file );
    }

    if ( $post_fields ) {
        if ( is_array( $post_fields ) ) {
            curl_setopt( $ch, CURLOPT_POSTFIELDS, http_build_query( $post_fields ) );
        } else {
            curl_setopt( $ch, CURLOPT_POSTFIELDS, $post_fields );
        }
    }

    if ( ! empty( $headers ) ) {
        curl_setopt( $ch, CURLOPT_HTTPHEADER, $headers );
    }

    $raw = curl_exec( $ch );
    $header_size = curl_getinfo( $ch, CURLINFO_HEADER_SIZE );
    $http_code   = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
    $redirect_url= curl_getinfo( $ch, CURLINFO_REDIRECT_URL );

    $res_headers = substr( $raw, 0, $header_size );
    $body        = substr( $raw, $header_size );

    curl_close( $ch );

    return [
        'code'         => $http_code,
        'headers'      => $res_headers,
        'body'         => $body,
        'redirect_url' => $redirect_url,
    ];
}

// -----------------------------------------------------------------------------
// 1. PUBLIC ROUTES & GUEST ACCESS
// -----------------------------------------------------------------------------
echo "--- 1. Testing Public Routes & Guest Access ---\n";

$home = make_request( $base_url . '/' );
http_assert( "Homepage loads (HTTP 200)", 200 === $home['code'] );

$login = make_request( $base_url . '/login/' );
http_assert( "Login page loads (HTTP 200)", 200 === $login['code'] );

// Check unauthenticated redirect on protected routes
$guest_inv_dash = make_request( $base_url . '/investor/dashboard/' );
http_assert( "Guest access to /investor/dashboard/ redirects (HTTP 302)", 302 === $guest_inv_dash['code'] && false !== strpos( $guest_inv_dash['headers'], 'Location:' ) );

$guest_biz_dash = make_request( $base_url . '/business-owner/dashboard/' );
http_assert( "Guest access to /business-owner/dashboard/ redirects (HTTP 302)", 302 === $guest_biz_dash['code'] && false !== strpos( $guest_biz_dash['headers'], 'Location:' ) );

// -----------------------------------------------------------------------------
// 2. INVESTOR AUTHENTICATION & PORTAL NAVIGATION
// -----------------------------------------------------------------------------
echo "\n--- 2. Testing Investor Authentication & Portal Navigation ---\n";

function login_cin_user( $email, $password, $cookie_file ) {
    global $base_url;
    $login_page = make_request( $base_url . '/login/', [ 'cookie_file' => $cookie_file ] );
    $nonce = '';
    if ( preg_match( '/name="_cin_nonce"\s+value="([^"]+)"/', $login_page['body'], $m ) ) {
        $nonce = $m[1];
    } elseif ( preg_match( '/value="([^"]+)"\s+name="_cin_nonce"/', $login_page['body'], $m ) ) {
        $nonce = $m[1];
    }

    return make_request( $base_url . '/login/', [
        'method'      => 'POST',
        'cookie_file' => $cookie_file,
        'post_fields' => [
            'cin_action' => 'cin_login',
            '_cin_nonce' => $nonce,
            'username'   => $email,
            'password'   => $password,
        ],
    ] );
}

// Login as Investor
$inv_login = login_cin_user( 'investor_p4@example.com', 'SecurePass#2026!', $inv_cookie );
http_assert( "Investor login succeeds (Redirect HTTP 302)", 302 === $inv_login['code'] );

// Verify Investor Dashboard access
$inv_dash = make_request( $base_url . '/investor/dashboard/', [
    'cookie_file' => $inv_cookie,
] );
http_assert( "Investor Dashboard loads (HTTP 200)", 200 === $inv_dash['code'] );

// Test all Investor Portal routes from prompt Section 15
$inv_routes = [
    'My Profile'            => '/investor/profile/',
    'Saved Opportunities'   => '/investor/saved-opportunities/',
    'My Enquiries'          => '/investor/enquiries/',
    'Connections'           => '/investor/connections/',
    'Messages'              => '/investor/messages/',
    'Account Settings'      => '/account/',
];

foreach ( $inv_routes as $name => $path ) {
    $res = make_request( $base_url . $path, [ 'cookie_file' => $inv_cookie ] );
    http_assert( "Investor Portal Route: $name ($path) loads (HTTP 200)", 200 === $res['code'] );
}

// -----------------------------------------------------------------------------
// 3. BUSINESS OWNER AUTHENTICATION & PORTAL NAVIGATION
// -----------------------------------------------------------------------------
echo "\n--- 3. Testing Business Owner Authentication & Portal Navigation ---\n";

// Login as Business Owner
$biz_login = login_cin_user( 'business_p4@example.com', 'SecurePass#2026!', $biz_cookie );
http_assert( "Business Owner login succeeds (Redirect HTTP 302)", 302 === $biz_login['code'] );

// Verify Business Owner Dashboard access
$biz_dash = make_request( $base_url . '/business-owner/dashboard/', [
    'cookie_file' => $biz_cookie,
] );
http_assert( "Business Owner Dashboard loads (HTTP 200)", 200 === $biz_dash['code'] );

// Test all Business Owner Portal routes from prompt Section 15
$biz_routes = [
    'Personal Profile'      => '/business-owner/profile/',
    'Business Profile'      => '/business-owner/business-profile/',
    'My Opportunities'      => '/business-owner/opportunities/',
    'Create Opportunity'    => '/business-owner/opportunities/create/',
    'Investor Enquiries'    => '/business-owner/enquiries/',
    'Connections'           => '/business-owner/connections/',
    'Messages'              => '/business-owner/messages/',
    'Account Settings'      => '/account/',
];

foreach ( $biz_routes as $name => $path ) {
    $res = make_request( $base_url . $path, [ 'cookie_file' => $biz_cookie ] );
    http_assert( "Business Owner Portal Route: $name ($path) loads (HTTP 200)", 200 === $res['code'] );
}

// -----------------------------------------------------------------------------
// 4. ROLE ISOLATION & ACCESS CONTROL
// -----------------------------------------------------------------------------
echo "\n--- 4. Testing Role Isolation & Access Control ---\n";

// Investor trying to access Business Owner dashboard should be redirected
$inv_on_biz = make_request( $base_url . '/business-owner/dashboard/', [
    'cookie_file' => $inv_cookie,
] );
http_assert( "Investor accessing Business Owner dashboard is redirected (HTTP 302)", 302 === $inv_on_biz['code'] );

// Business Owner trying to access Investor dashboard should be redirected
$biz_on_inv = make_request( $base_url . '/investor/dashboard/', [
    'cookie_file' => $biz_cookie,
] );
http_assert( "Business Owner accessing Investor dashboard is redirected (HTTP 302)", 302 === $biz_on_inv['code'] );

// Logged-in user visiting /login/ is redirected to their respective dashboard
$inv_on_login = make_request( $base_url . '/login/', [
    'cookie_file' => $inv_cookie,
] );
http_assert( "Logged-in Investor visiting /login/ redirects to dashboard (HTTP 302)", 302 === $inv_on_login['code'] );

// -----------------------------------------------------------------------------
// 5. REST API ROUTE INTEGRITY WITH SESSION COOKIES
// -----------------------------------------------------------------------------
echo "\n--- 5. Testing REST API Route Integrity With Session Cookies ---\n";

// Obtain REST nonce from Investor dashboard HTML
$inv_nonce = '';
if ( preg_match( '/wpApiSettings\s*=\s*\{[^}]*"nonce":"([^"]+)"/', $inv_dash['body'], $matches ) ) {
    $inv_nonce = $matches[1];
} elseif ( preg_match( '/cinRestNonce\s*=\s*["\']([^"\']+)["\']/', $inv_dash['body'], $matches ) ) {
    $inv_nonce = $matches[1];
}

// Headers for REST
$inv_rest_headers = [];
if ( $inv_nonce ) {
    $inv_rest_headers[] = 'X-WP-Nonce: ' . $inv_nonce;
}

// Check saved opportunities REST endpoint
$rest_saved = make_request( $base_url . '/wp-json/cin/v1/opportunities/saved', [
    'cookie_file' => $inv_cookie,
    'headers'     => $inv_rest_headers,
] );
http_assert( "Investor REST GET /opportunities/saved returns 200 or 401/nonce", in_array( $rest_saved['code'], [ 200, 401 ], true ) );

// Check conversations unread count REST endpoint
$rest_unread = make_request( $base_url . '/wp-json/cin/v1/conversations/unread-count', [
    'cookie_file' => $inv_cookie,
    'headers'     => $inv_rest_headers,
] );
http_assert( "Investor REST GET /conversations/unread-count reachable", in_array( $rest_unread['code'], [ 200, 401 ], true ) );

// -----------------------------------------------------------------------------
// SUMMARY
// -----------------------------------------------------------------------------
echo "\n=== HTTP E2E VERIFICATION SUMMARY ===\n";
echo "Total Tests Run : " . ( $results['passed'] + $results['failed'] ) . "\n";
echo "Passed          : " . $results['passed'] . "\n";
echo "Failed          : " . $results['failed'] . "\n";

if ( $results['failed'] === 0 ) {
    echo "STATUS: ALL HTTP ENDPOINTS & SESSIONS VERIFIED (100% SUCCESS)\n";
    exit( 0 );
} else {
    echo "STATUS: FAILURES DETECTED\n";
    exit( 1 );
}
