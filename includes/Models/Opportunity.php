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

class Opportunity implements \ArrayAccess {

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
     * Cached array representation
     *
     * @var array|null
     */
    protected $data = null;

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

        $documents         = get_post_meta( $this->id, '_cin_documents', true );
        if ( ! is_array( $documents ) ) {
            $documents = [];
        }

        $partnership_types = get_post_meta( $this->id, '_cin_partnership_types', true );
        if ( ! is_array( $partnership_types ) ) {
            $partnership_types = [];
        }

        // Status Label & Badge Classes
        $status_label = 'Draft';
        $badge_class  = 'bg-slate-100 text-slate-700 border-slate-200';

        if ( 'pending' === $this->post->post_status || Constants::STATUS_PENDING_REVIEW === $review_status ) {
            $status_label = 'Under Review';
            $badge_class  = 'bg-blue-50 text-blue-700 border-blue-200';
            $review_status = Constants::STATUS_PENDING_REVIEW;
        } elseif ( 'publish' === $this->post->post_status || Constants::STATUS_APPROVED === $review_status ) {
            $status_label = 'Published';
            $badge_class  = 'bg-emerald-50 text-emerald-700 border-emerald-200';
            $review_status = Constants::STATUS_APPROVED;
        } elseif ( Constants::STATUS_REVISION_REQUESTED === $review_status ) {
            $status_label = 'Revision Requested';
            $badge_class  = 'bg-amber-50 text-amber-700 border-amber-200';
        } elseif ( 'paused' === $review_status ) {
            $status_label = 'Paused';
            $badge_class  = 'bg-purple-50 text-purple-700 border-purple-200';
        } elseif ( 'closed' === $review_status ) {
            $status_label = 'Closed';
            $badge_class  = 'bg-rose-50 text-rose-700 border-rose-200';
        } elseif ( Constants::STATUS_ARCHIVED === $review_status ) {
            $status_label = 'Archived';
            $badge_class  = 'bg-slate-100 text-slate-500 border-slate-200';
        }

        $city = get_post_meta( $this->id, '_cin_city', true ) ?: $province_name;
        $use_of_funds = get_post_meta( $this->id, '_cin_use_of_funds', true ) ?: get_post_meta( $this->id, '_cin_capital_purpose', true ) ?: '';

