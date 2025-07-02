<?php
/**
 * Main admin dashboard page
 *
 * @package    Crock_Data_Visualizer
 * @subpackage Crock_Data_Visualizer/admin/partials
 * @since      1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Get statistics
global $wpdb;
$stats = [
    'total_datasets' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}cdv_datasets WHERE status = 'active'"),
    'total_visualizations' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}cdv_visualizations WHERE status = 'active'"),
    'total_rows' => $wpdb->get_var("SELECT SUM(total_rows) FROM {$wpdb->prefix}cdv_datasets WHERE status = 'active'"),
    'recent_imports' => $wpdb->get_results("SELECT name, import_date FROM {$wpdb->prefix}cdv_datasets WHERE status = 'active' ORDER BY import_date DESC LIMIT 5")
];
?>

<div class="wrap cdv-admin-wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <!-- Welcome Section -->
    <div class="cdv-welcome-panel">
        <div class="cdv-welcome-panel-content">
            <h2><?php _e('Welcome to Data Visualizer', 'crock-data-visualizer'); ?></h2>
            <p class="about-description">
                <?php _e('Import, manage and visualize your data easily. Create beautiful charts and tables from CSV, JSON and XML files.', 'crock-data-visualizer'); ?>
            </p>
            <div class="cdv-welcome-panel-actions">
                <a href="<?php echo admin_url('admin.php?page=crock-data-visualizer-import'); ?>" class="button button-primary button-hero">
                    <span class="dashicons dashicons-upload" style="vertical-align: middle; margin-right: 5px;"></span>
                    <?php _e('Import Your First Dataset', 'crock-data-visualizer'); ?>
                </a>
                <a href="<?php echo admin_url('admin.php?page=crock-data-visualizer-datasets'); ?>" class="button button-secondary button-hero">
                    <span class="dashicons dashicons-database" style="vertical-align: middle; margin-right: 5px;"></span>
                    <?php _e('Manage Datasets', 'crock-data-visualizer'); ?>
                </a>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="cdv-stats-grid">
        <div class="cdv-stat-card">
            <div class="cdv-stat-icon">
                <span class="dashicons dashicons-database"></span>
            </div>
            <div class="cdv-stat-content">
                <h3><?php echo number_format($stats['total_datasets'] ?? 0); ?></h3>
                <p><?php _e('Active Datasets', 'crock-data-visualizer'); ?></p>
            </div>
        </div>

        <div class="cdv-stat-card">
            <div class="cdv-stat-icon">
                <span class="dashicons dashicons-chart-area"></span>
            </div>
            <div class="cdv-stat-content">
                <h3><?php echo number_format($stats['total_visualizations'] ?? 0); ?></h3>
                <p><?php _e('Visualizations', 'crock-data-visualizer'); ?></p>
            </div>
        </div>

        <div class="cdv-stat-card">
            <div class="cdv-stat-icon">
                <span class="dashicons dashicons-editor-table"></span>
            </div>
            <div class="cdv-stat-content">
                <h3><?php echo number_format($stats['total_rows'] ?? 0); ?></h3>
                <p><?php _e('Total Data Rows', 'crock-data-visualizer'); ?></p>
            </div>
        </div>

        <div class="cdv-stat-card">
            <div class="cdv-stat-icon">
                <span class="dashicons dashicons-calendar-alt"></span>
            </div>
            <div class="cdv-stat-content">
                <h3><?php echo date('j'); ?></h3>
                <p><?php _e('Day of Month', 'crock-data-visualizer'); ?></p>
            </div>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="cdv-dashboard-widgets">
        <div class="cdv-widget">
            <h3><?php _e('Recent Imports', 'crock-data-visualizer'); ?></h3>
            <div class="cdv-widget-content">
                <?php if (!empty($stats['recent_imports'])) : ?>
                    <table class="cdv-recent-table">
                        <?php foreach ($stats['recent_imports'] as $import) : ?>
                            <tr>
                                <td>
                                    <span class="dashicons dashicons-database"></span>
                                    <strong><?php echo esc_html($import->name); ?></strong>
                                </td>
                                <td class="cdv-date">
                                    <?php echo human_time_diff(strtotime($import->import_date), current_time('timestamp')); ?> ago
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                <?php else : ?>
                    <p class="cdv-no-data">
                        <span class="dashicons dashicons-info"></span>
                        <?php _e('No datasets imported yet. Start by importing your first dataset!', 'crock-data-visualizer'); ?>
                    </p>
                <?php endif; ?>
            </div>
        </div>

        <div class="cdv-widget">
            <h3><?php _e('Quick Actions', 'crock-data-visualizer'); ?></h3>
            <div class="cdv-widget-content">
                <div class="cdv-quick-actions">
                    <a href="<?php echo admin_url('admin.php?page=crock-data-visualizer-import'); ?>" class="cdv-quick-action">
                        <span class="dashicons dashicons-upload"></span>
                        <span><?php _e('Import Data', 'crock-data-visualizer'); ?></span>
                    </a>
                    <a href="<?php echo admin_url('admin.php?page=crock-data-visualizer-datasets'); ?>" class="cdv-quick-action">
                        <span class="dashicons dashicons-list-view"></span>
                        <span><?php _e('View All Datasets', 'crock-data-visualizer'); ?></span>
                    </a>
                    <a href="#" class="cdv-quick-action" id="cdv-create-visualization">
                        <span class="dashicons dashicons-chart-bar"></span>
                        <span><?php _e('Create Visualization', 'crock-data-visualizer'); ?></span>
                    </a>
                    <a href="#" class="cdv-quick-action" id="cdv-export-data">
                        <span class="dashicons dashicons-download"></span>
                        <span><?php _e('Export Data', 'crock-data-visualizer'); ?></span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Help Section -->
    <div class="cdv-help-section">
        <h3><?php _e('Need Help?', 'crock-data-visualizer'); ?></h3>
        <p><?php _e('Check out our documentation and examples to get started quickly.', 'crock-data-visualizer'); ?></p>
        <div class="cdv-help-links">
            <a href="#" class="button">📖 <?php _e('Documentation', 'crock-data-visualizer'); ?></a>
            <a href="#" class="button">💡 <?php _e('Examples', 'crock-data-visualizer'); ?></a>
            <a href="#" class="button">🎬 <?php _e('Video Tutorials', 'crock-data-visualizer'); ?></a>
        </div>
    </div>
</div>
