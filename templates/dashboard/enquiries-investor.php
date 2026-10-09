<?php
/**
 * Template: My Enquiries (Investor Portal)
 * Route: /investor/enquiries/
 *
 * Displays introduction inquiries sent by the investor to Cuban business owners,
 * tracking statuses (Pending, Accepted, Declined, Withdrawn) with response notes.
 *
 * @package CubaInvestment\Core
 */

use CubaInvestment\Core\Auth\Permissions;
use CubaInvestment\Core\Common\Constants;
use CubaInvestment\Core\Services\InquiryService;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Access Control
if ( ! is_user_logged_in() ) {
    wp_safe_redirect( home_url( '/login/?redirect_to=' . urlencode( home_url( '/investor/enquiries/' ) ) ) );
    exit;
}

$user = wp_get_current_user();
if ( ! Permissions::is_investor( $user->ID ) && ! Permissions::is_admin_or_reviewer( $user->ID ) ) {
    wp_safe_redirect( home_url( '/business-owner/dashboard/' ) );
    exit;
}

$current_filter = isset( $_GET['status'] ) ? sanitize_key( $_GET['status'] ) : 'all';

$all_inquiries = InquiryService::get_for_user( $user->ID, 'investor', 100 );

// Compute status counts
$counts = [
    'all'       => count( $all_inquiries ),
    'pending'   => 0,
    'accepted'  => 0,
    'declined'  => 0,
    'withdrawn' => 0,
];

foreach ( $all_inquiries as $inq ) {
    $st = $inq['status'] ?? 'pending';
    if ( isset( $counts[ $st ] ) ) {
        $counts[ $st ]++;
    }
}

// Filter items
$filtered_inquiries = array_filter( $all_inquiries, function( $item ) use ( $current_filter ) {
    if ( 'all' === $current_filter ) {
        return true;
    }
    return ( $item['status'] ?? '' ) === $current_filter;
} );

