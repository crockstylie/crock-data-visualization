<?php
/**
 * Datasets management page
 *
 * @package    Crock_Data_Visualizer
 * @subpackage Crock_Data_Visualizer/admin/partials
 * @since      1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Get datasets
global $wpdb;
$datasets = $wpdb->get_results( "
    SELECT d.*, 
           COUNT(v.id) as visualizations_count
    FROM {$wpdb->prefix}cdv_datasets d
    LEFT JOIN {$wpdb->prefix}cdv_visualizations v ON d.id = v.dataset_id AND v.status = 'active'
    WHERE d.status = 'active'
    GROUP BY d.id
    ORDER BY d.import_date DESC
" );
?>

<div class="wrap cdv-admin-wrap">
    <div class="cdv-page-header">
        <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
        <a href="<?php echo admin_url( 'admin.php?page=crock-data-visualizer-import' ); ?>"
           class="button button-primary">
            <span class="dashicons dashicons-plus-alt"></span>
            <?php _e( 'Import New Dataset', 'crock-data-visualizer' ); ?>
        </a>
    </div>

    <?php if ( empty( $datasets ) ): ?>
        <!-- Empty State -->
        <div class="cdv-empty-state">
            <div class="cdv-empty-content">
                <span class="dashicons dashicons-database cdv-empty-icon"></span>
                <h2><?php _e( 'No Datasets Yet', 'crock-data-visualizer' ); ?></h2>
                <p><?php _e( 'Import your first dataset to get started with data visualization.', 'crock-data-visualizer' ); ?></p>
                <a href="<?php echo admin_url( 'admin.php?page=crock-data-visualizer-import' ); ?>"
                   class="button button-primary button-hero">
                    <span class="dashicons dashicons-upload"></span>
                    <?php _e( 'Import Your First Dataset', 'crock-data-visualizer' ); ?>
                </a>
            </div>
        </div>
    <?php else: ?>
        <!-- Datasets Table -->
        <div class="cdv-datasets-container">
            <div class="cdv-table-actions">
                <div class="cdv-bulk-actions">
                    <select id="cdv-bulk-action">
                        <option value=""><?php _e( 'Bulk Actions', 'crock-data-visualizer' ); ?></option>
                        <option value="delete"><?php _e( 'Delete', 'crock-data-visualizer' ); ?></option>
                        <option value="export"><?php _e( 'Export', 'crock-data-visualizer' ); ?></option>
                    </select>
                    <button type="button" class="button"
                            id="cdv-apply-bulk"><?php _e( 'Apply', 'crock-data-visualizer' ); ?></button>
                </div>

                <div class="cdv-search-box">
                    <input type="search" id="cdv-search-datasets"
                           placeholder="<?php _e( 'Search datasets...', 'crock-data-visualizer' ); ?>">
                    <button type="button" class="button" id="cdv-search-button">
                        <span class="dashicons dashicons-search"></span>
                    </button>
                </div>
            </div>

            <table class="cdv-datasets-table">
                <thead>
                <tr>
                    <td class="check-column">
                        <input type="checkbox" id="cb-select-all">
                    </td>
                    <th><?php _e( 'Dataset', 'crock-data-visualizer' ); ?></th>
                    <th><?php _e( 'Type', 'crock-data-visualizer' ); ?></th>
                    <th><?php _e( 'Rows', 'crock-data-visualizer' ); ?></th>
                    <th><?php _e( 'Columns', 'crock-data-visualizer' ); ?></th>
                    <th><?php _e( 'Visualizations', 'crock-data-visualizer' ); ?></th>
                    <th><?php _e( 'Import Date', 'crock-data-visualizer' ); ?></th>
                    <th><?php _e( 'Actions', 'crock-data-visualizer' ); ?></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ( $datasets as $dataset ): ?>
                    <tr data-dataset-id="<?php echo $dataset->id; ?>">
                        <td class="check-column">
                            <input type="checkbox" name="dataset[]" value="<?php echo $dataset->id; ?>">
                        </td>
                        <td class="cdv-dataset-name">
                            <strong><?php echo esc_html( $dataset->name ); ?></strong>
                            <?php if ( $dataset->description ): ?>
                                <p class="cdv-dataset-description"><?php echo esc_html( $dataset->description ); ?></p>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="cdv-file-type-badge cdv-type-<?php echo strtolower( $dataset->file_type ); ?>">
                                <?php echo strtoupper( $dataset->file_type ); ?>
                            </span>
                        </td>
                        <td><?php echo number_format( $dataset->total_rows ); ?></td>
                        <td><?php echo number_format( $dataset->total_columns ); ?></td>
                        <td>
                            <?php if ( $dataset->visualizations_count > 0 ): ?>
                                <a href="#" class="cdv-visualizations-count"
                                   data-dataset-id="<?php echo $dataset->id; ?>">
                                    <?php echo $dataset->visualizations_count; ?>
                                </a>
                            <?php else: ?>
                                <span class="cdv-no-visualizations">0</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo date_i18n( get_option( 'date_format' ), strtotime( $dataset->import_date ) ); ?></td>
                        <td class="cdv-actions">
                            <div class="cdv-action-buttons">
                                <button
                                        type="button"
                                        class="button button-small cdv-view-dataset"
                                        data-dataset-id="<?php echo $dataset->id; ?>"
                                        title="<?php _e( 'View Data', 'crock-data-visualizer' ); ?>">
                                    <span class="dashicons dashicons-visibility"></span>
                                </button>
                                <button
                                        type="button"
                                        class="button button-small cdv-create-visualization"
                                        data-dataset-id="<?php echo $dataset->id; ?>"
                                        title="<?php _e( 'Create Visualization', 'crock-data-visualizer' ); ?>">
                                    <span class="dashicons dashicons-chart-bar"></span>
                                </button>
                                <button
                                        type="button"
                                        class="button button-small cdv-export-dataset"
                                        data-dataset-id="<?php echo $dataset->id; ?>"
                                        title="<?php _e( 'Export', 'crock-data-visualizer' ); ?>">
                                    <span class="dashicons dashicons-download"></span>
                                </button>
                                <button
                                        type="button"
                                        class="button button-small cdv-delete-dataset"
                                        data-dataset-id="<?php echo $dataset->id; ?>"
                                        title="<?php _e( 'Delete', 'crock-data-visualizer' ); ?>">
                                    <span class="dashicons dashicons-trash"></span>
                                </button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- Dataset Details Modal -->
<div id="cdv-dataset-modal" class="cdv-modal" style="display: none;">
    <div class="cdv-modal-content">
        <div class="cdv-modal-header">
            <h2 id="cdv-modal-title"><?php _e( 'Dataset Details', 'crock-data-visualizer' ); ?></h2>
            <button type="button" class="cdv-modal-close">&times;</button>
        </div>
        <div class="cdv-modal-body">
            <div id="cdv-modal-loading">
                <span class="spinner is-active"></span>
                <span><?php _e( 'Loading dataset...', 'crock-data-visualizer' ); ?></span>
            </div>
            <div id="cdv-modal-content"></div>
        </div>
    </div>
</div>