<?php
/**
 * Data Handler - Reads ACF data for Vertretungen
 *
 * @package IBD_Vertretungen
 */

if (!defined('ABSPATH')) {
    exit;
}

class IBD_Data_Handler {

    /**
     * Get all Vertretungen
     *
     * @return array
     */
    public static function get_all_vertretungen() {
        $args = [
            'post_type' => 'vertretung',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'orderby' => 'title',
            'order' => 'ASC',
        ];

        $posts = get_posts($args);
        $vertretungen = [];

        foreach ($posts as $post) {
            $vertretung = self::get_vertretung_data($post->ID);
            if ($vertretung) {
                $vertretungen[] = $vertretung;
            }
        }

        return $vertretungen;
    }

    /**
     * Get single Vertretung data
     *
     * @param int $post_id Post ID
     * @return array|null
     */
    public static function get_vertretung_data($post_id) {
        if (!function_exists('get_field')) {
            return null;
        }

        $post = get_post($post_id);
        if (!$post || $post->post_type !== 'vertretung') {
            return null;
        }

        // Get coordinates from Google Maps field
        $location = get_field('vertretung_google_maps', $post_id);
        $coordinates = null;

        if ($location && isset($location['lat']) && isset($location['lng'])) {
            $coordinates = [
                'lat' => floatval($location['lat']),
                'lng' => floatval($location['lng']),
                'address' => $location['address'] ?? '',
            ];
        }

        // Get logo (Featured Image)
        $logo_url = '';
        $thumbnail_id = get_post_thumbnail_id($post_id);
        if ($thumbnail_id) {
            $logo_url = wp_get_attachment_image_url($thumbnail_id, 'medium');
        }

        // Get website
        $website = get_field('vertretung_website', $post_id);
        $website_data = null;
        if ($website) {
            $website_data = [
                'url' => $website['url'] ?? '',
                'title' => $website['title'] ?? __('Website', 'ibd-vertretungen'),
                'target' => $website['target'] ?? '_blank',
            ];
        }

        // Get contacts by country
        $contacts_by_country = self::get_contacts_by_country($post_id);

        // Get all assigned countries
        $all_countries = self::get_all_assigned_countries($post_id);

        return [
            'id' => $post_id,
            'title' => $post->post_title,
            'logo' => $logo_url,
            'address' => [
                'street' => get_field('vertretung_strase', $post_id) ?: '',
                'zip' => get_field('vertretung_plz', $post_id) ?: '',
                'city' => get_field('vertretung_ort', $post_id) ?: '',
                'country' => get_field('vertretung_land', $post_id) ?: '',
            ],
            'coordinates' => $coordinates,
            'website' => $website_data,
            'additional_info' => get_field('vertretung_zusatzliche_informationen', $post_id) ?: '',
            'contacts_by_country' => $contacts_by_country,
            'all_countries' => $all_countries,
        ];
    }

    /**
     * Get contacts grouped by country
     *
     * @param int $post_id Post ID
     * @return array
     */
    private static function get_contacts_by_country($post_id) {
        $ansprechpartner = get_field('ansprechpartner', $post_id);
        $result = [];

        if (!$ansprechpartner || !is_array($ansprechpartner)) {
            return $result;
        }

        foreach ($ansprechpartner as $entry) {
            $countries = [];
            $country_ids = $entry['land'] ?? [];

            if (!is_array($country_ids)) {
                $country_ids = [$country_ids];
            }

            foreach ($country_ids as $term_id) {
                $term = get_term($term_id, 'land');
                if ($term && !is_wp_error($term)) {
                    $iso_code = IBD_Country_Mapper::get_iso_code($term->name);
                    $countries[] = [
                        'id' => $term->term_id,
                        'name' => $term->name,
                        'slug' => $term->slug,
                        'iso_code' => $iso_code,
                    ];
                }
            }

            $contacts = [];
            $kontaktpersonen = $entry['kontaktpersonen'] ?? [];

            if (is_array($kontaktpersonen)) {
                foreach ($kontaktpersonen as $person) {
                    $contacts[] = [
                        'name' => $person['vertretung_name'] ?? '',
                        'email' => $person['vertretung_email'] ?? '',
                        'phone' => $person['vertretung_telefon'] ?? '',
                    ];
                }
            }

            if (!empty($countries) || !empty($contacts)) {
                $result[] = [
                    'countries' => $countries,
                    'contacts' => $contacts,
                ];
            }
        }

        return $result;
    }

