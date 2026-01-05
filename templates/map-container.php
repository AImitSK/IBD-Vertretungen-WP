<?php
/**
 * Main Map Container Template
 *
 * @package IBD_Vertretungen
 */

if (!defined('ABSPATH')) {
    exit;
}

$show_search = ($atts['show_search'] === 'true');
$show_filter = ($atts['show_filter'] === 'true');

// Load CSS inline (guarantees it works with Avada and all page builders)
$css_file = IBD_VERTRETUNGEN_PLUGIN_DIR . 'assets/css/frontend.css';
static $ibd_css_loaded = false;
if (!$ibd_css_loaded && file_exists($css_file)) {
    $ibd_css_loaded = true;
    echo '<style id="ibd-vertretungen-inline-css">';
    readfile($css_file);
    echo '</style>';
}
?>

<div class="ibd-vertretungen-wrapper" style="--ibd-primary: <?php echo esc_attr($primary_color); ?>;">

    <?php if ($show_search): ?>
    <div class="ibd-search-bar">
        <div class="ibd-search-input-wrapper">
            <svg class="ibd-search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="11" cy="11" r="8"></circle>
                <path d="m21 21-4.35-4.35"></path>
            </svg>
            <input type="text"
                   id="ibd-search-input"
                   class="ibd-search-input"
                   placeholder="<?php esc_attr_e('Land oder Vertretung suchen...', 'ibd-vertretungen'); ?>">
            <button type="button" id="ibd-search-clear" class="ibd-search-clear" aria-label="<?php esc_attr_e('Suche löschen', 'ibd-vertretungen'); ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M18 6L6 18M6 6l12 12"></path>
                </svg>
            </button>
        </div>
    </div>
    <?php endif; ?>

    <div class="ibd-main-container">
        <div class="ibd-map-container">
            <div id="ibd-map" class="ibd-map"></div>
            <div id="ibd-map-loading" class="ibd-map-loading">
                <div class="ibd-spinner"></div>
                <span><?php _e('Karte wird geladen...', 'ibd-vertretungen'); ?></span>
            </div>
        </div>

        <div class="ibd-cards-container">
            <div class="ibd-cards-header">
                <h3 class="ibd-cards-title"><?php _e('Unsere Vertretungen', 'ibd-vertretungen'); ?></h3>
                <span id="ibd-results-count" class="ibd-results-count"></span>
            </div>

            <div id="ibd-cards-list" class="ibd-cards-list">
                <?php if (empty($vertretungen)): ?>
                    <div class="ibd-no-results">
                        <p><?php _e('Keine Vertretungen gefunden.', 'ibd-vertretungen'); ?></p>
                    </div>
                <?php else: ?>
                    <?php foreach ($vertretungen as $v): ?>
                        <?php include IBD_VERTRETUNGEN_PLUGIN_DIR . 'templates/card-template.php'; ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="ibd-cards-footer">
                <a href="<?php echo esc_url(IBD_PDF_Export::get_download_url(0)); ?>"
                   class="ibd-btn ibd-btn-secondary"
                   target="_blank">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                        <line x1="16" y1="13" x2="8" y2="13"></line>
                        <line x1="16" y1="17" x2="8" y2="17"></line>
                        <polyline points="10 9 9 9 8 9"></polyline>
                    </svg>
                    <?php _e('Alle als PDF', 'ibd-vertretungen'); ?>
                </a>
            </div>
        </div>
    </div>

</div>

<!-- Hidden data for JavaScript -->
<script type="application/json" id="ibd-vertretungen-data">
<?php echo wp_json_encode($vertretungen); ?>
</script>
