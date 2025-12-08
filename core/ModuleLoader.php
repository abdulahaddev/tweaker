<?php
/**
 * Module Discovery and Loading
 *
 * @package NabaTech\Tweaker\Core
 */

namespace NabaTech\Tweaker\Core;

/**
 * Module loader class
 */
class ModuleLoader
{
    /**
     * Modules directory
     */
    private string $modules_dir;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->modules_dir = NT_PLUGIN_DIR . 'modules/';
    }

    /**
     * Discover all valid modules
     *
     * @return array Array of module instances
     */
    public function discover_modules(): array
    {
        $modules = [];

        if (!is_dir($this->modules_dir)) {
            return $modules;
        }

        // Get enabled modules list
        $enabled_modules = get_option('nt_enabled_modules', null);
        
        // If first run, enable all modules by default
        if ($enabled_modules === null) {
            $enabled_modules = $this->initialize_enabled_modules();
        }

        $module_dirs = glob($this->modules_dir . '*', GLOB_ONLYDIR);

        foreach ($module_dirs as $module_dir) {
            $module = $this->load_module($module_dir, $enabled_modules);
            if ($module !== null) {
                $modules[] = $module;
            }
        }

        return $modules;
    }

    /**
     * Initialize enabled modules list on first run
     *
     * @return array List of module IDs to enable
     */
    /**
     * Initialize enabled modules list on first run
     *
     * @return array List of module IDs to enable
     */
    private function initialize_enabled_modules(): array
    {
        // Default to no modules enabled
        $enabled = [];
        
        // Save the empty array to DB so we don't run this check again
        update_option('nt_enabled_modules', $enabled);
        
        return $enabled;
    }

    /**
     * Load a single module
     *
     * @param string $module_dir Module directory path
     * @param array  $enabled_modules List of enabled module IDs
     * @return object|null Module instance or null if invalid/disabled
     */
    private function load_module(string $module_dir, array $enabled_modules): ?object
    {
        $manifest_file = $module_dir . '/module.json';

        // Check for manifest
        if (!file_exists($manifest_file)) {
            return null;
        }

        // Parse manifest
        $manifest = json_decode(file_get_contents($manifest_file), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            tweaker_log("Invalid module.json in: {$module_dir}");
            return null;
        }

        // Check if module is enabled
        $module_id = $manifest['id'] ?? null;
        if (!$module_id || !in_array($module_id, $enabled_modules)) {
            return null; // Skip disabled modules
        }

        // Validate requirements
        if (!$this->validate_requirements($manifest)) {
            return null;
        }

        // Get module class name
        $module_class = $manifest['entry_points']['bootstrap'] ?? null;
        if (!$module_class) {
            return null;
        }

        // Build full class name
        $full_class = "NabaTech\\Tweaker\\Modules\\{$module_class}";

        // Load Module.php
        $module_file = $module_dir . '/Module.php';
        if (!file_exists($module_file)) {
            tweaker_log("Module.php not found in: {$module_dir}");
            return null;
        }

        require_once $module_file;

        // Instantiate module
        if (!class_exists($full_class)) {
            tweaker_log("Module class not found: {$full_class}");
            return null;
        }

        return new $full_class($module_dir, $manifest);
    }

    /**
     * Validate module requirements
     *
     * @param array $manifest Module manifest
     * @return bool True if requirements met
     */
    private function validate_requirements(array $manifest): bool
    {
        $requires = $manifest['requires'] ?? [];

        // Check WordPress version
        if (isset($requires['wordpress'])) {
            global $wp_version;
            $min_wp = str_replace('+', '', $requires['wordpress']);
            if (version_compare($wp_version, $min_wp, '<')) {
                return false;
            }
        }

        // Check PHP version
        if (isset($requires['php'])) {
            $min_php = str_replace('+', '', $requires['php']);
            if (version_compare(PHP_VERSION, $min_php, '<')) {
                return false;
            }
        }

        // Check required plugins
        if (isset($requires['woocommerce'])) {
            if (!class_exists('WooCommerce')) {
                return false;
            }
        }

        // Check PHP extensions
        if (isset($requires['extensions'])) {
            foreach ($requires['extensions'] as $extension) {
                if (!extension_loaded($extension)) {
                    return false;
                }
            }
        }

        return true;
    }
}
