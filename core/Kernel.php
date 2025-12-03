<?php
/**
 * Tweaker Kernel - Core module management and lifecycle
 *
 * @package NabaTech\Tweaker\Core
 */

namespace NabaTech\Tweaker\Core;

/**
 * Singleton kernel class
 */
class Kernel
{
    /**
     * Singleton instance
     */
    private static ?self $instance = null;

    /**
     * Module loader instance
     */
    private ?ModuleLoader $module_loader = null;

    /**
     * Admin menu manager
     */
    private ?Admin\MenuManager $menu_manager = null;

    /**
     * Assets manager
     */
    private ?Assets $assets_manager = null;

    /**
     * Loaded modules
     */
    private array $modules = [];

    /**
     * Get singleton instance
     */
    public static function get_instance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Private constructor (singleton)
     */
    private function __construct()
    {
        // Prevent direct instantiation
    }

    /**
     * Initialize kernel
     */
    public function init(): void
    {
        // Load module loader
        $this->module_loader = new ModuleLoader();

        // Discover and register modules
        $this->modules = $this->module_loader->discover_modules();

        // Initialize admin
        if (is_admin()) {
            $this->init_admin();
        }

        // Activate modules
        foreach ($this->modules as $module) {
            $module->register_hooks();
        }

        // Hook to auto-disable modules when dependencies are deactivated
        add_action('deactivated_plugin', [$this, 'check_module_dependencies']);

        // Fire kernel initialization hook
        do_action('nt_kernel_initialized', $this);
    }

    /**
     * Check and auto-disable modules when dependencies are deactivated
     *
     * @param string $plugin The plugin that was deactivated
     */
    public function check_module_dependencies(string $plugin): void
    {
        // Check if WooCommerce was deactivated
        if (strpos($plugin, 'woocommerce') !== false) {
            $this->auto_disable_dependent_modules('woocommerce');
        }
    }

    /**
     * Auto-disable modules that depend on a specific plugin
     *
     * @param string $dependency The dependency that was deactivated
     */
    private function auto_disable_dependent_modules(string $dependency): void
    {
        $enabled_modules = get_option('nt_enabled_modules', []);
        $modules_dir = NT_PLUGIN_DIR . 'modules/';
        $disabled = [];

        foreach ($enabled_modules as $module_id) {
            // Find module manifest
            $module_dirs = glob($modules_dir . '*', GLOB_ONLYDIR);
            foreach ($module_dirs as $module_dir) {
                $manifest_file = $module_dir . '/module.json';
                if (file_exists($manifest_file)) {
                    $manifest = json_decode(file_get_contents($manifest_file), true);
                    if ($manifest && $manifest['id'] === $module_id) {
                        // Check if this module depends on the deactivated plugin
                        if (isset($manifest['requires'][$dependency])) {
                            $disabled[] = $module_id;
                        }
                    }
                }
            }
        }

        if (!empty($disabled)) {
            // Remove disabled modules from enabled list
            $enabled_modules = array_diff($enabled_modules, $disabled);
            update_option('nt_enabled_modules', array_values($enabled_modules));

            // Log the auto-disable
            nt_log("Auto-disabled modules due to {$dependency} deactivation: " . implode(', ', $disabled));
        }
    }

    /**
     * Initialize admin interface
     */
    private function init_admin(): void
    {
        $this->menu_manager = new Admin\MenuManager($this->modules);
        $this->assets_manager = new Assets();

        add_action('admin_menu', [$this->menu_manager, 'register_menus']);
        add_action('admin_enqueue_scripts', [$this->assets_manager, 'enqueue_admin_assets']);
    }

    /**
     * Plugin activation
     */
    public function activate(): void
    {
        // Create capabilities
        $this->create_capabilities();

        // Create module loader for activation (kernel hasn't been initialized yet)
        $module_loader = new ModuleLoader();
        $modules = $module_loader->discover_modules();

        // Run module installations
        foreach ($modules as $module) {
            $module->install();
            $module->activate();
        }

        // Set activation flag
        update_option('nt_activated', true);
        update_option('nt_version', NT_PLUGIN_VERSION);

        nt_log('Tweaker activated successfully');
    }

    /**
     * Plugin deactivation
     */
    public function deactivate(): void
    {
        // Run module deactivations (if modules were loaded)
        if (!empty($this->modules)) {
            foreach ($this->modules as $module) {
                $module->deactivate();
            }
        }

        delete_option('nt_activated');

        nt_log('Tweaker deactivated');
    }

    /**
     * Plugin uninstall
     */
    public function uninstall(): void
    {
        // Discover modules for uninstall
        $module_loader = new ModuleLoader();
        $modules = $module_loader->discover_modules();

        // Run module uninstalls
        foreach ($modules as $module) {
            $module->uninstall();
        }

        // Remove capabilities
        $this->remove_capabilities();

        // Remove all nt_* options
        global $wpdb;
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE 'nt_%'");

        nt_log('Tweaker uninstalled completely');
    }

    /**
     * Create plugin capabilities
     */
    private function create_capabilities(): void
    {
        $admin_role = get_role('administrator');
        if ($admin_role) {
            $admin_role->add_cap('nt_manage_tweaker');
            $admin_role->add_cap('nt_manage_modules');
            $admin_role->add_cap('nt_manage_checkout_fields');
            $admin_role->add_cap('nt_manage_object_cache');
        }
    }

    /**
     * Remove plugin capabilities
     */
    private function remove_capabilities(): void
    {
        $admin_role = get_role('administrator');
        if ($admin_role) {
            $admin_role->remove_cap('nt_manage_tweaker');
            $admin_role->remove_cap('nt_manage_modules');
            $admin_role->remove_cap('nt_manage_checkout_fields');
            $admin_role->remove_cap('nt_manage_object_cache');
        }
    }

    /**
     * Get loaded modules
     */
    public function get_modules(): array
    {
        return $this->modules;
    }

    /**
     * Get specific module by ID
     */
    public function get_module(string $module_id): ?object
    {
        foreach ($this->modules as $module) {
            if ($module->get_id() === $module_id) {
                return $module;
            }
        }
        return null;
    }
}
