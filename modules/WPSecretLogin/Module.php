<?php

namespace NabaTech\Tweaker\Modules\WPSecretLogin;

use NabaTech\Tweaker\Modules\WPSecretLogin\Admin\SettingsPage;
use NabaTech\Tweaker\Modules\WPSecretLogin\Core\SecretLogin;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * WP Secret Login Module Bootstrap
 */
class Module {
    /**
     * Module directory
     */
    private string $module_dir;

    /**
     * Module manifest
     */
    private array $manifest;

    /**
     * Constructor
     * 
     * @param string $module_dir Module directory
     * @param array $manifest Module manifest
     */
    public function __construct(string $module_dir, array $manifest) {
        $this->module_dir = $module_dir;
        $this->manifest = $manifest;

        // Require necessary files
        require_once $this->module_dir . '/src/Services/OptionService.php';
        require_once $this->module_dir . '/src/Core/SecretLogin.php';
        if (is_admin()) {
            require_once $this->module_dir . '/src/Admin/SettingsPage.php';
        }
    }

    /**
     * Register hooks
     */
    public function register_hooks(): void {
        // Initialize Core Logic
        $secret_login = new SecretLogin();
        $secret_login->init();

        // Initialize Admin Settings
        if (is_admin()) {
            $settings_page = new SettingsPage();
            $settings_page->init();
        }
    }

    /**
     * Get module ID
     */
    public function get_id(): string {
        return $this->manifest['id'] ?? 'wp-secret-login';
    }

    /**
     * Get module manifest
     */
    public function get_manifest(): array {
        return $this->manifest;
    }

    /**
     * Install module
     */
    public function install(): void {
        // Migration logic is handled in OptionService or separate migration class
    }

    /**
     * Activate module
     */
    public function activate(): void {
        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Deactivate module
     */
    public function deactivate(): void {
        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Uninstall module
     */
    public function uninstall(): void {
        // Cleanup options
        delete_option('nt_wp_secret_login_settings');
    }
}
