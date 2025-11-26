<?php
/**
 * AJAX request handlers.
 *
 * @package Housekeeping_PWA_App
 */

if (!defined('ABSPATH')) {
    exit;
}

class HKA_AJAX {

    /**
     * Constructor.
     */
    public function __construct() {
        // Room status endpoints
        add_action('wp_ajax_hka_get_room_status', array($this, 'get_room_status'));
        add_action('wp_ajax_hka_update_room_status', array($this, 'update_room_status'));
        add_action('wp_ajax_hka_assign_room', array($this, 'assign_room'));

        // Notes endpoints
        add_action('wp_ajax_hka_get_room_notes', array($this, 'get_room_notes'));
        add_action('wp_ajax_hka_add_room_note', array($this, 'add_room_note'));
        add_action('wp_ajax_hka_resolve_note', array($this, 'resolve_note'));

        // Tasks endpoints
        add_action('wp_ajax_hka_get_tasks', array($this, 'get_tasks'));
        add_action('wp_ajax_hka_create_task', array($this, 'create_task'));
        add_action('wp_ajax_hka_complete_task', array($this, 'complete_task'));

        // Checklist endpoints
        add_action('wp_ajax_hka_get_checklist', array($this, 'get_checklist'));
        add_action('wp_ajax_hka_save_checklist', array($this, 'save_checklist'));
    }

