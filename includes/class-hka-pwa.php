<?php
/**
 * PWA (Progressive Web App) functionality.
 *
 * @package Housekeeping_PWA_App
 */

if (!defined('ABSPATH')) {
    exit;
}

class HKA_PWA {

    /**
     * Constructor.
     */
    public function __construct() {
        add_action('init', array($this, 'add_rewrite_rules'));
        add_action('template_redirect', array($this, 'serve_pwa_files'));
        add_action('wp_head', array($this, 'add_pwa_meta_tags'));
        add_filter('query_vars', array($this, 'add_query_vars'));
    }

    /**
     * Add query vars for PWA files.
     */
    public function add_query_vars($vars) {
        $vars[] = 'hka_manifest';
        $vars[] = 'hka_sw';
        return $vars;
    }

    /**
     * Add rewrite rules for PWA files.
     */
    public function add_rewrite_rules() {
        add_rewrite_rule('^manifest\.json$', 'index.php?hka_manifest=1', 'top');
        add_rewrite_rule('^service-worker\.js$', 'index.php?hka_sw=1', 'top');
    }

    /**
     * Serve PWA files.
     */
    public function serve_pwa_files() {
        global $wp_query;

        // Serve manifest
        if (isset($wp_query->query_vars['hka_manifest'])) {
            $this->serve_manifest();
            exit;
        }

        // Serve service worker
        if (isset($wp_query->query_vars['hka_sw'])) {
            $this->serve_service_worker();
            exit;
        }
    }

    /**
     * Serve manifest.json.
     */
    private function serve_manifest() {
        header('Content-Type: application/json');
        header('Service-Worker-Allowed: /');

        $app_page_id = get_option('hka_app_page_id');
        $start_url = $app_page_id ? get_permalink($app_page_id) : home_url();

        $manifest = array(
            'name' => get_bloginfo('name') . ' - Housekeeping',
            'short_name' => 'Housekeeping',
            'description' => 'Housekeeping operations management',
            'start_url' => $start_url,
            'display' => 'standalone',
            'background_color' => '#ffffff',
            'theme_color' => '#2196f3',
            'orientation' => 'portrait',
            'icons' => array(
                array(
                    'src' => HKA_PLUGIN_URL . 'assets/icons/icon-192x192.png',
                    'sizes' => '192x192',
                    'type' => 'image/png',
                    'purpose' => 'any maskable'
                ),
                array(
                    'src' => HKA_PLUGIN_URL . 'assets/icons/icon-512x512.png',
                    'sizes' => '512x512',
                    'type' => 'image/png',
                    'purpose' => 'any maskable'
                )
            ),
            'categories' => array('productivity', 'business'),
            'screenshots' => array()
        );

        echo json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Serve service-worker.js.
     */
    private function serve_service_worker() {
        header('Content-Type: application/javascript');
        header('Service-Worker-Allowed: /');

        $sw_file = HKA_PLUGIN_DIR . 'assets/js/service-worker.js';

        if (file_exists($sw_file)) {
            readfile($sw_file);
        } else {
            echo '// Service worker file not found';
        }
    }

    /**
     * Add PWA meta tags to head.
     */
    public function add_pwa_meta_tags() {
        $app_page_id = get_option('hka_app_page_id');

        // Only on app page
        if (!is_page($app_page_id)) {
            return;
        }

        ?>
        <!-- PWA Meta Tags -->
        <meta name="theme-color" content="#2196f3">
        <meta name="mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="default">
        <meta name="apple-mobile-web-app-title" content="Housekeeping">
        <link rel="manifest" href="<?php echo home_url('/manifest.json'); ?>">
        <link rel="apple-touch-icon" href="<?php echo HKA_PLUGIN_URL; ?>assets/icons/icon-192x192.png">
        <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5, user-scalable=yes">
        <?php
    }
}
