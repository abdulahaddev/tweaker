<?php
/**
 * Assets Manager - Conditional asset loading
 *
 * @package NabaTech\Tweaker\Core
 */

namespace NabaTech\Tweaker\Core;

/**
 * Assets manager class
 */
class Assets
{
    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets(): void
    {
        $screen = get_current_screen();

        // Load shared Tweaker admin assets on Tweaker pages
        if ($screen && strpos($screen->id, 'tweaker') !== false) {
            $this->enqueue_shared_assets();
        }

        // Let modules enqueue their own assets
        do_action('nt_enqueue_module_assets', $screen);
    }

    /**
     * Enqueue shared admin CSS and JS
     */
    private function enqueue_shared_assets(): void
    {
        // Shared CSS
        wp_enqueue_style(
            'nt-admin',
            NT_PLUGIN_URL . 'assets/css/tweaker-admin.css',
            [],
            $this->get_file_version('assets/css/tweaker-admin.css')
        );

        // Shared JS
        wp_enqueue_script(
            'nt-admin',
            NT_PLUGIN_URL . 'assets/js/tweaker-admin.js',
            ['jquery'],
            $this->get_file_version('assets/js/tweaker-admin.js'),
            true
        );
    }

    /**
     * Get file version based on modification time
     *
     * @param string $file Relative file path
     * @return string Version string
     */
    private function get_file_version(string $file): string
    {
        $file_path = NT_PLUGIN_DIR . $file;
        if (file_exists($file_path)) {
            return (string) filemtime($file_path);
        }
        return NT_PLUGIN_VERSION;
    }

    /**
     * Enqueue module assets
     *
     * @param string $module_id    Module ID
     * @param string $module_url   Module URL
     * @param array  $assets       Assets array from manifest
     * @param array  $allowed_screens Screens to load on
     */
    public static function enqueue_module_assets(
        string $module_id,
        string $module_url,
        array $assets,
        array $allowed_screens
    ): void {
        $screen = get_current_screen();
        if (!$screen || !in_array($screen->id, $allowed_screens, true)) {
            return;
        }

        // Enqueue JS files
        if (!empty($assets['js'])) {
            foreach ($assets['js'] as $js_file) {
                $js_path = $module_url . $js_file;
                wp_enqueue_script(
                    "nt-module-{$module_id}",
                    $js_path,
                    ['jquery', 'nt-admin'],
                    self::get_module_file_version($module_url, $js_file),
                    true
                );
            }
        }

        // Enqueue CSS files
        if (!empty($assets['css'])) {
            foreach ($assets['css'] as $css_file) {
                $css_path = $module_url . $css_file;
                wp_enqueue_style(
                    "nt-module-{$module_id}",
                    $css_path,
                    ['nt-admin'],
                    self::get_module_file_version($module_url, $css_file)
                );
            }
        }
    }

    /**
     * Get module file version
     *
     * @param string $module_url Module URL
     * @param string $file       File path
     * @return string Version
     */
    private static function get_module_file_version(string $module_url, string $file): string
    {
        $module_dir = str_replace(NT_PLUGIN_URL, NT_PLUGIN_DIR, $module_url);
        $file_path = $module_dir . $file;

        if (file_exists($file_path)) {
            return (string) filemtime($file_path);
        }

        return NT_PLUGIN_VERSION;
    }
}
