<?php
/**
 * Component: Recent Activity Panel & Empty State
 *
 * Displays real account activity logs from cin_audit_logs, or a clean,
 * professional empty state if no activity has been recorded yet.
 *
 * @package CubaInvestment\Core
 *
 * Expected parameters in $args:
 * @var array  $activities (from ProfileService::get_recent_activity())
 * @var string $title
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$activities = $args['activities'] ?? [];
$title      = $args['title'] ?? __( 'Recent Activity', 'cuba-investment-core' );
?>

<div class="card bg-white p-6 sm:p-7 rounded-2xl border border-slate-200/80 shadow-xs flex flex-col justify-between">
    <div class="flex items-center justify-between pb-4 border-b border-slate-100 mb-5">
        <h3 class="text-base sm:text-lg font-heading font-bold text-slate-900 flex items-center gap-2">
            <svg class="w-5 h-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span><?php echo esc_html( $title ); ?></span>
        </h3>
        <span class="text-xs text-slate-400 font-medium">
            <?php esc_html_e( 'Real-time logs', 'cuba-investment-core' ); ?>
        </span>
    </div>

    <?php if ( ! empty( $activities ) ) : ?>
        <div class="space-y-4">
            <?php foreach ( $activities as $act ) : ?>
                <div class="flex items-start gap-3.5 pb-3 border-b border-slate-50 last:border-0 last:pb-0">
                    <div class="w-8 h-8 rounded-lg bg-slate-100 text-primary flex items-center justify-center shrink-0 mt-0.5">
                        <svg class="w-4 h-4 text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-xs sm:text-sm font-semibold text-slate-900 leading-snug">
                            <?php echo esc_html( $act['message'] ); ?>
                        </p>
                        <p class="text-[11px] text-slate-400 mt-0.5">
                            <?php echo esc_html( $act['time_diff'] ); ?>
                        </p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else : ?>
        <!-- Professional Empty State -->
        <div class="text-center py-8 px-4 flex flex-col items-center justify-center">
            <div class="w-14 h-14 rounded-2xl bg-slate-50 border border-slate-100 flex items-center justify-center text-slate-400 mb-3.5 shadow-2xs">
                <svg class="w-7 h-7 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                </svg>
            </div>
            <h4 class="text-sm font-heading font-bold text-slate-800 mb-1">
                <?php esc_html_e( 'No recent activity recorded yet', 'cuba-investment-core' ); ?>
            </h4>
            <p class="text-xs text-slate-500 max-w-sm leading-relaxed">
                <?php esc_html_e( 'When you update your profile, submit inquiries, or establish connections, your platform activity history will appear here.', 'cuba-investment-core' ); ?>
            </p>
        </div>
    <?php endif; ?>
</div>
