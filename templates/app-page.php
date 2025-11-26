<?php
/**
 * Main app page template.
 *
 * @package Housekeeping_PWA_App
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div id="hka-app" class="hka-app">
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

<style>
/* Critical inline styles for initial render */
.hka-app {
    max-width: 1200px;
    margin: 0 auto;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
}

.hka-loading {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 3rem;
}

.hka-spinner {
    border: 3px solid #f3f3f3;
    border-top: 3px solid #2196f3;
    border-radius: 50%;
    width: 40px;
    height: 40px;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
</style>
