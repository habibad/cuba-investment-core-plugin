<?php
/**
 * Cuba Investment Core - Admin Dashboard & Listing Review Interface
 *
 * @package CubaInvestment\Core
 */

namespace CubaInvestment\Core\Admin;

use CubaInvestment\Core\Common\Constants;
use CubaInvestment\Core\Services\OpportunityService;
use CubaInvestment\Core\Security\NonceManager;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AdminManager {

    public static function init() {
        add_action( 'admin_menu', [ __CLASS__, 'register_admin_menus' ] );
        add_action( 'admin_post_cin_admin_review_action', [ __CLASS__, 'handle_review_action' ] );
    }

    public static function register_admin_menus() {
        // Submenu under Opportunities for Listing Reviews
        add_submenu_page(
            'edit.php?post_type=' . Constants::POST_TYPE_OPPORTUNITY,
            __( 'Listing Review Queue', 'cuba-investment-core' ),
            __( 'Review Queue', 'cuba-investment-core' ),
            Constants::CAP_REVIEW_OPPORTUNITIES,
            'cin-listing-reviews',
            [ __CLASS__, 'render_review_queue_page' ]
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

    public static function render_audit_logs_page() {
        if ( ! current_user_can( Constants::CAP_VIEW_AUDIT_LOGS ) ) {
            wp_die( esc_html__( 'Unauthorized.', 'cuba-investment-core' ) );
        }

        global $wpdb;
        $table = Constants::get_table_name( Constants::TABLE_AUDIT_LOGS );
        $logs  = $wpdb->get_results( "SELECT * FROM {$table} ORDER BY created_at DESC LIMIT 100" );
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Security & Governance Audit Trail', 'cuba-investment-core' ); ?></h1>
            <p class="description"><?php esc_html_e( 'Chronological immutable log of critical platform actions, user registration, listing approvals and security events.', 'cuba-investment-core' ); ?></p>
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
