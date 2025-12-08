<?php
/**
 * Validation Service - Checkout field validation
 *
 * @package NabaTech\Tweaker\Modules\CheckoutFields
 */

namespace NabaTech\Tweaker\Modules\Services;

/**
 * Validation service class
 */
class ValidationService
{
    /**
     * Store validation errors for inline display
     */
    private static array $validation_errors = [];

    /**
     * Validate checkout fields
     */
    public function validate_checkout_fields(): void
    {
        $config = get_option('nt_checkout_fields_config', []);
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verification is handled by WooCommerce checkout process
        $posted_data = $_POST;

        // Clear previous errors
        self::$validation_errors = [];

        // Validate all field groups
        $groups = [
            'billing_fields' => 'billing_active',
            'shipping_fields' => 'shipping_active',
            'order_fields' => 'order_active',
        ];

        foreach ($groups as $group => $active_key) {
            if (empty($config[$group]) || !($config[$active_key] ?? true)) {
                continue;
            }

            foreach ($config[$group] as $field_key => $field_config) {
                $this->validate_field($field_key, $field_config, $posted_data);
            }
        }
    }

    /**
     * Validate single field
     *
     * @param string $field_key    Field key
     * @param array  $field_config Field configuration
     * @param array  $posted_data  Posted form data
     */
    private function validate_field(string $field_key, array $field_config, array $posted_data): void
    {
        // Skip if field is not enabled or not required
        if (empty($field_config['enabled']) || empty($field_config['required'])) {
            return;
        }

        $field_value = $posted_data[$field_key] ?? '';
        $field_label = $field_config['label'] ?? ucwords(str_replace('_', ' ', $field_key));

        // Check if field is empty
        if (empty($field_value)) {
            $error_message = sprintf(
                '%s is a required field.',
                '<strong>' . esc_html($field_label) . '</strong>'
            );

            // Add to WooCommerce notices
            wc_add_notice($error_message, 'error');

            // Store for inline display
            self::$validation_errors[$field_key] = wp_strip_all_tags($error_message);
        }

        // Additional validation rules can be added here
        // For example: email format, phone format, etc.
    }

    /**
     * Add inline validation message to field HTML
     *
     * @param string $field     Field HTML
     * @param string $key       Field key
     * @param array  $args      Field arguments
     * @param mixed  $value     Field value
     * @return string Modified field HTML
     */
    public function add_inline_validation_message(string $field, string $key, array $args, $value): string
    {
        // Check if field is required
        $is_required = isset($args['required']) && $args['required'];
        
        if (!$is_required) {
            return $field;
        }

        // Check if this field has a validation error from WooCommerce
        $has_error = false;
        $config = get_option('nt_checkout_fields_config', []);
        $field_label = '';
        
        // Find the custom label from our config
        $groups = [
            'billing_fields' => 'billing_active',
            'shipping_fields' => 'shipping_active',
            'order_fields' => 'order_active',
        ];

        foreach ($groups as $group => $active_key) {
            if (!($config[$active_key] ?? true)) {
                continue;
            }

            if (isset($config[$group][$key]['label'])) {
                $field_label = $config[$group][$key]['label'];
                break;
            }
        }
        
        // If no custom label, use the default from args
        if (empty($field_label)) {
            $field_label = $args['label'] ?? ucwords(str_replace('_', ' ', $key));
        }
        
        // Check if field is empty (will trigger validation error)
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verification is handled by WooCommerce checkout process
        $field_value = isset($_POST[$key]) ? sanitize_text_field(wp_unslash($_POST[$key])) : $value;
        if (empty($field_value) && $is_required) {
            $has_error = true;
        }

        // Add inline error if validation failed
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verification is handled by WooCommerce checkout process
        if ($has_error && isset($_POST['woocommerce_checkout_place_order'])) {
            // Add error class to field wrapper
            $field = str_replace(
                'class="form-row',
                'class="form-row woocommerce-invalid woocommerce-invalid-required-field',
                $field
            );

            // Create error message
            $error_message = sprintf('%s is a required field.', wp_strip_all_tags($field_label));
            
            // Inject error message before closing </p> tag
            $error_html = '<span class="nt-field-error woocommerce-error" style="color: #e2401c; font-size: 0.875em; display: block; margin-top: 5px;">';
            $error_html .= esc_html($error_message);
            $error_html .= '</span>';

            $field = str_replace('</p>', $error_html . '</p>', $field);
        }

        return $field;
    }

    /**
     * Get validation errors
     *
     * @return array Validation errors
     */
    public static function get_errors(): array
    {
        return self::$validation_errors;
    }

    /**
     * Customize error messages to use our custom field labels
     *
     * @param string $error Error message
     * @return string Modified error message
     */
    public function customize_error_messages(string $error): string
    {
        $config = get_option('nt_checkout_fields_config', []);
        
        // First, check if this error matches a field with a custom validation message
        $groups = [
            'billing_fields' => 'billing_active',
            'shipping_fields' => 'shipping_active',
            'order_fields' => 'order_active',
        ];

        foreach ($groups as $group => $active_key) {
            if (empty($config[$group]) || !($config[$active_key] ?? true)) {
                continue;
            }
            
            foreach ($config[$group] as $field_key => $field_config) {
                // Skip if no custom validation message is set
                if (empty($field_config['validation_message'])) {
                    continue;
                }
                
                $custom_label = $field_config['label'] ?? '';
                if (empty($custom_label)) {
                    continue;
                }
                
                // Check if this error is for this field
                // Match both "Billing CustomLabel" and "CustomLabel" formats
                $patterns = [
                    '<strong>' . $custom_label . '</strong> is a required field.',
                    '<strong>Billing ' . $custom_label . '</strong> is a required field.',
                    '<strong>Shipping ' . $custom_label . '</strong> is a required field.',
                ];
                
                foreach ($patterns as $pattern) {
                    if (strpos($error, $pattern) !== false) {
                        // Replace with custom validation message
                        return '<strong>' . esc_html($field_config['validation_message']) . '</strong>';
                    }
                }
            }
        }
        
        // If no custom message, proceed with default label replacement
        $label_map = [];
        
        foreach ($groups as $group => $active_key) {
            if (empty($config[$group]) || !($config[$active_key] ?? true)) {
                continue;
            }
            
            foreach ($config[$group] as $field_key => $field_config) {
                if (empty($field_config['label'])) {
                    continue;
                }
                
                // Get the default WooCommerce label for this field
                $default_label = ucwords(str_replace('_', ' ', $field_key));
                
                // Also handle "Billing X" format with original field name
                $billing_label = 'Billing ' . ucwords(str_replace(['billing_', '_'], ['', ' '], $field_key));
                $shipping_label = 'Shipping ' . ucwords(str_replace(['shipping_', '_'], ['', ' '], $field_key));
                
                // Map all variations to our custom label
                $custom_label = $field_config['label'];
                $label_map[$default_label] = $custom_label;
                $label_map[$billing_label] = $custom_label;
                $label_map[$shipping_label] = $custom_label;
                
                // IMPORTANT: Also map "Billing CustomLabel" to "CustomLabel"
                // This handles cases where WooCommerce uses the already-modified label
                $label_map['Billing ' . $custom_label] = $custom_label;
                $label_map['Shipping ' . $custom_label] = $custom_label;
            }
        }
        
        // Replace default labels with custom labels in the error message
        foreach ($label_map as $default => $custom) {
            $error = str_replace(
                '<strong>' . $default . '</strong>',
                '<strong>' . $custom . '</strong>',
                $error
            );
        }
        
        return $error;
    }
}
