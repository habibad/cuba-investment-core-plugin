<?php
/**
 * Master QA Automation Verification Suite - Cuba Investment Network
 *
 * Verifies Phase 01-05 Architecture, Auth, Profiles, Accounts, Opportunities,
 * and Cross-Role Networking Modules (Saved Opps, Enquiries, Connections, Messaging).
 */

require_once __DIR__ . '/../../../../wp-load.php';
require_once ABSPATH . 'wp-admin/includes/user.php';
require_once ABSPATH . 'wp-admin/includes/post.php';

use CubaInvestment\Core\Common\Constants;
use CubaInvestment\Core\Auth\Permissions;
use CubaInvestment\Core\Services\ProfileService;
use CubaInvestment\Core\Services\OpportunityService;
use CubaInvestment\Core\Services\SavedOpportunityService;
use CubaInvestment\Core\Services\InquiryService;
use CubaInvestment\Core\Services\ConnectionService;
use CubaInvestment\Core\Services\MessagingService;

echo "=== STARTING MASTER QA VERIFICATION SUITE ===\n\n";

$results = [
    'passed' => 0,
    'failed' => 0,
    'tests'  => [],
];

function assert_test( $name, $condition, $details = '' ) {
    global $results;
    if ( $condition ) {
        $results['passed']++;
        $results['tests'][] = [ 'name' => $name, 'status' => 'PASS', 'details' => $details ];
        echo "[PASS] $name\n";
    } else {
        $results['failed']++;
        $results['tests'][] = [ 'name' => $name, 'status' => 'FAIL', 'details' => $details ];
        echo "[FAIL] $name - Details: $details\n";
    }
}

// -----------------------------------------------------------------------------
// 1. PHASE 01: Core Architecture & Database Structure
// -----------------------------------------------------------------------------
echo "--- Testing Phase 01: Core Architecture & Database Integrity ---\n";

global $wpdb;

$required_tables = [
    Constants::get_table_name( Constants::TABLE_INQUIRIES ),
    Constants::get_table_name( Constants::TABLE_CONNECTIONS ),
    Constants::get_table_name( Constants::TABLE_CONVERSATIONS ),
    Constants::get_table_name( Constants::TABLE_MESSAGES ),
    Constants::get_table_name( Constants::TABLE_SAVED_OPPORTUNITIES ),
    Constants::get_table_name( Constants::TABLE_NOTIFICATIONS ),
    Constants::get_table_name( Constants::TABLE_SUBSCRIPTIONS ),
    Constants::get_table_name( Constants::TABLE_AUDIT_LOGS ),
];

foreach ( $required_tables as $table ) {
    $exists = $wpdb->get_var( "SHOW TABLES LIKE '{$table}'" ) === $table;
    assert_test( "Table exists: {$table}", $exists, "Checking DB table existence" );
}

// Check custom roles
$roles = wp_roles()->roles;
assert_test( "Role 'cin_investor' registered", isset( $roles[Constants::ROLE_INVESTOR] ) );
assert_test( "Role 'cin_business_owner' registered", isset( $roles[Constants::ROLE_BUSINESS_OWNER] ) );

// -----------------------------------------------------------------------------
// 2. PHASE 02: Authentication & User Fixtures
// -----------------------------------------------------------------------------
echo "\n--- Testing Phase 02: Authentication & Test Fixtures ---\n";

$inv_user = get_user_by( 'email', 'investor_p4@example.com' );
if ( ! $inv_user ) {
    $inv_id = wp_create_user( 'phase4_test_investor', 'SecurePass#2026!', 'investor_p4@example.com' );
    $inv_user = get_user_by( 'id', $inv_id );
    $inv_user->set_role( Constants::ROLE_INVESTOR );
} else {
    $inv_id = $inv_user->ID;
    $inv_user->set_role( Constants::ROLE_INVESTOR );
}
update_user_meta( $inv_id, '_cin_account_status', 'active' );
update_user_meta( $inv_id, '_cin_email_verified', '1' );

$biz_user = get_user_by( 'email', 'business_p4@example.com' );
if ( ! $biz_user ) {
    $biz_id = wp_create_user( 'phase4_test_business', 'SecurePass#2026!', 'business_p4@example.com' );
    $biz_user = get_user_by( 'id', $biz_id );
    $biz_user->set_role( Constants::ROLE_BUSINESS_OWNER );
} else {
    $biz_id = $biz_user->ID;
    $biz_user->set_role( Constants::ROLE_BUSINESS_OWNER );
}
update_user_meta( $biz_id, '_cin_account_status', 'active' );
update_user_meta( $biz_id, '_cin_email_verified', '1' );

