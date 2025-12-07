<?php

namespace NabaTech\Tweaker\Modules\WPSecretLogin\Admin;

use NabaTech\Tweaker\Modules\WPSecretLogin\Services\OptionService;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Settings Page
 */
class SettingsPage {
    /**
     * @var OptionService
     */
    private $option_service;

    /**
     * Constructor
     */
    public function __construct() {
        $this->option_service = new OptionService();
    }

    /**
     * Initialize
     */
    public function init(): void {
        add_action('admin_init', [$this, 'register_settings']);
    }

    /**
     * Register settings
     */
    public function register_settings(): void {
        register_setting('nt_wp_secret_login_group', 'nt_wp_secret_login_settings', [
            'sanitize_callback' => [$this, 'sanitize_settings']
        ]);
    }



    /**
     * Sanitize settings
     */
    public function sanitize_settings($input) {
        $output = [];
        $output['enabled'] = isset($input['enabled']) && $input['enabled'] == '1';
        $output['login_slug'] = sanitize_title($input['login_slug'] ?? 'login');
        if (empty($output['login_slug'])) {
            $output['login_slug'] = 'login';
        }
        $output['redirect_slug'] = sanitize_title($input['redirect_slug'] ?? '404');
        if (empty($output['redirect_slug'])) {
            $output['redirect_slug'] = '404';
        }
        $output['redirect_to_secret'] = isset($input['redirect_to_secret']) && $input['redirect_to_secret'] == '1';
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        return $output;
    }

    /**
     * Render page
     */
    public function render_page(): void {
        // Handle saving manually if needed, or use options.php
        // Since we are using a custom option service, we might want to handle saving manually 
        // to ensure we use the service's update method, but register_setting handles it automatically 
        // if the option name matches.
        // However, OptionService uses 'nt_wp_secret_login_settings'.
        
        if (isset($_GET['settings-updated'])) {
             add_settings_error('nt_wp_secret_login_messages', 'nt_wp_secret_login_message', __('Settings Saved', 'tweaker'), 'updated');
        }
        settings_errors('nt_wp_secret_login_messages');

        $settings = $this->option_service->get_settings();
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('WP Secret Login', 'tweaker'); ?></h1>
            <form method="post" action="options.php">
                <?php settings_fields('nt_wp_secret_login_group'); ?>
                
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row"><?php esc_html_e('Enable Secret Login', 'tweaker'); ?></th>
                        <td>
                            <label class="nt-toggle">
                                <input type="checkbox" name="nt_wp_secret_login_settings[enabled]" value="1" <?php checked($settings['enabled']); ?> />
                                <span class="nt-toggle-slider"></span>
                            </label>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><?php esc_html_e('Login Slug', 'tweaker'); ?></th>
                        <td>
                            <input type="text" name="nt_wp_secret_login_settings[login_slug]" value="<?php echo esc_attr($settings['login_slug']); ?>" class="regular-text" />
                            <p class="description">
                                <?php printf(esc_html__('Your login URL will be: %s', 'tweaker'), '<code>' . home_url($settings['login_slug']) . '</code>'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><?php esc_html_e('Redirect to Secret Login', 'tweaker'); ?></th>
                        <td>
                            <label class="nt-toggle">
                                <input type="checkbox" name="nt_wp_secret_login_settings[redirect_to_secret]" value="1" <?php checked($settings['redirect_to_secret']); ?> />
                                <span class="nt-toggle-slider"></span>
                            </label>
                            <p class="description"><?php esc_html_e('If enabled, accessing wp-login.php will redirect to your Secret Login URL instead of the Redirection URL.', 'tweaker'); ?></p>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><?php esc_html_e('Redirection URL', 'tweaker'); ?></th>
                        <td>
                            <?php
                            wp_dropdown_pages([
                                'name' => 'nt_wp_secret_login_settings[redirect_slug]',
                                'selected' => $settings['redirect_slug'],
                                'show_option_none' => __('Default 404 Page', 'tweaker'),
                                'option_none_value' => '404',
                            ]);
                            ?>
                            <p class="description"><?php esc_html_e('Select the page to redirect to when someone tries to access the wp-login.php page and the wp-admin directory while not logged in.', 'tweaker'); ?></p>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
}
