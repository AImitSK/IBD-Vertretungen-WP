<?php
/**
 * Country Mapper - Maps country names to ISO codes
 *
 * @package IBD_Vertretungen
 */

if (!defined('ABSPATH')) {
    exit;
}

class IBD_Country_Mapper {

    /**
     * Country name to ISO code mapping
     */
    private static $country_map = [
        // German names
        'Albanien' => 'AL',
        'Andorra' => 'AD',
        'Belgien' => 'BE',
        'Bosnien und Herzegowina' => 'BA',
        'Bosnien-Herzegowina' => 'BA',
        'Bulgarien' => 'BG',
        'Dänemark' => 'DK',
        'Deutschland' => 'DE',
        'Estland' => 'EE',
        'Finnland' => 'FI',
        'Frankreich' => 'FR',
        'Griechenland' => 'GR',
        'Großbritannien' => 'GB',
        'Irland' => 'IE',
        'Island' => 'IS',
        'Israel' => 'IL',
        'Italien' => 'IT',
        'Kasachstan' => 'KZ',
        'Kosovo' => 'XK',
        'Kroatien' => 'HR',
        'Lettland' => 'LV',
        'Liechtenstein' => 'LI',
        'Litauen' => 'LT',
        'Luxemburg' => 'LU',
        'Malta' => 'MT',
        'Mazedonien' => 'MK',
        'Nordmazedonien' => 'MK',
        'Moldawien' => 'MD',
        'Moldau' => 'MD',
        'Monaco' => 'MC',
        'Montenegro' => 'ME',
        'Niederlande' => 'NL',
        'Norwegen' => 'NO',
        'Österreich' => 'AT',
        'Polen' => 'PL',
        'Portugal' => 'PT',
        'Rumänien' => 'RO',
        'Russland' => 'RU',
        'San Marino' => 'SM',
        'Schweden' => 'SE',
        'Schweiz' => 'CH',
        'Serbien' => 'RS',
        'Slowakei' => 'SK',
        'Slowenien' => 'SI',
        'Spanien' => 'ES',
        'Tschechien' => 'CZ',
        'Tschechische Republik' => 'CZ',
        'Türkei' => 'TR',
        'Ukraine' => 'UA',
        'Ungarn' => 'HU',
        'Vatikanstadt' => 'VA',
        'Weißrussland' => 'BY',
        'Belarus' => 'BY',
        'Zypern' => 'CY',

        // English names
        'Albania' => 'AL',
        'Austria' => 'AT',
        'Belgium' => 'BE',
        'Bosnia and Herzegovina' => 'BA',
        'Bulgaria' => 'BG',
        'Croatia' => 'HR',
        'Czech Republic' => 'CZ',
        'Czechia' => 'CZ',
        'Denmark' => 'DK',
        'Estonia' => 'EE',
        'Finland' => 'FI',
        'France' => 'FR',
        'Germany' => 'DE',
        'Greece' => 'GR',
        'Hungary' => 'HU',
        'Iceland' => 'IS',
        'Ireland' => 'IE',
        'Italy' => 'IT',
        'Kazakhstan' => 'KZ',
        'Latvia' => 'LV',
        'Lithuania' => 'LT',
        'Luxembourg' => 'LU',
        'Macedonia' => 'MK',
        'North Macedonia' => 'MK',
        'Moldova' => 'MD',
        'Netherlands' => 'NL',
        'Norway' => 'NO',
        'Poland' => 'PL',
        'Romania' => 'RO',
        'Russia' => 'RU',
        'Serbia' => 'RS',
        'Slovakia' => 'SK',
        'Slovenia' => 'SI',
        'Spain' => 'ES',
        'Sweden' => 'SE',
        'Switzerland' => 'CH',
        'Turkey' => 'TR',
        'United Kingdom' => 'GB',
        'UK' => 'GB',
    ];

