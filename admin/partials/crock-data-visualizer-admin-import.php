<?php
/**
 * Data import page
 *
 * @package    Crock_Data_Visualizer
 * @subpackage Crock_Data_Visualizer/admin/partials
 * @since      1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap cdv-admin-wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="cdv-import-container">
        <!-- Import Steps -->
        <div class="cdv-steps-indicator">
            <div class="cdv-step active" data-step="1">
                <span class="cdv-step-number">1</span>
                <span class="cdv-step-title"><?php _e('Upload File', 'crock-data-visualizer'); ?></span>
            </div>
            <div class="cdv-step" data-step="2">
                <span class="cdv-step-number">2</span>
                <span class="cdv-step-title"><?php _e('Configure', 'crock-data-visualizer'); ?></span>
            </div>
            <div class="cdv-step" data-step="3">
                <span class="cdv-step-number">3</span>
                <span class="cdv-step-title"><?php _e('Preview', 'crock-data-visualizer'); ?></span>
            </div>
            <div class="cdv-step" data-step="4">
                <span class="cdv-step-number">4</span>
                <span class="cdv-step-title"><?php _e('Import', 'crock-data-visualizer'); ?></span>
            </div>
        </div>
        
        <!-- Step 1: File Upload -->
        <div class="cdv-import-step" id="cdv-step-1">
            <div class="cdv-import-card">
                <h2><?php _e('Upload Your Data File', 'crock-data-visualizer'); ?></h2>
                <p class="cdv-description">
                    <?php _e('Select a CSV, JSON, or XML file to import. Maximum file size: 50MB', 'crock-data-visualizer'); ?>
                </p>
                
                <div class="cdv-upload-area" id="cdv-upload-dropzone">
                    <div class="cdv-upload-content">
                        <span class="dashicons dashicons-cloud-upload cdv-upload-icon"></span>
                        <h3><?php _e('Drag & Drop your file here', 'crock-data-visualizer'); ?></h3>
                        <p><?php _e('or', 'crock-data-visualizer'); ?></p>
                        <button type="button" class="button button-primary" id="cdv-select-file">
                            <?php _e('Choose File', 'crock-data-visualizer'); ?>
                        </button>
                        <input type="file" id="cdv-file-input" accept=".csv,.json,.xml" style="display: none;">
                    </div>
                </div>
                
                <div class="cdv-file-info" id="cdv-file-info" style="display: none;">
                    <div class="cdv-file-preview">
                        <span class="dashicons dashicons-media-default"></span>
                        <div class="cdv-file-details">
                            <strong id="cdv-file-name"></strong>
                            <span id="cdv-file-size"></span>
                            <span id="cdv-file-type"></span>
                        </div>
                        <button type="button" class="button-link cdv-remove-file" id="cdv-remove-file">
                            <span class="dashicons dashicons-no-alt"></span>
                        </button>
                    </div>
                </div>
                
                <div class="cdv-supported-formats">
                    <h4><?php _e('Supported Formats:', 'crock-data-visualizer'); ?></h4>
                    <div class="cdv-format-list">
                        <span class="cdv-format-badge">CSV</span>
                        <span class="cdv-format-badge">JSON</span>
                        <span class="cdv-format-badge">XML</span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Step 2: Configuration -->
        <div class="cdv-import-step" id="cdv-step-2" style="display: none;">
            <div class="cdv-import-card">
                <h2><?php _e('Configure Import Settings', 'crock-data-visualizer'); ?></h2>
                
                <form id="cdv-import-config" class="cdv-form">
                    <div class="cdv-form-row">
                        <div class="cdv-form-group">
                            <label for="cdv-dataset-name"><?php _e('Dataset Name', 'crock-data-visualizer'); ?></label>
                            <input type="text" id="cdv-dataset-name" name="dataset_name" required class="cdv-input">
                            <p class="cdv-help-text"><?php _e('Choose a descriptive name for your dataset', 'crock-data-visualizer'); ?></p>
                        </div>
                        
                        <div class="cdv-form-group">
                            <label for="cdv-dataset-description"><?php _e('Description (Optional)', 'crock-data-visualizer'); ?></label>
                            <textarea
                                id="cdv-dataset-description"
                                name="dataset_description"
                                class="cdv-textarea"></textarea>
                        </div>
                    </div>
                    
                    <div class="cdv-form-row" id="cdv-csv-options" style="display: none;">
                        <div class="cdv-form-group">
                            <label for="cdv-delimiter"><?php _e('Delimiter', 'crock-data-visualizer'); ?></label>
                            <select id="cdv-delimiter" name="delimiter" class="cdv-select">
                                <option value=","><?php _e('Comma (,)', 'crock-data-visualizer'); ?></option>
                                <option value=";"><?php _e('Semicolon (;)', 'crock-data-visualizer'); ?></option>
                                <option value="\t"><?php _e('Tab', 'crock-data-visualizer'); ?></option>
                                <option value="|"><?php _e('Pipe (|)', 'crock-data-visualizer'); ?></option>
                            </select>
                        </div>
                        
                        <div class="cdv-form-group">
                            <label for="cdv-encoding"><?php _e('Encoding', 'crock-data-visualizer'); ?></label>
                            <select id="cdv-encoding" name="encoding" class="cdv-select">
                                <option value="UTF-8">UTF-8</option>
                                <option value="ISO-8859-1">ISO-8859-1</option>
                                <option value="Windows-1252">Windows-1252</option>
                            </select>
                        </div>
                        
                        <div class="cdv-form-group">
                            <label class="cdv-checkbox-label">
                                <input type="checkbox" id="cdv-has-header" name="has_header" checked>
                                <?php _e('First row contains headers', 'crock-data-visualizer'); ?>
                            </label>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Step 3: Preview -->
        <div class="cdv-import-step" id="cdv-step-3" style="display: none;">
            <div class="cdv-import-card">
                <h2><?php _e('Data Preview', 'crock-data-visualizer'); ?></h2>
                <p class="cdv-description">
                    <?php _e('Review the first few rows of your data to make sure everything looks correct.', 'crock-data-visualizer'); ?>
                </p>
                
                <div id="cdv-preview-container">
                    <div class="cdv-loading" id="cdv-preview-loading">
                        <span class="spinner is-active"></span>
                        <span><?php _e('Analyzing file...', 'crock-data-visualizer'); ?></span>
                    </div>
                    
                    <div id="cdv-preview-content" style="display: none;">
                        <div class="cdv-preview-stats">
                            <span class="cdv-stat">
                                <strong id="cdv-preview-rows">0</strong> <?php _e('rows', 'crock-data-visualizer'); ?>
                            </span>
                            <span class="cdv-stat">
                                <strong id="cdv-preview-columns">0</strong> <?php _e('columns', 'crock-data-visualizer'); ?>
                            </span>
                        </div>
                        
                        <div class="cdv-preview-table-container">
                            <table class="cdv-preview-table" id="cdv-preview-table">
                                <!-- Dynamic content -->
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Step 4: Import -->
        <div class="cdv-import-step" id="cdv-step-4" style="display: none;">
            <div class="cdv-import-card">
                <h2><?php _e('Import Data', 'crock-data-visualizer'); ?></h2>
                
                <div class="cdv-import-progress" id="cdv-import-progress" style="display: none;">
                    <div class="cdv-progress-bar">
                        <div class="cdv-progress-fill" id="cdv-progress-fill"></div>
                    </div>
                    <p class="cdv-progress-text" id="cdv-progress-text">
                        <?php _e('Starting import...', 'crock-data-visualizer'); ?>
                    </p>
                </div>
                
                <div class="cdv-import-success" id="cdv-import-success" style="display: none;">
                    <span class="dashicons dashicons-yes-alt cdv-success-icon"></span>
                    <h3><?php _e('Import Complete!', 'crock-data-visualizer'); ?></h3>
                    <p><?php _e('Your data has been successfully imported.', 'crock-data-visualizer'); ?></p>
                    <div class="cdv-success-actions">
                        <a href="#" class="button button-primary" id="cdv-view-dataset">
                            <?php _e('View Dataset', 'crock-data-visualizer'); ?>
                        </a>
                        <a href="#" class="button" id="cdv-create-visualization">
                            <?php _e('Create Visualization', 'crock-data-visualizer'); ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Navigation Buttons -->
        <div class="cdv-import-navigation">
            <button type="button" class="button" id="cdv-prev-step" style="display: none;">
                <span class="dashicons dashicons-arrow-left-alt2"></span>
                <?php _e('Previous', 'crock-data-visualizer'); ?>
            </button>
            
            <button type="button" class="button button-primary" id="cdv-next-step">
                <?php _e('Next', 'crock-data-visualizer'); ?>
                <span class="dashicons dashicons-arrow-right-alt2"></span>
            </button>
            
            <button type="button" class="button button-primary" id="cdv-start-import" style="display: none;">
                <span class="dashicons dashicons-download"></span>
                <?php _e('Start Import', 'crock-data-visualizer'); ?>
            </button>
        </div>
    </div>
</div>
