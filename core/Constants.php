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

if (!defined('TWEAKER_PLUGIN_VERSION')) {
    define('TWEAKER_PLUGIN_VERSION', '1.0.0');

    // Define core directory path (eliminates cross-platform case-sensitivity issues)
    define('TWEAKER_CORE_DIR', __DIR__);

    // Resolve main plugin file path
    // This file is in /core/Constants.php and main file is /tweaker.php
    define('TWEAKER_PLUGIN_FILE', dirname(__DIR__) . '/tweaker.php');
    define('TWEAKER_PLUGIN_DIR', plugin_dir_path(TWEAKER_PLUGIN_FILE));
    define('TWEAKER_PLUGIN_URL', plugin_dir_url(TWEAKER_PLUGIN_FILE));
    define('TWEAKER_PLUGIN_BASENAME', plugin_basename(TWEAKER_PLUGIN_FILE));

    // Minimum requirements
    define('TWEAKER_MIN_PHP_VERSION', '8.1.0');
    define('TWEAKER_MIN_WP_VERSION', '6.9');
}
