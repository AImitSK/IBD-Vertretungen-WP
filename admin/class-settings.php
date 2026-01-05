<?php
/**
 * Settings Page
 *
 * @package IBD_Vertretungen
 */

if (!defined('ABSPATH')) {
    exit;
}

class IBD_Settings {

    /**
     * Option name
     */
    private $option_name = 'ibd_vertretungen_options';

    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_menu', [$this, 'add_settings_page']);
        add_action('admin_init', [$this, 'register_settings']);
    }

    /**
     * Add settings page to menu
     */
    public function add_settings_page() {
        add_options_page(
            __('IBD Vertretungen', 'ibd-vertretungen'),
            __('IBD Vertretungen', 'ibd-vertretungen'),
            'manage_options',
            'ibd-vertretungen',
            [$this, 'render_settings_page']
        );
    }

    /**
     * Register settings
     */
    public function register_settings() {
        register_setting(
            'ibd_vertretungen_settings',
            $this->option_name,
            [$this, 'sanitize_options']
        );

        // Google Maps Section
        add_settings_section(
            'ibd_google_maps_section',
            __('Google Maps Einstellungen', 'ibd-vertretungen'),
            [$this, 'render_maps_section'],
            'ibd-vertretungen'
        );

        add_settings_field(
            'google_maps_api_key',
            __('Google Maps API Key', 'ibd-vertretungen'),
            [$this, 'render_api_key_field'],
            'ibd-vertretungen',
            'ibd_google_maps_section'
        );

        add_settings_field(
            'map_center',
            __('Kartenstart-Position', 'ibd-vertretungen'),
            [$this, 'render_map_center_field'],
            'ibd-vertretungen',
            'ibd_google_maps_section'
        );

        add_settings_field(
            'map_zoom',
            __('Zoom-Level', 'ibd-vertretungen'),
            [$this, 'render_map_zoom_field'],
            'ibd-vertretungen',
            'ibd_google_maps_section'
        );

        // Design Section
        add_settings_section(
            'ibd_design_section',
            __('Design Einstellungen', 'ibd-vertretungen'),
            [$this, 'render_design_section'],
            'ibd-vertretungen'
        );

        add_settings_field(
            'primary_color',
            __('Primärfarbe', 'ibd-vertretungen'),
            [$this, 'render_color_field'],
            'ibd-vertretungen',
            'ibd_design_section'
        );
    }

    /**
     * Sanitize options
     */
    public function sanitize_options($input) {
        $sanitized = [];

        $sanitized['google_maps_api_key'] = sanitize_text_field($input['google_maps_api_key'] ?? '');
        $sanitized['map_center_lat'] = floatval($input['map_center_lat'] ?? 50);
        $sanitized['map_center_lng'] = floatval($input['map_center_lng'] ?? 10);
        $sanitized['map_zoom'] = intval($input['map_zoom'] ?? 4);

        // Sanitize color
        $color = sanitize_hex_color($input['primary_color'] ?? '#BE1622');
        $sanitized['primary_color'] = $color ?: '#BE1622';

        return $sanitized;
    }

    /**
     * Render settings page
     */
    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }

        $options = get_option($this->option_name, []);
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

            <form action="options.php" method="post">
                <?php
                settings_fields('ibd_vertretungen_settings');
                do_settings_sections('ibd-vertretungen');
                submit_button(__('Einstellungen speichern', 'ibd-vertretungen'));
                ?>
            </form>

            <hr>

            <h2><?php _e('Shortcode', 'ibd-vertretungen'); ?></h2>
            <p><?php _e('Verwenden Sie folgenden Shortcode, um die Vertretungen-Karte einzubinden:', 'ibd-vertretungen'); ?></p>
            <code>[ibd_vertretungen]</code>

            <h3><?php _e('Verfügbare Parameter', 'ibd-vertretungen'); ?></h3>
            <table class="widefat" style="max-width: 600px;">
                <thead>
                    <tr>
                        <th><?php _e('Parameter', 'ibd-vertretungen'); ?></th>
                        <th><?php _e('Beschreibung', 'ibd-vertretungen'); ?></th>
                        <th><?php _e('Standard', 'ibd-vertretungen'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><code>show_search</code></td>
                        <td><?php _e('Suchfeld anzeigen', 'ibd-vertretungen'); ?></td>
                        <td><code>true</code></td>
                    </tr>
                    <tr>
                        <td><code>show_filter</code></td>
                        <td><?php _e('Filter anzeigen', 'ibd-vertretungen'); ?></td>
                        <td><code>true</code></td>
                    </tr>
                </tbody>
            </table>

            <hr>

            <h2><?php _e('ACF Import', 'ibd-vertretungen'); ?></h2>
            <p><?php _e('Die ACF-Feldgruppen können über ACF Pro importiert werden:', 'ibd-vertretungen'); ?></p>
            <p><strong><?php _e('Datei:', 'ibd-vertretungen'); ?></strong> <code>wp-content/plugins/ibd-vertretungen/acf/acf-export-2026-01-05.json</code></p>
            <p>
                <a href="<?php echo admin_url('edit.php?post_type=acf-field-group&page=acf-tools'); ?>" class="button">
                    <?php _e('Zu ACF Import', 'ibd-vertretungen'); ?>
                </a>
            </p>
        </div>
        <?php
    }

    /**
     * Render maps section description
     */
    public function render_maps_section() {
        echo '<p>' . __('Konfigurieren Sie die Google Maps Integration.', 'ibd-vertretungen') . '</p>';
    }

    /**
     * Render design section description
     */
    public function render_design_section() {
        echo '<p>' . __('Passen Sie das Design an Ihr Corporate Design an.', 'ibd-vertretungen') . '</p>';
    }

    /**
     * Render API key field
     */
    public function render_api_key_field() {
        $options = get_option($this->option_name, []);
        $value = $options['google_maps_api_key'] ?? '';
        ?>
        <input type="text"
               name="<?php echo $this->option_name; ?>[google_maps_api_key]"
               value="<?php echo esc_attr($value); ?>"
               class="regular-text"
               placeholder="AIza...">
        <p class="description">
            <?php
            printf(
                __('Erstellen Sie einen API Key in der %sGoogle Cloud Console%s', 'ibd-vertretungen'),
                '<a href="https://console.cloud.google.com/google/maps-apis/credentials" target="_blank">',
                '</a>'
            );
            ?>
        </p>
        <?php
    }

    /**
     * Render map center field
     */
    public function render_map_center_field() {
        $options = get_option($this->option_name, []);
        $lat = $options['map_center_lat'] ?? 50;
        $lng = $options['map_center_lng'] ?? 10;
        ?>
        <label>
            <?php _e('Breitengrad:', 'ibd-vertretungen'); ?>
            <input type="number"
                   name="<?php echo $this->option_name; ?>[map_center_lat]"
                   value="<?php echo esc_attr($lat); ?>"
                   step="0.0001"
                   style="width: 100px;">
        </label>
        &nbsp;&nbsp;
        <label>
            <?php _e('Längengrad:', 'ibd-vertretungen'); ?>
            <input type="number"
                   name="<?php echo $this->option_name; ?>[map_center_lng]"
                   value="<?php echo esc_attr($lng); ?>"
                   step="0.0001"
                   style="width: 100px;">
        </label>
        <p class="description">
            <?php _e('Standard für Europa: Lat 50, Lng 10', 'ibd-vertretungen'); ?>
        </p>
        <?php
    }

    /**
     * Render map zoom field
     */
    public function render_map_zoom_field() {
        $options = get_option($this->option_name, []);
        $value = $options['map_zoom'] ?? 4;
        ?>
        <input type="range"
               name="<?php echo $this->option_name; ?>[map_zoom]"
               value="<?php echo esc_attr($value); ?>"
               min="1"
               max="20"
               step="1"
               id="ibd_map_zoom_range">
        <span id="ibd_map_zoom_value"><?php echo esc_html($value); ?></span>
        <p class="description">
            <?php _e('1 = Weltansicht, 20 = Straßenansicht. Empfohlen für Europa: 4', 'ibd-vertretungen'); ?>
        </p>
        <script>
            document.getElementById('ibd_map_zoom_range').addEventListener('input', function() {
                document.getElementById('ibd_map_zoom_value').textContent = this.value;
            });
        </script>
        <?php
    }

    /**
     * Render color picker field
     */
    public function render_color_field() {
        $options = get_option($this->option_name, []);
        $value = $options['primary_color'] ?? '#BE1622';
        ?>
        <input type="text"
               name="<?php echo $this->option_name; ?>[primary_color]"
               value="<?php echo esc_attr($value); ?>"
               class="ibd-color-picker"
               data-default-color="#BE1622">
        <p class="description">
            <?php _e('IBD Rot: #BE1622', 'ibd-vertretungen'); ?>
        </p>
        <?php
    }
}
