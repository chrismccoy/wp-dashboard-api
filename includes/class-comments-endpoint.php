<?php
/**
 * Comments endpoint class file.
 *
 * Provides the REST endpoint `/wp-dashboard/v1/comments`
 */

declare(strict_types=1);

namespace WP_Dashboard;

// Prevent direct file access outside of WordPress.
defined('ABSPATH') || exit;

/**
 * Handles the /comments REST API endpoint.
 */
class Comments_Endpoint
{
    /**
     * Default number of recent comments to return when no `limit` is given.
     */
    private const DEFAULT_LIMIT = 5;

    /**
     * Maximum number of recent comments the endpoint will return.
     */
    private const MAX_LIMIT = 50;

    /**
     * Allowed values for the `status` query parameter.
     */
    private const ALLOWED_STATUSES = ['all', 'approve', 'hold', 'spam', 'trash'];

    /**
     * Registers the /comments REST route with WordPress.
     */
    public function register_routes(): void
    {
        register_rest_route(
            WP_DASHBOARD_API_NAMESPACE,
            '/comments',
            [
                [
                    'methods'             => \WP_REST_Server::READABLE,
                    'callback'            => [$this, 'get_comments_data'],
                    'permission_callback' => [$this, 'check_permission'],
                    'args'                => [
                        'limit'  => [
                            'description'       => __(
                                'Number of recent comments to return. Must be between 1 and 50.'
                            ),
                            'type'              => 'integer',
                            'default'           => self::DEFAULT_LIMIT,
                            'sanitize_callback' => 'absint',
                            'validate_callback' => fn(int $value): bool =>
                                $value >= 1 && $value <= self::MAX_LIMIT,
                        ],
                        'status' => [
                            'description'       => __(
                                'Filter comments by status. One of: all, approve, hold, spam, trash.'
                            ),
                            'type'              => 'string',
                            'default'           => 'all',
                            'sanitize_callback' => 'sanitize_text_field',
                            'validate_callback' => fn(string $value): bool =>
                                in_array($value, self::ALLOWED_STATUSES, true),
                        ],
                    ],
                ],
            ]
        );
    }

    /**
     * Permission callback for the /comments endpoint.
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
     * REST callback for GET /comments.
     */
    public function get_comments_data(\WP_REST_Request $request): \WP_REST_Response
    {
        return new \WP_REST_Response(
            [
                'success'   => true,
                'timestamp' => current_time('c'),
                'data'      => $this->get_data(
                    (int) $request->get_param('limit'),
                    (string) $request->get_param('status')
                ),
            ],
            200
        );
    }

    /**
     * Returns the full comments data payload.
     */
    public function get_data(
        int $limit = self::DEFAULT_LIMIT,
        string $status = 'all'
    ): array {
        $counts = wp_count_comments();

        /**
         * Recent comments matching the requested status.
         */
        $recent = get_comments([
            'number'  => $limit,
            'orderby' => 'comment_date_gmt',
            'order'   => 'DESC',
            'status'  => $status,
            'type'    => 'comment',
        ]);

        return [
            'counts' => [
                'approved' => (int) $counts->approved,
                'pending'  => (int) $counts->moderated,
                'spam'     => (int) $counts->spam,
                'trash'    => (int) $counts->trash,
                'total'    => (int) $counts->total_comments,
            ],
            'recent' => array_map([$this, 'format_comment'], $recent),
        ];
    }

    /**
     * Normalises a WP_Comment object into a flat array.
     */
    private function format_comment(\WP_Comment $comment): array
    {
        /** @var int $post_id ID of the post this comment belongs to. */
        $post_id = (int) $comment->comment_post_ID;

        return [
            'id'              => (int) $comment->comment_ID,
            'status'          => $this->map_status($comment->comment_approved),
            'date'            => $comment->comment_date_gmt,
            'content'         => $comment->comment_content,
            'content_excerpt' => wp_trim_words($comment->comment_content, 15),
            'author'          => [
                'name'   => $comment->comment_author,
                'email'  => $comment->comment_author_email,
                'url'    => $comment->comment_author_url,
                'ip'     => $comment->comment_author_IP,
                'avatar' => get_avatar_url(
                    $comment->comment_author_email,
                    ['size' => 32]
                ),
            ],
            'post'            => [
                'id'        => $post_id,
                'title'     => get_the_title($post_id),
                'permalink' => get_permalink($post_id),
            ],
            'parent_id'       => (int) $comment->comment_parent ?: null,
        ];
    }

    /**
     * Maps the raw `comment_approved` database value to a readable status string.
     */
    private function map_status(string $approved): string
    {
        return match ($approved) {
            '1'     => 'approved',
            '0'     => 'pending',
            'spam'  => 'spam',
            'trash' => 'trash',
            default => 'unknown',
        };
    }
}
