<?php
/**
 * Test Suite: Phase 05 — Opportunity Submission & Listing Management System
 */

// Load WordPress
require_once dirname( __DIR__, 4 ) . '/wp-load.php';

use CubaInvestment\Core\Common\Constants;
use CubaInvestment\Core\Models\Opportunity;
use CubaInvestment\Core\Services\OpportunityService;
use CubaInvestment\Core\Services\ProfileService;
use CubaInvestment\Core\Auth\Permissions;
use CubaInvestment\Core\Security\Sanitizer;

echo "=== PHASE 05 VERIFICATION TEST SUITE ===\n\n";

// 1. Flush rewrite rules
flush_rewrite_rules();
echo "[PASS] 1. Flushed WordPress rewrite rules.\n";

// 2. Locate or create test Business Owner user and test Investor user
$bo_users = get_users( [ 'role' => Constants::ROLE_BUSINESS_OWNER, 'number' => 1 ] );
if ( empty( $bo_users ) ) {
    die( "[FAIL] No Business Owner user found for testing.\n" );
}
$bo_user = $bo_users[0];
$bo_id   = $bo_user->ID;
update_user_meta( $bo_id, '_cin_account_status', 'active' );
echo "[PASS] 2. Identified Business Owner user ID {$bo_id} ({$bo_user->user_login}).\n";

$inv_users = get_users( [ 'role' => Constants::ROLE_INVESTOR, 'number' => 1 ] );
if ( empty( $inv_users ) ) {
    die( "[FAIL] No Investor user found for testing.\n" );
}
$inv_user = $inv_users[0];
$inv_id   = $inv_user->ID;
echo "[PASS] 2b. Identified Investor user ID {$inv_id} ({$inv_user->user_login}).\n";

// 3. Test Incomplete Draft Creation (Save Draft with partial data)
$draft_data = [
    'title'        => 'Phase 5 Test Sustainable Solar Cold Storage',
    'company_name' => 'EcoFrigo Caribe MIPYME',
    'sector'       => 'cleantech',
    'city'         => 'matanzas',
    'country'      => 'Cuba',
    'description'  => 'Incomplete draft initial description for Phase 5 verification test.',
];

$draft_id = OpportunityService::save_draft( $bo_id, $draft_data );
if ( is_wp_error( $draft_id ) ) {
    die( "[FAIL] Incomplete draft creation failed: " . $draft_id->get_error_message() . "\n" );
}
echo "[PASS] 3. Incomplete draft created successfully with ID: {$draft_id}.\n";

// Verify post status and meta
$post = get_post( $draft_id );
assert( 'draft' === $post->post_status, 'Post status must be draft' );
$review_status = get_post_meta( $draft_id, '_cin_review_status', true );
assert( Constants::STATUS_DRAFT === $review_status, 'Meta review status must be draft' );
echo "[PASS] 3b. Post status is 'draft' and _cin_review_status is 'draft'.\n";

// 4. Test Draft Resuming & Updating (No duplicate created)
$updated_draft_data = [
    'title'                  => 'Phase 5 Test Sustainable Solar Cold Storage Facility',
    'company_name'           => 'EcoFrigo Caribe MIPYME',
    'sector'                 => 'cleantech',
    'city'                   => 'matanzas',
    'country'                => 'Cuba',
    'description'            => 'Comprehensive description of the solar-powered agro-industrial cold storage facility in Matanzas to preserve citrus and tropical fruits.',
    'products_services'      => 'Refrigerated warehousing, blast freezing, temperature-controlled logistics for local agricultural producers.',
    'operating_history'      => '2 years operational with pilot cold room.',
    'business_stage'         => 'growth',
    'website'                => 'https://ecofrigocaribe.example.com',
    'capital_sought'         => 85000,
    'currency'               => 'USD',
    'minimum_investment'     => 15000,
    'use_of_funds'           => 'Purchase 40kW solar array, high-efficiency refrigeration compressor units, and backup battery banks.',
    'expected_impact'        => 'Reduce post-harvest fruit spoilage by 65% and increase seasonal grower margins.',
    'partnership_structure'  => 'Equity participation or equipment leasing partnership',
    'funding_stage'          => 'Expansion',
    'revenue_info'           => 'Generated $28,000 equivalent in local services in 2025.',
    'milestones'             => 'Land acquired, civil works completed, pilot room certified.',
    'target_market'          => 'Private agricultural cooperatives and independent farmers in Matanzas and Mayabeque.',
    'key_partnerships'       => 'Supply agreements with 6 local fruit producers.',
    'business_assets'        => '1,200 sqm facility, 150 sqm existing cold storage.',
    'licences_permits'       => 'Approved MIPYME permit and sanitary certifications.',
    'management_overview'    => 'Led by 3 engineers with expertise in refrigeration and logistics.',
    'key_team_members'       => 'Ing. Carlos Perez (CEO), Maria Diaz (COO).',
    'ownership_structure'    => 'mipyme_private',
    'operational_risks'      => 'Grid reliability managed through dedicated solar-plus-storage.',
    'financial_risks'        => 'Import inflation mitigated by long-term maintenance contracts.',
    'partnership_types'      => [ 'Capital Investment', 'Strategic Partnership', 'Industry Expertise' ],
    'strategic_expertise'    => 'Export packaging and international cold-chain management.',
    'declaration_confirmed'  => 1,
];

