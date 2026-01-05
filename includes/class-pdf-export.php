<?php
/**
 * PDF Export Handler
 *
 * @package IBD_Vertretungen
 */

if (!defined('ABSPATH')) {
    exit;
}

class IBD_PDF_Export {

    /**
     * Constructor
     */
    public function __construct() {
        add_action('wp_ajax_nopriv_ibd_download_pdf', [$this, 'ajax_download']);
        add_action('wp_ajax_ibd_download_pdf', [$this, 'ajax_download']);
    }

    /**
     * AJAX download handler
     */
    public function ajax_download() {
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;

        if ($id > 0) {
            self::generate_single($id);
        } else {
            self::generate_all();
        }

        exit;
    }

    /**
     * Generate PDF for single Vertretung
     *
     * @param int $post_id Post ID
     */
    public static function generate_single($post_id) {
        $vertretung = IBD_Data_Handler::get_vertretung_data($post_id);

        if (!$vertretung) {
            wp_die(__('Vertretung nicht gefunden', 'ibd-vertretungen'));
        }

        $html = self::build_html([$vertretung], false);
        $filename = sanitize_file_name($vertretung['title']) . '.pdf';

        self::output_pdf($html, $filename);
    }

    /**
     * Generate PDF for all Vertretungen
     */
    public static function generate_all() {
        $vertretungen = IBD_Data_Handler::get_all_vertretungen();

        if (empty($vertretungen)) {
            wp_die(__('Keine Vertretungen gefunden', 'ibd-vertretungen'));
        }

        $html = self::build_html($vertretungen, true);
        $filename = 'IBD-Vertretungen-' . date('Y-m-d') . '.pdf';

        self::output_pdf($html, $filename);
    }

