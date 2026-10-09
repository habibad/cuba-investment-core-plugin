<?php
/**
 * Template: Private Messaging (Investor & Business Owner Portals)
 * Routes: /investor/messages/ and /business-owner/messages/
 *
 * Implements a high-grade, responsive private chat interface between connected members.
 * Supports AJAX polling, unread badges, conversation switching, and mobile back navigation.
 *
 * @package CubaInvestment\Core
 */

use CubaInvestment\Core\Auth\Permissions;
use CubaInvestment\Core\Common\Constants;
use CubaInvestment\Core\Services\MessagingService;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Access Control
if ( ! is_user_logged_in() ) {
    wp_safe_redirect( home_url( '/login/?redirect_to=' . urlencode( $_SERVER['REQUEST_URI'] ?? '' ) ) );
    exit;
}

$user = wp_get_current_user();
$roles = (array) $user->roles;
$is_investor = in_array( Constants::ROLE_INVESTOR, $roles, true );
$is_business = in_array( Constants::ROLE_BUSINESS_OWNER, $roles, true );

$current_path = trim( parse_url( $_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH ), '/' );
if ( false !== strpos( $current_path, 'investor' ) ) {
    $portal_role = 'investor';
    $back_url    = home_url( '/investor/dashboard/' );
} else {
    $portal_role = 'business';
    $back_url    = home_url( '/business-owner/dashboard/' );
}

$preselected_convo = isset( $_GET['convo'] ) ? absint( $_GET['convo'] ) : 0;

$page_title = __( 'Messages', 'cuba-investment-core' );
$topbar_cta = null;

require_once CIN_PLUGIN_DIR . 'templates/dashboard/layout/header.php';
require_once CIN_PLUGIN_DIR . 'templates/dashboard/layout/sidebar.php';
?>

<!-- MAIN CONTENT WRAPPER -->
<div class="main-content-area lg:pl-64 xl:pl-72 flex flex-col flex-1 min-h-screen transition-all duration-300">
    <?php require CIN_PLUGIN_DIR . 'templates/dashboard/layout/topbar.php'; ?>

    <main id="dashboard-main-content" class="flex-1 p-4 sm:p-6 lg:p-8 max-w-7xl w-full mx-auto flex flex-col">

        <!-- Header -->
        <div class="flex items-center justify-between pb-3">
            <div>
                <a href="<?php echo esc_url( $back_url ); ?>" class="inline-flex items-center text-xs font-bold text-primary hover:text-accent mb-1 transition-colors">
                    &larr; <?php esc_html_e( 'Back to Overview', 'cuba-investment-core' ); ?>
                </a>
                <h1 class="text-2xl sm:text-3xl font-heading font-extrabold text-slate-900 tracking-tight">
                    <?php esc_html_e( 'Direct Messages', 'cuba-investment-core' ); ?>
                </h1>
            </div>
            <div class="text-xs text-slate-400 font-medium">
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-50 text-emerald-700 font-bold border border-emerald-100">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    <?php esc_html_e( 'Direct Connection Active', 'cuba-investment-core' ); ?>
                </span>
            </div>
        </div>

