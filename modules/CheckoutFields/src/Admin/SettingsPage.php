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
            'billing' => __('Billing Fields', 'tweaker'),
            'shipping' => __('Shipping Fields', 'tweaker'),
            'order' => __('Order Fields', 'tweaker'),
        ];

        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Checkout Fields', 'tweaker'); ?></h1>
            <p><?php esc_html_e('Customize WooCommerce checkout field labels, placeholders, and validation.', 'tweaker'); ?></p>

            <?php TabRenderer::render_tabs($tabs, $current_tab, 'nt-checkout-fields'); ?>

            <?php TabRenderer::render_content_start(); ?>

            <form method="post" action="" class="nt-form">
                <?php wp_nonce_field('nt_checkout_fields_save'); ?>

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

                <p class="submit">
                    <button type="submit" name="nt_save_checkout_fields" class="button button-primary">
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

        echo '<div class="nt-field-editor">';

        foreach ($fields as $field_key => $field_config) {
            $this->render_field_row($group_key, $field_key, $field_config);
        }

        echo '</div>';
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
        ?>
        <div class="nt-field-row">
            <div class="nt-field-label">
                <?php echo esc_html(ucwords(str_replace(['_', 'billing ', 'shipping ', 'order '], [' ', '', '', ''], $field_key))); ?>
            </div>

            <div class="nt-field-controls">
                <div class="nt-field-control">
                    <label><?php esc_html_e('Label', 'tweaker'); ?></label>
                    <input
                        type="text"
                        name="<?php echo esc_attr($field_name); ?>[label]"
                        value="<?php echo esc_attr($field_config['label'] ?? ''); ?>"
                        class="regular-text"
                    />
                </div>

                <div class="nt-field-control">
                    <label><?php esc_html_e('Placeholder', 'tweaker'); ?></label>
                    <input
                        type="text"
                        name="<?php echo esc_attr($field_name); ?>[placeholder]"
                        value="<?php echo esc_attr($field_config['placeholder'] ?? ''); ?>"
                        class="regular-text"
                    />
                </div>

                <div class="nt-field-control">
                    <label><?php esc_html_e('Validation Message', 'tweaker'); ?></label>
                    <input
                        type="text"
                        name="<?php echo esc_attr($field_name); ?>[validation_message]"
                        value="<?php echo esc_attr($field_config['validation_message'] ?? ''); ?>"
                        class="regular-text"
                        placeholder="e.g., Please enter your name"
                    />
                    <p class="description"><?php esc_html_e('Custom error message when field is empty (leave blank for default)', 'tweaker'); ?></p>
                </div>

                <div class="nt-field-control">
                    <label><?php esc_html_e('Required', 'tweaker'); ?></label>
                    <label class="nt-toggle">
                        <input
                            type="checkbox"
                            name="<?php echo esc_attr($field_name); ?>[required]"
                            value="1"
                            <?php checked($field_config['required'] ?? false, true); ?>
                        />
                        <span class="nt-toggle-slider"></span>
                    </label>
                </div>

                <div class="nt-field-control">
                    <label><?php esc_html_e('Enabled', 'tweaker'); ?></label>
                    <label class="nt-toggle">
                        <input
                            type="checkbox"
                            name="<?php echo esc_attr($field_name); ?>[enabled]"
                            value="1"
                            <?php checked($field_config['enabled'] ?? true, true); ?>
                        />
                        <span class="nt-toggle-slider"></span>
                    </label>
                </div>

                <input
                    type="hidden"
                    name="<?php echo esc_attr($field_name); ?>[priority]"
                    value="<?php echo esc_attr($field_config['priority'] ?? 10); ?>"
                />
            </div>
        </div>
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
