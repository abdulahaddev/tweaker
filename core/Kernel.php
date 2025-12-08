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
        
        // Add global hooks for checkout page settings
        $this->register_checkout_hooks();

        // Fire kernel initialization hook
        do_action('tweaker_kernel_initialized', $this);
    }
    
    /**
     * Register checkout page hooks
     */
    private function register_checkout_hooks(): void
    {
        // Only register if WooCommerce is active
        if (!class_exists('WooCommerce')) {
            return;
        }
        
        // Hide shipping section if setting is enabled
        if (get_option('nt_hide_shipping_section', 0) == 1) {
            add_filter('woocommerce_checkout_fields', [$this, 'hide_shipping_fields'], 99999);
            add_filter('woocommerce_ship_to_different_address_checked', '__return_false');
            add_action('woocommerce_after_checkout_billing_form', [$this, 'hide_shipping_option'], 10);
        }
    }
    
    /**
     * Hide "Ship to different address" option via CSS
     */
    public function hide_shipping_option(): void
    {
        echo '<style>.woocommerce-shipping-fields { display: none !important; }</style>';
    }
    
    /**
     * Hide shipping fields from checkout
     *
     * @param array $fields Checkout fields
     * @return array Modified checkout fields
     */
    public function hide_shipping_fields(array $fields): array
    {
        // Remove the entire shipping fields array
        if (isset($fields['shipping'])) {
            unset($fields['shipping']);
        }
        
        return $fields;
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
            tweaker_log("Auto-disabled modules due to {$dependency} deactivation: " . implode(', ', $disabled));
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

        tweaker_log('Tweaker activated successfully');
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

        tweaker_log('Tweaker deactivated');
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
        // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE 'nt_%'");
        // phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

        tweaker_log('Tweaker uninstalled completely');
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
