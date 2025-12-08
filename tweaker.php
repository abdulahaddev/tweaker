<?php
/**
 * Plugin Name: Tweaker
 * Plugin URI: https://nabatech.com/tweaker
 * Description: Modular WordPress plugin system for Naba Tech Ltd - Professional, maintainable, and extensible.
 * Version: 1.0.0
 * Author: Naba Tech Ltd
 * Author URI: https://nabatech.com
 * Text Domain: tweaker
 * Domain Path: /languages
 * Requires at least: 6.9
 * Requires PHP: 8.1
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 *
 * @package NabaTech\Tweaker
 */

namespace NabaTech\Tweaker;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// ============================================================================
// IMPORTANT: All folder names MUST be lowercase for Linux compatibility!
// Folders: core/, modules/ (lowercase)
// Namespaces: Core, Modules (PascalCase)
// See core/Autoloader.php for details on how this mapping works.
// ============================================================================

// Plugin constants
require_once __DIR__ . '/core/Constants.php';

/**
 * Check minimum requirements
 */
function nt_check_requirements() {
    $errors = [];

    // Check PHP version
    if (version_compare(PHP_VERSION, NT_MIN_PHP_VERSION, '<')) {
        $errors[] = sprintf(
            'Tweaker requires PHP %s or higher. You are running PHP %s.',
            NT_MIN_PHP_VERSION,
            PHP_VERSION
        );
    }

    // Check WordPress version
    global $wp_version;
    if (version_compare($wp_version, NT_MIN_WP_VERSION, '<')) {
        $errors[] = sprintf(
            'Tweaker requires WordPress %s or higher. You are running WordPress %s.',
            NT_MIN_WP_VERSION,
            $wp_version
        );
    }

    if (!empty($errors)) {
        add_action('admin_notices', function () use ($errors) {
            echo '<div class="notice notice-error"><p><strong>Tweaker:</strong></p><ul>';
            foreach ($errors as $error) {
                echo '<li>' . esc_html($error) . '</li>';
            }
            echo '</ul></div>';
        });
        return false;
    }

    return true;
}

/**
 * Load logger first (needed by other core files)
 */
require_once NT_CORE_DIR . '/Logger.php';

/**
 * Autoloader for Tweaker classes
 */
require_once NT_CORE_DIR . '/Autoloader.php';
$nt_autoloader = new \NabaTech\Tweaker\Core\Autoloader();
$nt_autoloader->register();

/**
 * Initialize Tweaker
 */
function nt_init() {
    if (!nt_check_requirements()) {
        return;
    }

    // Initialize the kernel
    $kernel = \NabaTech\Tweaker\Core\Kernel::get_instance();
    $kernel->init();
}
add_action('plugins_loaded', __NAMESPACE__ . '\nt_init', 1);

/**
 * Activation hook
 */
function nt_activate() {
    if (!nt_check_requirements()) {
        wp_die(
            'Tweaker cannot be activated due to unmet requirements. Please check your PHP and WordPress versions.',
            'Activation Error',
            ['response' => 200, 'back_link' => true]
        );
    }

    $kernel = \NabaTech\Tweaker\Core\Kernel::get_instance();
    $kernel->activate();

    flush_rewrite_rules();
}
register_activation_hook(__FILE__, __NAMESPACE__ . '\nt_activate');

/**
 * Deactivation hook
 */
function nt_deactivate() {
    $kernel = \NabaTech\Tweaker\Core\Kernel::get_instance();
    $kernel->deactivate();

    flush_rewrite_rules();
}
register_deactivation_hook(__FILE__, __NAMESPACE__ . '\nt_deactivate');

/**
 * Add Settings link to plugins page
 */
function nt_plugin_action_links($links) {
    $settings_link = sprintf(
        '<a href="%s">%s</a>',
        admin_url('admin.php?page=tweaker'),
        __('Settings', 'tweaker')
    );
    
    array_unshift($links, $settings_link);
    return $links;
}
add_filter('plugin_action_links_' . plugin_basename(__FILE__), __NAMESPACE__ . '\nt_plugin_action_links');

/**
 * Uninstall handling - see uninstall.php
 */
