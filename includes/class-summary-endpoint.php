<?php
/**
 * Summary endpoint
 *
 * Provides the REST endpoint `/wp-dashboard/v1/summary`
 */

declare(strict_types=1);

namespace WP_Dashboard;

// Prevent direct file access outside of WordPress.
defined('ABSPATH') || exit;

/**
 * Handles the /summary REST API endpoint.
 */
class Summary_Endpoint
{
    /**
     * Registers the /summary REST route
     */
    public function register_routes(): void
    {
        register_rest_route(
            WP_DASHBOARD_API_NAMESPACE,
            '/summary',
            [
                [
                    'methods'             => \WP_REST_Server::READABLE,
                    'callback'            => [$this, 'get_summary_data'],
                    'permission_callback' => [$this, 'check_permission'],
                ],
            ]
        );
    }

    /**
     * Permission callback for the /summary endpoint.
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
     * REST callback for GET /summary.
     */
    public function get_summary_data(\WP_REST_Request $request): \WP_REST_Response
    {
        /**
         * Full dashboard payload.
         */
        $data = [
            'health'   => (new Health_Endpoint())->get_data(),
            'plugins'  => (new Plugins_Endpoint())->get_data(),
            'themes'   => (new Themes_Endpoint())->get_data(),
            'posts'    => (new Posts_Endpoint())->get_data(),
            'comments' => (new Comments_Endpoint())->get_data(),
        ];

        return new \WP_REST_Response(
            [
                'success'   => true,
                'timestamp' => current_time('c'),
                'data'      => $data,
            ],
            200
        );
    }
}
