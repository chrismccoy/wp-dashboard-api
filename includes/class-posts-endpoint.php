<?php
/**
 * Posts endpoint
 *
 * Provides the REST endpoint `/wp-dashboard/v1/posts`
 */

declare(strict_types=1);

namespace WP_Dashboard;

// Prevent direct file access outside of WordPress.
defined('ABSPATH') || exit;

/**
 * Handles the /posts REST API endpoint.
 */
class Posts_Endpoint
{
    /**
     * Default number of recent posts to return when no `limit` is specified.
     */
    private const DEFAULT_LIMIT = 5;

    /**
     * Maximum number of recent posts the endpoint will return.
     */
    private const MAX_LIMIT = 50;

    /**
     * Registers the /posts REST route
     */
    public function register_routes(): void
    {
        register_rest_route(
            WP_DASHBOARD_API_NAMESPACE,
            '/posts',
            [
                [
                    'methods'             => \WP_REST_Server::READABLE,
                    'callback'            => [$this, 'get_posts_data'],
                    'permission_callback' => [$this, 'check_permission'],
                    'args'                => [
                        'limit' => [
                            'description'       => __(
                                'Number of recent posts to return. Must be between 1 and 50.'
                            ),
                            'type'              => 'integer',
                            'default'           => self::DEFAULT_LIMIT,
                            'sanitize_callback' => 'absint',
                            'validate_callback' => fn(int $value): bool =>
                                $value >= 1 && $value <= self::MAX_LIMIT,
                        ],
                    ],
                ],
            ]
        );
    }

    /**
     * Permission callback for the /posts endpoint.
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
     * REST callback for GET /posts.
     */
    public function get_posts_data(\WP_REST_Request $request): \WP_REST_Response
    {
        return new \WP_REST_Response(
            [
                'success'   => true,
                'timestamp' => current_time('c'),
                'data'      => $this->get_data((int) $request->get_param('limit')),
            ],
            200
        );
    }

    /**
     * Full posts data payload.
     */
    public function get_data(int $limit = self::DEFAULT_LIMIT): array
    {
        $post_counts = wp_count_posts('post');

        $page_counts = wp_count_posts('page');

        /**
         * Fetch recent posts across the four primary visible statuses.
         */
        $recent = get_posts([
            'numberposts'      => $limit,
            'post_type'        => 'post',
            'post_status'      => ['publish', 'draft', 'pending', 'future'],
            'orderby'          => 'date',
            'order'            => 'DESC',
            'suppress_filters' => false,
        ]);

        return [
            'counts' => [
                'posts' => [
                    'published' => (int) $post_counts->publish,
                    'draft'     => (int) $post_counts->draft,
                    'pending'   => (int) $post_counts->pending,
                    'future'    => (int) $post_counts->future,
                    'trash'     => (int) $post_counts->trash,
                    'total'     => (int) (
                        $post_counts->publish +
                        $post_counts->draft +
                        $post_counts->pending +
                        $post_counts->future
                    ),
                ],
                'pages' => [
                    'published' => (int) $page_counts->publish,
                    'draft'     => (int) $page_counts->draft,
                    'total'     => (int) ($page_counts->publish + $page_counts->draft),
                ],
            ],
            'recent' => array_map([$this, 'format_post'], $recent),
        ];
    }

    /**
     * Normalises a WP_Post object into a flat array
     */
    private function format_post(\WP_Post $post): array
    {
        $author_id = (int) $post->post_author;

        $thumbnail_id = get_post_thumbnail_id($post->ID);

        /**
         * Thumbnail-size URL of the featured image, or null if none is set.
         */
        $thumbnail_url = $thumbnail_id
            ? wp_get_attachment_image_url($thumbnail_id, 'thumbnail')
            : false;

        return [
            'id'            => $post->ID,
            'title'         => get_the_title($post->ID),
            'slug'          => $post->post_name,
            'status'        => $post->post_status,
            'date'          => get_the_date('c', $post->ID),
            'modified'      => get_the_modified_date('c', $post->ID),
            'author'        => [
                'id'     => $author_id,
                'name'   => get_the_author_meta('display_name', $author_id),
                'avatar' => get_avatar_url($author_id, ['size' => 32]),
            ],
            'permalink'     => get_permalink($post->ID),
            'comment_count' => (int) $post->comment_count,
            'excerpt'       => get_the_excerpt($post->ID),
            'thumbnail'     => $thumbnail_url ?: null,
            'categories'    => wp_get_post_categories($post->ID, ['fields' => 'names']),
            'tags'          => array_values(
                array_map(
                    static fn(\WP_Term $t): string => $t->name,
                    wp_get_post_tags($post->ID)
                )
            ),
        ];
    }
}
