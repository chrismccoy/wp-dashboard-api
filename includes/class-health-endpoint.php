<?php

/**
 * Health endpoint
 *
 * Provides the REST endpoint `/wp-dashboard/v1/health`
 */

declare(strict_types=1);

namespace WP_Dashboard;

// Prevent direct file access outside of WordPress.
defined('ABSPATH') || exit;

/**
 * Handles the /health REST API endpoint.
 */
class Health_Endpoint
{
    /**
     * Registers the /health REST route
     */
    public function register_routes(): void
    {
        register_rest_route(
            WP_DASHBOARD_API_NAMESPACE,
            '/health',
            [
                [
                    'methods'             => \WP_REST_Server::READABLE,
                    'callback'            => [$this, 'get_health_data'],
                    'permission_callback' => [$this, 'check_permission'],
                ],
            ]
        );
    }

    /**
     * Permission callback for the /health endpoint.
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
     * REST callback for GET /health.
     */
    public function get_health_data(\WP_REST_Request $request): \WP_REST_Response
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
     * Returns the full health data payload.
     */
    public function get_data(): array
    {
        return [
            'wordpress'  => $this->get_wordpress_info(),
            'php'        => $this->get_php_info(),
            'database'   => $this->get_database_info(),
            'server'     => $this->get_server_info(),
            'filesystem' => $this->get_filesystem_info(),
        ];
    }

    /**
     * Collects WordPress core configuration and settings.
     */
    private function get_wordpress_info(): array
    {
        global $wp_version;

        return [
            'version'             => $wp_version,
            'site_url'            => get_site_url(),
            'home_url'            => get_home_url(),
            'is_multisite'        => is_multisite(),
            'permalink_structure' => get_option('permalink_structure') ?: 'Plain',
            'debug_mode'          => defined('WP_DEBUG') && WP_DEBUG,
            'debug_log'           => defined('WP_DEBUG_LOG') && WP_DEBUG_LOG,
            'script_debug'        => defined('SCRIPT_DEBUG') && SCRIPT_DEBUG,
            'cron_disabled'       => defined('DISABLE_WP_CRON') && DISABLE_WP_CRON,
            'https'               => is_ssl(),
            'language'            => get_locale(),
            'timezone'            => wp_timezone_string(),
            'admin_email'         => get_option('admin_email'),
        ];
    }

    /**
     * PHP runtime configuration and loaded extensions
     */
    private function get_php_info(): array
    {
        return [
            'version'             => phpversion(),
            'sapi'                => php_sapi_name(),
            'memory_limit'        => ini_get('memory_limit'),
            'memory_usage'        => $this->format_bytes(memory_get_usage(true)),
            'memory_peak'         => $this->format_bytes(memory_get_peak_usage(true)),
            'max_execution_time'  => (int) ini_get('max_execution_time'),
            'max_input_vars'      => (int) ini_get('max_input_vars'),
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size'       => ini_get('post_max_size'),
            'display_errors'      => (bool) ini_get('display_errors'),
            'extensions'          => [
                'curl'     => extension_loaded('curl'),
                'json'     => extension_loaded('json'),
                'mbstring' => extension_loaded('mbstring'),
                'openssl'  => extension_loaded('openssl'),
                'zip'      => extension_loaded('zip'),
                'gd'       => extension_loaded('gd'),
                'imagick'  => extension_loaded('imagick'),
                'intl'     => extension_loaded('intl'),
            ],
            'curl_version'        => $this->get_curl_version(),
        ];
    }

