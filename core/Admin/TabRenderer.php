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

        echo '<div class="nt-tabs-wrapper">';
        echo '<ul class="nt-tabs">';

        foreach ($tabs as $slug => $title) {
            $active_class = ($slug === $current_tab) ? ' active' : '';
            $url = admin_url('admin.php?page=' . $page_slug . '&tab=' . $slug);

            printf(
                '<li class="nt-tab%s"><a href="%s">%s</a></li>',
                esc_attr($active_class),
                esc_url($url),
                esc_html($title)
            );
        }

        echo '</ul>';
        echo '</div>';
    }

    /**
     * Get current tab from $_GET
     *
     * @param string $default Default tab if none specified
     * @return string Current tab slug
     */
    public static function get_current_tab(string $default = ''): string
    {
        return isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : $default;
    }

    /**
     * Render tab content wrapper start
     */
    public static function render_content_start(): void
    {
        echo '<div class="nt-tab-content">';
    }

    /**
     * Render tab content wrapper end
     */
    public static function render_content_end(): void
    {
        echo '</div>';
    }
}