    /**
     * Build HTML for PDF
     *
     * @param array $vertretungen Vertretungen data
     * @param bool $is_overview Is overview document
     * @return string
     */
    private static function build_html($vertretungen, $is_overview = false) {
        $options = get_option('ibd_vertretungen_options', []);
        $primary_color = $options['primary_color'] ?? '#BE1622';

        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                * {
                    margin: 0;
                    padding: 0;
                    box-sizing: border-box;
                }
                body {
                    font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
                    font-size: 11pt;
                    line-height: 1.5;
                    color: #333;
                }
                .header {
                    background: <?php echo esc_attr($primary_color); ?>;
                    color: white;
                    padding: 20px;
                    margin-bottom: 20px;
                }
                .header h1 {
                    font-size: 24pt;
                    font-weight: bold;
                }
                .header p {
                    font-size: 10pt;
                    opacity: 0.9;
                    margin-top: 5px;
                }
                .vertretung {
                    page-break-inside: avoid;
                    margin-bottom: 30px;
                    padding: 20px;
                    border: 1px solid #ddd;
                    border-radius: 8px;
                }
                .vertretung-header {
                    display: flex;
                    align-items: center;
                    margin-bottom: 15px;
                    border-bottom: 2px solid <?php echo esc_attr($primary_color); ?>;
                    padding-bottom: 10px;
                }
                .vertretung-logo {
                    width: 80px;
                    height: 80px;
                    object-fit: contain;
                    margin-right: 15px;
                }
                .vertretung-title {
                    font-size: 16pt;
                    font-weight: bold;
                    color: <?php echo esc_attr($primary_color); ?>;
                }
                .vertretung-address {
                    margin-bottom: 15px;
                }
                .vertretung-website {
                    color: <?php echo esc_attr($primary_color); ?>;
                    text-decoration: none;
                }
                .countries-section {
                    margin-bottom: 15px;
                }
                .countries-section h4 {
                    font-size: 11pt;
                    color: #666;
                    margin-bottom: 5px;
                }
                .countries-list {
                    background: #f5f5f5;
                    padding: 10px;
                    border-radius: 4px;
                }
                .contacts-section {
                    margin-top: 15px;
                }
                .contacts-section h4 {
                    font-size: 11pt;
                    color: #666;
                    margin-bottom: 10px;
                }
                .contact-group {
                    margin-bottom: 15px;
                    padding: 10px;
                    background: #f9f9f9;
                    border-left: 3px solid <?php echo esc_attr($primary_color); ?>;
                }
                .contact-group-countries {
                    font-weight: bold;
                    margin-bottom: 8px;
                    color: <?php echo esc_attr($primary_color); ?>;
                }
                .contact {
                    margin-bottom: 8px;
                    padding-left: 10px;
                }
                .contact-name {
                    font-weight: bold;
                }
                .contact-info {
                    font-size: 10pt;
                    color: #666;
                }
                .footer {
                    margin-top: 30px;
                    padding-top: 15px;
                    border-top: 1px solid #ddd;
                    text-align: center;
                    font-size: 9pt;
                    color: #999;
                }
                .page-break {
                    page-break-after: always;
                }
            </style>
        </head>
        <body>
            <div class="header">
                <h1><?php echo $is_overview ? 'IBD Wickeltechnik - Internationale Vertretungen' : esc_html($vertretungen[0]['title']); ?></h1>
                <p><?php echo esc_html(sprintf(__('Stand: %s', 'ibd-vertretungen'), date_i18n(get_option('date_format')))); ?></p>
            </div>

            <?php foreach ($vertretungen as $index => $v): ?>
                <div class="vertretung">
                    <div class="vertretung-header">
                        <?php if (!empty($v['logo'])): ?>
                            <img src="<?php echo esc_url($v['logo']); ?>" class="vertretung-logo" alt="">
                        <?php endif; ?>
                        <div class="vertretung-title"><?php echo esc_html($v['title']); ?></div>
                    </div>

                    <div class="vertretung-address">
                        <?php if (!empty($v['address']['street'])): ?>
                            <?php echo esc_html($v['address']['street']); ?><br>
                        <?php endif; ?>
                        <?php if (!empty($v['address']['zip']) || !empty($v['address']['city'])): ?>
                            <?php echo esc_html(trim($v['address']['zip'] . ' ' . $v['address']['city'])); ?><br>
                        <?php endif; ?>
                        <?php if (!empty($v['address']['country'])): ?>
                            <?php echo esc_html($v['address']['country']); ?>
                        <?php endif; ?>
                    </div>

                    <?php if (!empty($v['website']['url'])): ?>
                        <p>
                            <strong><?php _e('Website:', 'ibd-vertretungen'); ?></strong>
                            <a href="<?php echo esc_url($v['website']['url']); ?>" class="vertretung-website">
                                <?php echo esc_html($v['website']['url']); ?>
                            </a>
                        </p>
                    <?php endif; ?>

                    <?php if (!empty($v['all_countries'])): ?>
                        <div class="countries-section">
                            <h4><?php _e('Zuständig für:', 'ibd-vertretungen'); ?></h4>
                            <div class="countries-list">
                                <?php
                                $country_names = array_map(function($c) { return $c['name']; }, $v['all_countries']);
                                echo esc_html(implode(', ', $country_names));
                                ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($v['contacts_by_country'])): ?>
                        <div class="contacts-section">
                            <h4><?php _e('Ansprechpartner:', 'ibd-vertretungen'); ?></h4>
                            <?php foreach ($v['contacts_by_country'] as $group): ?>
                                <div class="contact-group">
                                    <?php if (!empty($group['countries'])): ?>
                                        <div class="contact-group-countries">
                                            <?php
                                            $group_countries = array_map(function($c) { return $c['name']; }, $group['countries']);
                                            echo esc_html(implode(', ', $group_countries));
                                            ?>
                                        </div>
                                    <?php endif; ?>
                                    <?php foreach ($group['contacts'] as $contact): ?>
                                        <div class="contact">
                                            <?php if (!empty($contact['name'])): ?>
                                                <div class="contact-name"><?php echo esc_html($contact['name']); ?></div>
                                            <?php endif; ?>
                                            <div class="contact-info">
                                                <?php if (!empty($contact['email'])): ?>
                                                    <?php _e('E-Mail:', 'ibd-vertretungen'); ?> <?php echo esc_html($contact['email']); ?><br>
                                                <?php endif; ?>
                                                <?php if (!empty($contact['phone'])): ?>
                                                    <?php _e('Tel:', 'ibd-vertretungen'); ?> <?php echo esc_html($contact['phone']); ?>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($v['additional_info'])): ?>
                        <div class="additional-info">
                            <h4><?php _e('Zusätzliche Informationen:', 'ibd-vertretungen'); ?></h4>
                            <p><?php echo nl2br(esc_html($v['additional_info'])); ?></p>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if ($is_overview && $index < count($vertretungen) - 1 && ($index + 1) % 2 === 0): ?>
                    <div class="page-break"></div>
                <?php endif; ?>
            <?php endforeach; ?>

            <div class="footer">
                <?php echo esc_html(sprintf(__('IBD Wickeltechnik - %s', 'ibd-vertretungen'), home_url())); ?>
            </div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }

    /**
     * Output PDF using browser print
     *
     * Note: For full PDF generation, consider using a library like TCPDF or mPDF
     * This implementation outputs HTML that can be printed to PDF
     *
     * @param string $html HTML content
     * @param string $filename Filename
     */
    private static function output_pdf($html, $filename) {
        // For now, output as printable HTML
        // For true PDF generation, you would integrate TCPDF, mPDF, or wkhtmltopdf

        header('Content-Type: text/html; charset=utf-8');
        header('Content-Disposition: inline; filename="' . $filename . '"');

        // Add print-specific styles and auto-print script
        $html = str_replace('</head>', '
            <style>
                @media print {
                    .no-print { display: none !important; }
                    body { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
                }
            </style>
            <script>
                window.onload = function() {
                    // Auto-trigger print dialog
                    setTimeout(function() {
                        window.print();
                    }, 500);
                };
            </script>
            </head>', $html);

        echo $html;
    }

    /**
     * Get download URL for PDF
     *
     * @param int $post_id Post ID (0 for all)
     * @return string
     */
    public static function get_download_url($post_id = 0) {
        return add_query_arg([
            'action' => 'ibd_download_pdf',
            'id' => $post_id,
        ], admin_url('admin-ajax.php'));
    }
}
