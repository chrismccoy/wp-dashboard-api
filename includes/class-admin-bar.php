<?php
/**
 * Admin bar
 *
 * Adds a top-level item to the WordPress admin toolbar
 */

declare(strict_types=1);

namespace WP_Dashboard;

// Prevent direct file access outside of WordPress.
defined('ABSPATH') || exit;

/**
 * Manages the WP Dashboard admin toolbar
 */
class Admin_Bar
{
    /**
     * The unique ID for the admin bar node.
     */
    private const NODE_ID = 'wp-dashboard-api';

    /**
     * The position of the node in the admin bar.
     */
    private const MENU_PRIORITY = 100;

    /**
     * The resolved dashboard URL slug for this request.
     */
    private string $slug;

    /**
     * Registers the WordPress hook.
     */
    public function __construct()
    {
        /**
         * Filters the URL slug used for the frontend dashboard page.
         */
        $this->slug = (string) apply_filters(
            'wp_dashboard_api_slug',
            'wpdashboard'
        );

        add_action('admin_bar_menu', [$this, 'add_node'], self::MENU_PRIORITY);
    }

    /**
     * Adds the WP Dashboard to the WordPress admin toolbar.
     */
    public function add_node(\WP_Admin_Bar $admin_bar): void
    {
        if (!is_admin_bar_showing() || !current_user_can('manage_options')) {
            return;
        }

        $admin_bar->add_node(
            [
                'id'    => self::NODE_ID,
                'title' => '<span class="ab-icon dashicons dashicons-chart-bar" style="top:2px;"></span>'
                         . '<span class="ab-label">Site Dashboard</span>',
                'href'  => home_url('/' . $this->slug . '/'),
                'meta'  => [
                    'title'  => __('View Site Dashboard'),
                    'target' => '',
                    'class'  => 'wp-dashboard-api-node',
                ],
            ]
        );
    }
}
