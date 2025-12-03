<?php
/**
 * Tweaker Uninstall Handler
 *
 * @package NabaTech\Tweaker
 */

namespace NabaTech\Tweaker;

// Exit if accessed directly or not in uninstall context
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Load autoloader
require_once plugin_dir_path(__FILE__) . 'core/Autoloader.php';
$autoloader = new \NabaTech\Tweaker\Core\Autoloader();
$autoloader->register();

// Run kernel uninstall
$kernel = \NabaTech\Tweaker\Core\Kernel::get_instance();
$kernel->uninstall();
