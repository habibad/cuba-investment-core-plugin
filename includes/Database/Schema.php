<?php
/**
 * Cuba Investment Core - Database Schema Definitions
 *
 * @package CubaInvestment\Core
 */

namespace CubaInvestment\Core\Database;

use CubaInvestment\Core\Common\Constants;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Schema {

    /**
     * Get SQL creation statements for all custom tables
     * Formatted strictly for WordPress dbDelta() compatibility
     *
     * @return array Array of SQL DDL queries
     */
    public static function get_schema_queries() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        $t_inquiries     = Constants::get_table_name( Constants::TABLE_INQUIRIES );
        $t_connections   = Constants::get_table_name( Constants::TABLE_CONNECTIONS );
        $t_conversations = Constants::get_table_name( Constants::TABLE_CONVERSATIONS );
        $t_messages      = Constants::get_table_name( Constants::TABLE_MESSAGES );
        $t_notifications = Constants::get_table_name( Constants::TABLE_NOTIFICATIONS );
        $t_subscriptions = Constants::get_table_name( Constants::TABLE_SUBSCRIPTIONS );
        $t_audit_logs    = Constants::get_table_name( Constants::TABLE_AUDIT_LOGS );

        $queries = [];

        // 1. Inquiries Table
        $queries[] = "CREATE TABLE {$t_inquiries} (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  opportunity_id bigint(20) unsigned NOT NULL,
  investor_user_id bigint(20) unsigned NOT NULL,
  business_user_id bigint(20) unsigned NOT NULL,
  subject varchar(255) NOT NULL,
  message text NOT NULL,
  capital_range varchar(100) DEFAULT '' NOT NULL,
  status varchar(30) DEFAULT 'pending' NOT NULL,
  admin_notes text DEFAULT '' NOT NULL,
  created_at datetime NOT NULL,
  updated_at datetime NOT NULL,
  PRIMARY KEY  (id),
  KEY opportunity_id (opportunity_id),
  KEY investor_user_id (investor_user_id),
  KEY business_user_id (business_user_id),
  KEY status (status)
) {$charset_collate};";

        // 2. Direct Connections Table
        $queries[] = "CREATE TABLE {$t_connections} (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  investor_user_id bigint(20) unsigned NOT NULL,
  business_user_id bigint(20) unsigned NOT NULL,
  origin_inquiry_id bigint(20) unsigned DEFAULT 0 NOT NULL,
  opportunity_id bigint(20) unsigned DEFAULT 0 NOT NULL,
  status varchar(30) DEFAULT 'active' NOT NULL,
  connected_at datetime NOT NULL,
  updated_at datetime NOT NULL,
  PRIMARY KEY  (id),
  UNIQUE KEY user_pair (investor_user_id, business_user_id),
  KEY status (status)
) {$charset_collate};";

        // 3. Conversations Table
        $queries[] = "CREATE TABLE {$t_conversations} (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  connection_id bigint(20) unsigned NOT NULL,
  participant_one_id bigint(20) unsigned NOT NULL,
  participant_two_id bigint(20) unsigned NOT NULL,
  status varchar(30) DEFAULT 'active' NOT NULL,
  last_message_at datetime NOT NULL,
  created_at datetime NOT NULL,
  PRIMARY KEY  (id),
  KEY connection_id (connection_id),
  KEY participant_one (participant_one_id),
  KEY participant_two (participant_two_id),
  KEY last_message (last_message_at)
) {$charset_collate};";

        // 4. Messages Table
        $queries[] = "CREATE TABLE {$t_messages} (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  conversation_id bigint(20) unsigned NOT NULL,
  sender_user_id bigint(20) unsigned NOT NULL,
  recipient_user_id bigint(20) unsigned NOT NULL,
  message_body longtext NOT NULL,
  is_read tinyint(1) DEFAULT 0 NOT NULL,
  read_at datetime DEFAULT NULL,
  created_at datetime NOT NULL,
  PRIMARY KEY  (id),
  KEY conversation_id (conversation_id),
  KEY sender_user_id (sender_user_id),
  KEY recipient_read (recipient_user_id, is_read)
) {$charset_collate};";

        // 5. Notifications Table
        $queries[] = "CREATE TABLE {$t_notifications} (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  user_id bigint(20) unsigned NOT NULL,
  type varchar(50) NOT NULL,
  title varchar(255) NOT NULL,
  content text NOT NULL,
  action_url varchar(255) DEFAULT '' NOT NULL,
  is_read tinyint(1) DEFAULT 0 NOT NULL,
  read_at datetime DEFAULT NULL,
  created_at datetime NOT NULL,
  PRIMARY KEY  (id),
  KEY user_unread (user_id, is_read),
  KEY created_at (created_at)
) {$charset_collate};";

        // 6. Subscriptions Table (Future extensibility ready, Free tier launch)
        $queries[] = "CREATE TABLE {$t_subscriptions} (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  user_id bigint(20) unsigned NOT NULL,
  tier_slug varchar(50) DEFAULT 'free' NOT NULL,
  status varchar(30) DEFAULT 'active' NOT NULL,
  starts_at datetime NOT NULL,
  expires_at datetime DEFAULT NULL,
  features longtext DEFAULT '' NOT NULL,
  created_at datetime NOT NULL,
  updated_at datetime NOT NULL,
  PRIMARY KEY  (id),
  KEY user_id (user_id),
  KEY tier_status (tier_slug, status)
) {$charset_collate};";

        // 7. Audit Logs Table
        $queries[] = "CREATE TABLE {$t_audit_logs} (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  user_id bigint(20) unsigned DEFAULT 0 NOT NULL,
  level varchar(20) DEFAULT 'INFO' NOT NULL,
  action varchar(100) DEFAULT '' NOT NULL,
  message text NOT NULL,
  context longtext DEFAULT '' NOT NULL,
  ip_address varchar(45) DEFAULT '' NOT NULL,
  user_agent varchar(255) DEFAULT '' NOT NULL,
  created_at datetime NOT NULL,
  PRIMARY KEY  (id),
  KEY user_id (user_id),
  KEY action (action),
  KEY created_at (created_at)
) {$charset_collate};";

        // 8. Saved Opportunities Table
        $t_saved = Constants::get_table_name( Constants::TABLE_SAVED_OPPORTUNITIES );
        $queries[] = "CREATE TABLE {$t_saved} (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  user_id bigint(20) unsigned NOT NULL,
  opportunity_id bigint(20) unsigned NOT NULL,
  created_at datetime NOT NULL,
  PRIMARY KEY  (id),
  UNIQUE KEY user_opportunity (user_id, opportunity_id),
  KEY user_id (user_id),
  KEY opportunity_id (opportunity_id),
  KEY created_at (created_at)
) {$charset_collate};";

        return $queries;
    }
}
