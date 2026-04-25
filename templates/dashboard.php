<?php

/**
 * Frontend dashboard template.
 *
 * Renders the full WordPress stats dashboard as a standalone HTML page.
 */

// Prevent direct file access outside of WordPress.
defined('ABSPATH') || exit;

/**
 * Returns Tailwind colour classes and a display label for a given post status.
 */
$post_status_classes = static function (string $status): array {
    return match ($status) {
        'publish' => [
            'bar'   => 'bg-violet-500',
            'badge' => 'bg-violet-500/10 text-violet-400 border-violet-500/20',
            'label' => 'Published',
        ],
        'draft' => [
            'bar'   => 'bg-yellow-500',
            'badge' => 'bg-yellow-500/10 text-yellow-400 border-yellow-500/20',
            'label' => 'Draft',
        ],
        'pending' => [
            'bar'   => 'bg-orange-500',
            'badge' => 'bg-orange-500/10 text-orange-400 border-orange-500/20',
            'label' => 'Pending',
        ],
        'future' => [
            'bar'   => 'bg-sky-500',
            'badge' => 'bg-sky-500/10 text-sky-400 border-sky-500/20',
            'label' => 'Scheduled',
        ],
        'private' => [
            'bar'   => 'bg-slate-500',
            'badge' => 'bg-slate-500/10 text-slate-400 border-slate-500/20',
            'label' => 'Private',
        ],
        default => [
            'bar'   => 'bg-slate-600',
            'badge' => 'bg-slate-600/10 text-slate-400 border-slate-600/20',
            'label' => ucfirst($status),
        ],
    };
};

/**
 * Returns Tailwind colour classes and a display label for a given comment status.
 */
$comment_status_classes = static function (string $status): array {
    return match ($status) {
        'approved' => [
            'badge' => 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20',
            'label' => 'Approved',
        ],
        'pending' => [
            'badge' => 'bg-yellow-500/10 text-yellow-400 border-yellow-500/20',
            'label' => 'Pending',
        ],
        'spam' => [
            'badge' => 'bg-red-500/10 text-red-400 border-red-500/20',
            'label' => 'Spam',
        ],
        'trash' => [
            'badge' => 'bg-slate-500/10 text-slate-400 border-slate-500/20',
            'label' => 'Trash',
        ],
        default => [
            'badge' => 'bg-slate-700/10 text-slate-500 border-slate-700/20',
            'label' => ucfirst($status),
        ],
    };
};

/**
 * Cycles through a predefined set of Tailwind avatar colour combinations
 * based on a numeric index so each comment author gets a consistent colour
 */
$avatar_color = static function (int $index): string {
    $colors = [
        'bg-violet-500/20 border-violet-500/30 text-violet-300',
        'bg-sky-500/20 border-sky-500/30 text-sky-300',
        'bg-emerald-500/20 border-emerald-500/30 text-emerald-300',
        'bg-pink-500/20 border-pink-500/30 text-pink-300',
        'bg-orange-500/20 border-orange-500/30 text-orange-300',
    ];

    return $colors[$index % count($colors)];
};

/**
 * Cycles through a predefined set of Tailwind gradient class strings for
 * theme avatar tiles when no screenshot is available.
 */
$theme_gradient = static function (int $index): string {
    $gradients = [
        'from-pink-500 to-violet-600',
        'from-sky-500 to-blue-600',
        'from-emerald-500 to-teal-600',
        'from-orange-500 to-red-600',
        'from-slate-500 to-slate-700',
    ];

    return $gradients[$index % count($gradients)];
};

$site_label = (string) preg_replace('#^https?://#', '', $health['wordpress']['site_url'] ?? '');
$plugin_total = (int) ($plugins['counts']['total'] ?? 0);
$plugin_active = (int) ($plugins['counts']['active'] ?? 0);
$plugin_inactive = (int) ($plugins['counts']['inactive'] ?? 0);

$plugin_active_pct = $plugin_total > 0
    ? (int) round(($plugin_active / $plugin_total) * 100)
    : 0;

$post_total = (int) ($posts['counts']['posts']['total'] ?? 0);
$post_published = (int) ($posts['counts']['posts']['published'] ?? 0);

$post_published_pct = $post_total > 0
    ? (int) round(($post_published / $post_total) * 100)
    : 0;

$page_total = (int) ($posts['counts']['pages']['total'] ?? 0);
$page_published = (int) ($posts['counts']['pages']['published'] ?? 0);

$page_published_pct = $page_total > 0
    ? (int) round(($page_published / $page_total) * 100)
    : 0;

$comment_total = (int) ($comments['counts']['total'] ?? 0);
$comment_approved = (int) ($comments['counts']['approved'] ?? 0);
$comment_pending = (int) ($comments['counts']['pending'] ?? 0);

