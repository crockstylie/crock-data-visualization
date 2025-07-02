<?php

/**
 * Plugin activation functionality
 *
 * Fired during plugin activation. This class defines all code necessary to run during
 * the plugin's activation, including database table creation and initial setup.
 *
 * @package    Crock_Data_Visualizer
 * @subpackage Crock_Data_Visualizer/includes
 * @since      1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class Crock_Data_Visualizer_Activator
{
    /**
     * Activate the plugin.
     *
     * Creates database tables and performs initial setup when plugin is activated.
     *
     * @since 1.0.0
     */
    public static function activate(): void
    {
        self::create_tables();
        self::set_default_options();
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Create plugin database tables.
     *
     * @since 1.0.0
     */
    private static function create_tables(): void
    {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Datasets table
        $table_datasets = $wpdb->prefix . 'cdv_datasets';
        $sql_datasets = "CREATE TABLE $table_datasets (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            description text,
            file_name varchar(255) NOT NULL,
            file_type varchar(50) NOT NULL,
            file_size bigint(20) DEFAULT 0,
            delimiter varchar(10) DEFAULT ',',
            encoding varchar(20) DEFAULT 'UTF-8',
            has_header tinyint(1) DEFAULT 1,
            total_rows int(11) DEFAULT 0,
            total_columns int(11) DEFAULT 0,
            import_date datetime DEFAULT CURRENT_TIMESTAMP,
            last_update datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            status varchar(20) DEFAULT 'active',
            PRIMARY KEY (id),
            KEY status (status),
            KEY import_date (import_date),
            KEY file_type (file_type)
        ) $charset_collate;";
        
        // Visualizations table
        $table_visualizations = $wpdb->prefix . 'cdv_visualizations';
        $sql_visualizations = "CREATE TABLE $table_visualizations (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            dataset_id mediumint(9) NOT NULL,
            name varchar(255) NOT NULL,
            type varchar(50) NOT NULL DEFAULT 'table',
            config longtext,
            shortcode varchar(100),
            display_order int(11) DEFAULT 0,
            created_date datetime DEFAULT CURRENT_TIMESTAMP,
            last_update datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            status varchar(20) DEFAULT 'active',
            PRIMARY KEY (id),
            KEY dataset_id (dataset_id),
            KEY shortcode (shortcode),
            KEY status (status),
            KEY display_order (display_order)
        ) $charset_collate;";
        
        // Data table (stores actual imported data)
        $table_data = $wpdb->prefix . 'cdv_data';
        $sql_data = "CREATE TABLE $table_data (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            dataset_id mediumint(9) NOT NULL,
            row_index int(11) NOT NULL,
            column_name varchar(255) NOT NULL,
            column_value longtext,
            created_date datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY dataset_id (dataset_id),
            KEY row_index (row_index),
            KEY column_name (column_name)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        // Execute table creation
        dbDelta($sql_datasets);
        dbDelta($sql_visualizations);
        dbDelta($sql_data);
        
        // Verify tables were created before adding foreign keys
        self::add_foreign_keys();
    }
    
    /**
     * Add foreign key constraints to tables.
     *
     * @since 1.0.0
     */
    private static function add_foreign_keys(): void
    {
        global $wpdb;
        
        $table_datasets = $wpdb->prefix . 'cdv_datasets';
        $table_visualizations = $wpdb->prefix . 'cdv_visualizations';
        $table_data = $wpdb->prefix . 'cdv_data';
        
        // Check if tables exist before adding foreign keys
        $tables_exist = $wpdb->get_results("SHOW TABLES LIKE '$table_datasets'");
        $visualizations_exist = $wpdb->get_results("SHOW TABLES LIKE '$table_visualizations'");
        $data_exist = $wpdb->get_results("SHOW TABLES LIKE '$table_data'");
        
        if (empty($tables_exist) || empty($visualizations_exist) || empty($data_exist)) {
            error_log('CDV Plugin: Some tables were not created, skipping foreign key creation');
            return;
        }
        
        // Check if foreign keys already exist before adding them
        $existing_fks = $wpdb->get_results($wpdb->prepare(
            "SELECT CONSTRAINT_NAME
            FROM information_schema.TABLE_CONSTRAINTS
            WHERE TABLE_SCHEMA = %s
            AND TABLE_NAME IN (%s, %s)
            AND CONSTRAINT_TYPE = 'FOREIGN KEY'",
            DB_NAME,
            $table_visualizations,
            $table_data
        ));
        
        $existing_fk_names = array_column($existing_fks, 'CONSTRAINT_NAME');
        
        // Add visualization foreign key
        if (!in_array('fk_cdv_visualizations_dataset', $existing_fk_names)) {
            $result = $wpdb->query($wpdb->prepare(
                "ALTER TABLE %i
                ADD CONSTRAINT fk_cdv_visualizations_dataset
                FOREIGN KEY (dataset_id) REFERENCES %i(id) ON DELETE CASCADE",
                $table_visualizations,
                $table_datasets
            ));
            
            if ($result === false) {
                error_log('CDV Plugin: Failed to add foreign key for visualizations table');
            }
        }
        
        // Add data foreign key
        if (!in_array('fk_cdv_data_dataset', $existing_fk_names)) {
            $result = $wpdb->query($wpdb->prepare(
                "ALTER TABLE %i
                ADD CONSTRAINT fk_cdv_data_dataset
                FOREIGN KEY (dataset_id) REFERENCES %i(id) ON DELETE CASCADE",
                $table_data,
                $table_datasets
            ));
            
            if ($result === false) {
                error_log('CDV Plugin: Failed to add foreign key for data table');
            }
        }
    }
    
    /**
     * Set default plugin options.
     *
     * @since 1.0.0
     */
    private static function set_default_options(): void
    {
        $default_options = [
            'version' => CROCK_DATA_VISUALIZER_VERSION,
            'max_file_size' => 50 * 1024 * 1024, // 50MB
            'allowed_file_types' => ['csv', 'json', 'xml'],
            'default_chart_type' => 'table',
            'pagination_limit' => 50,
            'cache_enabled' => true,
            'cache_duration' => 3600, // 1 hour
        ];
        
        add_option('crock_data_visualizer_options', $default_options);
    }
}
