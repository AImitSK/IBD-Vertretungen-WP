<?php
/**
 * GitHub Plugin Updater
 *
 * Ermöglicht WordPress-Updates direkt von GitHub Releases
 *
 * @package IBD_Vertretungen
 */

if (!defined('ABSPATH')) {
    exit;
}

class IBD_GitHub_Updater {

    private $slug;
    private $plugin_file;
    private $github_repo;
    private $github_user;
    private $current_version;
    private $transient_name;

    /**
     * Constructor
     *
     * @param string $plugin_file Main plugin file path
     * @param string $github_user GitHub username
     * @param string $github_repo GitHub repository name
     */
    public function __construct($plugin_file, $github_user, $github_repo) {
        $this->plugin_file = $plugin_file;
        $this->slug = plugin_basename($plugin_file);
        $this->github_user = $github_user;
        $this->github_repo = $github_repo;
        $this->transient_name = 'ibd_github_update_' . md5($this->slug);

        // Get current version from plugin header
        if (!function_exists('get_plugin_data')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        $plugin_data = get_plugin_data($plugin_file);
        $this->current_version = $plugin_data['Version'];

        // Hook into WordPress update system
        add_filter('pre_set_site_transient_update_plugins', [$this, 'check_update']);
        add_filter('plugins_api', [$this, 'plugin_info'], 20, 3);
        add_filter('upgrader_source_selection', [$this, 'fix_source_dir'], 10, 4);

        // Add "Check for updates" link
        add_filter('plugin_action_links_' . $this->slug, [$this, 'add_action_links']);

        // Handle manual update check
        add_action('admin_init', [$this, 'handle_force_check']);
    }

    /**
     * Handle force update check request
     */
    public function handle_force_check() {
        if (!isset($_GET['ibd_check_update'])) {
            return;
        }

        if (!current_user_can('update_plugins')) {
            return;
        }

        // Verify nonce
        if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'ibd_check_update')) {
            return;
        }

        // Clear caches
        delete_transient($this->transient_name);
        delete_site_transient('update_plugins');

