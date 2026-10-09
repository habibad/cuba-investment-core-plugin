<?php
/**
 * Template: Investor Enquiries (Business Owner Portal)
 * Route: /business-owner/enquiries/
 *
 * Displays introduction inquiries received on the business owner's opportunities.
 * Allows reviewing investor credentials and accepting or declining introductions.
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
    wp_safe_redirect( home_url( '/login/?redirect_to=' . urlencode( home_url( '/business-owner/enquiries/' ) ) ) );
    exit;
}

$user = wp_get_current_user();
if ( ! Permissions::is_business_owner( $user->ID ) && ! Permissions::is_admin_or_reviewer( $user->ID ) ) {
    wp_safe_redirect( home_url( '/investor/dashboard/' ) );
    exit;
}

$current_filter = isset( $_GET['status'] ) ? sanitize_key( $_GET['status'] ) : 'all';

$all_inquiries = InquiryService::get_for_user( $user->ID, 'business_owner', 100 );

// Compute status counts
$counts = [
    'all'       => count( $all_inquiries ),
    'pending'   => 0,
    'accepted'  => 0,
    'declined'  => 0,
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

$page_title = __( 'Investor Enquiries', 'cuba-investment-core' );
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

        <!-- Flash Notice Container -->
        <div id="cin-bo-inquiry-notice" class="hidden p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm font-medium shadow-xs animate-fade-in flex items-center justify-between">
            <span id="cin-bo-inquiry-notice-text"></span>
            <button type="button" onclick="this.parentElement.classList.add('hidden')" class="text-emerald-500 hover:text-emerald-800 text-sm font-bold ml-3">&times;</button>
        </div>

        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-2">
            <div>
                <a href="<?php echo esc_url( home_url( '/business-owner/dashboard/' ) ); ?>" class="inline-flex items-center text-xs font-bold text-primary hover:text-accent mb-2 transition-colors">
                    &larr; <?php esc_html_e( 'Back to Business Overview', 'cuba-investment-core' ); ?>
                </a>
                <h1 class="text-2xl sm:text-3xl font-heading font-extrabold text-slate-900 tracking-tight">
                    <?php esc_html_e( 'Investor Enquiries', 'cuba-investment-core' ); ?>
                </h1>
                <p class="text-sm text-slate-500 mt-1">
                    <?php esc_html_e( 'Review and respond to introduction requests from verified investors.', 'cuba-investment-core' ); ?>
                </p>
            </div>

            <div class="flex items-center gap-3">
                <a href="<?php echo esc_url( home_url( '/business-owner/opportunities/' ) ); ?>" class="btn btn-outline-primary btn-sm font-bold shadow-xs flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <span><?php esc_html_e( 'My Opportunities', 'cuba-investment-core' ); ?></span>
                </a>
            </div>
        </div>

        <!-- Filter Pills Bar -->
        <div class="bg-white p-2 rounded-2xl border border-slate-200/80 shadow-xs flex items-center gap-1.5 overflow-x-auto">
            <a 
                href="<?php echo esc_url( home_url( '/business-owner/enquiries/' ) ); ?>" 
                class="px-4 py-2 rounded-xl text-xs font-bold transition-all whitespace-nowrap <?php echo 'all' === $current_filter ? 'bg-primary text-white shadow-xs' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100'; ?>"
            >
                <?php esc_html_e( 'All Inquiries', 'cuba-investment-core' ); ?>
                <span class="ml-1.5 px-1.5 py-0.5 rounded-full text-[10px] <?php echo 'all' === $current_filter ? 'bg-white/20 text-white' : 'bg-slate-200 text-slate-600'; ?>">
                    <?php echo esc_html( $counts['all'] ); ?>
                </span>
            </a>

            <a 
                href="<?php echo esc_url( add_query_arg( 'status', 'pending', home_url( '/business-owner/enquiries/' ) ) ); ?>" 
                class="px-4 py-2 rounded-xl text-xs font-bold transition-all whitespace-nowrap <?php echo 'pending' === $current_filter ? 'bg-amber-600 text-white shadow-xs' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100'; ?>"
            >
                <?php esc_html_e( 'Pending Review', 'cuba-investment-core' ); ?>
                <span class="ml-1.5 px-1.5 py-0.5 rounded-full text-[10px] <?php echo 'pending' === $current_filter ? 'bg-white/20 text-white' : 'bg-amber-100 text-amber-700'; ?>">
                    <?php echo esc_html( $counts['pending'] ); ?>
                </span>
            </a>

            <a 
                href="<?php echo esc_url( add_query_arg( 'status', 'accepted', home_url( '/business-owner/enquiries/' ) ) ); ?>" 
                class="px-4 py-2 rounded-xl text-xs font-bold transition-all whitespace-nowrap <?php echo 'accepted' === $current_filter ? 'bg-emerald-700 text-white shadow-xs' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100'; ?>"
            >
                <?php esc_html_e( 'Accepted / Connected', 'cuba-investment-core' ); ?>
                <span class="ml-1.5 px-1.5 py-0.5 rounded-full text-[10px] <?php echo 'accepted' === $current_filter ? 'bg-white/20 text-white' : 'bg-emerald-100 text-emerald-800'; ?>">
                    <?php echo esc_html( $counts['accepted'] ); ?>
                </span>
            </a>

            <a 
                href="<?php echo esc_url( add_query_arg( 'status', 'declined', home_url( '/business-owner/enquiries/' ) ) ); ?>" 
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
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                    </svg>
                </div>
                <h3 class="text-lg font-heading font-bold text-slate-900 mb-2">
                    <?php esc_html_e( 'No Enquiries Received Yet', 'cuba-investment-core' ); ?>
                </h3>
                <p class="text-sm text-slate-500 max-w-md mx-auto mb-6 leading-relaxed">
                    <?php esc_html_e( 'When registered investors review your published business listings and request an introduction, their inquiries will appear here for your direct response.', 'cuba-investment-core' ); ?>
                </p>
                <a href="<?php echo esc_url( home_url( '/business-owner/opportunities/create/' ) ); ?>" class="btn btn-primary btn-md font-bold shadow-xs inline-flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                    <span><?php esc_html_e( 'Submit New Opportunity', 'cuba-investment-core' ); ?></span>
                </a>
            </div>
        <?php else : ?>
            <!-- Inquiries List -->
            <div class="space-y-5">
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
                    }
                ?>
                    <div id="bo-inquiry-<?php echo esc_attr( $inq['id'] ); ?>" class="bg-white rounded-2xl border border-slate-200/80 shadow-xs hover:border-slate-300 transition-all p-5 sm:p-6 space-y-4">
                        
                        <!-- Header with Investor Profile Info -->
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 pb-3">
                            <div class="flex items-center gap-3">
                                <?php if ( ! empty( $inq['partner_avatar'] ) ) : ?>
                                    <img src="<?php echo esc_url( $inq['partner_avatar'] ); ?>" alt="<?php echo esc_attr( $inq['partner_name'] ); ?>" class="w-10 h-10 rounded-full object-cover border border-slate-200">
                                <?php else : ?>
                                    <div class="w-10 h-10 rounded-full bg-primary/10 text-primary font-extrabold flex items-center justify-center text-xs">
                                        <?php echo esc_html( strtoupper( substr( $inq['partner_name'], 0, 2 ) ) ); ?>
                                    </div>
                                <?php endif; ?>
                                <div>
                                    <div class="flex items-center gap-2">
                                        <h4 class="text-sm font-bold text-slate-900"><?php echo esc_html( $inq['partner_name'] ); ?></h4>
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-600">
                                            <?php echo esc_html( $inq['partner_type'] ?? 'Investor' ); ?>
                                        </span>
                                    </div>
                                    <p class="text-xs text-slate-400">
                                        <?php echo esc_html( $inq['partner_country'] ?? 'International' ); ?> &bull; <?php echo esc_html( date_i18n( 'M j, Y g:i A', strtotime( $inq['created_at'] ) ) ); ?>
                                    </p>
                                </div>
                            </div>

                            <div class="flex items-center gap-2">
                                <span class="px-3 py-1 rounded-full text-xs font-bold tracking-wide <?php echo esc_attr( $badge_class ); ?>">
                                    <?php echo esc_html( $badge_label ); ?>
                                </span>
                            </div>
                        </div>

                        <!-- Inquiry Subject & Context -->
                        <div>
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                                <h3 class="text-base sm:text-lg font-heading font-bold text-slate-900">
                                    <?php echo esc_html( $inq['subject'] ); ?>
                                </h3>
                                <?php if ( ! empty( $inq['capital_range'] ) ) : ?>
                                    <span class="text-xs font-bold text-primary bg-primary/5 px-2.5 py-1 rounded-lg shrink-0">
                                        <?php esc_html_e( 'Stated Capital Range:', 'cuba-investment-core' ); ?> <?php echo esc_html( $inq['capital_range'] ); ?>
                                    </span>
                                <?php endif; ?>
                            </div>

                            <p class="text-xs text-slate-500 font-medium mt-1">
                                <?php esc_html_e( 'Regarding Listing:', 'cuba-investment-core' ); ?> 
                                <a href="<?php echo esc_url( $inq['opportunity_url'] ); ?>" class="text-primary hover:underline font-bold">
                                    <?php echo esc_html( $inq['opportunity_title'] ); ?>
                                </a>
                            </p>
                        </div>

                        <!-- Full Message Body -->
                        <div class="p-4 rounded-xl bg-slate-50/80 border border-slate-100 text-xs text-slate-800 leading-relaxed font-normal whitespace-pre-wrap">
                            <?php echo esc_html( $inq['message'] ); ?>
                        </div>

                        <!-- Decline / Response Note if present -->
                        <?php if ( ! empty( $inq['admin_notes'] ) ) : ?>
                            <div class="p-3.5 rounded-xl bg-slate-100 text-xs text-slate-700">
                                <span class="font-bold block"><?php esc_html_e( 'Your Response Note:', 'cuba-investment-core' ); ?></span>
                                <span><?php echo esc_html( $inq['admin_notes'] ); ?></span>
                            </div>
                        <?php endif; ?>

                        <!-- Action Controls -->
                        <div class="pt-3 border-t border-slate-100 flex items-center justify-between">
                            <span class="text-[11px] text-slate-400">
                                <?php printf( esc_html__( 'Status: %s', 'cuba-investment-core' ), ucfirst( $status ) ); ?>
                            </span>

                            <div class="flex items-center gap-2">
                                <?php if ( 'pending' === $status ) : ?>
                                    <!-- Decline Button -->
                                    <button 
                                        type="button" 
                                        onclick="cinOpenResponseModal(<?php echo esc_attr( $inq['id'] ); ?>, 'decline')"
                                        id="btn-decline-<?php echo esc_attr( $inq['id'] ); ?>"
                                        class="btn btn-secondary btn-sm font-bold text-rose-600 hover:text-rose-700 hover:bg-rose-50 border-rose-200"
                                    >
                                        <?php esc_html_e( 'Decline', 'cuba-investment-core' ); ?>
                                    </button>

                                    <!-- Accept Button -->
                                    <button 
                                        type="button" 
                                        onclick="cinOpenResponseModal(<?php echo esc_attr( $inq['id'] ); ?>, 'accept')"
                                        id="btn-accept-<?php echo esc_attr( $inq['id'] ); ?>"
                                        class="btn btn-primary btn-sm font-bold shadow-xs inline-flex items-center gap-1.5"
                                    >
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                        </svg>
                                        <span><?php esc_html_e( 'Accept Introduction', 'cuba-investment-core' ); ?></span>
                                    </button>
                                <?php elseif ( 'accepted' === $status ) : ?>
                                    <a 
                                        href="<?php echo esc_url( home_url( '/business-owner/messages/' . ( ! empty( $inq['conversation_id'] ) ? '?convo=' . $inq['conversation_id'] : '' ) ) ); ?>" 
                                        class="btn btn-primary btn-sm font-bold shadow-xs inline-flex items-center gap-1.5"
                                    >
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                        </svg>
                                        <span><?php esc_html_e( 'Open Direct Messages', 'cuba-investment-core' ); ?></span>
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

<!-- RESPONSE CONFIRMATION MODAL -->
<div id="cin-response-modal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-modal="true" role="dialog">
    <div class="min-h-screen px-4 text-center flex items-center justify-center">
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity" onclick="cinCloseResponseModal()"></div>

        <div class="inline-block w-full max-w-md p-6 my-8 text-left align-middle transition-all transform bg-white shadow-2xl rounded-3xl relative z-10 border border-slate-100">
            <h3 id="cin-modal-title" class="text-lg font-heading font-bold text-slate-900 mb-2">
                <?php esc_html_e( 'Respond to Enquiry', 'cuba-investment-core' ); ?>
            </h3>
            <p id="cin-modal-desc" class="text-xs text-slate-500 mb-4 leading-relaxed">
                <?php esc_html_e( 'Accepting opens a mutual direct connection and private messaging channel.', 'cuba-investment-core' ); ?>
            </p>

            <form id="cin-response-form" onsubmit="cinSubmitResponse(event)">
                <input type="hidden" id="cin-modal-inquiry-id" value="">
                <input type="hidden" id="cin-modal-action" value="">

                <div class="mb-4">
                    <label for="cin-modal-note" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                        <?php esc_html_e( 'Optional Note to Investor', 'cuba-investment-core' ); ?>
                    </label>
                    <textarea 
                        id="cin-modal-note" 
                        rows="3" 
                        class="w-full text-xs rounded-xl border border-slate-200 p-3 focus:ring-2 focus:ring-primary focus:border-transparent outline-none"
                        placeholder="<?php esc_attr_e( 'Add an introductory greeting or context...', 'cuba-investment-core' ); ?>"
                    ></textarea>
                </div>

                <div class="flex items-center justify-end gap-3 pt-2">
                    <button type="button" onclick="cinCloseResponseModal()" class="btn btn-secondary btn-sm font-bold">
                        <?php esc_html_e( 'Cancel', 'cuba-investment-core' ); ?>
                    </button>
                    <button type="submit" id="cin-modal-submit-btn" class="btn btn-primary btn-sm font-bold shadow-xs">
                        <?php esc_html_e( 'Confirm Response', 'cuba-investment-core' ); ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function cinOpenResponseModal(inquiryId, action) {
    document.getElementById('cin-modal-inquiry-id').value = inquiryId;
    document.getElementById('cin-modal-action').value = action;
    document.getElementById('cin-modal-note').value = '';

    const titleElem = document.getElementById('cin-modal-title');
    const descElem  = document.getElementById('cin-modal-desc');
    const submitBtn = document.getElementById('cin-modal-submit-btn');

    if (action === 'accept') {
        titleElem.textContent = '<?php echo esc_js( __( 'Accept Business Introduction?', 'cuba-investment-core' ) ); ?>';
        descElem.textContent  = '<?php echo esc_js( __( 'This will establish a direct connection with the investor and initialize private messaging for your opportunity.', 'cuba-investment-core' ) ); ?>';
        submitBtn.className   = 'btn btn-primary btn-sm font-bold shadow-xs';
        submitBtn.textContent = '<?php echo esc_js( __( 'Accept Introduction', 'cuba-investment-core' ) ); ?>';
    } else {
        titleElem.textContent = '<?php echo esc_js( __( 'Decline Introduction Request', 'cuba-investment-core' ) ); ?>';
        descElem.textContent  = '<?php echo esc_js( __( 'Politely decline this inquiry. You may provide a short courteous note explaining why this opportunity is not currently a fit.', 'cuba-investment-core' ) ); ?>';
        submitBtn.className   = 'btn btn-secondary btn-sm font-bold text-rose-600 hover:text-rose-700 hover:bg-rose-50 border-rose-200';
        submitBtn.textContent = '<?php echo esc_js( __( 'Decline Request', 'cuba-investment-core' ) ); ?>';
    }

    document.getElementById('cin-response-modal').classList.remove('hidden');
}

function cinCloseResponseModal() {
    document.getElementById('cin-response-modal').classList.add('hidden');
}

function cinSubmitResponse(e) {
    e.preventDefault();

    const inquiryId = document.getElementById('cin-modal-inquiry-id').value;
    const action    = document.getElementById('cin-modal-action').value;
    const note      = document.getElementById('cin-modal-note').value;
    const submitBtn = document.getElementById('cin-modal-submit-btn');

    submitBtn.disabled = true;
    submitBtn.textContent = '<?php echo esc_js( __( 'Processing...', 'cuba-investment-core' ) ); ?>';

    fetch('<?php echo esc_url( rest_url( 'cin/v1/inquiries/' ) ); ?>' + inquiryId + '/respond', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-WP-Nonce': '<?php echo esc_js( wp_create_nonce( 'wp_rest' ) ); ?>'
        },
        body: JSON.stringify({
            action: action,
            note: note
        })
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            cinCloseResponseModal();
            window.location.reload();
        } else {
            alert(res.message || '<?php echo esc_js( __( 'Failed to respond to enquiry.', 'cuba-investment-core' ) ); ?>');
            submitBtn.disabled = false;
        }
    })
    .catch(() => {
        alert('<?php echo esc_js( __( 'Network error. Please try again.', 'cuba-investment-core' ) ); ?>');
        submitBtn.disabled = false;
    });
}
</script>
