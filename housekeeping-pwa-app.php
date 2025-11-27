<?php
/**
 * Plugin Name: Housekeeping PWA App
 * Plugin URI: https://github.com/jtricerolph/housekeeping-pwa-app
 * Description: Progressive Web App for housekeeping operations. Requires workforce-authentication plugin.
 * Version: 1.0.0
 * Author: JTR
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: housekeeping-pwa-app
 * Requires at least: 6.0
 * Requires PHP: 7.4
 */

if (!defined('ABSPATH')) {
    exit;
}

define('HKA_VERSION', '1.0.0');
define('HKA_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('HKA_PLUGIN_URL', plugin_dir_url(__FILE__));
define('HKA_TABLE_PREFIX', 'hka_');

/**
 * Activation hook with dependency check.
 */
function hka_activate() {
    if (!function_exists('wfa_user_can')) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die(
            '<h1>Dependency Missing</h1>' .
            '<p><strong>Housekeeping PWA App</strong> requires <strong>Workforce Authentication</strong> plugin to be installed and activated.</p>' .
            '<p><a href="' . admin_url('plugins.php') . '">&larr; Return to Plugins</a></p>',
            'Plugin Activation Error',
            array('back_link' => true)
        );
    }

    require_once HKA_PLUGIN_DIR . 'includes/class-hka-activator.php';
    HKA_Activator::activate();
}
register_activation_hook(__FILE__, 'hka_activate');

/**
 * Deactivation hook.
 */
function hka_deactivate() {
    require_once HKA_PLUGIN_DIR . 'includes/class-hka-activator.php';
    HKA_Activator::deactivate();
}
register_deactivation_hook(__FILE__, 'hka_deactivate');

/**
 * Class autoloader.
 */
spl_autoload_register(function($class) {
    // Only autoload HKA_ classes
    if (strpos($class, 'HKA_') !== 0) {
        return;
    }

    // Convert class name to file name
    $class_file = str_replace('_', '-', strtolower($class));

    // First check standard includes directory
    $file_path = HKA_PLUGIN_DIR . 'includes/class-' . $class_file . '.php';

    if (file_exists($file_path)) {
        require_once $file_path;
        return;
    }

    // Check if it's a module class (ends with -module)
    if (strpos($class_file, '-module') !== false) {
        // Extract module name: hka-room-status-module -> room-status
        $module_name = str_replace('hka-', '', $class_file);
        $module_name = str_replace('-module', '', $module_name);

        $module_path = HKA_PLUGIN_DIR . 'includes/modules/' . $module_name . '/class-' . $class_file . '.php';

        if (file_exists($module_path)) {
            require_once $module_path;
            return;
        }
    }
});

/**
 * Initialize plugin after all plugins are loaded.
 */
add_action('plugins_loaded', 'hka_init');

function hka_init() {
    // Check for workforce-authentication dependency
    if (!function_exists('wfa_user_can')) {
        add_action('admin_notices', 'hka_dependency_notice');
        return;
    }

    // Initialize core
    HKA_Core::instance();
}

/**
 * Display admin notice if dependency is missing.
 */
function hka_dependency_notice() {
    ?>
    <div class="notice notice-error">
        <p>
            <strong>Housekeeping PWA App</strong> requires <strong>Workforce Authentication</strong> plugin to be installed and activated.
        </p>
    </div>
    <?php
}

/**
 * Register permissions with workforce-authentication.
 */
add_action('wfa_register_permissions', 'hka_register_permissions');

function hka_register_permissions() {
    // Room status permissions
    wfa_register_permission(
        'housekeeping.view_rooms',
        'View Room Status',
        'Access to view room status dashboard and room details',
        'Housekeeping App'
    );

    wfa_register_permission(
        'housekeeping.update_status',
        'Update Room Status',
        'Mark rooms as clean, dirty, inspected, or out of order',
        'Housekeeping App'
    );

    wfa_register_permission(
        'housekeeping.assign_rooms',
        'Assign Rooms',
        'Assign rooms to housekeeping staff members',
        'Housekeeping App'
    );

    // Checklist permissions
    wfa_register_permission(
        'housekeeping.view_checklist',
        'View Checklists',
        'Access cleaning checklists and inspection forms',
        'Housekeeping App'
    );

    wfa_register_permission(
        'housekeeping.complete_tasks',
        'Complete Tasks',
        'Mark checklist items and tasks as complete',
        'Housekeeping App'
    );

    // Notes and communication
    wfa_register_permission(
        'housekeeping.add_notes',
        'Add Notes',
        'Add notes and comments to rooms and tasks',
        'Housekeeping App'
    );

    wfa_register_permission(
        'housekeeping.view_all_notes',
        'View All Notes',
        'View notes from all staff members (not just own notes)',
        'Housekeeping App'
    );

    // Supervisor permissions
    wfa_register_permission(
        'housekeeping.view_reports',
        'View Reports',
        'Access summary reports and analytics',
        'Housekeeping App'
    );

    wfa_register_permission(
        'housekeeping.manage_settings',
        'Manage Settings',
        'Configure app settings and preferences',
        'Housekeeping App'
    );
}

/**
 * Helper function to get the main plugin instance.
 */
function hka() {
    return HKA_Core::instance();
}
