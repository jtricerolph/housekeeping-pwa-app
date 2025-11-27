<?php
/**
 * Standalone template - Clean app experience without WordPress theme elements.
 *
 * @package Housekeeping_PWA_App
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get user's available modules
$user_modules = hka()->modules->get_user_modules();
$user_permissions = wfa_get_user_permissions();
$current_user = wp_get_current_user();
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5, user-scalable=yes">
    <meta name="theme-color" content="#2196f3">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="Housekeeping">

    <title><?php echo esc_html(get_bloginfo('name')); ?> - Housekeeping</title>

    <!-- PWA Meta Tags -->
    <link rel="manifest" href="<?php echo home_url('/housekeeping-manifest.json'); ?>">
    <link rel="apple-touch-icon" href="<?php echo HKA_PLUGIN_URL; ?>assets/icons/icon-192x192.png">

    <?php
    // Enqueue WordPress scripts and styles (includes jQuery)
    wp_head();
    ?>

    <style>
        /* Reset body styles to remove theme interference */
        body {
            margin: 0 !important;
            padding: 0 !important;
            background: #f5f5f5 !important;
            overflow-x: hidden;
        }

        /* Hide any theme elements that might leak through */
        body > *:not(#hka-standalone-app) {
            display: none !important;
        }

        #hka-standalone-app {
            display: block !important;
            min-height: 100vh;
        }
    </style>
</head>
<body <?php body_class('hka-standalone'); ?>>

<div id="hka-standalone-app">
    <!-- App Header -->
    <div class="hka-header">
        <div class="hka-header-title">
            <h1>Housekeeping</h1>
            <span class="hka-current-date"></span>
        </div>
        <div class="hka-header-actions">
            <button class="hka-refresh-btn" aria-label="Refresh">
                <span class="dashicons dashicons-update"></span>
            </button>
            <button class="hka-menu-btn" aria-label="Menu">
                <span class="dashicons dashicons-menu"></span>
            </button>
        </div>
    </div>

    <!-- Sidebar Menu -->
    <div class="hka-sidebar">
        <div class="hka-sidebar-overlay"></div>
        <div class="hka-sidebar-content">
            <div class="hka-sidebar-header">
                <h3>Menu</h3>
                <button class="hka-sidebar-close" aria-label="Close Menu">×</button>
            </div>

            <div class="hka-sidebar-user">
                <div class="hka-user-avatar">
                    <?php echo get_avatar($current_user->ID, 48); ?>
                </div>
                <div class="hka-user-info">
                    <strong><?php echo esc_html($current_user->display_name); ?></strong>
                    <span><?php echo esc_html($current_user->user_email); ?></span>
                </div>
            </div>

            <div class="hka-sidebar-modules">
                <h4>Modules</h4>
                <!-- Populated by JavaScript -->
            </div>

            <div class="hka-sidebar-footer">
                <a href="<?php echo wp_logout_url(home_url()); ?>" class="hka-logout-btn">
                    <span class="dashicons dashicons-exit"></span>
                    Logout
                </a>
            </div>
        </div>
    </div>

    <!-- Date Picker -->
    <div class="hka-date-picker">
        <button class="hka-date-prev" aria-label="Previous Day">
            <span class="dashicons dashicons-arrow-left-alt2"></span>
        </button>
        <input type="date" class="hka-date-input" value="<?php echo esc_attr(current_time('Y-m-d')); ?>">
        <button class="hka-date-next" aria-label="Next Day">
            <span class="dashicons dashicons-arrow-right-alt2"></span>
        </button>
        <button class="hka-date-today">Today</button>
    </div>

    <!-- Module Navigation -->
    <div class="hka-module-nav" style="display: none;">
        <!-- Populated by JavaScript -->
    </div>

    <!-- Module Container -->
    <div class="hka-module-container">
        <!-- Module content loaded here -->
        <div class="hka-loading">
            <div class="hka-spinner"></div>
            <p>Loading...</p>
        </div>
    </div>

    <!-- Install PWA Prompt -->
    <div class="hka-install-prompt" style="display: none;">
        <div class="hka-install-prompt-content">
            <span class="dashicons dashicons-smartphone"></span>
            <div>
                <strong>Install Housekeeping App</strong>
                <p>Add to home screen for quick access</p>
            </div>
            <button class="hka-install-btn">Install</button>
            <button class="hka-install-dismiss" aria-label="Dismiss">×</button>
        </div>
    </div>

    <!-- Offline Indicator -->
    <div class="hka-offline-indicator" style="display: none;">
        <span class="dashicons dashicons-warning"></span>
        You are offline
    </div>
</div>

<?php wp_footer(); ?>

</body>
</html>
