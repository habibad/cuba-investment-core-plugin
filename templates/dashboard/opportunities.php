<?php
/**
 * Template: My Opportunities Listing
 * Route: /business-owner/opportunities/ (and /dashboard/business/opportunities/)
 *
 * Implements listing management, status filtering (All, Draft, Under Review, Published),
 * keyword search, dynamic counts, action menus (Edit, Submit, Delete), and empty state.
 *
 * @package CubaInvestment\Core
 */

use CubaInvestment\Core\Auth\FormHandler;
use CubaInvestment\Core\Auth\Permissions;
use CubaInvestment\Core\Common\Constants;
use CubaInvestment\Core\Security\NonceManager;
use CubaInvestment\Core\Services\OpportunityService;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$user = wp_get_current_user();

// Flash messages
$flash_error   = FormHandler::get_profile_flash_error( $user->ID );
$flash_success = FormHandler::get_profile_flash_success( $user->ID );

if ( empty( $flash_success ) && isset( $_GET['submitted'] ) ) {
    $flash_success = __( 'Opportunity Submitted Successfully. Your opportunity has been submitted for review. You can monitor its status from My Opportunities.', 'cuba-investment-core' );
} elseif ( empty( $flash_success ) && isset( $_GET['deleted'] ) ) {
    $flash_success = __( 'Draft opportunity removed successfully.', 'cuba-investment-core' );
} elseif ( empty( $flash_success ) && isset( $_GET['saved'] ) ) {
    $flash_success = __( 'Draft opportunity saved.', 'cuba-investment-core' );
}

// Current filter and search query
$current_status = isset( $_GET['status'] ) ? sanitize_key( $_GET['status'] ) : 'all';
$search_query   = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
$paged          = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1;

// Fetch opportunities from OpportunityService
$results = OpportunityService::get_owner_opportunities( $user->ID, [
    'status' => $current_status,
    'search' => $search_query,
    'page'   => $paged,
    'limit'  => 12,
] );

$opportunities = $results['items'];
$total_items   = $results['total'];
$total_pages   = $results['total_pages'];
$counts        = $results['counts'];

$page_title = __( 'My Opportunities', 'cuba-investment-core' );
$topbar_cta = [
    'label' => __( 'Create Opportunity', 'cuba-investment-core' ),
    'url'   => home_url( '/business-owner/opportunities/create/' ),
    'icon'  => '<svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>',
];

require_once CIN_PLUGIN_DIR . 'templates/dashboard/layout/header.php';
require_once CIN_PLUGIN_DIR . 'templates/dashboard/layout/sidebar.php';
?>