$comment_approved_pct = $comment_total > 0
    ? (int) round(($comment_approved / $comment_total) * 100)
    : 0;

$theme_total = (int) ($themes['counts']['total'] ?? 0);
$php = $health['php'] ?? [];
$db = $health['database'] ?? [];
$wp = $health['wordpress'] ?? [];
$server = $health['server'] ?? [];
$fs = $health['filesystem'] ?? [];

/**
 * True when PHP is >= 8.0, HTTPS is active, and WP_DEBUG is off.
 */
$health_good = (
    version_compare((string) ($php['version'] ?? '0'), '8.0', '>=')
    && !empty($wp['https'])
    && empty($wp['debug_mode'])
);

$disk_pct = min((float) ($fs['disk_used_percent'] ?? 0), 100);

/**
 * Disk bar colour class: red above 85%, yellow above 65%, sky blue otherwise.
 */
$disk_bar_color = match (true) {
    $disk_pct > 85 => 'bg-red-500',
    $disk_pct > 65 => 'bg-yellow-500',
    default        => 'bg-sky-500',
};

$fetched_at = current_time('M j, Y \a\t g:i A');

/**
 * REST API URL for the summary endpoint, passed to JavaScript for AJAX refresh.
 */
$rest_url_encoded = wp_json_encode(rest_url('wp-dashboard/v1/summary'));

/**
 * A single use nonce passed to JavaScript so the AJAX refresh
 * can authenticate against the WordPress REST API
 */
