<?php
/**
 * Cuba Investment Core - Constants Definition
 *
 * @package CubaInvestment\Core
 */

namespace CubaInvestment\Core\Common;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Constants {

    // Version Identifiers
    const VERSION    = '1.0.0';
    const DB_VERSION = '1.0.0';

    // Option Keys
    const OPTION_DB_VERSION     = 'cin_db_version';
    const OPTION_INSTALLED_AT   = 'cin_installed_at';
    const OPTION_SETTINGS       = 'cin_settings';
    const OPTION_LAUNCH_MODE    = 'cin_launch_mode';

    // User Roles
    const ROLE_INVESTOR       = 'cin_investor';
    const ROLE_BUSINESS_OWNER = 'cin_business_owner';

    // Capabilities - Investor
    const CAP_ACCESS_INVESTOR_PORTAL = 'cin_access_investor_portal';
    const CAP_VIEW_OPPORTUNITIES     = 'cin_view_opportunities';
    const CAP_SEND_INQUIRIES         = 'cin_send_inquiries';
    const CAP_MANAGE_CONNECTIONS     = 'cin_manage_connections';
    const CAP_SEND_MESSAGES          = 'cin_send_messages';
    const CAP_VIEW_DOCUMENTS         = 'cin_view_documents';

    // Capabilities - Business Owner
    const CAP_ACCESS_BUSINESS_PORTAL = 'cin_access_business_portal';
    const CAP_SUBMIT_OPPORTUNITY     = 'cin_submit_opportunity';
    const CAP_EDIT_OWN_OPPORTUNITY   = 'cin_edit_own_opportunity';
    const CAP_DELETE_OWN_OPPORTUNITY = 'cin_delete_own_opportunity';
    const CAP_VIEW_INQUIRIES         = 'cin_view_inquiries';
    const CAP_UPLOAD_DOCUMENTS       = 'cin_upload_documents';

    // Capabilities - Administrator / Reviewer
    const CAP_REVIEW_OPPORTUNITIES   = 'cin_review_opportunities';
    const CAP_PUBLISH_OPPORTUNITIES  = 'cin_publish_opportunities';
    const CAP_MODERATE_PLATFORM      = 'cin_moderate_platform';
    const CAP_VIEW_AUDIT_LOGS        = 'cin_view_audit_logs';

    // Post Type & Taxonomies
    const POST_TYPE_OPPORTUNITY = 'cin_opportunity';
    const TAX_SECTOR            = 'cin_sector';
    const TAX_PROVINCE          = 'cin_province';
    const TAX_STAGE             = 'cin_deal_stage';

    // Opportunity Statuses
    const STATUS_DRAFT              = 'draft';
    const STATUS_PENDING_REVIEW     = 'pending_review';
    const STATUS_APPROVED           = 'publish';
    const STATUS_REVISION_REQUESTED = 'revision_requested';
    const STATUS_ARCHIVED           = 'archived';
    const STATUS_REJECTED           = 'rejected';

    // Inquiry Statuses
    const INQUIRY_PENDING   = 'pending';
    const INQUIRY_ACCEPTED  = 'accepted';
    const INQUIRY_DECLINED  = 'declined';
    const INQUIRY_WITHDRAWN = 'withdrawn';
    const INQUIRY_CLOSED    = 'closed';

    // Connection Statuses
    const CONNECTION_REQUESTED  = 'requested';
    const CONNECTION_ACTIVE     = 'active';
    const CONNECTION_PAUSED     = 'paused';
    const CONNECTION_BLOCKED    = 'blocked';
    const CONNECTION_TERMINATED = 'terminated';

    // Conversation Statuses
    const CONVERSATION_ACTIVE   = 'active';
    const CONVERSATION_ARCHIVED = 'archived';
    const CONVERSATION_BLOCKED  = 'blocked';

    // Membership / Entitlement Tiers
    const TIER_FREE       = 'free';
    const TIER_LAUNCH     = 'launch_free';
    const TIER_PREMIUM    = 'premium'; // Reserved for future phase
    const TIER_ENTERPRISE = 'enterprise'; // Reserved for future phase

    // REST API
    const API_NAMESPACE = 'cin/v1';

    // Table Suffixes
    const TABLE_INQUIRIES     = 'cin_inquiries';
    const TABLE_CONNECTIONS   = 'cin_connections';
    const TABLE_CONVERSATIONS = 'cin_conversations';
    const TABLE_MESSAGES      = 'cin_messages';
    const TABLE_NOTIFICATIONS = 'cin_notifications';
    const TABLE_SUBSCRIPTIONS = 'cin_subscriptions';
    const TABLE_AUDIT_LOGS    = 'cin_audit_logs';

    /**
     * Get full table name with WordPress prefix
     *
     * @param string $suffix Table suffix
     * @return string
     */
    public static function get_table_name( $suffix ) {
        global $wpdb;
        return $wpdb->prefix . $suffix;
    }
}
