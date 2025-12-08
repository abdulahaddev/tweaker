<?php
/**
 * Admin Menu Manager
 *
 * @package NabaTech\Tweaker\Core\Admin
 */

namespace NabaTech\Tweaker\Core\Admin;

/**
 * Menu manager class
 */
class MenuManager
{
    /**
     * Loaded modules
     */
    private array $modules;

    /**
     * Constructor
     *
     * @param array $modules Loaded modules
     */
    public function __construct(array $modules)
    {
        $this->modules = $modules;
    }

    /**
     * Register admin menus
     */
    /**
     * Register admin menus
     */
    public function register_menus(): void
    {
        // Main Tweaker menu (now points to Modules page, acting as Dashboard)
        add_menu_page(
            __('Tweaker', 'tweaker'),
            __('Tweaker', 'tweaker'),
            'nt_manage_tweaker',
            'tweaker',
            [$this, 'render_modules_page'],
            'dashicons-admin-generic',
            59
        );

        // Dashboard submenu (rename first item to Dashboard)
        add_submenu_page(
            'tweaker',
            __('Dashboard', 'tweaker'),
            __('Dashboard', 'tweaker'),
            'nt_manage_tweaker',
            'tweaker',
            [$this, 'render_modules_page']
        );

        // Add module submenus
        foreach ($this->modules as $module) {
            $manifest = $module->get_manifest();
            $entry = $manifest['entry_points']['admin_menu'] ?? null;

            if ($entry && $entry['parent'] === 'tweaker') {
                add_submenu_page(
                    'tweaker',
                    $entry['title'],
                    $entry['title'],
                    $entry['capability'],
                    $entry['slug'],
                    [$module, 'render_admin_page']
                );
            }
        }

        // Settings
        add_submenu_page(
            'tweaker',
            __('Settings', 'tweaker'),
            __('Settings', 'tweaker'),
            'nt_manage_tweaker',
            'tweaker-settings',
            [$this, 'render_settings_page']
        );
    }