$nonce_encoded = wp_json_encode(wp_create_nonce('wp_rest'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>WP Dashboard &middot; <?php echo esc_html($site_label); ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>
  <style>
    ::-webkit-scrollbar { width: 4px; }
    ::-webkit-scrollbar-track { background: transparent; }
    ::-webkit-scrollbar-thumb { background: #334155; border-radius: 999px; }
    @keyframes wpdash-spin {
      from { transform: rotate(0deg); }
      to   { transform: rotate(360deg); }
    }
    .wpdash-spinning { animation: wpdash-spin 1s linear infinite; }
  </style>

  <script>
    var wpDash = {
      restUrl: <?php echo $rest_url_encoded; ?>,
      nonce:   <?php echo $nonce_encoded; ?>
    };
  </script>
</head>
<body class="bg-slate-950 text-slate-100 min-h-screen antialiased font-sans">

<header class="border-b border-slate-800 bg-slate-900/80 backdrop-blur sticky top-0 z-50">
  <div class="max-w-screen-2xl mx-auto px-6 py-3 flex items-center justify-between gap-4">

    <div class="flex items-center gap-3">
      <div class="w-8 h-8 rounded-lg bg-blue-600 flex items-center justify-center flex-shrink-0">
        <span class="text-white font-black text-sm">W</span>
      </div>
      <div>
        <p class="font-semibold text-sm leading-none">
          <?php echo esc_html($site_label); ?>
        </p>
        <p class="text-slate-500 text-xs mt-0.5">
          <?php echo !empty($wp['is_multisite']) ? 'Multisite' : 'Single Site'; ?>
        </p>
      </div>
    </div>

    <div class="flex items-center gap-3 flex-wrap justify-end">

      <span class="text-xs text-slate-500 hidden sm:block">
        Last synced: <span class="text-slate-300" id="wpdash-last-synced">
          <?php echo esc_html($fetched_at); ?>
        </span>
      </span>

      <span class="inline-flex items-center gap-1.5 rounded-full bg-blue-500/10 border border-blue-500/20 px-3 py-1 text-xs font-medium text-blue-400">
        <i data-lucide="globe" class="w-3 h-3"></i>
        WordPress <?php echo esc_html($wp['version'] ?? ''); ?>
      </span>

      <?php if (!empty($wp['https'])) : ?>
        <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-500/10 border border-emerald-500/20 px-3 py-1 text-xs font-medium text-emerald-400">
          <i data-lucide="shield-check" class="w-3 h-3"></i> HTTPS
        </span>
      <?php endif; ?>

      <?php if (!empty($wp['debug_mode'])) : ?>
        <span class="inline-flex items-center gap-1.5 rounded-full bg-yellow-500/10 border border-yellow-500/20 px-3 py-1 text-xs font-medium text-yellow-400">
          <i data-lucide="bug" class="w-3 h-3"></i> Debug ON
        </span>
      <?php endif; ?>

      <a href="<?php echo esc_url(admin_url()); ?>"
         class="rounded-lg bg-slate-800 hover:bg-slate-700 transition px-3 py-1.5 text-xs flex items-center gap-1.5 text-slate-300">
        <i data-lucide="layout-dashboard" class="w-3 h-3"></i>
        WP Admin
      </a>

      <button
        id="wpdash-refresh"
        class="rounded-lg bg-slate-800 hover:bg-slate-700 transition px-3 py-1.5 text-xs flex items-center gap-1.5 text-slate-300 disabled:opacity-50 disabled:cursor-not-allowed">
        <i data-lucide="refresh-cw" class="w-3 h-3" id="wpdash-refresh-icon"></i>
        <span id="wpdash-refresh-label">Refresh</span>
      </button>

    </div>
  </div>
</header>

<main class="max-w-screen-2xl mx-auto px-6 py-6 space-y-4">

<div class="grid grid-cols-2 md:grid-cols-4 gap-4">

  <div class="rounded-xl bg-slate-900 border border-slate-800 p-5 flex flex-col gap-3">
    <div class="flex items-center justify-between">
      <span class="text-xs font-medium text-slate-400 uppercase tracking-wider">Total Posts</span>
      <span class="w-8 h-8 rounded-lg bg-violet-500/10 border border-violet-500/20 flex items-center justify-center">
        <i data-lucide="file-text" class="w-4 h-4 text-violet-400"></i>
      </span>
    </div>
    <div>
      <p class="text-3xl font-bold tracking-tight">
        <?php echo esc_html(number_format($post_total)); ?>
      </p>
      <p class="text-xs text-slate-500 mt-1">
        <span class="text-slate-400">
          <?php echo esc_html((string) ($posts['counts']['posts']['draft'] ?? 0)); ?> drafts
        </span>
        &middot;
        <span class="text-yellow-400">
          <?php echo esc_html((string) ($posts['counts']['posts']['pending'] ?? 0)); ?> pending
        </span>
      </p>
    </div>
    <div class="w-full bg-slate-800 rounded-full h-1">
      <div class="bg-violet-500 h-1 rounded-full"
           style="width:<?php echo esc_attr((string) $post_published_pct); ?>%"></div>
    </div>
  </div>

  <div class="rounded-xl bg-slate-900 border border-slate-800 p-5 flex flex-col gap-3">
    <div class="flex items-center justify-between">
      <span class="text-xs font-medium text-slate-400 uppercase tracking-wider">Pages</span>
      <span class="w-8 h-8 rounded-lg bg-indigo-500/10 border border-indigo-500/20 flex items-center justify-center">
        <i data-lucide="layout" class="w-4 h-4 text-indigo-400"></i>
      </span>
    </div>
    <div>
      <p class="text-3xl font-bold tracking-tight">
        <?php echo esc_html(number_format($page_total)); ?>
      </p>
      <p class="text-xs text-slate-500 mt-1">
        <span class="text-emerald-400">
          <?php echo esc_html((string) $page_published); ?> published
        </span>
        &middot;
        <?php echo esc_html((string) ($posts['counts']['pages']['draft'] ?? 0)); ?> drafts
      </p>
    </div>
    <div class="w-full bg-slate-800 rounded-full h-1">
      <div class="bg-indigo-500 h-1 rounded-full"
           style="width:<?php echo esc_attr((string) $page_published_pct); ?>%"></div>
    </div>
  </div>

  <div class="rounded-xl bg-slate-900 border border-slate-800 p-5 flex flex-col gap-3">
    <div class="flex items-center justify-between">
      <span class="text-xs font-medium text-slate-400 uppercase tracking-wider">Comments</span>
      <span class="w-8 h-8 rounded-lg bg-sky-500/10 border border-sky-500/20 flex items-center justify-center">
        <i data-lucide="message-circle" class="w-4 h-4 text-sky-400"></i>
      </span>
    </div>
    <div>
      <p class="text-3xl font-bold tracking-tight">
        <?php echo esc_html(number_format($comment_total)); ?>
      </p>
      <p class="text-xs text-slate-500 mt-1">
        <?php if ($comment_pending > 0) : ?>
          <span class="text-yellow-400">
            <?php echo esc_html((string) $comment_pending); ?> pending
          </span> moderation
        <?php else : ?>
          <span class="text-emerald-400">All clear</span> &middot; no pending
        <?php endif; ?>
      </p>
    </div>
    <div class="w-full bg-slate-800 rounded-full h-1">
      <div class="bg-sky-500 h-1 rounded-full"
           style="width:<?php echo esc_attr((string) $comment_approved_pct); ?>%"></div>
    </div>
  </div>

  <div class="rounded-xl bg-slate-900 border border-slate-800 p-5 flex flex-col gap-3">
    <div class="flex items-center justify-between">
      <span class="text-xs font-medium text-slate-400 uppercase tracking-wider">Plugins</span>
      <span class="w-8 h-8 rounded-lg bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center">
        <i data-lucide="puzzle" class="w-4 h-4 text-emerald-400"></i>
      </span>
    </div>
    <div>
      <p class="text-3xl font-bold tracking-tight">
        <?php echo esc_html((string) $plugin_total); ?>
      </p>
      <p class="text-xs text-slate-500 mt-1">
        <span class="text-emerald-400">
          <?php echo esc_html((string) $plugin_active); ?> active
        </span>
        &middot; <?php echo esc_html((string) $plugin_inactive); ?> inactive
      </p>
    </div>
    <div class="w-full bg-slate-800 rounded-full h-1">
      <div class="bg-emerald-500 h-1 rounded-full"
           style="width:<?php echo esc_attr((string) $plugin_active_pct); ?>%"></div>
    </div>
  </div>

</div>

<div class="grid grid-cols-12 gap-4">

  <div class="col-span-12 lg:col-span-4 xl:col-span-3 rounded-xl bg-slate-900 border border-slate-800 flex flex-col max-h-[640px]">

    <div class="flex items-center gap-2 px-5 pt-5 pb-4 border-b border-slate-800 flex-shrink-0">
      <i data-lucide="puzzle" class="w-4 h-4 text-slate-400"></i>
      <h2 class="font-semibold text-sm">Plugins</h2>
      <span class="ml-auto text-xs text-slate-500">
        <?php echo esc_html((string) $plugin_total); ?> installed
      </span>
    </div>

    <div class="flex-1 overflow-y-auto p-4 space-y-5">

      <div>
        <p class="text-xs font-semibold text-emerald-400 uppercase tracking-wider mb-2 flex items-center gap-1.5">
          <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 inline-block"></span>
          Active (<?php echo esc_html((string) $plugin_active); ?>)
        </p>
        <ul class="space-y-1.5">
          <?php foreach ($plugins['active'] as $plugin) : ?>
            <li class="flex items-center justify-between rounded-lg bg-slate-800/50 px-3 py-2 gap-2">
              <div class="min-w-0">
                <p class="text-sm text-slate-200 truncate">
                  <?php echo esc_html($plugin['name']); ?>
                </p>
                <?php if (!empty($plugin['author'])) : ?>
                  <p class="text-xs text-slate-600 truncate">
                    by <?php echo esc_html($plugin['author']); ?>
                  </p>
                <?php endif; ?>
              </div>
              <span class="text-xs text-slate-500 flex-shrink-0">
                v<?php echo esc_html($plugin['version']); ?>
              </span>
            </li>
          <?php endforeach; ?>
        </ul>
      </div>

      <?php if (!empty($plugins['inactive'])) : ?>
        <div>
          <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2 flex items-center gap-1.5">
            <span class="w-1.5 h-1.5 rounded-full bg-slate-600 inline-block"></span>
            Inactive (<?php echo esc_html((string) $plugin_inactive); ?>)
          </p>
          <ul class="space-y-1.5">
            <?php foreach ($plugins['inactive'] as $plugin) : ?>
              <li class="flex items-center justify-between rounded-lg bg-slate-800/20 border border-slate-800/60 px-3 py-2 gap-2">
                <div class="min-w-0">
                  <p class="text-sm text-slate-500 truncate">
                    <?php echo esc_html($plugin['name']); ?>
                  </p>
                  <?php if (!empty($plugin['author'])) : ?>
                    <p class="text-xs text-slate-700 truncate">
                      by <?php echo esc_html($plugin['author']); ?>
                    </p>
                  <?php endif; ?>
                </div>
                <span class="text-xs text-slate-600 flex-shrink-0">
                  v<?php echo esc_html($plugin['version']); ?>
                </span>
              </li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

    </div>
  </div>

  <div class="col-span-12 md:col-span-6 lg:col-span-5 xl:col-span-5 rounded-xl bg-slate-900 border border-slate-800 flex flex-col">

    <div class="flex items-center gap-2 px-5 pt-5 pb-4 border-b border-slate-800">
      <i data-lucide="file-text" class="w-4 h-4 text-slate-400"></i>
      <h2 class="font-semibold text-sm">Recent Posts</h2>
      <span class="ml-auto text-xs text-slate-500">
        <?php echo esc_html(number_format($post_total)); ?> total
      </span>
    </div>

    <div class="flex-1 p-4 space-y-1">
      <?php foreach ($posts['recent'] as $post) :
          $sc = $post_status_classes($post['status']);
      ?>
        <a href="<?php echo esc_url($post['permalink']); ?>"
           target="_blank" rel="noopener noreferrer"
           class="flex items-start gap-3 rounded-lg hover:bg-slate-800/40 px-2 py-2.5 transition group">

          <div class="w-1 min-h-[2rem] rounded-full <?php echo esc_attr($sc['bar']); ?> flex-shrink-0 mt-0.5"></div>

          <div class="flex-1 min-w-0">
            <p class="text-sm font-medium text-slate-200 truncate group-hover:text-white transition">
              <?php echo esc_html($post['title']); ?>
            </p>
            <p class="text-xs text-slate-500 mt-0.5">
              <?php echo esc_html(date_i18n('M j, Y', strtotime($post['date']))); ?>
              &middot;
              <span class="text-slate-400">
                <?php echo esc_html((string) $post['comment_count']); ?> comments
              </span>
              <?php if (!empty($post['categories'][0])) : ?>
                &middot;
                <span class="text-slate-500">
                  <?php echo esc_html($post['categories'][0]); ?>
                </span>
              <?php endif; ?>
            </p>
          </div>

          <div class="flex items-center gap-2 flex-shrink-0">
            <?php if (!empty($post['author']['avatar'])) : ?>
              <img src="<?php echo esc_url($post['author']['avatar']); ?>"
                   alt="<?php echo esc_attr($post['author']['name']); ?>"
                   class="w-5 h-5 rounded-full opacity-60" />
            <?php endif; ?>
            <span class="text-xs border rounded-full px-2 py-0.5 <?php echo esc_attr($sc['badge']); ?>">
              <?php echo esc_html($sc['label']); ?>
            </span>
          </div>

        </a>
      <?php endforeach; ?>
    </div>

  </div>

  <div class="col-span-12 md:col-span-6 lg:col-span-3 xl:col-span-4 rounded-xl bg-slate-900 border border-slate-800 flex flex-col">

    <div class="flex items-center gap-2 px-5 pt-5 pb-4 border-b border-slate-800">
      <i data-lucide="activity" class="w-4 h-4 text-slate-400"></i>
      <h2 class="font-semibold text-sm">Site Health</h2>
      <?php if ($health_good) : ?>
        <span class="ml-auto inline-flex items-center gap-1 text-xs text-emerald-400 bg-emerald-500/10 border border-emerald-500/20 rounded-full px-2 py-0.5">
          <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span> Good
        </span>
      <?php else : ?>
        <span class="ml-auto inline-flex items-center gap-1 text-xs text-yellow-400 bg-yellow-500/10 border border-yellow-500/20 rounded-full px-2 py-0.5">
          <span class="w-1.5 h-1.5 rounded-full bg-yellow-400 animate-pulse"></span> Review
        </span>
      <?php endif; ?>
    </div>

    <div class="flex-1 p-4 grid grid-cols-2 gap-2 content-start">

      <?php $php_ok = version_compare((string) ($php['version'] ?? '0'), '8.0', '>='); ?>

      <div class="rounded-lg bg-slate-800/50 p-3">
        <p class="text-xs text-slate-500 mb-1">PHP Version</p>
        <p class="text-sm font-semibold flex items-center gap-1 <?php echo $php_ok ? 'text-emerald-400' : 'text-yellow-400'; ?>">
          <i data-lucide="<?php echo $php_ok ? 'check-circle' : 'alert-circle'; ?>" class="w-3 h-3"></i>
          <?php echo esc_html($php['version'] ?? 'N/A'); ?>
        </p>
        <p class="text-xs text-slate-600"><?php echo esc_html($php['sapi'] ?? ''); ?></p>
      </div>

      <div class="rounded-lg bg-slate-800/50 p-3">
        <p class="text-xs text-slate-500 mb-1">
          <?php echo !empty($db['is_mariadb']) ? 'MariaDB' : 'MySQL'; ?>
        </p>
        <p class="text-sm font-semibold text-emerald-400 flex items-center gap-1">
          <i data-lucide="check-circle" class="w-3 h-3"></i>
          <?php echo esc_html($db['version'] ?? 'N/A'); ?>
        </p>
        <p class="text-xs text-slate-600"><?php echo esc_html($db['extension'] ?? ''); ?></p>
      </div>

      <div class="rounded-lg bg-slate-800/50 p-3">
        <p class="text-xs text-slate-500 mb-1">HTTPS</p>
        <p class="text-sm font-semibold flex items-center gap-1 <?php echo !empty($wp['https']) ? 'text-emerald-400' : 'text-red-400'; ?>">
          <i data-lucide="<?php echo !empty($wp['https']) ? 'shield-check' : 'shield-x'; ?>" class="w-3 h-3"></i>
          <?php echo !empty($wp['https']) ? 'Active' : 'Inactive'; ?>
        </p>
      </div>

      <div class="rounded-lg bg-slate-800/50 p-3">
        <p class="text-xs text-slate-500 mb-1">Debug Mode</p>
        <p class="text-sm font-semibold flex items-center gap-1 <?php echo empty($wp['debug_mode']) ? 'text-emerald-400' : 'text-yellow-400'; ?>">
          <i data-lucide="<?php echo empty($wp['debug_mode']) ? 'x-circle' : 'bug'; ?>" class="w-3 h-3"></i>
          <?php echo empty($wp['debug_mode']) ? 'Off' : 'ON'; ?>
        </p>
      </div>

      <div class="rounded-lg bg-slate-800/50 p-3">
        <p class="text-xs text-slate-500 mb-1">Memory Limit</p>
        <p class="text-sm font-semibold text-slate-200">
          <?php echo esc_html($php['memory_limit'] ?? 'N/A'); ?>
        </p>
        <p class="text-xs text-slate-600">
          Using <?php echo esc_html($php['memory_usage'] ?? 'N/A'); ?>
        </p>
      </div>

      <div class="rounded-lg bg-slate-800/50 p-3">
        <p class="text-xs text-slate-500 mb-1">Max Upload</p>
        <p class="text-sm font-semibold text-slate-200">
          <?php echo esc_html($php['upload_max_filesize'] ?? 'N/A'); ?>
        </p>
        <p class="text-xs text-slate-600">
          Post: <?php echo esc_html($php['post_max_size'] ?? 'N/A'); ?>
        </p>
      </div>

      <div class="rounded-lg bg-slate-800/50 p-3">
        <p class="text-xs text-slate-500 mb-1">Max Exec Time</p>
        <p class="text-sm font-semibold text-slate-200">
          <?php echo esc_html((string) ($php['max_execution_time'] ?? 'N/A')); ?>s
        </p>
      </div>

      <div class="rounded-lg bg-slate-800/50 p-3">
        <p class="text-xs text-slate-500 mb-1">WP Cron</p>
        <p class="text-sm font-semibold flex items-center gap-1 <?php echo empty($wp['cron_disabled']) ? 'text-emerald-400' : 'text-yellow-400'; ?>">
          <i data-lucide="<?php echo empty($wp['cron_disabled']) ? 'check-circle' : 'alert-circle'; ?>" class="w-3 h-3"></i>
          <?php echo empty($wp['cron_disabled']) ? 'Enabled' : 'Disabled'; ?>
        </p>
      </div>

      <div class="col-span-2 rounded-lg bg-slate-800/50 p-3">
        <p class="text-xs text-slate-500 mb-1">Permalink Structure</p>
        <p class="text-sm font-mono text-sky-400">
          <?php echo esc_html($wp['permalink_structure'] ?: 'Plain'); ?>
        </p>
      </div>

      <div class="col-span-2 rounded-lg bg-slate-800/50 p-3">
        <p class="text-xs text-slate-500 mb-1">Database Size</p>
        <p class="text-sm font-semibold text-slate-200">
          <?php echo esc_html($db['size'] ?? 'N/A'); ?>
        </p>
        <p class="text-xs text-slate-600">
          <?php echo esc_html($db['charset'] ?? ''); ?>
          &middot;
          <?php echo esc_html($db['collate'] ?? ''); ?>
        </p>
      </div>

    </div>
  </div>

  <div class="col-span-12 md:col-span-6 lg:col-span-5 xl:col-span-5 rounded-xl bg-slate-900 border border-slate-800 flex flex-col">

    <div class="flex items-center gap-2 px-5 pt-5 pb-4 border-b border-slate-800">
      <i data-lucide="messages-square" class="w-4 h-4 text-slate-400"></i>
      <h2 class="font-semibold text-sm">Recent Comments</h2>
      <div class="ml-auto flex items-center gap-2">
        <?php if ($comment_pending > 0) : ?>
          <span class="text-xs bg-yellow-500/10 text-yellow-400 border border-yellow-500/20 rounded-full px-2 py-0.5">
            <?php echo esc_html((string) $comment_pending); ?> pending
          </span>
        <?php endif; ?>
        <span class="text-xs text-slate-500">
          <?php echo esc_html(number_format($comment_total)); ?> total
        </span>
      </div>
    </div>

    <div class="flex-1 p-4 space-y-2">
      <?php foreach ($comments['recent'] as $index => $comment) :
          $sc      = $comment_status_classes($comment['status']);
          $col     = $avatar_color($index);
          $initial = strtoupper(mb_substr($comment['author']['name'] ?? '?', 0, 1));
      ?>
        <div class="flex items-start gap-3 rounded-lg hover:bg-slate-800/40 px-2 py-2 transition">

          <?php if (!empty($comment['author']['avatar'])) : ?>
            <img src="<?php echo esc_url($comment['author']['avatar']); ?>"
                 alt="<?php echo esc_attr($comment['author']['name']); ?>"
                 class="w-7 h-7 rounded-full flex-shrink-0" />
          <?php else : ?>
            <div class="w-7 h-7 rounded-full border flex items-center justify-center text-xs font-bold flex-shrink-0 <?php echo esc_attr($col); ?>">
              <?php echo esc_html($initial); ?>
            </div>
          <?php endif; ?>

          <div class="flex-1 min-w-0">
            <div class="flex items-center gap-2 flex-wrap">
              <p class="text-xs font-semibold text-slate-300">
                <?php echo esc_html($comment['author']['name']); ?>
              </p>
              <span class="text-xs text-slate-600">&middot;</span>
              <p class="text-xs text-slate-500">
                <?php echo esc_html(date_i18n('M j', strtotime($comment['date']))); ?>
              </p>
              <span class="ml-auto text-xs border rounded-full px-1.5 py-0.5 <?php echo esc_attr($sc['badge']); ?>">
                <?php echo esc_html($sc['label']); ?>
              </span>
            </div>
            <p class="text-xs text-slate-500 mt-0.5 truncate">
              on <a href="<?php echo esc_url($comment['post']['permalink']); ?>"
                    target="_blank" rel="noopener noreferrer"
                    class="text-slate-400 hover:text-slate-200 transition">
                <?php echo esc_html($comment['post']['title']); ?>
              </a>
            </p>
            <p class="text-xs text-slate-400 mt-1 line-clamp-1">
              <?php echo esc_html($comment['content_excerpt']); ?>
            </p>
          </div>

        </div>
      <?php endforeach; ?>
    </div>

  </div>

  <div class="col-span-12 md:col-span-6 lg:col-span-3 xl:col-span-4 rounded-xl bg-slate-900 border border-slate-800 flex flex-col">

    <div class="flex items-center gap-2 px-5 pt-5 pb-4 border-b border-slate-800">
      <i data-lucide="layers" class="w-4 h-4 text-slate-400"></i>
      <h2 class="font-semibold text-sm">Themes</h2>
      <span class="ml-auto text-xs text-slate-500">
        <?php echo esc_html((string) $theme_total); ?> installed
      </span>
    </div>

    <div class="flex-1 p-4 space-y-2">
      <?php foreach ($themes['themes'] as $index => $theme) :
          $grad    = $theme_gradient($index);
          $initial = strtoupper(mb_substr($theme['name'] ?? '?', 0, 1));
      ?>
        <div class="rounded-lg <?php echo $theme['is_active']
            ? 'bg-pink-500/5 border border-pink-500/20'
            : 'bg-slate-800/40 border border-slate-800'; ?> p-3 flex items-center gap-3">

          <?php if (!empty($theme['screenshot'])) : ?>
            <img src="<?php echo esc_url($theme['screenshot']); ?>"
                 alt="<?php echo esc_attr($theme['name']); ?>"
                 class="w-10 h-10 rounded-lg object-cover flex-shrink-0" />
          <?php else : ?>
            <div class="w-10 h-10 rounded-lg bg-gradient-to-br <?php echo esc_attr($grad); ?> flex items-center justify-center text-white font-bold text-sm flex-shrink-0">
              <?php echo esc_html($initial); ?>
            </div>
          <?php endif; ?>

          <div class="flex-1 min-w-0">
            <div class="flex items-center gap-2 flex-wrap">
              <p class="text-sm <?php echo $theme['is_active']
                  ? 'font-semibold text-slate-200'
                  : 'font-medium text-slate-400'; ?> truncate">
                <?php echo esc_html($theme['name']); ?>
              </p>
              <?php if ($theme['is_active']) : ?>
                <span class="text-xs bg-pink-500/10 text-pink-400 border border-pink-500/20 rounded-full px-1.5 py-0.5 flex-shrink-0">
                  Active
                </span>
              <?php endif; ?>
              <?php if (!empty($theme['is_child'])) : ?>
                <span class="text-xs bg-sky-500/10 text-sky-400 border border-sky-500/20 rounded-full px-1.5 py-0.5 flex-shrink-0">
                  Child
                </span>
              <?php endif; ?>
            </div>
            <p class="text-xs <?php echo $theme['is_active'] ? 'text-slate-500' : 'text-slate-600'; ?> truncate">
              v<?php echo esc_html($theme['version']); ?>
              <?php if (!empty($theme['author'])) : ?>
                &middot; <?php echo esc_html($theme['author']); ?>
              <?php endif; ?>
            </p>
          </div>

        </div>
      <?php endforeach; ?>
    </div>

  </div>

</div>

<div class="grid grid-cols-12 gap-4">
  <div class="col-span-12 rounded-xl bg-slate-900 border border-slate-800 p-5">

    <div class="flex items-center gap-2 mb-4">
      <i data-lucide="server" class="w-4 h-4 text-slate-400"></i>
      <h2 class="font-semibold text-sm">Server Environment</h2>
      <span class="ml-auto text-xs text-slate-500">
        <?php echo esc_html($server['hostname'] ?? ''); ?>
      </span>
    </div>

    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3">

      <div class="rounded-lg bg-slate-800/50 p-3 border border-slate-800">
        <p class="text-xs text-slate-500 mb-1 flex items-center gap-1">
          <i data-lucide="cpu" class="w-3 h-3"></i> OS
        </p>
        <p class="text-sm font-semibold text-slate-200">
          <?php echo esc_html($server['os_name'] ?? 'N/A'); ?>
        </p>
        <p class="text-xs text-slate-500 truncate">
          <?php echo esc_html($server['os_version'] ?? ''); ?>
        </p>
      </div>

      <div class="rounded-lg bg-slate-800/50 p-3 border border-slate-800">
        <p class="text-xs text-slate-500 mb-1 flex items-center gap-1">
          <i data-lucide="globe" class="w-3 h-3"></i> Web Server
        </p>
        <p class="text-sm font-semibold text-slate-200">
          <?php echo esc_html($server['type'] ?? 'N/A'); ?>
        </p>
        <p class="text-xs text-slate-500 truncate">
          <?php echo esc_html($server['software'] ?? ''); ?>
        </p>
      </div>

      <div class="rounded-lg bg-slate-800/50 p-3 border border-slate-800">
        <p class="text-xs text-slate-500 mb-1 flex items-center gap-1">
          <i data-lucide="code" class="w-3 h-3"></i> PHP SAPI
        </p>
        <p class="text-sm font-semibold text-slate-200">
          <?php echo esc_html($php['sapi'] ?? 'N/A'); ?>
        </p>
        <p class="text-xs text-slate-500">
          v<?php echo esc_html($php['version'] ?? ''); ?>
        </p>
      </div>

      <div class="rounded-lg bg-slate-800/50 p-3 border border-slate-800">
        <p class="text-xs text-slate-500 mb-1 flex items-center gap-1">
          <i data-lucide="database" class="w-3 h-3"></i> DB Extension
        </p>
        <p class="text-sm font-semibold text-slate-200">
          <?php echo esc_html($db['extension'] ?? 'N/A'); ?>
        </p>
        <p class="text-xs text-emerald-400">Active</p>
      </div>

      <?php if (!empty($php['curl_version'])) : ?>
        <div class="rounded-lg bg-slate-800/50 p-3 border border-slate-800">
          <p class="text-xs text-slate-500 mb-1 flex items-center gap-1">
            <i data-lucide="layers" class="w-3 h-3"></i> cURL
          </p>
          <p class="text-sm font-semibold text-slate-200">
            <?php echo esc_html($php['curl_version']); ?>
          </p>
          <p class="text-xs text-emerald-400">Loaded</p>
        </div>
      <?php endif; ?>

      <div class="rounded-lg bg-slate-800/50 p-3 border border-slate-800">
        <p class="text-xs text-slate-500 mb-1 flex items-center gap-1">
          <i data-lucide="hard-drive" class="w-3 h-3"></i> Disk
        </p>
        <p class="text-sm font-semibold text-slate-200">
          <?php echo esc_html($fs['disk_free_space'] ?? 'N/A'); ?> free
        </p>
        <div class="w-full bg-slate-700 rounded-full h-1 mt-1.5">
          <div class="<?php echo esc_attr($disk_bar_color); ?> h-1 rounded-full"
               style="width:<?php echo esc_attr(number_format($disk_pct, 1)); ?>%"></div>
        </div>
        <p class="text-xs text-slate-600 mt-1">
          <?php echo esc_html(number_format($disk_pct, 1)); ?>%
          used of <?php echo esc_html($fs['disk_total_space'] ?? 'N/A'); ?>
        </p>
      </div>

    </div>

    <?php if (!empty($php['extensions']) && is_array($php['extensions'])) : ?>
      <div class="mt-4 flex flex-wrap gap-2 items-center">
        <p class="text-xs text-slate-600 w-full">PHP Extensions:</p>
        <?php foreach ($php['extensions'] as $ext_name => $ext_loaded) : ?>
          <span class="text-xs px-2 py-0.5 rounded-full border <?php echo $ext_loaded
              ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20'
              : 'bg-slate-800 text-slate-600 border-slate-700'; ?>">
            <?php echo esc_html($ext_name); ?>
          </span>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

  </div>
</div>

<p class="text-center text-xs text-slate-700 pb-4">
  WP Dashboard &middot; Data collected at
  <span id="wpdash-footer-synced"><?php echo esc_html($fetched_at); ?></span>
  &middot;
  <a href="<?php echo esc_url(admin_url()); ?>"
     class="hover:text-slate-500 transition">WP Admin</a>
</p>

</main>

<script>lucide.createIcons();</script>
<script src="<?php echo WP_DASHBOARD_JS;?>"></script>

</body>
</html>
