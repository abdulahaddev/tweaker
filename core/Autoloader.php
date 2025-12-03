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
        $file = $this->base_dir . str_replace('\\', '/', $relative_class) . '.php';

        // Load file if it exists
        if (file_exists($file)) {
            require_once $file;
        }
    }
}
