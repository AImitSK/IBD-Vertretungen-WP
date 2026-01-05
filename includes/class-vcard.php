<?php
/**
 * vCard Generator
 *
 * @package IBD_Vertretungen
 */

if (!defined('ABSPATH')) {
    exit;
}

class IBD_VCard {

    /**
     * Constructor
     */
    public function __construct() {
        // AJAX handler for logged-out users
        add_action('wp_ajax_nopriv_ibd_download_vcard', [$this, 'ajax_download']);
        add_action('wp_ajax_ibd_download_vcard', [$this, 'ajax_download']);
    }

    /**
     * AJAX download handler
     */
    public function ajax_download() {
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        $group_index = isset($_GET['group']) ? intval($_GET['group']) : 0;
        $contact_index = isset($_GET['contact']) ? intval($_GET['contact']) : 0;

        if (!$id) {
            wp_die(__('Ungültige Anfrage', 'ibd-vertretungen'));
        }

        $vcard = self::generate($id, $group_index, $contact_index);

        if (!$vcard) {
            wp_die(__('Kontakt nicht gefunden', 'ibd-vertretungen'));
        }

        $vertretung = IBD_Data_Handler::get_vertretung_data($id);
        $filename = sanitize_file_name($vertretung['title']) . '.vcf';

        header('Content-Type: text/vcard; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($vcard));

        echo $vcard;
        exit;
    }

    /**
     * Generate vCard for a Vertretung contact
     *
     * @param int $post_id Post ID
     * @param int $group_index Contact group index
     * @param int $contact_index Contact index within group
     * @return string|null vCard content
     */
    public static function generate($post_id, $group_index = 0, $contact_index = 0) {
        $vertretung = IBD_Data_Handler::get_vertretung_data($post_id);

        if (!$vertretung) {
            return null;
        }

        // Get specific contact if available
        $contact = null;
        if (isset($vertretung['contacts_by_country'][$group_index]['contacts'][$contact_index])) {
            $contact = $vertretung['contacts_by_country'][$group_index]['contacts'][$contact_index];
        }

        // Build vCard
        $vcard = "BEGIN:VCARD\r\n";
        $vcard .= "VERSION:3.0\r\n";
        $vcard .= "PRODID:-//IBD Vertretungen//DE\r\n";

        // Name
        if ($contact && !empty($contact['name'])) {
            $vcard .= "FN:" . self::escape($contact['name']) . "\r\n";
            $vcard .= "N:" . self::escape($contact['name']) . ";;;;\r\n";
        } else {
            $vcard .= "FN:" . self::escape($vertretung['title']) . "\r\n";
            $vcard .= "N:" . self::escape($vertretung['title']) . ";;;;\r\n";
        }

        // Organization
        $vcard .= "ORG:" . self::escape($vertretung['title']) . "\r\n";

        // Address
        $addr = $vertretung['address'];
        if (!empty($addr['street']) || !empty($addr['city'])) {
            $vcard .= "ADR;TYPE=WORK:;;" .
                self::escape($addr['street']) . ";" .
                self::escape($addr['city']) . ";;" .
                self::escape($addr['zip']) . ";" .
                self::escape($addr['country']) . "\r\n";
        }

        // Phone
        if ($contact && !empty($contact['phone'])) {
            $vcard .= "TEL;TYPE=WORK,VOICE:" . self::escape($contact['phone']) . "\r\n";
        }

        // Email
        if ($contact && !empty($contact['email'])) {
            $vcard .= "EMAIL;TYPE=WORK:" . self::escape($contact['email']) . "\r\n";
        }

        // Website
        if (!empty($vertretung['website']['url'])) {
            $vcard .= "URL:" . self::escape($vertretung['website']['url']) . "\r\n";
        }

        // Logo
        if (!empty($vertretung['logo'])) {
            // Only include logo URL, not embedded image (for smaller file size)
            $vcard .= "PHOTO;VALUE=URI:" . self::escape($vertretung['logo']) . "\r\n";
        }

        // Geo coordinates
        if (!empty($vertretung['coordinates'])) {
            $vcard .= "GEO:" . $vertretung['coordinates']['lat'] . ";" . $vertretung['coordinates']['lng'] . "\r\n";
        }

        // Additional info as note
        if (!empty($vertretung['additional_info'])) {
            $vcard .= "NOTE:" . self::escape($vertretung['additional_info']) . "\r\n";
        }

        // Countries served
        if (!empty($vertretung['all_countries'])) {
            $countries = array_map(function($c) { return $c['name']; }, $vertretung['all_countries']);
            $vcard .= "CATEGORIES:" . self::escape(implode(',', $countries)) . "\r\n";
        }

        // Revision date
        $vcard .= "REV:" . date('Ymd\THis\Z') . "\r\n";

        $vcard .= "END:VCARD\r\n";

        return $vcard;
    }

    /**
     * Generate vCard for entire Vertretung (all contacts)
     *
     * @param int $post_id Post ID
     * @return string|null vCard content
     */
    public static function generate_full($post_id) {
        $vertretung = IBD_Data_Handler::get_vertretung_data($post_id);

        if (!$vertretung) {
            return null;
        }

        $vcards = [];

        // Main organization card
        $vcards[] = self::generate($post_id);

        // Individual contact cards
        foreach ($vertretung['contacts_by_country'] as $group_index => $group) {
            foreach ($group['contacts'] as $contact_index => $contact) {
                if (!empty($contact['name'])) {
                    $vcards[] = self::generate($post_id, $group_index, $contact_index);
                }
            }
        }

        return implode("\r\n", array_filter($vcards));
    }

    /**
     * Escape special characters for vCard
     *
     * @param string $value Value to escape
     * @return string
     */
    private static function escape($value) {
        $value = str_replace(['\\', ',', ';', "\n", "\r"], ['\\\\', '\\,', '\\;', '\\n', ''], $value);
        return $value;
    }

    /**
     * Get download URL for a vCard
     *
     * @param int $post_id Post ID
     * @param int $group_index Contact group index
     * @param int $contact_index Contact index
     * @return string
     */
    public static function get_download_url($post_id, $group_index = 0, $contact_index = 0) {
        return add_query_arg([
            'action' => 'ibd_download_vcard',
            'id' => $post_id,
            'group' => $group_index,
            'contact' => $contact_index,
        ], admin_url('admin-ajax.php'));
    }
}
