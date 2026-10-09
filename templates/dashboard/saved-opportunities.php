<?php
/**
 * Template: Saved Opportunities (Investor Portal)
 * Route: /investor/saved-opportunities/
 *
 * Displays bookmarked investment opportunities with full metadata,
 * quick actions to view or remove, and live bookmark state management.
 *
 * @package CubaInvestment\Core
 */

use CubaInvestment\Core\Auth\Permissions;
use CubaInvestment\Core\Common\Constants;
use CubaInvestment\Core\Services\SavedOpportunityService;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Access Control
if ( ! is_user_logged_in() ) {
    wp_safe_redirect( home_url( '/login/?redirect_to=' . urlencode( home_url( '/investor/saved-opportunities/' ) ) ) );
    exit;
}

$user = wp_get_current_user();
if ( ! Permissions::is_investor( $user->ID ) && ! Permissions::is_admin_or_reviewer( $user->ID ) ) {
    wp_safe_redirect( home_url( '/business-owner/dashboard/' ) );
    exit;
}

$paged  = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1;
$limit  = 12;
$offset = ( $paged - 1 ) * $limit;

$saved_items = SavedOpportunityService::get_saved( $user->ID, $limit, $offset );
$total_saved = SavedOpportunityService::count( $user->ID );
$total_pages = ceil( $total_saved / $limit );

$page_title = __( 'Saved Opportunities', 'cuba-investment-core' );
$topbar_cta = [
    'label' => __( 'Explore Deals', 'cuba-investment-core' ),
    'url'   => home_url( '/invest/' ),
    'icon'  => '<svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>',
];

require_once CIN_PLUGIN_DIR . 'templates/dashboard/layout/header.php';
require_once CIN_PLUGIN_DIR . 'templates/dashboard/layout/sidebar.php';
?>