    /**
     * Render modules management page (Main Dashboard)
     */
    public function render_modules_page(): void
    {
        // Handle AJAX toggle
        if (isset($_POST['nt_toggle_module']) && check_admin_referer('nt_toggle_module', 'nt_nonce')) {
            $this->handle_module_toggle();
        }

        // Get all discovered modules (enabled and disabled)
        $all_modules = $this->get_all_modules();
        $enabled_modules = get_option('nt_enabled_modules', []);
        
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Tweaker Dashboard', 'tweaker'); ?></h1>
            
            <?php 
            // Display notice directly in page content to prevent jumping
            $notice = get_transient('nt_module_notice');
            if ($notice) {
                $class = $notice['type'] === 'success' ? 'notice-success' : 'notice-error';
                echo '<div class="notice ' . esc_attr($class) . ' is-dismissible" style="margin: 15px 0;"><p>';
                echo esc_html($notice['message']);
                echo '</p></div>';
                delete_transient('nt_module_notice');
            }
            ?>
            
            <p><?php esc_html_e('Enable or disable Tweaker modules. Modules with unmet dependencies cannot be enabled.', 'tweaker'); ?></p>

            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th style="width: 30%;"><?php esc_html_e('Module', 'tweaker'); ?></th>
                        <th style="width: 35%;"><?php esc_html_e('Description', 'tweaker'); ?></th>
                        <th style="width: 10%;"><?php esc_html_e('Version', 'tweaker'); ?></th>
                        <th style="width: 15%;"><?php esc_html_e('Dependencies', 'tweaker'); ?></th>
                        <th style="width: 10%;"><?php esc_html_e('Status', 'tweaker'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($all_modules as $module_id => $manifest) : ?>
                        <?php
                        $is_enabled = in_array($module_id, $enabled_modules);
                        $deps_met = $this->check_dependencies_met($manifest);
                        $deps_status = $this->get_dependencies_status($manifest);
                        ?>
                        <tr>
                            <td><strong><?php echo esc_html($manifest['name']); ?></strong></td>
                            <td><?php echo esc_html($manifest['description']); ?></td>
                            <td><?php echo esc_html($manifest['version']); ?></td>
                            <td>
                                <?php if (!empty($manifest['requires'])) : ?>
                                    <?php foreach ($manifest['requires'] as $dep => $version) : ?>
                                        <?php if ($dep === 'wordpress' || $dep === 'php') continue; ?>
                                        <div style="font-size: 12px;">
                                            <?php
                                            $dep_met = $deps_status[$dep] ?? false;
                                            $icon = $dep_met ? '✓' : '✗';
                                            $color = $dep_met ? 'green' : 'red';
                                            ?>
                                            <span style="color: <?php echo esc_attr($color); ?>;"><?php echo esc_html($icon); ?></span>
                                            <?php echo esc_html(ucfirst($dep)); ?> <?php echo esc_html($version); ?>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else : ?>
                                    <span style="color: #999;">None</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <form method="post" style="display: inline;">
                                    <?php wp_nonce_field('nt_toggle_module', 'nt_nonce'); ?>
                                    <input type="hidden" name="nt_toggle_module" value="<?php echo esc_attr($module_id); ?>">
                                    <input type="hidden" name="nt_module_action" value="<?php echo $is_enabled ? 'disable' : 'enable'; ?>">
                                    
                                    <label class="nt-toggle" <?php echo !$deps_met ? 'title="Dependencies not met"' : ''; ?>>
                                        <input
                                            type="checkbox"
                                            <?php checked($is_enabled); ?>
                                            <?php disabled(!$deps_met && !$is_enabled); ?>
                                            onchange="this.form.submit()"
                                        />
                                        <span class="nt-toggle-slider"></span>
                                    </label>
                                    
                                    <?php if ($is_enabled) : ?>
                                        <span style="color: green; margin-left: 10px;">Active</span>
                                    <?php elseif (!$deps_met) : ?>
                                        <span style="color: red; margin-left: 10px;">Disabled</span>
                                    <?php else : ?>
                                        <span style="color: #999; margin-left: 10px;">Inactive</span>
                                    <?php endif; ?>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    /**
     * Get all modules (from filesystem)
     */
    private function get_all_modules(): array
    {
        $modules = [];
        $modules_dir = TWEAKER_PLUGIN_DIR . 'modules';
        
        if (!is_dir($modules_dir)) {
            return $modules;
        }

        foreach (scandir($modules_dir) as $dir) {
            if ($dir === '.' || $dir === '..') {
                continue;
            }

            $module_path = $modules_dir . '/' . $dir;
            $manifest_file = $module_path . '/module.json';

            if (is_file($manifest_file)) {
                $manifest = json_decode(file_get_contents($manifest_file), true);
                if ($manifest && isset($manifest['id'])) {
                    $modules[$manifest['id']] = $manifest;
                }
            }
        }

        return $modules;
    }

    /**
     * Check if all dependencies are met
     */
    private function check_dependencies_met(array $manifest): bool
    {
        $requires = $manifest['requires'] ?? [];
        
        foreach ($requires as $dep => $version) {
            if ($dep === 'woocommerce' && !class_exists('WooCommerce')) {
                return false;
            }
            // Add more dependency checks here as needed
        }

        return true;
    }

    /**
     * Get detailed dependencies status
     */
    private function get_dependencies_status(array $manifest): array
    {
        $requires = $manifest['requires'] ?? [];
        $status = [];
        
        foreach ($requires as $dep => $version) {
            if ($dep === 'woocommerce') {
                $status[$dep] = class_exists('WooCommerce');
            }
            // Add more dependency status checks here
        }

        return $status;
    }

    /**
     * Handle module toggle request
     */
    private function handle_module_toggle(): void
    {
        // Verify nonce
        if (!isset($_POST['nt_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nt_nonce'])), 'nt_toggle_module')) {
            wp_die(esc_html__('Security check failed.', 'tweaker'));
        }
        
        // Validate and sanitize POST data
        if (!isset($_POST['nt_toggle_module']) || !isset($_POST['nt_module_action'])) {
            wp_die(esc_html__('Missing required parameters.', 'tweaker'));
        }
        
        $module_id = sanitize_text_field(wp_unslash($_POST['nt_toggle_module']));
        $action = sanitize_text_field(wp_unslash($_POST['nt_module_action']));
        
        $enabled_modules = get_option('nt_enabled_modules', []);
        $redirect_url = admin_url('admin.php?page=tweaker'); // Default redirect
        
        if ($action === 'enable') {
            // Check dependencies before enabling
            $all_modules = $this->get_all_modules();
            if (isset($all_modules[$module_id]) && $this->check_dependencies_met($all_modules[$module_id])) {
                if (!in_array($module_id, $enabled_modules)) {
                    $enabled_modules[] = $module_id;
                    update_option('nt_enabled_modules', $enabled_modules);
                    
                    // Store success notice in transient
                    set_transient('nt_module_notice', [
                        'type' => 'success',
                        /* translators: %s: Module name */
                        'message' => sprintf(__('Module "%s" has been enabled.', 'tweaker'), $all_modules[$module_id]['name'])
                    ], 30);
                    
                    // Redirect to module's settings page if it has one
                    $entry = $all_modules[$module_id]['entry_points']['admin_menu'] ?? null;
                    if ($entry && isset($entry['slug'])) {
                        $redirect_url = admin_url('admin.php?page=' . $entry['slug']);
                    }
                }
            } else {
                // Store error notice in transient
                set_transient('nt_module_notice', [
                    'type' => 'error',
                    'message' => __('Cannot enable module: dependencies not met.', 'tweaker')
                ], 30);
            }
        } elseif ($action === 'disable') {
            $enabled_modules = array_diff($enabled_modules, [$module_id]);
            update_option('nt_enabled_modules', array_values($enabled_modules));
            
            // Get module name for better message
            $all_modules = $this->get_all_modules();
            $module_name = $all_modules[$module_id]['name'] ?? $module_id;
            
            // Store success notice in transient
            set_transient('nt_module_notice', [
                'type' => 'success',
                /* translators: %s: Module name */
                'message' => sprintf(__('Module "%s" has been disabled.', 'tweaker'), $module_name)
            ], 30);
        }
        
        // Redirect
        wp_safe_redirect($redirect_url);
        exit;
    }


