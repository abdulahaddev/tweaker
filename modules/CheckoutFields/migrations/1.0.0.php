<?php
/**
 * CheckoutFields Migration v1.0.0
 * Migrates data from WooCommerce Checkout Field Editor (if exists)
 *
 * @package NabaTech\Tweaker\Modules\CheckoutFields
 */

// Run migration
nt_checkout_fields_migrate_1_0_0();

/**
 * Perform migration
 */
function nt_checkout_fields_migrate_1_0_0()
{
    // Check if already migrated
    $current_version = get_option('nt_checkout_fields_version');
    if ($current_version === '1.0.0') {
        return;
    }

    // Check if old plugin data exists
    $old_settings = get_option('wc_checkout_field_editor_settings');

    if (!empty($old_settings)) {
        nt_log('Starting CheckoutFields migration from old plugin');

        // Transform old structure to new format
        $new_config = nt_checkout_fields_transform_settings($old_settings);

        // Save to new option
        update_option('nt_checkout_fields_config', $new_config);

        // Backup old data
        update_option('_nt_checkout_fields_old_backup', $old_settings);

        // Schedule cleanup after 30 days
        if (!wp_next_scheduled('nt_cleanup_old_checkout_fields_data')) {
            wp_schedule_single_event(
                time() + (30 * DAY_IN_SECONDS),
                'nt_cleanup_old_checkout_fields_data'
            );
        }

        nt_log('CheckoutFields migration completed successfully');
    } else {
        nt_log('No old plugin data found, using default configuration');
    }

    // Set migration version
    update_option('nt_checkout_fields_version', '1.0.0');
}

/**
 * Transform old settings to new format
 *
 * @param array $old_settings Old plugin settings
 * @return array New module configuration
 */
function nt_checkout_fields_transform_settings(array $old_settings): array
{
    $new_config = [
        'billing_fields' => [],
        'shipping_fields' => [],
        'order_fields' => [],
    ];

    // Map old field groups to new structure
    $field_group_map = [
        'billing' => 'billing_fields',
        'shipping' => 'shipping_fields',
        'order' => 'order_fields',
    ];

    foreach ($field_group_map as $old_group => $new_group) {
        if (empty($old_settings[$old_group])) {
            continue;
        }

        foreach ($old_settings[$old_group] as $field_key => $field_data) {
            $new_config[$new_group][$field_key] = [
                'label' => $field_data['label'] ?? $field_data['name'] ?? '',
                'placeholder' => $field_data['placeholder'] ?? '',
                'required' => (bool) ($field_data['required'] ?? false),
                'enabled' => (bool) ($field_data['enabled'] ?? true),
                'priority' => (int) ($field_data['priority'] ?? 10),
            ];
        }
    }

    return $new_config;
}

/**
 * Cleanup action - runs 30 days after migration
 */
add_action('nt_cleanup_old_checkout_fields_data', function () {
    delete_option('_nt_checkout_fields_old_backup');
    nt_log('Old CheckoutFields plugin data cleaned up');
});