    /**
     * Get all assigned countries for a Vertretung
     *
     * @param int $post_id Post ID
     * @return array
     */
    private static function get_all_assigned_countries($post_id) {
        $ansprechpartner = get_field('ansprechpartner', $post_id);
        $countries = [];
        $seen_ids = [];

        if (!$ansprechpartner || !is_array($ansprechpartner)) {
            return $countries;
        }

        foreach ($ansprechpartner as $entry) {
            $country_ids = $entry['land'] ?? [];

            if (!is_array($country_ids)) {
                $country_ids = [$country_ids];
            }

            foreach ($country_ids as $term_id) {
                if (in_array($term_id, $seen_ids)) {
                    continue;
                }

                $term = get_term($term_id, 'land');
                if ($term && !is_wp_error($term)) {
                    $seen_ids[] = $term_id;
                    $iso_code = IBD_Country_Mapper::get_iso_code($term->name);
                    $countries[] = [
                        'id' => $term->term_id,
                        'name' => $term->name,
                        'slug' => $term->slug,
                        'iso_code' => $iso_code,
                    ];
                }
            }
        }

        // Sort alphabetically
        usort($countries, function($a, $b) {
            return strcasecmp($a['name'], $b['name']);
        });

        return $countries;
    }

    /**
     * Get all countries from taxonomy
     *
     * @return array
     */
    public static function get_all_countries() {
        $terms = get_terms([
            'taxonomy' => 'land',
            'hide_empty' => false,
            'orderby' => 'name',
            'order' => 'ASC',
        ]);

        if (is_wp_error($terms)) {
            return [];
        }

        $countries = [];

        foreach ($terms as $term) {
            $iso_code = IBD_Country_Mapper::get_iso_code($term->name);

            // Find which Vertretung handles this country
            $vertretung_id = self::get_vertretung_for_country($term->term_id);

            $countries[] = [
                'id' => $term->term_id,
                'name' => $term->name,
                'slug' => $term->slug,
                'iso_code' => $iso_code,
                'vertretung_id' => $vertretung_id,
            ];
        }

        return $countries;
    }

    /**
     * Get Vertretung ID that handles a specific country
     *
     * @param int $country_term_id Country term ID
     * @return int|null
     */
    public static function get_vertretung_for_country($country_term_id) {
        $args = [
            'post_type' => 'vertretung',
            'posts_per_page' => -1,
            'post_status' => 'publish',
        ];

        $posts = get_posts($args);

        foreach ($posts as $post) {
            $ansprechpartner = get_field('ansprechpartner', $post->ID);

            if (!$ansprechpartner || !is_array($ansprechpartner)) {
                continue;
            }

            foreach ($ansprechpartner as $entry) {
                $country_ids = $entry['land'] ?? [];

                if (!is_array($country_ids)) {
                    $country_ids = [$country_ids];
                }

                if (in_array($country_term_id, $country_ids)) {
                    return $post->ID;
                }
            }
        }

        return null;
    }

    /**
     * Search Vertretungen
     *
     * @param string $query Search query
     * @return array
     */
    public static function search_vertretungen($query) {
        $all = self::get_all_vertretungen();
        $query = strtolower(trim($query));

        if (empty($query)) {
            return $all;
        }

        $results = [];

        foreach ($all as $vertretung) {
            $match = false;

            // Search in title
            if (strpos(strtolower($vertretung['title']), $query) !== false) {
                $match = true;
            }

            // Search in address
            if (!$match) {
                $address_string = implode(' ', array_filter([
                    $vertretung['address']['city'],
                    $vertretung['address']['country'],
                ]));
                if (strpos(strtolower($address_string), $query) !== false) {
                    $match = true;
                }
            }

            // Search in countries
            if (!$match) {
                foreach ($vertretung['all_countries'] as $country) {
                    if (strpos(strtolower($country['name']), $query) !== false) {
                        $match = true;
                        break;
                    }
                }
            }

            // Search in contacts
            if (!$match) {
                foreach ($vertretung['contacts_by_country'] as $group) {
                    foreach ($group['contacts'] as $contact) {
                        if (strpos(strtolower($contact['name']), $query) !== false) {
                            $match = true;
                            break 2;
                        }
                    }
                }
            }

            if ($match) {
                $results[] = $vertretung;
            }
        }

        return $results;
    }
}
