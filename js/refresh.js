(function () {
  'use strict';

  var btn   = document.getElementById('wpdash-refresh');
  var icon  = document.getElementById('wpdash-refresh-icon');
  var label = document.getElementById('wpdash-refresh-label');
  var lastSynced = document.getElementById('wpdash-last-synced');
  var footerSynced = document.getElementById('wpdash-footer-synced');

  // do nothing if the button is not present
  if (!btn || !icon || !label) {
    return;
  }

  /**
   * Puts the refresh button into a loading state.
   */
  function setLoading(loading) {
    btn.disabled = loading;

    if (loading) {
      icon.classList.add('wpdash-spinning');
      label.textContent = 'Refreshing\u2026';
    } else {
      icon.classList.remove('wpdash-spinning');
      label.textContent = 'Refresh';
    }
  }

  /**
   * Puts the refresh button into an error state
   */
  function setError() {
    btn.disabled = false;
    icon.classList.remove('wpdash-spinning');
    label.textContent = 'Failed \u2014 retry?';

    // Restore the default label after 3 seconds
    setTimeout(function () {
      label.textContent = 'Refresh';
    }, 3000);
  }

  /**
   * Updates the "Last synced" timestamp shown in the header and footer
   */
  function updateTimestamp() {
    var now = new Date();
    var formatted = now.toLocaleString('en-US', {
      month: 'short',
      day:   'numeric',
      year:  'numeric',
      hour:  'numeric',
      minute: '2-digit',
    });

    if (lastSynced)   { lastSynced.textContent   = formatted; }
    if (footerSynced) { footerSynced.textContent = formatted; }
  }

  /**
   * Performs the AJAX refresh
   */
  async function refresh() {
    setLoading(true);

    try {
      var response = await fetch(wpDash.restUrl, {
        method:  'GET',
        headers: {
          'X-WP-Nonce':   wpDash.nonce,
          'Content-Type': 'application/json',
          'Accept':        'application/json',
        },
        // Prevent the browser from serving a cached response.
        cache: 'no-store',
      });

      if (!response.ok) {
        throw new Error('API returned ' + response.status + ' ' + response.statusText);
      }

      // Confirm the response body contains the success flag
      var json = await response.json();

      if (!json || json.success !== true) {
        throw new Error('Unexpected API response structure.');
      }

      // Data confirmed fresh update the timestamp then reload.
      updateTimestamp();
      window.location.reload();

    } catch (err) {
      console.error('[WP Dashboard] Refresh failed:', err.message);
      setError();
    }
  }

  btn.addEventListener('click', refresh);
}());
