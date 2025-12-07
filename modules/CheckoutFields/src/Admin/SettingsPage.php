<?php
/**
 * Settings Page - Admin interface for checkout fields
 *
 * @package NabaTech\Tweaker\Modules\CheckoutFields
 */

namespace NabaTech\Tweaker\Modules\Admin;

use NabaTech\Tweaker\Core\Admin\TabRenderer;

/**
 * Settings page class
 */
class SettingsPage
{
    /**
     * Current configuration
     */
    private array $config;

    /**
     * Constructor
     *
     * @param array $config Current module configuration
     */
    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * Render settings page
     */
    public function render(): void
    {
        // Handle form submission
        if (isset($_POST['nt_save_checkout_fields']) && check_admin_referer('nt_checkout_fields_save')) {
            $this->save_settings();
        }

        $current_tab = TabRenderer::get_current_tab('billing');

        $tabs = [
            'billing' => __('Billing', 'tweaker'),
            'shipping' => __('Shipping', 'tweaker'),
            'order' => __('Order', 'tweaker'),
        ];

        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Checkout Fields', 'tweaker'); ?></h1>
            <p><?php esc_html_e('Customize WooCommerce checkout field labels, placeholders, and validation.', 'tweaker'); ?></p>

            <?php TabRenderer::render_tabs($tabs, $current_tab, 'nt-checkout-fields'); ?>

            <?php TabRenderer::render_content_start(); ?>

            <form method="post" action="" class="nt-form">
                <?php wp_nonce_field('nt_checkout_fields_save'); ?>
                <!-- Hidden input to ensuring save logic triggers on JS submit -->
                <input type="hidden" name="nt_save_checkout_fields" value="1" />

                <?php
                switch ($current_tab) {
                    case 'billing':
                        $this->render_field_group('billing_fields', 'Billing');
                        break;
                    case 'shipping':
                        $this->render_field_group('shipping_fields', 'Shipping');
                        break;
                    case 'order':
                        $this->render_field_group('order_fields', 'Order');
                        break;
                }
                ?>


                <?php
                // Check if current section is active to determine button state
                $section_active_key_map = [
                    'billing' => 'billing_active',
                    'shipping' => 'shipping_active',
                    'order' => 'order_active',
                ];
                $is_section_active = $this->config[$section_active_key_map[$current_tab]] ?? true;
                ?>

                <p class="submit">
                    <button 
                        type="submit" 
                        name="nt_save_checkout_fields" 
                        id="nt-save-changes-btn" 
                        class="button button-primary"
                        <?php disabled(!$is_section_active); ?>
                        style="<?php echo !$is_section_active ? 'opacity: 0.5; cursor: not-allowed;' : ''; ?>"
                    >
                        <?php esc_html_e('Save Changes', 'tweaker'); ?>
                    </button>
                </p>
            </form>

            <?php TabRenderer::render_content_end(); ?>
        </div>
        <?php
    }

    /**
     * Render field group editor
     *
     * @param string $group_key   Configuration group key
     * @param string $group_label Group label for display
     */
    private function render_field_group(string $group_key, string $group_label): void
    {
        $fields = $this->config[$group_key] ?? [];

        if (empty($fields)) {
            echo '<p>' . sprintf(
                esc_html__('No %s fields configured yet.', 'tweaker'),
                strtolower($group_label)
            ) . '</p>';
            return;
        }

        // Sort fields by priority
        uasort($fields, function ($a, $b) {
            return ($a['priority'] ?? 10) <=> ($b['priority'] ?? 10);
        });

        // Map group_key to config key for active state
        // billing_fields -> billing_active, shipping_fields -> shipping_active, order_fields -> order_active
        $active_key_map = [
            'billing_fields' => 'billing_active',
            'shipping_fields' => 'shipping_active',
            'order_fields' => 'order_active',
        ];
        $config_key = $active_key_map[$group_key] ?? $group_key . '_active';
        $form_key = $group_key . '_active'; // billing_fields_active (used in form input name)
        $is_active = $this->config[$config_key] ?? true;
        ?>
        <div style="margin: 0px 0 15px;">
            <label class="nt-toggle">
                <input type="checkbox" name="<?php echo esc_attr($form_key); ?>" value="1" <?php checked($is_active); ?> style="display: none;" />
                <span class="nt-toggle-slider"></span>
            </label>
            
            <?php if ($is_active) : ?>
                <span class="nt-status-active">Active</span>
            <?php else : ?>
                <span class="nt-status-inactive">Inactive</span>
            <?php endif; ?>
        </div>
        
        <script>
            jQuery(document).ready(function($) {
                var $container = $('.nt-field-table-<?php echo esc_attr($group_key); ?>');
                var $toggle = $('input[name="<?php echo esc_attr($form_key); ?>"]');
                var $labelContainer = $toggle.parent().parent();
                
                function updateState() {
                    var checked = $toggle.is(':checked');
                    $container.toggleClass('nt-disabled', !checked);
                    $container.find('input, select, textarea').prop('readonly', !checked);
                    
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
                    $(this).closest('form').submit();
                });
            });
        </script>
        
