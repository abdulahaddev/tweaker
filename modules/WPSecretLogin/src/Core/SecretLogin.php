<?php

namespace NabaTech\Tweaker\Modules\WPSecretLogin\Core;

use NabaTech\Tweaker\Modules\WPSecretLogin\Services\OptionService;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Core Secret Login Logic
 */
class SecretLogin {
    /**
     * @var OptionService
     */
    private $option_service;

    /**
     * @var bool
     */
    private $wp_login_php = false;

    /**
     * Constructor
     */
    public function __construct() {
        $this->option_service = new OptionService();
    }

    /**
     * Initialize hooks
     */
    public function init(): void {
        if (!$this->option_service->get('enabled')) {
            return;
        }

        // Main logic to intercept requests
        if (did_action('plugins_loaded')) {
            $this->check_request();
        } else {
            add_action('plugins_loaded', [$this, 'check_request'], 9999);
        }

        add_filter('site_url', [$this, 'filter_site_url'], 10, 4);
        add_filter('network_site_url', [$this, 'filter_site_url'], 10, 3);
        add_filter('wp_redirect', [$this, 'filter_wp_redirect'], 10, 2);
        add_filter('login_url', [$this, 'filter_login_url'], 10, 3);
        add_action('wp_loaded', [$this, 'wp_loaded']);
        add_filter('site_option_welcome_email', [$this, 'filter_welcome_email']);
    }

    /**
     * Check the current request and modify global state or redirect immediately
     */
    public function check_request(): void {
        global $pagenow;

        if (is_multisite() && (strpos($_SERVER['REQUEST_URI'], 'wp-signup') !== false || strpos($_SERVER['REQUEST_URI'], 'wp-activate') !== false)) {
             return;
        }

        $request_uri = rawurldecode($_SERVER['REQUEST_URI']);
        $login_slug = $this->option_service->get('login_slug');

        // 1. Check if accessing the custom login slug
        if (untrailingslashit(parse_url($request_uri, PHP_URL_PATH)) === home_url($login_slug, 'relative') || (!get_option('permalink_structure') && isset($_GET[$login_slug]))) {
            $_SERVER['SCRIPT_NAME'] = '/wp-login.php'; // Trick WP into thinking it's wp-login.php
            $pagenow = 'wp-login.php';
            return;
        }

        // 2. Check if accessing wp-login.php or wp-register.php directly
        if ((strpos($request_uri, 'wp-login.php') !== false || untrailingslashit($request_uri) === site_url('wp-login', 'relative') || strpos($request_uri, 'wp-register.php') !== false || untrailingslashit($request_uri) === site_url('wp-register', 'relative')) && !is_admin()) {
            
            if ($this->option_service->get('redirect_to_secret')) {
                // Safe to redirect early
                $url = $this->new_login_url();
                header('Location: ' . $url);
                exit;
            } else {
                $redirect_slug = $this->option_service->get('redirect_slug', '404');
                if ($redirect_slug === '404') {
                    // Safe to redirect early
                    $url = $this->new_redirect_url();
                    header('Location: ' . $url);
                    exit;
                } else {
                    // Custom Page ID. Unsafe to redirect early (needs WP_Rewrite).
                    // Fallback to wp_loaded
                    $this->wp_login_php = true;
                    $_SERVER['REQUEST_URI'] = $this->user_trailingslashit('/' . str_repeat('-/', 10)); // Obfuscate
                    $pagenow = 'index.php';
                }
            }
        } 
    }