<!-- MAIN CONTENT WRAPPER -->
<div class="main-content-area lg:pl-64 xl:pl-72 flex flex-col flex-1 min-h-screen transition-all duration-300">
    <?php require CIN_PLUGIN_DIR . 'templates/dashboard/layout/topbar.php'; ?>

    <main id="dashboard-main-content" class="flex-1 p-4 sm:p-6 lg:p-8 max-w-7xl w-full mx-auto space-y-6">

        <!-- Flash Message Container (JS dynamic) -->
        <div id="cin-saved-notice" class="hidden p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm font-medium shadow-xs animate-fade-in flex items-center justify-between">
            <span id="cin-saved-notice-text"></span>
            <button type="button" onclick="this.parentElement.classList.add('hidden')" class="text-emerald-500 hover:text-emerald-800 text-sm font-bold ml-3">&times;</button>
        </div>

        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-2">
            <div>
                <a href="<?php echo esc_url( home_url( '/investor/dashboard/' ) ); ?>" class="inline-flex items-center text-xs font-bold text-primary hover:text-accent mb-2 transition-colors">
                    &larr; <?php esc_html_e( 'Back to Investor Overview', 'cuba-investment-core' ); ?>
                </a>
                <h1 class="text-2xl sm:text-3xl font-heading font-extrabold text-slate-900 tracking-tight">
                    <?php esc_html_e( 'Saved Opportunities', 'cuba-investment-core' ); ?>
                </h1>
                <p class="text-sm text-slate-500 mt-1">
                    <?php esc_html_e( 'Keep track of business opportunities you want to explore further.', 'cuba-investment-core' ); ?>
                </p>
            </div>

            <div class="flex items-center gap-3">
                <a href="<?php echo esc_url( home_url( '/invest/' ) ); ?>" class="btn btn-outline-primary btn-sm font-bold shadow-xs flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <span><?php esc_html_e( 'Explore All Opportunities', 'cuba-investment-core' ); ?></span>
                </a>
            </div>
        </div>

        <!-- Summary Strip -->
        <div class="bg-white p-4 sm:p-5 rounded-2xl border border-slate-200/80 shadow-xs flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center font-bold">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-slate-800">
                        <span id="cin-saved-total-count"><?php echo esc_html( $total_saved ); ?></span> <?php esc_html_e( 'Saved Opportunities', 'cuba-investment-core' ); ?>
                    </h3>
                    <p class="text-xs text-slate-500">
                        <?php esc_html_e( 'Published Cuban enterprises bookmarked for evaluation.', 'cuba-investment-core' ); ?>
                    </p>
                </div>
            </div>
        </div>

        <?php if ( empty( $saved_items ) ) : ?>
            <!-- Empty State -->
            <div class="bg-white rounded-3xl border border-slate-200/80 p-8 sm:p-14 text-center shadow-xs">
                <div class="w-16 h-16 rounded-2xl bg-slate-50 text-slate-400 mx-auto flex items-center justify-center mb-4 border border-slate-100">
                    <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z" />
                    </svg>
                </div>
                <h3 class="text-lg font-heading font-bold text-slate-900 mb-2">
                    <?php esc_html_e( 'No Saved Opportunities Yet', 'cuba-investment-core' ); ?>
                </h3>
                <p class="text-sm text-slate-500 max-w-md mx-auto mb-6 leading-relaxed">
                    <?php esc_html_e( 'When you find promising Cuban enterprises on the Explore Opportunities page, bookmark them here for quick access and comparative review.', 'cuba-investment-core' ); ?>
                </p>
                <a href="<?php echo esc_url( home_url( '/invest/' ) ); ?>" class="btn btn-primary btn-md font-bold shadow-xs inline-flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <span><?php esc_html_e( 'Explore Opportunities', 'cuba-investment-core' ); ?></span>
                </a>
            </div>
        <?php else : ?>
            <!-- Grid of Saved Opportunities -->
            <div id="cin-saved-grid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ( $saved_items as $item ) : ?>
                    <div id="saved-card-<?php echo esc_attr( $item['id'] ); ?>" class="bg-white rounded-2xl border border-slate-200/80 shadow-xs hover:shadow-md transition-all flex flex-col justify-between overflow-hidden group">
                        
                        <!-- Card Header & Metadata -->
                        <div class="p-5 sm:p-6 space-y-4">
                            <div class="flex items-start justify-between gap-3">
                                <span class="px-2.5 py-1 rounded-lg text-[11px] font-bold bg-slate-100 text-slate-700 tracking-wide">
                                    <?php echo esc_html( $item['sector'] ); ?>
                                </span>
                                <span class="text-[11px] text-slate-400 font-medium flex items-center gap-1">
                                    <svg class="w-3.5 h-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                    <?php echo esc_html( $item['location'] ); ?>
                                </span>
                            </div>

                            <div>
                                <h3 class="text-base sm:text-lg font-heading font-bold text-slate-900 group-hover:text-primary transition-colors line-clamp-2">
                                    <a href="<?php echo esc_url( $item['url'] ); ?>">
                                        <?php echo esc_html( $item['title'] ); ?>
                                    </a>
                                </h3>
                                <p class="text-xs font-semibold text-slate-500 mt-1 uppercase tracking-wider">
                                    <?php echo esc_html( $item['business_name'] ); ?>
                                </p>
                            </div>

                            <!-- Financial Parameters -->
                            <div class="p-3 rounded-xl bg-slate-50/80 border border-slate-100 flex items-center justify-between">
                                <div>
                                    <span class="block text-[10px] font-bold uppercase tracking-wider text-slate-400">
                                        <?php esc_html_e( 'Capital Sought', 'cuba-investment-core' ); ?>
                                    </span>
                                    <span class="text-sm font-extrabold font-heading text-slate-900">
                                        <?php echo esc_html( number_format( (float) $item['capital_sought'] ) ); ?> <?php echo esc_html( $item['currency'] ); ?>
                                    </span>
                                </div>
                                <div class="text-right">
                                    <span class="block text-[10px] font-bold uppercase tracking-wider text-slate-400">
                                        <?php esc_html_e( 'Last Updated', 'cuba-investment-core' ); ?>
                                    </span>
                                    <span class="text-xs font-semibold text-slate-700">
                                        <?php echo esc_html( $item['last_updated'] ); ?>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Card Action Footer -->
                        <div class="px-5 py-4 sm:px-6 bg-slate-50/50 border-t border-slate-100 flex items-center justify-between gap-3">
                            <button 
                                type="button" 
                                onclick="cinRemoveBookmark(<?php echo esc_attr( $item['id'] ); ?>)" 
                                id="btn-remove-<?php echo esc_attr( $item['id'] ); ?>"
                                class="inline-flex items-center gap-1.5 text-xs font-semibold text-rose-600 hover:text-rose-800 transition-colors cursor-pointer"
                                title="<?php esc_attr_e( 'Remove from Saved', 'cuba-investment-core' ); ?>"
                            >
                                <svg class="w-4 h-4 text-rose-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                                <span><?php esc_html_e( 'Remove', 'cuba-investment-core' ); ?></span>
                            </button>

                            <a 
                                href="<?php echo esc_url( $item['url'] ); ?>" 
                                class="btn btn-outline-primary btn-sm font-bold inline-flex items-center gap-1.5 shadow-2xs"
                            >
                                <span><?php esc_html_e( 'View Opportunity', 'cuba-investment-core' ); ?></span>
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                </svg>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Pagination -->
            <?php if ( $total_pages > 1 ) : ?>
                <div class="flex items-center justify-center gap-2 pt-6">
                    <?php for ( $i = 1; $i <= $total_pages; $i++ ) : ?>
                        <a 
                            href="<?php echo esc_url( add_query_arg( 'paged', $i ) ); ?>" 
                            class="w-9 h-9 rounded-xl flex items-center justify-center text-xs font-bold transition-all <?php echo $paged === $i ? 'bg-primary text-white shadow-xs' : 'bg-white text-slate-700 border border-slate-200 hover:bg-slate-50'; ?>"
                        >
                            <?php echo esc_html( $i ); ?>
                        </a>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>

    </main>

    <?php require CIN_PLUGIN_DIR . 'templates/dashboard/layout/footer.php'; ?>
