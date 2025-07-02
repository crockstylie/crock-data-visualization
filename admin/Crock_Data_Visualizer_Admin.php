<?php
/** @noinspection PhpMethodNamingConventionInspection */
/** @noinspection PhpPropertyNamingConventionInspection */
/** @noinspection ForgottenDebugOutputInspection */

/**
 * Admin area functionality - handles backend interface and operations
 *
 * Defines all hooks for the admin area, including enqueue scripts/styles,
 * menu creation, AJAX handlers, and file import operations.
 *
 * @package    Crock_Data_Visualizer
 * @subpackage Crock_Data_Visualizer/admin
 * @since      1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class Crock_Data_Visualizer_Admin
{
    /**
     * The ID of this plugin.
     *
     * @since  1.0.0
     * @var    string $plugin_name The ID of this plugin.
     */
    private string $plugin_name;
    
    /**
     * The version of this plugin.
     *
     * @since  1.0.0
     * @var    string $version The current version of this plugin.
     */
    private string $version;
    
    /**
     * Initialize the class and set its properties.
     *
     * @since 1.0.0
     * @param string $plugin_name The name of this plugin.
     * @param string $version     The version of this plugin.
     */
    public function __construct(string $plugin_name, string $version)
    {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }
    
    /**
     * Register the stylesheets for the admin area.
     *
     * @since 1.0.0
     */
    public function enqueue_styles(): void
    {
        wp_enqueue_style(
            $this->plugin_name,
            CROCK_DATA_VISUALIZER_PLUGIN_URL . 'admin/css/crock-data-visualizer-admin.css',
            [],
            $this->version,
            'all'
        );
    }
    
    /**
     * Register the JavaScript for the admin area.
     *
     * @since 1.0.0
     */
    public function enqueue_scripts(): void
    {
        wp_enqueue_script(
            $this->plugin_name,
            CROCK_DATA_VISUALIZER_PLUGIN_URL . 'admin/js/crock-data-visualizer-admin.js',
            ['jquery'],
            $this->version,
            false
        );
        
        // Localize script for AJAX
        wp_localize_script($this->plugin_name, 'cdv_ajax', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('cdv_nonce'),
            'strings'  => [
                'analyzing' => __('Analyzing file...', 'crock-data-visualizer'),
                'importing' => __('Importing data...', 'crock-data-visualizer'),
                'error'     => __('An error occurred', 'crock-data-visualizer'),
            ]
        ]);
    }
    
    /**
     * Add plugin admin menu.
     *
     * @since 1.0.0
     */
    public function add_plugin_admin_menu(): void
    {
        add_menu_page(
            __('Data Visualizer', 'crock-data-visualizer'),
            __('Data Visualizer', 'crock-data-visualizer'),
            'manage_options',
            $this->plugin_name,
            [$this, 'display_plugin_admin_page'],
            'dashicons-chart-area',
            30
        );
        
        add_submenu_page(
            $this->plugin_name,
            __('Import Data', 'crock-data-visualizer'),
            __('Import Data', 'crock-data-visualizer'),
            'manage_options',
            $this->plugin_name . '-import',
            [$this, 'display_import_page']
        );
        
        add_submenu_page(
            $this->plugin_name,
            __('Manage Datasets', 'crock-data-visualizer'),
            __('Manage Datasets', 'crock-data-visualizer'),
            'manage_options',
            $this->plugin_name . '-datasets',
            [$this, 'display_datasets_page']
        );
    }
    
    /**
     * Display the main admin page.
     *
     * @since 1.0.0
     */
    final public function display_plugin_admin_page(): void
    {
        include_once CROCK_DATA_VISUALIZER_PLUGIN_DIR . 'admin/partials/crock-data-visualizer-admin-display.php';
    }
    
    /**
     * Display the import page.
     *
     * @since 1.0.0
     */
    final public function display_import_page(): void
    {
        include_once CROCK_DATA_VISUALIZER_PLUGIN_DIR . 'admin/partials/crock-data-visualizer-admin-import.php';
    }
    
    /**
     * Display the datasets management page.
     *
     * @since 1.0.0
     */
    final public function display_datasets_page(): void
    {
        include_once CROCK_DATA_VISUALIZER_PLUGIN_DIR . 'admin/partials/crock-data-visualizer-admin-datasets.php';
    }
    
    /**
     * AJAX handler for file analysis.
     *
     * @since 1.0.0
     */
    public function ajax_analyze_file(): void
    {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'cdv_nonce')) {
            wp_die(__('Security check failed', 'crock-data-visualizer'));
        }
        
        // TODO: Implement file analysis logic
        wp_send_json_success([
            'message' => __('File analysis placeholder', 'crock-data-visualizer')
        ]);
    }
    
    /**
     * AJAX handler for file import.
     *
     * @since 1.0.0
     */
    public function ajax_import_file(): void
    {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'cdv_nonce')) {
            wp_die(__('Security check failed', 'crock-data-visualizer'));
        }
        
        // TODO: Implement file import logic
        wp_send_json_success([
            'message' => __('File import placeholder', 'crock-data-visualizer')
        ]);
    }
    
    /**
     * AJAX handler for getting datasets.
     *
     * @since 1.0.0
     */
    public function ajax_get_datasets(): void
    {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'cdv_nonce')) {
            wp_die(__('Security check failed', 'crock-data-visualizer'));
        }
        
        // TODO: Implement get datasets logic
        wp_send_json_success([
            'datasets' => []
        ]);
    }
}