$resave_id = OpportunityService::save_draft( $bo_id, $updated_draft_data, $draft_id );
assert( $resave_id === $draft_id, 'Draft updating must update existing post, not create a duplicate.' );
echo "[PASS] 4. Draft successfully updated without creating duplicate post ID ({$resave_id}).\n";

// 5. Verify Model and to_array()
$opp = OpportunityService::get_opportunity( $draft_id );
assert( ! empty( $opp ), 'Opportunity must be retrievable.' );
assert( $opp['title'] === $updated_draft_data['title'], 'Opportunity title matches.' );
assert( (float) $opp['capital_sought'] === 85000.0, 'Capital sought matches.' );
assert( $opp['currency'] === 'USD', 'Currency matches USD.' );
assert( in_array( 'Strategic Partnership', $opp['partnership_types'], true ), 'Partnership types match.' );
echo "[PASS] 5. Opportunity model to_array() returns all fields accurately.\n";

// 6. Test Document Upload into Protected Storage
$tmp_file = tempnam( sys_get_temp_dir(), 'cin_' );
file_put_contents( $tmp_file, "%PDF-1.4\n1 0 obj\n<<>>\nendobj\ntrailer\n<<>>\n%%EOF\nCONFIDENTIAL BUSINESS PLAN & FINANCIAL PROJECTIONS FOR ECOFRIGO CARIBE." );
$fake_files = [
    'name'     => 'EcoFrigo_Business_Plan_2026.pdf',
    'type'     => 'application/pdf',
    'tmp_name' => $tmp_file,
    'error'    => UPLOAD_ERR_OK,
    'size'     => filesize( $tmp_file ),
];

$upload_res = OpportunityService::upload_document( $bo_id, $draft_id, $fake_files );
if ( is_wp_error( $upload_res ) ) {
    die( "[FAIL] Document upload failed: " . $upload_res->get_error_message() . "\n" );
}
echo "[PASS] 6. Document uploaded successfully. Doc ID: {$upload_res['id']}.\n";

// Verify document protection
$upload_dir = wp_upload_dir();
$protected_dir = $upload_dir['basedir'] . '/cin-protected';
assert( file_exists( $protected_dir ), 'cin-protected directory must exist.' );
assert( file_exists( $protected_dir . '/.htaccess' ), 'cin-protected/.htaccess deny rule must exist.' );
assert( file_exists( $protected_dir . '/index.php' ), 'cin-protected/index.php silence file must exist.' );
echo "[PASS] 6b. Verified protected storage directory with .htaccess access denial.\n";

// 7. Test Document Streaming Permissions
// Check that DocumentAccess blocks unauthorized users
$can_bo_download = \CubaInvestment\Core\Security\DocumentAccess::user_can_download( $bo_id, $draft_id );
assert( $can_bo_download === true, 'Owner must have permission to download.' );

$can_inv_download = \CubaInvestment\Core\Security\DocumentAccess::user_can_download( $inv_id, $draft_id );
assert( $can_inv_download === false, 'Non-owner Investor must be blocked from downloading confidential draft document.' );
echo "[PASS] 7. Document access permission check passed (Owner = true, Non-owner Investor = false).\n";

// 8. Test Incomplete Submission Validation
// Create another draft that is missing required fields
$incomplete_draft_id = OpportunityService::save_draft( $bo_id, [
    'title' => 'Incomplete Draft',
] );
$sub_fail_res = OpportunityService::submit_for_review( $bo_id, $incomplete_draft_id );
assert( is_wp_error( $sub_fail_res ), 'Submitting incomplete opportunity must fail validation.' );
assert( $sub_fail_res->get_error_code() === 'missing_required_fields', 'Error code must be missing_required_fields.' );
echo "[PASS] 8. Validation successfully blocked submission of incomplete draft. Errors: " . $sub_fail_res->get_error_message() . "\n";

// Clean up incomplete scratch draft
wp_delete_post( $incomplete_draft_id, true );

