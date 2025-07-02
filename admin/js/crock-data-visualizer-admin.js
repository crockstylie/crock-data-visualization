/**
 * Admin JavaScript for Crock Data Visualizer (Vanilla JS)
 *
 * @package    Crock_Data_Visualizer
 * @subpackage Crock_Data_Visualizer/admin/js
 * @since      1.0.0
 */

(function() {
    'use strict';

    // =====================================================================
    // AJAX HANDLER CLASS
    // =====================================================================

    /**
     * AJAX communication handler
     */
    const CdvAjax = {

        /**
         * Default configuration
         */
        defaults: {
            timeout: 30000,
            retries: 3,
            retryDelay: 1000
        },

        /**
         * Make AJAX request
         */
        async request(action, data = {}, options = {}) {
            const config = { ...this.defaults, ...options };

            // Prepare form data
            const formData = new FormData();
            formData.append('action', `cdv_${action}`);
            formData.append('nonce', cdv_ajax.nonce);

            // Add data to form
            Object.keys(data).forEach(key => {
                if (data[key] instanceof File) {
                    formData.append(key, data[key]);
                } else if (typeof data[key] === 'object') {
                    formData.append(key, JSON.stringify(data[key]));
                } else {
                    formData.append(key, data[key]);
                }
            });

            let attempt = 0;

            while (attempt < config.retries) {
                try {
                    const response = await this.makeRequest(formData, config.timeout);

                    if (response.success) {
                        return response.data;
                    } else {
                        throw new Error(response.data?.message || 'Request failed');
                    }

                } catch (error) {
                    attempt++;

                    if (attempt >= config.retries) {
                        throw error;
                    }

                    // Wait before retry
                    await this.delay(config.retryDelay * attempt);
                    console.warn(`🔄 Retry attempt ${attempt} for action: ${action}`);
                }
            }
        },

        /**
         * Make the actual fetch request
         */
        async makeRequest(formData, timeout) {
            const controller = new AbortController();
            const timeoutId = setTimeout(() => controller.abort(), timeout);

            try {
                const response = await fetch(cdv_ajax.ajax_url, {
                    method: 'POST',
                    body: formData,
                    signal: controller.signal,
                    credentials: 'same-origin',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                clearTimeout(timeoutId);

                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }

                const result = await response.json();
                return result;

            } catch (error) {
                clearTimeout(timeoutId);

                if (error.name === 'AbortError') {
                    throw new Error('Request timeout');
                }

                throw error;
            }
        },

        /**
         * Delay utility
         */
        delay(ms) {
            return new Promise(resolve => setTimeout(resolve, ms));
        },

        /**
         * Upload file with progress
         */
        async uploadWithProgress(action, file, data = {}, onProgress = null) {
            return new Promise((resolve, reject) => {
                const xhr = new XMLHttpRequest();
                const formData = new FormData();

                // Prepare form data
                formData.append('action', `cdv_${action}`);
                formData.append('nonce', cdv_ajax.nonce);
                formData.append('file', file);

                Object.keys(data).forEach(key => {
                    if (typeof data[key] === 'object') {
                        formData.append(key, JSON.stringify(data[key]));
                    } else {
                        formData.append(key, data[key]);
                    }
                });

                // Progress handler
                if (onProgress) {
                    xhr.upload.addEventListener('progress', (e) => {
                        if (e.lengthComputable) {
                            const percent = (e.loaded / e.total) * 100;
                            onProgress(percent);
                        }
                    });
                }

                // Response handler
                xhr.addEventListener('load', () => {
                    try {
                        const response = JSON.parse(xhr.responseText);

                        if (response.success) {
                            resolve(response.data);
                        } else {
                            reject(new Error(response.data?.message || 'Upload failed'));
                        }
                    } catch (error) {
                        reject(new Error('Invalid response format'));
                    }
                });

                // Error handler
                xhr.addEventListener('error', () => {
                    reject(new Error('Network error'));
                });

                // Timeout handler
                xhr.addEventListener('timeout', () => {
                    reject(new Error('Upload timeout'));
                });

                // Configure and send
                xhr.timeout = 60000; // 1 minute for uploads
                xhr.open('POST', cdv_ajax.ajax_url);
                xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                xhr.send(formData);
            });
        },

        // =====================================================================
        // SPECIFIC API METHODS
        // =====================================================================

        /**
         * Analyze uploaded file
         */
        async analyzeFile(file, config = {}) {
            console.log('📊 Analyzing file via AJAX...');

            return await this.uploadWithProgress('analyze_file', file, { config }, (progress) => {
                console.log(`📊 Analysis progress: ${progress.toFixed(1)}%`);
            });
        },

        /**
         * Import file data
         */
        async importFile(config = {}) {
            console.log('📥 Importing file via AJAX...');

            return await this.request('import_file', { config });
        },

        /**
         * Get datasets list
         */
        async getDatasets(page = 1, perPage = 20, search = '') {
            console.log('📋 Loading datasets via AJAX...');

            return await this.request('get_datasets', {
                page,
                per_page: perPage,
                search
            });
        },

        /**
         * Delete dataset
         */
        async deleteDataset(datasetId) {
            console.log(`🗑️ Deleting dataset ${datasetId} via AJAX...`);

            return await this.request('delete_dataset', {
                dataset_id: datasetId
            });
        },

        /**
         * Perform bulk action
         */
        async bulkAction(actionType, datasetIds) {
            console.log(`🔧 Performing bulk action ${actionType} via AJAX...`);

            return await this.request('bulk_action', {
                action_type: actionType,
                dataset_ids: datasetIds
            });
        },

        /**
         * Get dataset preview
         */
        async getDatasetPreview(datasetId, limit = 10) {
            console.log(`👀 Loading dataset preview ${datasetId} via AJAX...`);

            return await this.request('get_dataset_preview', {
                dataset_id: datasetId,
                limit
            });
        }
    };

    // =====================================================================
    // MAIN ADMIN OBJECT
    // =====================================================================

    /**
     * Main admin object
     */
    const CrockDataVisualizer = {

        // Configuration
        config: {
            maxFileSize: 50 * 1024 * 1024, // 50MB
            allowedTypes: ['csv', 'json', 'xml'],
            currentStep: 1,
            totalSteps: 4,
            uploadedFile: null,
            previewData: null,
            progressInterval: null
        },

        /**
         * Initialize the admin interface
         */
        init() {
            this.bindEvents();
            this.initComponents();
            console.log('🚀 Crock Data Visualizer Admin initialized (Vanilla JS)');
        },

        /**
         * Bind all event handlers
         */
        bindEvents() {
            // Dashboard events
            this.bindDashboardEvents();

            // Import page events
            this.bindImportEvents();

            // Datasets page events
            this.bindDatasetsEvents();

            // Global events
            this.bindGlobalEvents();
        },

        /**
         * Initialize components
         */
        initComponents() {
            // Initialize tooltips
            this.initTooltips();

            // Initialize modals
            this.initModals();

            // Initialize search
            this.initSearch();

            // Auto-save form data
            this.initAutoSave();
        },

        // =====================================================================
        // DASHBOARD EVENTS
        // =====================================================================

        /**
         * Bind dashboard specific events
         */
        bindDashboardEvents() {
            // Quick actions
            this.addEventListener('#cdv-create-visualization', 'click', this.handleCreateVisualization);
            this.addEventListener('#cdv-export-data', 'click', this.handleExportData);

            // Statistics refresh
            this.addEventListenerAll('.cdv-stat-card', 'click', this.handleStatCardClick);
        },

        /**
         * Handle create visualization click
         */
        handleCreateVisualization(e) {
            e.preventDefault();

            CrockDataVisualizer.showNotification(
                'info',
                'Nouvelle fonctionnalité',
                'La création de visualisations sera bientôt disponible !'
            );
        },

        /**
         * Handle export data click
         */
        handleExportData(e) {
            e.preventDefault();

            CrockDataVisualizer.showNotification(
                'info',
                'Export de données',
                'L\'export en masse sera bientôt disponible.'
            );
        },

        /**
         * Handle stat card click for details
         */
        handleStatCardClick(e) {
            const card = e.currentTarget;
            card.classList.add('cdv-fade-in');

            setTimeout(() => {
                card.classList.remove('cdv-fade-in');
            }, 300);
        },

        // =====================================================================
        // IMPORT PAGE EVENTS
        // =====================================================================

        /**
         * Bind import page events
         */
        bindImportEvents() {
            // File upload events
            this.bindFileUploadEvents();

            // Step navigation
            this.addEventListener('#cdv-next-step', 'click', this.handleNextStep);
            this.addEventListener('#cdv-prev-step', 'click', this.handlePrevStep);
            this.addEventListener('#cdv-start-import', 'click', this.handleStartImport);

            // Form events
            this.addEventListener('#cdv-delimiter', 'change', this.handleDelimiterChange);
            this.addEventListener('#cdv-encoding', 'change', this.handleEncodingChange);
            this.addEventListener('#cdv-has-header', 'change', this.handleHeaderChange);
        },

        /**
         * Bind file upload events
         */
        bindFileUploadEvents() {
            const dropzone = document.getElementById('cdv-upload-dropzone');
            const fileInput = document.getElementById('cdv-file-input');
            const selectButton = document.getElementById('cdv-select-file');

            if (!dropzone || !fileInput) return;

            // Click to select file
            [selectButton, dropzone].forEach(element => {
                if (element) {
                    element.addEventListener('click', (e) => {
                        e.preventDefault();
                        fileInput.click();
                    });
                }
            });

            // File input change
            fileInput.addEventListener('change', (e) => {
                if (e.target.files[0]) {
                    CrockDataVisualizer.handleFileSelect(e.target.files[0]);
                }
            });

            // Drag and drop events
            dropzone.addEventListener('dragover', (e) => {
                e.preventDefault();
                e.stopPropagation();
                dropzone.classList.add('dragover');
            });

            dropzone.addEventListener('dragenter', (e) => {
                e.preventDefault();
                e.stopPropagation();
                dropzone.classList.add('dragover');
            });

            dropzone.addEventListener('dragleave', (e) => {
                e.preventDefault();
                e.stopPropagation();
                dropzone.classList.remove('dragover');
            });

            dropzone.addEventListener('drop', (e) => {
                e.preventDefault();
                e.stopPropagation();
                dropzone.classList.remove('dragover');

                const files = e.dataTransfer.files;
                if (files.length > 0) {
                    CrockDataVisualizer.handleFileSelect(files[0]);
                }
            });

            // Remove file
            this.addEventListener('#cdv-remove-file', 'click', this.handleRemoveFile);
        },

        /**
         * Handle file selection
         */
        handleFileSelect(file) {
            console.log('📁 File selected:', file.name);

            // Validate file
            if (!this.validateFile(file)) {
                return;
            }

            // Store file
            this.config.uploadedFile = file;

            // Show file info
            this.showFileInfo(file);

            // Enable next step
            this.enableNextStep();

            // Auto-fill dataset name
            this.autoFillDatasetName(file.name);
        },

        /**
         * Validate selected file
         */
        validateFile(file) {
            // Check file size
            if (file.size > this.config.maxFileSize) {
                this.showNotification(
                    'error',
                    'Fichier trop volumineux',
                    `La taille du fichier doit être inférieure à ${this.formatFileSize(this.config.maxFileSize)}`
                );
                return false;
            }

            // Check file type
            const extension = file.name.split('.').pop().toLowerCase();
            if (!this.config.allowedTypes.includes(extension)) {
                this.showNotification(
                    'error',
                    'Type de fichier invalide',
                    `Seuls les fichiers ${this.config.allowedTypes.join(', ').toUpperCase()} sont autorisés`
                );
                return false;
            }

            return true;
        },

        /**
         * Show file information
         */
        showFileInfo(file) {
            const fileInfo = document.getElementById('cdv-file-info');
            const uploadArea = document.getElementById('cdv-upload-dropzone');

            if (!fileInfo || !uploadArea) return;

            const fileName = document.getElementById('cdv-file-name');
            const fileSize = document.getElementById('cdv-file-size');
            const fileType = document.getElementById('cdv-file-type');

            if (fileName) fileName.textContent = file.name;
            if (fileSize) fileSize.textContent = this.formatFileSize(file.size);
            if (fileType) fileType.textContent = file.type || 'Inconnu';

            uploadArea.style.display = 'none';
            fileInfo.style.display = 'block';
            fileInfo.classList.add('cdv-fade-in');
        },

        /**
         * Handle file removal
         */
        handleRemoveFile(e) {
            e.preventDefault();

            const fileInfo = document.getElementById('cdv-file-info');
            const uploadArea = document.getElementById('cdv-upload-dropzone');
            const fileInput = document.getElementById('cdv-file-input');

            // Clear file data
            CrockDataVisualizer.config.uploadedFile = null;
            if (fileInput) fileInput.value = '';

            // Reset UI
            if (fileInfo) {
                fileInfo.style.display = 'none';
                fileInfo.classList.remove('cdv-fade-in');
            }
            if (uploadArea) uploadArea.style.display = 'block';

            // Disable next step
            CrockDataVisualizer.disableNextStep();

            console.log('🗑️ File removed');
        },

        /**
         * Handle step navigation
         */
        handleNextStep(e) {
            e.preventDefault();

            if (CrockDataVisualizer.config.currentStep < CrockDataVisualizer.config.totalSteps) {
                CrockDataVisualizer.goToStep(CrockDataVisualizer.config.currentStep + 1);
            }
        },

        handlePrevStep(e) {
            e.preventDefault();

            if (CrockDataVisualizer.config.currentStep > 1) {
                CrockDataVisualizer.goToStep(CrockDataVisualizer.config.currentStep - 1);
            }
        },

        /**
         * Navigate to specific step
         */
        goToStep(step) {
            console.log(`📍 Going to step ${step}`);

            // Validate step transition
            if (!this.canGoToStep(step)) {
                return;
            }

            // Update step indicator
            this.updateStepIndicator(step);

            // Show/hide step content
            this.showStepContent(step);

            // Update navigation buttons
            this.updateNavigationButtons(step);

            // Handle step-specific logic
            this.handleStepLogic(step);

            // Update current step
            this.config.currentStep = step;
        },

        /**
         * Check if can go to step
         */
        canGoToStep(step) {
            switch(step) {
                case 2:
                    return this.config.uploadedFile !== null;
                case 3:
                    return this.validateConfigForm();
                case 4:
                    return this.config.previewData !== null;
                default:
                    return true;
            }
        },

        /**
         * Update step indicator
         */
        updateStepIndicator(currentStep) {
            const steps = document.querySelectorAll('.cdv-step');

            steps.forEach((step, index) => {
                const stepNumber = index + 1;

                step.classList.remove('active', 'completed');

                if (stepNumber < currentStep) {
                    step.classList.add('completed');
                } else if (stepNumber === currentStep) {
                    step.classList.add('active');
                }
            });
        },

        /**
         * Show step content
         */
        showStepContent(step) {
            // Hide all steps
            document.querySelectorAll('.cdv-import-step').forEach(stepEl => {
                stepEl.style.display = 'none';
            });

            // Show current step
            const currentStepEl = document.getElementById(`cdv-step-${step}`);
            if (currentStepEl) {
                currentStepEl.style.display = 'block';
                currentStepEl.classList.add('cdv-fade-in');

                setTimeout(() => {
                    currentStepEl.classList.remove('cdv-fade-in');
                }, 300);
            }
        },

        /**
         * Update navigation buttons
         */
        updateNavigationButtons(step) {
            const prevBtn = document.getElementById('cdv-prev-step');
            const nextBtn = document.getElementById('cdv-next-step');
            const importBtn = document.getElementById('cdv-start-import');

            // Previous button
            if (prevBtn) {
                prevBtn.style.display = step === 1 ? 'none' : 'inline-block';
            }

            // Next/Import buttons
            if (step === this.config.totalSteps) {
                if (nextBtn) nextBtn.style.display = 'none';
                if (importBtn) importBtn.style.display = 'inline-block';
            } else {
                if (nextBtn) nextBtn.style.display = 'inline-block';
                if (importBtn) importBtn.style.display = 'none';
            }
        },

        /**
         * Handle step-specific logic
         */
        handleStepLogic(step) {
            switch(step) {
                case 2:
                    this.initializeConfigStep();
                    break;
                case 3:
                    this.generatePreview();
                    break;
                case 4:
                    this.prepareImport();
                    break;
            }
        },

        /**
         * Initialize configuration step
         */
        initializeConfigStep() {
            const file = this.config.uploadedFile;
            const csvOptions = document.getElementById('cdv-csv-options');

            if (!file || !csvOptions) return;

            const extension = file.name.split('.').pop().toLowerCase();

            if (extension === 'csv') {
                csvOptions.style.display = 'block';
            } else {
                csvOptions.style.display = 'none';
            }
        },

        /**
         * Generate data preview (maintenant avec AJAX)
         */
        async generatePreview() {
            console.log('👀 Generating preview with AJAX...');

            const loading = document.getElementById('cdv-preview-loading');
            const content = document.getElementById('cdv-preview-content');

            if (loading) loading.style.display = 'block';
            if (content) content.style.display = 'none';

            try {
                // Get form configuration
                const config = this.getImportConfiguration();

                // Analyze file via AJAX
                const analysis = await CdvAjax.analyzeFile(this.config.uploadedFile, config);

                // Store analysis results
                this.config.previewData = analysis;

                // Update UI with real data
                this.updatePreviewUI(analysis);

                if (loading) loading.style.display = 'none';
                if (content) {
                    content.style.display = 'block';
                    content.classList.add('cdv-fade-in');
                }

                this.showNotification('success', 'Analyse terminée', 'Le fichier a été analysé avec succès !');

            } catch (error) {
                console.error('❌ Preview generation failed:', error);

                if (loading) loading.style.display = 'none';

                this.showNotification(
                    'error',
                    'Erreur d\'analyse',
                    error.message || 'Impossible d\'analyser le fichier'
                );
            }
        },

        /**
         * Get import configuration from form
         */
        getImportConfiguration() {
            const config = {};

            // Dataset name
            const datasetName = document.getElementById('cdv-dataset-name');
            if (datasetName) config.dataset_name = datasetName.value;

            // CSV specific options
            const delimiter = document.getElementById('cdv-delimiter');
            if (delimiter) config.delimiter = delimiter.value;

            const encoding = document.getElementById('cdv-encoding');
            if (encoding) config.encoding = encoding.value;

            const hasHeader = document.getElementById('cdv-has-header');
            if (hasHeader) config.has_header = hasHeader.checked;

            return config;
        },

        /**
         * Update preview UI with real analysis data
         */
        updatePreviewUI(analysis) {
            // Update stats
            const previewRows = document.getElementById('cdv-preview-rows');
            const previewColumns = document.getElementById('cdv-preview-columns');

            if (previewRows) previewRows.textContent = analysis.rows.toLocaleString();
            if (previewColumns) previewColumns.textContent = analysis.columns;

            // Generate preview table with real data
            if (analysis.preview) {
                this.generatePreviewTable(analysis.preview);
            }
        },

        /**
         * Generate preview table
         */
        generatePreviewTable(data) {
            const table = document.getElementById('cdv-preview-table');
            if (!table || !data.length) return;

            let html = '';

            // Generate header
            if (data.length > 0) {
                html += '<thead><tr>';
                data[0].forEach(header => {
                    html += `<th>${this.escapeHtml(header)}</th>`;
                });
                html += '</tr></thead>';
            }

            // Generate body
            html += '<tbody>';
            for (let i = 1; i < Math.min(data.length, 11); i++) { // Show max 10 rows
                html += '<tr>';
                data[i].forEach(cell => {
                    html += `<td>${this.escapeHtml(cell)}</td>`;
                });
                html += '</tr>';
            }
            html += '</tbody>';

            table.innerHTML = html;
        },

        /**
         * Handle import start (maintenant avec AJAX)
         */
        async handleStartImport(e) {
            e.preventDefault();
            console.log('🚀 Starting import with AJAX...');

            const progressContainer = document.getElementById('cdv-import-progress');
            if (progressContainer) progressContainer.style.display = 'block';

            try {
                // Get configuration
                const config = this.getImportConfiguration();

                // Start progress animation
                this.startProgressAnimation();

                // Perform import via AJAX
                const result = await CdvAjax.importFile(config);

                // Show success
                this.showImportSuccess(result);

            } catch (error) {
                console.error('❌ Import failed:', error);

                if (progressContainer) progressContainer.style.display = 'none';

                this.showNotification(
                    'error',
                    'Erreur d\'import',
                    error.message || 'L\'import a échoué'
                );
            }
        },

        /**
         * Start progress animation
         */
        startProgressAnimation() {
            let progress = 0;
            const progressFill = document.getElementById('cdv-progress-fill');
            const progressText = document.getElementById('cdv-progress-text');

            const steps = [
                'Validation du fichier...',
                'Lecture des données...',
                'Traitement des lignes...',
                'Sauvegarde en base...',
                'Création des index...',
                'Finalisation de l\'import...'
            ];

            this.config.progressInterval = setInterval(() => {
                progress += Math.random() * 15;

                if (progress >= 95) {
                    progress = 95; // Don't complete until AJAX returns
                }

                if (progressFill) progressFill.style.width = progress + '%';

                const stepIndex = Math.floor((progress / 100) * steps.length);
                if (steps[stepIndex] && progressText) {
                    progressText.textContent = steps[stepIndex];
                }

            }, 200);
        },

        /**
         * Show import success with real data
         */
        showImportSuccess(result) {
            // Clear progress animation
            if (this.config.progressInterval) {
                clearInterval(this.config.progressInterval);
            }

            // Complete progress bar
            const progressFill = document.getElementById('cdv-progress-fill');
            if (progressFill) progressFill.style.width = '100%';

            setTimeout(() => {
                const progressContainer = document.getElementById('cdv-import-progress');
                const successContainer = document.getElementById('cdv-import-success');

                if (progressContainer) progressContainer.style.display = 'none';
                if (successContainer) {
                    // Update success message with real data
                    const successMessage = successContainer.querySelector('.success-message');
                    if (successMessage) {
                        successMessage.textContent = `${result.rows_imported} lignes importées avec succès !`;
                    }

                    successContainer.style.display = 'block';
                    successContainer.classList.add('cdv-fade-in');
                }

                this.showNotification('success', 'Import réussi', result.message);

            }, 500);
        },

        /**
         * Prepare import
         */
        prepareImport() {
            console.log('📋 Preparing import...');
            // This step just reviews the configuration
        },

        // =====================================================================
        // DATASETS PAGE EVENTS
        // =====================================================================

        /**
         * Bind datasets page events
         */
        bindDatasetsEvents() {
            // Table interactions
            this.addEventListener('#cb-select-all', 'change', this.handleSelectAll);
            this.addEventListenerAll('input[name="dataset[]"]', 'change', this.handleDatasetSelect);
            this.addEventListener('#cdv-apply-bulk', 'click', this.handleBulkAction);

            // Dataset actions
            this.addEventListenerAll('.cdv-view-dataset', 'click', this.handleViewDataset);
            this.addEventListenerAll('.cdv-create-visualization', 'click', this.handleCreateVisualizationFromDataset);
            this.addEventListenerAll('.cdv-export-dataset', 'click', this.handleExportDataset);
            this.addEventListenerAll('.cdv-delete-dataset', 'click', this.handleDeleteDataset);

            // Search
            this.addEventListener('#cdv-search-datasets', 'input', this.handleDatasetSearch);
            this.addEventListener('#cdv-search-button', 'click', this.handleDatasetSearchSubmit);
        },

        /**
         * Handle select all checkbox
         */
        handleSelectAll(e) {
            const isChecked = e.target.checked;
            const checkboxes = document.querySelectorAll('input[name="dataset[]"]');

            checkboxes.forEach(checkbox => {
                checkbox.checked = isChecked;
            });

            CrockDataVisualizer.updateBulkActionButton();
        },

        /**
         * Handle individual dataset selection
         */
        handleDatasetSelect() {
            CrockDataVisualizer.updateBulkActionButton();
        },

        /**
         * Update bulk action button state
         */
        updateBulkActionButton() {
            const selected = document.querySelectorAll('input[name="dataset[]"]:checked');
            const bulkButton = document.getElementById('cdv-apply-bulk');

            if (bulkButton) {
                bulkButton.disabled = selected.length === 0;
            }
        },

        /**
         * Handle bulk actions
         */
        handleBulkAction(e) {
            e.preventDefault();

            const actionSelect = document.getElementById('cdv-bulk-action');
            const action = actionSelect ? actionSelect.value : '';
            const selected = Array.from(document.querySelectorAll('input[name="dataset[]"]:checked'))
                .map(checkbox => checkbox.value);

            if (!action || selected.length === 0) {
                CrockDataVisualizer.showNotification(
                    'error',
                    'Aucune action sélectionnée',
                    'Veuillez sélectionner une action et au moins un dataset.'
                );
                return;
            }

            console.log(`🔧 Bulk action: ${action} on ${selected.length} datasets`);

            // Confirm action
            if (confirm(`Êtes-vous sûr de vouloir ${action} ${selected.length} dataset(s) ?`)) {
                CrockDataVisualizer.performBulkAction(action, selected);
            }
        },

        /**
         * Perform bulk action (maintenant avec AJAX)
         */
        async performBulkAction(action, datasetIds) {
            try {
                this.showNotification('info', 'Traitement en cours', 'Exécution de l\'action en masse...');

                const result = await CdvAjax.bulkAction(action, datasetIds);

                this.showNotification('success', 'Action terminée', result.message);

                // Refresh the datasets list
                this.refreshDatasetsList();

            } catch (error) {
                console.error('❌ Bulk action failed:', error);

                this.showNotification(
                    'error',
                    'Erreur d\'action en masse',
                    error.message || 'L\'action a échoué'
                );
            }
        },

        /**
         * Handle view dataset (maintenant avec AJAX)
         */
        async handleViewDataset(e) {
            e.preventDefault();

            const datasetId = e.currentTarget.dataset.datasetId;
            console.log(`👀 Viewing dataset: ${datasetId}`);

            const modal = document.getElementById('cdv-dataset-modal');
            const loading = document.getElementById('cdv-modal-loading');
            const content = document.getElementById('cdv-modal-content');

            if (!modal) return;

            if (loading) loading.style.display = 'block';
            if (content) content.style.display = 'none';
            modal.style.display = 'flex';

            try {
                // Load dataset preview via AJAX
                const preview = await CdvAjax.getDatasetPreview(datasetId);

                if (loading) loading.style.display = 'none';
                if (content) {
                    content.innerHTML = this.generateDatasetPreviewHTML(preview);
                    content.style.display = 'block';
                }

            } catch (error) {
                console.error('❌ Failed to load dataset preview:', error);

                if (loading) loading.style.display = 'none';
                if (content) {
                    content.innerHTML = `<div class="error">Erreur: ${error.message}</div>`;
                    content.style.display = 'block';
                }
            }
        },

        /**
         * Generate dataset preview HTML from real data
         */
        generateDatasetPreviewHTML(preview) {
            let tableHTML = '<table class="cdv-preview-table"><thead><tr>';

            // Headers
            preview.headers.forEach(header => {
                tableHTML += `<th>${this.escapeHtml(header)}</th>`;
            });
            tableHTML += '</tr></thead><tbody>';

            // Rows
            preview.rows.forEach(row => {
                tableHTML += '<tr>';
                row.forEach(cell => {
                    tableHTML += `<td>${this.escapeHtml(cell)}</td>`;
                });
                tableHTML += '</tr>';
            });
            tableHTML += '</tbody></table>';

            return `
                <div class="cdv-dataset-preview">
                    <h3>Aperçu du Dataset</h3>
                    <div class="cdv-preview-stats">
                        <span class="cdv-stat"><strong>${preview.total_rows.toLocaleString()}</strong> lignes</span>
                        <span class="cdv-stat"><strong>${preview.total_columns}</strong> colonnes</span>
                    </div>
                    ${tableHTML}
                </div>
            `;
        },

        /**
         * Handle dataset search
         */
        handleDatasetSearch(e) {
            const query = e.target.value.toLowerCase();
            const rows = document.querySelectorAll('.cdv-datasets-table tbody tr');

            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                if (text.includes(query)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        },

        /**
         * Handle delete dataset (maintenant avec AJAX)
         */
        async handleDeleteDataset(e) {
            e.preventDefault();

            const datasetId = e.currentTarget.dataset.datasetId;

            if (!confirm('Êtes-vous sûr de vouloir supprimer ce dataset ?')) {
                return;
            }

            try {
                await CdvAjax.deleteDataset(datasetId);

                this.showNotification('success', 'Suppression réussie', 'Le dataset a été supprimé.');

                // Remove row from table
                const row = e.currentTarget.closest('tr');
                if (row) {
                    row.remove();
                }

            } catch (error) {
                console.error('❌ Delete failed:', error);

                this.showNotification(
                    'error',
                    'Erreur de suppression',
                    error.message || 'Impossible de supprimer le dataset'
                );
            }
        },

        /**
         * Refresh datasets list
         */
        async refreshDatasetsList() {
            const tableBody = document.querySelector('.cdv-datasets-table tbody');
            if (!tableBody) return;

            try {
                const result = await CdvAjax.getDatasets();

                // TODO: Update table with new data
                console.log('📋 Datasets refreshed:', result);

            } catch (error) {
                console.error('❌ Failed to refresh datasets:', error);
            }
        },

        // =====================================================================
        // GLOBAL EVENTS
        // =====================================================================

        /**
         * Bind global events
         */
        bindGlobalEvents() {
            // Modal close
            document.addEventListener('click', (e) => {
                if (e.target.classList.contains('cdv-modal-close') ||
                    e.target.classList.contains('cdv-modal')) {
                    const modals = document.querySelectorAll('.cdv-modal');
                    modals.forEach(modal => modal.style.display = 'none');
                }
            });

            // Keyboard shortcuts
            document.addEventListener('keydown', this.handleKeyboardShortcuts);

            // Auto-save forms
            this.addEventListenerAll('.cdv-auto-save', 'input', this.handleAutoSave);
        },

        /**
         * Handle keyboard shortcuts
         */
        handleKeyboardShortcuts(e) {
            // Escape key to close modals
            if (e.keyCode === 27) {
                const modals = document.querySelectorAll('.cdv-modal');
                modals.forEach(modal => modal.style.display = 'none');
            }

            // Ctrl+S to save (prevent browser save)
            if (e.ctrlKey && e.keyCode === 83) {
                e.preventDefault();
                CrockDataVisualizer.showNotification(
                    'info',
                    'Sauvegarde automatique',
                    'Votre progression est automatiquement sauvegardée.'
                );
            }
        },

        // =====================================================================
        // UTILITY FUNCTIONS
        // =====================================================================

        /**
         * Add event listener with null check
         */
        addEventListener(selector, event, handler) {
            const element = document.querySelector(selector);
            if (element) {
                element.addEventListener(event, handler.bind(this));
            }
        },

        /**
         * Add event listeners to multiple elements
         */
        addEventListenerAll(selector, event, handler) {
            const elements = document.querySelectorAll(selector);
            elements.forEach(element => {
                element.addEventListener(event, handler.bind(this));
            });
        },

        /**
         * Initialize tooltips
         */
        initTooltips() {
            const elements = document.querySelectorAll('[title]');
            elements.forEach(element => {
                element.setAttribute('data-title', element.getAttribute('title'));
                element.removeAttribute('title');
            });
        },

        /**
         * Initialize modals
         */
        initModals() {
            // Modal backdrop click to close
            const modals = document.querySelectorAll('.cdv-modal');
            modals.forEach(modal => {
                modal.addEventListener('click', (e) => {
                    if (e.target === modal) {
                        modal.style.display = 'none';
                    }
                });
            });
        },

        /**
         * Initialize search functionality
         */
        initSearch() {
            // Add search debouncing
            let searchTimeout;
            const searchInput = document.getElementById('cdv-search-datasets');

            if (searchInput) {
                searchInput.addEventListener('input', function() {
                    clearTimeout(searchTimeout);
                    searchTimeout = setTimeout(() => {
                        CrockDataVisualizer.handleDatasetSearch.call(CrockDataVisualizer, { target: this });
                    }, 300);
                });
            }
        },

        /**
         * Initialize auto-save
         */
        initAutoSave() {
            // Auto-save form data to localStorage
            setInterval(() => {
                const formData = {};
                const autoSaveElements = document.querySelectorAll('.cdv-auto-save');

                autoSaveElements.forEach(element => {
                    if (element.id) {
                        formData[element.id] = element.value;
                    }
                });

                if (Object.keys(formData).length > 0) {
                    localStorage.setItem('cdv_form_data', JSON.stringify(formData));
                }
            }, 30000); // Save every 30 seconds
        },

        /**
         * Auto-fill dataset name from filename
         */
        autoFillDatasetName(filename) {
            const name = filename.replace(/\.[^/.]+$/, '').replace(/[_-]/g, ' ');
            const cleanName = name.charAt(0).toUpperCase() + name.slice(1);
            const datasetNameInput = document.getElementById('cdv-dataset-name');

            if (datasetNameInput) {
                datasetNameInput.value = cleanName;
            }
        },

        /**
         * Enable next step button
         */
        enableNextStep() {
            const nextBtn = document.getElementById('cdv-next-step');
            if (nextBtn) nextBtn.disabled = false;
        },

        /**
         * Disable next step button
         */
        disableNextStep() {
            const nextBtn = document.getElementById('cdv-next-step');
            if (nextBtn) nextBtn.disabled = true;
        },

        /**
         * Validate configuration form
         */
        validateConfigForm() {
            const nameInput = document.getElementById('cdv-dataset-name');
            return nameInput && nameInput.value.trim().length > 0;
        },

        /**
         * Format file size
         */
        formatFileSize(bytes) {
            if (bytes === 0) return '0 Octets';

            const k = 1024;
            const sizes = ['Octets', 'Ko', 'Mo', 'Go'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));

            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        },

        /**
         * Escape HTML
         */
        escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        },

        /**
         * Show notification
         */
        showNotification(type, title, message) {
            // Create notification element
            const notification = document.createElement('div');
            notification.className = `cdv-notification cdv-${type}`;
            notification.innerHTML = `
                <div class="cdv-notification-content">
                    <strong>${title}</strong>
                    <p>${message}</p>
                </div>
                <button class="cdv-notification-close">&times;</button>
            `;

            // Add to page
            let notificationsContainer = document.querySelector('.cdv-notifications');
            if (!notificationsContainer) {
                notificationsContainer = document.createElement('div');
                notificationsContainer.className = 'cdv-notifications';
                document.body.appendChild(notificationsContainer);
            }

            notificationsContainer.appendChild(notification);

            // Show with animation
            setTimeout(() => {
                notification.classList.add('show');
            }, 100);

            // Auto-remove after 5 seconds
            setTimeout(() => {
                this.removeNotification(notification);
            }, 5000);

            // Click to remove
            const closeBtn = notification.querySelector('.cdv-notification-close');
            if (closeBtn) {
                closeBtn.addEventListener('click', () => {
                    this.removeNotification(notification);
                });
            }
        },

        /**
         * Remove notification
         */
        removeNotification(notification) {
            notification.classList.remove('show');
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 300);
        },

        /**
         * Handle form configuration changes
         */
        handleDelimiterChange(e) {
            console.log('📝 Delimiter changed:', e.target.value);
        },

        handleEncodingChange(e) {
            console.log('📝 Encoding changed:', e.target.value);
        },

        handleHeaderChange(e) {
            console.log('📝 Header setting changed:', e.target.checked);
        },

        handleAutoSave(e) {
            // Visual feedback for auto-save
            e.target.classList.add('cdv-auto-saved');
            setTimeout(() => {
                e.target.classList.remove('cdv-auto-saved');
            }, 1000);
        },

        // Placeholder handlers for datasets
        handleCreateVisualizationFromDataset(e) {
            e.preventDefault();
            this.showNotification('info', 'Créer une visualisation', 'Fonctionnalité bientôt disponible !');
        },

        handleExportDataset(e) {
            e.preventDefault();
            this.showNotification('info', 'Export dataset', 'Fonctionnalité bientôt disponible !');
        },

        handleDatasetSearchSubmit(e) {
            e.preventDefault();
            // Search is handled in real-time, no need for submit action
        }
    };

    // =========================================================================
    // INITIALIZE ON DOCUMENT READY
    // =========================================================================

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            CrockDataVisualizer.init();
        });
    } else {
        CrockDataVisualizer.init();
    }

    // =========================================================================
    // EXPOSE TO GLOBAL SCOPE
    // =========================================================================

    window.CrockDataVisualizer = CrockDataVisualizer;

})();