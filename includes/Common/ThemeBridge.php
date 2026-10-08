<?php
/**
 * Cuba Investment Core - Theme Bridge & Template Helper Functions
 *
 * Provides a clean compatibility layer between the core backend plugin
 * and the active WordPress theme without altering theme files.
 *
 * @package CubaInvestment\Core
 */

namespace CubaInvestment\Core\Common;

use CubaInvestment\Core\Auth\Permissions;
use CubaInvestment\Core\Services\OpportunityService;
use CubaInvestment\Core\Services\ProfileService;
use CubaInvestment\Core\Services\NotificationService;
use CubaInvestment\Core\Services\EntitlementService;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class ThemeBridge {

    public static function init() {
        // Filter to enhance theme demo data when real listings exist
        add_filter( 'angel_filter_opportunities', [ __CLASS__, 'filter_theme_opportunities' ], 10, 2 );
    }

    /**
     * Filter theme opportunities: prepend or provide real published listings
     *
     * @param array $default_deals Default demo deals
     * @param array $args
     * @return array
     */
    public static function filter_theme_opportunities( $default_deals, $args = [] ) {
        $real_deals = OpportunityService::get_opportunities( array_merge( $args, [ 'status' => 'publish' ] ) );

        if ( ! empty( $real_deals ) ) {
            return $real_deals;
        }

        return $default_deals;
    }
}
