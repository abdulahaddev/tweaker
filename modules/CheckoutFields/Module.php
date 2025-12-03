<?php
/**
 * CheckoutFields Module Bootstrap
 *
 * @package NabaTech\Tweaker\Modules
 */

namespace NabaTech\Tweaker\Modules;

use NabaTech\Tweaker\Core\Admin\TabRenderer;
use NabaTech\Tweaker\Core\Assets;

/**
 * CheckoutFields module class
 */
class CheckoutFields_Module
{
    /**
     * Module directory
     */
    private string $module_dir;

    /**
     * Module URL
     */
    private string $module_url;

    /**
     * Module manifest
     */
    private array $manifest;

    /**
     * Constructor
     *
     * @param string $module_dir Module directory path
     * @param array  $manifest   Module manifest data
     */
    public function __construct(string $module_dir, array $manifest)
    {
        $this->module_dir = trailingslashit($module_dir);
        $this->module_url = str_replace(NT_PLUGIN_DIR, NT_PLUGIN_URL, $this->module_dir);
        $this->manifest = $manifest;

        // Load services
        require_once $this->module_dir . 'src/Services/FieldService.php';
        require_once $this->module_dir . 'src/Services/ValidationService.php';
        require_once $this->module_dir . 'src/Admin/SettingsPage.php';
    }

    /**
     * Get module ID
     */
    public function get_id(): string
    {
        return $this->manifest['id'];
    }

    /**
     * Get module manifest
     */
    public function get_manifest(): array
    {
        return $this->manifest;
    }

    /**
     * Register module hooks with WordPress
     */
    public function register_hooks(): void
    {
        // Only load WooCommerce hooks if WooCommerce is active
        if (!class_exists('WooCommerce')) {
            add_action('admin_notices', [$this, 'woocommerce_missing_notice']);
            return;
        }

        // Initialize services
        $field_service = new Services\FieldService();
        $validation_service = new Services\ValidationService();

        // Register WooCommerce hooks with very high priority to override other plugins
        add_filter('woocommerce_checkout_fields', [$field_service, 'modify_checkout_fields'], 99999);
        
        // Filter error messages to use our custom labels
        add_filter('woocommerce_add_error', [$validation_service, 'customize_error_messages'], 10, 1);

        // Admin assets
        add_action('nt_enqueue_module_assets', [$this, 'enqueue_assets']);
    }

    /**
     * Install module
     */
    public function install(): void
    {
        // Set default configuration
        $default_config = $this->get_default_config();
        add_option('nt_checkout_fields_config', $default_config);
        add_option('nt_checkout_fields_version', $this->manifest['version']);

        nt_log('CheckoutFields module installed');
    }

    /**
     * Activate module
     */
    public function activate(): void
    {
        // Run migration if needed
        $this->run_migrations();

        nt_log('CheckoutFields module activated');
    }

    /**
     * Deactivate module
     */
    public function deactivate(): void
    {
        nt_log('CheckoutFields module deactivated');
    }

    /**
     * Uninstall module
     */
    public function uninstall(): void
    {
        // Remove all module options
        delete_option('nt_checkout_fields_config');
        delete_option('nt_checkout_fields_version');
        delete_option('_nt_checkout_fields_old_backup');

        nt_log('CheckoutFields module uninstalled');
    }

    /**
     * Run migration scripts
     */
    private function run_migrations(): void
    {
        $current_version = get_option('nt_checkout_fields_version', '0.0.0');

        foreach ($this->manifest['migrations']['scripts'] as $script) {
            $script_path = $this->module_dir . $script;
            if (file_exists($script_path)) {
                require_once $script_path;
            }
        }
    }

    /**
     * Render admin page
     */
    public function render_admin_page(): void
    {
        $settings_page = new Admin\SettingsPage($this->get_current_config());
        $settings_page->render();
    }

    /**
     * Enqueue module assets
     */
    public function enqueue_assets($screen): void
    {
        Assets::enqueue_module_assets(
            $this->get_id(),
            $this->module_url,
            $this->manifest['assets']['admin'],
            $this->manifest['assets']['admin']['load_on']
        );
    }

    /**
     * WooCommerce missing notice
     */
    public function woocommerce_missing_notice(): void
    {
        ?>
        <div class="notice notice-error">
            <p>
                <strong>Tweaker - Checkout Fields:</strong>
                This module requires WooCommerce to be installed and activated.
            </p>
        </div>
        <?php
    }

    /**
     * Get default configuration
     */
    private function get_default_config(): array
    {
        return [
            'billing_fields' => [
                'billing_first_name' => [
                    'label' => 'Name',
                    'placeholder' => 'Enter your full name',
                    'required' => true,
                    'enabled' => true,
                    'priority' => 10,
                ],
                'billing_address_1' => [
                    'label' => 'Address',
                    'placeholder' => 'Enter your address',
                    'required' => true,
                    'enabled' => true,
                    'priority' => 50,
                ],
                'billing_state' => [
                    'label' => 'State',
                    'placeholder' => 'Select your state',
                    'required' => true,
                    'enabled' => true,
                    'priority' => 80,
                ],
                'billing_phone' => [
                    'label' => 'Phone',
                    'placeholder' => 'Enter your phone number',
                    'required' => true,
                    'enabled' => true,
                    'priority' => 100,
                ],
                'billing_email' => [
                    'label' => 'Email',
                    'placeholder' => 'Enter your email',
                    'required' => true,
                    'enabled' => true,
                    'priority' => 110,
                ],
            ],
            'shipping_fields' => [],
            'order_fields' => [
                'order_comments' => [
                    'label' => 'Order Comments',
                    'placeholder' => 'Notes about your order',
                    'required' => false,
                    'enabled' => true,
                    'priority' => 10,
                ],
            ],
        ];
    }

    /**
     * Get current configuration
     */
    private function get_current_config(): array
    {
        $config = get_option('nt_checkout_fields_config', []);
        return wp_parse_args($config, $this->get_default_config());
    }
}
