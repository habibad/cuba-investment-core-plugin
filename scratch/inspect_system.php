<?php
require_once dirname(__DIR__, 4) . '/wp-load.php';

use CubaInvestment\Core\Common\Constants;
use CubaInvestment\Core\Database\Migrations;

echo "=== ENVIRONMENT & DATABASE AUDIT ===\n";
echo "WordPress Version: " . get_bloginfo('version') . "\n";
echo "PHP Version: " . phpversion() . "\n";
echo "Site URL: " . get_option('siteurl') . "\n";
echo "Home URL: " . get_option('home') . "\n";

echo "\n--- DB Migrations Table Verification ---\n";
$verified = Migrations::verify_tables();
echo "Tables verified: " . ($verified ? "YES" : "NO") . "\n";

global $wpdb;
$tables = [
    'inquiries' => Constants::get_table_name(Constants::TABLE_INQUIRIES),
    'connections' => Constants::get_table_name(Constants::TABLE_CONNECTIONS),
    'conversations' => Constants::get_table_name(Constants::TABLE_CONVERSATIONS),
    'messages' => Constants::get_table_name(Constants::TABLE_MESSAGES),
    'notifications' => Constants::get_table_name(Constants::TABLE_NOTIFICATIONS),
    'subscriptions' => Constants::get_table_name(Constants::TABLE_SUBSCRIPTIONS),
    'audit_logs' => Constants::get_table_name(Constants::TABLE_AUDIT_LOGS),
    'saved_opps' => Constants::get_table_name(Constants::TABLE_SAVED_OPPORTUNITIES),
];

foreach ($tables as $key => $table) {
    $exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table));
    if ($exists) {
        $count = $wpdb->get_var("SELECT COUNT(*) FROM `{$table}`");
        echo "Table {$table}: EXISTS ({$count} rows)\n";
    } else {
        echo "Table {$table}: MISSING!\n";
    }
}

echo "\n--- Users by Role ---\n";
$roles = [Constants::ROLE_INVESTOR, Constants::ROLE_BUSINESS_OWNER, 'administrator'];
foreach ($roles as $role) {
    $users = get_users(['role' => $role]);
    echo "Role '{$role}': " . count($users) . " users\n";
    foreach ($users as $u) {
        $status = get_user_meta($u->ID, '_cin_account_status', true);
        $verified = get_user_meta($u->ID, '_cin_email_verified', true);
        echo "  - ID {$u->ID}: {$u->user_login} ({$u->user_email}) | status: '{$status}' | verified: " . var_export($verified, true) . "\n";
    }
}

echo "\n--- Opportunities (CPT) ---\n";
$posts = get_posts([
    'post_type' => Constants::POST_TYPE_OPPORTUNITY,
    'post_status' => 'any',
    'numberposts' => -1,
]);
echo "Total Opportunities: " . count($posts) . "\n";
foreach ($posts as $p) {
    $r_status = get_post_meta($p->ID, '_cin_review_status', true);
    echo "  - ID {$p->ID}: '{$p->post_title}' | post_status: {$p->post_status} | review_status: {$r_status} | author: {$p->post_author}\n";
}
