<?php
/**
 * Core plugin class.
 *
 * @package Housekeeping_PWA_App
 */

if (!defined('ABSPATH')) {
    exit;
}

class HKA_Core {

    /**
     * Single instance of the class.
     */
    private static $instance = null;

    /**
     * Module registry.
     */
    public $modules;

    /**
     * PWA handler.
     */
    public $pwa;

    /**
     * AJAX handler.
     */
    public $ajax;

    /**
     * Get singleton instance.
     */
    public static function instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor.
     */
    private function __construct() {
        $this->init();
    }

    /**
     * Initialize the plugin.
     */
    private function init() {
        // Initialize components
        $this->modules = new HKA_Modules();
        $this->pwa = new HKA_PWA();
        $this->ajax = new HKA_AJAX();

        // Register hooks
        add_action('init', array($this, 'register_shortcodes'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('template_redirect', array($this, 'check_app_access'));

        // Register modules
        $this->register_default_modules();
    }

    /**
     * Register default modules.
     */
    private function register_default_modules() {
        // Room status module
        $room_status = new HKA_Room_Status_Module();
        $this->modules->register_module($room_status);

        // Allow other plugins to register modules
        do_action('hka_register_modules', $this->modules);
    }

    /**
     * Register shortcodes.
     */
    public function register_shortcodes() {
        add_shortcode('housekeeping_pwa_app', array($this, 'render_app'));
    }

    /**
     * Render the main app via shortcode.
     */
    public function render_app($atts) {
        // Check if user is logged in
        if (!is_user_logged_in()) {
            return '<p>Please <a href="' . wp_login_url(get_permalink()) . '">log in</a> to access the Housekeeping App.</p>';
        }

        // Check if user has any housekeeping permissions
        $has_access = false;
        $all_permissions = array(
            'housekeeping.view_rooms',
            'housekeeping.update_status',
            'housekeeping.view_checklist',
            'housekeeping.view_reports'
        );

        foreach ($all_permissions as $permission) {
            if (wfa_user_can($permission)) {
                $has_access = true;
                break;
            }
        }

        if (!$has_access) {
            return '<div class="hka-no-access"><p>You do not have permission to access the Housekeeping App. Please contact your administrator.</p></div>';
        }

        // Load template
        ob_start();
        include HKA_PLUGIN_DIR . 'templates/app-page.php';
        return ob_get_clean();
    }

    /**
     * Check app access for direct page visits.
     */
    public function check_app_access() {
        $app_page_id = get_option('hka_app_page_id');

        if (!$app_page_id || !is_page($app_page_id)) {
            return;
        }

        // Redirect to login if not authenticated
        if (!is_user_logged_in()) {
            wp_redirect(wp_login_url(get_permalink()));
            exit;
        }
    }

    /**
     * Enqueue scripts and styles.
     */
    public function enqueue_scripts() {
        $app_page_id = get_option('hka_app_page_id');

        // Only load on app page
        if (!is_page($app_page_id)) {
            return;
        }

        // Check if user has access
        if (!is_user_logged_in()) {
            return;
        }

        // Enqueue styles
        wp_enqueue_style(
            'hka-app',
            HKA_PLUGIN_URL . 'assets/css/app.css',
            array(),
            HKA_VERSION
        );

        // Enqueue scripts
        wp_enqueue_script(
            'hka-app',
            HKA_PLUGIN_URL . 'assets/js/app.js',
            array('jquery'),
            HKA_VERSION,
            true
        );

        // Get user's available modules
        $user_modules = $this->modules->get_user_modules();

        // Localize script with data
        wp_localize_script('hka-app', 'hkaData', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('hka_nonce'),
            'userId' => get_current_user_id(),
            'modules' => $user_modules,
            'permissions' => wfa_get_user_permissions(),
            'pluginUrl' => HKA_PLUGIN_URL,
            'roomStatusColors' => json_decode(get_option('hka_room_status_colors', '{}'), true)
        ));

        // Enqueue module-specific scripts
        foreach ($user_modules as $module_id => $module_config) {
            $module_js = HKA_PLUGIN_URL . 'assets/js/modules/' . $module_id . '.js';
            $module_js_path = HKA_PLUGIN_DIR . 'assets/js/modules/' . $module_id . '.js';

            if (file_exists($module_js_path)) {
                wp_enqueue_script(
                    'hka-module-' . $module_id,
                    $module_js,
                    array('hka-app'),
                    HKA_VERSION,
                    true
                );
            }
        }
    }

    /**
     * Add admin menu.
     */
    public function add_admin_menu() {
        add_menu_page(
            'Housekeeping App',
            'Housekeeping',
            'manage_options',
            'housekeeping-app',
            array($this, 'render_admin_page'),
            'dashicons-clipboard',
            30
        );

        add_submenu_page(
            'housekeeping-app',
            'Settings',
            'Settings',
            'manage_options',
            'housekeeping-app-settings',
            array($this, 'render_settings_page')
        );
    }

    /**
     * Render admin page.
     */
    public function render_admin_page() {
        $app_page_id = get_option('hka_app_page_id');
        $app_url = $app_page_id ? get_permalink($app_page_id) : '';

        ?>
        <div class="wrap">
            <h1>Housekeeping PWA App</h1>
            <div class="card">
                <h2>Quick Links</h2>
                <p>
                    <a href="<?php echo esc_url($app_url); ?>" class="button button-primary" target="_blank">
                        Open Housekeeping App
                    </a>
                </p>
                <p>
                    <a href="<?php echo admin_url('admin.php?page=workforce-auth-departments'); ?>" class="button">
                        Manage Department Permissions
                    </a>
                </p>
            </div>
            <div class="card">
                <h2>App Information</h2>
                <p><strong>Version:</strong> <?php echo HKA_VERSION; ?></p>
                <p><strong>App Page:</strong> <a href="<?php echo esc_url($app_url); ?>" target="_blank"><?php echo esc_url($app_url); ?></a></p>
            </div>
        </div>
        <?php
    }

    /**
     * Render settings page.
     */
    public function render_settings_page() {
        if (isset($_POST['hka_save_settings']) && check_admin_referer('hka_settings')) {
            // Save default checklist items
            if (isset($_POST['default_checklist_items'])) {
                $items = array_map('sanitize_text_field', explode("\n", $_POST['default_checklist_items']));
                $items = array_filter($items); // Remove empty lines
                update_option('hka_default_checklist_items', json_encode($items));
            }

            echo '<div class="notice notice-success"><p>Settings saved successfully.</p></div>';
        }

        $default_items = json_decode(get_option('hka_default_checklist_items', '[]'), true);
        ?>
        <div class="wrap">
            <h1>Housekeeping App Settings</h1>
            <form method="post" action="">
                <?php wp_nonce_field('hka_settings'); ?>

                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="default_checklist_items">Default Checklist Items</label>
                        </th>
                        <td>
                            <textarea
                                name="default_checklist_items"
                                id="default_checklist_items"
                                rows="10"
                                class="large-text code"
                            ><?php echo esc_textarea(implode("\n", $default_items)); ?></textarea>
                            <p class="description">Enter one item per line. These will be used as the default cleaning checklist.</p>
                        </td>
                    </tr>
                </table>

                <p class="submit">
                    <input type="submit" name="hka_save_settings" class="button button-primary" value="Save Settings">
                </p>
            </form>
        </div>
        <?php
    }
}
