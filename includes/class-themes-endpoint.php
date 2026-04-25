<?php
/**
 * Themes endpoint
 *
 * Provides the REST endpoint `/wp-dashboard/v1/themes`
 */

declare(strict_types=1);

namespace WP_Dashboard;

// Prevent direct file access outside of WordPress.
defined('ABSPATH') || exit;

/**
 * Handles the /themes REST API endpoint.
 */
class Themes_Endpoint
{
    /**
     * Registers the /themes REST route
     */
    public function register_routes(): void
    {
        register_rest_route(
            WP_DASHBOARD_API_NAMESPACE,
            '/themes',
            [
                [
                    'methods'             => \WP_REST_Server::READABLE,
                    'callback'            => [$this, 'get_themes_data'],
                    'permission_callback' => [$this, 'check_permission'],
                ],
            ]
        );
    }

    /**
     * Permission callback for the /themes endpoint.
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
     * REST callback for GET /themes.
     */
    public function get_themes_data(\WP_REST_Request $request): \WP_REST_Response
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
     * Full themes data payload.
     */
    public function get_data(): array
    {
        $all_themes = wp_get_themes();

        $active_theme = wp_get_theme();

        /**
         * The stylesheet slug identifies the active theme.
         */
        $active_stylesheet = get_stylesheet();

        /**
         * The template slug identifies the parent (or standalone) theme.
         */
        $active_template = get_template();

        $is_child_theme = ($active_stylesheet !== $active_template);

        $themes = [];

        foreach ($all_themes as $stylesheet => $theme) {
            $themes[] = $this->build_theme_entry(
                $stylesheet,
                $theme,
                $stylesheet === $active_stylesheet
            );
        }

        // Sort: active theme to the top, remaining themes alphabetically.
        usort(
            $themes,
            static function (array $a, array $b): int {
                if ($a['is_active'] !== $b['is_active']) {
                    return $b['is_active'] <=> $a['is_active'];
                }

                return strcmp($a['name'], $b['name']);
            }
        );

        return [
            'counts' => [
                'total' => count($themes),
            ],
            'active' => [
                'stylesheet' => $active_stylesheet,
                'name'       => $active_theme->get('Name'),
                'version'    => $active_theme->get('Version'),
                'author'     => wp_strip_all_tags($active_theme->get('Author')),
                'is_child'   => $is_child_theme,
                'parent'     => $is_child_theme ? $active_template : null,
                'screenshot' => $active_theme->get_screenshot('uri') ?: null,
            ],
            'themes' => $themes,
        ];
    }

    /**
     * Builds a theme entry array from a WP_Theme object.
     */
    private function build_theme_entry(
        string $stylesheet,
        \WP_Theme $theme,
        bool $is_active
    ): array {
        return [
            'stylesheet'   => $stylesheet,
            'name'         => $theme->get('Name'),
            'version'      => $theme->get('Version'),
            'author'       => wp_strip_all_tags($theme->get('Author')),
            'author_uri'   => $theme->get('AuthorURI'),
            'theme_uri'    => $theme->get('ThemeURI'),
            'description'  => $theme->get('Description'),
            'requires_wp'  => $theme->get('RequiresWP'),
            'requires_php' => $theme->get('RequiresPHP'),
            'template'     => $theme->get_template(),
            'is_child'     => ($theme->get_template() !== $stylesheet),
            'is_active'    => $is_active,
            'screenshot'   => $theme->get_screenshot('uri') ?: null,
            'tags'         => $theme->get('Tags'),
        ];
    }
}
