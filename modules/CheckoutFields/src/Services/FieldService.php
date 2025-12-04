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
        $config = get_option('nt_checkout_fields_config', []);

        if (!empty($config['billing_fields'])) {
            $fields['billing'] = $this->apply_field_config($fields['billing'] ?? [], $config['billing_fields']);
        }

        if (!empty($config['shipping_fields'])) {
            $fields['shipping'] = $this->apply_field_config($fields['shipping'] ?? [], $config['shipping_fields']);
        }

        if (!empty($config['order_fields'])) {
            $fields['order'] = $this->apply_field_config($fields['order'] ?? [], $config['order_fields']);
        }

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
                if (!empty($config['label'])) {
                    $fields[$field_key]['label'] = $config['label'];
                }

                if (!empty($config['placeholder'])) {
                    $fields[$field_key]['placeholder'] = $config['placeholder'];
                }

                if (isset($config['required'])) {
                    $fields[$field_key]['required'] = $config['required'];
                }

                if (isset($config['priority'])) {
                    $fields[$field_key]['priority'] = $config['priority'];
                }
            }
        }

        return $fields;
    }

    /**
     * Modify default locale settings for address placeholders
     *
     * @param array $locale Default locale settings
     * @return array Modified locale
     */
    public function modify_locale_defaults(array $locale): array
    {
        $config = get_option('nt_checkout_fields_config', []);
        
        if (!empty($config['billing_fields']['billing_address_1']['placeholder'])) {
            $locale['address_1']['placeholder'] = $config['billing_fields']['billing_address_1']['placeholder'];
        }
        
        if (!empty($config['billing_fields']['billing_address_2']['placeholder'])) {
            $locale['address_2']['placeholder'] = $config['billing_fields']['billing_address_2']['placeholder'];
        }
        
        return $locale;
    }

    /**
     * Modify country-specific locale settings for address placeholders
     *
     * @param array $locale Country locale settings
     * @return array Modified locale
     */
    public function modify_country_locale(array $locale): array
    {
        $config = get_option('nt_checkout_fields_config', []);
        
        $address_1_placeholder = $config['billing_fields']['billing_address_1']['placeholder'] ?? '';
        $address_2_placeholder = $config['billing_fields']['billing_address_2']['placeholder'] ?? '';
        
        foreach ($locale as $country_code => $country_locale) {
            if (!empty($address_1_placeholder) && isset($locale[$country_code]['address_1'])) {
                $locale[$country_code]['address_1']['placeholder'] = $address_1_placeholder;
            }
            if (!empty($address_2_placeholder) && isset($locale[$country_code]['address_2'])) {
                $locale[$country_code]['address_2']['placeholder'] = $address_2_placeholder;
            }
        }
        
        return $locale;
    }
}
