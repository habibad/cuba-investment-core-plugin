<?php
/**
 * Cuba Investment Core - Sanitization and Input Validation
 *
 * @package CubaInvestment\Core
 */

namespace CubaInvestment\Core\Security;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Sanitizer {

    /**
     * Allowed transaction currencies (PDF Page 35: USD/EUR neutral standard)
     *
     * @var array
     */
    protected static $allowed_currencies = [ 'USD', 'EUR', 'GBP', 'CAD', 'CUP' ];

    /**
     * Official Cuban Provinces and Special Municipality
     *
     * @var array
     */
    protected static $cuban_provinces = [
        'Pinar del Río',
        'Artemisa',
        'La Habana',
        'Mayabeque',
        'Matanzas',
        'Cienfuegos',
        'Villa Clara',
        'Sancti Spíritus',
        'Ciego de Ávila',
        'Camagüey',
        'Las Tunas',
        'Holguín',
        'Granma',
        'Santiago de Cuba',
        'Guantánamo',
        'Isla de la Juventud',
    ];

    /**
     * Recognized Cuban Private Enterprise Legal Structures
     *
     * @var array
     */
    protected static $legal_structures = [
        'mipyme_private' => 'Private Cuban Enterprise (MIPYME)',
        'mipyme_state'   => 'State-Private Joint Enterprise (MIPYME)',
        'cna'            => 'Non-Agricultural Cooperative (CNA)',
        'tcp'            => 'Self-Employed Activity (TCP)',
        'foreign_jv'     => 'International Joint Venture (Empresa Mixta)',
        'other'          => 'Other Registered Private Enterprise',
    ];

    /**
     * Sanitize currency code
     *
     * @param string $currency
     * @return string
     */
    public static function currency( $currency ) {
        $currency = strtoupper( sanitize_text_field( $currency ) );
        return in_array( $currency, self::$allowed_currencies, true ) ? $currency : 'USD';
    }

    /**
     * Sanitize financial monetary amount
     *
     * @param mixed $amount
     * @return float
     */
    public static function amount( $amount ) {
        if ( is_string( $amount ) ) {
            $amount = preg_replace( '/[^\d.]/', '', $amount );
        }
        $amount = (float) $amount;
        return max( 0.0, round( $amount, 2 ) );
    }

    /**
     * Validate and sanitize Cuban province name
     *
     * @param string $province
     * @return string
     */
    public static function province( $province ) {
        $province = sanitize_text_field( $province );
        foreach ( self::$cuban_provinces as $official ) {
            if ( 0 === strcasecmp( $official, $province ) ) {
                return $official;
            }
        }
        return 'La Habana';
    }

    /**
     * Validate legal structure key
     *
     * @param string $key
     * @return string
     */
    public static function legal_structure( $key ) {
        $key = sanitize_key( $key );
        return isset( self::$legal_structures[ $key ] ) ? $key : 'mipyme_private';
    }

    /**
     * Get legal structure human label
     *
     * @param string $key
     * @return string
     */
    public static function legal_structure_label( $key ) {
        $key = self::legal_structure( $key );
        return self::$legal_structures[ $key ];
    }

    /**
     * Sanitize phone / WhatsApp number
     *
     * @param string $phone
     * @return string
     */
    public static function phone( $phone ) {
        return preg_replace( '/[^\d+()\s-]/', '', sanitize_text_field( $phone ) );
    }

    /**
     * Sanitize highlights array
     *
     * @param array $highlights
     * @return array
     */
    public static function highlights( $highlights ) {
        if ( ! is_array( $highlights ) ) {
            return [];
        }

        $clean = [];
        foreach ( $highlights as $item ) {
            $text = sanitize_text_field( trim( (string) $item ) );
            if ( ! empty( $text ) ) {
                $clean[] = substr( $text, 0, 300 );
            }
        }

        return array_slice( $clean, 0, 10 );
    }

    /**
     * Get list of provinces
     *
     * @return array
     */
    public static function get_provinces() {
        return self::$cuban_provinces;
    }

    /**
     * Get list of legal structures
     *
     * @return array
     */
    public static function get_legal_structures() {
        return self::$legal_structures;
    }
}
