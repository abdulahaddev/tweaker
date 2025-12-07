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
     * Cached configuration
     */
    private ?array $config = null;
    
    /**
     * Get configuration with caching
     *
     * @return array
     */
    private function get_config(): array
    {
        if ($this->config === null) {
            $this->config = get_option('nt_checkout_fields_config', []);
        }
        return $this->config;
    }
    
    /**
     * Locale field mapping (locale_key => config_key)
     */
    private const LOCALE_FIELD_MAPPING = [
        'address_1' => 'billing_address_1',
        'address_2' => 'billing_address_2',
        'city' => 'billing_city',
        'state' => 'billing_state',
        'postcode' => 'billing_postcode',
    ];
    
    /**
     * Apply locale field overrides from configuration
     *
     * @param array &$locale Locale array to modify (by reference)
     * @param string $field_key Locale field key (e.g., 'address_1')
     * @param array $config Configuration array
     */
    private function apply_locale_override(array &$locale, string $field_key, array $config): void
    {
        $config_key = self::LOCALE_FIELD_MAPPING[$field_key] ?? null;
        if (!$config_key) {
            return;
        }
        
        if (!empty($config['billing_fields'][$config_key]['label'])) {
            $locale[$field_key]['label'] = $config['billing_fields'][$config_key]['label'];
        }
        
        if (!empty($config['billing_fields'][$config_key]['placeholder'])) {
            $locale[$field_key]['placeholder'] = $config['billing_fields'][$config_key]['placeholder'];
        }
    }

    /**
     * Modify WooCommerce checkout fields
     *
     * @param array $fields Checkout fields
     * @return array Modified fields
     */
    public function modify_checkout_fields(array $fields): array
    {
        $config = $this->get_config();

        // Billing
        if (!empty($config['billing_fields']) && ($config['billing_active'] ?? true)) {
            $fields['billing'] = $this->apply_field_config($fields['billing'] ?? [], $config['billing_fields']);
        }

        // Shipping
        if (!empty($config['shipping_fields']) && ($config['shipping_active'] ?? true)) {
            $fields['shipping'] = $this->apply_field_config($fields['shipping'] ?? [], $config['shipping_fields']);
        }

        // Order
        if (!empty($config['order_fields']) && ($config['order_active'] ?? true)) {
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
        $config = $this->get_config();
        
        if (!($config['billing_active'] ?? true)) {
            return $locale;
        }
        
        // Apply overrides for all mapped fields
        foreach (array_keys(self::LOCALE_FIELD_MAPPING) as $field_key) {
            $this->apply_locale_override($locale, $field_key, $config);
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
        $config = $this->get_config();
        
        if (!($config['billing_active'] ?? true)) {
            return $locale;
        }
        
        // Apply overrides for each country
        foreach ($locale as $country_code => $country_locale) {
            foreach (array_keys(self::LOCALE_FIELD_MAPPING) as $field_key) {
                // Only apply if this country has this field defined
                if (isset($locale[$country_code][$field_key])) {
                    $this->apply_locale_override($locale[$country_code], $field_key, $config);
                }
            }
        }
        
        return $locale;
    }
}
