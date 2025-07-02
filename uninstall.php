<?php

/**
 * Plugin uninstallation handler - complete cleanup
 *
 * This file is executed when the plugin is uninstalled (deleted).
 * It removes all plugin data, tables, and options.
 *
 * @package    Crock_Data_Visualizer
 * @since      1.0.0
 */

// If uninstall not called from WordPress, then exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

/**
 * Remove all plugin data from the database.
 *
 * @since 1.0.0
 */
function crock_data_visualizer_uninstall_cleanup(): void
{
    global $wpdb;
    
    // Remove all plugin options
    $option_names = [
        'crock_data_visualizer_options',
        'crock_data_visualizer_version',
        'crock_data_visualizer_activated_date'
    ];
    
    foreach ($option_names as $option_name) {
        delete_option($option_name);
        delete_site_option($option_name); // For multisite
    }
    
    // Remove all transients
    $transients = [
        'cdv_dataset_cache',
        'cdv_visualization_cache',
        'cdv_file_analysis_cache',
        'cdv_table_stats_cache'
    ];
    
    foreach ($transients as $transient) {
        delete_transient($transient);
        delete_site_transient($transient);
    }
    
    // Get all dynamic data tables using proper method
    $database_name = $wpdb->get_var("SELECT DATABASE()");
    $table_prefix = $wpdb->get_blog_prefix(); // Plus sûr que $wpdb->prefix
    
    $data_tables = $wpdb->get_results($wpdb->prepare(
        "SELECT table_name
         FROM information_schema.tables
         WHERE table_schema = %s
         AND table_name LIKE %s",
        $database_name,
        $table_prefix . 'cdv_data_%'
    ));
    
    // Drop dynamic data tables
    if ($data_tables) {
        foreach ($data_tables as $table) {
            $table_name = esc_sql($table->table_name);
            $wpdb->query("DROP TABLE IF EXISTS `$table_name`");
        }
    }
    
    // Drop main plugin tables
    $tables_to_drop = [
        $table_prefix . 'cdv_visualizations',
        $table_prefix . 'cdv_datasets'
    ];
    
    foreach ($tables_to_drop as $table) {
        $table_name = esc_sql($table);
        $wpdb->query("DROP TABLE IF EXISTS `$table_name`");
    }
    
    // Remove upload directories and files
    $upload_dir = wp_upload_dir();
    $plugin_dir = $upload_dir['basedir'] . '/crock-data-visualizer/';
    
    if (is_dir($plugin_dir)) {
        crock_data_visualizer_remove_directory_recursive($plugin_dir);
    }
    
    // Clear any remaining cached data
    if (function_exists('wp_cache_flush')) {
        wp_cache_flush();
    }
}

/**
 * Recursively remove a directory and all its contents.
 *
 * @since 1.0.0
 * @param string $dir Directory path to remove.
 */
function crock_data_visualizer_remove_directory_recursive(string $dir): void
{
    if (!is_dir($dir)) {
        return;
    }
    
    $files = array_diff(scandir($dir), ['.', '..']);
    
    foreach ($files as $file) {
        $path = $dir . DIRECTORY_SEPARATOR . $file;
        
        if (is_dir($path)) {
            crock_data_visualizer_remove_directory_recursive($path);
        } else {
            unlink($path);
        }
    }
    
    rmdir($dir);
}

// Execute cleanup
crock_data_visualizer_uninstall_cleanup();