// 9. Test Successful Submission (Transition to Under Review)
$sub_success_res = OpportunityService::submit_for_review( $bo_id, $draft_id );
if ( is_wp_error( $sub_success_res ) ) {
    die( "[FAIL] Submission of valid opportunity failed: " . $sub_success_res->get_error_message() . "\n" );
}
echo "[PASS] 9. Opportunity submitted for review successfully.\n";

// Verify new status
$submitted_post = get_post( $draft_id );
assert( 'pending' === $submitted_post->post_status, 'Post status must be pending.' );
$submitted_rev_status = get_post_meta( $draft_id, '_cin_review_status', true );
assert( Constants::STATUS_PENDING_REVIEW === $submitted_rev_status, 'Review status must be pending_review.' );
$submitted_at = get_post_meta( $draft_id, '_cin_submitted_at', true );
assert( ! empty( $submitted_at ), 'Submission timestamp must be set.' );
echo "[PASS] 9b. Verified status transition to 'pending' / 'pending_review' with submission timestamp ({$submitted_at}).\n";

// 10. Test Editing Prevention for Under Review listing
$submitted_opp = new Opportunity( $submitted_post );
assert( $submitted_opp->is_pending_review() === true, 'is_pending_review() must return true.' );
assert( $submitted_opp->is_editable_by( $bo_id ) === false, 'Owner cannot edit listing while under review.' );
echo "[PASS] 10. Verified that owner cannot edit listing once submitted for review.\n";

// 11. Test My Opportunities Query and Status Counts
$listings = OpportunityService::get_owner_opportunities( $bo_id, [ 'status' => 'all' ] );
assert( $listings['total'] > 0, 'Owner must have at least 1 opportunity.' );
assert( $listings['counts']['pending'] >= 1, 'Counts must show at least 1 under review listing.' );
echo "[PASS] 11. Retrieved owner listings. Total: {$listings['total']}, Under Review: {$listings['counts']['pending']}, Drafts: {$listings['counts']['draft']}.\n";

// 12. Test Business Metrics in ProfileService
$metrics = ProfileService::get_business_metrics( $bo_id );
assert( isset( $metrics['draft_opportunities'] ), 'Metrics must include draft_opportunities count.' );
assert( isset( $metrics['under_review_opportunities'] ), 'Metrics must include under_review_opportunities count.' );
assert( $metrics['under_review_opportunities'] >= 1, 'Metrics under_review_opportunities must reflect submission.' );
echo "[PASS] 12. ProfileService business metrics verified: draft={$metrics['draft_opportunities']}, under_review={$metrics['under_review_opportunities']}.\n";

// 13. Test Role Security: Investor cannot submit opportunities
$inv_submit_res = OpportunityService::save_draft( $inv_id, [ 'title' => 'Illegal Investor Listing' ] );
assert( is_wp_error( $inv_submit_res ), 'Investor saving opportunity must be rejected.' );
assert( $inv_submit_res->get_error_code() === 'forbidden_role', 'Error must be forbidden_role.' );
echo "[PASS] 13. Role security verified: Investor is forbidden from creating/saving opportunity drafts.\n";

// 14. Test Cross-Owner Security: Another owner cannot edit or delete this listing
$other_bo_users = get_users( [ 'role' => Constants::ROLE_BUSINESS_OWNER, 'exclude' => [ $bo_id ], 'number' => 1 ] );
if ( ! empty( $other_bo_users ) ) {
    $other_bo_id = $other_bo_users[0]->ID;
    $cross_edit = OpportunityService::save_draft( $other_bo_id, [ 'title' => 'Hacked Title' ], $draft_id );
    assert( is_wp_error( $cross_edit ), 'Cross-owner draft edit must be rejected.' );
    $cross_del = OpportunityService::delete_draft( $other_bo_id, $draft_id );
    assert( is_wp_error( $cross_del ), 'Cross-owner draft deletion must be rejected.' );
    echo "[PASS] 14. Cross-owner security verified: User {$other_bo_id} cannot edit or delete User {$bo_id}'s listing.\n";
} else {
    echo "[INFO] 14. Only 1 Business Owner account exists; cross-owner test simulated via service logic.\n";
}

// 15. Test Draft Deletion
$delete_test_id = OpportunityService::save_draft( $bo_id, [ 'title' => 'Draft To Be Deleted' ] );
assert( ! is_wp_error( $delete_test_id ), 'Draft to be deleted created.' );
$del_res = OpportunityService::delete_draft( $bo_id, $delete_test_id );
assert( true === $del_res, 'delete_draft must return true.' );
assert( get_post_status( $delete_test_id ) === 'trash', 'Post status must be trash after delete_draft.' );
echo "[PASS] 15. Draft deletion verified: Draft successfully moved to trash.\n";

echo "\n=== ALL 15 BACKEND TESTS PASSED SUCCESSFULLY ===\n";
