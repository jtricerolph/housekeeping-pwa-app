/**
 * Room Status Module - Frontend
 *
 * @package Housekeeping_PWA_App
 */

(function($) {
    'use strict';

window.HKAModule_room_status = {
    rooms: [],
    filteredRooms: [],
    currentFilter: 'all',

    /**
     * Initialize module.
     */
    init(date) {
        this.loadRoomStatus(date);
    },

    /**
     * Load room status from server.
     */
    async loadRoomStatus(date) {
        try {
            const response = await HKApp.ajax('get_room_status', { date });

            if (response.success) {
                this.rooms = response.data.rooms || [];
                this.filteredRooms = this.rooms;
                this.render();
            } else {
                const errorMsg = response.data?.message || 'Failed to load room status';
                console.error('Room status error:', errorMsg);
                HKApp.toast(errorMsg, 'error');
            }
        } catch (error) {
            console.error('Error loading room status:', error);
            // Show actual error details
            const errorMsg = error.responseJSON?.data?.message || error.statusText || 'Error loading room status';
            console.log('Error details:', {
                status: error.status,
                statusText: error.statusText,
                responseJSON: error.responseJSON,
                responseText: error.responseText
            });
            HKApp.toast(errorMsg, 'error');
        }
    },

    /**
     * Render module content.
     */
    render() {
        const $container = $('.hka-tab-content');

        let html = '';

        // Filters
        html += this.renderFilters();

        // Room list
        html += '<div class="hka-room-list">';

        if (this.filteredRooms.length === 0) {
            html += '<div class="hka-empty-state">No rooms found</div>';
        } else {
            this.filteredRooms.forEach(room => {
                html += this.renderRoomCard(room);
            });
        }

        html += '</div>';

        $container.html(html);

        // Bind events
        this.bindEvents();
    },

    /**
     * Render filters.
     */
    renderFilters() {
        const filters = [
            { id: 'all', label: 'All Rooms' },
            { id: 'dirty', label: 'Dirty' },
            { id: 'clean', label: 'Clean' },
            { id: 'checkout', label: 'Checkout' },
            { id: 'priority', label: 'Priority' }
        ];

        let html = '<div class="hka-filters">';

        filters.forEach(filter => {
            const active = this.currentFilter === filter.id ? 'active' : '';
            html += `
                <button class="hka-filter-btn ${active}" data-filter="${filter.id}">
                    ${filter.label}
                </button>
            `;
        });

        html += '</div>';

        return html;
    },

    /**
     * Render room card.
     */
    renderRoomCard(room) {
        const statusColor = hkaData.roomStatusColors[room.housekeeping_status] || '#999';
        const notesIndicator = room.notes_count > 0 ? `<span class="hka-notes-badge">${room.notes_count}</span>` : '';

        return `
            <div class="hka-room-card" data-room="${room.room_number}">
                <div class="hka-room-header">
                    <div class="hka-room-number">
                        <span class="hka-status-indicator" style="background-color: ${statusColor}"></span>
                        ${room.room_number}
                    </div>
                    <div class="hka-room-type">${room.room_type}</div>
                </div>

                <div class="hka-room-status">
                    <span class="hka-occupancy-status hka-status-${room.occupancy_status}">
                        ${this.formatOccupancyStatus(room.occupancy_status)}
                    </span>
                    <span class="hka-housekeeping-status">
                        ${this.formatHousekeepingStatus(room.housekeeping_status)}
                    </span>
                </div>

                ${room.guest_name ? `<div class="hka-guest-info">${room.guest_name}</div>` : ''}

                <div class="hka-room-actions">
                    <button class="hka-btn hka-btn-sm hka-update-status" data-room="${room.room_number}">
                        Update Status
                    </button>
                    <button class="hka-btn hka-btn-sm hka-view-details" data-room="${room.room_number}">
                        Details ${notesIndicator}
                    </button>
                </div>
            </div>
        `;
    },

    /**
     * Bind event listeners.
     */
    bindEvents() {
        // Filter buttons
        $('.hka-filter-btn').on('click', (e) => {
            const filter = $(e.currentTarget).data('filter');
            this.applyFilter(filter);
        });

        // Update status buttons
        $('.hka-update-status').on('click', (e) => {
            const room = $(e.currentTarget).data('room');
            this.showStatusDialog(room);
        });

        // View details buttons
        $('.hka-view-details').on('click', (e) => {
            const room = $(e.currentTarget).data('room');
            this.showDetailsDialog(room);
        });
    },

    /**
     * Apply filter to room list.
     */
    applyFilter(filter) {
        this.currentFilter = filter;

        if (filter === 'all') {
            this.filteredRooms = this.rooms;
        } else if (filter === 'priority') {
            this.filteredRooms = this.rooms.filter(r => r.priority === 'high' || r.priority === 'urgent');
        } else {
            this.filteredRooms = this.rooms.filter(r => r.housekeeping_status === filter);
        }

        this.render();
    },

    /**
     * Show status update dialog.
     */
    showStatusDialog(roomNumber) {
        const statuses = ['dirty', 'clean', 'inspected', 'out_of_order'];

        let html = `
            <div class="hka-dialog-overlay">
                <div class="hka-dialog">
                    <div class="hka-dialog-header">
                        <h3>Update Room ${roomNumber}</h3>
                        <button class="hka-dialog-close">×</button>
                    </div>
                    <div class="hka-dialog-body">
                        <div class="hka-status-options">
        `;

        statuses.forEach(status => {
            const color = hkaData.roomStatusColors[status] || '#999';
            html += `
                <button class="hka-status-option" data-status="${status}" style="border-color: ${color}">
                    <span class="hka-status-color" style="background-color: ${color}"></span>
                    ${this.formatHousekeepingStatus(status)}
                </button>
            `;
        });

        html += `
                        </div>
                    </div>
                </div>
            </div>
        `;

        $('body').append(html);

        // Bind events
        $('.hka-dialog-close, .hka-dialog-overlay').on('click', (e) => {
            if (e.target === e.currentTarget) {
                $('.hka-dialog-overlay').remove();
            }
        });

        $('.hka-status-option').on('click', async (e) => {
            const status = $(e.currentTarget).data('status');
            await this.updateRoomStatus(roomNumber, status);
            $('.hka-dialog-overlay').remove();
        });
    },

    /**
     * Update room status.
     */
    async updateRoomStatus(roomNumber, status) {
        try {
            const response = await HKApp.ajax('update_room_status', {
                room_number: roomNumber,
                status: status
            });

            if (response.success) {
                HKApp.toast('Room status updated', 'success');
                this.loadRoomStatus(HKApp.currentDate);
            } else {
                HKApp.toast(response.data.message || 'Update failed', 'error');
            }
        } catch (error) {
            console.error('Error updating room status:', error);
            HKApp.toast('Error updating status', 'error');
        }
    },

    /**
     * Show room details dialog.
     */
    showDetailsDialog(roomNumber) {
        // TODO: Implement room details with notes and checklist
        HKApp.toast('Room details coming soon', 'info');
    },

    /**
     * Format occupancy status for display.
     */
    formatOccupancyStatus(status) {
        const labels = {
            'vacant': 'Vacant',
            'occupied': 'Occupied',
            'checkout': 'Checkout'
        };
        return labels[status] || status;
    },

    /**
     * Format housekeeping status for display.
     */
    formatHousekeepingStatus(status) {
        const labels = {
            'dirty': 'Dirty',
            'clean': 'Clean',
            'inspected': 'Inspected',
            'out_of_order': 'Out of Order'
        };
        return labels[status] || status;
    }
};

})(jQuery);
