<?php
/**
 * PSR-4 Autoloader for Tweaker
 *
 * @package NabaTech\Tweaker\Core
 */

namespace NabaTech\Tweaker\Core;

/**
 * PSR-4 compliant autoloader
 */
class Autoloader
{
    /**
     * Base namespace
     */
    private const BASE_NAMESPACE = 'NabaTech\\Tweaker\\';

    /**
     * Base directory
     */
    private string $base_dir;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->base_dir = dirname(__DIR__) . '/';
    }

    /**
     * Register autoloader
     */
    public function register(): void
    {
        spl_autoload_register([$this, 'load_class']);
    }

    /**
     * Load class file
     *
     * IMPORTANT - WordPress Naming Convention:
     * =========================================
     * WordPress uses lowercase folder names (e.g., 'core/', 'modules/') for consistency
     * with plugin slugs and Linux filesystem conventions. However, PHP namespaces use
     * PascalCase (e.g., 'Core', 'Modules') per PSR standards.
     * 
     * This autoloader bridges the gap by converting the first directory component to
     * lowercase when mapping namespaces to file paths.
     * 
     * Example Mapping:
     * - Namespace: NabaTech\Tweaker\Core\Kernel
     * - File Path: core/Kernel.php (NOT Core/Kernel.php)
     * 
     * This works on both Windows (case-insensitive) and Linux (case-sensitive).
     * DO NOT change this logic without understanding the WordPress conventions!
     *
     * @param string $class Fully qualified class name
     */
    public function load_class(string $class): void
    {
        // Check if class uses our namespace
        if (strpos($class, self::BASE_NAMESPACE) !== 0) {
            return;
        }

        // Remove base namespace
        $relative_class = substr($class, strlen(self::BASE_NAMESPACE));

        // Convert namespace to file path
        $path_parts = explode('\\', $relative_class);
        
        // CRITICAL: Convert first part (Core/Modules) to lowercase for WordPress compatibility
        // This ensures 'Core\Admin\MenuManager' maps to 'core/Admin/MenuManager.php'
        // Folders are lowercase, but subdirectories/files maintain their case
        if (!empty($path_parts[0])) {
            $path_parts[0] = strtolower($path_parts[0]);
        }
        
        $relative_path = implode('/', $path_parts);
        $file = $this->base_dir . $relative_path . '.php';

        // Load file if it exists
        if (file_exists($file)) {
            require_once $file;
        }
    }
}
