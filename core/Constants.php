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

    // Define core directory path (eliminates cross-platform case-sensitivity issues)
    define('NT_CORE_DIR', __DIR__);

    // Resolve main plugin file path
    // This file is in /core/Constants.php and main file is /tweaker.php
    define('NT_PLUGIN_FILE', dirname(__DIR__) . '/tweaker.php');
    define('NT_PLUGIN_DIR', plugin_dir_path(NT_PLUGIN_FILE));
    define('NT_PLUGIN_URL', plugin_dir_url(NT_PLUGIN_FILE));
    define('NT_PLUGIN_BASENAME', plugin_basename(NT_PLUGIN_FILE));

    // Minimum requirements
    define('NT_MIN_PHP_VERSION', '8.1.0');
    define('NT_MIN_WP_VERSION', '6.9');
}
