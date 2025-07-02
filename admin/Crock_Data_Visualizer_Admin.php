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
            [],
            $this->version,
            true
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
     * Register AJAX hooks
     *
     * @since 1.0.0
     */
    public function register_ajax_hooks(): void
    {
        // File analysis
        add_action('wp_ajax_cdv_analyze_file', [$this, 'ajax_analyze_file']);
        
        // File import
        add_action('wp_ajax_cdv_import_file', [$this, 'ajax_import_file']);
        
        // Get datasets
        add_action('wp_ajax_cdv_get_datasets', [$this, 'ajax_get_datasets']);
        
        // Delete dataset
        add_action('wp_ajax_cdv_delete_dataset', [$this, 'ajax_delete_dataset']);
        
        // Bulk actions
        add_action('wp_ajax_cdv_bulk_action', [$this, 'ajax_bulk_action']);
        
        // Get dataset preview
        add_action('wp_ajax_cdv_get_dataset_preview', [$this, 'ajax_get_dataset_preview']);
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
            wp_send_json_error([
                'message' => __('Security check failed', 'crock-data-visualizer')
            ]);
        }
        
        // Check file upload
        if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            wp_send_json_error([
                'message' => __('No file uploaded or upload error', 'crock-data-visualizer')
            ]);
        }
        
        $file = $_FILES['file'];
        $config = $_POST['config'] ?? [];
        
        try {
            // Validate file
            $validation = $this->validate_uploaded_file($file);
            if (!$validation['valid']) {
                wp_send_json_error(['message' => $validation['message']]);
            }
            
            // Analyze file content
            $analysis = $this->analyze_file_content($file, $config);
            
            wp_send_json_success([
                'analysis' => $analysis,
                'message' => __('File analyzed successfully', 'crock-data-visualizer')
            ]);
        } catch (Exception $e) {
            wp_send_json_error([
                'message' => sprintf(__('Analysis failed: %s', 'crock-data-visualizer'), $e->getMessage())
            ]);
        }
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
            wp_send_json_error([
                'message' => __('Security check failed', 'crock-data-visualizer')
            ]);
        }
        
        // Get import configuration
        $config = $_POST['config'] ?? [];
        $dataset_name = sanitize_text_field($config['dataset_name'] ?? '');
        
        if (empty($dataset_name)) {
            wp_send_json_error([
                'message' => __('Dataset name is required', 'crock-data-visualizer')
            ]);
        }
        
        try {
            // Process import
            $result = $this->process_file_import($config);
            
            wp_send_json_success([
                'dataset_id' => $result['dataset_id'],
                'rows_imported' => $result['rows_imported'],
                'message' => sprintf(
                    __('Successfully imported %d rows into dataset "%s"', 'crock-data-visualizer'),
                    $result['rows_imported'],
                    $dataset_name
                )
            ]);
        } catch (Exception $e) {
            wp_send_json_error([
                'message' => sprintf(__('Import failed: %s', 'crock-data-visualizer'), $e->getMessage())
            ]);
        }
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
            wp_send_json_error([
                'message' => __('Security check failed', 'crock-data-visualizer')
            ]);
        }
        
        $page = absint($_POST['page'] ?? 1);
        $per_page = absint($_POST['per_page'] ?? 20);
        $search = sanitize_text_field($_POST['search'] ?? '');
        
        try {
            $datasets = $this->get_datasets_list($page, $per_page, $search);
            
            wp_send_json_success([
                'datasets' => $datasets['items'],
                'total' => $datasets['total'],
                'pages' => $datasets['pages'],
                'current_page' => $page
            ]);
        } catch (Exception $e) {
            wp_send_json_error([
                'message' => sprintf(__('Failed to load datasets: %s', 'crock-data-visualizer'), $e->getMessage())
            ]);
        }
    }
    
    /**
     * AJAX handler for dataset deletion.
     *
     * @since 1.0.0
     */
    public function ajax_delete_dataset(): void
    {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'cdv_nonce')) {
            wp_send_json_error([
                'message' => __('Security check failed', 'crock-data-visualizer')
            ]);
        }
        
        $dataset_id = absint($_POST['dataset_id'] ?? 0);
        
        if (!$dataset_id) {
            wp_send_json_error([
                'message' => __('Invalid dataset ID', 'crock-data-visualizer')
            ]);
        }
        
        try {
            $result = $this->delete_dataset($dataset_id);
            
            if ($result) {
                wp_send_json_success([
                    'message' => __('Dataset deleted successfully', 'crock-data-visualizer')
                ]);
            } else {
                wp_send_json_error([
                    'message' => __('Failed to delete dataset', 'crock-data-visualizer')
                ]);
            }
        } catch (Exception $e) {
            wp_send_json_error([
                'message' => sprintf(__('Deletion failed: %s', 'crock-data-visualizer'), $e->getMessage())
            ]);
        }
    }
    
    /**
     * AJAX handler for bulk actions.
     *
     * @since 1.0.0
     */
    public function ajax_bulk_action(): void
    {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'cdv_nonce')) {
            wp_send_json_error([
                'message' => __('Security check failed', 'crock-data-visualizer')
            ]);
        }
        
        $action = sanitize_text_field($_POST['action_type'] ?? '');
        $dataset_ids = array_map('absint', $_POST['dataset_ids'] ?? []);
        
        if (empty($action) || empty($dataset_ids)) {
            wp_send_json_error([
                'message' => __('Invalid action or no datasets selected', 'crock-data-visualizer')
            ]);
        }
        
        try {
            $result = $this->perform_bulk_action($action, $dataset_ids);
            
            wp_send_json_success([
                'processed' => $result['processed'],
                'failed' => $result['failed'],
                'message' => sprintf(
                    __('Bulk action completed. %d processed, %d failed.', 'crock-data-visualizer'),
                    $result['processed'],
                    $result['failed']
                )
            ]);
        } catch (Exception $e) {
            wp_send_json_error([
                'message' => sprintf(__('Bulk action failed: %s', 'crock-data-visualizer'), $e->getMessage())
            ]);
        }
    }
    
    /**
     * AJAX handler for dataset preview.
     *
     * @since 1.0.0
     */
    public function ajax_get_dataset_preview(): void
    {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'cdv_nonce')) {
            wp_send_json_error([
                'message' => __('Security check failed', 'crock-data-visualizer')
            ]);
        }
        
        $dataset_id = absint($_POST['dataset_id'] ?? 0);
        $limit = absint($_POST['limit'] ?? 10);
        
        if (!$dataset_id) {
            wp_send_json_error([
                'message' => __('Invalid dataset ID', 'crock-data-visualizer')
            ]);
        }
        
        try {
            $preview = $this->get_dataset_preview($dataset_id, $limit);
            
            wp_send_json_success([
                'preview' => $preview,
                'message' => __('Dataset preview loaded', 'crock-data-visualizer')
            ]);
        } catch (Exception $e) {
            wp_send_json_error([
                'message' => sprintf(__('Failed to load preview: %s', 'crock-data-visualizer'), $e->getMessage())
            ]);
        }
    }
    
    // =====================================================================
    // HELPER METHODS (placeholders for now)
    // =====================================================================
    
    /**
     * Validate uploaded file
     */
    private function validate_uploaded_file(array $file): array
    {
        // TODO: Implement actual validation
        return [
            'valid' => true,
            'message' => ''
        ];
    }
    
    /**
     * Analyze file content
     */
    private function analyze_file_content(array $file, array $config): array
    {
        // TODO: Implement actual analysis
        return [
            'rows' => rand(100, 10000),
            'columns' => rand(3, 20),
            'preview' => [
                ['ID', 'Nom', 'Email', 'Date'],
                ['1', 'Jean Dupont', 'jean@exemple.com', '2024-01-15'],
                ['2', 'Marie Martin', 'marie@exemple.com', '2024-01-16'],
            ],
            'file_type' => 'csv',
            'encoding' => 'UTF-8',
            'delimiter' => ','
        ];
    }
    
    /**
     * Process file import
     */
    private function process_file_import(array $config): array
    {
        // TODO: Implement actual import
        return [
            'dataset_id' => rand(1, 1000),
            'rows_imported' => rand(100, 5000)
        ];
    }
    
    /**
     * Get datasets list
     */
    private function get_datasets_list(int $page, int $per_page, string $search): array
    {
        // TODO: Implement actual database query
        return [
            'items' => [],
            'total' => 0,
            'pages' => 1
        ];
    }
    
    /**
     * Delete dataset
     */
    private function delete_dataset(int $dataset_id): bool
    {
        // TODO: Implement actual deletion
        return true;
    }
    
    /**
     * Perform bulk action
     */
    private function perform_bulk_action(string $action, array $dataset_ids): array
    {
        // TODO: Implement actual bulk actions
        return [
            'processed' => count($dataset_ids),
            'failed' => 0
        ];
    }
    
    /**
     * Get dataset preview
     */
    private function get_dataset_preview(int $dataset_id, int $limit): array
    {
        // TODO: Implement actual preview
        return [
            'headers' => ['ID', 'Nom', 'Email', 'Date'],
            'rows' => [
                ['1', 'Jean Dupont', 'jean@exemple.com', '2024-01-15'],
                ['2', 'Marie Martin', 'marie@exemple.com', '2024-01-16'],
            ],
            'total_rows' => rand(100, 5000),
            'total_columns' => 4
        ];
    }
}