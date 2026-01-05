<?php
/**
 * Shortcode Handler
 *
 * @package IBD_Vertretungen
 */

if (!defined('ABSPATH')) {
    exit;
}

class IBD_Shortcode {

    /**
     * Constructor
     */
    public function __construct() {
        add_shortcode('ibd_vertretungen', [$this, 'render_shortcode']);
    }

    /**
     * Render shortcode
     *
     * @param array $atts Shortcode attributes
     * @return string
     */
    public function render_shortcode($atts) {
        $atts = shortcode_atts([
            'show_search' => 'true',
            'show_filter' => 'true',
            'region' => '',
        ], $atts, 'ibd_vertretungen');

        // Get all Vertretungen data
        $vertretungen = IBD_Data_Handler::get_all_vertretungen();

        // Get plugin options
        $options = get_option('ibd_vertretungen_options', []);
        $primary_color = $options['primary_color'] ?? '#BE1622';

        ob_start();

        // Include template
        include IBD_VERTRETUNGEN_PLUGIN_DIR . 'templates/map-container.php';

        return ob_get_clean();
    }
}
