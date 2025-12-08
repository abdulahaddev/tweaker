<?php
/**
 * Tab Renderer - Reusable admin tab UI
 *
 * @package NabaTech\Tweaker\Core\Admin
 */

namespace NabaTech\Tweaker\Core\Admin;

/**
 * Tab renderer class
 */
class TabRenderer
{
    /**
     * Render tab navigation
     *
     * @param array  $tabs        Array of tabs ['slug' => 'Title']
     * @param string $current_tab Current active tab slug
     * @param string $page_slug   Admin page slug
     */
    public static function render_tabs(array $tabs, string $current_tab, string $page_slug): void
    {
        if (empty($tabs)) {
            return;
        }

        echo '<h2 class="nav-tab-wrapper">';

        foreach ($tabs as $slug => $title) {
            $active_class = ($slug === $current_tab) ? ' nav-tab-active' : '';
            $url = admin_url('admin.php?page=' . $page_slug . '&tab=' . $slug);

            printf(
                '<a href="%s" class="nav-tab%s">%s</a>',
                esc_url($url),
                esc_attr($active_class),
                esc_html($title)
            );
        }

        echo '</h2>';
    }

    /**
     * Get current tab from $_GET
     *
     * @param string $default Default tab if none specified
     * @return string Current tab slug
     */
    public static function get_current_tab(string $default = ''): string
    {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Tab navigation doesn't require nonce verification.
        return isset($_GET['tab']) ? sanitize_text_field(wp_unslash($_GET['tab'])) : $default;
    }

    /**
     * Render tab content wrapper start
     */
    public static function render_content_start(): void
    {
        echo '<div class="nt-tab-content" style="margin-top: 10px;">';
    }

    /**
     * Render tab content wrapper end
     */
    public static function render_content_end(): void
    {
        echo '</div>';
    }
}