    /**
     * Get ISO code for country name
     *
     * @param string $country_name Country name
     * @return string|null ISO code or null if not found
     */
    public static function get_iso_code($country_name) {
        $country_name = trim($country_name);

        // Direct match
        if (isset(self::$country_map[$country_name])) {
            return self::$country_map[$country_name];
        }

        // Case-insensitive search
        foreach (self::$country_map as $name => $code) {
            if (strcasecmp($name, $country_name) === 0) {
                return $code;
            }
        }

        // Partial match (for variations)
        $country_lower = strtolower($country_name);
        foreach (self::$country_map as $name => $code) {
            if (strpos(strtolower($name), $country_lower) !== false ||
                strpos($country_lower, strtolower($name)) !== false) {
                return $code;
            }
        }

        return null;
    }

    /**
     * Get country name for ISO code
     *
     * @param string $iso_code ISO code
     * @param string $language 'de' or 'en'
     * @return string|null Country name or null if not found
     */
    public static function get_country_name($iso_code, $language = 'de') {
        $iso_code = strtoupper(trim($iso_code));

        // German names mapping
        $german_names = [
            'AL' => 'Albanien',
            'AT' => 'Österreich',
            'BA' => 'Bosnien und Herzegowina',
            'BE' => 'Belgien',
            'BG' => 'Bulgarien',
            'BY' => 'Belarus',
            'CH' => 'Schweiz',
            'CY' => 'Zypern',
            'CZ' => 'Tschechien',
            'DE' => 'Deutschland',
            'DK' => 'Dänemark',
            'EE' => 'Estland',
            'ES' => 'Spanien',
            'FI' => 'Finnland',
            'FR' => 'Frankreich',
            'GB' => 'Großbritannien',
            'GR' => 'Griechenland',
            'HR' => 'Kroatien',
            'HU' => 'Ungarn',
            'IE' => 'Irland',
            'IL' => 'Israel',
            'IS' => 'Island',
            'IT' => 'Italien',
            'KZ' => 'Kasachstan',
            'LT' => 'Litauen',
            'LU' => 'Luxemburg',
            'LV' => 'Lettland',
            'MD' => 'Moldau',
            'ME' => 'Montenegro',
            'MK' => 'Nordmazedonien',
            'NL' => 'Niederlande',
            'NO' => 'Norwegen',
            'PL' => 'Polen',
            'PT' => 'Portugal',
            'RO' => 'Rumänien',
            'RS' => 'Serbien',
            'RU' => 'Russland',
            'SE' => 'Schweden',
            'SI' => 'Slowenien',
            'SK' => 'Slowakei',
            'TR' => 'Türkei',
            'UA' => 'Ukraine',
            'XK' => 'Kosovo',
        ];

        // English names mapping
        $english_names = [
            'AL' => 'Albania',
            'AT' => 'Austria',
            'BA' => 'Bosnia and Herzegovina',
            'BE' => 'Belgium',
            'BG' => 'Bulgaria',
            'BY' => 'Belarus',
            'CH' => 'Switzerland',
            'CY' => 'Cyprus',
            'CZ' => 'Czech Republic',
            'DE' => 'Germany',
            'DK' => 'Denmark',
            'EE' => 'Estonia',
            'ES' => 'Spain',
            'FI' => 'Finland',
            'FR' => 'France',
            'GB' => 'United Kingdom',
            'GR' => 'Greece',
            'HR' => 'Croatia',
            'HU' => 'Hungary',
            'IE' => 'Ireland',
            'IL' => 'Israel',
            'IS' => 'Iceland',
            'IT' => 'Italy',
            'KZ' => 'Kazakhstan',
            'LT' => 'Lithuania',
            'LU' => 'Luxembourg',
            'LV' => 'Latvia',
            'MD' => 'Moldova',
            'ME' => 'Montenegro',
            'MK' => 'North Macedonia',
            'NL' => 'Netherlands',
            'NO' => 'Norway',
            'PL' => 'Poland',
            'PT' => 'Portugal',
            'RO' => 'Romania',
            'RS' => 'Serbia',
            'RU' => 'Russia',
            'SE' => 'Sweden',
            'SI' => 'Slovenia',
            'SK' => 'Slovakia',
            'TR' => 'Turkey',
            'UA' => 'Ukraine',
            'XK' => 'Kosovo',
        ];

        $names = ($language === 'de') ? $german_names : $english_names;

        return $names[$iso_code] ?? null;
    }

    /**
     * Get all country mappings
     *
     * @return array
     */
    public static function get_all_mappings() {
        return self::$country_map;
    }
}
