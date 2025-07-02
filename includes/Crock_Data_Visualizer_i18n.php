<?php
/** @noinspection PhpMethodNamingConventionInspection */

/**
 * Internationalization functionality - handles plugin translations
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @package    Crock_Data_Visualizer
 * @subpackage Crock_Data_Visualizer/includes
 * @since      1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class Crock_Data_Visualizer_i18n
{
    /**
     * Load the plugin text domain for translation.
     *
     * @since 1.0.0
     */
    final public function load_plugin_textdomain(): void
    {
        load_plugin_textdomain(
            'crock-data-visualizer',
            false,
            dirname(plugin_basename(__FILE__), 2) . '/languages/'
        );
    }
}