<style>
#cin-convo-list-pane {
    width: 360px;
    flex-shrink: 0;
    display: flex;
    flex-direction: column;
}
#cin-chat-pane {
    display: flex;
    flex: 1 1 0%;
    flex-direction: column;
}
@media (max-width: 767px) {
    #cin-convo-list-pane {
        width: 100%;
    }
    #cin-convo-list-pane.mobile-hidden {
        display: none !important;
    }
    #cin-chat-pane {
        display: none !important;
    }
    #cin-chat-pane.mobile-active {
        display: flex !important;
    }
}
</style>

        <!-- Chat Container Window -->
        <div class="bg-white rounded-3xl border border-slate-200/90 shadow-md flex-1 flex overflow-hidden min-h-[580px] h-[calc(100vh-220px)] max-h-[820px] relative">
            
            <!-- LEFT PANEL: Conversation List -->
            <div id="cin-convo-list-pane" class="border-r border-slate-100 flex flex-col shrink-0 bg-slate-50/50">
                <!-- Search box -->
                <div class="p-3.5 border-b border-slate-100 bg-white">
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-slate-400">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </span>
                        <input 
                            type="text" 
                            id="cin-search-convos" 
                            placeholder="<?php esc_attr_e( 'Filter conversations...', 'cuba-investment-core' ); ?>"
                            class="w-full pl-9 pr-3 py-2 text-xs rounded-xl bg-slate-100 border-none focus:ring-2 focus:ring-primary focus:bg-white outline-none transition-all"
                            oninput="cinFilterConversations(this.value)"
                        >
                    </div>
                </div>

                <!-- Conversation scroll items -->
                <div id="cin-convo-items" class="flex-1 overflow-y-auto divide-y divide-slate-100/80">
                    <div class="p-8 text-center text-xs text-slate-400">
                        <div class="w-6 h-6 border-2 border-primary border-t-transparent rounded-full animate-spin mx-auto mb-2"></div>
                        <?php esc_html_e( 'Loading conversations...', 'cuba-investment-core' ); ?>
                    </div>
                </div>
            </div>

            <!-- RIGHT PANEL: Active Chat Thread -->
            <div id="cin-chat-pane" class="flex-1 flex flex-col bg-white overflow-hidden relative">
                
                <!-- Chat Header -->
                <div id="cin-chat-header" class="px-5 py-3.5 border-b border-slate-100 bg-white flex items-center justify-between shadow-2xs z-10">
                    <div class="flex items-center gap-3 min-w-0">
                        <!-- Mobile back button -->
                        <button type="button" onclick="cinMobileBackToList()" class="md:hidden p-1.5 -ml-1.5 text-slate-500 hover:text-slate-900 focus:outline-none" aria-label="<?php esc_attr_e( 'Back to list', 'cuba-investment-core' ); ?>">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                            </svg>
                        </button>

                        <div id="cin-active-partner-avatar" class="w-10 h-10 rounded-full bg-primary/10 text-primary font-black flex items-center justify-center text-xs shrink-0 border border-slate-100">
                            CIN
                        </div>
                        <div class="min-w-0">
                            <h3 id="cin-active-partner-name" class="text-sm font-bold text-slate-900 truncate">
                                <?php esc_html_e( 'Select a Conversation', 'cuba-investment-core' ); ?>
                            </h3>
                            <p id="cin-active-opp-title" class="text-[11px] text-slate-400 truncate">
                                <?php esc_html_e( 'Select a discussion thread from the left.', 'cuba-investment-core' ); ?>
                            </p>
                        </div>
                    </div>

                    <div class="text-[11px] font-bold text-emerald-700 bg-emerald-50 px-2.5 py-1 rounded-full shrink-0 border border-emerald-100">
                        <?php esc_html_e( 'Connected', 'cuba-investment-core' ); ?>
                    </div>
                </div>

                <!-- Messages Thread Scroll Area -->
                <div id="cin-messages-thread" class="flex-1 overflow-y-auto p-4 sm:p-6 space-y-3.5 bg-slate-50/40">
                    <!-- Placeholder initial message -->
                    <div id="cin-thread-empty-state" class="h-full flex flex-col items-center justify-center text-center p-8">
                        <div class="w-14 h-14 rounded-2xl bg-white text-slate-300 shadow-sm flex items-center justify-center mb-3">
                            <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                            </svg>
                        </div>
                        <h4 class="text-sm font-bold text-slate-700"><?php esc_html_e( 'No Conversation Selected', 'cuba-investment-core' ); ?></h4>
                        <p class="text-xs text-slate-400 max-w-xs mt-1"><?php esc_html_e( 'Choose a connection from the list to view messages and reply.', 'cuba-investment-core' ); ?></p>
                    </div>
                </div>

                <!-- Message Composer Bar -->
                <div id="cin-composer-bar" class="p-3.5 sm:p-4 border-t border-slate-100 bg-white">
                    <form id="cin-message-form" onsubmit="cinSendMessage(event)" class="flex items-end gap-2.5">
                        <div class="flex-1 min-w-0">
                            <textarea 
                                id="cin-message-input" 
                                rows="2" 
                                placeholder="<?php esc_attr_e( 'Write a direct message (Press Enter to send, Shift+Enter for new line)...', 'cuba-investment-core' ); ?>"
                                class="w-full text-xs rounded-2xl border border-slate-200 p-3 focus:ring-2 focus:ring-primary focus:border-transparent outline-none resize-none leading-relaxed"
                                onkeydown="cinHandleComposerKeydown(event)"
                                disabled
                            ></textarea>
                        </div>
                        <button 
                            type="submit" 
                            id="cin-send-btn" 
                            disabled 
                            class="p-3 rounded-2xl bg-primary text-white hover:bg-primary-light disabled:opacity-40 disabled:cursor-not-allowed transition-all shadow-xs cursor-pointer shrink-0"
                            title="<?php esc_attr_e( 'Send Message', 'cuba-investment-core' ); ?>"
                        >
                            <svg class="w-5 h-5 rotate-90" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                            </svg>
                        </button>
                    </form>
                </div>

            </div>

        </div>

    </main>

    <?php require CIN_PLUGIN_DIR . 'templates/dashboard/layout/footer.php'; ?>
