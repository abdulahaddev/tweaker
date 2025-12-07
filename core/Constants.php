<?php
/**
 * Tweaker Constants
 *
 * @package NabaTech\Tweaker\Core
 */

namespace NabaTech\Tweaker\Core;

if (!defined('ABSPATH')) {
    exit;
}

if (!defined('NT_PLUGIN_VERSION')) {
    define('NT_PLUGIN_VERSION', '1.0.0');

    // Resolve main plugin file path
    // Assuming this file is in /Core/Constants.php and main file is /tweaker.php
    $main_file = dirname(__DIR__) . '/tweaker.php';
    
    define('NT_PLUGIN_FILE', $main_file);
    define('NT_PLUGIN_DIR', plugin_dir_path($main_file));
    define('NT_PLUGIN_URL', plugin_dir_url($main_file));
    define('NT_PLUGIN_BASENAME', plugin_basename($main_file));

    // Minimum requirements
    define('NT_MIN_PHP_VERSION', '8.1.0');
    define('NT_MIN_WP_VERSION', '6.9');
}