<!-- MAIN CONTENT WRAPPER -->
<div class="main-content-area lg:pl-64 xl:pl-72 flex flex-col flex-1 min-h-screen transition-all duration-300">
    <?php require CIN_PLUGIN_DIR . 'templates/dashboard/layout/topbar.php'; ?>

    <main id="dashboard-main-content" class="flex-1 p-4 sm:p-6 lg:p-8 max-w-7xl w-full mx-auto space-y-6">

        <!-- Flash Notice: Success -->
        <?php if ( ! empty( $flash_success ) ) : ?>
            <div class="p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm flex items-start gap-3 shadow-xs animate-fade-in" role="alert">
                <svg class="w-5 h-5 text-emerald-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <div class="flex-1 font-medium">
                    <?php echo esc_html( $flash_success ); ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Flash Notice: Error -->
        <?php if ( ! empty( $flash_error ) ) : ?>
            <div class="p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-sm flex items-start gap-3 shadow-xs animate-fade-in" role="alert">
                <svg class="w-5 h-5 text-rose-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <div class="flex-1 font-medium">
                    <?php echo esc_html( $flash_error ); ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-2">
            <div>
                <a href="<?php echo esc_url( home_url( '/business-owner/dashboard/' ) ); ?>" class="inline-flex items-center text-xs font-bold text-primary hover:text-accent mb-2 transition-colors">
                    &larr; <?php esc_html_e( 'Back to Business Overview', 'cuba-investment-core' ); ?>
                </a>
                <h1 class="text-2xl sm:text-3xl font-heading font-extrabold text-slate-900 tracking-tight">
                    <?php esc_html_e( 'My Opportunities', 'cuba-investment-core' ); ?>
                </h1>
                <p class="text-sm text-slate-500 mt-1">
                    <?php esc_html_e( 'Create, manage, and track the business opportunities you present to potential investors.', 'cuba-investment-core' ); ?>
                </p>
            </div>

            <div class="flex items-center gap-3">
                <a href="<?php echo esc_url( home_url( '/business-owner/opportunities/create/' ) ); ?>" class="btn btn-primary btn-sm font-bold shadow-xs flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                    <span><?php esc_html_e( 'Create Opportunity', 'cuba-investment-core' ); ?></span>
                </a>
            </div>
        </div>

        <!-- Filters & Search Bar -->
        <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs flex flex-col md:flex-row items-stretch md:items-center justify-between gap-4">
            <!-- Status Tabs -->
            <div class="flex items-center gap-1 overflow-x-auto pb-1 md:pb-0 scrollbar-none">
                <?php
                $status_tabs = [
                    'all'            => [ 'label' => __( 'All Opportunities', 'cuba-investment-core' ), 'count' => $counts['all'] ],
                    'draft'          => [ 'label' => __( 'Draft', 'cuba-investment-core' ), 'count' => $counts['draft'] ],
                    'pending_review' => [ 'label' => __( 'Under Review', 'cuba-investment-core' ), 'count' => $counts['pending_review'] ],
                    'publish'        => [ 'label' => __( 'Published', 'cuba-investment-core' ), 'count' => $counts['publish'] ],
                ];

                if ( ! empty( $counts['paused'] ) ) {
                    $status_tabs['paused'] = [ 'label' => __( 'Paused', 'cuba-investment-core' ), 'count' => $counts['paused'] ];
                }
                if ( ! empty( $counts['closed'] ) ) {
                    $status_tabs['closed'] = [ 'label' => __( 'Closed', 'cuba-investment-core' ), 'count' => $counts['closed'] ];
                }
                if ( ! empty( $counts['archived'] ) ) {
                    $status_tabs['archived'] = [ 'label' => __( 'Archived', 'cuba-investment-core' ), 'count' => $counts['archived'] ];
                }

                foreach ( $status_tabs as $tab_key => $tab_data ) :
                    $is_current = ( $current_status === $tab_key );
                    $tab_url = add_query_arg( [
                        'status' => $tab_key,
                        's'      => $search_query ? rawurlencode( $search_query ) : false,
                    ], home_url( '/business-owner/opportunities/' ) );
                ?>
                    <a href="<?php echo esc_url( $tab_url ); ?>" class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition-all shrink-0 flex items-center gap-2 <?php echo $is_current ? 'bg-primary text-white shadow-xs' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100'; ?>">
                        <span><?php echo esc_html( $tab_data['label'] ); ?></span>
                        <span class="text-[10px] px-1.5 py-0.2 rounded-full font-bold <?php echo $is_current ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-500'; ?>">
                            <?php echo esc_html( $tab_data['count'] ); ?>
                        </span>
                    </a>
                <?php endforeach; ?>
            </div>

            <!-- Search Form -->
            <form method="get" action="<?php echo esc_url( home_url( '/business-owner/opportunities/' ) ); ?>" class="flex items-center gap-2">
                <input type="hidden" name="status" value="<?php echo esc_attr( $current_status ); ?>">
                <div class="relative w-full sm:w-64">
                    <input type="text" name="s" value="<?php echo esc_attr( $search_query ); ?>" placeholder="<?php esc_attr_e( 'Search opportunities...', 'cuba-investment-core' ); ?>" class="w-full text-xs pl-8 pr-3 py-2 rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all">
                    <svg class="w-4 h-4 text-slate-400 absolute left-2.5 top-2.5 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                <button type="submit" class="btn btn-outline btn-sm font-semibold border-slate-300 text-slate-700 hover:border-primary hover:text-primary transition-colors">
                    <?php esc_html_e( 'Search', 'cuba-investment-core' ); ?>
                </button>
                <?php if ( ! empty( $search_query ) ) : ?>
                    <a href="<?php echo esc_url( add_query_arg( [ 'status' => $current_status ], home_url( '/business-owner/opportunities/' ) ) ); ?>" class="text-xs text-rose-600 hover:underline">
                        <?php esc_html_e( 'Clear', 'cuba-investment-core' ); ?>
                    </a>
                <?php endif; ?>
            </form>
        </div>

        <!-- OPPORTUNITY LISTINGS / TABLE -->
        <?php if ( empty( $opportunities ) ) : ?>
            <!-- EMPTY STATE -->
            <div class="card bg-white p-10 sm:p-14 rounded-2xl border border-slate-200/80 shadow-xs text-center max-w-2xl mx-auto my-8">
                <div class="w-16 h-16 rounded-2xl bg-primary-50 text-primary mx-auto flex items-center justify-center mb-4 border border-primary-100 shadow-xs">
                    <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                </div>
                <h3 class="text-xl font-heading font-extrabold text-slate-900 tracking-tight mb-2">
                    <?php esc_html_e( "You Haven't Created an Opportunity Yet", 'cuba-investment-core' ); ?>
                </h3>
                <p class="text-sm text-slate-500 max-w-md mx-auto mb-6 leading-relaxed">
                    <?php esc_html_e( 'Create your first business opportunity to present your company and capital requirements.', 'cuba-investment-core' ); ?>
                </p>
                <a href="<?php echo esc_url( home_url( '/business-owner/opportunities/create/' ) ); ?>" class="btn btn-primary font-bold shadow-xs px-6 py-2.5 inline-flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                    <span><?php esc_html_e( 'Create Opportunity', 'cuba-investment-core' ); ?></span>
                </a>
            </div>
        <?php else : ?>
            <!-- LISTINGS TABLE -->
            <div class="card bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50/75 border-b border-slate-200/80 text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                                <th class="py-3.5 px-4 sm:px-6"><?php esc_html_e( 'Opportunity Title & Company', 'cuba-investment-core' ); ?></th>
                                <th class="py-3.5 px-4"><?php esc_html_e( 'Sector & Location', 'cuba-investment-core' ); ?></th>
                                <th class="py-3.5 px-4"><?php esc_html_e( 'Capital Sought', 'cuba-investment-core' ); ?></th>
                                <th class="py-3.5 px-4"><?php esc_html_e( 'Status', 'cuba-investment-core' ); ?></th>
                                <th class="py-3.5 px-4"><?php esc_html_e( 'Last Updated', 'cuba-investment-core' ); ?></th>
                                <th class="py-3.5 px-4 sm:px-6 text-right"><?php esc_html_e( 'Actions', 'cuba-investment-core' ); ?></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-xs text-slate-700">
                            <?php foreach ( $opportunities as $opp ) :
                                $is_draft     = in_array( $opp['status'], [ 'draft', Constants::STATUS_DRAFT ], true );
                                $is_review    = in_array( $opp['status'], [ 'pending', 'pending_review', Constants::STATUS_PENDING_REVIEW ], true );
                                $is_published = in_array( $opp['status'], [ 'publish', Constants::STATUS_APPROVED ], true );
                                $currency_symbol = '$';
                                if ( 'EUR' === $opp['currency'] ) $currency_symbol = '€';
                                elseif ( 'GBP' === $opp['currency'] ) $currency_symbol = '£';
                            ?>
                                <tr class="hover:bg-slate-50/60 transition-colors">
                                    <!-- Title & Company -->
                                    <td class="py-4 px-4 sm:px-6">
                                        <div class="font-heading font-bold text-slate-900 text-sm mb-0.5">
                                            <?php echo esc_html( $opp['title'] ); ?>
                                        </div>
                                        <div class="text-xs text-slate-500 font-medium">
                                            <?php echo esc_html( $opp['company_name'] ); ?>
                                        </div>
                                    </td>

                                    <!-- Sector & Location -->
                                    <td class="py-4 px-4">
                                        <div class="font-semibold text-slate-800">
                                            <?php echo esc_html( $opp['industry'] ?: __( 'General Sector', 'cuba-investment-core' ) ); ?>
                                        </div>
                                        <div class="text-[11px] text-slate-400">
                                            <?php echo esc_html( $opp['location'] ?: 'Cuba' ); ?>
                                        </div>
                                    </td>

                                    <!-- Capital Sought -->
                                    <td class="py-4 px-4">
                                        <div class="font-heading font-extrabold text-slate-900 text-sm">
                                            <?php echo esc_html( $currency_symbol . number_format( $opp['capital_sought'], 2 ) ); ?>
                                        </div>
                                        <div class="text-[10px] uppercase font-bold text-slate-400">
                                            <?php echo esc_html( $opp['currency'] ); ?>
                                        </div>
                                    </td>

                                    <!-- Status -->
                                    <td class="py-4 px-4">
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold border <?php echo esc_attr( $opp['badge_class'] ); ?>">
                                            <span class="w-1.5 h-1.5 rounded-full <?php echo $is_published ? 'bg-emerald-500' : ( $is_review ? 'bg-blue-500' : 'bg-slate-400' ); ?>"></span>
                                            <?php echo esc_html( $opp['status_label'] ); ?>
                                        </span>
                                    </td>

                                    <!-- Last Updated -->
                                    <td class="py-4 px-4 text-slate-500 text-[11px]">
                                        <?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $opp['updated_at'] ?: $opp['created_at'] ) ) ); ?>
                                    </td>

                                    <!-- Actions -->
                                    <td class="py-4 px-4 sm:px-6 text-right space-x-2">
                                        <?php if ( $is_draft ) : ?>
                                            <!-- Continue Draft -->
                                            <a href="<?php echo esc_url( add_query_arg( 'id', $opp['id'], home_url( '/business-owner/opportunities/create/' ) ) ); ?>" class="btn btn-primary btn-xs font-bold shadow-xs">
                                                <?php esc_html_e( 'Continue Draft', 'cuba-investment-core' ); ?>
                                            </a>

                                            <!-- Delete Draft (triggers confirm modal) -->
                                            <button type="button" onclick="cinOpenDeleteModal(<?php echo esc_attr( $opp['id'] ); ?>, '<?php echo esc_attr( addslashes( $opp['title'] ) ); ?>')" class="text-xs font-semibold text-rose-600 hover:text-rose-800 transition-colors p-1">
                                                <?php esc_html_e( 'Remove', 'cuba-investment-core' ); ?>
                                            </button>

                                        <?php elseif ( $is_review ) : ?>
                                            <!-- Under Review: Read-Only View -->
                                            <a href="<?php echo esc_url( add_query_arg( [ 'id' => $opp['id'], 'preview' => '1' ], home_url( '/business-owner/opportunities/create/' ) ) ); ?>" class="btn btn-outline btn-xs font-semibold border-slate-300 text-slate-700 hover:border-primary hover:text-primary">
                                                <?php esc_html_e( 'View Details', 'cuba-investment-core' ); ?>
                                            </a>
                                            <span class="text-[10px] text-slate-400 italic"><?php esc_html_e( '(Read-Only)', 'cuba-investment-core' ); ?></span>

                                        <?php elseif ( $is_published ) : ?>
                                            <!-- Published -->
                                            <a href="<?php echo esc_url( add_query_arg( [ 'id' => $opp['id'], 'preview' => '1' ], home_url( '/business-owner/opportunities/create/' ) ) ); ?>" class="btn btn-outline btn-xs font-semibold border-slate-300 text-slate-700 hover:border-primary hover:text-primary">
                                                <?php esc_html_e( 'View Listing', 'cuba-investment-core' ); ?>
                                            </a>
                                        <?php else : ?>
                                            <a href="<?php echo esc_url( add_query_arg( [ 'id' => $opp['id'], 'preview' => '1' ], home_url( '/business-owner/opportunities/create/' ) ) ); ?>" class="text-xs font-semibold text-primary hover:underline">
                                                <?php esc_html_e( 'View', 'cuba-investment-core' ); ?>
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination if multiple pages -->
                <?php if ( $total_pages > 1 ) : ?>
                    <div class="p-4 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
                        <div>
                            <?php echo esc_html( sprintf( __( 'Showing page %d of %d (%d listings total)', 'cuba-investment-core' ), $paged, $total_pages, $total_items ) ); ?>
                        </div>
                        <div class="flex items-center gap-2">
                            <?php if ( $paged > 1 ) : ?>
                                <a href="<?php echo esc_url( add_query_arg( [ 'status' => $current_status, 's' => $search_query, 'paged' => $paged - 1 ], home_url( '/business-owner/opportunities/' ) ) ); ?>" class="px-3 py-1 rounded-lg border border-slate-200 hover:bg-slate-50 font-bold">
                                    &larr; <?php esc_html_e( 'Previous', 'cuba-investment-core' ); ?>
                                </a>
                            <?php endif; ?>
                            <?php if ( $paged < $total_pages ) : ?>
                                <a href="<?php echo esc_url( add_query_arg( [ 'status' => $current_status, 's' => $search_query, 'paged' => $paged + 1 ], home_url( '/business-owner/opportunities/' ) ) ); ?>" class="px-3 py-1 rounded-lg border border-slate-200 hover:bg-slate-50 font-bold">
                                    <?php esc_html_e( 'Next', 'cuba-investment-core' ); ?> &rarr;
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

    </main>
