<?php
/**
 * Cuba Investment Core - Opportunity Admin Meta Boxes
 *
 * @package CubaInvestment\Core
 */

namespace CubaInvestment\Core\PostTypes;

use CubaInvestment\Core\Common\Constants;
use CubaInvestment\Core\Security\Sanitizer;
use CubaInvestment\Core\Security\NonceManager;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class OpportunityMetaBoxes {

    public static function register() {
        add_action( 'add_meta_boxes', [ __CLASS__, 'add_meta_boxes' ] );
        add_action( 'save_post_' . Constants::POST_TYPE_OPPORTUNITY, [ __CLASS__, 'save_meta_boxes' ], 10, 2 );
    }

    public static function add_meta_boxes() {
        add_meta_box(
            'cin_opportunity_parameters',
            __( 'Investment & Enterprise Parameters', 'cuba-investment-core' ),
            [ __CLASS__, 'render_parameters_meta_box' ],
            Constants::POST_TYPE_OPPORTUNITY,
            'normal',
            'high'
        );

        add_meta_box(
            'cin_opportunity_review',
            __( 'Listing Review & Administrative Quality Controls', 'cuba-investment-core' ),
            [ __CLASS__, 'render_review_meta_box' ],
            Constants::POST_TYPE_OPPORTUNITY,
            'side',
            'high'
        );
    }

    public static function render_parameters_meta_box( $post ) {
        NonceManager::field( NonceManager::ACTION_OPPORTUNITY, '_cin_opportunity_nonce' );

        $company_name   = get_post_meta( $post->ID, '_cin_company_name', true );
        $capital_sought = get_post_meta( $post->ID, '_cin_capital_sought', true );
        $min_investment = get_post_meta( $post->ID, '_cin_minimum_investment', true );
        $currency       = get_post_meta( $post->ID, '_cin_currency', true ) ?: 'USD';
        $ownership      = get_post_meta( $post->ID, '_cin_ownership_structure', true ) ?: 'mipyme_private';
        $partnership    = get_post_meta( $post->ID, '_cin_partnership_type', true );
        $history        = get_post_meta( $post->ID, '_cin_operating_history', true );
        $purpose        = get_post_meta( $post->ID, '_cin_capital_purpose', true );
        $highlights     = get_post_meta( $post->ID, '_cin_highlights', true );
        if ( ! is_array( $highlights ) ) {
            $highlights = [];
        }

        $legal_structures = Sanitizer::get_legal_structures();
        ?>
        <table class="form-table">
            <tr>
                <th scope="row"><label for="cin_company_name"><?php esc_html_e( 'Enterprise Legal Name', 'cuba-investment-core' ); ?></label></th>
                <td>
                    <input type="text" id="cin_company_name" name="cin_company_name" value="<?php echo esc_attr( $company_name ); ?>" class="regular-text" required />
                    <p class="description"><?php esc_html_e( 'Official registered name of the Cuban private enterprise or cooperative.', 'cuba-investment-core' ); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="cin_capital_sought"><?php esc_html_e( 'Capital Sought', 'cuba-investment-core' ); ?></label></th>
                <td>
                    <input type="number" step="0.01" min="0" id="cin_capital_sought" name="cin_capital_sought" value="<?php echo esc_attr( $capital_sought ); ?>" class="regular-text" required />
                    <select name="cin_currency" id="cin_currency">
                        <option value="USD" <?php selected( $currency, 'USD' ); ?>>USD</option>
                        <option value="EUR" <?php selected( $currency, 'EUR' ); ?>>EUR</option>
                        <option value="CAD" <?php selected( $currency, 'CAD' ); ?>>CAD</option>
                        <option value="CUP" <?php selected( $currency, 'CUP' ); ?>>CUP</option>
                    </select>
                    <p class="description"><?php esc_html_e( 'Total capital requirement. Neutral currency display per platform audit.', 'cuba-investment-core' ); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="cin_minimum_investment"><?php esc_html_e( 'Minimum Investment', 'cuba-investment-core' ); ?></label></th>
                <td>
                    <input type="number" step="0.01" min="0" id="cin_minimum_investment" name="cin_minimum_investment" value="<?php echo esc_attr( $min_investment ); ?>" class="regular-text" />
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="cin_ownership_structure"><?php esc_html_e( 'Ownership Structure', 'cuba-investment-core' ); ?></label></th>
                <td>
                    <select name="cin_ownership_structure" id="cin_ownership_structure">
                        <?php foreach ( $legal_structures as $key => $label ) : ?>
                            <option value="<?php echo esc_attr( $key ); ?>" <?php selected( $ownership, $key ); ?>>
                                <?php echo esc_html( $label ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="cin_partnership_type"><?php esc_html_e( 'Proposed Partnership Type', 'cuba-investment-core' ); ?></label></th>
                <td>
                    <input type="text" id="cin_partnership_type" name="cin_partnership_type" value="<?php echo esc_attr( $partnership ); ?>" class="regular-text" placeholder="<?php esc_attr_e( 'e.g. Equipment Partnership, Revenue-Share, Direct Investment', 'cuba-investment-core' ); ?>" />
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="cin_operating_history"><?php esc_html_e( 'Operating History', 'cuba-investment-core' ); ?></label></th>
                <td>
                    <input type="text" id="cin_operating_history" name="cin_operating_history" value="<?php echo esc_attr( $history ); ?>" class="regular-text" placeholder="<?php esc_attr_e( 'e.g. 3+ Years Operating', 'cuba-investment-core' ); ?>" />
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="cin_capital_purpose"><?php esc_html_e( 'Use of Funds / Objective', 'cuba-investment-core' ); ?></label></th>
                <td>
                    <textarea id="cin_capital_purpose" name="cin_capital_purpose" rows="3" class="large-text"><?php echo esc_textarea( $purpose ); ?></textarea>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="cin_highlights"><?php esc_html_e( 'Key Business Highlights', 'cuba-investment-core' ); ?></label></th>
                <td>
                    <textarea id="cin_highlights" name="cin_highlights" rows="4" class="large-text" placeholder="<?php esc_attr_e( 'Enter one highlight bullet per line', 'cuba-investment-core' ); ?>"><?php echo esc_textarea( implode( "\n", $highlights ) ); ?></textarea>
                    <p class="description"><?php esc_html_e( 'Enter one key verified metric or operational highlight per line.', 'cuba-investment-core' ); ?></p>
                </td>
            </tr>
        </table>
        <?php
    }

    public static function render_review_meta_box( $post ) {
        $review_status = get_post_meta( $post->ID, '_cin_review_status', true ) ?: Constants::STATUS_PENDING_REVIEW;
        $is_sample     = (bool) get_post_meta( $post->ID, '_cin_is_sample', true );
        $review_notes  = get_post_meta( $post->ID, '_cin_review_notes', true );
        $info_source   = get_post_meta( $post->ID, '_cin_info_source', true ) ?: 'Information supplied by the business owner';
        ?>
        <p>
            <label for="cin_review_status"><strong><?php esc_html_e( 'Listing Review Status:', 'cuba-investment-core' ); ?></strong></label><br />
            <select name="cin_review_status" id="cin_review_status" style="width:100%; margin-top:5px;">
                <option value="draft" <?php selected( $review_status, 'draft' ); ?>><?php esc_html_e( 'Draft (Incomplete)', 'cuba-investment-core' ); ?></option>
                <option value="pending_review" <?php selected( $review_status, 'pending_review' ); ?>><?php esc_html_e( 'Initial Listing Review (Under Review)', 'cuba-investment-core' ); ?></option>
                <option value="publish" <?php selected( $review_status, 'publish' ); ?>><?php esc_html_e( 'Approved & Published', 'cuba-investment-core' ); ?></option>
                <option value="revision_requested" <?php selected( $review_status, 'revision_requested' ); ?>><?php esc_html_e( 'Revision Requested', 'cuba-investment-core' ); ?></option>
                <option value="rejected" <?php selected( $review_status, 'rejected' ); ?>><?php esc_html_e( 'Rejected / Non-Compliant', 'cuba-investment-core' ); ?></option>
            </select>
        </p>

        <p>
            <label>
                <input type="checkbox" name="cin_is_sample" value="1" <?php checked( $is_sample, true ); ?> />
                <?php esc_html_e( 'Mark as Pre-Launch Sample Opportunity', 'cuba-investment-core' ); ?>
            </label>
        </p>

        <p>
            <label for="cin_info_source"><strong><?php esc_html_e( 'Information Source Notice:', 'cuba-investment-core' ); ?></strong></label><br />
            <input type="text" id="cin_info_source" name="cin_info_source" value="<?php echo esc_attr( $info_source ); ?>" style="width:100%;" />
        </p>

        <p>
            <label for="cin_review_notes"><strong><?php esc_html_e( 'Admin Review Notes:', 'cuba-investment-core' ); ?></strong></label><br />
            <textarea id="cin_review_notes" name="cin_review_notes" rows="4" style="width:100%;"><?php echo esc_textarea( $review_notes ); ?></textarea>
            <span class="description"><?php esc_html_e( 'Private notes between review committee and business owner.', 'cuba-investment-core' ); ?></span>
        </p>
        <?php
    }

    public static function save_meta_boxes( $post_id, $post ) {
        if ( ! isset( $_POST['_cin_opportunity_nonce'] ) || ! NonceManager::verify( sanitize_text_field( wp_unslash( $_POST['_cin_opportunity_nonce'] ) ), NonceManager::ACTION_OPPORTUNITY ) ) {
            return;
        }

        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        // Save parameters
        if ( isset( $_POST['cin_company_name'] ) ) {
            update_post_meta( $post_id, '_cin_company_name', sanitize_text_field( wp_unslash( $_POST['cin_company_name'] ) ) );
        }

        if ( isset( $_POST['cin_capital_sought'] ) ) {
            update_post_meta( $post_id, '_cin_capital_sought', Sanitizer::amount( wp_unslash( $_POST['cin_capital_sought'] ) ) );
        }

        if ( isset( $_POST['cin_currency'] ) ) {
            update_post_meta( $post_id, '_cin_currency', Sanitizer::currency( wp_unslash( $_POST['cin_currency'] ) ) );
        }

        if ( isset( $_POST['cin_minimum_investment'] ) ) {
            update_post_meta( $post_id, '_cin_minimum_investment', Sanitizer::amount( wp_unslash( $_POST['cin_minimum_investment'] ) ) );
        }

        if ( isset( $_POST['cin_ownership_structure'] ) ) {
            update_post_meta( $post_id, '_cin_ownership_structure', Sanitizer::legal_structure( wp_unslash( $_POST['cin_ownership_structure'] ) ) );
        }

        if ( isset( $_POST['cin_partnership_type'] ) ) {
            update_post_meta( $post_id, '_cin_partnership_type', sanitize_text_field( wp_unslash( $_POST['cin_partnership_type'] ) ) );
        }

        if ( isset( $_POST['cin_operating_history'] ) ) {
            update_post_meta( $post_id, '_cin_operating_history', sanitize_text_field( wp_unslash( $_POST['cin_operating_history'] ) ) );
        }

        if ( isset( $_POST['cin_capital_purpose'] ) ) {
            update_post_meta( $post_id, '_cin_capital_purpose', sanitize_textarea_field( wp_unslash( $_POST['cin_capital_purpose'] ) ) );
        }

        if ( isset( $_POST['cin_highlights'] ) ) {
            $raw_lines = explode( "\n", sanitize_textarea_field( wp_unslash( $_POST['cin_highlights'] ) ) );
            update_post_meta( $post_id, '_cin_highlights', Sanitizer::highlights( $raw_lines ) );
        }

        if ( isset( $_POST['cin_review_status'] ) ) {
            update_post_meta( $post_id, '_cin_review_status', sanitize_key( wp_unslash( $_POST['cin_review_status'] ) ) );
        }

        $is_sample = isset( $_POST['cin_is_sample'] ) ? 1 : 0;
        update_post_meta( $post_id, '_cin_is_sample', $is_sample );

        if ( isset( $_POST['cin_info_source'] ) ) {
            update_post_meta( $post_id, '_cin_info_source', sanitize_text_field( wp_unslash( $_POST['cin_info_source'] ) ) );
        }

        if ( isset( $_POST['cin_review_notes'] ) && current_user_can( Constants::CAP_REVIEW_OPPORTUNITIES ) ) {
            update_post_meta( $post_id, '_cin_review_notes', sanitize_textarea_field( wp_unslash( $_POST['cin_review_notes'] ) ) );
        }
    }
}