    /**
     * Render settings page
     */
    public function render_settings_page(): void
    {
        // Handle settings save
        if (isset($_POST['nt_save_settings']) && check_admin_referer('nt_settings_save')) {
            // Save Uninstall Settings
            $delete_on_uninstall = isset($_POST['nt_delete_on_uninstall']) ? 1 : 0;
            update_option('nt_delete_data_on_uninstall', $delete_on_uninstall);
            
            // Save Checkout Page Settings
            $hide_shipping_section = isset($_POST['nt_hide_shipping_section']) ? 1 : 0;
            update_option('nt_hide_shipping_section', $hide_shipping_section);
            
            echo '<div class="notice notice-success is-dismissible"><p>';
            esc_html_e('Settings saved successfully!', 'tweaker');
            echo '</p></div>';
        }
        
        $delete_on_uninstall = get_option('nt_delete_data_on_uninstall', 0);
        $hide_shipping_section = get_option('nt_hide_shipping_section', 0);
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Tweaker Settings', 'tweaker'); ?></h1>
            <p><?php esc_html_e('Global settings for the Tweaker plugin system.', 'tweaker'); ?></p>

            <form method="post" action="">
                <?php wp_nonce_field('nt_settings_save'); ?>
                <input type="hidden" name="nt_save_settings" value="1" />
                
                <!-- Checkout Page Settings Section -->
                <div class="nt-card">
                    <h2><?php esc_html_e('Checkout Page Settings', 'tweaker'); ?></h2>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php esc_html_e('Hide Shipping Section', 'tweaker'); ?></th>
                            <td>
                                <label class="nt-toggle">
                                    <input type="checkbox" name="nt_hide_shipping_section" value="1" <?php checked($hide_shipping_section, 1); ?> />
                                    <span class="nt-toggle-slider"></span>
                                </label>
                                <p class="description">
                                    <?php esc_html_e('When enabled, the shipping address section will be completely hidden on the WooCommerce checkout page. Useful for digital products or services that don\'t require shipping.', 'tweaker'); ?>
                                </p>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <div class="nt-card">
                    <h2><?php esc_html_e('Uninstall Settings', 'tweaker'); ?></h2>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php esc_html_e('Remove All Data on Uninstall', 'tweaker'); ?></th>
                            <td>
                                <label class="nt-toggle">
                                    <input type="checkbox" name="nt_delete_on_uninstall" value="1" <?php checked($delete_on_uninstall, 1); ?> />
                                    <span class="nt-toggle-slider"></span>
                                </label>
                                <p class="description">
                                    <?php esc_html_e('When enabled, all plugin data (settings, modules, database tables) will be permanently deleted when you uninstall this plugin. Leave disabled to keep your data for future use.', 'tweaker'); ?>
                                </p>
                                <p class="description" style="color: #d63638; font-weight: 500;">
                                    ⚠️ <?php esc_html_e('Warning: This action cannot be undone. All module configurations will be lost.', 'tweaker'); ?>
                                </p>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <div class="nt-card">
                    <h2><?php esc_html_e('Debug Information', 'tweaker'); ?></h2>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php esc_html_e('Debug Mode', 'tweaker'); ?></th>
                            <td>
                                <?php if (defined('WP_DEBUG') && WP_DEBUG) : ?>
                                    <span style="color: orange;">⚠️ <?php esc_html_e('Debug mode is currently', 'tweaker'); ?> <strong><?php esc_html_e('ENABLED', 'tweaker'); ?></strong></span>
                                <?php else : ?>
                                    <span style="color: green;">✓ <?php esc_html_e('Debug mode is currently', 'tweaker'); ?> <strong><?php esc_html_e('DISABLED', 'tweaker'); ?></strong></span>
                                <?php endif; ?>
                                <p class="description">
                                    <?php esc_html_e('Debug mode is controlled via wp-config.php (WP_DEBUG constant).', 'tweaker'); ?>
                                </p>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <p class="submit">
                    <button type="submit" name="nt_save_settings" class="button button-primary">
                        <?php esc_html_e('Save Settings', 'tweaker'); ?>
                    </button>
                </p>
            </form>
        </div>
        <?php
    }
}