$page_title = __( 'My Enquiries', 'cuba-investment-core' );
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

        <!-- Flash Message Container -->
        <div id="cin-inquiry-notice" class="hidden p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm font-medium shadow-xs animate-fade-in flex items-center justify-between">
            <span id="cin-inquiry-notice-text"></span>
            <button type="button" onclick="this.parentElement.classList.add('hidden')" class="text-emerald-500 hover:text-emerald-800 text-sm font-bold ml-3">&times;</button>
        </div>

        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-2">
            <div>
                <a href="<?php echo esc_url( home_url( '/investor/dashboard/' ) ); ?>" class="inline-flex items-center text-xs font-bold text-primary hover:text-accent mb-2 transition-colors">
                    &larr; <?php esc_html_e( 'Back to Investor Overview', 'cuba-investment-core' ); ?>
                </a>
                <h1 class="text-2xl sm:text-3xl font-heading font-extrabold text-slate-900 tracking-tight">
                    <?php esc_html_e( 'My Enquiries', 'cuba-investment-core' ); ?>
                </h1>
                <p class="text-sm text-slate-500 mt-1">
                    <?php esc_html_e( 'Track your introductory enquiries and communications with Cuban business owners.', 'cuba-investment-core' ); ?>
                </p>
            </div>

            <div class="flex items-center gap-3">
                <a href="<?php echo esc_url( home_url( '/invest/' ) ); ?>" class="btn btn-primary btn-sm font-bold shadow-xs flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                    <span><?php esc_html_e( 'Find New Opportunity', 'cuba-investment-core' ); ?></span>
                </a>
            </div>
        </div>

        <!-- Filter Pills Bar -->
        <div class="bg-white p-2 rounded-2xl border border-slate-200/80 shadow-xs flex items-center gap-1.5 overflow-x-auto">
            <a 
                href="<?php echo esc_url( home_url( '/investor/enquiries/' ) ); ?>" 
                class="px-4 py-2 rounded-xl text-xs font-bold transition-all whitespace-nowrap <?php echo 'all' === $current_filter ? 'bg-primary text-white shadow-xs' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100'; ?>"
            >
                <?php esc_html_e( 'All Enquiries', 'cuba-investment-core' ); ?>
                <span class="ml-1.5 px-1.5 py-0.5 rounded-full text-[10px] <?php echo 'all' === $current_filter ? 'bg-white/20 text-white' : 'bg-slate-200 text-slate-600'; ?>">
                    <?php echo esc_html( $counts['all'] ); ?>
                </span>
            </a>

            <a 
                href="<?php echo esc_url( add_query_arg( 'status', 'pending', home_url( '/investor/enquiries/' ) ) ); ?>" 
                class="px-4 py-2 rounded-xl text-xs font-bold transition-all whitespace-nowrap <?php echo 'pending' === $current_filter ? 'bg-amber-600 text-white shadow-xs' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100'; ?>"
            >
                <?php esc_html_e( 'Pending', 'cuba-investment-core' ); ?>
                <span class="ml-1.5 px-1.5 py-0.5 rounded-full text-[10px] <?php echo 'pending' === $current_filter ? 'bg-white/20 text-white' : 'bg-amber-100 text-amber-700'; ?>">
                    <?php echo esc_html( $counts['pending'] ); ?>
                </span>
            </a>

            <a 
                href="<?php echo esc_url( add_query_arg( 'status', 'accepted', home_url( '/investor/enquiries/' ) ) ); ?>" 
                class="px-4 py-2 rounded-xl text-xs font-bold transition-all whitespace-nowrap <?php echo 'accepted' === $current_filter ? 'bg-emerald-700 text-white shadow-xs' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100'; ?>"
            >
                <?php esc_html_e( 'Accepted & Connected', 'cuba-investment-core' ); ?>
                <span class="ml-1.5 px-1.5 py-0.5 rounded-full text-[10px] <?php echo 'accepted' === $current_filter ? 'bg-white/20 text-white' : 'bg-emerald-100 text-emerald-800'; ?>">
                    <?php echo esc_html( $counts['accepted'] ); ?>
                </span>
            </a>

            <a 
                href="<?php echo esc_url( add_query_arg( 'status', 'declined', home_url( '/investor/enquiries/' ) ) ); ?>" 
                class="px-4 py-2 rounded-xl text-xs font-bold transition-all whitespace-nowrap <?php echo 'declined' === $current_filter ? 'bg-rose-600 text-white shadow-xs' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100'; ?>"
            >
                <?php esc_html_e( 'Declined', 'cuba-investment-core' ); ?>
                <span class="ml-1.5 px-1.5 py-0.5 rounded-full text-[10px] <?php echo 'declined' === $current_filter ? 'bg-white/20 text-white' : 'bg-rose-100 text-rose-700'; ?>">
                    <?php echo esc_html( $counts['declined'] ); ?>
                </span>
            </a>
        </div>

        <?php if ( empty( $filtered_inquiries ) ) : ?>
            <!-- Empty State -->
            <div class="bg-white rounded-3xl border border-slate-200/80 p-8 sm:p-14 text-center shadow-xs">
                <div class="w-16 h-16 rounded-2xl bg-slate-50 text-slate-400 mx-auto flex items-center justify-center mb-4 border border-slate-100">
                    <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                </div>
                <h3 class="text-lg font-heading font-bold text-slate-900 mb-2">
                    <?php esc_html_e( 'No Enquiries Found', 'cuba-investment-core' ); ?>
                </h3>
                <p class="text-sm text-slate-500 max-w-md mx-auto mb-6 leading-relaxed">
                    <?php esc_html_e( 'Browse published Cuban businesses and click "Request Introduction" to send your investment credentials and start discussions.', 'cuba-investment-core' ); ?>
                </p>
                <a href="<?php echo esc_url( home_url( '/invest/' ) ); ?>" class="btn btn-primary btn-md font-bold shadow-xs inline-flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <span><?php esc_html_e( 'Explore Opportunities', 'cuba-investment-core' ); ?></span>
                </a>
            </div>
        <?php else : ?>
            <!-- Inquiry List -->
            <div class="space-y-4">
                <?php foreach ( $filtered_inquiries as $inq ) : 
                    $status = $inq['status'] ?? 'pending';
                    $badge_class = 'bg-slate-100 text-slate-600';
                    $badge_label = __( 'Pending Review', 'cuba-investment-core' );

                    if ( 'accepted' === $status ) {
                        $badge_class = 'bg-emerald-100 text-emerald-800';
                        $badge_label = __( 'Accepted / Connected', 'cuba-investment-core' );
                    } elseif ( 'declined' === $status ) {
                        $badge_class = 'bg-rose-100 text-rose-800';
                        $badge_label = __( 'Declined', 'cuba-investment-core' );
                    } elseif ( 'withdrawn' === $status ) {
                        $badge_class = 'bg-slate-100 text-slate-500';
                        $badge_label = __( 'Withdrawn', 'cuba-investment-core' );
                    }
                ?>
                    <div id="inquiry-row-<?php echo esc_attr( $inq['id'] ); ?>" class="bg-white rounded-2xl border border-slate-200/80 shadow-xs hover:border-slate-300 transition-all p-5 sm:p-6 space-y-4">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 pb-3">
                            <div class="flex items-center gap-3">
                                <span class="px-3 py-1 rounded-full text-xs font-bold tracking-wide <?php echo esc_attr( $badge_class ); ?>">
                                    <?php echo esc_html( $badge_label ); ?>
                                </span>
                                <span class="text-xs text-slate-400 font-medium">
                                    <?php echo esc_html( date_i18n( 'M j, Y — g:i A', strtotime( $inq['created_at'] ) ) ); ?>
                                </span>
                            </div>

                            <div class="flex items-center gap-2">
                                <span class="text-xs font-semibold text-slate-500">
                                    <?php esc_html_e( 'Recipient:', 'cuba-investment-core' ); ?>
                                </span>
                                <span class="text-xs font-bold text-slate-800">
                                    <?php echo esc_html( $inq['partner_name'] ); ?>
                                </span>
                            </div>
                        </div>

                        <div>
                            <div class="flex items-baseline justify-between gap-4">
                                <h3 class="text-base sm:text-lg font-heading font-bold text-slate-900">
                                    <?php echo esc_html( $inq['subject'] ); ?>
                                </h3>
                                <?php if ( ! empty( $inq['capital_range'] ) ) : ?>
                                    <span class="text-xs font-bold text-primary bg-primary/5 px-2.5 py-1 rounded-lg shrink-0">
                                        <?php esc_html_e( 'Target Capital:', 'cuba-investment-core' ); ?> <?php echo esc_html( $inq['capital_range'] ); ?>
                                    </span>
                                <?php endif; ?>
                            </div>

                            <p class="text-xs text-primary font-semibold mt-1">
                                <a href="<?php echo esc_url( $inq['opportunity_url'] ); ?>" class="hover:underline inline-flex items-center gap-1">
                                    <span><?php esc_html_e( 'Listing:', 'cuba-investment-core' ); ?> <?php echo esc_html( $inq['opportunity_title'] ); ?></span>
                                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                    </svg>
                                </a>
                            </p>
                        </div>

                        <!-- Message Content -->
                        <div class="p-4 rounded-xl bg-slate-50/70 border border-slate-100 text-xs text-slate-700 leading-relaxed font-normal whitespace-pre-wrap">
                            <?php echo esc_html( $inq['message'] ); ?>
                        </div>

                        <!-- Admin / Business Response Note if present -->
                        <?php if ( ! empty( $inq['admin_notes'] ) ) : ?>
                            <div class="p-3.5 rounded-xl bg-blue-50/70 border border-blue-100 text-xs text-blue-900 flex items-start gap-2.5">
                                <svg class="w-4 h-4 text-blue-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <div>
                                    <span class="font-bold block"><?php esc_html_e( 'Founder Note / Response:', 'cuba-investment-core' ); ?></span>
                                    <span><?php echo esc_html( $inq['admin_notes'] ); ?></span>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Row Actions Footer -->
                        <div class="pt-3 border-t border-slate-100 flex items-center justify-between">
                            <div>
                                <?php if ( 'pending' === $status ) : ?>
                                    <button 
                                        type="button" 
                                        onclick="cinWithdrawInquiry(<?php echo esc_attr( $inq['id'] ); ?>)" 
                                        id="btn-withdraw-<?php echo esc_attr( $inq['id'] ); ?>"
                                        class="text-xs font-semibold text-rose-600 hover:text-rose-800 transition-colors cursor-pointer inline-flex items-center gap-1"
                                    >
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                        <span><?php esc_html_e( 'Withdraw Enquiry', 'cuba-investment-core' ); ?></span>
                                    </button>
                                <?php else : ?>
                                    <span class="text-[11px] text-slate-400">
                                        <?php printf( esc_html__( 'Last updated: %s', 'cuba-investment-core' ), esc_html( date_i18n( 'M j, Y', strtotime( $inq['updated_at'] ) ) ) ); ?>
                                    </span>
                                <?php endif; ?>
                            </div>

                            <div class="flex items-center gap-2">
                                <?php if ( 'accepted' === $status ) : ?>
                                    <a 
                                        href="<?php echo esc_url( home_url( '/investor/messages/' . ( ! empty( $inq['conversation_id'] ) ? '?convo=' . $inq['conversation_id'] : '' ) ) ); ?>" 
                                        class="btn btn-primary btn-sm font-bold shadow-xs inline-flex items-center gap-1.5"
                                    >
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                        </svg>
                                        <span><?php esc_html_e( 'Open Direct Messages', 'cuba-investment-core' ); ?></span>
                                    </a>
                                <?php else : ?>
                                    <a 
                                        href="<?php echo esc_url( $inq['opportunity_url'] ); ?>" 
                                        class="btn btn-outline-primary btn-sm font-bold shadow-2xs inline-flex items-center gap-1"
                                    >
                                        <span><?php esc_html_e( 'View Listing', 'cuba-investment-core' ); ?></span>
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                        </svg>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </main>

    <?php require CIN_PLUGIN_DIR . 'templates/dashboard/layout/footer.php'; ?>
</div>

<script>
function cinWithdrawInquiry(id) {
    if (!confirm('<?php echo esc_js( __( 'Are you sure you want to withdraw this introduction inquiry?', 'cuba-investment-core' ) ); ?>')) {
        return;
    }

    const btn = document.getElementById('btn-withdraw-' + id);
    if (btn) btn.disabled = true;

    fetch('<?php echo esc_url( rest_url( 'cin/v1/inquiries/' ) ); ?>' + id + '/withdraw', {
        method: 'POST',
        headers: {
            'X-WP-Nonce': '<?php echo esc_js( wp_create_nonce( 'wp_rest' ) ); ?>'
        }
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            window.location.reload();
        } else {
            alert(res.message || '<?php echo esc_js( __( 'Failed to withdraw enquiry.', 'cuba-investment-core' ) ); ?>');
            if (btn) btn.disabled = false;
        }
    })
    .catch(() => {
        alert('<?php echo esc_js( __( 'Network error. Please try again.', 'cuba-investment-core' ) ); ?>');
        if (btn) btn.disabled = false;
    });
}
</script>
