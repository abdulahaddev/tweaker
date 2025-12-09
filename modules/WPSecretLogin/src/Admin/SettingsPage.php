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
        // Handle toggle-only save
        if (isset($_POST['nt_toggle_only_save']) && check_admin_referer('nt_wp_secret_login_toggle')) {
            $existing_settings = $this->option_service->get_settings();
            $existing_settings['enabled'] = isset($_POST['nt_wp_secret_login_settings']['enabled']);
            
            update_option('nt_wp_secret_login_settings', $existing_settings);
            
            // Redirect to refresh page and show updated state
            wp_safe_redirect(add_query_arg(['settings-updated' => 'true'], wp_get_referer()));
            exit;
        }
        
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- settings-updated is set by WordPress after nonce verification
        if (isset($_GET['settings-updated'])) {
             add_settings_error('nt_wp_secret_login_messages', 'nt_wp_secret_login_message', __('Settings Saved', 'tweaker'), 'updated');
        }
        settings_errors('nt_wp_secret_login_messages');

        $settings = $this->option_service->get_settings();
        $is_enabled = $settings['enabled'];
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('WP Secret Login', 'tweaker'); ?></h1>
            <p><?php esc_html_e('Customize your WordPress login URL and protect wp-login.php and wp-admin from unauthorized access.', 'tweaker'); ?></p>

            <div class="nt-tab-content" style="margin-top: 10px;">
                <!-- Toggle Form (separate from main settings form) -->
                <form method="post" action="" id="nt-toggle-form">
                    <?php wp_nonce_field('nt_wp_secret_login_toggle'); ?>
                    <input type="hidden" name="nt_toggle_only_save" value="1" />
                    
                    <div style="margin: 0px 0 15px;">
                        <label class="nt-toggle">
                            <input type="checkbox" name="nt_wp_secret_login_settings[enabled]" value="1" <?php checked($is_enabled); ?> style="display: none;" />
                            <span class="nt-toggle-slider"></span>
                        </label>
                        
                        <?php if ($is_enabled) : ?>
                            <span class="nt-status-active">Active</span>
                        <?php else : ?>
                            <span class="nt-status-inactive">Inactive</span>
                        <?php endif; ?>
                    </div>
                </form>
                
                <!-- Main Settings Form -->
                <form method="post" action="options.php" class="nt-form" id="nt-settings-form">
                    <?php settings_fields('nt_wp_secret_login_group'); ?>
                    
                    <script>
                        jQuery(document).ready(function($) {
                            var $container = $('.nt-secret-login-fields');
                            var $toggle = $('#nt-toggle-form input[name="nt_wp_secret_login_settings[enabled]"]');
                            var $labelContainer = $toggle.parent().parent();
                            var $saveButton = $('#nt-save-changes-btn');
                            
                            function updateState() {
                                var checked = $toggle.is(':checked');
                                $container.toggleClass('nt-disabled', !checked);
                                $container.find('input, select, textarea').prop('readonly', !checked);
                                
                                // Enable/disable Save Changes button based on toggle state
                                $saveButton.prop('disabled', !checked);
                                if (!checked) {
                                    $saveButton.css({'opacity': '0.5', 'cursor': 'not-allowed'});
                                } else {
                                    $saveButton.css({'opacity': '1', 'cursor': 'pointer'});
                                }
                                
                                var $statusSpan = $labelContainer.find('span:not(.nt-toggle-slider)');
                                if (checked) {
                                    $statusSpan.text('Active')
                                        .removeClass('nt-status-inactive')
                                        .addClass('nt-status-active');
                                } else {
                                    $statusSpan.text('Inactive')
                                        .removeClass('nt-status-active')
                                        .addClass('nt-status-inactive');
                                }
                            }
                            
                            updateState();
                            
                            $toggle.change(function() {
                                updateState();
                                // Submit the toggle form (not the settings form)
                                $('#nt-toggle-form').submit();
                            });
                        });
                    </script>
                    
                    <div class="nt-secret-login-fields <?php echo !$is_enabled ? 'nt-disabled' : ''; ?>">
                        <table class="form-table">
                            <tr valign="top">
                                <th scope="row"><?php esc_html_e('Login URL', 'tweaker'); ?></th>
                                <td>
                                    <input type="text" name="nt_wp_secret_login_settings[login_slug]" value="<?php echo esc_attr($settings['login_slug']); ?>" class="regular-text" />
                                    <p class="description">
                                        <?php 
                                        /* translators: %s: Login URL with slug */
                                        printf(esc_html__('Your login URL will be: %s', 'tweaker'), '<code>' . esc_url(home_url($settings['login_slug'])) . '</code>'); 
                                        ?>
                                    </p>
                                </td>
                            </tr>
                            <tr valign="top">
                                <th scope="row"><?php esc_html_e('Redirection Page', 'tweaker'); ?></th>
                                <td>
                                    <?php
                                    wp_dropdown_pages([
                                        'name' => 'nt_wp_secret_login_settings[redirect_slug]',
                                        'selected' => esc_attr($settings['redirect_slug']),
                                        'show_option_none' => esc_html__('Default 404 Page', 'tweaker'),
                                        'option_none_value' => '404',
                                    ]);
                                    ?>
                                    <p class="description"><?php esc_html_e('Select the page to redirect to when someone tries to access the wp-login.php page and the wp-admin directory while not logged in.', 'tweaker'); ?></p>
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
                        </table>
                    </div>
                    
                    <p class="submit">
                        <button 
                            type="submit" 
                            name="submit" 
                            id="nt-save-changes-btn" 
                            class="button button-primary"
                            <?php disabled(!$is_enabled); ?>
                            style="<?php echo !$is_enabled ? 'opacity: 0.5; cursor: not-allowed;' : ''; ?>"
                        >
                            <?php esc_html_e('Save Changes', 'tweaker'); ?>
                        </button>
                    </p>
                </form>
            </div>
        </div>
        <?php
    }
}