    /**
     * Database server information
     */
    private function get_database_info(): array
    {
        global $wpdb;

	$db_version       = $wpdb->get_var('SELECT VERSION()');
	$db_version_clean = $db_version !== null ? explode('-', $db_version)[0] : null;

        $is_mariadb = stripos($db_version ?? '', 'mariadb') !== false;

        // Determine which PHP database extension is in use.
        $db_extension = class_exists('mysqli') ? 'MySQLi' : 'PDO_MySQL';

        /**
         * All table data and index sizes in the WordPress database.
         */
        $db_size = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT SUM(data_length + index_length)
                 FROM information_schema.TABLES
                 WHERE table_schema = %s",
                DB_NAME
            )
        );

        return [
            'version'    => $db_version_clean,
            'is_mariadb' => $is_mariadb,
            'extension'  => $db_extension,
            'host'       => DB_HOST,
            'name'       => DB_NAME,
            'prefix'     => $wpdb->prefix,
            'charset'    => DB_CHARSET,
            'collate'    => DB_COLLATE ?: 'Default',
            'size'       => $db_size !== null
                ? $this->format_bytes((int) $db_size)
                : 'N/A',
            'size_bytes' => $db_size !== null ? (int) $db_size : null,
        ];
    }

    /**
     * Web server software and operating system information.
     */
    private function get_server_info(): array
    {
        $software    = $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown';
        $server_type = 'Unknown';

        if (stripos($software, 'apache') !== false) {
            $server_type = 'Apache';
        } elseif (stripos($software, 'nginx') !== false) {
            $server_type = 'Nginx';
        } elseif (stripos($software, 'litespeed') !== false) {
            $server_type = 'LiteSpeed';
        }

        return [
            'software'   => $software,
            'type'       => $server_type,
            'os'         => php_uname('s') . ' ' . php_uname('r'),
            'os_name'    => php_uname('s'),
            'os_version' => php_uname('r'),
            'hostname'   => php_uname('n'),
            'ip_address' => $_SERVER['SERVER_ADDR'] ?? 'N/A',
            'port'       => $_SERVER['SERVER_PORT'] ?? 'N/A',
            'gateway'    => $_SERVER['GATEWAY_INTERFACE'] ?? 'N/A',
            'protocol'   => $_SERVER['SERVER_PROTOCOL'] ?? 'N/A',
        ];
    }

    /**
     * Filesystem and disk usage information for the uploads directory.
     */
    private function get_filesystem_info(): array
    {
        $upload_dir  = wp_upload_dir();
        $upload_path = $upload_dir['basedir'];

        /**
         * Free disk space in bytes
         */
        $disk_free = function_exists('disk_free_space')
            ? disk_free_space($upload_path)
            : null;

        /**
         * Total disk space in bytes
         */
        $disk_total = function_exists('disk_total_space')
            ? disk_total_space($upload_path)
            : null;

        $disk_free  = $disk_free  === false ? null : $disk_free;
        $disk_total = $disk_total === false ? null : $disk_total;

        $disk_used = ($disk_total !== null && $disk_free !== null)
            ? $disk_total - $disk_free
            : null;

        $disk_used_percent = ($disk_total !== null && $disk_used !== null && $disk_total > 0)
            ? round(($disk_used / $disk_total) * 100, 1)
            : null;

        return [
            'upload_dir'        => $upload_path,
            'upload_url'        => $upload_dir['baseurl'],
            'is_writable'       => wp_is_writable($upload_path),
            'disk_free_space'   => $disk_free !== null
                ? $this->format_bytes((int) $disk_free)
                : 'N/A',
            'disk_free_bytes'   => $disk_free !== null ? (int) $disk_free : null,
            'disk_total_space'  => $disk_total !== null
                ? $this->format_bytes((int) $disk_total)
                : 'N/A',
            'disk_total_bytes'  => $disk_total !== null ? (int) $disk_total : null,
            'disk_used_percent' => $disk_used_percent,
        ];
    }

    /**
     * Converts a raw byte count into a human-readable string
     */
    private function format_bytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow   = $bytes > 0 ? (int) floor(log($bytes) / log(1024)) : 0;
        $pow   = min($pow, count($units) - 1);

        return round($bytes / (1024 ** $pow), $precision) . ' ' . $units[$pow];
    }

    /**
     * cURL version
     */
    private function get_curl_version(): ?string
    {
        if (!extension_loaded('curl')) {
            return null;
        }

        $info = curl_version();

        return $info['version'] ?? null;
    }
}
