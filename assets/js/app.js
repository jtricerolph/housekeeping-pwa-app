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
            this.renderModuleNav();
            this.registerServiceWorker();
            this.setupInstallPrompt();
            this.checkOnlineStatus();

            // Load first module
            const firstModule = Object.keys(this.modules)[0];
            if (firstModule) {
                this.loadModule(firstModule);
            }
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

            // Menu button
            $('.hka-menu-btn').on('click', () => this.toggleMenu());

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
         * Render module navigation.
         */
        renderModuleNav() {
            const $nav = $('.hka-module-nav');
            $nav.empty();

            Object.keys(this.modules).forEach(moduleId => {
                const module = this.modules[moduleId];
                const $button = $('<button>')
                    .addClass('hka-module-btn')
                    .attr('data-module', moduleId)
                    .html(`
                        <span class="dashicons dashicons-${module.icon}"></span>
                        <span>${module.name}</span>
                    `)
                    .on('click', () => this.loadModule(moduleId));

                $nav.append($button);
            });

            $nav.show();
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

            window.addEventListener('beforeinstallprompt', (e) => {
                e.preventDefault();
                deferredPrompt = e;

                // Show install prompt
                $('.hka-install-prompt').show();

                $('.hka-install-btn').on('click', async () => {
                    deferredPrompt.prompt();
                    const { outcome } = await deferredPrompt.userChoice;
                    console.log(`User response: ${outcome}`);
                    $('.hka-install-prompt').hide();
                    deferredPrompt = null;
                });

                $('.hka-install-dismiss').on('click', () => {
                    $('.hka-install-prompt').hide();
                });
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
        }
    };

    // Initialize when document is ready
    $(document).ready(() => {
        HKApp.init();
    });

    // Expose to global scope
    window.HKApp = HKApp;

})(jQuery);
