<?php

/**
 * Plugin hooks orchestrator - registers all actions and filters
 *
 * Maintains a list of all hooks that are registered throughout the plugin
 * and registers them with the WordPress API.
 *
 * @package    Crock_Data_Visualizer
 * @subpackage Crock_Data_Visualizer/includes
 * @since      1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class Crock_Data_Visualizer_Loader
{
    /**
     * The array of actions registered with WordPress.
     *
     * @since 1.0.0
     * @var   array $actions The actions registered with WordPress to fire when the plugin loads.
     */
    private array $actions = [];
    
    /**
     * The array of filters registered with WordPress.
     *
     * @since 1.0.0
     * @var   array $filters The filters registered with WordPress to fire when the plugin loads.
     */
    private array $filters = [];
    
    /**
     * Add a new action to the collection to be registered with WordPress.
     *
     * @since 1.0.0
     * @param string $hook           The name of the WordPress action that is being registered.
     * @param object $component      A reference to the instance of the object on which the action is defined.
     * @param string $callback       The name of the function definition on the $component.
     * @param int    $priority       Optional. The priority at which the function should be fired. Default is 10.
     * @param int    $accepted_args  Optional. The number of arguments that should be passed to the $callback. Default is 1.
     */
    final public function add_action(string $hook, object $component, string $callback, int $priority = 10, int $accepted_args = 1): void
    {
        $this->actions = $this->add($this->actions, $hook, $component, $callback, $priority, $accepted_args);
    }
    
    /**
     * Add a new filter to the collection to be registered with WordPress.
     *
     * @since 1.0.0
     * @param string $hook           The name of the WordPress filter that is being registered.
     * @param object $component      A reference to the instance of the object on which the filter is defined.
     * @param string $callback       The name of the function definition on the $component.
     * @param int    $priority       Optional. The priority at which the function should be fired. Default is 10.
     * @param int    $accepted_args  Optional. The number of arguments that should be passed to the $callback. Default is 1.
     */
    final public function add_filter(string $hook, object $component, string $callback, int $priority = 10, int $accepted_args = 1): void
    {
        $this->filters = $this->add($this->filters, $hook, $component, $callback, $priority, $accepted_args);
    }
    
    /**
     * A utility function that is used to register the actions and hooks into a single collection.
     *
     * @since 1.0.0
     * @param array  $hooks          The collection of hooks that is being registered (that is, actions or filters).
     * @param string $hook           The name of the WordPress filter that is being registered.
     * @param object $component      A reference to the instance of the object on which the filter is defined.
     * @param string $callback       The name of the function definition on the $component.
     * @param int    $priority       The priority at which the function should be fired.
     * @param int    $accepted_args  The number of arguments that should be passed to the $callback.
     * @return array The collection of actions and filters registered with WordPress.
     */
    private function add(array $hooks, string $hook, object $component, string $callback, int $priority, int $accepted_args): array
    {
        $hooks[] = [
            'hook'          => $hook,
            'component'     => $component,
            'callback'      => $callback,
            'priority'      => $priority,
            'accepted_args' => $accepted_args
        ];
        
        return $hooks;
    }
    
    /**
     * Register the filters and actions with WordPress.
     *
     * @since 1.0.0
     */
    final public function run(): void
    {
        foreach ($this->filters as $hook) {
            add_filter($hook['hook'], [$hook['component'], $hook['callback']], $hook['priority'], $hook['accepted_args']);
        }
        
        foreach ($this->actions as $hook) {
            add_action($hook['hook'], [$hook['component'], $hook['callback']], $hook['priority'], $hook['accepted_args']);
        }
    }
}