assert_test( "Investor test user exists & has '" . Constants::ROLE_INVESTOR . "' role", in_array( Constants::ROLE_INVESTOR, $inv_user->roles ) );
assert_test( "Business Owner test user exists & has '" . Constants::ROLE_BUSINESS_OWNER . "' role", in_array( Constants::ROLE_BUSINESS_OWNER, $biz_user->roles ) );

// Check suspension logic
$test_susp_id = wp_create_user( 'temp_susp_user_' . time(), 'TempPass#2026!', 'susp_' . time() . '@example.com' );
update_user_meta( $test_susp_id, '_cin_account_status', 'suspended' );
assert_test( "Permissions::is_account_suspended() correctly identifies suspended status", Permissions::is_account_suspended( $test_susp_id ) );
assert_test( "Permissions::is_account_active() returns false for suspended user", ! Permissions::is_account_active( $test_susp_id ) );
wp_delete_user( $test_susp_id );

// -----------------------------------------------------------------------------
// 3. PHASE 03: Profile Management
// -----------------------------------------------------------------------------
echo "\n--- Testing Phase 03: Profile Management ---\n";

$profile = ProfileService::get_profile( $inv_id );
assert_test( "ProfileService::get_profile() returns valid profile data", is_array( $profile ) && isset( $profile['display_name'] ) );

// Test updating investor profile
$update_res = ProfileService::update_profile( $inv_id, [
    'first_name' => 'QA_Tester_First',
    'last_name'  => 'QA_Tester_Last',
    'bio'        => 'Automated test bio description for investor',
] );
assert_test( "ProfileService::update_profile() executes successfully", ! is_wp_error( $update_res ) );
$fresh_user = get_userdata( $inv_id );
assert_test( "Profile persistence verified in database", $fresh_user->first_name === 'QA_Tester_First' && $fresh_user->last_name === 'QA_Tester_Last' );

// Cross-user permission test: Investor updating another user should fail in controller
$auth_check = Permissions::can_edit_profile( $inv_id, $biz_id );
assert_test( "Cross-user security: Investor cannot edit Business Owner profile", ! $auth_check );

// -----------------------------------------------------------------------------
// 4. PHASE 04: Account Operations & Dashboards
// -----------------------------------------------------------------------------
echo "\n--- Testing Phase 04: Account Operations & Dashboards ---\n";

$saved_count = SavedOpportunityService::count( $inv_id );
assert_test( "SavedOpportunityService::count() returns integer", is_int( $saved_count ) );

$unread_count = MessagingService::get_unread_count( $inv_id );
assert_test( "MessagingService::get_unread_count() returns integer", is_int( $unread_count ) );

// -----------------------------------------------------------------------------
// 5. PHASE 05: Opportunity Management
// -----------------------------------------------------------------------------
echo "\n--- Testing Phase 05: Opportunity Management ---\n";

// Create or verify test opportunity owned by $biz_id
$existing_opp = get_posts( [
    'post_type'   => Constants::POST_TYPE_OPPORTUNITY,
    'author'      => $biz_id,
    'post_status' => [ 'publish', 'draft', 'pending' ],
    'numberposts' => 1,
] );

if ( ! empty( $existing_opp ) ) {
    $opp_id = $existing_opp[0]->ID;
    wp_update_post( [ 'ID' => $opp_id, 'post_status' => 'publish' ] );
} else {
    $opp_id = wp_insert_post( [
        'post_title'   => 'QA Test Renewable Microgrid & Agrivoltaic Hub',
        'post_content' => 'High yield decentralized agrivoltaic project in Matanzas province.',
        'post_status'  => 'publish',
        'post_author'  => $biz_id,
        'post_type'    => Constants::POST_TYPE_OPPORTUNITY,
    ] );
    update_post_meta( $opp_id, '_cin_capital_sought', '150000' );
    update_post_meta( $opp_id, '_cin_currency', 'USD' );
    update_post_meta( $opp_id, '_cin_min_investment', '25000' );
}

$opp_post = get_post( $opp_id );
assert_test( "Published opportunity exists and belongs to Business Owner", $opp_post && (int) $opp_post->post_author === (int) $biz_id && 'publish' === $opp_post->post_status );

// -----------------------------------------------------------------------------
// 6. NETWORKING: Saved Opportunities
// -----------------------------------------------------------------------------
echo "\n--- Testing Networking: Saved Opportunities ---\n";

// Ensure clean state
SavedOpportunityService::remove( $inv_id, $opp_id );
$init_saved = SavedOpportunityService::is_saved( $inv_id, $opp_id );
assert_test( "Initial bookmark state is false", ! $init_saved );

// Save opportunity
$save_res = SavedOpportunityService::save( $inv_id, $opp_id );
assert_test( "SavedOpportunityService::save() returns true", true === $save_res );
assert_test( "SavedOpportunityService::is_saved() confirms opportunity is bookmarked", SavedOpportunityService::is_saved( $inv_id, $opp_id ) );