    /**
     * Verify nonce and user permissions.
     *
     * @param string $permission Required permission.
     * @return bool
     */
    private function verify_request($permission = '') {
        // Check nonce
        if (!check_ajax_referer('hka_nonce', 'nonce', false)) {
            wp_send_json_error(array('message' => 'Invalid security token'));
            return false;
        }

        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'Not authenticated'));
            return false;
        }

        // Check permission if specified
        if (!empty($permission) && !wfa_user_can($permission)) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
            return false;
        }

        return true;
    }

    /**
     * Get room status for a specific date.
     */
    public function get_room_status() {
        $this->verify_request('housekeeping.view_rooms');

        global $wpdb;
        $table = $wpdb->prefix . HKA_TABLE_PREFIX . 'room_status';

        $date = sanitize_text_field($_POST['date'] ?? current_time('Y-m-d'));

        $rooms = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table} WHERE status_date = %s ORDER BY room_number ASC",
            $date
        ));

        wp_send_json_success(array('rooms' => $rooms));
    }

    /**
     * Update room status.
     */
    public function update_room_status() {
        $this->verify_request('housekeeping.update_status');

        global $wpdb;
        $table = $wpdb->prefix . HKA_TABLE_PREFIX . 'room_status';

        $room_number = sanitize_text_field($_POST['room_number']);
        $status = sanitize_text_field($_POST['status']);
        $date = sanitize_text_field($_POST['date'] ?? current_time('Y-m-d'));
        $inspection_required = isset($_POST['inspection_required']) ? 1 : 0;
        $priority = sanitize_text_field($_POST['priority'] ?? 'normal');

        // Validate status
        $valid_statuses = array('clean', 'dirty', 'inspected', 'occupied', 'out_of_order', 'checkout');
        if (!in_array($status, $valid_statuses)) {
            wp_send_json_error(array('message' => 'Invalid status'));
        }

        // Check if record exists
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM {$table} WHERE room_number = %s AND status_date = %s",
            $room_number,
            $date
        ));

        if ($existing) {
            // Update existing record
            $wpdb->update(
                $table,
                array(
                    'status' => $status,
                    'updated_by' => get_current_user_id(),
                    'updated_at' => current_time('mysql'),
                    'inspection_required' => $inspection_required,
                    'priority' => $priority
                ),
                array(
                    'id' => $existing->id
                ),
                array('%s', '%d', '%s', '%d', '%s'),
                array('%d')
            );
        } else {
            // Insert new record
            $wpdb->insert(
                $table,
                array(
                    'room_number' => $room_number,
                    'status' => $status,
                    'status_date' => $date,
                    'updated_by' => get_current_user_id(),
                    'updated_at' => current_time('mysql'),
                    'inspection_required' => $inspection_required,
                    'priority' => $priority
                ),
                array('%s', '%s', '%s', '%d', '%s', '%d', '%s')
            );
        }

        wp_send_json_success(array('message' => 'Status updated successfully'));
    }

    /**
     * Assign room to staff member.
     */
    public function assign_room() {
        $this->verify_request('housekeeping.assign_rooms');

        global $wpdb;
        $table = $wpdb->prefix . HKA_TABLE_PREFIX . 'room_status';

        $room_number = sanitize_text_field($_POST['room_number']);
        $assigned_to = intval($_POST['assigned_to']);
        $date = sanitize_text_field($_POST['date'] ?? current_time('Y-m-d'));

        $wpdb->update(
            $table,
            array(
                'assigned_to' => $assigned_to,
                'updated_by' => get_current_user_id(),
                'updated_at' => current_time('mysql')
            ),
            array(
                'room_number' => $room_number,
                'status_date' => $date
            ),
            array('%d', '%d', '%s'),
            array('%s', '%s')
        );

        wp_send_json_success(array('message' => 'Room assigned successfully'));
    }

    /**
     * Get notes for a room.
     */
    public function get_room_notes() {
        $this->verify_request('housekeeping.view_rooms');

        global $wpdb;
        $table = $wpdb->prefix . HKA_TABLE_PREFIX . 'room_notes';

        $room_number = sanitize_text_field($_POST['room_number']);
        $date = sanitize_text_field($_POST['date'] ?? current_time('Y-m-d'));

        $notes = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table} WHERE room_number = %s AND note_date = %s ORDER BY created_at DESC",
            $room_number,
            $date
        ));

        wp_send_json_success(array('notes' => $notes));
    }

    /**
     * Add a note to a room.
     */
    public function add_room_note() {
        $this->verify_request('housekeeping.add_notes');

        global $wpdb;
        $table = $wpdb->prefix . HKA_TABLE_PREFIX . 'room_notes';

        $room_number = sanitize_text_field($_POST['room_number']);
        $note_text = sanitize_textarea_field($_POST['note_text']);
        $note_type = sanitize_text_field($_POST['note_type'] ?? 'general');
        $date = sanitize_text_field($_POST['date'] ?? current_time('Y-m-d'));

        $wpdb->insert(
            $table,
            array(
                'room_number' => $room_number,
                'note_date' => $date,
                'note_text' => $note_text,
                'note_type' => $note_type,
                'created_by' => get_current_user_id(),
                'created_at' => current_time('mysql')
            ),
            array('%s', '%s', '%s', '%s', '%d', '%s')
        );

        wp_send_json_success(array(
            'message' => 'Note added successfully',
            'note_id' => $wpdb->insert_id
        ));
    }

    /**
     * Resolve a note.
     */
    public function resolve_note() {
        $this->verify_request('housekeeping.add_notes');

        global $wpdb;
        $table = $wpdb->prefix . HKA_TABLE_PREFIX . 'room_notes';

        $note_id = intval($_POST['note_id']);

        $wpdb->update(
            $table,
            array(
                'is_resolved' => 1,
                'resolved_by' => get_current_user_id(),
                'resolved_at' => current_time('mysql')
            ),
            array('id' => $note_id),
            array('%d', '%d', '%s'),
            array('%d')
        );

        wp_send_json_success(array('message' => 'Note resolved'));
    }

    /**
     * Get tasks.
     */
    public function get_tasks() {
        $this->verify_request('housekeeping.view_rooms');

        global $wpdb;
        $table = $wpdb->prefix . HKA_TABLE_PREFIX . 'tasks';

        $status = sanitize_text_field($_POST['status'] ?? 'pending');

        $tasks = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table} WHERE status = %s ORDER BY priority DESC, due_date ASC",
            $status
        ));

        wp_send_json_success(array('tasks' => $tasks));
    }

    /**
     * Create a task.
     */
    public function create_task() {
        $this->verify_request('housekeeping.assign_rooms');

        global $wpdb;
        $table = $wpdb->prefix . HKA_TABLE_PREFIX . 'tasks';

        $wpdb->insert(
            $table,
            array(
                'task_title' => sanitize_text_field($_POST['title']),
                'task_description' => sanitize_textarea_field($_POST['description'] ?? ''),
                'task_type' => sanitize_text_field($_POST['task_type'] ?? 'general'),
                'room_number' => sanitize_text_field($_POST['room_number'] ?? ''),
                'assigned_to' => intval($_POST['assigned_to'] ?? 0),
                'created_by' => get_current_user_id(),
                'created_at' => current_time('mysql'),
                'due_date' => sanitize_text_field($_POST['due_date'] ?? ''),
                'priority' => sanitize_text_field($_POST['priority'] ?? 'normal')
            ),
            array('%s', '%s', '%s', '%s', '%d', '%d', '%s', '%s', '%s')
        );

        wp_send_json_success(array(
            'message' => 'Task created successfully',
            'task_id' => $wpdb->insert_id
        ));
    }

    /**
     * Complete a task.
     */
    public function complete_task() {
        $this->verify_request('housekeeping.complete_tasks');

        global $wpdb;
        $table = $wpdb->prefix . HKA_TABLE_PREFIX . 'tasks';

        $task_id = intval($_POST['task_id']);

        $wpdb->update(
            $table,
            array(
                'status' => 'completed',
                'completed_by' => get_current_user_id(),
                'completed_at' => current_time('mysql')
            ),
            array('id' => $task_id),
            array('%s', '%d', '%s'),
            array('%d')
        );

        wp_send_json_success(array('message' => 'Task completed'));
    }

    /**
     * Get checklist for a room.
     */
    public function get_checklist() {
        $this->verify_request('housekeeping.view_checklist');

        global $wpdb;
        $table = $wpdb->prefix . HKA_TABLE_PREFIX . 'cleaning_checklists';

        $room_number = sanitize_text_field($_POST['room_number']);
        $date = sanitize_text_field($_POST['date'] ?? current_time('Y-m-d'));

        $checklist = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table} WHERE room_number = %s AND checklist_date = %s",
            $room_number,
            $date
        ));

        if (!$checklist) {
            // Return default checklist
            $default_items = json_decode(get_option('hka_default_checklist_items', '[]'), true);
            wp_send_json_success(array(
                'checklist' => null,
                'items' => $default_items
            ));
        } else {
            wp_send_json_success(array(
                'checklist' => $checklist,
                'items' => json_decode($checklist->items_json, true)
            ));
        }
    }

    /**
     * Save checklist.
     */
    public function save_checklist() {
        $this->verify_request('housekeeping.complete_tasks');

        global $wpdb;
        $table = $wpdb->prefix . HKA_TABLE_PREFIX . 'cleaning_checklists';

        $room_number = sanitize_text_field($_POST['room_number']);
        $date = sanitize_text_field($_POST['date'] ?? current_time('Y-m-d'));
        $items = json_encode($_POST['items']);

        // Check if checklist exists
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM {$table} WHERE room_number = %s AND checklist_date = %s",
            $room_number,
            $date
        ));

        if ($existing) {
            $wpdb->update(
                $table,
                array(
                    'items_json' => $items,
                    'completed_by' => get_current_user_id(),
                    'completed_at' => current_time('mysql')
                ),
                array('id' => $existing->id),
                array('%s', '%d', '%s'),
                array('%d')
            );
        } else {
            $wpdb->insert(
                $table,
                array(
                    'room_number' => $room_number,
                    'checklist_date' => $date,
                    'items_json' => $items,
                    'completed_by' => get_current_user_id(),
                    'completed_at' => current_time('mysql')
                ),
                array('%s', '%s', '%s', '%d', '%s')
            );
        }

        wp_send_json_success(array('message' => 'Checklist saved successfully'));
    }
}
