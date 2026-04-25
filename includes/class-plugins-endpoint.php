<?php

/**
 * Plugins endpoint
 *
 * Provides the REST endpoint `/wp-dashboard/v1/plugins`
 */

declare(strict_types=1);

namespace WP_Dashboard;

// Prevent direct file access outside of WordPress.
defined('ABSPATH') || exit;

/**
 * Handles the /plugins REST API endpoint.
 */
class Plugins_Endpoint
{
    /**
     * Registers the /plugins REST route
     */
    public function register_routes(): void
    {
        register_rest_route(
            WP_DASHBOARD_API_NAMESPACE,
            '/plugins',
            [
                [
                    'methods'             => \WP_REST_Server::READABLE,
                    'callback'            => [$this, 'get_plugins_data'],
                    'permission_callback' => [$this, 'check_permission'],
                ],
            ]
        );
    }

    /**
     * Permission callback for the /plugins endpoint.
     */
    public function check_permission(): bool|\WP_Error
    {
        if (!current_user_can('manage_options')) {
            return new \WP_Error(
                'rest_forbidden',
                __('You do not have permission to access this endpoint.'),
                ['status' => 403]
            );
        }

        return true;
    }

    /**
     * REST callback for GET /plugins.
     */
    public function get_plugins_data(\WP_REST_Request $request): \WP_REST_Response
    {
        return new \WP_REST_Response(
            [
                'success'   => true,
                'timestamp' => current_time('c'),
                'data'      => $this->get_data(),
            ],
            200
        );
    }

    /**
     * Full plugins data payload.
     */
    public function get_data(): array
    {
        if (!function_exists('get_plugins')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        $all_plugins = get_plugins();

        /**
         * List of plugin file paths that are active on the current site.
         */
        $active_plugins = get_option('active_plugins', []);

        /**
         * On multisite, also collect network-activated plugins
         */
        $network_active = [];
        if (is_multisite()) {
            $network_active = array_keys(
                get_site_option('active_sitewide_plugins', [])
            );
        }

        $active = [];

        $inactive = [];

        foreach ($all_plugins as $plugin_file => $plugin_data) {
            $is_active         = in_array($plugin_file, $active_plugins, true);
            $is_network_active = in_array($plugin_file, $network_active, true);

            $entry = $this->build_plugin_entry(
                $plugin_file,
                $plugin_data,
                $is_active,
                $is_network_active
            );

            if ($is_active || $is_network_active) {
                $active[] = $entry;
            } else {
                $inactive[] = $entry;
            }
        }

        // Sort both groups alphabetically by plugin display name.
        $sort_by_name = static fn(array $a, array $b): int =>
            strcmp($a['name'], $b['name']);

        usort($active, $sort_by_name);
        usort($inactive, $sort_by_name);

        return [
            'counts'   => [
                'total'    => count($all_plugins),
                'active'   => count($active),
                'inactive' => count($inactive),
            ],
            'active'   => $active,
            'inactive' => $inactive,
        ];
    }

    /**
     * Plugin array from raw WordPress plugin data.
     */
    private function build_plugin_entry(
        string $plugin_file,
        array $plugin_data,
        bool $is_active,
        bool $is_network_active
    ): array {
        return [
            'file'              => $plugin_file,
            'name'              => $plugin_data['Name'],
            'version'           => $plugin_data['Version'],
            'author'            => wp_strip_all_tags($plugin_data['Author']),
            'author_uri'        => $plugin_data['AuthorURI'],
            'plugin_uri'        => $plugin_data['PluginURI'],
            'description'       => wp_strip_all_tags($plugin_data['Description']),
            'text_domain'       => $plugin_data['TextDomain'],
            'requires_wp'       => $plugin_data['RequiresWP']  ?? null,
            'requires_php'      => $plugin_data['RequiresPHP'] ?? null,
            'is_active'         => $is_active || $is_network_active,
            'is_network_active' => $is_network_active,
        ];
    }
}