        // Add admin notice
        add_action('admin_notices', function() {
            echo '<div class="notice notice-success is-dismissible"><p>';
            echo esc_html__('Update-Prüfung abgeschlossen. Falls ein Update verfügbar ist, wird es jetzt angezeigt.', 'ibd-vertretungen');
            echo '</p></div>';
        });
    }

    /**
     * Get release info from GitHub
     *
     * @return object|false
     */
    private function get_github_release() {
        // Check cache first
        $cached = get_transient($this->transient_name);
        if ($cached !== false) {
            return $cached;
        }

        // Fetch from GitHub API
        $url = sprintf(
            'https://api.github.com/repos/%s/%s/releases/latest',
            $this->github_user,
            $this->github_repo
        );

        $response = wp_remote_get($url, [
            'headers' => [
                'Accept' => 'application/vnd.github.v3+json',
                'User-Agent' => 'WordPress/' . get_bloginfo('version')
            ],
            'timeout' => 10
        ]);

        if (is_wp_error($response)) {
            return false;
        }

        $body = wp_remote_retrieve_body($response);
        $release = json_decode($body);

        if (empty($release) || isset($release->message)) {
            return false;
        }

        // Cache for 6 hours
        set_transient($this->transient_name, $release, 6 * HOUR_IN_SECONDS);

        return $release;
    }

    /**
     * Check for plugin updates
     *
     * @param object $transient
     * @return object
     */
    public function check_update($transient) {
        if (empty($transient->checked)) {
            return $transient;
        }

        $release = $this->get_github_release();
        if (!$release) {
            return $transient;
        }

        // Get version from tag (remove 'v' prefix if present)
        $new_version = ltrim($release->tag_name, 'v');

        // Compare versions
        if (version_compare($this->current_version, $new_version, '<')) {
            $plugin_slug = dirname($this->slug);

            // Find the ZIP asset
            $download_url = $release->zipball_url;

            // Check for uploaded ZIP asset (preferred)
            if (!empty($release->assets)) {
                foreach ($release->assets as $asset) {
                    if (strpos($asset->name, '.zip') !== false) {
                        $download_url = $asset->browser_download_url;
                        break;
                    }
                }
            }

            $transient->response[$this->slug] = (object) [
                'slug' => $plugin_slug,
                'plugin' => $this->slug,
                'new_version' => $new_version,
                'url' => $release->html_url,
                'package' => $download_url,
                'icons' => [],
                'banners' => [],
                'requires' => '6.0',
                'tested' => get_bloginfo('version'),
                'requires_php' => '8.0',
            ];
        }

        return $transient;
    }

    /**
     * Provide plugin information for the update details popup
     *
     * @param false|object|array $result
     * @param string $action
     * @param object $args
     * @return object|false
     */
    public function plugin_info($result, $action, $args) {
        if ($action !== 'plugin_information') {
            return $result;
        }

        if (!isset($args->slug) || $args->slug !== dirname($this->slug)) {
            return $result;
        }

        $release = $this->get_github_release();
        if (!$release) {
            return $result;
        }

        $new_version = ltrim($release->tag_name, 'v');

        // Parse markdown changelog
        $changelog = $release->body ?? '';
        $changelog = wp_kses_post(Parsedown::instance()->text($changelog) ?? nl2br(esc_html($changelog)));

        return (object) [
            'name' => 'IBD Vertretungen',
            'slug' => dirname($this->slug),
            'version' => $new_version,
            'author' => '<a href="https://ibd-wt.de">IBD Wickeltechnik</a>',
            'author_profile' => 'https://ibd-wt.de',
            'homepage' => 'https://github.com/' . $this->github_user . '/' . $this->github_repo,
            'requires' => '6.0',
            'tested' => get_bloginfo('version'),
            'requires_php' => '8.0',
            'downloaded' => 0,
            'last_updated' => $release->published_at ?? '',
            'sections' => [
                'description' => 'Interaktive Google Maps Karte mit Firmenvertretungen für IBD Wickeltechnik.',
                'changelog' => $changelog ?: 'Siehe GitHub Release für Details.',
                'installation' => 'Plugin hochladen und aktivieren. ACF-Felder importieren.',
            ],
            'download_link' => $release->zipball_url,
        ];
    }

    /**
     * Fix the source directory name from GitHub's format to our plugin slug
     *
     * GitHub zipball extracts to: AImitSK-IBD-Vertretungen-WP-abc1234/
     * We need it to be: IBD-Vertretungen-WP/
     *
     * @param string $source Path to upgrade/temp directory
     * @param string $remote_source Remote file source
     * @param WP_Upgrader $upgrader
     * @param array $hook_extra
     * @return string|WP_Error
     */
    public function fix_source_dir($source, $remote_source, $upgrader, $hook_extra) {
        global $wp_filesystem;

        // Check if this is our plugin being updated
        if (!isset($hook_extra['plugin']) || $hook_extra['plugin'] !== $this->slug) {
            return $source;
        }

        // Expected correct folder name
        $correct_folder = trailingslashit($remote_source) . dirname($this->slug);

        // If source is already correct, return it
        if ($source === $correct_folder || $source === trailingslashit($correct_folder)) {
            return $source;
        }

        // Rename the extracted folder to the correct name
        if ($wp_filesystem->move($source, $correct_folder, true)) {
            return trailingslashit($correct_folder);
        }

        // If move failed, return error
        return new WP_Error(
            'rename_failed',
            __('Das Plugin-Verzeichnis konnte nicht umbenannt werden.', 'ibd-vertretungen')
        );
    }

    /**
     * Add action links to plugin row
     *
     * @param array $links
     * @return array
     */
    public function add_action_links($links) {
        $check_link = sprintf(
            '<a href="%s">%s</a>',
            wp_nonce_url(admin_url('plugins.php?ibd_check_update=1'), 'ibd_check_update'),
            __('Auf Updates prüfen', 'ibd-vertretungen')
        );

        array_unshift($links, $check_link);

        return $links;
    }

}

// Simple Parsedown fallback (minimal markdown parser)
if (!class_exists('Parsedown')) {
    class Parsedown {
        private static $instance;

        public static function instance() {
            if (!self::$instance) {
                self::$instance = new self();
            }
            return self::$instance;
        }

        public function text($text) {
            // Simple markdown to HTML conversion
            $text = esc_html($text);

            // Headers
            $text = preg_replace('/^### (.+)$/m', '<h4>$1</h4>', $text);
            $text = preg_replace('/^## (.+)$/m', '<h3>$1</h3>', $text);
            $text = preg_replace('/^# (.+)$/m', '<h2>$1</h2>', $text);

            // Bold and italic
            $text = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $text);
            $text = preg_replace('/\*(.+?)\*/', '<em>$1</em>', $text);

            // Lists
            $text = preg_replace('/^- (.+)$/m', '<li>$1</li>', $text);
            $text = preg_replace('/(<li>.*<\/li>)/s', '<ul>$1</ul>', $text);

            // Line breaks
            $text = nl2br($text);

            return $text;
        }
    }
}
