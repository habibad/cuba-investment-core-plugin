<?php
/**
 * Cuba Investment Core - Profile Management Service
 *
 * Handles Investor & Business Owner profile retrieval, server-side validation,
 * persistent updates, image uploads, dynamic completion calculations, and
 * real-data summary metric queries.
 *
 * @package CubaInvestment\Core
 */

namespace CubaInvestment\Core\Services;

use CubaInvestment\Core\Common\Constants;
use CubaInvestment\Core\Common\Logger;
use CubaInvestment\Core\Security\Sanitizer;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class ProfileService {

    /**
     * Register a new user with role and initial profile
     *
     * @param array $data
     * @return int|\WP_Error User ID or WP_Error
     */
    public static function register_user( array $data ) {
        $email      = isset( $data['email'] ) ? sanitize_email( $data['email'] ) : '';
        $role       = isset( $data['role'] ) ? sanitize_key( $data['role'] ) : '';
        $password   = isset( $data['password'] ) ? (string) $data['password'] : '';
        $first_name = isset( $data['first_name'] ) ? sanitize_text_field( $data['first_name'] ) : '';
        $last_name  = isset( $data['last_name'] ) ? sanitize_text_field( $data['last_name'] ) : '';

        // Validation
        if ( empty( $email ) || ! is_email( $email ) ) {
            return new \WP_Error( 'invalid_email', __( 'A valid email address is required.', 'cuba-investment-core' ), [ 'status' => 400 ] );
        }

        if ( email_exists( $email ) ) {
            return new \WP_Error( 'email_exists', __( 'An account with this email address already exists.', 'cuba-investment-core' ), [ 'status' => 409 ] );
        }

        if ( strlen( $password ) < 8 ) {
            return new \WP_Error( 'weak_password', __( 'Password must be at least 8 characters long.', 'cuba-investment-core' ), [ 'status' => 400 ] );
        }

        // Map role
        $wp_role = Constants::ROLE_INVESTOR;
        if ( in_array( $role, [ 'entrepreneur', 'business_owner', Constants::ROLE_BUSINESS_OWNER ], true ) ) {
            $wp_role = Constants::ROLE_BUSINESS_OWNER;
        }

        // Generate username from email
        $username = sanitize_user( current( explode( '@', $email ) ), true );
        if ( empty( $username ) || username_exists( $username ) ) {
            $username = 'cin_' . wp_generate_password( 8, false );
        }

        $user_id = wp_create_user( $username, $password, $email );
        if ( is_wp_error( $user_id ) ) {
            Logger::error( 'User registration failed', [ 'email' => $email, 'error' => $user_id->get_error_message() ] );
            return $user_id;
        }

        // Assign Role
        $user = new \WP_User( $user_id );
        $user->set_role( $wp_role );

        // Update Name
        wp_update_user( [
            'ID'           => $user_id,
            'first_name'   => $first_name,
            'last_name'    => $last_name,
            'display_name' => trim( "{$first_name} {$last_name}" ) ?: $username,
        ] );

        // Set User Meta
        update_user_meta( $user_id, '_cin_user_type', $wp_role === Constants::ROLE_INVESTOR ? 'investor' : 'business_owner' );
        update_user_meta( $user_id, '_cin_verification_status', 'unverified' );
        update_user_meta( $user_id, '_cin_registered_at', current_time( 'mysql' ) );

        if ( ! empty( $data['investor_cert'] ) ) {
            update_user_meta( $user_id, '_cin_jurisdiction_acknowledged', 1 );
        }

        // Audit Log
        Logger::audit( 'user_registered', 'New platform user registered', [
            'user_id' => $user_id,
            'role'    => $wp_role,
        ] );

        return $user_id;
    }

    /**
     * Get complete Investor Profile data
     *
     * @param int $user_id
     * @return array
     */
    public static function get_investor_profile( $user_id ) {
        $user = get_userdata( $user_id );
        if ( ! $user ) {
            return [];
        }

        $first_name = get_user_meta( $user->ID, 'first_name', true ) ?: $user->first_name;
        $last_name  = get_user_meta( $user->ID, 'last_name', true ) ?: $user->last_name;

        $sectors = get_user_meta( $user->ID, '_cin_preferred_sectors', true );
        if ( ! is_array( $sectors ) ) {
            $sectors = ! empty( $sectors ) ? [ $sectors ] : [];
        }

        $locations = get_user_meta( $user->ID, '_cin_preferred_locations', true );
        if ( ! is_array( $locations ) ) {
            $locations = ! empty( $locations ) ? [ $locations ] : [];
        }

        $interests = get_user_meta( $user->ID, '_cin_investment_interests', true );
        if ( ! is_array( $interests ) ) {
            $interests = ! empty( $interests ) ? [ $interests ] : [];
        }

        $expertise = get_user_meta( $user->ID, '_cin_areas_of_expertise', true );
        if ( ! is_array( $expertise ) ) {
            $expertise = ! empty( $expertise ) ? [ $expertise ] : [];
        }

        $completion = self::calculate_investor_completion( $user_id );

        return [
            'id'                          => $user->ID,
            'email'                       => $user->user_email,
            'first_name'                  => $first_name,
            'last_name'                   => $last_name,
            'display_name'                => $user->display_name ?: trim( "{$first_name} {$last_name}" ),
            'country_of_residence'        => get_user_meta( $user->ID, '_cin_country_of_residence', true ) ?: ( get_user_meta( $user->ID, '_cin_country', true ) ?: 'International' ),
            'city_region'                 => get_user_meta( $user->ID, '_cin_city_region', true ) ?: '',
            'avatar_url'                  => get_user_meta( $user->ID, '_cin_avatar_url', true ) ?: '',
            'bio'                         => get_user_meta( $user->ID, '_cin_bio', true ) ?: '',
            // Investment Preferences
            'preferred_sectors'           => $sectors,
            'capital_range'               => get_user_meta( $user->ID, '_cin_capital_range', true ) ?: '',
            'preferred_locations'         => $locations,
            'investment_interests'        => $interests,
            'areas_of_expertise'          => $expertise,
            'collaboration_type'          => get_user_meta( $user->ID, '_cin_collaboration_type', true ) ?: 'strategic_partner',
            // Privacy & Visibility
            'profile_visibility'          => get_user_meta( $user->ID, '_cin_profile_visibility', true ) ?: 'registered_only',
            'show_email_to_connections'   => (bool) get_user_meta( $user->ID, '_cin_show_email_to_connections', true ),
            'show_phone_to_connections'   => (bool) get_user_meta( $user->ID, '_cin_show_phone_to_connections', true ),
            // Account Information
            'account_type'                => 'investor',
            'account_status'              => get_user_meta( $user->ID, '_cin_account_status', true ) ?: 'active',
            'membership_tier'             => get_user_meta( $user->ID, '_cin_membership_tier', true ) ?: Constants::TIER_LAUNCH,
            'membership_status'           => get_user_meta( $user->ID, '_cin_membership_status', true ) ?: 'active',
            'email_verified'              => (bool) get_user_meta( $user->ID, '_cin_email_verified', true ),
            'registered_at'               => get_user_meta( $user->ID, '_cin_registered_at', true ) ?: $user->user_registered,
            'completion'                  => $completion,
        ];
    }

    /**
     * Update Investor Profile data
     *
     * @param int $user_id
     * @param array $data
     * @param array $files
     * @return bool|\WP_Error
     */
    public static function update_investor_profile( $user_id, array $data, array $files = [] ) {
        $user = get_userdata( $user_id );
        if ( ! $user ) {
            return new \WP_Error( 'not_found', __( 'User not found.', 'cuba-investment-core' ) );
        }

        // 1. Validate Required Fields
        $first_name = isset( $data['first_name'] ) ? sanitize_text_field( $data['first_name'] ) : '';
        $last_name  = isset( $data['last_name'] ) ? sanitize_text_field( $data['last_name'] ) : '';

        if ( empty( $first_name ) || empty( $last_name ) ) {
            return new \WP_Error( 'missing_name', __( 'First name and last name are required.', 'cuba-investment-core' ) );
        }

        // Update WordPress user core attributes
        wp_update_user( [
            'ID'           => $user_id,
            'first_name'   => $first_name,
            'last_name'    => $last_name,
            'display_name' => trim( "{$first_name} {$last_name}" ),
        ] );

        // 2. Personal Information Meta
        if ( isset( $data['country_of_residence'] ) ) {
            $country = sanitize_text_field( $data['country_of_residence'] );
            update_user_meta( $user_id, '_cin_country_of_residence', $country );
            update_user_meta( $user_id, '_cin_country', $country );
        }

        if ( isset( $data['city_region'] ) ) {
            update_user_meta( $user_id, '_cin_city_region', sanitize_text_field( $data['city_region'] ) );
        }

        if ( isset( $data['bio'] ) ) {
            update_user_meta( $user_id, '_cin_bio', sanitize_textarea_field( $data['bio'] ) );
        }

        // 3. Investment Preferences Meta
        if ( isset( $data['preferred_sectors'] ) && is_array( $data['preferred_sectors'] ) ) {
            $clean_sectors = array_map( 'sanitize_key', $data['preferred_sectors'] );
            update_user_meta( $user_id, '_cin_preferred_sectors', array_values( array_unique( $clean_sectors ) ) );
        } else {
            update_user_meta( $user_id, '_cin_preferred_sectors', [] );
        }

        if ( isset( $data['capital_range'] ) ) {
            update_user_meta( $user_id, '_cin_capital_range', sanitize_text_field( $data['capital_range'] ) );
        }

        if ( isset( $data['preferred_locations'] ) && is_array( $data['preferred_locations'] ) ) {
            $clean_locs = array_map( 'sanitize_text_field', $data['preferred_locations'] );
            update_user_meta( $user_id, '_cin_preferred_locations', array_values( array_unique( $clean_locs ) ) );
        } else {
            update_user_meta( $user_id, '_cin_preferred_locations', [] );
        }

        if ( isset( $data['investment_interests'] ) && is_array( $data['investment_interests'] ) ) {
            $clean_int = array_map( 'sanitize_key', $data['investment_interests'] );
            update_user_meta( $user_id, '_cin_investment_interests', array_values( array_unique( $clean_int ) ) );
        } else {
            update_user_meta( $user_id, '_cin_investment_interests', [] );
        }

        if ( isset( $data['areas_of_expertise'] ) && is_array( $data['areas_of_expertise'] ) ) {
            $clean_exp = array_map( 'sanitize_text_field', $data['areas_of_expertise'] );
            update_user_meta( $user_id, '_cin_areas_of_expertise', array_values( array_unique( $clean_exp ) ) );
        } else {
            update_user_meta( $user_id, '_cin_areas_of_expertise', [] );
        }

        if ( isset( $data['collaboration_type'] ) ) {
            update_user_meta( $user_id, '_cin_collaboration_type', sanitize_key( $data['collaboration_type'] ) );
        }

        // 4. Profile Visibility & Privacy
        if ( isset( $data['profile_visibility'] ) ) {
            $vis = sanitize_key( $data['profile_visibility'] );
            update_user_meta( $user_id, '_cin_profile_visibility', in_array( $vis, [ 'registered_only', 'connections_only', 'anonymous' ], true ) ? $vis : 'registered_only' );
        }

        update_user_meta( $user_id, '_cin_show_email_to_connections', ! empty( $data['show_email_to_connections'] ) ? 1 : 0 );
        update_user_meta( $user_id, '_cin_show_phone_to_connections', ! empty( $data['show_phone_to_connections'] ) ? 1 : 0 );

        // 5. Handle Avatar Photo Upload
        if ( ! empty( $files['avatar']['name'] ) ) {
            $upload_res = self::handle_file_upload( $files['avatar'], 'avatar', $user_id );
            if ( is_wp_error( $upload_res ) ) {
                return $upload_res;
            }
            if ( ! empty( $upload_res ) ) {
                update_user_meta( $user_id, '_cin_avatar_url', esc_url_raw( $upload_res ) );
            }
        }

        Logger::audit( 'investor_profile_updated', 'Investor updated profile information', [ 'user_id' => $user_id ] );

        return true;
    }

    /**
     * Get Business Owner Personal Profile data
     *
     * @param int $user_id
     * @return array
     */
    public static function get_business_owner_profile( $user_id ) {
        $user = get_userdata( $user_id );
        if ( ! $user ) {
            return [];
        }

        $first_name = get_user_meta( $user->ID, 'first_name', true ) ?: $user->first_name;
        $last_name  = get_user_meta( $user->ID, 'last_name', true ) ?: $user->last_name;
        $completion = self::calculate_business_owner_completion( $user_id );

        return [
            'id'                   => $user->ID,
            'email'                => $user->user_email,
            'first_name'           => $first_name,
            'last_name'            => $last_name,
            'display_name'         => $user->display_name ?: trim( "{$first_name} {$last_name}" ),
            'country_of_residence' => get_user_meta( $user->ID, '_cin_country_of_residence', true ) ?: ( get_user_meta( $user->ID, '_cin_country', true ) ?: 'Cuba' ),
            'phone_number'         => get_user_meta( $user->ID, '_cin_phone_number', true ) ?: get_user_meta( $user->ID, '_cin_company_phone', true ),
            'avatar_url'           => get_user_meta( $user->ID, '_cin_avatar_url', true ) ?: '',
            'founder_bio'          => get_user_meta( $user->ID, '_cin_founder_bio', true ) ?: '',
            // Account Information
            'account_type'         => 'business_owner',
            'account_status'       => get_user_meta( $user->ID, '_cin_account_status', true ) ?: 'active',
            'membership_tier'      => get_user_meta( $user->ID, '_cin_membership_tier', true ) ?: Constants::TIER_LAUNCH,
            'membership_status'    => get_user_meta( $user->ID, '_cin_membership_status', true ) ?: 'active',
            'email_verified'       => (bool) get_user_meta( $user->ID, '_cin_email_verified', true ),
            'registered_at'        => get_user_meta( $user->ID, '_cin_registered_at', true ) ?: $user->user_registered,
            'completion'           => $completion,
        ];
    }

    /**
     * Update Business Owner Personal Profile
     *
     * @param int $user_id
     * @param array $data
     * @param array $files
     * @return bool|\WP_Error
     */
    public static function update_business_owner_profile( $user_id, array $data, array $files = [] ) {
        $user = get_userdata( $user_id );
        if ( ! $user ) {
            return new \WP_Error( 'not_found', __( 'User not found.', 'cuba-investment-core' ) );
        }

        $first_name = isset( $data['first_name'] ) ? sanitize_text_field( $data['first_name'] ) : '';
        $last_name  = isset( $data['last_name'] ) ? sanitize_text_field( $data['last_name'] ) : '';

        if ( empty( $first_name ) || empty( $last_name ) ) {
            return new \WP_Error( 'missing_name', __( 'First name and last name are required.', 'cuba-investment-core' ) );
        }

        wp_update_user( [
            'ID'           => $user_id,
            'first_name'   => $first_name,
            'last_name'    => $last_name,
            'display_name' => trim( "{$first_name} {$last_name}" ),
        ] );

        if ( isset( $data['country_of_residence'] ) ) {
            $country = sanitize_text_field( $data['country_of_residence'] );
            update_user_meta( $user_id, '_cin_country_of_residence', $country );
            update_user_meta( $user_id, '_cin_country', $country );
        }

        if ( isset( $data['phone_number'] ) ) {
            $phone = Sanitizer::phone( $data['phone_number'] );
            update_user_meta( $user_id, '_cin_phone_number', $phone );
            update_user_meta( $user_id, '_cin_company_phone', $phone );
        }

        if ( isset( $data['founder_bio'] ) ) {
            update_user_meta( $user_id, '_cin_founder_bio', sanitize_textarea_field( $data['founder_bio'] ) );
        }

        // Avatar upload
        if ( ! empty( $files['avatar']['name'] ) ) {
            $upload_res = self::handle_file_upload( $files['avatar'], 'avatar', $user_id );
            if ( is_wp_error( $upload_res ) ) {
                return $upload_res;
            }
            if ( ! empty( $upload_res ) ) {
                update_user_meta( $user_id, '_cin_avatar_url', esc_url_raw( $upload_res ) );
            }
        }

        Logger::audit( 'business_owner_personal_profile_updated', 'Business owner updated personal profile', [ 'user_id' => $user_id ] );

        return true;
    }

    /**
     * Get Business Profile data for Business Owner
     *
     * @param int $user_id
     * @return array
     */
    public static function get_business_profile( $user_id ) {
        $user = get_userdata( $user_id );
        if ( ! $user ) {
            return [];
        }

        $completion = self::calculate_business_owner_completion( $user_id );

        return [
            'id'                          => $user->ID,
            // Section B: Business Information
            'company_name'                => get_user_meta( $user->ID, '_cin_company_name', true ) ?: ( get_user_meta( $user->ID, '_cin_business_name', true ) ?: '' ),
            'company_sector'              => get_user_meta( $user->ID, '_cin_company_sector', true ) ?: 'hospitality',
            'company_country'             => get_user_meta( $user->ID, '_cin_company_country', true ) ?: 'Cuba',
            'company_location_province'   => get_user_meta( $user->ID, '_cin_company_location_province', true ) ?: ( get_user_meta( $user->ID, '_cin_business_location', true ) ?: 'La Habana' ),
            'company_website'             => get_user_meta( $user->ID, '_cin_company_website', true ) ?: '',
            'company_description'         => get_user_meta( $user->ID, '_cin_company_description', true ) ?: '',
            'company_products_services'   => get_user_meta( $user->ID, '_cin_company_products_services', true ) ?: '',
            'company_year_established'    => (int) get_user_meta( $user->ID, '_cin_company_year_established', true ) ?: '',
            'company_stage'               => get_user_meta( $user->ID, '_cin_company_stage', true ) ?: 'growth',
            'company_legal_type'          => get_user_meta( $user->ID, '_cin_company_legal_type', true ) ?: 'mipyme_private',
            'company_market_description'  => get_user_meta( $user->ID, '_cin_company_market_description', true ) ?: '',
            'company_logo_url'            => get_user_meta( $user->ID, '_cin_company_logo_url', true ) ?: '',
            // Section C: Partnership Interests
            'seeking_investment'          => (bool) get_user_meta( $user->ID, '_cin_seeking_investment', true ),
            'seeking_partners'            => (bool) get_user_meta( $user->ID, '_cin_seeking_partners', true ),
            'seeking_expertise'           => (bool) get_user_meta( $user->ID, '_cin_seeking_expertise', true ),
            'collaboration_interests'     => get_user_meta( $user->ID, '_cin_collaboration_interests', true ) ?: '',
            // Section D: Profile Visibility
            'business_visibility'         => get_user_meta( $user->ID, '_cin_business_visibility', true ) ?: 'verified_investors',
            'show_phone_privacy'          => get_user_meta( $user->ID, '_cin_show_phone_privacy', true ) ?: 'connections_only',
            'show_financials_privacy'     => get_user_meta( $user->ID, '_cin_show_financials_privacy', true ) ?: 'verified_investors',
            // Account Information
            'account_type'                => 'business_owner',
            'account_status'              => get_user_meta( $user->ID, '_cin_account_status', true ) ?: 'active',
            'membership_tier'             => get_user_meta( $user->ID, '_cin_membership_tier', true ) ?: Constants::TIER_LAUNCH,
            'email_verified'              => (bool) get_user_meta( $user->ID, '_cin_email_verified', true ),
            'completion'                  => $completion,
        ];
    }

    /**
     * Update Business Profile data for Business Owner
     *
     * @param int $user_id
     * @param array $data
     * @param array $files
     * @return bool|\WP_Error
     */
    public static function update_business_profile( $user_id, array $data, array $files = [] ) {
        $user = get_userdata( $user_id );
        if ( ! $user ) {
            return new \WP_Error( 'not_found', __( 'User not found.', 'cuba-investment-core' ) );
        }

        // Validate Business Name
        $company_name = isset( $data['company_name'] ) ? sanitize_text_field( $data['company_name'] ) : '';
        if ( empty( $company_name ) ) {
            return new \WP_Error( 'missing_company_name', __( 'Business name is required.', 'cuba-investment-core' ) );
        }

        update_user_meta( $user_id, '_cin_company_name', $company_name );
        update_user_meta( $user_id, '_cin_business_name', $company_name );

        if ( isset( $data['company_sector'] ) ) {
            update_user_meta( $user_id, '_cin_company_sector', sanitize_key( $data['company_sector'] ) );
        }

        if ( isset( $data['company_country'] ) ) {
            update_user_meta( $user_id, '_cin_company_country', sanitize_text_field( $data['company_country'] ) );
        }

        if ( isset( $data['company_location_province'] ) ) {
            $province = Sanitizer::province( $data['company_location_province'] );
            update_user_meta( $user_id, '_cin_company_location_province', $province );
            update_user_meta( $user_id, '_cin_business_location', $province );
        }

        if ( isset( $data['company_website'] ) ) {
            update_user_meta( $user_id, '_cin_company_website', esc_url_raw( $data['company_website'] ) );
        }

        if ( isset( $data['company_description'] ) ) {
            update_user_meta( $user_id, '_cin_company_description', sanitize_textarea_field( $data['company_description'] ) );
        }

        if ( isset( $data['company_products_services'] ) ) {
            update_user_meta( $user_id, '_cin_company_products_services', sanitize_textarea_field( $data['company_products_services'] ) );
        }

        if ( isset( $data['company_year_established'] ) ) {
            update_user_meta( $user_id, '_cin_company_year_established', absint( $data['company_year_established'] ) );
        }

        if ( isset( $data['company_stage'] ) ) {
            $stage = sanitize_key( $data['company_stage'] );
            update_user_meta( $user_id, '_cin_company_stage', in_array( $stage, [ 'idea', 'pre_revenue', 'early_stage', 'growth', 'established' ], true ) ? $stage : 'growth' );
        }

        if ( isset( $data['company_legal_type'] ) ) {
            update_user_meta( $user_id, '_cin_company_legal_type', Sanitizer::legal_structure( $data['company_legal_type'] ) );
        }

        if ( isset( $data['company_market_description'] ) ) {
            update_user_meta( $user_id, '_cin_company_market_description', sanitize_textarea_field( $data['company_market_description'] ) );
        }

        // Section C: Partnership Interests
        update_user_meta( $user_id, '_cin_seeking_investment', ! empty( $data['seeking_investment'] ) ? 1 : 0 );
        update_user_meta( $user_id, '_cin_seeking_partners', ! empty( $data['seeking_partners'] ) ? 1 : 0 );
        update_user_meta( $user_id, '_cin_seeking_expertise', ! empty( $data['seeking_expertise'] ) ? 1 : 0 );

        if ( isset( $data['collaboration_interests'] ) ) {
            update_user_meta( $user_id, '_cin_collaboration_interests', sanitize_textarea_field( $data['collaboration_interests'] ) );
        }

        // Section D: Profile Visibility
        if ( isset( $data['business_visibility'] ) ) {
            $b_vis = sanitize_key( $data['business_visibility'] );
            update_user_meta( $user_id, '_cin_business_visibility', in_array( $b_vis, [ 'members_only', 'verified_investors', 'public' ], true ) ? $b_vis : 'verified_investors' );
        }

        if ( isset( $data['show_phone_privacy'] ) ) {
            $p_vis = sanitize_key( $data['show_phone_privacy'] );
            update_user_meta( $user_id, '_cin_show_phone_privacy', in_array( $p_vis, [ 'connections_only', 'verified_investors', 'hidden' ], true ) ? $p_vis : 'connections_only' );
        }

        if ( isset( $data['show_financials_privacy'] ) ) {
            $f_vis = sanitize_key( $data['show_financials_privacy'] );
            update_user_meta( $user_id, '_cin_show_financials_privacy', in_array( $f_vis, [ 'verified_investors', 'connections_only' ], true ) ? $f_vis : 'verified_investors' );
        }

        // Logo Upload
        if ( ! empty( $files['company_logo']['name'] ) ) {
            $upload_res = self::handle_file_upload( $files['company_logo'], 'logo', $user_id );
            if ( is_wp_error( $upload_res ) ) {
                return $upload_res;
            }
            if ( ! empty( $upload_res ) ) {
                update_user_meta( $user_id, '_cin_company_logo_url', esc_url_raw( $upload_res ) );
            }
        }

        Logger::audit( 'business_profile_updated', 'Business owner updated business profile', [ 'user_id' => $user_id ] );

        return true;
    }

    /**
     * Calculate dynamic Investor profile completion percentage
     *
     * @param int $user_id
     * @return array
     */
    public static function calculate_investor_completion( $user_id ) {
        $user = get_userdata( $user_id );
        if ( ! $user ) {
            return [ 'percentage' => 0, 'completed' => [], 'missing' => [], 'is_complete' => false ];
        }

        $first_name = get_user_meta( $user_id, 'first_name', true ) ?: $user->first_name;
        $last_name  = get_user_meta( $user_id, 'last_name', true ) ?: $user->last_name;
        $country    = get_user_meta( $user_id, '_cin_country_of_residence', true ) ?: get_user_meta( $user_id, '_cin_country', true );
        $bio        = get_user_meta( $user_id, '_cin_bio', true );
        $sectors    = (array) get_user_meta( $user_id, '_cin_preferred_sectors', true );
        $sectors    = array_filter( $sectors );
        $range      = get_user_meta( $user_id, '_cin_capital_range', true ) ?: get_user_meta( $user_id, '_cin_investment_range_max', true );
        $collab     = get_user_meta( $user_id, '_cin_collaboration_type', true ) ?: get_user_meta( $user_id, '_cin_areas_of_expertise', true );
        $avatar     = get_user_meta( $user_id, '_cin_avatar_url', true );

        $fields = [
            'name' => [
                'label'  => __( 'First & Last Name', 'cuba-investment-core' ),
                'weight' => 15,
                'done'   => ( ! empty( $first_name ) && ! empty( $last_name ) ),
                'url'    => home_url( '/investor/profile/#section-personal' ),
            ],
            'country' => [
                'label'  => __( 'Country of Residence', 'cuba-investment-core' ),
                'weight' => 15,
                'done'   => ! empty( $country ),
                'url'    => home_url( '/investor/profile/#section-personal' ),
            ],
            'bio' => [
                'label'  => __( 'Professional Bio', 'cuba-investment-core' ),
                'weight' => 15,
                'done'   => ! empty( $bio ),
                'url'    => home_url( '/investor/profile/#section-personal' ),
            ],
            'sectors' => [
                'label'  => __( 'Preferred Business Sectors', 'cuba-investment-core' ),
                'weight' => 20,
                'done'   => ! empty( $sectors ),
                'url'    => home_url( '/investor/profile/#section-preferences' ),
            ],
            'range' => [
                'label'  => __( 'Capital / Investment Range', 'cuba-investment-core' ),
                'weight' => 15,
                'done'   => ! empty( $range ),
                'url'    => home_url( '/investor/profile/#section-preferences' ),
            ],
            'collaboration' => [
                'label'  => __( 'Collaboration Type & Expertise', 'cuba-investment-core' ),
                'weight' => 10,
                'done'   => ! empty( $collab ),
                'url'    => home_url( '/investor/profile/#section-preferences' ),
            ],
            'avatar' => [
                'label'  => __( 'Profile Photo', 'cuba-investment-core' ),
                'weight' => 10,
                'done'   => ! empty( $avatar ),
                'url'    => home_url( '/investor/profile/#section-personal' ),
            ],
        ];

        $percentage = 0;
        $completed  = [];
        $missing    = [];

        foreach ( $fields as $key => $info ) {
            if ( $info['done'] ) {
                $percentage += $info['weight'];
                $completed[] = [ 'key' => $key, 'label' => $info['label'] ];
            } else {
                $missing[] = [ 'key' => $key, 'label' => $info['label'], 'url' => $info['url'] ];
            }
        }

        $percentage = min( 100, $percentage );

        return [
            'percentage'  => $percentage,
            'completed'   => $completed,
            'missing'     => $missing,
            'is_complete' => ( $percentage === 100 ),
        ];
    }

    /**
     * Calculate dynamic Business Owner profile completion percentage
     *
     * @param int $user_id
     * @return array
     */
    public static function calculate_business_owner_completion( $user_id ) {
        $user = get_userdata( $user_id );
        if ( ! $user ) {
            return [ 'percentage' => 0, 'completed' => [], 'missing' => [], 'is_complete' => false ];
        }

        $first_name = get_user_meta( $user_id, 'first_name', true ) ?: $user->first_name;
        $last_name  = get_user_meta( $user_id, 'last_name', true ) ?: $user->last_name;
        $phone      = get_user_meta( $user_id, '_cin_phone_number', true ) ?: get_user_meta( $user_id, '_cin_company_phone', true );
        $comp_name  = get_user_meta( $user_id, '_cin_company_name', true ) ?: get_user_meta( $user_id, '_cin_business_name', true );
        $sector     = get_user_meta( $user_id, '_cin_company_sector', true );
        $province   = get_user_meta( $user_id, '_cin_company_location_province', true );
        $desc       = get_user_meta( $user_id, '_cin_company_description', true );
        $products   = get_user_meta( $user_id, '_cin_company_products_services', true );
        $stage      = get_user_meta( $user_id, '_cin_company_stage', true );
        $legal      = get_user_meta( $user_id, '_cin_company_legal_type', true );
        $seeking_inv= get_user_meta( $user_id, '_cin_seeking_investment', true );
        $seeking_prt= get_user_meta( $user_id, '_cin_seeking_partners', true );
        $seeking_exp= get_user_meta( $user_id, '_cin_seeking_expertise', true );
        $has_partner= ( ! empty( $seeking_inv ) || ! empty( $seeking_prt ) || ! empty( $seeking_exp ) );
        $logo       = get_user_meta( $user_id, '_cin_company_logo_url', true ) ?: get_user_meta( $user_id, '_cin_avatar_url', true );

        $fields = [
            'personal_info' => [
                'label'  => __( 'Personal Name & Contact', 'cuba-investment-core' ),
                'weight' => 15,
                'done'   => ( ! empty( $first_name ) && ! empty( $last_name ) && ! empty( $phone ) ),
                'url'    => home_url( '/business-owner/profile/' ),
            ],
            'business_name' => [
                'label'  => __( 'Business Name', 'cuba-investment-core' ),
                'weight' => 15,
                'done'   => ! empty( $comp_name ),
                'url'    => home_url( '/business-owner/business-profile/#field-company-name' ),
            ],
            'business_sector' => [
                'label'  => __( 'Industry Sector & Location', 'cuba-investment-core' ),
                'weight' => 15,
                'done'   => ( ! empty( $sector ) && ! empty( $province ) ),
                'url'    => home_url( '/business-owner/business-profile/#field-sector' ),
            ],
            'description' => [
                'label'  => __( 'Business Description', 'cuba-investment-core' ),
                'weight' => 15,
                'done'   => ! empty( $desc ),
                'url'    => home_url( '/business-owner/business-profile/#field-description' ),
            ],
            'products' => [
                'label'  => __( 'Products or Services', 'cuba-investment-core' ),
                'weight' => 10,
                'done'   => ! empty( $products ),
                'url'    => home_url( '/business-owner/business-profile/#field-products' ),
            ],
            'stage_legal' => [
                'label'  => __( 'Business Stage & Legal Structure', 'cuba-investment-core' ),
                'weight' => 10,
                'done'   => ( ! empty( $stage ) && ! empty( $legal ) ),
                'url'    => home_url( '/business-owner/business-profile/#field-stage' ),
            ],
            'partnership' => [
                'label'  => __( 'Partnership & Collaboration Interests', 'cuba-investment-core' ),
                'weight' => 10,
                'done'   => $has_partner,
                'url'    => home_url( '/business-owner/business-profile/#section-partnership' ),
            ],
            'logo' => [
                'label'  => __( 'Company Logo / Branding', 'cuba-investment-core' ),
                'weight' => 10,
                'done'   => ! empty( $logo ),
                'url'    => home_url( '/business-owner/business-profile/#field-logo' ),
            ],
        ];

        $percentage = 0;
        $completed  = [];
        $missing    = [];

        foreach ( $fields as $key => $info ) {
            if ( $info['done'] ) {
                $percentage += $info['weight'];
                $completed[] = [ 'key' => $key, 'label' => $info['label'] ];
            } else {
                $missing[] = [ 'key' => $key, 'label' => $info['label'], 'url' => $info['url'] ];
            }
        }

        $percentage = min( 100, $percentage );

        return [
            'percentage'  => $percentage,
            'completed'   => $completed,
            'missing'     => $missing,
            'is_complete' => ( $percentage === 100 ),
        ];
    }

    /**
     * Get real summary metrics for Investor Dashboard
     *
     * @param int $user_id
     * @return array
     */
    public static function get_investor_metrics( $user_id ) {
        global $wpdb;

        // 1. Available Opportunities (Real published listings)
        $opp_counts = wp_count_posts( Constants::POST_TYPE_OPPORTUNITY );
        $available  = isset( $opp_counts->publish ) ? (int) $opp_counts->publish : 0;

        // 2. Saved Opportunities
        $saved = (array) get_user_meta( $user_id, '_cin_saved_opportunities', true );
        $saved_count = count( array_filter( $saved ) );

        // 3. Enquiries Sent
        $t_inquiries = Constants::get_table_name( Constants::TABLE_INQUIRIES );
        $enquiries   = (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$t_inquiries} WHERE investor_user_id = %d",
            $user_id
        ) );

        // 4. Active Connections
        $t_connections = Constants::get_table_name( Constants::TABLE_CONNECTIONS );
        $connections   = (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$t_connections} WHERE investor_user_id = %d AND status = %s",
            $user_id,
            Constants::CONNECTION_ACTIVE
        ) );

        return [
            'available_opportunities' => $available,
            'saved_opportunities'     => $saved_count,
            'enquiries_sent'          => $enquiries,
            'active_connections'      => $connections,
        ];
    }

    /**
     * Get real summary metrics for Business Owner Dashboard
     *
     * @param int $user_id
     * @return array
     */
    public static function get_business_metrics( $user_id ) {
        global $wpdb;

        // 1. My Opportunities (Total authored by this user)
        $my_opps = (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_author = %d AND post_type = %s AND post_status NOT IN ('trash', 'auto-draft')",
            $user_id,
            Constants::POST_TYPE_OPPORTUNITY
        ) );

        // 2. Published Listings
        $published = (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_author = %d AND post_type = %s AND post_status = 'publish'",
            $user_id,
            Constants::POST_TYPE_OPPORTUNITY
        ) );

        // 3. Investor Enquiries
        $t_inquiries = Constants::get_table_name( Constants::TABLE_INQUIRIES );
        $enquiries   = (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$t_inquiries} WHERE business_user_id = %d",
            $user_id
        ) );

        // 4. Active Connections
        $t_connections = Constants::get_table_name( Constants::TABLE_CONNECTIONS );
        $connections   = (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$t_connections} WHERE business_user_id = %d AND status = %s",
            $user_id,
            Constants::CONNECTION_ACTIVE
        ) );

        return [
            'my_opportunities'   => $my_opps,
            'published_listings' => $published,
            'investor_enquiries' => $enquiries,
            'active_connections' => $connections,
        ];
    }

    /**
     * Get real recent account activity from audit logs
     *
     * @param int $user_id
     * @param int $limit
     * @return array
     */
    public static function get_recent_activity( $user_id, $limit = 5 ) {
        global $wpdb;

        $t_audit = Constants::get_table_name( Constants::TABLE_AUDIT_LOGS );
        $results = $wpdb->get_results( $wpdb->prepare(
            "SELECT action, message, created_at FROM {$t_audit} WHERE user_id = %d ORDER BY created_at DESC LIMIT %d",
            $user_id,
            $limit
        ), ARRAY_A );

        if ( empty( $results ) ) {
            return [];
        }

        $activities = [];
        foreach ( $results as $row ) {
            $activities[] = [
                'action'     => $row['action'],
                'message'    => $row['message'],
                'created_at' => $row['created_at'],
                'time_diff'  => human_time_diff( strtotime( $row['created_at'] ), current_time( 'timestamp' ) ) . ' ' . __( 'ago', 'cuba-investment-core' ),
            ];
        }

        return $activities;
    }

    /**
     * Secure file upload handler for profile photos and company logos
     *
     * @param array  $file
     * @param string $type
     * @param int    $user_id
     * @return string|\WP_Error URL on success, WP_Error on failure
     */
    public static function handle_file_upload( array $file, string $type = 'avatar', int $user_id = 0 ) {
        if ( empty( $file['name'] ) || empty( $file['tmp_name'] ) ) {
            return '';
        }

        if ( isset( $file['error'] ) && $file['error'] !== UPLOAD_ERR_OK ) {
            return new \WP_Error( 'upload_error', __( 'File upload failed. Please try again.', 'cuba-investment-core' ) );
        }

        // 2MB max size
        if ( $file['size'] > 2097152 ) {
            return new \WP_Error( 'file_too_large', __( 'Image file size must not exceed 2MB.', 'cuba-investment-core' ) );
        }

        $allowed_mimes = [
            'jpg'  => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png'  => 'image/png',
            'webp' => 'image/webp',
        ];

        $file_info = wp_check_filetype( $file['name'], $allowed_mimes );
        if ( empty( $file_info['ext'] ) || empty( $file_info['type'] ) ) {
            return new \WP_Error( 'invalid_mime', __( 'Only JPG, PNG, and WebP images are allowed.', 'cuba-investment-core' ) );
        }

        if ( function_exists( 'mime_content_type' ) ) {
            $real_mime = mime_content_type( $file['tmp_name'] );
            if ( ! in_array( $real_mime, [ 'image/jpeg', 'image/png', 'image/webp' ], true ) ) {
                return new \WP_Error( 'invalid_mime_content', __( 'The uploaded file is not a valid image.', 'cuba-investment-core' ) );
            }
        }

        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';

        $upload_overrides = [
            'test_form' => false,
            'mimes'     => $allowed_mimes,
        ];

        $moved_file = wp_handle_upload( $file, $upload_overrides );

        if ( isset( $moved_file['error'] ) ) {
            return new \WP_Error( 'upload_move_failed', $moved_file['error'] );
        }

        return $moved_file['url'];
    }
}
