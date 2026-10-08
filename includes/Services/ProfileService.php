<?php
/**
 * Cuba Investment Core - Profile Management Service
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
        $email     = isset( $data['email'] ) ? sanitize_email( $data['email'] ) : '';
        $role      = isset( $data['role'] ) ? sanitize_key( $data['role'] ) : '';
        $password  = isset( $data['password'] ) ? (string) $data['password'] : '';
        $first_name= isset( $data['first_name'] ) ? sanitize_text_field( $data['first_name'] ) : '';
        $last_name = isset( $data['last_name'] ) ? sanitize_text_field( $data['last_name'] ) : '';

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
     * Get profile data for a user
     *
     * @param int $user_id
     * @return array
     */
    public static function get_profile( $user_id ) {
        $user = get_userdata( $user_id );
        if ( ! $user ) {
            return [];
        }

        $is_investor = in_array( Constants::ROLE_INVESTOR, (array) $user->roles, true );
        $is_business = in_array( Constants::ROLE_BUSINESS_OWNER, (array) $user->roles, true );

        $profile = [
            'id'                  => $user->ID,
            'email'               => $user->user_email,
            'first_name'          => $user->first_name,
            'last_name'           => $user->last_name,
            'display_name'        => $user->display_name,
            'role'                => $is_investor ? 'investor' : ( $is_business ? 'business_owner' : 'admin' ),
            'verification_status' => get_user_meta( $user->ID, '_cin_verification_status', true ) ?: 'unverified',
            'registered_at'       => get_user_meta( $user->ID, '_cin_registered_at', true ) ?: $user->user_registered,
        ];

        if ( $is_investor ) {
            $profile['investor_type']            = get_user_meta( $user->ID, '_cin_investor_type', true ) ?: 'angel';
            $profile['investment_range_min']     = (float) get_user_meta( $user->ID, '_cin_investment_range_min', true );
            $profile['investment_range_max']     = (float) get_user_meta( $user->ID, '_cin_investment_range_max', true );
            $profile['preferred_sectors']        = (array) get_user_meta( $user->ID, '_cin_preferred_sectors', true );
            $profile['jurisdiction_country']     = get_user_meta( $user->ID, '_cin_jurisdiction_country', true ) ?: '';
            $profile['jurisdiction_acknowledged']= (bool) get_user_meta( $user->ID, '_cin_jurisdiction_acknowledged', true );
            $profile['bio']                      = get_user_meta( $user->ID, '_cin_bio', true ) ?: '';
            $profile['linkedin_url']             = get_user_meta( $user->ID, '_cin_linkedin_url', true ) ?: '';
        } elseif ( $is_business ) {
            $profile['company_name']             = get_user_meta( $user->ID, '_cin_company_name', true ) ?: '';
            $profile['company_legal_type']       = get_user_meta( $user->ID, '_cin_company_legal_type', true ) ?: 'mipyme_private';
            $profile['company_legal_label']      = Sanitizer::legal_structure_label( $profile['company_legal_type'] );
            $profile['company_province']         = get_user_meta( $user->ID, '_cin_company_location_province', true ) ?: 'La Habana';
            $profile['company_phone']            = get_user_meta( $user->ID, '_cin_company_phone', true ) ?: '';
            $profile['company_whatsapp']         = get_user_meta( $user->ID, '_cin_company_whatsapp', true ) ?: '';
            $profile['company_website']          = get_user_meta( $user->ID, '_cin_company_website', true ) ?: '';
            $profile['company_year_established'] = get_user_meta( $user->ID, '_cin_company_year_established', true ) ?: '';
        }

        return $profile;
    }

    /**
     * Update user profile data
     *
     * @param int $user_id
     * @param array $data
     * @return bool|\WP_Error
     */
    public static function update_profile( $user_id, array $data ) {
        $user = get_userdata( $user_id );
        if ( ! $user ) {
            return new \WP_Error( 'not_found', __( 'User not found.', 'cuba-investment-core' ), [ 'status' => 404 ] );
        }

        $wp_updates = [ 'ID' => $user_id ];
        if ( isset( $data['first_name'] ) ) {
            $wp_updates['first_name'] = sanitize_text_field( $data['first_name'] );
        }
        if ( isset( $data['last_name'] ) ) {
            $wp_updates['last_name'] = sanitize_text_field( $data['last_name'] );
        }
        if ( isset( $data['display_name'] ) ) {
            $wp_updates['display_name'] = sanitize_text_field( $data['display_name'] );
        }
        wp_update_user( $wp_updates );

        // Role-specific fields
        if ( in_array( Constants::ROLE_INVESTOR, (array) $user->roles, true ) ) {
            if ( isset( $data['investor_type'] ) ) {
                update_user_meta( $user_id, '_cin_investor_type', sanitize_key( $data['investor_type'] ) );
            }
            if ( isset( $data['investment_range_min'] ) ) {
                update_user_meta( $user_id, '_cin_investment_range_min', Sanitizer::amount( $data['investment_range_min'] ) );
            }
            if ( isset( $data['investment_range_max'] ) ) {
                update_user_meta( $user_id, '_cin_investment_range_max', Sanitizer::amount( $data['investment_range_max'] ) );
            }
            if ( isset( $data['preferred_sectors'] ) && is_array( $data['preferred_sectors'] ) ) {
                update_user_meta( $user_id, '_cin_preferred_sectors', array_map( 'sanitize_key', $data['preferred_sectors'] ) );
            }
            if ( isset( $data['bio'] ) ) {
                update_user_meta( $user_id, '_cin_bio', sanitize_textarea_field( $data['bio'] ) );
            }
            if ( isset( $data['linkedin_url'] ) ) {
                update_user_meta( $user_id, '_cin_linkedin_url', esc_url_raw( $data['linkedin_url'] ) );
            }
        } elseif ( in_array( Constants::ROLE_BUSINESS_OWNER, (array) $user->roles, true ) ) {
            if ( isset( $data['company_name'] ) ) {
                update_user_meta( $user_id, '_cin_company_name', sanitize_text_field( $data['company_name'] ) );
            }
            if ( isset( $data['company_legal_type'] ) ) {
                update_user_meta( $user_id, '_cin_company_legal_type', Sanitizer::legal_structure( $data['company_legal_type'] ) );
            }
            if ( isset( $data['company_province'] ) ) {
                update_user_meta( $user_id, '_cin_company_location_province', Sanitizer::province( $data['company_province'] ) );
            }
            if ( isset( $data['company_phone'] ) ) {
                update_user_meta( $user_id, '_cin_company_phone', Sanitizer::phone( $data['company_phone'] ) );
            }
            if ( isset( $data['company_whatsapp'] ) ) {
                update_user_meta( $user_id, '_cin_company_whatsapp', Sanitizer::phone( $data['company_whatsapp'] ) );
            }
            if ( isset( $data['company_website'] ) ) {
                update_user_meta( $user_id, '_cin_company_website', esc_url_raw( $data['company_website'] ) );
            }
            if ( isset( $data['company_year_established'] ) ) {
                update_user_meta( $user_id, '_cin_company_year_established', absint( $data['company_year_established'] ) );
            }
        }

        Logger::audit( 'profile_updated', 'User updated profile', [ 'user_id' => $user_id ] );

        return true;
    }
}
