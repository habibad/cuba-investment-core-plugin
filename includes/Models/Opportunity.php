<?php
/**
 * Cuba Investment Core - Opportunity Model
 *
 * @package CubaInvestment\Core
 */

namespace CubaInvestment\Core\Models;

use CubaInvestment\Core\Common\Constants;
use CubaInvestment\Core\Security\Sanitizer;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Opportunity {

    /**
     * WP Post ID
     *
     * @var int
     */
    public $id;

    /**
     * Post object
     *
     * @var \WP_Post|null
     */
    protected $post;

    /**
     * Constructor
     *
     * @param int|\WP_Post $post
     */
    public function __construct( $post ) {
        if ( is_numeric( $post ) ) {
            $this->id   = (int) $post;
            $this->post = get_post( $this->id );
        } elseif ( $post instanceof \WP_Post ) {
            $this->post = $post;
            $this->id   = (int) $post->ID;
        }
    }

    /**
     * Convert to standard structured array (consistent with theme template expectations)
     *
     * @return array
     */
    public function to_array() {
        if ( ! $this->post ) {
            return [];
        }

        $currency          = get_post_meta( $this->id, '_cin_currency', true ) ?: 'USD';
        $capital_sought    = (float) ( get_post_meta( $this->id, '_cin_capital_sought', true ) ?: 0 );
        $min_investment    = (float) ( get_post_meta( $this->id, '_cin_minimum_investment', true ) ?: 0 );
        $ownership_key     = get_post_meta( $this->id, '_cin_ownership_structure', true ) ?: 'mipyme_private';
        $ownership_label   = Sanitizer::legal_structure_label( $ownership_key );
        $review_status     = get_post_meta( $this->id, '_cin_review_status', true ) ?: 'pending_review';
        $is_sample         = (bool) get_post_meta( $this->id, '_cin_is_sample', true );
        $info_source       = get_post_meta( $this->id, '_cin_info_source', true ) ?: 'Information supplied by the business owner';
        $highlights        = get_post_meta( $this->id, '_cin_highlights', true );
        if ( ! is_array( $highlights ) ) {
            $highlights = [];
        }

        // Sectors
        $sectors      = wp_get_post_terms( $this->id, Constants::TAX_SECTOR );
        $sector_name  = ! empty( $sectors ) && ! is_wp_error( $sectors ) ? $sectors[0]->name : 'General Sector';
        $sector_slug  = ! empty( $sectors ) && ! is_wp_error( $sectors ) ? $sectors[0]->slug : 'general';

        // Provinces
        $provinces     = wp_get_post_terms( $this->id, Constants::TAX_PROVINCE );
        $province_name = ! empty( $provinces ) && ! is_wp_error( $provinces ) ? $provinces[0]->name : 'La Habana';

        // Stage
        $stages        = wp_get_post_terms( $this->id, Constants::TAX_STAGE );
        $stage_name    = ! empty( $stages ) && ! is_wp_error( $stages ) ? $stages[0]->name : 'Operating Business';

        // Featured Image
        $image_id  = get_post_thumbnail_id( $this->id );
        $image_url = $image_id ? wp_get_attachment_image_url( $image_id, 'large' ) : '';

        // Status Label
        $status_label = 'Initial Listing Review';
        if ( 'publish' === $this->post->post_status || 'publish' === $review_status ) {
            $status_label = 'Published Listing';
        } elseif ( 'revision_requested' === $review_status ) {
            $status_label = 'Revision Requested';
        }

        return [
            'id'                  => $this->id,
            'title'               => get_the_title( $this->post ),
            'slug'                => $this->post->post_name,
            'company_name'        => get_post_meta( $this->id, '_cin_company_name', true ) ?: get_the_title( $this->post ),
            'location'            => $province_name,
            'country'             => 'Cuba',
            'industry'            => $sector_name,
            'industry_slug'       => $sector_slug,
            'stage'               => $stage_name,
            'status'              => $review_status,
            'status_label'        => $status_label,
            'is_sample'           => $is_sample,
            'description'         => get_the_excerpt( $this->post ) ?: wp_trim_words( $this->post->post_content, 35 ),
            'content'             => $this->post->post_content,
            'highlights'          => $highlights,
            'capital_sought'      => $capital_sought,
            'total_required'      => $capital_sought,
            'minimum_investment'  => $min_investment,
            'currency'            => $currency,
            'ownership_structure' => $ownership_label,
            'partnership_type'    => get_post_meta( $this->id, '_cin_partnership_type', true ) ?: 'Direct Investment',
            'operating_history'   => get_post_meta( $this->id, '_cin_operating_history', true ) ?: 'Operating Business',
            'capital_purpose'     => get_post_meta( $this->id, '_cin_capital_purpose', true ) ?: '',
            'info_source'         => $info_source,
            'image'               => $image_url,
            'author_id'           => (int) $this->post->post_author,
            'created_at'          => $this->post->post_date,
        ];
    }
}
