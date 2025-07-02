<?php

/**
 * Database operations handler - manages data storage and retrieval
 *
 * Handles all database operations including dataset management,
 * data import/export, and query optimization.
 *
 * @package    Crock_Data_Visualizer
 * @subpackage Crock_Data_Visualizer/includes
 * @since      1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class Crock_Data_Visualizer_Database
{
    /**
     * Get all datasets.
     *
     * @since 1.0.0
     * @return array List of datasets.
     */
    public function get_datasets(): array
    {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'cdv_datasets';
        
        return $wpdb->get_results(
            "SELECT * FROM $table_name WHERE status = 'active' ORDER BY import_date DESC"
        );
    }
    
    /**
     * Get a specific dataset by ID.
     *
     * @since 1.0.0
     * @param int $dataset_id Dataset ID.
     * @return object|null Dataset object or null if not found.
     */
    public function get_dataset(int $dataset_id): ?object
    {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'cdv_datasets';
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE id = %d AND status = 'active'",
            $dataset_id
        ));
    }
    
    /**
     * Create a new dataset.
     *
     * @since 1.0.0
     * @param array $data Dataset data.
     * @return int|false Dataset ID on success, false on failure.
     */
    public function create_dataset(array $data)
    {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'cdv_datasets';
        
        $result = $wpdb->insert($table_name, $data);
        
        return $result ? $wpdb->insert_id : false;
    }
    
    /**
     * Update an existing dataset.
     *
     * @since 1.0.0
     * @param int   $dataset_id Dataset ID.
     * @param array $data       Updated data.
     * @return bool Success status.
     */
    public function update_dataset(int $dataset_id, array $data): bool
    {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'cdv_datasets';
        
        return $wpdb->update(
            $table_name,
            $data,
            ['id' => $dataset_id]
        ) !== false;
    }
    
    /**
     * Delete a dataset.
     *
     * @since 1.0.0
     * @param int $dataset_id Dataset ID.
     * @return bool Success status.
     */
    public function delete_dataset(int $dataset_id): bool
    {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'cdv_datasets';
        
        return $wpdb->update(
            $table_name,
            ['status' => 'deleted'],
            ['id' => $dataset_id]
        ) !== false;
    }
}
