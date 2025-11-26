<?php
/**
 * Module registry and management.
 *
 * @package Housekeeping_PWA_App
 */

if (!defined('ABSPATH')) {
    exit;
}

class HKA_Modules {

    /**
     * Registered modules.
     */
    private static $modules = array();

    /**
     * Register a module.
     *
     * @param object $module Module instance.
     */
    public function register_module($module) {
        if (!method_exists($module, 'get_config')) {
            return new WP_Error('invalid_module', 'Module must have get_config() method');
        }

        $config = $module->get_config();

        if (empty($config['id'])) {
            return new WP_Error('invalid_module_id', 'Module must have an ID');
        }

        self::$modules[$config['id']] = array(
            'instance' => $module,
            'config' => $config
        );

        return true;
    }

    /**
     * Get all registered modules.
     *
     * @return array
     */
    public static function get_all_modules() {
        return self::$modules;
    }

    /**
     * Get modules available to current user based on permissions.
     *
     * @param int $user_id Optional user ID. Defaults to current user.
     * @return array
     */
    public function get_user_modules($user_id = null) {
        if (null === $user_id) {
            $user_id = get_current_user_id();
        }

        if (!$user_id) {
            return array();
        }

        $available_modules = array();

        foreach (self::$modules as $module_id => $module_data) {
            $config = $module_data['config'];

            // Check if module has permission requirements
            if (!empty($config['permissions'])) {
                $has_access = false;

                // User needs at least ONE of the module permissions
                foreach ($config['permissions'] as $permission) {
                    if (wfa_user_can($permission, $user_id)) {
                        $has_access = true;
                        break;
                    }
                }

                if (!$has_access) {
                    continue; // Skip this module
                }
            }

            // Filter tabs based on permissions
            $filtered_tabs = array();
            if (!empty($config['tabs'])) {
                foreach ($config['tabs'] as $tab_id => $tab_config) {
                    // Check tab-level permissions
                    if (!empty($tab_config['permissions'])) {
                        $tab_has_access = false;

                        foreach ($tab_config['permissions'] as $permission) {
                            if (wfa_user_can($permission, $user_id)) {
                                $tab_has_access = true;
                                break;
                            }
                        }

                        if (!$tab_has_access) {
                            continue; // Skip this tab
                        }
                    }

                    $filtered_tabs[$tab_id] = $tab_config;
                }
            }

            // Add module with filtered tabs
            $available_modules[$module_id] = array(
                'id' => $config['id'],
                'name' => $config['name'],
                'icon' => $config['icon'] ?? 'list',
                'color' => $config['color'] ?? '#2196f3',
                'tabs' => $filtered_tabs,
                'order' => $config['order'] ?? 100
            );
        }

        // Sort by order
        uasort($available_modules, function($a, $b) {
            return ($a['order'] ?? 100) - ($b['order'] ?? 100);
        });

        return $available_modules;
    }

    /**
     * Get a specific module instance.
     *
     * @param string $module_id Module ID.
     * @return object|null
     */
    public static function get_module($module_id) {
        if (isset(self::$modules[$module_id])) {
            return self::$modules[$module_id]['instance'];
        }
        return null;
    }

    /**
     * Check if a module is registered.
     *
     * @param string $module_id Module ID.
     * @return bool
     */
    public static function has_module($module_id) {
        return isset(self::$modules[$module_id]);
    }
}