// Check saved listing in database
$saved_list = SavedOpportunityService::get_saved( $inv_id );
$found_saved = false;
foreach ( $saved_list as $s ) {
    if ( (int) $s['id'] === (int) $opp_id ) {
        $found_saved = true;
        break;
    }
}
assert_test( "Saved opportunity appears in get_saved() collection", $found_saved );

// Test negative: Business owner cannot bookmark
$biz_save_res = SavedOpportunityService::save( $biz_id, $opp_id );
assert_test( "Business Owner is rejected from saving opportunities", is_wp_error( $biz_save_res ) && 'forbidden' === $biz_save_res->get_error_code() );

// Test negative: Suspended investor cannot bookmark
$temp_susp_inv = wp_create_user( 'temp_susp_inv_' . time(), 'TempPass#2026!', 'susp_inv_' . time() . '@example.com' );
$u = get_user_by( 'id', $temp_susp_inv );
$u->set_role( Constants::ROLE_INVESTOR );
update_user_meta( $temp_susp_inv, '_cin_account_status', 'suspended' );
$susp_save_res = SavedOpportunityService::save( $temp_susp_inv, $opp_id );
assert_test( "Suspended investor cannot bookmark opportunities", is_wp_error( $susp_save_res ) && 'account_inactive' === $susp_save_res->get_error_code() );
wp_delete_user( $temp_susp_inv );

// -----------------------------------------------------------------------------
// 7. NETWORKING: Investor Enquiry Workflow
// -----------------------------------------------------------------------------
echo "\n--- Testing Networking: Investor Enquiry Workflow ---\n";

// Clear previous test inquiries between these two for this opportunity
$t_inq = Constants::get_table_name( Constants::TABLE_INQUIRIES );
$wpdb->delete( $t_inq, [ 'investor_user_id' => $inv_id, 'opportunity_id' => $opp_id ] );

// Investor creates inquiry
$inq_id = InquiryService::create_inquiry( $inv_id, $opp_id, [
    'subject'       => 'Strategic Partnership Discussion - Round 1',
    'message'       => 'We are interested in co-funding the agrivoltaic installation in Matanzas.',
    'capital_range' => '$25k - $50k',
] );

assert_test( "InquiryService::create_inquiry() succeeds and returns ID", is_numeric( $inq_id ) && $inq_id > 0 );

$inquiry_obj = InquiryService::get_inquiry( $inq_id );
assert_test( "Inquiry retrieved correctly and status is 'pending'", $inquiry_obj && 'pending' === $inquiry_obj->status );
assert_test( "Inquiry references correct investor and business owner", (int) $inquiry_obj->investor_user_id === (int) $inv_id && (int) $inquiry_obj->business_user_id === (int) $biz_id );

// Negative test: Business owner cannot create inquiry
$biz_inq_err = InquiryService::create_inquiry( $biz_id, $opp_id, [
    'subject' => 'Invalid inquiry',
    'message' => 'This should fail because author is not investor.',
] );
assert_test( "Non-investor inquiry creation is rejected", is_wp_error( $biz_inq_err ) );

// Negative test: Cannot enquire about draft opportunity
$draft_opp_id = wp_insert_post( [
    'post_title'   => 'Unpublished Opportunity Test',
    'post_status'  => 'draft',
    'post_author'  => $biz_id,
    'post_type'    => Constants::POST_TYPE_OPPORTUNITY,
] );
$draft_inq_err = InquiryService::create_inquiry( $inv_id, $draft_opp_id, [
    'subject' => 'Inquiry on draft',
    'message' => 'Should fail because listing is draft.',
] );
assert_test( "Inquiry on unpublished/draft opportunity is rejected", is_wp_error( $draft_inq_err ) );
wp_delete_post( $draft_opp_id, true );

// -----------------------------------------------------------------------------
// 8. NETWORKING: Connection Logic Verification
// -----------------------------------------------------------------------------
echo "\n--- Testing Networking: Connection Logic Verification ---\n";

// Clear previous connections
$t_conn = Constants::get_table_name( Constants::TABLE_CONNECTIONS );
$wpdb->delete( $t_conn, [ 'investor_user_id' => $inv_id, 'business_user_id' => $biz_id ] );
$wpdb->delete( $t_conn, [ 'investor_user_id' => $biz_id, 'business_user_id' => $inv_id ] );

assert_test( "Initially users are not connected", ! Permissions::are_connected( $inv_id, $biz_id ) );

// Business owner accepts the inquiry
$respond_res = InquiryService::respond( $inq_id, $biz_id, 'accepted', 'Glad to connect and share financial models.' );
assert_test( "InquiryService::respond() acceptance succeeds", ! is_wp_error( $respond_res ) );

