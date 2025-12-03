<?php
/**
 * Field Service - Checkout field modification
 *
 * @package NabaTech\Tweaker\Modules\CheckoutFields
 */

namespace NabaTech\Tweaker\Modules\Services;

/**
 * Field service class
 */
class FieldService
{
    /**
     * Modify WooCommerce checkout fields
     *
     * @param array $fields Checkout fields
     * @return array Modified fields
     */
    public function modify_checkout_fields(array $fields): array
    {
        static $already_modified = false;
        
        // Prevent multiple applications of the same changes
        if ($already_modified) {
            return $fields;
        }
        
        $config = get_option('nt_checkout_fields_config', []);

        // Modify billing fields
        if (!empty($config['billing_fields'])) {
            $fields['billing'] = $this->apply_field_config(
                $fields['billing'] ?? [],
                $config['billing_fields']
            );
        }

        // Modify shipping fields
        if (!empty($config['shipping_fields'])) {
            $fields['shipping'] = $this->apply_field_config(
                $fields['shipping'] ?? [],
                $config['shipping_fields']
            );
        }

        // Modify order fields
        if (!empty($config['order_fields'])) {
            $fields['order'] = $this->apply_field_config(
                $fields['order'] ?? [],
                $config['order_fields']
            );
        }

        $already_modified = true;
        return $fields;
    }

    /**
     * Apply configuration to field group
     *
     * @param array $fields       WooCommerce fields
     * @param array $field_config Module configuration
     * @return array Modified fields
     */
    private function apply_field_config(array $fields, array $field_config): array
    {
        foreach ($field_config as $field_key => $config) {
            // Skip if field is disabled
            if (isset($config['enabled']) && !$config['enabled']) {
                unset($fields[$field_key]);
                continue;
            }

            // Apply configuration to existing field
            if (isset($fields[$field_key])) {
                // Apply label if not empty
                if (!empty($config['label'])) {
                    $fields[$field_key]['label'] = $config['label'];
                }

                // Apply placeholder if not empty
                if (!empty($config['placeholder'])) {
                    $fields[$field_key]['placeholder'] = $config['placeholder'];
                }

                if (isset($config['required'])) {
                    $fields[$field_key]['required'] = $config['required'];
                }

                if (isset($config['priority'])) {
                    $fields[$field_key]['priority'] = $config['priority'];
                }

                if (isset($config['class']) && is_array($config['class'])) {
                    $fields[$field_key]['class'] = array_merge(
                        $fields[$field_key]['class'] ?? [],
                        $config['class']
                    );
                }
            }
        }

        return $fields;
    }

    /**
     * Get field value with fallback
     *
     * @param string $field_key Field key
     * @param mixed  $default   Default value
     * @return mixed Field value
     */
    public function get_field_value(string $field_key, $default = '')
    {
        return WC()->checkout()->get_value($field_key) ?? $default;
    }
}
