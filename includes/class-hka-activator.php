<?php
/**
 * Fired during plugin activation and deactivation.
 *
 * @package Housekeeping_PWA_App
 */

if (!defined('ABSPATH')) {
    exit;
}

class HKA_Activator {

    /**
     * Activate plugin - create database tables and set defaults.
     */
    public static function activate() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();
        $table_prefix = $wpdb->prefix . HKA_TABLE_PREFIX;

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        // Room status table
        $sql_room_status = "CREATE TABLE {$table_prefix}room_status (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            room_number varchar(20) NOT NULL,
            status varchar(20) NOT NULL DEFAULT 'dirty',
            status_date date NOT NULL,
            assigned_to bigint(20) DEFAULT NULL,
            updated_by bigint(20) NOT NULL,
            updated_at datetime NOT NULL,
            inspection_required tinyint(1) DEFAULT 0,
            priority varchar(10) DEFAULT 'normal',
            PRIMARY KEY  (id),
            KEY room_number (room_number),
            KEY status (status),
            KEY status_date (status_date),
            KEY assigned_to (assigned_to)
        ) $charset_collate;";

        dbDelta($sql_room_status);

        // Room notes table
        $sql_room_notes = "CREATE TABLE {$table_prefix}room_notes (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            room_number varchar(20) NOT NULL,
            note_date date NOT NULL,
            note_text text NOT NULL,
            note_type varchar(20) DEFAULT 'general',
            created_by bigint(20) NOT NULL,
            created_at datetime NOT NULL,
            is_resolved tinyint(1) DEFAULT 0,
            resolved_by bigint(20) DEFAULT NULL,
            resolved_at datetime DEFAULT NULL,
            PRIMARY KEY  (id),
            KEY room_number (room_number),
            KEY note_date (note_date),
            KEY created_by (created_by),
            KEY is_resolved (is_resolved)
        ) $charset_collate;";

        dbDelta($sql_room_notes);

        // Cleaning checklists table
        $sql_checklists = "CREATE TABLE {$table_prefix}cleaning_checklists (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            room_number varchar(20) NOT NULL,
            checklist_date date NOT NULL,
            checklist_type varchar(20) DEFAULT 'standard',
            items_json text NOT NULL,
            completed_by bigint(20) DEFAULT NULL,
            completed_at datetime DEFAULT NULL,
            inspection_passed tinyint(1) DEFAULT NULL,
            inspected_by bigint(20) DEFAULT NULL,
            inspected_at datetime DEFAULT NULL,
            inspection_notes text,
            PRIMARY KEY  (id),
            KEY room_number (room_number),
            KEY checklist_date (checklist_date),
            KEY completed_by (completed_by)
        ) $charset_collate;";

        dbDelta($sql_checklists);

        // Task assignments table
        $sql_tasks = "CREATE TABLE {$table_prefix}tasks (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            task_title varchar(200) NOT NULL,
            task_description text,
            task_type varchar(20) DEFAULT 'general',
            room_number varchar(20) DEFAULT NULL,
            assigned_to bigint(20) DEFAULT NULL,
            created_by bigint(20) NOT NULL,
            created_at datetime NOT NULL,
            due_date datetime DEFAULT NULL,
            priority varchar(10) DEFAULT 'normal',
            status varchar(20) DEFAULT 'pending',
            completed_by bigint(20) DEFAULT NULL,
            completed_at datetime DEFAULT NULL,
            is_recurring tinyint(1) DEFAULT 0,
            recurrence_pattern varchar(50) DEFAULT NULL,
            PRIMARY KEY  (id),
            KEY room_number (room_number),
            KEY assigned_to (assigned_to),
            KEY status (status),
            KEY due_date (due_date)
        ) $charset_collate;";

        dbDelta($sql_tasks);

        // Set default options
        add_option('hka_version', HKA_VERSION);
        add_option('hka_app_page_id', '');
        add_option('hka_default_checklist_items', json_encode(array(
            'Vacuum floor',
            'Dust surfaces',
            'Clean bathroom',
            'Change linens',
            'Empty trash',
            'Restock amenities',
            'Check minibar',
            'Inspect for damage'
        )));
        add_option('hka_newbook_integration_enabled', true);
        add_option('hka_room_status_colors', json_encode(array(
            'clean' => '#4caf50',
            'dirty' => '#f44336',
            'inspected' => '#2196f3',
            'occupied' => '#9e9e9e',
            'out_of_order' => '#ff9800',
            'checkout' => '#ffc107'
        )));

        // Create app page if it doesn't exist
        self::create_app_page();

        // Add rewrite rules before flushing
        add_rewrite_rule('^manifest\.json$', 'index.php?hka_manifest=1', 'top');
        add_rewrite_rule('^service-worker\.js$', 'index.php?hka_sw=1', 'top');

        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Deactivate plugin.
     */
    public static function deactivate() {
        // Clear scheduled hooks
        wp_clear_scheduled_hook('hka_daily_cleanup');

        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Create the main app page.
     */
    private static function create_app_page() {
        $page_id = get_option('hka_app_page_id');

        // Check if page already exists
        if ($page_id && get_post($page_id)) {
            return;
        }

        // Create new page
        $page_data = array(
            'post_title'    => 'Housekeeping',
            'post_content'  => '[housekeeping_pwa_app]',
            'post_status'   => 'publish',
            'post_type'     => 'page',
            'post_author'   => 1,
            'comment_status' => 'closed',
            'ping_status'   => 'closed'
        );

        $page_id = wp_insert_post($page_data);

        if ($page_id && !is_wp_error($page_id)) {
            update_option('hka_app_page_id', $page_id);
        }
    }
}
