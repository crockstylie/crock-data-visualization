<?php

/**
 * Core plugin class - orchestrates all plugin functionality
 *
 * Defines internationalization, admin-specific hooks, and public-facing site hooks.
 * This is the main entry point that coordinates all plugin components.
 *
 * @package    Crock_Data_Visualizer
 * @subpackage Crock_Data_Visualizer/includes
 * @since      1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class Crock_Data_Visualizer
{
    /**
     * The loader that's responsible for maintaining and registering all hooks that power
     * the plugin.
     *
     * @since  1.0.0
     * @var    Crock_Data_Visualizer_Loader|null $loader Maintains and registers all hooks for the plugin.
     */
    private ?Crock_Data_Visualizer_Loader $loader = null;
    
    /**
     * The unique identifier of this plugin.
     *
     * @since  1.0.0
     * @var    string $plugin_name The string used to uniquely identify this plugin.
     */
    private string $plugin_name;
    
    /**
     * The current version of the plugin.
     *
     * @since  1.0.0
     * @var    string $version The current version of the plugin.
     */
    private string $version;
    
    /**
     * The admin class instance.
     *
     * @since  1.0.0
     * @var    Crock_Data_Visualizer_Admin|null $admin The admin class instance.
     */
    private ?Crock_Data_Visualizer_Admin $admin = null;
    
    /**
     * The public class instance.
     *
     * @since  1.0.0
     * @var    Crock_Data_Visualizer_Public|null $public The public class instance.
     */
    private ?Crock_Data_Visualizer_Public $public = null;
    
    /**
     * The database manager instance.
     *
     * @since  1.0.0
     * @var    Crock_Data_Visualizer_Database|null $database The database manager instance.
     */
    private ?Crock_Data_Visualizer_Database $database = null;
    
    /**
     * The importer instance.
     *
     * @since  1.0.0
     * @var    Crock_Data_Visualizer_Importer|null $importer The importer instance.
     */
    private ?Crock_Data_Visualizer_Importer $importer = null;
    
    /**
     * The shortcode manager instance.
     *
     * @since  1.0.0
     * @var    Crock_Data_Visualizer_Shortcode|null $shortcode The shortcode manager instance.
     */
    private ?Crock_Data_Visualizer_Shortcode $shortcode = null;
    
    /**
     * Define the core functionality of the plugin.
     *
     * @since 1.0.0
     */
    public function __construct()
    {
        $this->version = defined('CROCK_DATA_VISUALIZER_VERSION')
            ? CROCK_DATA_VISUALIZER_VERSION
            : '1.0.0';
        
        $this->plugin_name = 'crock-data-visualizer';
        
        $this->load_dependencies();
        $this->set_locale();
        $this->init_components();
        $this->define_admin_hooks();
        $this->define_public_hooks();
        $this->define_shared_hooks();
    }
    
    /**
     * Load the required dependencies for this plugin.
     *
     * @since 1.0.0
     */
    private function load_dependencies(): void
    {
        $includes_path = CROCK_DATA_VISUALIZER_PLUGIN_DIR . 'includes/';
        $admin_path = CROCK_DATA_VISUALIZER_PLUGIN_DIR . 'admin/';
        $public_path = CROCK_DATA_VISUALIZER_PLUGIN_DIR . 'public/';
        
        // Core classes - Load in dependency order
        require_once $includes_path . 'Crock_Data_Visualizer_Loader.php';
        require_once $includes_path . 'Crock_Data_Visualizer_i18n.php';
        require_once $includes_path . 'Crock_Data_Visualizer_Database.php';
        require_once $includes_path . 'Crock_Data_Visualizer_Importer.php';
        require_once $includes_path . 'Crock_Data_Visualizer_Shortcode.php';
        
        // Admin and public classes
        require_once $admin_path . 'Crock_Data_Visualizer_Admin.php';
        require_once $public_path . 'Crock_Data_Visualizer_Public.php';
        
        // Initialize the loader
        $this->loader = new Crock_Data_Visualizer_Loader();
    }
    
    /**
     * Initialize component instances.
     *
     * @since 1.0.0
     */
    private function init_components(): void
    {
        // Initialize database manager first (other components may depend on it)
        $this->database = new Crock_Data_Visualizer_Database();
        
        // Initialize other core components
        $this->importer = new Crock_Data_Visualizer_Importer($this->database);
        $this->shortcode = new Crock_Data_Visualizer_Shortcode($this->database);
        
        // Initialize admin and public components
        $this->admin = new Crock_Data_Visualizer_Admin($this->get_plugin_name(), $this->get_version());
        $this->public = new Crock_Data_Visualizer_Public($this->get_plugin_name(), $this->get_version());
    }
    
    /**
     * Define the locale for this plugin for internationalization.
     *
     * @since 1.0.0
     */
    private function set_locale(): void
    {
        $plugin_i18n = new Crock_Data_Visualizer_i18n();
        $this->loader->add_action('plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');
    }
    
    /**
     * Register all hooks related to the admin area functionality.
     *
     * @since 1.0.0
     */
    private function define_admin_hooks(): void
    {
        if (!$this->admin) {
            return;
        }
        
        // Only load admin hooks in admin area
        if (!is_admin()) {
            return;
        }
        
        // Enqueue admin assets
        $this->loader->add_action('admin_enqueue_scripts', $this->admin, 'enqueue_styles');
        $this->loader->add_action('admin_enqueue_scripts', $this->admin, 'enqueue_scripts');
        
        // Admin menu
        $this->loader->add_action('admin_menu', $this->admin, 'add_plugin_admin_menu');
        
        // AJAX hooks for file operations
        $this->loader->add_action('wp_ajax_cdv_analyze_file', $this->admin, 'ajax_analyze_file');
        $this->loader->add_action('wp_ajax_cdv_import_file', $this->admin, 'ajax_import_file');
        $this->loader->add_action('wp_ajax_cdv_get_datasets', $this->admin, 'ajax_get_datasets');
        $this->loader->add_action('wp_ajax_cdv_delete_dataset', $this->admin, 'ajax_delete_dataset');
        $this->loader->add_action('wp_ajax_cdv_bulk_action', $this->admin, 'ajax_bulk_action');
        $this->loader->add_action('wp_ajax_cdv_get_dataset_preview', $this->admin, 'ajax_get_dataset_preview');
        
        // Admin initialization
        $this->loader->add_action('admin_init', $this, 'admin_init');
        
        // Plugin activation/deactivation hooks
        $this->loader->add_action('admin_notices', $this, 'admin_notices');
    }
    
    /**
     * Register all hooks related to the public-facing functionality.
     *
     * @since 1.0.0
     */
    private function define_public_hooks(): void
    {
        if (!$this->public) {
            return;
        }
        
        // Enqueue public assets
        $this->loader->add_action('wp_enqueue_scripts', $this->public, 'enqueue_styles');
        $this->loader->add_action('wp_enqueue_scripts', $this->public, 'enqueue_scripts');
        
        // Public AJAX hooks (for frontend interactions)
        $this->loader->add_action('wp_ajax_nopriv_cdv_get_chart_data', $this->public, 'ajax_get_chart_data');
        $this->loader->add_action('wp_ajax_cdv_get_chart_data', $this->public, 'ajax_get_chart_data');
    }
    
    /**
     * Register hooks that are shared between admin and public areas.
     *
     * @since 1.0.0
     */
    private function define_shared_hooks(): void
    {
        // Shortcode registration
        if ($this->shortcode) {
            $this->loader->add_action('init', $this->shortcode, 'register_shortcodes');
        }
        
        // Database schema updates
        if ($this->database) {
            //$this->loader->add_action('init', $this->database, 'check_schema_version');
        }
        
        // REST API endpoints
        $this->loader->add_action('rest_api_init', $this, 'register_rest_routes');
        
        // Cleanup hooks
        $this->loader->add_action('wp_scheduled_delete', $this, 'cleanup_old_data');
    }
    
    /**
     * Initialize admin-specific functionality.
     *
     * @since 1.0.0
     */
    public function admin_init(): void
    {
        // Check if database needs to be created/updated
        //$this->database?->maybe_create_tables();
        
        // Handle plugin settings registration
        $this->register_settings();
        
        // Add admin capabilities if needed
        $this->maybe_add_capabilities();
    }
    
    /**
     * Display admin notices.
     *
     * @since 1.0.0
     */
    public function admin_notices(): void
    {
        // Check for plugin requirements
        if (!$this->check_requirements()) {
            $this->display_requirements_notice();
        }
        
        // Display activation notice
        if (get_transient('cdv_activation_notice')) {
            $this->display_activation_notice();
            delete_transient('cdv_activation_notice');
        }
    }
    
    /**
     * Register plugin settings.
     *
     * @since 1.0.0
     */
    private function register_settings(): void
    {
        // Register settings sections and fields
        register_setting('cdv_settings', 'cdv_max_file_size', [
            'type' => 'integer',
            'default' => 52428800, // 50MB
            'sanitize_callback' => 'absint'
        ]);
        
        register_setting('cdv_settings', 'cdv_allowed_file_types', [
            'type' => 'array',
            'default' => ['csv', 'json', 'xml'],
            'sanitize_callback' => [$this, 'sanitize_file_types']
        ]);
        
        register_setting('cdv_settings', 'cdv_cache_duration', [
            'type' => 'integer',
            'default' => 3600, // 1 hour
            'sanitize_callback' => 'absint'
        ]);
    }
    
    /**
     * Sanitize file types setting.
     *
     * @since 1.0.0
     * @param array $file_types Array of file type extensions.
     * @return array Sanitized file types.
     */
    public function sanitize_file_types(array $file_types): array
    {
        $allowed_types = ['csv', 'json', 'xml', 'xlsx', 'xls'];
        return array_intersect($file_types, $allowed_types);
    }
    
    /**
     * Add plugin capabilities to administrator role.
     *
     * @since 1.0.0
     */
    private function maybe_add_capabilities(): void
    {
        $role = get_role('administrator');
        
        if ($role && !$role->has_cap('manage_data_visualizer')) {
            $role->add_cap('manage_data_visualizer');
            $role->add_cap('import_datasets');
            $role->add_cap('export_datasets');
            $role->add_cap('delete_datasets');
        }
    }
    
    /**
     * Check plugin requirements.
     *
     * @since 1.0.0
     * @return bool True if requirements are met.
     */
    private function check_requirements(): bool
    {
        global $wp_version;
        
        // Check WordPress version
        if (version_compare($wp_version, '5.0', '<')) {
            return false;
        }
        
        // Check PHP version
        if (version_compare(PHP_VERSION, '8.0', '<')) {
            return false;
        }
        
        // Check for required PHP extensions
        $required_extensions = ['json', 'mbstring'];
        foreach ($required_extensions as $extension) {
            if (!extension_loaded($extension)) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Display requirements notice.
     *
     * @since 1.0.0
     */
    private function display_requirements_notice(): void
    {
        $message = sprintf(
            __('Crock Data Visualizer requires WordPress 5.0+ and PHP 8.0+. Current versions: WordPress %s, PHP %s', 'crock-data-visualizer'),
            get_bloginfo('version'),
            PHP_VERSION
        );
        
        printf(
            '<div class="notice notice-error"><p><strong>%s:</strong> %s</p></div>',
            esc_html__('Plugin Requirements Not Met', 'crock-data-visualizer'),
            esc_html($message)
        );
    }
    
    /**
     * Display activation notice.
     *
     * @since 1.0.0
     */
    private function display_activation_notice(): void
    {
        $admin_url = admin_url('admin.php?page=' . $this->plugin_name);
        
        printf(
            '<div class="notice notice-success is-dismissible"><p><strong>%s</strong> %s <a href="%s">%s</a></p></div>',
            esc_html__('Crock Data Visualizer', 'crock-data-visualizer'),
            esc_html__('has been activated successfully!', 'crock-data-visualizer'),
            esc_url($admin_url),
            esc_html__('Get Started', 'crock-data-visualizer')
        );
    }
    
    /**
     * Register REST API routes.
     *
     * @since 1.0.0
     */
    public function register_rest_routes(): void
    {
        // Register namespace
        $namespace = 'crock-data-visualizer/v1';
        
        // Dataset endpoints
        register_rest_route($namespace, '/datasets', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'rest_get_datasets'],
                'permission_callback' => [$this, 'rest_permission_check']
            ],
            [
                'methods' => 'POST',
                'callback' => [$this, 'rest_create_dataset'],
                'permission_callback' => [$this, 'rest_permission_check']
            ]
        ]);
        
        register_rest_route($namespace, '/datasets/(?P<id>\d+)', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'rest_get_dataset'],
                'permission_callback' => [$this, 'rest_permission_check']
            ],
            [
                'methods' => 'DELETE',
                'callback' => [$this, 'rest_delete_dataset'],
                'permission_callback' => [$this, 'rest_permission_check']
            ]
        ]);
        
        // Chart data endpoint (public)
        register_rest_route($namespace, '/chart-data/(?P<id>\d+)', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_get_chart_data'],
            'permission_callback' => '__return_true' // Public endpoint
        ]);
    }
    
    /**
     * REST API permission check.
     *
     * @since 1.0.0
     * @return bool True if user has permission.
     */
    public function rest_permission_check(): bool
    {
        return current_user_can('manage_data_visualizer');
    }
    
    /**
     * REST endpoint: Get datasets.
     *
     * @since 1.0.0
     * @param WP_REST_Request $request REST request object.
     * @return WP_REST_Response|WP_Error Response object or error.
     */
    public function rest_get_datasets(WP_REST_Request $request): WP_Error|WP_REST_Response {
        if (!$this->database) {
            return new WP_Error('no_database', 'Database manager not available', ['status' => 500]);
        }
        
        try {
            $page = $request->get_param('page') ?? 1;
            $per_page = $request->get_param('per_page') ?? 20;
            $search = $request->get_param('search') ?? '';
            
            $datasets = $this->database->get_datasets($page, $per_page, $search);
            
            return new WP_REST_Response($datasets, 200);
            
        } catch (Exception $e) {
            return new WP_Error('fetch_error', $e->getMessage(), ['status' => 500]);
        }
    }
    
    /**
     * REST endpoint: Get single dataset.
     *
     * @since 1.0.0
     * @param WP_REST_Request $request REST request object.
     * @return WP_REST_Response|WP_Error Response object or error.
     */
    public function rest_get_dataset(WP_REST_Request $request): WP_Error|WP_REST_Response {
        if (!$this->database) {
            return new WP_Error('no_database', 'Database manager not available', ['status' => 500]);
        }
        
        $id = $request->get_param('id');
        
        try {
            $dataset = $this->database->get_dataset($id);
            
            if (!$dataset) {
                return new WP_Error('not_found', 'Dataset not found', ['status' => 404]);
            }
            
            return new WP_REST_Response($dataset, 200);
            
        } catch (Exception $e) {
            return new WP_Error('fetch_error', $e->getMessage(), ['status' => 500]);
        }
    }
    
    /**
     * REST endpoint: Create dataset.
     *
     * @since 1.0.0
     * @param WP_REST_Request $request REST request object.
     * @return WP_REST_Response|WP_Error Response object or error.
     */
    public function rest_create_dataset(WP_REST_Request $request): WP_Error|WP_REST_Response {
        if (!$this->database) {
            return new WP_Error('no_database', 'Database manager not available', ['status' => 500]);
        }
        
        $params = $request->get_json_params();
        
        try {
            $dataset_id = $this->database->create_dataset($params);
            
            return new WP_REST_Response(['id' => $dataset_id], 201);
            
        } catch (Exception $e) {
            return new WP_Error('create_error', $e->getMessage(), ['status' => 500]);
        }
    }
    
    /**
     * REST endpoint: Delete dataset.
     *
     * @since 1.0.0
     * @param WP_REST_Request $request REST request object.
     * @return WP_REST_Response|WP_Error Response object or error.
     */
    public function rest_delete_dataset(WP_REST_Request $request): WP_Error|WP_REST_Response {
        if (!$this->database) {
            return new WP_Error('no_database', 'Database manager not available', ['status' => 500]);
        }
        
        $id = $request->get_param('id');
        
        try {
            $result = $this->database->delete_dataset($id);
            
            if (!$result) {
                return new WP_Error('delete_failed', 'Failed to delete dataset', ['status' => 500]);
            }
            
            return new WP_REST_Response(['success' => true], 200);
            
        } catch (Exception $e) {
            return new WP_Error('delete_error', $e->getMessage(), ['status' => 500]);
        }
    }
    
    /**
     * REST endpoint: Get chart data (public).
     *
     * @since 1.0.0
     * @param WP_REST_Request $request REST request object.
     * @return WP_REST_Response|WP_Error Response object or error.
     */
    public function rest_get_chart_data(WP_REST_Request $request): WP_Error|WP_REST_Response {
        if (!$this->database) {
            return new WP_Error('no_database', 'Database manager not available', ['status' => 500]);
        }
        
        $id = $request->get_param('id');
        
        try {
            $chart_data = $this->database->get_chart_data($id);
            
            if (!$chart_data) {
                return new WP_Error('not_found', 'Chart data not found', ['status' => 404]);
            }
            
            return new WP_REST_Response($chart_data, 200);
            
        } catch (Exception $e) {
            return new WP_Error('fetch_error', $e->getMessage(), ['status' => 500]);
        }
    }
    
    /**
     * Cleanup old data.
     *
     * @since 1.0.0
     */
    public function cleanup_old_data(): void
    {
        if (!$this->database) {
            return;
        }
        
        try {
            // Cleanup old temporary files
            $this->cleanup_temp_files();
            
            // Cleanup old cache entries
            $this->cleanup_cache();
            
            // Cleanup old log entries
            $this->database->cleanup_old_logs();
            
        } catch (Exception $e) {
            error_log('CDV Cleanup Error: ' . $e->getMessage());
        }
    }
    
    /**
     * Cleanup temporary files.
     *
     * @since 1.0.0
     */
    private function cleanup_temp_files(): void
    {
        $upload_dir = wp_upload_dir();
        $temp_dir = $upload_dir['basedir'] . '/crock-data-visualizer/temp/';
        
        if (!is_dir($temp_dir)) {
            return;
        }
        
        $files = glob($temp_dir . '*');
        $max_age = 24 * 60 * 60; // 24 hours
        
        foreach ($files as $file) {
            if (is_file($file) && time() - filemtime($file) > $max_age) {
                unlink($file);
            }
        }
    }
    
    /**
     * Cleanup cache.
     *
     * @since 1.0.0
     */
    private function cleanup_cache(): void
    {
        // Clean up WordPress transients
        global $wpdb;
        
        $wpdb->query(
            "DELETE FROM {$wpdb->options}
             WHERE option_name LIKE '_transient_cdv_%'
             AND option_value < UNIX_TIMESTAMP()"
        );
        
        $wpdb->query(
            "DELETE FROM {$wpdb->options}
             WHERE option_name LIKE '_transient_timeout_cdv_%'"
        );
    }
    
    /**
     * Run the loader to execute all hooks with WordPress.
     *
     * @since 1.0.0
     */
    final public function run(): void
    {
        if ($this->loader) {
            $this->loader->run();
        }
    }
    
    /**
     * Get the plugin name identifier.
     *
     * @since  1.0.0
     * @return string The plugin name.
     */
    final public function get_plugin_name(): string
    {
        return $this->plugin_name;
    }
    
    /**
     * Get the loader instance.
     *
     * @since  1.0.0
     * @return Crock_Data_Visualizer_Loader|null The loader instance.
     */
    final public function get_loader(): ?Crock_Data_Visualizer_Loader
    {
        return $this->loader;
    }
    
    /**
     * Get the plugin version.
     *
     * @since  1.0.0
     * @return string The plugin version.
     */
    final public function get_version(): string
    {
        return $this->version;
    }
    
    /**
     * Get the admin instance.
     *
     * @since  1.0.0
     * @return Crock_Data_Visualizer_Admin|null The admin instance.
     */
    final public function get_admin(): ?Crock_Data_Visualizer_Admin
    {
        return $this->admin;
    }
    
    /**
     * Get the public instance.
     *
     * @since  1.0.0
     * @return Crock_Data_Visualizer_Public|null The public instance.
     */
    final public function get_public(): ?Crock_Data_Visualizer_Public
    {
        return $this->public;
    }
    
    /**
     * Get the database manager instance.
     *
     * @since  1.0.0
     * @return Crock_Data_Visualizer_Database|null The database manager instance.
     */
    final public function get_database(): ?Crock_Data_Visualizer_Database
    {
        return $this->database;
    }
    
    /**
     * Get the importer instance.
     *
     * @since  1.0.0
     * @return Crock_Data_Visualizer_Importer|null The importer instance.
     */
    final public function get_importer(): ?Crock_Data_Visualizer_Importer
    {
        return $this->importer;
    }
    
    /**
     * Get the shortcode manager instance.
     *
     * @since  1.0.0
     * @return Crock_Data_Visualizer_Shortcode|null The shortcode manager instance.
     */
    final public function get_shortcode(): ?Crock_Data_Visualizer_Shortcode
    {
        return $this->shortcode;
    }
    
    /**
     * Static method to get plugin instance.
     *
     * @since 1.0.0
     * @return Crock_Data_Visualizer|null Plugin instance.
     */
    public static function get_instance(): ?self
    {
        static $instance = null;
        
        if ($instance === null) {
            $instance = new self();
        }
        
        return $instance;
    }
}