    /**
     * Handle redirects and access control
     */
    public function wp_loaded(): void {
        global $pagenow;

        // Handle Secret Login Page Loading
        if ($pagenow === 'wp-login.php' && isset($_SERVER['SCRIPT_NAME']) && $_SERVER['SCRIPT_NAME'] === '/wp-login.php') {
             global $error, $interim_login, $action, $user_login;
             @require_once ABSPATH . 'wp-login.php';
             die();
        }

        // Handle wp-admin Access Control
        $request = parse_url(rawurldecode($_SERVER['REQUEST_URI']));
        if (is_admin() && !is_user_logged_in() && !defined('WP_CLI') && !defined('DOING_AJAX') && !defined('DOING_CRON')) {
             // Block access to wp-admin if not logged in
             // Except for admin-post.php and options.php which might be needed
             if ($pagenow !== 'admin-post.php' && (isset($request['path']) ? $request['path'] : '') !== '/wp-admin/options.php') {
                 if ($this->option_service->get('redirect_to_secret')) {
                     wp_safe_redirect($this->new_login_url());
                 } else {
                     wp_safe_redirect($this->new_redirect_url());
                 }
                 die();
             }
        }

        // Fallback for Custom Page Redirect (if not handled in check_request)
        if ($this->wp_login_php) {
            wp_safe_redirect($this->new_redirect_url());
            die();
        }
    }

    /**
     * Filter site_url to replace wp-login.php with custom slug
     */
    public function filter_site_url($url, $path, $scheme, $blog_id = null) {
        // Avoid altering URLs in admin context to prevent redirect loops
        if (is_admin()) {
            return $url;
        }
        return $this->replace_login_url($url, $scheme);
    }

    /**
     * Filter wp_redirect to replace wp-login.php with custom slug
     */
    public function filter_wp_redirect($location, $status) {
        return $this->replace_login_url($location);
    }

    /**
     * Filter login_url
     */
    public function filter_login_url($login_url, $redirect, $force_reauth) {
        return $this->replace_login_url($login_url);
    }

    /**
     * Helper to replace login URL
     */
    private function replace_login_url($url, $scheme = null) {
        if (strpos($url, 'wp-login.php') !== false) {
            // Don't mess with postpass
            if (strpos($url, 'action=postpass') !== false) {
                return $url;
            }

            $login_slug = $this->option_service->get('login_slug');
            $new_url = $this->new_login_url($scheme);

            // Preserve query args
            $query = parse_url($url, PHP_URL_QUERY);
            if ($query) {
                $new_url = add_query_arg($query, $new_url);
            }
            
            return $new_url;
        }
        return $url;
    }

    /**
     * Generate the new login URL
     */
    private function new_login_url($scheme = null) {
        $login_slug = $this->option_service->get('login_slug');
        
        if (get_option('permalink_structure')) {
            return $this->user_trailingslashit(home_url('/', $scheme) . $login_slug);
        } else {
            return home_url('/', $scheme) . '?' . $login_slug;
        }
    }

    /**
     * Generate the new redirect URL (for unauthorized access)
     */
    private function new_redirect_url($scheme = null) {
        $redirect_slug = $this->option_service->get('redirect_slug', '404');
        
        // If numeric AND NOT '404', it's a page ID
        if ($redirect_slug !== '404' && is_numeric($redirect_slug)) {
            $permalink = get_permalink($redirect_slug);
            if ($permalink) {
                return $permalink;
            }
        }

        // Default or fallback to 404 slug
        if ($redirect_slug === '404' || empty($redirect_slug)) {
             $redirect_slug = '404';
        }
        
        if (get_option('permalink_structure')) {
            return $this->user_trailingslashit(home_url('/', $scheme) . $redirect_slug);
        } else {
            return home_url('/', $scheme) . '?' . $redirect_slug;
        }
    }

    /**
     * Filter welcome email
     */
    public function filter_welcome_email($value) {
        return str_replace('wp-login.php', $this->option_service->get('login_slug'), $value);
    }

    /**
     * Helper for trailing slash
     */
    private function user_trailingslashit($string) {
        global $wp_rewrite;
        if (is_object($wp_rewrite) && $wp_rewrite->use_trailing_slashes) {
            return trailingslashit($string);
        }
        return untrailingslashit($string);
    }
}
