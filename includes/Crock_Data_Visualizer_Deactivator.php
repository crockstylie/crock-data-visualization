<?php

/**
 * Plugin deactivation handler - cleanup and housekeeping
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 * It clears cache, temporary files, and scheduled tasks.
 *
 * @package    Crock_Data_Visualizer
 * @subpackage Crock_Data_Visualizer/includes
 * @since      1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class Crock_Data_Visualizer_Deactivator
{
    /**
     * Code to run on plugin deactivation.
     *
     * @since 1.0.0
     */
    public static function deactivate(): void
    {
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Clear any scheduled cron jobs
        self::clear_scheduled_tasks();
        
        // Clear cached data
        self::clear_cache();
        
        // Clean up temporary files
        self::cleanup_temp_files();
    }
    
    /**
     * Clear all scheduled tasks related to the plugin.
     *
     * @since 1.0.0
     */
    private static function clear_scheduled_tasks(): void
    {
        $scheduled_hooks = [
            'cdv_cleanup_temp_files',
            'cdv_optimize_database',
            'cdv_clear_cache'
        ];
        
        foreach ($scheduled_hooks as $hook) {
            wp_clear_scheduled_hook($hook);
        }
    }
    
    /**
     * Clear plugin cache and temporary data.
     *
     * @since 1.0.0
     */
    private static function clear_cache(): void
    {
        // Clear transients
        $transients_to_clear = [
            'cdv_dataset_cache',
            'cdv_visualization_cache',
            'cdv_file_analysis_cache',
            'cdv_table_stats_cache'
        ];
        
        foreach ($transients_to_clear as $transient) {
            delete_transient($transient);
        }
        
        // Clear any site transients
        foreach ($transients_to_clear as $transient) {
            delete_site_transient($transient);
        }
        
        // Clear the object cache if available
        if (function_exists('wp_cache_flush')) {
            wp_cache_flush();
        }
    }
    
    /**
     * Clean up temporary files created by the plugin.
     *
     * @since 1.0.0
     */
    private static function cleanup_temp_files(): void
    {
        $upload_dir = wp_upload_dir();
        $temp_dirs = [
            $upload_dir['basedir'] . '/crock-data-visualizer/temp/',
            $upload_dir['basedir'] . '/crock-data-visualizer/imports/'
        ];
        
        foreach ($temp_dirs as $temp_dir) {
            if (is_dir($temp_dir)) {
                $files = glob($temp_dir . '*');
                
                if ($files) {
                    foreach ($files as $file) {
                        if (is_file($file) && basename($file) !== 'index.php' && basename($file) !== '.htaccess') {
                            unlink($file);
                        }
                    }
                }
            }
        }
    }
}
