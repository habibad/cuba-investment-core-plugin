<?php
/**
 * Cuba Investment Core - Opportunity Custom Post Type & Taxonomies
 *
 * @package CubaInvestment\Core
 */

namespace CubaInvestment\Core\PostTypes;

use CubaInvestment\Core\Common\Constants;
use CubaInvestment\Core\Common\Logger;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class OpportunityPostType {

    /**
     * Register post type and taxonomies
     */
    public static function register() {
        self::register_post_type();
        self::register_taxonomies();
    }

    /**
     * Register 'cin_opportunity' Custom Post Type
     */
    protected static function register_post_type() {
        $labels = [
            'name'                  => _x( 'Investment Opportunities', 'Post Type General Name', 'cuba-investment-core' ),
            'singular_name'         => _x( 'Opportunity', 'Post Type Singular Name', 'cuba-investment-core' ),
            'menu_name'             => __( 'Opportunities', 'cuba-investment-core' ),
            'name_admin_bar'        => __( 'Opportunity', 'cuba-investment-core' ),
            'archives'              => __( 'Opportunity Archives', 'cuba-investment-core' ),
            'attributes'            => __( 'Opportunity Attributes', 'cuba-investment-core' ),
            'parent_item_colon'     => __( 'Parent Opportunity:', 'cuba-investment-core' ),
            'all_items'             => __( 'All Opportunities', 'cuba-investment-core' ),
            'add_new_item'          => __( 'Add New Opportunity', 'cuba-investment-core' ),
            'add_new'               => __( 'Add New', 'cuba-investment-core' ),
            'new_item'              => __( 'New Opportunity', 'cuba-investment-core' ),
            'edit_item'             => __( 'Edit Opportunity', 'cuba-investment-core' ),
            'update_item'           => __( 'Update Opportunity', 'cuba-investment-core' ),
            'view_item'             => __( 'View Opportunity', 'cuba-investment-core' ),
            'view_items'            => __( 'View Opportunities', 'cuba-investment-core' ),
            'search_items'          => __( 'Search Opportunities', 'cuba-investment-core' ),
            'not_found'             => __( 'No opportunities found', 'cuba-investment-core' ),
            'not_found_in_trash'    => __( 'No opportunities found in Trash', 'cuba-investment-core' ),
            'featured_image'        => __( 'Cover Image', 'cuba-investment-core' ),
            'set_featured_image'    => __( 'Set cover image', 'cuba-investment-core' ),
            'remove_featured_image' => __( 'Remove cover image', 'cuba-investment-core' ),
            'use_featured_image'    => __( 'Use as cover image', 'cuba-investment-core' ),
        ];

        $args = [
            'label'                 => __( 'Opportunity', 'cuba-investment-core' ),
            'description'           => __( 'Verified Cuban business investment opportunities', 'cuba-investment-core' ),
            'labels'                => $labels,
            'supports'              => [ 'title', 'editor', 'excerpt', 'thumbnail', 'author', 'revisions' ],
            'taxonomies'            => [ Constants::TAX_SECTOR, Constants::TAX_PROVINCE, Constants::TAX_STAGE ],
            'hierarchical'          => false,
            'public'                => true,
            'show_ui'               => true,
            'show_in_menu'          => true,
            'menu_position'         => 5,
            'menu_icon'             => 'dashicons-chart-area',
            'show_in_admin_bar'     => true,
            'show_in_nav_menus'     => true,
            'can_export'            => true,
            'has_archive'           => 'opportunities',
            'exclude_from_search'   => false,
            'publicly_queryable'    => true,
            'capability_type'       => 'post',
            'map_meta_cap'          => true,
            'rewrite'               => [
                'slug'       => 'opportunity',
                'with_front' => false,
            ],
            'show_in_rest'          => true,
            'rest_base'             => 'cin-opportunities',
        ];

        register_post_type( Constants::POST_TYPE_OPPORTUNITY, $args );
    }

    /**
     * Register Taxonomies
     */
    protected static function register_taxonomies() {
        // 1. Sector Taxonomy
        register_taxonomy(
            Constants::TAX_SECTOR,
            [ Constants::POST_TYPE_OPPORTUNITY ],
            [
                'hierarchical'      => true,
                'labels'            => [
                    'name'          => __( 'Sectors', 'cuba-investment-core' ),
                    'singular_name' => __( 'Sector', 'cuba-investment-core' ),
                    'search_items'  => __( 'Search Sectors', 'cuba-investment-core' ),
                    'all_items'     => __( 'All Sectors', 'cuba-investment-core' ),
                    'edit_item'     => __( 'Edit Sector', 'cuba-investment-core' ),
                    'update_item'   => __( 'Update Sector', 'cuba-investment-core' ),
                    'add_new_item'  => __( 'Add New Sector', 'cuba-investment-core' ),
                    'new_item_name' => __( 'New Sector Name', 'cuba-investment-core' ),
                    'menu_name'     => __( 'Sectors', 'cuba-investment-core' ),
                ],
                'show_ui'           => true,
                'show_admin_column' => true,
                'query_var'         => true,
                'rewrite'           => [ 'slug' => 'sector' ],
                'show_in_rest'      => true,
            ]
        );

        // 2. Province Taxonomy
        register_taxonomy(
            Constants::TAX_PROVINCE,
            [ Constants::POST_TYPE_OPPORTUNITY ],
            [
                'hierarchical'      => true,
                'labels'            => [
                    'name'          => __( 'Provinces', 'cuba-investment-core' ),
                    'singular_name' => __( 'Province', 'cuba-investment-core' ),
                    'search_items'  => __( 'Search Provinces', 'cuba-investment-core' ),
                    'all_items'     => __( 'All Provinces', 'cuba-investment-core' ),
                    'edit_item'     => __( 'Edit Province', 'cuba-investment-core' ),
                    'update_item'   => __( 'Update Province', 'cuba-investment-core' ),
                    'add_new_item'  => __( 'Add New Province', 'cuba-investment-core' ),
                    'new_item_name' => __( 'New Province Name', 'cuba-investment-core' ),
                    'menu_name'     => __( 'Provinces', 'cuba-investment-core' ),
                ],
                'show_ui'           => true,
                'show_admin_column' => true,
                'query_var'         => true,
                'rewrite'           => [ 'slug' => 'province' ],
                'show_in_rest'      => true,
            ]
        );

        // 3. Stage Taxonomy
        register_taxonomy(
            Constants::TAX_STAGE,
            [ Constants::POST_TYPE_OPPORTUNITY ],
            [
                'hierarchical'      => true,
                'labels'            => [
                    'name'          => __( 'Venture Stages', 'cuba-investment-core' ),
                    'singular_name' => __( 'Venture Stage', 'cuba-investment-core' ),
                    'search_items'  => __( 'Search Stages', 'cuba-investment-core' ),
                    'all_items'     => __( 'All Stages', 'cuba-investment-core' ),
                    'edit_item'     => __( 'Edit Stage', 'cuba-investment-core' ),
                    'update_item'   => __( 'Update Stage', 'cuba-investment-core' ),
                    'add_new_item'  => __( 'Add New Stage', 'cuba-investment-core' ),
                    'new_item_name' => __( 'New Stage Name', 'cuba-investment-core' ),
                    'menu_name'     => __( 'Stages', 'cuba-investment-core' ),
                ],
                'show_ui'           => true,
                'show_admin_column' => true,
                'query_var'         => true,
                'rewrite'           => [ 'slug' => 'stage' ],
                'show_in_rest'      => true,
            ]
        );
    }

    /**
     * Seed initial default terms into taxonomies
     */
    public static function seed_default_terms() {
        // Sectors
        $sectors = [
            'agriculture'   => 'Agriculture & Food Processing',
            'cleantech'     => 'Clean Energy & Infrastructure',
            'logistics'     => 'Supply Chain & Logistics',
            'manufacturing' => 'Light Manufacturing & Industry',
            'technology'    => 'Technology & Digital Services',
            'hospitality'   => 'Tourism & Hospitality Services',
        ];

        foreach ( $sectors as $slug => $name ) {
            if ( ! term_exists( $slug, Constants::TAX_SECTOR ) ) {
                wp_insert_term( $name, Constants::TAX_SECTOR, [ 'slug' => $slug ] );
            }
        }

        // Provinces
        $provinces = [
            'pinar-del-rio'     => 'Pinar del Río',
            'artemisa'          => 'Artemisa',
            'la-habana'         => 'La Habana',
            'mayabeque'         => 'Mayabeque',
            'matanzas'          => 'Matanzas',
            'cienfuegos'        => 'Cienfuegos',
            'villa-clara'       => 'Villa Clara',
            'sancti-spiritus'   => 'Sancti Spíritus',
            'ciego-de-avila'    => 'Ciego de Ávila',
            'camaguey'          => 'Camagüey',
            'las-tunas'         => 'Las Tunas',
            'holguin'           => 'Holguín',
            'granma'            => 'Granma',
            'santiago-de-cuba'  => 'Santiago de Cuba',
            'guantanamo'        => 'Guantánamo',
            'isla-de-juventud'  => 'Isla de la Juventud',
        ];

        foreach ( $provinces as $slug => $name ) {
            if ( ! term_exists( $slug, Constants::TAX_PROVINCE ) ) {
                wp_insert_term( $name, Constants::TAX_PROVINCE, [ 'slug' => $slug ] );
            }
        }

        // Stages
        $stages = [
            'seed'               => 'Seed Stage',
            'operating-business' => 'Operating Business',
            'growth'             => 'Growth Stage',
            'expansion'          => 'Expansion',
        ];

        foreach ( $stages as $slug => $name ) {
            if ( ! term_exists( $slug, Constants::TAX_STAGE ) ) {
                wp_insert_term( $name, Constants::TAX_STAGE, [ 'slug' => $slug ] );
            }
        }

        Logger::info( 'Taxonomy default terms seeded' );
    }
}
