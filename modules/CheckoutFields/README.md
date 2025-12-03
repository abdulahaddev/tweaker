# CheckoutFields Module

WooCommerce checkout field customization and validation module for Tweaker.

## Features

- **Field Customization**: Modify labels, placeholders, and validation for checkout fields
- **Tabbed Interface**: Organized interface for billing, shipping, and order fields
- **Inline Validation**: Real-time validation with error messages next to fields
- **Migration Support**: Automatically migrates data from WooCommerce Checkout Field Editor

## Configuration

All settings are stored in the `nt_checkout_fields_config` option with the following structure:

```php
[
    'billing_fields' => [
        'billing_first_name' => [
            'label' => 'Name',
            'placeholder' => 'Enter your full name',
            'required' => true,
            'enabled' => true,
            'priority' => 10,
        ],
        // ... more fields
    ],
    'shipping_fields' => [...],
    'order_fields' => [...],
]
```

## Hooks

### Actions
- `nt_checkout_fields_saved` - Fires after settings are saved

### Filters
- `nt_checkout_fields_config` - Filter the configuration before applying
- `nt_checkout_field_validation` - Custom validation logic

## Usage

After activation, navigate to **Tweaker > Checkout Fields** in the WordPress admin to configure your checkout fields.

## Requirements

- WordPress 6.9+
- PHP 8.1+
- WooCommerce 8.0+

## License

GPL-2.0+ - Naba Tech Ltd
