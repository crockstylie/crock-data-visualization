<?php

/**
 * Public-facing functionality - handles frontend display and interactions
 *
 * Defines all hooks for the public side, including enqueue scripts/styles
 * and shortcode rendering for data visualization.
 *
 * @package    Crock_Data_Visualizer
 * @subpackage Crock_Data_Visualizer/public
 * @since      1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class Crock_Data_Visualizer_Public
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
     * @param string $plugin_name The name of the plugin.
     * @param string $version     The version of this plugin.
     */
    public function __construct(string $plugin_name, string $version)
    {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }
    
    /**
     * Register the stylesheets for the public-facing side of the site.
     *
     * @since 1.0.0
     */
    public function enqueue_styles(): void
    {
        wp_enqueue_style(
            $this->plugin_name,
            CROCK_DATA_VISUALIZER_PLUGIN_URL . 'public/css/crock-data-visualizer-public.css',
            [],
            $this->version,
            'all'
        );
    }
    
    /**
     * Register the JavaScript for the public-facing side of the site.
     *
     * @since 1.0.0
     */
    public function enqueue_scripts(): void
    {
        wp_enqueue_script(
            $this->plugin_name,
            CROCK_DATA_VISUALIZER_PLUGIN_URL . 'public/js/crock-data-visualizer-public.js',
            ['jquery'],
            $this->version,
            false
        );
        
        // Localize script for frontend interactions
        wp_localize_script($this->plugin_name, 'cdv_public', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('cdv_public_nonce'),
            'strings'  => [
                'loading'   => __('Loading...', 'crock-data-visualizer'),
                'no_data'   => __('No data available', 'crock-data-visualizer'),
                'error'     => __('Error loading data', 'crock-data-visualizer'),
            ]
        ]);
    }
}