// Verify connection creation
$fresh_inq = InquiryService::get_inquiry( $inq_id );
assert_test( "Inquiry status changed to 'accepted'", 'accepted' === $fresh_inq->status );

$conn = ConnectionService::get_connection_by_inquiry( $inq_id );
assert_test( "Active connection automatically created from accepted inquiry", $conn && 'active' === $conn->status );
assert_test( "Permissions::are_connected() returns true for both users", Permissions::are_connected( $inv_id, $biz_id ) && Permissions::are_connected( $biz_id, $inv_id ) );

// Critical test: Duplicate acceptance prevention
$repeat_res = InquiryService::respond( $inq_id, $biz_id, 'accepted', 'Trying again' );
assert_test( "Repeat response on already accepted inquiry is rejected", is_wp_error( $repeat_res ) );

// Verify active connection counts
$inv_conn_count = ConnectionService::get_connections_count( $inv_id );
$biz_conn_count = ConnectionService::get_connections_count( $biz_id );
assert_test( "Investor active connection count >= 1", $inv_conn_count >= 1 );
assert_test( "Business Owner active connection count >= 1", $biz_conn_count >= 1 );

// -----------------------------------------------------------------------------
// 9. NETWORKING: Private Messaging Verification
// -----------------------------------------------------------------------------
echo "\n--- Testing Networking: Private Messaging Verification ---\n";

$convo_id = MessagingService::get_or_create_conversation( $conn->id, $inv_id, $biz_id );
assert_test( "MessagingService::get_or_create_conversation() returns valid ID", is_numeric( $convo_id ) && $convo_id > 0 );

// Verify conversation access permissions
assert_test( "Investor has access to conversation", Permissions::can_access_conversation( $inv_id, $convo_id ) );
assert_test( "Business Owner has access to conversation", Permissions::can_access_conversation( $biz_id, $convo_id ) );

// Unrelated user cannot access conversation
$unrelated_user_id = wp_create_user( 'unrelated_qa_' . time(), 'TempPass#2026!', 'unrelated_' . time() . '@example.com' );
assert_test( "Unrelated third-party user is denied access to conversation", ! Permissions::can_access_conversation( $unrelated_user_id, $convo_id ) );

// Investor sends message to Business Owner
$msg_id = MessagingService::send_message( $convo_id, $inv_id, $biz_id, "Hello from Investor! We are reviewing the business plan." );
assert_test( "Investor dispatches message successfully", is_numeric( $msg_id ) && $msg_id > 0 );

// Verify unread count for recipient
$biz_unread = MessagingService::get_unread_count( $biz_id );
assert_test( "Business Owner unread count increments", $biz_unread >= 1 );

// Business Owner reads messages and replies
$messages = MessagingService::get_messages( $convo_id );
assert_test( "Conversation contains dispatched message", count( $messages ) >= 1 && strpos( $messages[count($messages)-1]['message_body'], 'Hello from Investor' ) !== false );

MessagingService::mark_read( $convo_id, $biz_id );
$biz_unread_after = MessagingService::get_unread_count( $biz_id );
assert_test( "Marking conversation read resets recipient unread count for this conversation", $biz_unread_after < $biz_unread || $biz_unread_after === 0 );

// Business owner replies
$reply_id = MessagingService::send_message( $convo_id, $biz_id, $inv_id, "Thank you! I have uploaded the updated cap table." );
assert_test( "Business Owner successfully replies", is_numeric( $reply_id ) && $reply_id > 0 );

// Security test: Unconnected user cannot send message
$unconn_res = MessagingService::send_message( $convo_id, $unrelated_user_id, $biz_id, "Intrusion test message" );
assert_test( "Unconnected / unauthorized user cannot dispatch message into conversation", is_wp_error( $unconn_res ) );

// Security test: Suspended user cannot send message
update_user_meta( $unrelated_user_id, '_cin_account_status', 'suspended' );
$susp_msg_res = MessagingService::send_message( $convo_id, $unrelated_user_id, $biz_id, "Suspended test message" );
assert_test( "Suspended user cannot send message", is_wp_error( $susp_msg_res ) && 'account_inactive' === $susp_msg_res->get_error_code() );

// Cleanup unrelated user
wp_delete_user( $unrelated_user_id );

// -----------------------------------------------------------------------------
// SUMMARY
// -----------------------------------------------------------------------------
echo "\n=== QA VERIFICATION SUITE SUMMARY ===\n";
echo "Total Tests Run : " . ( $results['passed'] + $results['failed'] ) . "\n";
echo "Passed          : " . $results['passed'] . "\n";
echo "Failed          : " . $results['failed'] . "\n";

if ( $results['failed'] === 0 ) {
    echo "STATUS: ALL TESTS PASSED (100% SUCCESS)\n";
    exit( 0 );
} else {
    echo "STATUS: FAILURES DETECTED\n";
    exit( 1 );
}
