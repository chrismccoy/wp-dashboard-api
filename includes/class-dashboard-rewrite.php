<?php
/**
 * Dashboard rewrite
 *
 * Registers a custom WordPress rewrite rule
 */

declare(strict_types=1);

namespace WP_Dashboard;

// Prevent direct file access outside of WordPress.
defined('ABSPATH') || exit;

/**
 * Manages the custom rewrite rule and template rendering for the dashboard URL.
 */
class Dashboard_Rewrite
{
    /**
     * The internal WordPress query variable used to flag dashboard requests.
     */
    private const QUERY_VAR = 'wpdash_view';

    /**
     * The default URL slug for the frontend dashboard page.
     */
    private const DEFAULT_SLUG = 'wpdashboard';

    /**
     * The resolved URL slug for this request
     */
    private string $slug;

    /**
     * Dashboard_Rewrite instance and registers WordPress hooks.
     */
    public function __construct()
    {
        /**
         * Filters the URL slug used for the frontend dashboard page.
         */
        $this->slug = (string) apply_filters(
            'wp_dashboard_api_slug',
            self::DEFAULT_SLUG
        );

        add_action('init', [$this, 'register_rewrite_rule']);
        add_filter('query_vars', [$this, 'register_query_vars']);
        add_action('template_redirect', [$this, 'handle_template_redirect']);
    }

    /**
     * Registers the custom rewrite rule
     */
    public function register_rewrite_rule(): void
    {
        add_rewrite_rule(
            '^' . preg_quote($this->slug, '/') . '/?$',
            'index.php?' . self::QUERY_VAR . '=1',
            'top'
        );
    }

    /**
     * Whitelists the custom query variable in WordPress.
     */
    public function register_query_vars(array $vars): array
    {
        $vars[] = self::QUERY_VAR;

        return $vars;
    }

    /**
     * Intercepts dashboard requests and renders the stats template.
     */
    public function handle_template_redirect(): void
    {
        // Exit early if this is not a dashboard request.
        if (!get_query_var(self::QUERY_VAR)) {
            return;
        }

        // Redirect unauthenticated or unauthorised users to the login page.
        if (!current_user_can('manage_options')) {
            wp_safe_redirect(
                wp_login_url(
                    home_url('/' . $this->slug . '/')
                )
            );
            exit;
        }

        $this->render_dashboard();
        exit;
    }

    /**
     * Collects all dashboard data and renders the HTML template.
     */
    private function render_dashboard(): void
    {
        $template = WP_DASHBOARD_TEMPLATE;

        if (!file_exists($template)) {
            wp_die(
                esc_html__('WP Dashboard: template file not found.'),
                esc_html__('Dashboard Error'),
                ['response' => 500]
            );
        }

        // Collect data directly from each endpoint
        $health   = (new Health_Endpoint())->get_data();
        $plugins  = (new Plugins_Endpoint())->get_data();
        $themes   = (new Themes_Endpoint())->get_data();
        $posts    = (new Posts_Endpoint())->get_data();
        $comments = (new Comments_Endpoint())->get_data();

        // Make the resolved slug available to the template
        $dashboard_url = home_url('/' . $this->slug . '/');

        include $template;
    }

    /**
     * Registers the rewrite rule and immediately flushes rewrite cache
     */
    public static function on_activation(): void
    {
        $slug = (string) apply_filters('wp_dashboard_api_slug', self::DEFAULT_SLUG);

        add_rewrite_rule(
            '^' . preg_quote($slug, '/') . '/?$',
            'index.php?' . self::QUERY_VAR . '=1',
            'top'
        );

        flush_rewrite_rules();
    }

    /**
     * Flushes the WordPress rewrite cache on plugin deactivation
     */
    public static function on_deactivation(): void
    {
        flush_rewrite_rules();
    }
}
