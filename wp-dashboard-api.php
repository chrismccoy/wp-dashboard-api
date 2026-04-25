<?php
/**
 * Plugin Name:     WP Dashboard API
 * Plugin URI:      http://github.com/chrismccoy/wp-dashboard-api
 * Description:     Custom REST API endpoints and a frontend stats dashboard for WordPress.
 * Version:         1.0.0
 * Author:          Chris McCoy
 * Author URI:      http://github.com/chrismccoy
 * Requires at least: 6.0
 * Requires PHP:      8.0
 */

declare(strict_types=1);

// Prevent direct file access outside of WordPress.
defined('ABSPATH') || exit;

/**
 * The REST API namespace shared by all endpoints
 */
define('WP_DASHBOARD_API_NAMESPACE', 'wp-dashboard/v1');

/**
 * Dashboard Template File
 */
define('WP_DASHBOARD_TEMPLATE', plugin_dir_path(__FILE__) . 'templates/dashboard.php');

/**
 * Refresh Javascript
 */
define('WP_DASHBOARD_JS', plugin_dir_url(__FILE__) . 'js/refresh.js');

/**
 * List of endpoint files to load
 */
$wp_dash_api_includes = [
    'class-health-endpoint.php',
    'class-plugins-endpoint.php',
    'class-themes-endpoint.php',
    'class-posts-endpoint.php',
    'class-comments-endpoint.php',
    'class-summary-endpoint.php',
    'class-dashboard-rewrite.php',
    'class-admin-bar.php',
];

foreach ($wp_dash_api_includes as $wp_dash_api_file) {
    require_once plugin_dir_path(__FILE__) . 'includes/' . $wp_dash_api_file;
}

/**
 * Registers the custom rewrite rule and immediately flushes the rules
 */
register_activation_hook(__FILE__, static function (): void {
    WP_Dashboard\Dashboard_Rewrite::on_activation();
});

/**
 * Flushes the WordPress rewrite rule cache on deactivation
 */
register_deactivation_hook(__FILE__, static function (): void {
    WP_Dashboard\Dashboard_Rewrite::on_deactivation();
});

/**
 * Registers all WP Dashboard API REST routes.
 */
add_action('rest_api_init', static function (): void {
    $endpoints = [
        new WP_Dashboard\Health_Endpoint(),
        new WP_Dashboard\Plugins_Endpoint(),
        new WP_Dashboard\Themes_Endpoint(),
        new WP_Dashboard\Posts_Endpoint(),
        new WP_Dashboard\Comments_Endpoint(),
        new WP_Dashboard\Summary_Endpoint(),
    ];

    foreach ($endpoints as $endpoint) {
        $endpoint->register_routes();
    }
});

/**
 * Initialises the custom frontend dashboard rewrite system and admin bar
 */
new WP_Dashboard\Dashboard_Rewrite();
new WP_Dashboard\Admin_Bar();
