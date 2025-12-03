<?php
/**
 * Simple logging helper for Tweaker
 *
 * @package NabaTech\Tweaker\Core
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Global logging function
 * This must be simple and work during early activation
 */
if (!function_exists('nt_log')) {
    function nt_log(string $message, mixed $context = null): void
    {
        // Only log if WP_DEBUG is enabled
        if (!defined('WP_DEBUG') || !WP_DEBUG) {
            return;
        }

        $log_message = '[Tweaker] ' . $message;

        if ($context !== null) {
            $log_message .= ' | Context: ' . print_r($context, true);
        }

        // Use error_log to write to debug.log
        error_log($log_message);
    }
}