</div>

<script>
let cinConversations = [];
let cinActiveConvoId = <?php echo (int) $preselected_convo; ?>;
let cinPollTimer = null;
const restNonce = '<?php echo esc_js( wp_create_nonce( 'wp_rest' ) ); ?>';

document.addEventListener('DOMContentLoaded', function() {
    cinLoadConversations(true);

    // Auto poll every 6 seconds
    cinPollTimer = setInterval(() => {
        if (cinActiveConvoId) {
            cinLoadMessages(cinActiveConvoId, false);
        }
        cinLoadConversations(false);
    }, 6000);
});

function cinLoadConversations(initial = false) {
    fetch('<?php echo esc_url( rest_url( 'cin/v1/conversations' ) ); ?>', {
        headers: { 'X-WP-Nonce': restNonce }
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            cinConversations = res.conversations || [];
            cinRenderConvoList();

            if (initial) {
                if (cinActiveConvoId) {
                    cinSelectConversation(cinActiveConvoId);
                } else if (cinConversations.length > 0) {
                    cinSelectConversation(cinConversations[0].id);
                }
            }
        }
    })
    .catch(err => console.error(err));
}

function cinRenderConvoList(filterText = '') {
    const listContainer = document.getElementById('cin-convo-items');
    if (!listContainer) return;

    let items = cinConversations;
    if (filterText) {
        const query = filterText.toLowerCase();
        items = items.filter(c => 
            (c.partner_name && c.partner_name.toLowerCase().includes(query)) ||
            (c.opportunity_title && c.opportunity_title.toLowerCase().includes(query))
        );
    }

    if (items.length === 0) {
        listContainer.innerHTML = `
            <div class="p-8 text-center text-xs text-slate-400">
                ${cinConversations.length === 0 ? '<?php echo esc_js( __( 'No active message conversations.', 'cuba-investment-core' ) ); ?>' : '<?php echo esc_js( __( 'No matching conversations found.', 'cuba-investment-core' ) ); ?>'}
            </div>
        `;
        return;
    }

    let html = '';
    items.forEach(c => {
        const isActive = (c.id === cinActiveConvoId);
        const activeClass = isActive ? 'bg-primary/5 border-l-4 border-primary text-slate-900' : 'hover:bg-slate-100/70 text-slate-700';
        
        let avatarHtml = `<div class="w-10 h-10 rounded-full bg-primary/10 text-primary font-bold flex items-center justify-center text-xs shrink-0">${c.partner_initials || 'U'}</div>`;
        if (c.partner_avatar) {
            avatarHtml = `<img src="${c.partner_avatar}" class="w-10 h-10 rounded-full object-cover shrink-0 border border-slate-200">`;
        }

        let unreadBadge = '';
        if (c.unread_count > 0) {
            unreadBadge = `<span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-primary text-white shrink-0">${c.unread_count}</span>`;
        }

        html += `
            <div 
                onclick="cinSelectConversation(${c.id})" 
                class="p-4 cursor-pointer transition-all flex items-start gap-3 ${activeClass}"
                data-convo-id="${c.id}"
            >
                ${avatarHtml}
                <div class="min-w-0 flex-1">
                    <div class="flex items-baseline justify-between gap-1 mb-0.5">
                        <h4 class="text-xs font-bold truncate ${isActive ? 'text-primary' : 'text-slate-900'}">${c.partner_name}</h4>
                        <span class="text-[10px] text-slate-400 shrink-0">${c.last_message_time || ''}</span>
                    </div>
                    <p class="text-[11px] text-slate-500 line-clamp-1 leading-snug">${c.last_message_preview || ''}</p>
                    ${c.opportunity_title ? `<p class="text-[10px] text-primary/80 font-semibold truncate mt-1">Listing: ${c.opportunity_title}</p>` : ''}
                </div>
                ${unreadBadge}
            </div>
        `;
    });

    listContainer.innerHTML = html;
}

function cinFilterConversations(val) {
    cinRenderConvoList(val);
}