        <div class="nt-field-table-<?php echo esc_attr($group_key); ?> <?php echo !$is_active ? 'nt-disabled' : ''; ?>">

        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th style="width: 15%;"><?php esc_html_e('Field', 'tweaker'); ?></th>
                    <th style="width: 20%;"><?php esc_html_e('Label', 'tweaker'); ?></th>
                    <th style="width: 20%;"><?php esc_html_e('Placeholder', 'tweaker'); ?></th>
                    <th style="width: 25%;"><?php esc_html_e('Validation Message', 'tweaker'); ?></th>
                    <th style="width: 10%;"><?php esc_html_e('Required', 'tweaker'); ?></th>
                    <th style="width: 10%;"><?php esc_html_e('Enabled', 'tweaker'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($fields as $field_key => $field_config) : ?>
                    <?php $this->render_field_row($group_key, $field_key, $field_config); ?>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <?php
    }

    /**
     * Render single field row
     *
     * @param string $group_key    Group key
     * @param string $field_key    Field key
     * @param array  $field_config Field configuration
     */
    private function render_field_row(string $group_key, string $field_key, array $field_config): void
    {
        $field_name = $group_key . '[' . $field_key . ']';
        $display_name = ucwords(str_replace(['_', 'billing ', 'shipping ', 'order '], [' ', '', '', ''], $field_key));
        ?>
        <tr>
            <td><strong><?php echo esc_html($display_name); ?></strong></td>
            <td>
                <input type="text" name="<?php echo esc_attr($field_name); ?>[label]" value="<?php echo esc_attr($field_config['label'] ?? ''); ?>" class="regular-text" style="width: 100%;" />
            </td>
            <td>
                <input type="text" name="<?php echo esc_attr($field_name); ?>[placeholder]" value="<?php echo esc_attr($field_config['placeholder'] ?? ''); ?>" class="regular-text" style="width: 100%;" />
            </td>
            <td>
                <input type="text" name="<?php echo esc_attr($field_name); ?>[validation_message]" value="<?php echo esc_attr($field_config['validation_message'] ?? ''); ?>" placeholder="<?php esc_attr_e('(leave blank for default)', 'tweaker'); ?>" class="regular-text" style="width: 100%;" />
            </td>
            <td style="text-align: center;">
                <label class="nt-toggle">
                    <input type="checkbox" name="<?php echo esc_attr($field_name); ?>[required]" value="1" <?php checked($field_config['required'] ?? false, true); ?> />
                    <span class="nt-toggle-slider"></span>
                </label>
            </td>
            <td style="text-align: center;">
                <label class="nt-toggle">
                    <input type="checkbox" name="<?php echo esc_attr($field_name); ?>[enabled]" value="1" <?php checked($field_config['enabled'] ?? true, true); ?> />
                    <span class="nt-toggle-slider"></span>
                </label>
            </td>
            <input type="hidden" name="<?php echo esc_attr($field_name); ?>[priority]" value="<?php echo esc_attr($field_config['priority'] ?? 10); ?>" />
        </tr>
        <?php
    }

    /**
     * Save settings
     */
    private function save_settings(): void
    {
        // Get existing configuration
        $existing_config = get_option('nt_checkout_fields_config', []);
        $new_config = $existing_config; // Start with existing data

        // Process billing fields
        if (isset($_POST['billing_fields'])) {
            $new_config['billing_fields'] = $this->sanitize_field_group($_POST['billing_fields']);
        }

        // Process shipping fields
        if (isset($_POST['shipping_fields'])) {
            $new_config['shipping_fields'] = $this->sanitize_field_group($_POST['shipping_fields']);
        }

        // Process order fields
        if (isset($_POST['order_fields'])) {
            $new_config['order_fields'] = $this->sanitize_field_group($_POST['order_fields']);
        }

        // Save section enabled states - only update the one being submitted
        if (isset($_POST['billing_fields'])) {
            $new_config['billing_active'] = isset($_POST['billing_fields_active']);
        }
        
        if (isset($_POST['shipping_fields'])) {
            $new_config['shipping_active'] = isset($_POST['shipping_fields_active']);
        }
        
        if (isset($_POST['order_fields'])) {
            $new_config['order_active'] = isset($_POST['order_fields_active']);
        }


        // Save to database
        update_option('nt_checkout_fields_config', $new_config);

        // Show success message
        add_action('admin_notices', function () {
            echo '<div class="notice notice-success is-dismissible"><p>';
            esc_html_e('Checkout fields saved successfully!', 'tweaker');
            echo '</p></div>';
        });

        // Update current config
        $this->config = $new_config;

        nt_log('Checkout fields configuration saved', $new_config);
    }

    /**
     * Sanitize field group data
     *
     * @param array $fields Raw field data from $_POST
     * @return array Sanitized field data
     */
    private function sanitize_field_group(array $fields): array
    {
        $sanitized = [];

        foreach ($fields as $field_key => $field_data) {
            $sanitized[$field_key] = [
                'label' => sanitize_text_field($field_data['label'] ?? ''),
                'placeholder' => sanitize_text_field($field_data['placeholder'] ?? ''),
                'validation_message' => sanitize_text_field($field_data['validation_message'] ?? ''),
                'required' => isset($field_data['required']) && $field_data['required'] === '1',
                'enabled' => isset($field_data['enabled']) && $field_data['enabled'] === '1',
                'priority' => absint($field_data['priority'] ?? 10),
            ];
        }

        return $sanitized;
    }
}
