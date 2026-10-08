<?php
/**
 * Component: Profile Completion Card
 *
 * Dynamic completion card displaying percentage progress, missing items,
 * and direct call to action to complete or update profile.
 *
 * @package CubaInvestment\Core
 *
 * Expected parameters in $args:
 * @var array  $completion (from ProfileService::calculate_*_completion())
 * @var string $profile_url
 * @var string $title
 * @var string $description
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$completion  = $args['completion'] ?? [ 'percentage' => 0, 'completed' => [], 'missing' => [], 'is_complete' => false ];
$profile_url = $args['profile_url'] ?? home_url( '/account/' );
$title       = $args['title'] ?? __( 'Profile Completion', 'cuba-investment-core' );
$description = $args['description'] ?? __( 'Complete all sections of your profile to unlock full platform matching and credibility.', 'cuba-investment-core' );

$pct = (int) ( $completion['percentage'] ?? 0 );
$is_complete = $pct >= 100;

// Progress bar color
$bar_color = $pct >= 80 ? 'bg-accent' : ( $pct >= 50 ? 'bg-primary' : 'bg-amber-500' );
?>

<div class="card bg-white p-6 sm:p-7 rounded-2xl border border-slate-200/80 shadow-xs relative overflow-hidden">
    <!-- Top Progress Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-5 border-b border-slate-100">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <h3 class="text-base sm:text-lg font-heading font-bold text-slate-900">
                    <?php echo esc_html( $title ); ?>
                </h3>
                <?php if ( $is_complete ) : ?>
                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                        </svg>
                        <?php esc_html_e( '100% Complete', 'cuba-investment-core' ); ?>
                    </span>
                <?php endif; ?>
            </div>
            <p class="text-xs sm:text-sm text-slate-500 leading-relaxed max-w-2xl">
                <?php echo esc_html( $description ); ?>
            </p>
        </div>

        <div class="flex items-center sm:flex-col sm:items-end shrink-0 gap-2 sm:gap-0">
            <span class="text-2xl sm:text-3xl font-heading font-extrabold text-slate-900 tracking-tight">
                <?php echo esc_html( $pct ); ?>%
            </span>
            <span class="text-xs text-slate-500 font-medium">
                <?php echo esc_html( sprintf( __( '%d of %d items done', 'cuba-investment-core' ), count( $completion['completed'] ?? [] ), ( count( $completion['completed'] ?? [] ) + count( $completion['missing'] ?? [] ) ) ) ); ?>
            </span>
        </div>
    </div>

    <!-- Progress Bar -->
    <div class="mt-5 mb-5">
        <div class="w-full bg-slate-100 rounded-full h-3 overflow-hidden">
            <div class="<?php echo esc_attr( $bar_color ); ?> h-full rounded-full transition-all duration-500 ease-out" style="width: <?php echo esc_attr( $pct ); ?>%;"></div>
        </div>
    </div>

    <!-- Missing Items or Completion State -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pt-1">
        <?php if ( ! $is_complete && ! empty( $completion['missing'] ) ) : ?>
            <div class="flex-1">
                <span class="text-xs font-bold text-slate-700 uppercase tracking-wider block mb-2">
                    <?php esc_html_e( 'Recommended Next Steps:', 'cuba-investment-core' ); ?>
                </span>
                <div class="flex flex-wrap gap-2">
                    <?php foreach ( array_slice( $completion['missing'], 0, 4 ) as $item ) : ?>
                        <a href="<?php echo esc_url( $item['url'] ?? $profile_url ); ?>" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold bg-slate-50 text-slate-700 hover:bg-primary-50 hover:text-primary border border-slate-200 transition-colors">
                            <span class="text-accent font-bold">+</span>
                            <span><?php echo esc_html( $item['label'] ); ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php else : ?>
            <div class="flex items-center gap-2 text-xs font-semibold text-emerald-700 bg-emerald-50 px-3 py-2 rounded-xl border border-emerald-100">
                <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span><?php esc_html_e( 'Your profile is fully up to date and verified. Potential partners can see your complete credentials.', 'cuba-investment-core' ); ?></span>
            </div>
        <?php endif; ?>

        <div class="shrink-0">
            <a href="<?php echo esc_url( $profile_url ); ?>" class="btn btn-primary btn-sm px-4 py-2 font-bold shadow-xs hover:shadow transition-all inline-flex items-center gap-1.5">
                <span><?php echo $is_complete ? esc_html__( 'Edit Profile', 'cuba-investment-core' ) : esc_html__( 'Complete Profile', 'cuba-investment-core' ); ?></span>
                <span aria-hidden="true">&rarr;</span>
            </a>
        </div>
    </div>
</div>
