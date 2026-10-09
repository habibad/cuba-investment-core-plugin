<?php
require_once dirname( __DIR__, 4 ) . '/wp-load.php';

use CubaInvestment\Core\Services\ProfileService;
use CubaInvestment\Core\Security\NonceManager;

$user_id = 35; // rahmananik523
wp_set_current_user( $user_id );

echo "=== TESTING BUSINESS OWNER PERSONAL PROFILE UPDATE ===\n";

$data = [
    'first_name'           => 'Rahman',
    'last_name'            => 'Anik',
    'country_of_residence' => 'Cuba',
    'phone_number'         => '+53 5 123 4567',
    'founder_bio'          => 'Entrepreneur testing founder profile bio.',
];

$res = ProfileService::update_business_owner_profile( $user_id, $data, [] );
if ( is_wp_error( $res ) ) {
    echo "[FAIL] update_business_owner_profile: " . $res->get_error_message() . "\n";
} else {
    echo "[PASS] update_business_owner_profile succeeded.\n";
}

$profile = ProfileService::get_business_owner_profile( $user_id );
echo "First Name: " . $profile['first_name'] . "\n";
echo "Phone: " . $profile['phone_number'] . "\n";
echo "Bio: " . $profile['founder_bio'] . "\n";
echo "Completion: " . $profile['completion']['percentage'] . "%\n\n";

echo "=== TESTING BUSINESS PROFILE UPDATE ===\n";

$biz_data = [
    'company_name'              => 'Caribe Solutions SRL',
    'company_sector'            => 'technology',
    'company_country'           => 'Cuba',
    'company_location_province' => 'la_habana',
    'company_website'           => 'https://caribesolutions.example.com',
    'company_description'       => 'Leading tech consulting and software development services in Havana.',
    'company_products_services' => 'Custom software, web applications, cloud hosting.',
    'company_year_established'  => 2022,
    'company_stage'             => 'growth',
    'company_legal_type'        => 'mipyme_private',
    'company_market_description'=> 'Domestic enterprises and international tourism operators.',
    'seeking_investment'        => 1,
    'seeking_partners'          => 1,
    'collaboration_interests'   => 'Looking for European angel investors and export partners.',
    'business_visibility'       => 'verified_investors',
];

$res2 = ProfileService::update_business_profile( $user_id, $biz_data, [] );
if ( is_wp_error( $res2 ) ) {
    echo "[FAIL] update_business_profile: " . $res2->get_error_message() . "\n";
} else {
    echo "[PASS] update_business_profile succeeded.\n";
}

$biz_profile = ProfileService::get_business_profile( $user_id );
echo "Company Name: " . $biz_profile['company_name'] . "\n";
echo "Sector: " . $biz_profile['company_sector'] . "\n";
echo "Province: " . $biz_profile['company_location_province'] . "\n";
echo "Seeking Investment: " . ( $biz_profile['seeking_investment'] ? 'Yes' : 'No' ) . "\n";
echo "Completion: " . $biz_profile['completion']['percentage'] . "%\n";
