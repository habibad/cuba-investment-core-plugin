<?php
/**
 * Cuba Investment Core - Admin Dashboard, Listing Review & User Management Interface
 *
 * @package CubaInvestment\Core
 */

namespace CubaInvestment\Core\Admin;

use CubaInvestment\Core\Common\Constants;
use CubaInvestment\Core\Common\Logger;
use CubaInvestment\Core\Services\OpportunityService;
use CubaInvestment\Core\Services\ProfileService;
use CubaInvestment\Core\Security\NonceManager;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AdminManager {

    public static function init() {
        add_action( 'admin_menu', [ __CLASS__, 'register_admin_menus' ] );
        add_action( 'admin_post_cin_admin_review_action', [ __CLASS__, 'handle_review_action' ] );
        add_action( 'admin_post_cin_admin_toggle_user_status', [ __CLASS__, 'handle_toggle_user_status' ] );
    }

    public static function register_admin_menus() {
        // Submenu for Listing Reviews
        add_submenu_page(
            'edit.php?post_type=' . Constants::POST_TYPE_OPPORTUNITY,
            __( 'Listing Review Queue', 'cuba-investment-core' ),
            __( 'Review Queue', 'cuba-investment-core' ),
            Constants::CAP_REVIEW_OPPORTUNITIES,
            'cin-listing-reviews',
            [ __CLASS__, 'render_review_queue_page' ]
        );

        // Submenu for Platform Accounts Management (Phase 04)
        add_submenu_page(
            'edit.php?post_type=' . Constants::POST_TYPE_OPPORTUNITY,
            __( 'Platform Accounts', 'cuba-investment-core' ),
            __( 'Platform Accounts', 'cuba-investment-core' ),
            'manage_options',
            'cin-user-management',
            [ __CLASS__, 'render_user_management_page' ]
        );

        // Submenu for Audit Logs
        add_submenu_page(
            'edit.php?post_type=' . Constants::POST_TYPE_OPPORTUNITY,
            __( 'Security Audit Logs', 'cuba-investment-core' ),
            __( 'Audit Logs', 'cuba-investment-core' ),
            Constants::CAP_VIEW_AUDIT_LOGS,
            'cin-audit-logs',
            [ __CLASS__, 'render_audit_logs_page' ]
        );
    }

    /**
     * Render Opportunity Review Queue Page
     */
    public static function render_review_queue_page() {
        if ( ! current_user_can( Constants::CAP_REVIEW_OPPORTUNITIES ) ) {
            wp_die( esc_html__( 'Unauthorized.', 'cuba-investment-core' ) );
        }

        // Fetch pending opportunities
        $pending_query = new \WP_Query( [
            'post_type'      => Constants::POST_TYPE_OPPORTUNITY,
            'post_status'    => [ 'pending', 'draft' ],
            'posts_per_page' => 50,
            'orderby'        => 'date',
            'order'          => 'DESC',
        ] );
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php esc_html_e( 'Cuba Investment Network — Listing Review Queue', 'cuba-investment-core' ); ?></h1>
            <p class="description">
                <?php esc_html_e( 'Review submitted Cuban business profiles for clarity, completeness, and compliance with platform standards before publication.', 'cuba-investment-core' ); ?>
            </p>
            <hr class="wp-header-end" />

            <?php if ( isset( $_GET['message'] ) && 'approved' === $_GET['message'] ) : ?>
                <div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Listing approved and published successfully.', 'cuba-investment-core' ); ?></p></div>
            <?php elseif ( isset( $_GET['message'] ) && 'revision' === $_GET['message'] ) : ?>
                <div class="notice notice-warning is-dismissible"><p><?php esc_html_e( 'Revision request recorded and sent to business owner.', 'cuba-investment-core' ); ?></p></div>
            <?php endif; ?>

            <table class="wp-list-table widefat fixed striped table-view-list posts" style="margin-top: 15px;">
                <thead>
                    <tr>
                        <th scope="col" style="width: 25%;"><?php esc_html_e( 'Opportunity Title & Company', 'cuba-investment-core' ); ?></th>
                        <th scope="col" style="width: 15%;"><?php esc_html_e( 'Author / Owner', 'cuba-investment-core' ); ?></th>
                        <th scope="col" style="width: 15%;"><?php esc_html_e( 'Capital Sought', 'cuba-investment-core' ); ?></th>
                        <th scope="col" style="width: 15%;"><?php esc_html_e( 'Current Status', 'cuba-investment-core' ); ?></th>
                        <th scope="col" style="width: 15%;"><?php esc_html_e( 'Submitted Date', 'cuba-investment-core' ); ?></th>
                        <th scope="col" style="width: 15%;"><?php esc_html_e( 'Review Actions', 'cuba-investment-core' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( $pending_query->have_posts() ) : ?>
                        <?php while ( $pending_query->have_posts() ) : $pending_query->the_post(); ?>
                            <?php
                            $p_id     = get_the_ID();
                            $company  = get_post_meta( $p_id, '_cin_company_name', true ) ?: get_the_title();
                            $capital  = get_post_meta( $p_id, '_cin_capital_sought', true );
                            $curr     = get_post_meta( $p_id, '_cin_currency', true ) ?: 'USD';
                            $status   = get_post_meta( $p_id, '_cin_review_status', true ) ?: 'pending_review';
                            $author   = get_userdata( get_the_author_meta( 'ID' ) );
                            ?>
                            <tr>
                                <td>
                                    <strong><a href="<?php echo esc_url( get_edit_post_link( $p_id ) ); ?>"><?php the_title(); ?></a></strong>
                                    <div class="row-actions"><?php echo esc_html( $company ); ?></div>
                                </td>
                                <td><?php echo esc_html( $author ? $author->display_name : 'Unknown' ); ?></td>
                                <td><strong>$<?php echo esc_html( number_format( (float) $capital, 2 ) ); ?> <?php echo esc_html( $curr ); ?></strong></td>
                                <td><span class="badge" style="background:#e0f2fe; color:#0369a1; padding:3px 8px; border-radius:4px; font-weight:600;"><?php echo esc_html( ucfirst( str_replace( '_', ' ', $status ) ) ); ?></span></td>
                                <td><?php echo esc_html( get_the_date( 'M j, Y' ) ); ?></td>
                                <td>
                                    <form method="POST" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline-block;">
                                        <?php NonceManager::field( NonceManager::ACTION_ADMIN, '_cin_admin_nonce' ); ?>
                                        <input type="hidden" name="action" value="cin_admin_review_action" />
                                        <input type="hidden" name="post_id" value="<?php echo esc_attr( $p_id ); ?>" />
                                        <input type="hidden" name="review_decision" value="approve" />
                                        <button type="submit" class="button button-primary button-small"><?php esc_html_e( 'Approve', 'cuba-investment-core' ); ?></button>
                                    </form>
                                    <a href="<?php echo esc_url( get_edit_post_link( $p_id ) ); ?>" class="button button-secondary button-small"><?php esc_html_e( 'Review', 'cuba-investment-core' ); ?></a>
                                </td>
                            </tr>
                        <?php endwhile; wp_reset_postdata(); ?>
                    <?php else : ?>
                        <tr>
                            <td colspan="6" style="text-align:center; padding: 20px; color:#64748b;">
                                <?php esc_html_e( 'No listings currently awaiting review. All submissions are up to date.', 'cuba-investment-core' ); ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    /**
     * Render Platform Accounts Management Page (Phase 04 Section 10)
     */
    public static function render_user_management_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Unauthorized access.', 'cuba-investment-core' ) );
        }

        $search        = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
        $role_filter   = isset( $_GET['role_filter'] ) ? sanitize_key( $_GET['role_filter'] ) : '';
        $status_filter = isset( $_GET['status_filter'] ) ? sanitize_key( $_GET['status_filter'] ) : '';
        $paged         = max( 1, absint( $_GET['paged'] ?? 1 ) );
        $per_page      = 20;

        // Base Query Args
        $query_args = [
            'number'  => $per_page,
            'paged'   => $paged,
            'orderby' => 'user_registered',
            'order'   => 'DESC',
        ];

        if ( ! empty( $role_filter ) ) {
            $query_args['role'] = $role_filter;
        } else {
            $query_args['role__in'] = [ Constants::ROLE_INVESTOR, Constants::ROLE_BUSINESS_OWNER ];
        }

        if ( ! empty( $search ) ) {
            global $wpdb;
            $like = '%' . $wpdb->esc_like( $search ) . '%';
            $meta_user_ids = $wpdb->get_col( $wpdb->prepare(
                "SELECT DISTINCT user_id FROM {$wpdb->usermeta} 
                 WHERE meta_key IN ('first_name', 'last_name', '_cin_company_name', '_cin_business_name') 
                 AND meta_value LIKE %s",
                $like
            ) );

            $users_table_ids = $wpdb->get_col( $wpdb->prepare(
                "SELECT ID FROM {$wpdb->users} 
                 WHERE user_login LIKE %s OR user_email LIKE %s OR display_name LIKE %s OR user_nicename LIKE %s",
                $like, $like, $like, $like
            ) );

            $matched_ids = array_unique( array_merge( $meta_user_ids, $users_table_ids ) );
            if ( empty( $matched_ids ) ) {
                $query_args['include'] = [ 0 ];
            } else {
                $query_args['include'] = $matched_ids;
            }
        }

        if ( ! empty( $status_filter ) ) {
            $query_args['meta_query'] = [
                [
                    'key'   => '_cin_account_status',
                    'value' => $status_filter,
                ],
            ];
        }

        $user_query = new \WP_User_Query( $query_args );
        $users      = $user_query->get_results();
        $total_users = $user_query->get_total_users();
        $total_pages = ceil( $total_users / $per_page );

        // Counts for summary metrics
        $count_investors = count_users()['avail_roles'][ Constants::ROLE_INVESTOR ] ?? 0;
        $count_business  = count_users()['avail_roles'][ Constants::ROLE_BUSINESS_OWNER ] ?? 0;
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php esc_html_e( 'Cuba Investment Network — Platform Accounts', 'cuba-investment-core' ); ?></h1>
            <p class="description">
                <?php esc_html_e( 'Manage registered Investor and Business Owner accounts, inspect email verification and profile completeness, and perform suspension/reactivation operations.', 'cuba-investment-core' ); ?>
            </p>
            <hr class="wp-header-end" />

            <!-- Feedback notices -->
            <?php if ( isset( $_GET['message'] ) && 'status_updated' === $_GET['message'] ) : ?>
                <div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Account status successfully updated.', 'cuba-investment-core' ); ?></p></div>
            <?php elseif ( isset( $_GET['error'] ) && 'self_lockout' === $_GET['error'] ) : ?>
                <div class="notice notice-error is-dismissible"><p><?php esc_html_e( 'Security Protection: You cannot suspend your own active administrator account.', 'cuba-investment-core' ); ?></p></div>
            <?php elseif ( isset( $_GET['error'] ) && 'admin_protect' === $_GET['error'] ) : ?>
                <div class="notice notice-error is-dismissible"><p><?php esc_html_e( 'Security Protection: Administrator accounts cannot be suspended through platform user management.', 'cuba-investment-core' ); ?></p></div>
            <?php endif; ?>

            <!-- Metrics bar -->
            <div style="display:flex; gap:16px; margin: 15px 0 20px 0;">
                <div style="background:#fff; border:1px solid #c3c4c7; padding:12px 18px; border-radius:6px; min-width:140px;">
                    <div style="font-size:12px; color:#64748b; font-weight:600; text-transform:uppercase;"><?php esc_html_e( 'Total Investors', 'cuba-investment-core' ); ?></div>
                    <div style="font-size:24px; font-weight:700; color:#0f172a; margin-top:4px;"><?php echo esc_html( number_format_i18n( $count_investors ) ); ?></div>
                </div>
                <div style="background:#fff; border:1px solid #c3c4c7; padding:12px 18px; border-radius:6px; min-width:140px;">
                    <div style="font-size:12px; color:#64748b; font-weight:600; text-transform:uppercase;"><?php esc_html_e( 'Total Business Owners', 'cuba-investment-core' ); ?></div>
                    <div style="font-size:24px; font-weight:700; color:#0f172a; margin-top:4px;"><?php echo esc_html( number_format_i18n( $count_business ) ); ?></div>
                </div>
                <div style="background:#fff; border:1px solid #c3c4c7; padding:12px 18px; border-radius:6px; min-width:140px;">
                    <div style="font-size:12px; color:#64748b; font-weight:600; text-transform:uppercase;"><?php esc_html_e( 'Filtered Results', 'cuba-investment-core' ); ?></div>
                    <div style="font-size:24px; font-weight:700; color:#0369a1; margin-top:4px;"><?php echo esc_html( number_format_i18n( $total_users ) ); ?></div>
                </div>
            </div>

            <!-- Filter & Search Toolbar -->
            <form method="GET" action="<?php echo esc_url( admin_url( 'edit.php' ) ); ?>" style="margin-bottom:15px; display:flex; flex-wrap:wrap; gap:10px; align-items:center;">
                <input type="hidden" name="post_type" value="<?php echo esc_attr( Constants::POST_TYPE_OPPORTUNITY ); ?>" />
                <input type="hidden" name="page" value="cin-user-management" />

                <!-- Role Filter -->
                <select name="role_filter">
                    <option value=""><?php esc_html_e( 'All Account Types', 'cuba-investment-core' ); ?></option>
                    <option value="<?php echo esc_attr( Constants::ROLE_INVESTOR ); ?>" <?php selected( $role_filter, Constants::ROLE_INVESTOR ); ?>><?php esc_html_e( 'Investors', 'cuba-investment-core' ); ?></option>
                    <option value="<?php echo esc_attr( Constants::ROLE_BUSINESS_OWNER ); ?>" <?php selected( $role_filter, Constants::ROLE_BUSINESS_OWNER ); ?>><?php esc_html_e( 'Business Owners', 'cuba-investment-core' ); ?></option>
                </select>

                <!-- Status Filter -->
                <select name="status_filter">
                    <option value=""><?php esc_html_e( 'All Account Statuses', 'cuba-investment-core' ); ?></option>
                    <option value="active" <?php selected( $status_filter, 'active' ); ?>><?php esc_html_e( 'Active', 'cuba-investment-core' ); ?></option>
                    <option value="pending_verification" <?php selected( $status_filter, 'pending_verification' ); ?>><?php esc_html_e( 'Pending Verification', 'cuba-investment-core' ); ?></option>
                    <option value="suspended" <?php selected( $status_filter, 'suspended' ); ?>><?php esc_html_e( 'Suspended', 'cuba-investment-core' ); ?></option>
                </select>

                <!-- Search Input -->
                <input type="search" name="s" value="<?php echo esc_attr( $search ); ?>" placeholder="<?php esc_attr_e( 'Search by name or email...', 'cuba-investment-core' ); ?>" style="width:240px;" />

                <button type="submit" class="button button-primary"><?php esc_html_e( 'Filter Accounts', 'cuba-investment-core' ); ?></button>

                <?php if ( ! empty( $role_filter ) || ! empty( $status_filter ) || ! empty( $search ) ) : ?>
                    <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=' . Constants::POST_TYPE_OPPORTUNITY . '&page=cin-user-management' ) ); ?>" class="button button-secondary"><?php esc_html_e( 'Reset Filters', 'cuba-investment-core' ); ?></a>
                <?php endif; ?>
            </form>

            <!-- Accounts Table -->
            <table class="wp-list-table widefat fixed striped table-view-list users">
                <thead>
                    <tr>
                        <th scope="col" style="width: 20%;"><?php esc_html_e( 'User & Email', 'cuba-investment-core' ); ?></th>
                        <th scope="col" style="width: 12%;"><?php esc_html_e( 'Account Role', 'cuba-investment-core' ); ?></th>
                        <th scope="col" style="width: 14%;"><?php esc_html_e( 'Email Verification', 'cuba-investment-core' ); ?></th>
                        <th scope="col" style="width: 12%;"><?php esc_html_e( 'Membership Tier', 'cuba-investment-core' ); ?></th>
                        <th scope="col" style="width: 14%;"><?php esc_html_e( 'Profile Completeness', 'cuba-investment-core' ); ?></th>
                        <th scope="col" style="width: 10%;"><?php esc_html_e( 'Account Status', 'cuba-investment-core' ); ?></th>
                        <th scope="col" style="width: 18%;"><?php esc_html_e( 'Account Actions', 'cuba-investment-core' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( ! empty( $users ) ) : ?>
                        <?php foreach ( $users as $u ) : ?>
                            <?php
                            $roles       = (array) $u->roles;
                            $is_inv      = in_array( Constants::ROLE_INVESTOR, $roles, true );
                            $is_biz      = in_array( Constants::ROLE_BUSINESS_OWNER, $roles, true );
                            $is_adm      = in_array( 'administrator', $roles, true );
                            $acc_status  = get_user_meta( $u->ID, '_cin_account_status', true ) ?: 'active';
                            $is_verified = (bool) get_user_meta( $u->ID, '_cin_email_verified', true );
                            $first       = get_user_meta( $u->ID, 'first_name', true ) ?: $u->first_name;
                            $last        = get_user_meta( $u->ID, 'last_name', true ) ?: $u->last_name;
                            $full_name   = trim( $first . ' ' . $last ) ?: $u->display_name;

                            // Calculate profile completion
                            $comp_pct = 0;
                            if ( $is_inv ) {
                                $comp_pct = ProfileService::calculate_investor_completion( $u->ID )['percentage'];
                            } elseif ( $is_biz ) {
                                $comp_pct = ProfileService::calculate_business_owner_completion( $u->ID )['percentage'];
                            }
                            ?>
                            <tr>
                                <td>
                                    <strong><?php echo esc_html( $full_name ); ?></strong>
                                    <?php 
                                    $comp_name = get_user_meta( $u->ID, '_cin_company_name', true ) ?: get_user_meta( $u->ID, '_cin_business_name', true );
                                    if ( $comp_name ) : ?>
                                        <div style="font-size:11px; color:#0369a1; font-weight:600;"><?php echo esc_html( $comp_name ); ?></div>
                                    <?php endif; ?>
                                    <div class="row-actions" style="margin-top:2px;">
                                        <a href="mailto:<?php echo esc_attr( $u->user_email ); ?>"><?php echo esc_html( $u->user_email ); ?></a>
                                        <span style="color:#94a3b8;"> · ID #<?php echo esc_html( $u->ID ); ?></span>
                                    </div>
                                </td>
                                <td>
                                    <?php if ( $is_inv ) : ?>
                                        <span style="background:#e0f2fe; color:#0369a1; padding:2px 8px; border-radius:4px; font-weight:600; font-size:11px;">
                                            <?php esc_html_e( 'Investor', 'cuba-investment-core' ); ?>
                                        </span>
                                    <?php elseif ( $is_biz ) : ?>
                                        <span style="background:#fef3c7; color:#92400e; padding:2px 8px; border-radius:4px; font-weight:600; font-size:11px;">
                                            <?php esc_html_e( 'Business Owner', 'cuba-investment-core' ); ?>
                                        </span>
                                    <?php elseif ( $is_adm ) : ?>
                                        <span style="background:#f1f5f9; color:#475569; padding:2px 8px; border-radius:4px; font-weight:600; font-size:11px;">
                                            <?php esc_html_e( 'Administrator', 'cuba-investment-core' ); ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ( $is_verified ) : ?>
                                        <span style="background:#dcfce7; color:#15803d; padding:2px 8px; border-radius:4px; font-weight:600; font-size:11px;">
                                            ✓ <?php esc_html_e( 'Verified', 'cuba-investment-core' ); ?>
                                        </span>
                                    <?php else : ?>
                                        <span style="background:#fef3c7; color:#b45309; padding:2px 8px; border-radius:4px; font-weight:600; font-size:11px;">
                                            ● <?php esc_html_e( 'Pending', 'cuba-investment-core' ); ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span style="font-weight:600; color:#334155; font-size:12px;">
                                        <?php esc_html_e( 'Launch Free', 'cuba-investment-core' ); ?>
                                    </span>
                                    <span style="color:#64748b; font-size:11px; display:block;">$0.00 / mo</span>
                                </td>
                                <td>
                                    <div style="display:flex; align-items:center; gap:8px;">
                                        <div style="flex:1; background:#e2e8f0; height:8px; border-radius:4px; overflow:hidden;">
                                            <div style="width:<?php echo esc_attr( $comp_pct ); ?>%; background:<?php echo $comp_pct >= 80 ? '#10b981' : ( $comp_pct >= 50 ? '#0284c7' : '#f59e0b' ); ?>; height:100%;"></div>
                                        </div>
                                        <span style="font-weight:700; font-size:11px; color:#1e293b; min-width:32px;"><?php echo esc_html( $comp_pct ); ?>%</span>
                                    </div>
                                </td>
                                <td>
                                    <?php if ( 'active' === $acc_status ) : ?>
                                        <span style="color:#16a34a; font-weight:700; font-size:12px;">● <?php esc_html_e( 'Active', 'cuba-investment-core' ); ?></span>
                                    <?php elseif ( 'suspended' === $acc_status ) : ?>
                                        <span style="color:#dc2626; font-weight:700; font-size:12px;">● <?php esc_html_e( 'Suspended', 'cuba-investment-core' ); ?></span>
                                    <?php else : ?>
                                        <span style="color:#d97706; font-weight:700; font-size:12px;">● <?php echo esc_html( ucfirst( $acc_status ) ); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ( $is_adm || $u->ID === get_current_user_id() ) : ?>
                                        <span style="color:#94a3b8; font-size:11px; font-style:italic;"><?php esc_html_e( 'Protected Account', 'cuba-investment-core' ); ?></span>
                                    <?php elseif ( 'suspended' === $acc_status ) : ?>
                                        <form method="POST" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline-block;">
                                            <?php NonceManager::field( NonceManager::ACTION_ADMIN, '_cin_admin_nonce' ); ?>
                                            <input type="hidden" name="action" value="cin_admin_toggle_user_status" />
                                            <input type="hidden" name="target_user_id" value="<?php echo esc_attr( $u->ID ); ?>" />
                                            <input type="hidden" name="new_status" value="active" />
                                            <button type="submit" class="button button-small" style="color:#16a34a; border-color:#86efac;">
                                                <?php esc_html_e( 'Reactivate', 'cuba-investment-core' ); ?>
                                            </button>
                                        </form>
                                    <?php else : ?>
                                        <form method="POST" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline-block;" onsubmit="return confirm('<?php echo esc_js( sprintf( __( 'Are you sure you want to suspend %s (#%d)? They will be immediately blocked from accessing protected portal features.', 'cuba-investment-core' ), $full_name, $u->ID ) ); ?>');">
                                            <?php NonceManager::field( NonceManager::ACTION_ADMIN, '_cin_admin_nonce' ); ?>
                                            <input type="hidden" name="action" value="cin_admin_toggle_user_status" />
                                            <input type="hidden" name="target_user_id" value="<?php echo esc_attr( $u->ID ); ?>" />
                                            <input type="hidden" name="new_status" value="suspended" />
                                            <button type="submit" class="button button-small" style="color:#dc2626; border-color:#fca5a5;">
                                                <?php esc_html_e( 'Suspend', 'cuba-investment-core' ); ?>
                                            </button>
                                        </form>
                                    <?php endif; ?>

                                    <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=' . Constants::POST_TYPE_OPPORTUNITY . '&page=cin-audit-logs&filter_user_id=' . $u->ID ) ); ?>" class="button button-small button-link" style="margin-left:4px;">
                                        <?php esc_html_e( 'Logs', 'cuba-investment-core' ); ?>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr>
                            <td colspan="7" style="text-align:center; padding:24px; color:#64748b;">
                                <?php esc_html_e( 'No platform accounts found matching the current criteria.', 'cuba-investment-core' ); ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <!-- Pagination -->
            <?php if ( $total_pages > 1 ) : ?>
                <div class="tablenav bottom" style="margin-top:15px;">
                    <div class="tablenav-pages">
                        <span class="displaying-num"><?php echo esc_html( sprintf( __( '%d accounts', 'cuba-investment-core' ), $total_users ) ); ?></span>
                        <?php
                        echo paginate_links( [
                            'base'      => add_query_arg( 'paged', '%#%' ),
                            'format'    => '',
                            'prev_text' => '&laquo;',
                            'next_text' => '&raquo;',
                            'total'     => $total_pages,
                            'current'   => $paged,
                        ] );
                        ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Handle Admin Toggle User Status (Suspend / Reactivate)
     */
    public static function handle_toggle_user_status() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Unauthorized request.', 'cuba-investment-core' ), 403 );
        }

        if ( ! isset( $_POST['_cin_admin_nonce'] ) || ! NonceManager::verify( sanitize_text_field( wp_unslash( $_POST['_cin_admin_nonce'] ) ), NonceManager::ACTION_ADMIN ) ) {
            wp_die( esc_html__( 'Security token expired.', 'cuba-investment-core' ), 403 );
        }

        $target_user_id = isset( $_POST['target_user_id'] ) ? absint( $_POST['target_user_id'] ) : 0;
        $new_status     = isset( $_POST['new_status'] ) ? sanitize_key( $_POST['new_status'] ) : '';

        if ( ! in_array( $new_status, [ 'active', 'suspended' ], true ) ) {
            wp_die( esc_html__( 'Invalid status value.', 'cuba-investment-core' ), 400 );
        }

        $target_user = get_userdata( $target_user_id );
        if ( ! $target_user ) {
            wp_die( esc_html__( 'Target user does not exist.', 'cuba-investment-core' ), 404 );
        }

        // Prevent self-lockout of the acting administrator
        if ( $target_user_id === get_current_user_id() ) {
            wp_safe_redirect( add_query_arg( [ 'page' => 'cin-user-management', 'post_type' => Constants::POST_TYPE_OPPORTUNITY, 'error' => 'self_lockout' ], admin_url( 'edit.php' ) ) );
            exit;
        }

        // Prevent suspending any administrator account
        if ( in_array( 'administrator', (array) $target_user->roles, true ) ) {
            wp_safe_redirect( add_query_arg( [ 'page' => 'cin-user-management', 'post_type' => Constants::POST_TYPE_OPPORTUNITY, 'error' => 'admin_protect' ], admin_url( 'edit.php' ) ) );
            exit;
        }

        // Apply status update
        update_user_meta( $target_user_id, '_cin_account_status', $new_status );

        // Log audit event
        Logger::audit(
            'account_status_changed',
            sprintf( 'Administrator #%d changed account #%d status to %s', get_current_user_id(), $target_user_id, $new_status ),
            [
                'admin_id'       => get_current_user_id(),
                'target_user_id' => $target_user_id,
                'target_email'   => $target_user->user_email,
                'new_status'     => $new_status,
            ]
        );

        wp_safe_redirect( add_query_arg( [ 'page' => 'cin-user-management', 'post_type' => Constants::POST_TYPE_OPPORTUNITY, 'message' => 'status_updated' ], admin_url( 'edit.php' ) ) );
        exit;
    }

    /**
     * Render Security Audit Logs Page
     */
    public static function render_audit_logs_page() {
        if ( ! current_user_can( Constants::CAP_VIEW_AUDIT_LOGS ) ) {
            wp_die( esc_html__( 'Unauthorized.', 'cuba-investment-core' ) );
        }

        global $wpdb;
        $table = Constants::get_table_name( Constants::TABLE_AUDIT_LOGS );

        $filter_user_id = isset( $_GET['filter_user_id'] ) ? absint( $_GET['filter_user_id'] ) : 0;

        if ( $filter_user_id > 0 ) {
            $logs = $wpdb->get_results( $wpdb->prepare(
                "SELECT * FROM {$table} WHERE user_id = %d ORDER BY created_at DESC LIMIT 100",
                $filter_user_id
            ) );
        } else {
            $logs = $wpdb->get_results( "SELECT * FROM {$table} ORDER BY created_at DESC LIMIT 100" );
        }
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Security & Governance Audit Trail', 'cuba-investment-core' ); ?></h1>
            <p class="description"><?php esc_html_e( 'Chronological immutable log of critical platform actions, user registration, listing approvals and security events.', 'cuba-investment-core' ); ?></p>

            <?php if ( $filter_user_id > 0 ) : ?>
                <div class="notice notice-info" style="margin-top:10px;">
                    <p>
                        <?php echo esc_html( sprintf( __( 'Filtered for User #%d.', 'cuba-investment-core' ), $filter_user_id ) ); ?>
                        <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=' . Constants::POST_TYPE_OPPORTUNITY . '&page=cin-audit-logs' ) ); ?>" style="margin-left:8px; font-weight:600;">
                            &larr; <?php esc_html_e( 'View All Audit Logs', 'cuba-investment-core' ); ?>
                        </a>
                    </p>
                </div>
            <?php endif; ?>

            <table class="wp-list-table widefat fixed striped" style="margin-top: 15px;">
                <thead>
                    <tr>
                        <th style="width: 15%;"><?php esc_html_e( 'Timestamp (UTC)', 'cuba-investment-core' ); ?></th>
                        <th style="width: 10%;"><?php esc_html_e( 'Level', 'cuba-investment-core' ); ?></th>
                        <th style="width: 15%;"><?php esc_html_e( 'Action', 'cuba-investment-core' ); ?></th>
                        <th style="width: 35%;"><?php esc_html_e( 'Message', 'cuba-investment-core' ); ?></th>
                        <th style="width: 15%;"><?php esc_html_e( 'IP Address', 'cuba-investment-core' ); ?></th>
                        <th style="width: 10%;"><?php esc_html_e( 'User ID', 'cuba-investment-core' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( ! empty( $logs ) ) : ?>
                        <?php foreach ( $logs as $l ) : ?>
                            <tr>
                                <td><?php echo esc_html( $l->created_at ); ?></td>
                                <td><strong><?php echo esc_html( $l->level ); ?></strong></td>
                                <td><code><?php echo esc_html( $l->action ); ?></code></td>
                                <td><?php echo esc_html( $l->message ); ?></td>
                                <td><?php echo esc_html( $l->ip_address ); ?></td>
                                <td><?php echo esc_html( $l->user_id ? "User #{$l->user_id}" : 'System' ); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr><td colspan="6" style="text-align:center;"><?php esc_html_e( 'No audit entries found.', 'cuba-investment-core' ); ?></td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    /**
     * Handle Listing Review Decisions (Approve / Reject)
     */
    public static function handle_review_action() {
        if ( ! current_user_can( Constants::CAP_REVIEW_OPPORTUNITIES ) ) {
            wp_die( esc_html__( 'Unauthorized.', 'cuba-investment-core' ) );
        }

        if ( ! isset( $_POST['_cin_admin_nonce'] ) || ! NonceManager::verify( sanitize_text_field( wp_unslash( $_POST['_cin_admin_nonce'] ) ), NonceManager::ACTION_ADMIN ) ) {
            wp_die( esc_html__( 'Security token expired.', 'cuba-investment-core' ) );
        }

        $post_id  = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
        $decision = isset( $_POST['review_decision'] ) ? sanitize_key( $_POST['review_decision'] ) : '';

        if ( $post_id && 'approve' === $decision ) {
            OpportunityService::approve_listing( $post_id );
            wp_safe_redirect( add_query_arg( [ 'page' => 'cin-listing-reviews', 'post_type' => Constants::POST_TYPE_OPPORTUNITY, 'message' => 'approved' ], admin_url( 'edit.php' ) ) );
            exit;
        }

        wp_safe_redirect( admin_url( 'edit.php?post_type=' . Constants::POST_TYPE_OPPORTUNITY . '&page=cin-listing-reviews' ) );
        exit;
    }
}
