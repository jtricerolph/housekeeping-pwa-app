/**
 * Housekeeping PWA App - Main Application
 *
 * @package Housekeeping_PWA_App
 */

(function($) {
    'use strict';

    const HKApp = {
        currentDate: new Date().toISOString().split('T')[0],
        currentModule: null,
        currentTab: null,
        modules: {},

        /**
         * Initialize the application.
         */
        init() {
            this.modules = hkaData.modules || {};
            this.bindEvents();
            this.setupDatePicker();
            this.renderSidebarModules();
            this.registerServiceWorker();
            this.setupInstallPrompt();
            this.checkOnlineStatus();

            // Load first module
            const firstModule = Object.keys(this.modules)[0];
            if (firstModule) {
                this.loadModule(firstModule);
            }

            // Debug PWA setup
            this.debugPWA();
        },

        /**
         * Bind event listeners.
         */
        bindEvents() {
            // Date navigation
            $('.hka-date-prev').on('click', () => this.changeDate(-1));
            $('.hka-date-next').on('click', () => this.changeDate(1));
            $('.hka-date-today').on('click', () => this.setToday());
            $('.hka-date-input').on('change', (e) => this.setDate(e.target.value));

            // Refresh button
            $('.hka-refresh-btn').on('click', () => this.refresh());

            // Menu button - opens sidebar
            $('.hka-menu-btn').on('click', () => this.openSidebar());

            // Sidebar close
            $('.hka-sidebar-close, .hka-sidebar-overlay').on('click', () => this.closeSidebar());

            // Online/offline status
            window.addEventListener('online', () => this.updateOnlineStatus(true));
            window.addEventListener('offline', () => this.updateOnlineStatus(false));
        },

        /**
         * Setup date picker.
         */
        setupDatePicker() {
            this.updateDateDisplay();
        },

        /**
         * Update date display.
         */
        updateDateDisplay() {
            const date = new Date(this.currentDate);
            const formatted = date.toLocaleDateString('en-US', {
                weekday: 'short',
                month: 'short',
                day: 'numeric',
                year: 'numeric'
            });
            $('.hka-current-date').text(formatted);
            $('.hka-date-input').val(this.currentDate);
        },

        /**
         * Change date by days offset.
         */
        changeDate(days) {
            const date = new Date(this.currentDate);
            date.setDate(date.getDate() + days);
            this.currentDate = date.toISOString().split('T')[0];
            this.updateDateDisplay();
            this.refresh();
        },

        /**
         * Set date to today.
         */
        setToday() {
            this.currentDate = new Date().toISOString().split('T')[0];
            this.updateDateDisplay();
            this.refresh();
        },

        /**
         * Set specific date.
         */
        setDate(date) {
            this.currentDate = date;
            this.updateDateDisplay();
            this.refresh();
        },

        /**
         * Render sidebar modules.
         */
        renderSidebarModules() {
            const $container = $('.hka-sidebar-modules');

            Object.keys(this.modules).forEach(moduleId => {
                const module = this.modules[moduleId];
                const $button = $('<button>')
                    .addClass('hka-sidebar-module-btn')
                    .attr('data-module', moduleId)
                    .html(`
                        <span class="dashicons dashicons-${module.icon}" style="color: ${module.color}"></span>
                        <span>${module.name}</span>
                    `)
                    .on('click', () => {
                        this.loadModule(moduleId);
                        this.closeSidebar();
                    });

                $container.append($button);
            });
        },

        /**
         * Open sidebar menu.
         */
        openSidebar() {
            $('.hka-sidebar').addClass('open');
            $('body').css('overflow', 'hidden');
        },

        /**
         * Close sidebar menu.
         */
        closeSidebar() {
            $('.hka-sidebar').removeClass('open');
            $('body').css('overflow', '');
        },

        /**
         * Load a module.
         */
        loadModule(moduleId, tabId = null) {
            if (!this.modules[moduleId]) {
                return;
            }

            this.currentModule = moduleId;
            const module = this.modules[moduleId];

            // Update active module button
            $('.hka-module-btn').removeClass('active');
            $(`.hka-module-btn[data-module="${moduleId}"]`).addClass('active');

            // Render module content
            this.renderModuleContent(module, tabId);

            // Trigger module-specific initialization
            if (typeof window[`HKAModule_${moduleId}`] !== 'undefined') {
                window[`HKAModule_${moduleId}`].init(this.currentDate);
            }
        },

        /**
         * Render module content.
         */
        renderModuleContent(module, tabId = null) {
            const $container = $('.hka-module-container');

            // Get first tab if not specified
            if (!tabId && module.tabs) {
                tabId = Object.keys(module.tabs)[0];
            }

            this.currentTab = tabId;

            let html = `<div class="hka-module" data-module="${module.id}">`;

            // Module header
            html += `
                <div class="hka-module-header">
                    <h2>${module.name}</h2>
                </div>
            `;

            // Tabs
            if (module.tabs && Object.keys(module.tabs).length > 1) {
                html += '<div class="hka-tabs">';
                Object.keys(module.tabs).forEach(tid => {
                    const tab = module.tabs[tid];
                    const active = tid === tabId ? 'active' : '';
                    html += `
                        <button class="hka-tab ${active}" data-tab="${tid}">
                            ${tab.name}
                        </button>
                    `;
                });
                html += '</div>';
            }

            // Tab content
            html += `<div class="hka-tab-content" data-tab="${tabId}"></div>`;

            html += '</div>';

            $container.html(html);

            // Bind tab click events
            $('.hka-tab').on('click', (e) => {
                const $tab = $(e.currentTarget);
                const tid = $tab.data('tab');

                $('.hka-tab').removeClass('active');
                $tab.addClass('active');

                this.currentTab = tid;
                this.loadModule(this.currentModule, tid);
            });
        },

        /**
         * Refresh current module.
         */
        refresh() {
            if (this.currentModule) {
                this.loadModule(this.currentModule, this.currentTab);
            }
        },

        /**
         * Toggle menu.
         */
        toggleMenu() {
            $('.hka-module-nav').toggleClass('open');
        },

        /**
         * Make AJAX request.
         */
        ajax(action, data = {}) {
            return $.ajax({
                url: hkaData.ajaxUrl,
                method: 'POST',
                data: {
                    action: `hka_${action}`,
                    nonce: hkaData.nonce,
                    date: this.currentDate,
                    ...data
                }
            });
        },

        /**
         * Show toast notification.
         */
        toast(message, type = 'info') {
            const $toast = $('<div>')
                .addClass(`hka-toast hka-toast-${type}`)
                .text(message)
                .appendTo('body');

            setTimeout(() => {
                $toast.addClass('show');
            }, 10);

            setTimeout(() => {
                $toast.removeClass('show');
                setTimeout(() => $toast.remove(), 300);
            }, 3000);
        },

        /**
         * Register service worker.
         */
        registerServiceWorker() {
            if ('serviceWorker' in navigator) {
                navigator.serviceWorker.register('/service-worker.js')
                    .then(registration => {
                        console.log('Service Worker registered:', registration);
                    })
                    .catch(error => {
                        console.log('Service Worker registration failed:', error);
                    });
            }
        },

        /**
         * Setup PWA install prompt.
         */
        setupInstallPrompt() {
            let deferredPrompt;

            // Check if already installed
            if (window.matchMedia('(display-mode: standalone)').matches) {
                console.log('App is already installed');
                return;
            }

            // Detect iOS
            const isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;

            // Show iOS instructions if on iOS
            if (isIOS) {
                // Show iOS-specific install prompt after a delay
                setTimeout(() => {
                    const $prompt = $('.hka-install-prompt');
                    $prompt.find('strong').text('Add to Home Screen');
                    $prompt.find('p').html('Tap <span class="dashicons dashicons-share-alt"></span> then "Add to Home Screen"');
                    $prompt.find('.hka-install-btn').hide();
                    $prompt.show();
                }, 3000);

                $('.hka-install-dismiss').on('click', () => {
                    $('.hka-install-prompt').hide();
                });
                return;
            }

            // Android/Chrome install prompt
            window.addEventListener('beforeinstallprompt', (e) => {
                console.log('beforeinstallprompt fired');
                e.preventDefault();
                deferredPrompt = e;

                // Show install prompt
                $('.hka-install-prompt').show();

                $('.hka-install-btn').off('click').on('click', async () => {
                    if (deferredPrompt) {
                        deferredPrompt.prompt();
                        const { outcome } = await deferredPrompt.userChoice;
                        console.log(`User response: ${outcome}`);
                        $('.hka-install-prompt').hide();
                        deferredPrompt = null;
                    }
                });

                $('.hka-install-dismiss').on('click', () => {
                    $('.hka-install-prompt').hide();
                });
            });

            // Log when app is installed
            window.addEventListener('appinstalled', () => {
                console.log('PWA was installed');
                $('.hka-install-prompt').hide();
            });
        },

        /**
         * Check online status.
         */
        checkOnlineStatus() {
            this.updateOnlineStatus(navigator.onLine);
        },

        /**
         * Update online status indicator.
         */
        updateOnlineStatus(isOnline) {
            if (isOnline) {
                $('.hka-offline-indicator').hide();
            } else {
                $('.hka-offline-indicator').show();
            }
        },

        /**
         * Debug PWA functionality.
         */
        async debugPWA() {
            console.log('=== PWA Debug Starting ===');

            // Create panel immediately with loading state
            const $panel = $('<div>')
                .attr('id', 'hka-debug-panel')
                .css({
                    position: 'fixed',
                    bottom: '10px',
                    right: '10px',
                    background: 'rgba(0,0,0,0.95)',
                    color: '#fff',
                    padding: '15px',
                    borderRadius: '8px',
                    fontSize: '12px',
                    maxWidth: '90%',
                    width: '300px',
                    maxHeight: '80vh',
                    overflow: 'auto',
                    zIndex: 9999,
                    fontFamily: 'monospace',
                    lineHeight: '1.5',
                    boxShadow: '0 4px 12px rgba(0,0,0,0.5)'
                })
                .html('<div style="color: #4caf50;"><strong>PWA Debug</strong><br>Loading...</div>');

            $('body').append($panel);
            console.log('Debug panel created and appended');

            const debugInfo = {
                protocol: window.location.protocol,
                isHTTPS: window.location.protocol === 'https:',
                isInstalled: window.matchMedia('(display-mode: standalone)').matches,
                hasServiceWorker: 'serviceWorker' in navigator,
                userAgent: navigator.userAgent,
                isIOS: /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream,
                isAndroid: /Android/.test(navigator.userAgent),
                isChrome: /Chrome/.test(navigator.userAgent),
                manifestUrl: $('link[rel="manifest"]').attr('href'),
                swRegistration: null,
                manifestData: null,
                errors: []
            };

            console.log('Protocol:', debugInfo.protocol);
            console.log('Is HTTPS:', debugInfo.isHTTPS);
            console.log('Is Installed:', debugInfo.isInstalled);
            console.log('Has SW Support:', debugInfo.hasServiceWorker);
            console.log('Is iOS:', debugInfo.isIOS);
            console.log('Is Android:', debugInfo.isAndroid);
            console.log('Manifest URL:', debugInfo.manifestUrl);

            // Check service worker registration
            if ('serviceWorker' in navigator) {
                try {
                    const registration = await navigator.serviceWorker.getRegistration();
                    if (registration) {
                        debugInfo.swRegistration = {
                            active: !!registration.active,
                            installing: !!registration.installing,
                            waiting: !!registration.waiting,
                            scope: registration.scope
                        };
                        console.log('SW Registration:', debugInfo.swRegistration);
                    } else {
                        debugInfo.errors.push('Service worker not registered');
                        console.warn('Service worker not registered');
                    }
                } catch (error) {
                    debugInfo.errors.push('SW check error: ' + error.message);
                    console.error('SW check error:', error);
                }
            }

            // Check manifest
            if (debugInfo.manifestUrl) {
                try {
                    const response = await fetch(debugInfo.manifestUrl);
                    if (response.ok) {
                        debugInfo.manifestData = await response.json();
                        console.log('Manifest loaded:', debugInfo.manifestData);
                    } else {
                        debugInfo.errors.push('Manifest HTTP ' + response.status);
                        console.error('Manifest failed to load:', response.status);
                    }
                } catch (error) {
                    debugInfo.errors.push('Manifest fetch error: ' + error.message);
                    console.error('Manifest fetch error:', error);
                }
            } else {
                debugInfo.errors.push('Manifest URL not found');
            }

            // Update debug panel with results
            this.updateDebugPanel($panel, debugInfo);
        },

        /**
         * Update debug panel with results.
         */
        updateDebugPanel($panel, info) {
            const statusColor = info.errors.length === 0 ? '#4caf50' : '#ff9800';

            let html = `<div style="margin-bottom: 10px; padding-bottom: 10px; border-bottom: 1px solid #444;">
                <strong style="color: ${statusColor};">PWA Debug Info</strong>
                <button id="hka-debug-close" style="float: right; background: none; border: none; color: #fff; cursor: pointer; font-size: 16px;">×</button>
            </div>`;

            html += `<div style="margin-bottom: 8px;">
                <strong>Protocol:</strong> ${info.protocol}<br>
                <strong>HTTPS:</strong> ${info.isHTTPS ? '✓' : '✗'}<br>
                <strong>Installed:</strong> ${info.isInstalled ? '✓' : '✗'}<br>
                <strong>SW Support:</strong> ${info.hasServiceWorker ? '✓' : '✗'}<br>
                <strong>Platform:</strong> ${info.isIOS ? 'iOS' : info.isAndroid ? 'Android' : 'Other'}<br>
            </div>`;

            if (info.swRegistration) {
                html += `<div style="margin-bottom: 8px; padding-top: 8px; border-top: 1px solid #444;">
                    <strong>Service Worker:</strong><br>
                    Active: ${info.swRegistration.active ? '✓' : '✗'}<br>
                    Installing: ${info.swRegistration.installing ? 'Yes' : 'No'}<br>
                    Scope: ${info.swRegistration.scope}
                </div>`;
            }

            if (info.manifestData) {
                html += `<div style="margin-bottom: 8px; padding-top: 8px; border-top: 1px solid #444;">
                    <strong>Manifest:</strong> ✓ Loaded<br>
                    Name: ${info.manifestData.name || 'N/A'}<br>
                    Icons: ${info.manifestData.icons?.length || 0}
                </div>`;
            }

            if (info.errors.length > 0) {
                html += `<div style="margin-top: 8px; padding-top: 8px; border-top: 1px solid #444; color: #ff9800;">
                    <strong>Issues:</strong><br>
                    ${info.errors.map(err => `• ${err}`).join('<br>')}
                </div>`;
            }

            if (info.isIOS) {
                html += `<div style="margin-top: 8px; padding-top: 8px; border-top: 1px solid #444; color: #2196f3;">
                    <strong>iOS Note:</strong><br>
                    Use Safari Share → Add to Home Screen
                </div>`;
            }

            $panel.html(html);

            // Close button
            $('#hka-debug-close').on('click', () => {
                $panel.fadeOut(() => $panel.remove());
            });

            // Auto-hide after 15 seconds
            setTimeout(() => {
                if ($panel.length) {
                    $panel.fadeOut(() => $panel.remove());
                }
            }, 15000);
        }
    };

    // Initialize when document is ready
    $(document).ready(() => {
        HKApp.init();
    });

    // Expose to global scope
    window.HKApp = HKApp;

})(jQuery);
