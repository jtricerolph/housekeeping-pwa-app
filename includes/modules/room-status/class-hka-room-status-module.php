<?php
/**
 * Room Status Module
 *
 * Displays room status by date with tasks, notes, and options.
 * Integrates with Newbook for occupancy data.
 *
 * @package Housekeeping_PWA_App
 */

if (!defined('ABSPATH')) {
    exit;
}

class HKA_Room_Status_Module {

    /**
     * Get module configuration.
     *
     * @return array
     */
    public function get_config() {
        return array(
            'id' => 'room_status',
            'name' => 'Room Status',
            'icon' => 'hotel',
            'color' => '#4caf50',
            'order' => 10,
            'permissions' => array('housekeeping.view_rooms'),
            'tabs' => array(
                'daily_list' => array(
                    'name' => 'Daily List',
                    'icon' => 'list',
                    'permissions' => array('housekeeping.view_rooms')
                ),
                'by_status' => array(
                    'name' => 'By Status',
                    'icon' => 'filter_list',
                    'permissions' => array('housekeeping.view_rooms')
                ),
                'assignments' => array(
                    'name' => 'Assignments',
                    'icon' => 'assignment_ind',
                    'permissions' => array('housekeeping.assign_rooms')
                )
            )
        );
    }

    /**
     * Get room list with Newbook occupancy data.
     *
     * @param string $date Date in Y-m-d format.
     * @return array
     */
    public function get_room_list($date) {
        $rooms = $this->get_newbook_rooms($date);
        $statuses = $this->get_housekeeping_statuses($date);
        $notes = $this->get_room_notes_summary($date);

        // Merge data
        $combined = array();
        foreach ($rooms as $room) {
            $room_number = $room['room_number'];

            $combined[$room_number] = array(
                'room_number' => $room_number,
                'room_type' => $room['room_type'] ?? '',
                'occupancy_status' => $room['occupancy_status'] ?? 'vacant',
                'guest_name' => $room['guest_name'] ?? '',
                'checkout_date' => $room['checkout_date'] ?? '',
                'checkin_date' => $room['checkin_date'] ?? '',
                'housekeeping_status' => $statuses[$room_number]['status'] ?? 'dirty',
                'assigned_to' => $statuses[$room_number]['assigned_to'] ?? null,
                'priority' => $statuses[$room_number]['priority'] ?? 'normal',
                'inspection_required' => $statuses[$room_number]['inspection_required'] ?? false,
                'notes_count' => $notes[$room_number] ?? 0,
                'updated_at' => $statuses[$room_number]['updated_at'] ?? null
            );
        }

        return array_values($combined);
    }

    /**
     * Get Newbook room data.
     *
     * @param string $date Date in Y-m-d format.
     * @return array
     */
    private function get_newbook_rooms($date) {
        // Check if Newbook integration is available
        if (!function_exists('get_newbook_room_occupancy')) {
            // Return sample data for testing
            return $this->get_sample_rooms();
        }

        // Get actual Newbook data
        $occupancy_data = get_newbook_room_occupancy($date);

        $rooms = array();
        foreach ($occupancy_data as $booking) {
            $rooms[] = array(
                'room_number' => $booking['room_number'],
                'room_type' => $booking['room_type'] ?? '',
                'occupancy_status' => $this->determine_occupancy_status($booking, $date),
                'guest_name' => $booking['guest_name'] ?? '',
                'checkout_date' => $booking['checkout_date'] ?? '',
                'checkin_date' => $booking['checkin_date'] ?? ''
            );
        }

        return $rooms;
    }

    /**
     * Determine occupancy status for a booking.
     *
     * @param array $booking Booking data.
     * @param string $date Current date.
     * @return string
     */
    private function determine_occupancy_status($booking, $date) {
        $checkin = $booking['checkin_date'] ?? '';
        $checkout = $booking['checkout_date'] ?? '';

        if (empty($checkin) || empty($checkout)) {
            return 'vacant';
        }

        if ($date < $checkin) {
            return 'vacant';
        } elseif ($date === $checkout) {
            return 'checkout';
        } elseif ($date >= $checkin && $date < $checkout) {
            return 'occupied';
        } else {
            return 'vacant';
        }
    }

    /**
     * Get housekeeping statuses for a date.
     *
     * @param string $date Date in Y-m-d format.
     * @return array Keyed by room number.
     */
    private function get_housekeeping_statuses($date) {
        global $wpdb;
        $table = $wpdb->prefix . HKA_TABLE_PREFIX . 'room_status';

        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table} WHERE status_date = %s",
            $date
        ), ARRAY_A);

        $statuses = array();
        foreach ($results as $row) {
            $statuses[$row['room_number']] = $row;
        }

        return $statuses;
    }

    /**
     * Get notes summary for a date.
     *
     * @param string $date Date in Y-m-d format.
     * @return array Keyed by room number with note count.
     */
    private function get_room_notes_summary($date) {
        global $wpdb;
        $table = $wpdb->prefix . HKA_TABLE_PREFIX . 'room_notes';

        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT room_number, COUNT(*) as note_count
             FROM {$table}
             WHERE note_date = %s AND is_resolved = 0
             GROUP BY room_number",
            $date
        ), ARRAY_A);

        $notes = array();
        foreach ($results as $row) {
            $notes[$row['room_number']] = intval($row['note_count']);
        }

        return $notes;
    }

    /**
     * Get sample rooms for testing (when Newbook is not available).
     *
     * @return array
     */
    private function get_sample_rooms() {
        $rooms = array();
        for ($i = 101; $i <= 120; $i++) {
            $rooms[] = array(
                'room_number' => (string) $i,
                'room_type' => $i % 2 === 0 ? 'Deluxe' : 'Standard',
                'occupancy_status' => 'vacant',
                'guest_name' => '',
                'checkout_date' => '',
                'checkin_date' => ''
            );
        }
        return $rooms;
    }

    /**
     * Get staff members for assignment.
     *
     * @return array
     */
    public function get_staff_members() {
        global $wpdb;
        $table = $wpdb->prefix . 'workforce_users';

        // Get users from housekeeping department
        $staff = $wpdb->get_results(
            "SELECT u.id, u.name, u.employee_id
             FROM {$table} u
             INNER JOIN {$wpdb->prefix}workforce_user_departments ud ON u.id = ud.user_id
             INNER JOIN {$wpdb->prefix}workforce_departments d ON ud.department_id = d.id
             WHERE d.name LIKE '%housekeeping%' OR d.name LIKE '%cleaning%'
             ORDER BY u.name ASC",
            ARRAY_A
        );

        if (empty($staff)) {
            // Fallback: get all workforce users with housekeeping permissions
            $staff = $wpdb->get_results(
                "SELECT id, name, employee_id FROM {$table} ORDER BY name ASC",
                ARRAY_A
            );
        }

        return $staff;
    }
}
