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
        $this->define_admin_hooks();
        $this->define_public_hooks();
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
        
        // Core classes
        require_once $includes_path . 'Crock_Data_Visualizer_Loader.php';
        require_once $includes_path . 'Crock_Data_Visualizer_i18n.php';
        require_once $includes_path . 'Crock_Data_Visualizer_Database.php';
        require_once $includes_path . 'Crock_Data_Visualizer_Importer.php';
        require_once $includes_path . 'Crock_Data_Visualizer_Shortcode.php';
        
        // Admin and public classes
        require_once $admin_path . 'Crock_Data_Visualizer_Admin.php';
        require_once $public_path . 'Crock_Data_Visualizer_Public.php';
        
        $this->loader = new Crock_Data_Visualizer_Loader();
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
        $plugin_admin = new Crock_Data_Visualizer_Admin($this->get_plugin_name(), $this->get_version());
        
        // Enqueue admin assets
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');
        
        // Admin menu
        $this->loader->add_action('admin_menu', $plugin_admin, 'add_plugin_admin_menu');
        
        // AJAX hooks for file operations
        $this->loader->add_action('wp_ajax_cdv_analyze_file', $plugin_admin, 'ajax_analyze_file');
        $this->loader->add_action('wp_ajax_cdv_import_file', $plugin_admin, 'ajax_import_file');
        $this->loader->add_action('wp_ajax_cdv_get_datasets', $plugin_admin, 'ajax_get_datasets');
    }
    
    /**
     * Register all hooks related to the public-facing functionality.
     *
     * @since 1.0.0
     */
    private function define_public_hooks(): void
    {
        $plugin_public = new Crock_Data_Visualizer_Public($this->get_plugin_name(), $this->get_version());
        
        // Enqueue public assets
        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_styles');
        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_scripts');
        
        // Shortcode registration
        $plugin_shortcode = new Crock_Data_Visualizer_Shortcode();
        $this->loader->add_action('init', $plugin_shortcode, 'register_shortcodes');
    }
    
    /**
     * Run the loader to execute all hooks with WordPress.
     *
     * @since 1.0.0
     */
    final public function run(): void
    {
        $this->loader->run();
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
     * @return Crock_Data_Visualizer_Loader The loader instance.
     */
    final public function get_loader(): Crock_Data_Visualizer_Loader
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
}
