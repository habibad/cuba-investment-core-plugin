<?php
/**
 * Component: Dashboard Summary Metric Stats Card
 *
 * Reusable 4-column summary metric card conforming to Cuba Investment Network UI.
 *
 * @package CubaInvestment\Core
 *
 * Expected parameters (can be extracted from $args or local variables):
 * @var string $title
 * @var int|string $value
 * @var string $note
 * @var string $icon_svg
 * @var string $color_class (e.g. 'emerald', 'blue', 'amber', 'purple')
 * @var string $link (optional)
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$title       = $args['title'] ?? __( 'Metric', 'cuba-investment-core' );
$value       = $args['value'] ?? 0;
$note        = $args['note'] ?? '';
$icon_svg    = $args['icon_svg'] ?? '';
$color_class = $args['color_class'] ?? 'emerald';
$link        = $args['link'] ?? '';

$color_styles = [
    'emerald' => [
        'bg'   => 'bg-emerald-50 text-emerald-600 border-emerald-100',
        'pill' => 'bg-emerald-50 text-emerald-700',
    ],
    'blue' => [
        'bg'   => 'bg-blue-50 text-blue-600 border-blue-100',
        'pill' => 'bg-blue-50 text-blue-700',
    ],
    'amber' => [
        'bg'   => 'bg-amber-50 text-amber-600 border-amber-100',
        'pill' => 'bg-amber-50 text-amber-700',
    ],
    'purple' => [
        'bg'   => 'bg-purple-50 text-purple-600 border-purple-100',
        'pill' => 'bg-purple-50 text-purple-700',
    ],
    'slate' => [
        'bg'   => 'bg-slate-100 text-slate-600 border-slate-200',
        'pill' => 'bg-slate-100 text-slate-700',
    ],
];

$theme_color = $color_styles[ $color_class ] ?? $color_styles['emerald'];
?>

<div class="card bg-white p-5 sm:p-6 rounded-2xl border border-slate-200/80 shadow-xs hover:shadow-card transition-all duration-200 flex flex-col justify-between">
    <div class="flex items-start justify-between gap-3">
        <div>
            <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider block">
                <?php echo esc_html( $title ); ?>
            </span>
            <div class="mt-2 flex items-baseline gap-2">
                <span class="text-3xl font-heading font-extrabold text-slate-900 tracking-tight">
                    <?php echo esc_html( number_format_i18n( (int) $value ) ); ?>
                </span>
            </div>
        </div>

        <div class="w-12 h-12 rounded-xl flex items-center justify-center shrink-0 border <?php echo esc_attr( $theme_color['bg'] ); ?>">
            <?php echo $icon_svg; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
        </div>
    </div>

    <?php if ( ! empty( $note ) || ! empty( $link ) ) : ?>
        <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-xs">
            <?php if ( ! empty( $note ) ) : ?>
                <span class="text-slate-500 font-medium">
                    <?php echo esc_html( $note ); ?>
                </span>
            <?php endif; ?>

            <?php if ( ! empty( $link ) ) : ?>
                <a href="<?php echo esc_url( $link ); ?>" class="font-bold text-primary hover:text-accent inline-flex items-center gap-1 transition-colors">
                    <span><?php esc_html_e( 'View', 'cuba-investment-core' ); ?></span>
                    <span aria-hidden="true">&rarr;</span>
                </a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>
