<?php

namespace NabaTech\Tweaker\Modules\WPSecretLogin\Services;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Option Service
 */
class OptionService {
    /**
     * Option key
     */
    private const OPTION_KEY = 'nt_wp_secret_login_settings';

    /**
     * Get all settings
     *
     * @return array
     */
    public function get_settings(): array {
        $defaults = [
            'login_slug' => 'login',
            'redirect_slug' => '404',
            'redirect_to_secret' => false,
            'enabled' => true,
        ];

        $settings = get_option(self::OPTION_KEY, []);

        // Migration from WPS Hide Login if not set
        if (empty($settings)) {
            $whl_page = get_option('whl_page');
            if ($whl_page) {
                $settings['login_slug'] = $whl_page;
                $settings['enabled'] = true;
                update_option(self::OPTION_KEY, $settings);
            }
        }

        return wp_parse_args($settings, $defaults);
    }

    /**
     * Get a specific setting
     *
     * @param string $key Setting key
     * @param mixed $default Default value
     * @return mixed
     */
    public function get(string $key, $default = null) {
        $settings = $this->get_settings();
        return $settings[$key] ?? $default;
    }

    /**
     * Update settings
     *
     * @param array $new_settings New settings
     * @return bool
     */
    public function update(array $new_settings): bool {
        $settings = $this->get_settings();
        $updated_settings = wp_parse_args($new_settings, $settings);
        return update_option(self::OPTION_KEY, $updated_settings);
    }
}