function cinSelectConversation(convoId) {
    cinActiveConvoId = convoId;

    const convo = cinConversations.find(c => c.id === convoId);
    if (convo) {
        document.getElementById('cin-active-partner-name').textContent = convo.partner_name;
        document.getElementById('cin-active-opp-title').textContent = convo.opportunity_title ? 'Listing: ' + convo.opportunity_title : '<?php echo esc_js( __( 'Direct Discussion Channel', 'cuba-investment-core' ) ); ?>';

        const avatarElem = document.getElementById('cin-active-partner-avatar');
        if (convo.partner_avatar) {
            avatarElem.innerHTML = `<img src="${convo.partner_avatar}" class="w-full h-full rounded-full object-cover">`;
        } else {
            avatarElem.textContent = convo.partner_initials || 'U';
        }

        // Enable composer input
        const input = document.getElementById('cin-message-input');
        input.disabled = false;
        document.getElementById('cin-send-btn').disabled = false;
    }

    cinRenderConvoList(document.getElementById('cin-search-convos').value);

    // On mobile, show chat pane and hide convo list pane
    const listPane = document.getElementById('cin-convo-list-pane');
    const chatPane = document.getElementById('cin-chat-pane');
    if (window.innerWidth < 768) {
        listPane.classList.add('mobile-hidden');
        chatPane.classList.add('mobile-active');
    }

    cinLoadMessages(convoId, true);
}

function cinMobileBackToList() {
    const listPane = document.getElementById('cin-convo-list-pane');
    const chatPane = document.getElementById('cin-chat-pane');
    listPane.classList.remove('mobile-hidden');
    chatPane.classList.remove('mobile-active');
}

function cinLoadMessages(convoId, scrollToBottom = true) {
    fetch('<?php echo esc_url( rest_url( 'cin/v1/conversations/' ) ); ?>' + convoId + '/messages', {
        headers: { 'X-WP-Nonce': restNonce }
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            cinRenderMessages(res.messages || [], scrollToBottom);
        }
    })
    .catch(err => console.error(err));
}

function cinRenderMessages(messages, scrollToBottom = true) {
    const thread = document.getElementById('cin-messages-thread');
    if (!thread) return;

    if (messages.length === 0) {
        thread.innerHTML = `
            <div class="h-full flex flex-col items-center justify-center text-center p-8">
                <p class="text-xs text-slate-400"><?php echo esc_js( __( 'No messages exchanged yet. Send an opening message below.', 'cuba-investment-core' ) ); ?></p>
            </div>
        `;
        return;
    }

    let html = '';
    messages.forEach(m => {
        const isMine = m.is_mine;
        const alignClass = isMine ? 'justify-end' : 'justify-start';
        const bubbleClass = isMine 
            ? 'bg-primary text-white rounded-br-xs shadow-xs' 
            : 'bg-white text-slate-800 border border-slate-200/80 rounded-bl-xs shadow-2xs';
        const timeClass = isMine ? 'text-white/70 text-right' : 'text-slate-400 text-left';

        html += `
            <div class="flex ${alignClass}">
                <div class="max-w-[85%] sm:max-w-[70%]">
                    <div class="px-4 py-2.5 rounded-2xl text-xs leading-relaxed whitespace-pre-wrap ${bubbleClass}">
                        ${m.message_body}
                    </div>
                    <div class="text-[10px] mt-1 px-1 ${timeClass}">
                        ${m.formatted_time || ''}
                    </div>
                </div>
            </div>
        `;
    });

    thread.innerHTML = html;

    if (scrollToBottom) {
        thread.scrollTop = thread.scrollHeight;
    }
}

function cinHandleComposerKeydown(e) {
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        document.getElementById('cin-message-form').dispatchEvent(new Event('submit'));
    }
}

function cinSendMessage(e) {
    e.preventDefault();

    if (!cinActiveConvoId) return;

    const input = document.getElementById('cin-message-input');
    const body  = input.value.trim();
    if (!body) return;

    const sendBtn = document.getElementById('cin-send-btn');
    sendBtn.disabled = true;

    fetch('<?php echo esc_url( rest_url( 'cin/v1/conversations/' ) ); ?>' + cinActiveConvoId + '/messages', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-WP-Nonce': restNonce
        },
        body: JSON.stringify({ message: body })
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            input.value = '';
            cinLoadMessages(cinActiveConvoId, true);
            cinLoadConversations(false);
        } else {
            alert(res.message || '<?php echo esc_js( __( 'Failed to dispatch message.', 'cuba-investment-core' ) ); ?>');
        }
        sendBtn.disabled = false;
        input.focus();
    })
    .catch(() => {
        alert('<?php echo esc_js( __( 'Network error. Please try again.', 'cuba-investment-core' ) ); ?>');
        sendBtn.disabled = false;
    });
}
</script>
