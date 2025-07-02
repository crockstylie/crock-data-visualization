<?php

/**
 * Plugin Name: Universal Data Visualizer by Crock
 * Plugin URI: https://antoinehory.fr
 * Description: A WordPress plugin that helps to import data in the Back Office and to show it in the Front Office
 * Version: 1.0.0
 * Author: Antoine (Crock) HORY
 * Author URI: https://antoinehory.fr
 * License: GPL v3
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: crock-data-visualizer
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.6
 * Requires PHP: 8.2
 * Network: false
 *
 * @package Crock_Data_Visualizer
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Currently plugin version.
 */
const CROCK_DATA_VISUALIZER_VERSION = '1.0.0';
const CROCK_DATA_VISUALIZER_PLUGIN_NAME = 'crock-data-visualizer';
define('CROCK_DATA_VISUALIZER_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CROCK_DATA_VISUALIZER_PLUGIN_URL', plugin_dir_url(__FILE__));
define('CROCK_DATA_VISUALIZER_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * The code that runs during plugin activation.
 */
function activate_crock_data_visualizer(): void
{
    require_once CROCK_DATA_VISUALIZER_PLUGIN_DIR . 'includes/Crock_Data_Visualizer_Activator.php';
    Crock_Data_Visualizer_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 */
function deactivate_crock_data_visualizer(): void
{
    require_once CROCK_DATA_VISUALIZER_PLUGIN_DIR . 'includes/Crock_Data_Visualizer_Deactivator.php';
    Crock_Data_Visualizer_Deactivator::deactivate();
}

register_activation_hook(__FILE__, 'activate_crock_data_visualizer');
register_deactivation_hook(__FILE__, 'deactivate_crock_data_visualizer');

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require CROCK_DATA_VISUALIZER_PLUGIN_DIR . 'includes/Crock_Data_Visualizer.php';

/**
 * Begins execution of the plugin.
 */
function run_crock_data_visualizer(): void
{
    $plugin = new Crock_Data_Visualizer();
    $plugin->run();
}

run_crock_data_visualizer();