</div>

<script>
function cinRemoveBookmark(oppId) {
    if (!confirm('<?php echo esc_js( __( 'Are you sure you want to remove this opportunity from your saved list?', 'cuba-investment-core' ) ); ?>')) {
        return;
    }

    const btn = document.getElementById('btn-remove-' + oppId);
    if (btn) btn.disabled = true;

    fetch('<?php echo esc_url( rest_url( 'cin/v1/opportunities/' ) ); ?>' + oppId + '/save', {
        method: 'DELETE',
        headers: {
            'X-WP-Nonce': '<?php echo esc_js( wp_create_nonce( 'wp_rest' ) ); ?>'
        }
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            const card = document.getElementById('saved-card-' + oppId);
            if (card) {
                card.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
                card.style.opacity = '0';
                card.style.transform = 'scale(0.95)';
                setTimeout(() => {
                    card.remove();
                    const remaining = document.querySelectorAll('#cin-saved-grid > div').length;
                    const totalElem = document.getElementById('cin-saved-total-count');
                    if (totalElem) totalElem.textContent = remaining;

                    if (remaining === 0) {
                        window.location.reload();
                    }
                }, 300);
            }

            const notice = document.getElementById('cin-saved-notice');
            const noticeText = document.getElementById('cin-saved-notice-text');
            if (notice && noticeText) {
                noticeText.textContent = res.message || '<?php echo esc_js( __( 'Opportunity removed from saved list.', 'cuba-investment-core' ) ); ?>';
                notice.classList.remove('hidden');
            }
        } else {
            alert(res.message || '<?php echo esc_js( __( 'Could not remove bookmark.', 'cuba-investment-core' ) ); ?>');
            if (btn) btn.disabled = false;
        }
    })
    .catch(err => {
        alert('<?php echo esc_js( __( 'Network error. Please try again.', 'cuba-investment-core' ) ); ?>');
        if (btn) btn.disabled = false;
    });
}
</script>
