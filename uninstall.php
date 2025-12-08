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

// Check if user wants to delete data on uninstall
$delete_data = get_option('nt_delete_data_on_uninstall', 0);

if (!$delete_data) {
    // User wants to keep data - exit without cleanup
    return;
}

// User chose to delete data - proceed with cleanup
// Define plugin constants needed by core classes
require_once __DIR__ . '/core/Constants.php';

// Load logger
require_once NT_CORE_DIR . '/Logger.php';

// Load autoloader
require_once NT_CORE_DIR . '/Autoloader.php';
$autoloader = new \NabaTech\Tweaker\Core\Autoloader();
$autoloader->register();

// Run kernel uninstall
$kernel = \NabaTech\Tweaker\Core\Kernel::get_instance();
$kernel->uninstall();