</div>

<!-- DELETE CONFIRMATION MODAL -->
<div id="delete-draft-modal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-xs hidden" role="dialog" aria-modal="true">
    <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-slate-200 space-y-4 animate-scale-in">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center border border-rose-100 shrink-0">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
            </div>
            <div>
                <h3 class="text-base font-heading font-extrabold text-slate-900">
                    <?php esc_html_e( 'Remove Draft Opportunity?', 'cuba-investment-core' ); ?>
                </h3>
                <p class="text-xs text-slate-500">
                    <?php esc_html_e( 'This draft will be removed from your list.', 'cuba-investment-core' ); ?>
                </p>
            </div>
        </div>

        <p class="text-xs text-slate-600 leading-relaxed">
            <?php esc_html_e( 'Are you sure you want to delete:', 'cuba-investment-core' ); ?>
            <span id="delete-modal-title" class="font-bold text-slate-900 block mt-1"></span>
        </p>

        <form method="post" action="<?php echo esc_url( home_url( '/business-owner/opportunities/' ) ); ?>" class="pt-2 flex items-center justify-end gap-3">
            <?php echo NonceManager::field( 'cin_delete_opportunity_draft', '_cin_nonce' ); ?>
            <input type="hidden" name="cin_action" value="cin_delete_opportunity_draft">
            <input type="hidden" name="opportunity_id" id="delete-modal-opp-id" value="">

            <button type="button" onclick="cinCloseDeleteModal()" class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-100 transition-colors">
                <?php esc_html_e( 'Cancel', 'cuba-investment-core' ); ?>
            </button>
            <button type="submit" class="px-4 py-2 rounded-xl text-xs font-bold bg-rose-600 hover:bg-rose-700 text-white shadow-xs transition-colors">
                <?php esc_html_e( 'Delete Draft', 'cuba-investment-core' ); ?>
            </button>
        </form>
    </div>
</div>

<script>
function cinOpenDeleteModal(id, title) {
    document.getElementById('delete-modal-opp-id').value = id;
    document.getElementById('delete-modal-title').textContent = '"' + title + '"';
    document.getElementById('delete-draft-modal').classList.remove('hidden');
}

function cinCloseDeleteModal() {
    document.getElementById('delete-draft-modal').classList.add('hidden');
}
</script>

<?php require_once CIN_PLUGIN_DIR . 'templates/dashboard/layout/footer.php'; ?>