        $this->data = [
            'id'                     => $this->id,
            'title'                  => get_the_title( $this->post ),
            'slug'                   => $this->post->post_name,
            'company_name'           => get_post_meta( $this->id, '_cin_company_name', true ) ?: get_the_title( $this->post ),
            'sector_slug'            => $sector_slug,
            'sector_name'            => $sector_name,
            'industry'               => $sector_name,
            'industry_slug'          => $sector_slug,
            'location'               => $province_name,
            'country'                => get_post_meta( $this->id, '_cin_country', true ) ?: 'Cuba',
            'city'                   => $city,
            'stage'                  => $stage_name,
            'status'                 => $review_status,
            'post_status'            => $this->post->post_status,
            'status_label'           => $status_label,
            'badge_class'            => $badge_class,
            'is_sample'              => $is_sample,
            'description'            => $this->post->post_content,
            'excerpt'                => get_the_excerpt( $this->post ) ?: wp_trim_words( $this->post->post_content, 35 ),
            'content'                => $this->post->post_content,
            'products_services'      => get_post_meta( $this->id, '_cin_products_services', true ) ?: '',
            'operating_history'      => get_post_meta( $this->id, '_cin_operating_history', true ) ?: '',
            'business_stage'         => get_post_meta( $this->id, '_cin_business_stage', true ) ?: $stage_name,
            'website'                => get_post_meta( $this->id, '_cin_website', true ) ?: '',
            'capital_sought'         => $capital_sought,
            'total_required'         => $capital_sought,
            'minimum_investment'     => $min_investment,
            'currency'               => $currency,
            'use_of_funds'           => $use_of_funds,
            'capital_purpose'        => $use_of_funds,
            'expected_impact'        => get_post_meta( $this->id, '_cin_expected_impact', true ) ?: '',
            'partnership_structure'  => get_post_meta( $this->id, '_cin_partnership_structure', true ) ?: '',
            'funding_stage'          => get_post_meta( $this->id, '_cin_funding_stage', true ) ?: '',
            'capital_notes'          => get_post_meta( $this->id, '_cin_capital_notes', true ) ?: '',
            'revenue_info'           => get_post_meta( $this->id, '_cin_revenue_info', true ) ?: '',
            'milestones'             => get_post_meta( $this->id, '_cin_milestones', true ) ?: '',
            'target_market'          => get_post_meta( $this->id, '_cin_target_market', true ) ?: '',
            'key_partnerships'       => get_post_meta( $this->id, '_cin_key_partnerships', true ) ?: '',
            'business_assets'        => get_post_meta( $this->id, '_cin_business_assets', true ) ?: '',
            'licences_permits'       => get_post_meta( $this->id, '_cin_licences_permits', true ) ?: '',
            'current_projects'       => get_post_meta( $this->id, '_cin_current_projects', true ) ?: '',
            'additional_operations'  => get_post_meta( $this->id, '_cin_additional_operations', true ) ?: '',
            'management_overview'    => get_post_meta( $this->id, '_cin_management_overview', true ) ?: '',
            'key_team_members'       => get_post_meta( $this->id, '_cin_key_team_members', true ) ?: '',
            'ownership_structure'    => $ownership_label,
            'ownership_structure_key'=> $ownership_key,
            'existing_liabilities'   => get_post_meta( $this->id, '_cin_existing_liabilities', true ) ?: '',
            'operational_risks'      => get_post_meta( $this->id, '_cin_operational_risks', true ) ?: '',
            'financial_risks'        => get_post_meta( $this->id, '_cin_financial_risks', true ) ?: '',
            'regulatory_risks'       => get_post_meta( $this->id, '_cin_regulatory_risks', true ) ?: '',
            'material_risks'         => get_post_meta( $this->id, '_cin_material_risks', true ) ?: '',
            'partnership_type'       => get_post_meta( $this->id, '_cin_partnership_type', true ) ?: 'Capital Investment',
            'partnership_types'      => $partnership_types,
            'investment_interest'    => get_post_meta( $this->id, '_cin_investment_interest', true ) ?: '',
            'strategic_expertise'    => get_post_meta( $this->id, '_cin_strategic_expertise', true ) ?: '',
            'bizdev_support'         => get_post_meta( $this->id, '_cin_bizdev_support', true ) ?: '',
            'partner_characteristics'=> get_post_meta( $this->id, '_cin_partner_characteristics', true ) ?: '',
            'additional_partnership' => get_post_meta( $this->id, '_cin_additional_partnership', true ) ?: '',
            'documents'              => $documents,
            'declaration_confirmed'  => (bool) get_post_meta( $this->id, '_cin_declaration_confirmed', true ),
            'submitted_at'           => get_post_meta( $this->id, '_cin_submitted_at', true ) ?: '',
            'updated_at'             => get_post_meta( $this->id, '_cin_updated_at', true ) ?: $this->post->post_modified,
            'created_at'             => $this->post->post_date,
            'author_id'              => (int) $this->post->post_author,
            'image'                  => $image_url,
            'highlights'             => $highlights,
            'info_source'            => $info_source,
        ];

        return $this->data;
    }

    public function get_id() {
        return $this->id;
    }

    public function get_post() {
        return $this->post;
    }

    public function is_draft() {
        if ( ! $this->post ) return false;
        $status = get_post_meta( $this->id, '_cin_review_status', true ) ?: $this->post->post_status;
        return 'draft' === $status;
    }

    public function is_pending_review() {
        if ( ! $this->post ) return false;
        $status = get_post_meta( $this->id, '_cin_review_status', true ) ?: $this->post->post_status;
        return in_array( $status, [ 'pending', Constants::STATUS_PENDING_REVIEW ], true );
    }

    public function is_published() {
        if ( ! $this->post ) return false;
        $status = get_post_meta( $this->id, '_cin_review_status', true ) ?: $this->post->post_status;
        return in_array( $status, [ 'publish', Constants::STATUS_APPROVED ], true );
    }

    public function is_editable_by( $user_id ) {
        if ( ! $user_id || ! $this->post ) return false;
        if ( user_can( $user_id, 'manage_options' ) ) return true;
        if ( (int) $this->post->post_author !== (int) $user_id ) return false;
        return $this->is_draft() || 'revision_requested' === get_post_meta( $this->id, '_cin_review_status', true );
    }

    // ArrayAccess implementation
    public function offsetExists( $offset ): bool {
        $arr = $this->to_array();
        return isset( $arr[ $offset ] );
    }

    #[\ReturnTypeWillChange]
    public function offsetGet( $offset ) {
        $arr = $this->to_array();
        return $arr[ $offset ] ?? null;
    }

    public function offsetSet( $offset, $value ): void {
        if ( null === $this->data ) {
            $this->to_array();
        }
        if ( null === $offset ) {
            $this->data[] = $value;
        } else {
            $this->data[ $offset ] = $value;
        }
    }

    public function offsetUnset( $offset ): void {
        if ( null === $this->data ) {
            $this->to_array();
        }
        unset( $this->data[ $offset ] );
    }
}
