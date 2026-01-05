<?php
/**
 * Plugin Name: IBD Vertretungen
 * Plugin URI: https://github.com/AImitSK/IBD-Vertretungen-WP
 * Description: Interaktive Google Maps Karte mit Firmenvertretungen für IBD Wickeltechnik
 * Version: 1.0.0
 * Author: IBD Wickeltechnik
 * Author URI: https://ibd-wt.de
 * Text Domain: ibd-vertretungen
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 8.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Plugin constants
define('IBD_VERTRETUNGEN_VERSION', '1.0.0');
define('IBD_VERTRETUNGEN_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('IBD_VERTRETUNGEN_PLUGIN_URL', plugin_dir_url(__FILE__));
define('IBD_VERTRETUNGEN_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Main Plugin Class
 */
final class IBD_Vertretungen {

    /**
     * Single instance
     */
    private static $instance = null;

    /**
     * Get instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        $this->load_dependencies();
        $this->init_hooks();
    }

    /**
     * Load required files
     */
    private function load_dependencies() {
        require_once IBD_VERTRETUNGEN_PLUGIN_DIR . 'includes/class-country-mapper.php';
        require_once IBD_VERTRETUNGEN_PLUGIN_DIR . 'includes/class-data-handler.php';
        require_once IBD_VERTRETUNGEN_PLUGIN_DIR . 'includes/class-rest-api.php';
        require_once IBD_VERTRETUNGEN_PLUGIN_DIR . 'includes/class-shortcode.php';
        require_once IBD_VERTRETUNGEN_PLUGIN_DIR . 'includes/class-vcard.php';
        require_once IBD_VERTRETUNGEN_PLUGIN_DIR . 'includes/class-pdf-export.php';

        if (is_admin()) {
            require_once IBD_VERTRETUNGEN_PLUGIN_DIR . 'admin/class-settings.php';

            // GitHub Updater - prüft auf Updates von GitHub Releases
            require_once IBD_VERTRETUNGEN_PLUGIN_DIR . 'includes/class-github-updater.php';
            new IBD_GitHub_Updater(
                __FILE__,
                'AImitSK',           // GitHub Username
                'IBD-Vertretungen-WP' // GitHub Repository Name
            );
        }
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        add_action('init', [$this, 'load_textdomain']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);

        // Initialize components
        add_action('init', [$this, 'init_components']);
    }

    /**
     * Load plugin textdomain
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'ibd-vertretungen',
            false,
            dirname(IBD_VERTRETUNGEN_PLUGIN_BASENAME) . '/languages'
        );
    }

    /**
     * Initialize plugin components
     */
    public function init_components() {
        // Initialize REST API
        new IBD_REST_API();

        // Initialize Shortcode
        new IBD_Shortcode();

        // Initialize vCard handler
        new IBD_VCard();

        // Initialize PDF Export
        new IBD_PDF_Export();

        // Initialize Admin Settings
        if (is_admin()) {
            new IBD_Settings();
        }
    }

    /**
     * Enqueue frontend assets
     * Called directly when shortcode is rendered (works with all page builders)
     */
    public static function enqueue_frontend_assets() {
        // Prevent loading twice
        static $loaded = false;
        if ($loaded) {
            return;
        }
        $loaded = true;

        $options = get_option('ibd_vertretungen_options', []);
        $google_maps_api_key = $options['google_maps_api_key'] ?? '';

        // Plugin CSS - load first
        wp_enqueue_style(
            'ibd-vertretungen-css',
            IBD_VERTRETUNGEN_PLUGIN_URL . 'assets/css/frontend.css',
            [],
            IBD_VERTRETUNGEN_VERSION
        );

        // Google Maps API - only load if not already loaded by another plugin (e.g., ACF)
        $maps_deps = [];
        if ($google_maps_api_key) {
            // Check if Google Maps is already registered/enqueued
            if (!wp_script_is('google-maps', 'registered') &&
                !wp_script_is('google-maps', 'enqueued') &&
                !wp_script_is('google-maps-api', 'registered') &&
                !wp_script_is('google-maps-api', 'enqueued')) {
                wp_enqueue_script(
                    'ibd-google-maps-api',
                    'https://maps.googleapis.com/maps/api/js?key=' . esc_attr($google_maps_api_key) . '&callback=Function.prototype',
                    [],
                    null,
                    true
                );
                $maps_deps = ['ibd-google-maps-api'];
            }
        }

        // Plugin JavaScript
        wp_enqueue_script(
            'ibd-vertretungen-js',
            IBD_VERTRETUNGEN_PLUGIN_URL . 'assets/js/app.js',
            $maps_deps,
            IBD_VERTRETUNGEN_VERSION,
            true
        );

        // Localize script
        wp_localize_script('ibd-vertretungen-js', 'ibdVertretungen', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'restUrl' => rest_url('ibd/v1/'),
            'nonce' => wp_create_nonce('wp_rest'),
            'pluginUrl' => IBD_VERTRETUNGEN_PLUGIN_URL,
            'mapCenter' => [
                'lat' => floatval($options['map_center_lat'] ?? 50),
                'lng' => floatval($options['map_center_lng'] ?? 10),
            ],
            'mapZoom' => intval($options['map_zoom'] ?? 4),
            'primaryColor' => $options['primary_color'] ?? '#BE1622',
            'i18n' => [
                'search' => __('Land oder Vertretung suchen...', 'ibd-vertretungen'),
                'noResults' => __('Keine Ergebnisse gefunden', 'ibd-vertretungen'),
                'loading' => __('Laden...', 'ibd-vertretungen'),
                'showContacts' => __('Ansprechpartner anzeigen', 'ibd-vertretungen'),
                'hideContacts' => __('Ansprechpartner verbergen', 'ibd-vertretungen'),
                'downloadVCard' => __('Kontakt speichern', 'ibd-vertretungen'),
                'downloadPDF' => __('PDF herunterladen', 'ibd-vertretungen'),
                'website' => __('Website', 'ibd-vertretungen'),
                'countries' => __('Länder', 'ibd-vertretungen'),
            ]
        ]);
    }

    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        if ('settings_page_ibd-vertretungen' !== $hook) {
            return;
        }

        wp_enqueue_style(
            'ibd-vertretungen-admin-css',
            IBD_VERTRETUNGEN_PLUGIN_URL . 'admin/css/admin.css',
            [],
            IBD_VERTRETUNGEN_VERSION
        );

        wp_enqueue_script(
            'ibd-vertretungen-admin-js',
            IBD_VERTRETUNGEN_PLUGIN_URL . 'admin/js/admin.js',
            ['jquery', 'wp-color-picker'],
            IBD_VERTRETUNGEN_VERSION,
            true
        );

        wp_enqueue_style('wp-color-picker');
    }

    /**
     * Plugin activation
     */
    public static function activate() {
        // Set default options
        $default_options = [
            'google_maps_api_key' => '',
            'map_center_lat' => 50,
            'map_center_lng' => 10,
            'map_zoom' => 4,
            'primary_color' => '#BE1622',
        ];

        add_option('ibd_vertretungen_options', $default_options);

        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Plugin deactivation
     */
    public static function deactivate() {
        flush_rewrite_rules();
    }
}

// Activation/Deactivation hooks
register_activation_hook(__FILE__, ['IBD_Vertretungen', 'activate']);
register_deactivation_hook(__FILE__, ['IBD_Vertretungen', 'deactivate']);

// Initialize plugin
function ibd_vertretungen() {
    return IBD_Vertretungen::get_instance();
}

// Start the plugin
add_action('plugins_loaded', 'ibd_vertretungen');
