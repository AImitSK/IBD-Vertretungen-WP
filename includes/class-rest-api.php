<?php
/**
 * REST API Handler
 *
 * @package IBD_Vertretungen
 */

if (!defined('ABSPATH')) {
    exit;
}

class IBD_REST_API {

    /**
     * Namespace
     */
    private $namespace = 'ibd/v1';

    /**
     * Constructor
     */
    public function __construct() {
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    /**
     * Register REST routes
     */
    public function register_routes() {
        // Get all Vertretungen
        register_rest_route($this->namespace, '/vertretungen', [
            'methods' => 'GET',
            'callback' => [$this, 'get_vertretungen'],
            'permission_callback' => '__return_true',
            'args' => [
                'search' => [
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
            ],
        ]);

        // Get single Vertretung
        register_rest_route($this->namespace, '/vertretungen/(?P<id>\d+)', [
            'methods' => 'GET',
            'callback' => [$this, 'get_vertretung'],
            'permission_callback' => '__return_true',
            'args' => [
                'id' => [
                    'validate_callback' => function($param) {
                        return is_numeric($param);
                    }
                ],
            ],
        ]);

        // Get all countries
        register_rest_route($this->namespace, '/countries', [
            'methods' => 'GET',
            'callback' => [$this, 'get_countries'],
            'permission_callback' => '__return_true',
        ]);

        // Get GeoJSON for a country
        register_rest_route($this->namespace, '/geojson/(?P<code>[a-zA-Z]{2})', [
            'methods' => 'GET',
            'callback' => [$this, 'get_geojson'],
            'permission_callback' => '__return_true',
            'args' => [
                'code' => [
                    'validate_callback' => function($param) {
                        return preg_match('/^[a-zA-Z]{2}$/', $param);
                    }
                ],
            ],
        ]);

        // Get vCard
        register_rest_route($this->namespace, '/vcard/(?P<id>\d+)', [
            'methods' => 'GET',
            'callback' => [$this, 'get_vcard'],
            'permission_callback' => '__return_true',
            'args' => [
                'id' => [
                    'validate_callback' => function($param) {
                        return is_numeric($param);
                    }
                ],
                'contact_index' => [
                    'type' => 'integer',
                    'default' => 0,
                ],
                'group_index' => [
                    'type' => 'integer',
                    'default' => 0,
                ],
            ],
        ]);

        // Get PDF
        register_rest_route($this->namespace, '/pdf', [
            'methods' => 'GET',
            'callback' => [$this, 'get_pdf'],
            'permission_callback' => '__return_true',
            'args' => [
                'id' => [
                    'type' => 'integer',
                    'default' => 0,
                ],
            ],
        ]);
    }

    /**
     * Get all Vertretungen
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response
     */
    public function get_vertretungen($request) {
        $search = $request->get_param('search');

        if ($search) {
            $data = IBD_Data_Handler::search_vertretungen($search);
        } else {
            $data = IBD_Data_Handler::get_all_vertretungen();
        }

        return rest_ensure_response($data);
    }

    /**
     * Get single Vertretung
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error
     */
    public function get_vertretung($request) {
        $id = intval($request->get_param('id'));
        $data = IBD_Data_Handler::get_vertretung_data($id);

        if (!$data) {
            return new WP_Error(
                'not_found',
                __('Vertretung nicht gefunden', 'ibd-vertretungen'),
                ['status' => 404]
            );
        }

        return rest_ensure_response($data);
    }

    /**
     * Get all countries
     *
     * @return WP_REST_Response
     */
    public function get_countries() {
        $data = IBD_Data_Handler::get_all_countries();
        return rest_ensure_response($data);
    }

    /**
     * Get GeoJSON for a country
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error
     */
    public function get_geojson($request) {
        $code = strtoupper($request->get_param('code'));
        $file = IBD_VERTRETUNGEN_PLUGIN_DIR . 'assets/geojson/' . $code . '.json';

        if (!file_exists($file)) {
            return new WP_Error(
                'not_found',
                __('GeoJSON nicht gefunden', 'ibd-vertretungen'),
                ['status' => 404]
            );
        }

        $content = file_get_contents($file);
        $data = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return new WP_Error(
                'invalid_json',
                __('Ungültiges GeoJSON', 'ibd-vertretungen'),
                ['status' => 500]
            );
        }

        return rest_ensure_response($data);
    }

    /**
     * Get vCard
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error
     */
    public function get_vcard($request) {
        $id = intval($request->get_param('id'));
        $contact_index = intval($request->get_param('contact_index'));
        $group_index = intval($request->get_param('group_index'));

        $vcard = IBD_VCard::generate($id, $group_index, $contact_index);

        if (!$vcard) {
            return new WP_Error(
                'not_found',
                __('Kontakt nicht gefunden', 'ibd-vertretungen'),
                ['status' => 404]
            );
        }

        // Send as file download
        $vertretung = IBD_Data_Handler::get_vertretung_data($id);
        $filename = sanitize_file_name($vertretung['title']) . '.vcf';

        header('Content-Type: text/vcard; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        echo $vcard;
        exit;
    }

    /**
     * Get PDF
     *
     * @param WP_REST_Request $request Request object
     * @return void
     */
    public function get_pdf($request) {
        $id = intval($request->get_param('id'));

        if ($id > 0) {
            IBD_PDF_Export::generate_single($id);
        } else {
            IBD_PDF_Export::generate_all();
        }

        exit;
    }
}
