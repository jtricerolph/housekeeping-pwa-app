# Housekeeping PWA App

Progressive Web App for housekeeping operations management. A mobile-first WordPress plugin designed for hotel housekeeping staff to manage room status, tasks, checklists, and notes.

## Features

### Room Status Management
- View daily room list with occupancy status
- Update room cleaning status (dirty, clean, inspected, out of order)
- Filter rooms by status and priority
- Assign rooms to specific staff members
- View guest information and checkout dates

### Notes & Communication
- Add notes to rooms
- Track unresolved issues
- View notes from all staff members (with permission)
- Mark issues as resolved

### Task Management
- Create and assign tasks
- Set priorities and due dates
- Track task completion
- Recurring task support

### Cleaning Checklists
- Standard cleaning checklist per room
- Track checklist completion
- Inspection workflow
- Customizable checklist items

### PWA Features
- Install to home screen
- Offline functionality
- Mobile-optimized interface
- Fast loading and responsive design

## Requirements

- WordPress 6.0+
- PHP 7.4+
- **Workforce Authentication plugin** (required dependency)
- MySQL 5.7+ or MariaDB 10.2+

## Installation

### Method 1: Manual Upload

1. Download the latest release ZIP file
2. Go to WordPress Admin → Plugins → Add New → Upload Plugin
3. Upload the ZIP file and click "Install Now"
4. Activate the plugin
5. Ensure **Workforce Authentication** plugin is active

### Method 2: GitHub Clone

```bash
cd wp-content/plugins/
git clone https://github.com/jtricerolph/housekeeping-pwa-app.git
```

Then activate via WordPress admin.

### Method 3: WP Pusher (Recommended for Auto-Updates)

1. Install WP Pusher plugin
2. Connect your GitHub account
3. Install from repository: `jtricerolph/housekeeping-pwa-app`
4. Enable auto-updates

## Configuration

### 1. Permissions Setup

Navigate to **Workforce Auth → Teams** and assign permissions to housekeeping departments:

**Basic Staff Permissions:**
- `housekeeping.view_rooms` - View room status dashboard
- `housekeeping.update_status` - Update room cleaning status
- `housekeeping.add_notes` - Add notes to rooms
- `housekeeping.view_checklist` - View cleaning checklists
- `housekeeping.complete_tasks` - Mark tasks as complete

**Supervisor Permissions:**
- `housekeeping.assign_rooms` - Assign rooms to staff
- `housekeeping.view_all_notes` - View notes from all staff
- `housekeeping.view_reports` - Access summary reports
- `housekeeping.manage_settings` - Configure app settings

### 2. Default Settings

Go to **Housekeeping → Settings** to configure:

- Default checklist items
- Room status colors
- Integration with Newbook (if available)

### 3. Page Setup

The plugin automatically creates a "Housekeeping" page with the `[housekeeping_pwa_app]` shortcode. Access it at:

```
https://yoursite.com/housekeeping/
```

## Usage

### For Housekeeping Staff

1. **Access the App**
   - Navigate to the Housekeeping page
   - Or install as PWA to home screen

2. **View Room Status**
   - Select date using date picker
   - View all rooms with current status
   - Filter by status (dirty, clean, checkout, etc.)

3. **Update Room Status**
   - Click "Update Status" on any room
   - Select new status (clean, inspected, etc.)
   - Status is saved immediately

4. **Add Notes**
   - Click "Details" on a room
   - Add notes about issues or special requests
   - Mark notes as resolved when fixed

5. **Complete Checklists**
   - Open room details
   - Check off completed items
   - Submit when cleaning is complete

### For Supervisors

1. **Assign Rooms**
   - Go to Assignments tab
   - Select rooms and assign to staff members
   - Set priorities for urgent rooms

2. **View Reports**
   - Access summary reports
   - Track team performance
   - Monitor completion rates

## Integration with Newbook

The app integrates with Newbook property management system to pull:

- Room occupancy status
- Guest names
- Check-in/check-out dates
- Room types

If Newbook integration is not available, the app will use sample data for testing.

## Architecture

### Plugin Structure

```
housekeeping-pwa-app/
├── housekeeping-pwa-app.php      # Main plugin file
├── includes/
│   ├── class-hka-core.php        # Core initialization
│   ├── class-hka-pwa.php         # PWA manifest & service worker
│   ├── class-hka-modules.php     # Module registry
│   ├── class-hka-ajax.php        # AJAX handlers
│   ├── class-hka-activator.php   # Database setup
│   └── modules/
│       └── room-status/          # Room status module
├── assets/
│   ├── js/
│   │   ├── app.js                # Main app JavaScript
│   │   ├── service-worker.js     # PWA service worker
│   │   └── modules/              # Module-specific JS
│   ├── css/
│   │   └── app.css               # Mobile-first styles
│   └── icons/                    # PWA icons
└── templates/
    └── app-page.php              # App template
```

### Module System

The app uses a modular architecture:

```php
// Register a new module
class My_Custom_Module {
    public function get_config() {
        return array(
            'id' => 'my_module',
            'name' => 'My Module',
            'icon' => 'dashboard',
            'permissions' => array('housekeeping.view_rooms'),
            'tabs' => array(...)
        );
    }
}

// Register via hook
add_action('hka_register_modules', function($modules) {
    $modules->register_module(new My_Custom_Module());
});
```

### Permission System

Integrates with Workforce Authentication:

```php
// Check permission
if (wfa_user_can('housekeeping.update_status')) {
    // Allow status update
}

// Get all user permissions
$permissions = wfa_get_user_permissions();
```

## Database Schema

### Tables Created

- `wp_hka_room_status` - Room status tracking
- `wp_hka_room_notes` - Notes and comments
- `wp_hka_cleaning_checklists` - Checklist data
- `wp_hka_tasks` - Task assignments

## Development

### Adding a New Module

1. Create module class in `includes/modules/your-module/`
2. Implement `get_config()` method
3. Register module via `hka_register_modules` action
4. Create JavaScript file in `assets/js/modules/`
5. Add permissions to main plugin file

### AJAX Endpoints

All AJAX endpoints are prefixed with `hka_`:

- `hka_get_room_status`
- `hka_update_room_status`
- `hka_get_room_notes`
- `hka_add_room_note`
- `hka_get_tasks`
- `hka_create_task`

### Coding Standards

- Follow WordPress Coding Standards
- Use consistent prefixing (`HKA_` for classes, `hka_` for functions)
- Document all functions with PHPDoc
- Use nonce verification for all AJAX requests

## Roadmap

- [ ] Push notifications for new assignments
- [ ] Photo attachments for room issues
- [ ] Integration with maintenance requests
- [ ] Advanced analytics and reporting
- [ ] Multi-language support
- [ ] Inventory management module
- [ ] Lost & found tracking

## Support

For issues, feature requests, or questions:

- GitHub Issues: https://github.com/jtricerolph/housekeeping-pwa-app/issues
- Documentation: See plugin admin page

## License

GPL-2.0+

## Credits

Developed by JTR for hotel housekeeping operations.

Integrates with:
- Workforce Authentication plugin
- Newbook PMS (optional)

## Changelog

### 1.0.0 - 2024
- Initial release
- Room status management
- PWA functionality
- Module-based architecture
- Workforce Authentication integration
