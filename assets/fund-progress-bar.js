/**
 * Fund Progress Bar – Front-end JavaScript v1.1.0
 *
 * Fetch strategy:
 *   Browser  →  /wp-json/fpb/v1/proxy?url=<api>&timeout=<s>  →  PHP wp_remote_get  →  API
 *
 * This eliminates CORS and HTTP/HTTPS mixed-content issues entirely.
 * The page renders instantly with the PHP-injected default value;
 * the proxy call runs in the background (lazy load).
 */
(function ($) {
    'use strict';

    var LOG_PREFIX = '[FundProgressBar]';

    /* ── Helpers ──────────────────────────────────────────────────── */

    function log()  { if (window.console && console.log)  console.log.apply(console,  [LOG_PREFIX].concat(Array.prototype.slice.call(arguments))); }
    function warn() { if (window.console && console.warn) console.warn.apply(console, [LOG_PREFIX].concat(Array.prototype.slice.call(arguments))); }

    function easeOutCubic(t) { return 1 - Math.pow(1 - t, 3); }

    function animateCounter($el, from, to, duration, onDone) {
        var start = null;
        function step(ts) {
            if (!start) start = ts;
            var p = Math.min((ts - start) / duration, 1);
            $el.text((from + (to - from) * easeOutCubic(p)).toFixed(1));
            if (p < 1) { requestAnimationFrame(step); }
            else if (typeof onDone === 'function') { onDone(to); }
        }
        requestAnimationFrame(step);
    }

    /* ── Visual update (only the widget's own DOM nodes) ─────────── */

    function updateWidget($card, percentage, animMs) {
        var circumference = parseFloat($card.data('circumference')) || 314.16;
        percentage = Math.max(0, Math.min(100, parseFloat(percentage) || 0));

        log('Updating widget #' + $card.attr('id') + ' →', percentage.toFixed(2) + '%');

        /* SVG ring */
        var $ring = $card.find('.fpb-circle-progress');
        if ($ring.length) {
            var offset = circumference - (percentage / 100) * circumference;
            $ring.css('transition', 'stroke-dashoffset ' + animMs + 'ms cubic-bezier(0.34,1.3,0.64,1)');
            requestAnimationFrame(function () { $ring.css('stroke-dashoffset', offset); });
        }

        /* Circular number */
        var $value = $card.find('.fpb-percent-value');
        if ($value.length) {
            animateCounter($value, parseFloat($value.text()) || 0, percentage, animMs, null);
        }

        /* Linear bar */
        var $fill = $card.find('.fpb-linear-fill');
        if ($fill.length) {
            $fill.css('transition', 'width ' + animMs + 'ms cubic-bezier(0.34,1.3,0.64,1)');
            requestAnimationFrame(function () { $fill.css('width', percentage.toFixed(4) + '%'); });
        }

        /* Linear label */
        var $pText = $card.find('.fpb-percent-text');
        if ($pText.length) {
            animateCounter($pText, parseFloat($pText.text()) || 0, percentage, animMs, function (f) {
                $pText.text(f.toFixed(1) + '%');
            });
        }
    }

    /* ── Status badges ───────────────────────────────────────────── */

    function markLive($card) {
        $card.addClass('fpb-live-ok').removeClass('fpb-error');
        $card.find('.fpb-live-dot').removeClass('fpb-dot-error').addClass('fpb-dot-ok');
        $card.find('.fpb-live-label').text('live ✓');
        $card.find('.fpb-error').hide();
    }

    function markError($card, reason) {
        $card.addClass('fpb-error fpb-live-ok');
        $card.find('.fpb-live-dot').removeClass('fpb-dot-ok').addClass('fpb-dot-error');
        $card.find('.fpb-live-label').text('offline');
        var msg = reason ? ' (' + reason + ')' : '';
        $card.find('.fpb-error').html('⚠ Не вдалось отримати живі дані — відображається значення за замовчуванням.' + msg).fadeIn(300);
        warn('Marked error on #' + $card.attr('id') + ':', reason);
    }

    /* ── Core fetch via WP REST proxy ────────────────────────────── */

    function fetchLiveData($card) {
        var apiUrl    = $card.data('api');
        var timeoutMs = (parseInt($card.data('timeout'), 10) || 30) * 1000;
        var animMs    = parseInt($card.data('anim'), 10) || 1800;

        if (!apiUrl) {
            warn('No API URL configured on widget #' + $card.attr('id'));
            return;
        }

        // Build proxy URL: /wp-json/fpb/v1/proxy?url=<encoded>&timeout=<s>
        var proxyBase   = (typeof fpbConfig !== 'undefined' && fpbConfig.proxyBase)
                            ? fpbConfig.proxyBase
                            : '/wp-json/fpb/v1/proxy';
        var timeoutSecs = Math.ceil(timeoutMs / 1000);
        var proxyUrl    = proxyBase + '?url=' + encodeURIComponent(apiUrl) + '&timeout=' + timeoutSecs;

        log('Fetching via proxy:', proxyUrl, '(timeout:', timeoutSecs + 's)');

        // AbortController so we can cancel if the user navigates away
        var controller = (typeof AbortController !== 'undefined') ? new AbortController() : null;
        var abortTimer = controller
            ? setTimeout(function () {
                controller.abort();
                warn('Client-side abort after', timeoutMs, 'ms on #' + $card.attr('id'));
              }, timeoutMs + 2000)   // 2 s grace beyond server timeout
            : null;

        var fetchOptions = { method: 'GET', headers: { 'Accept': 'application/json' } };
        if (controller) fetchOptions.signal = controller.signal;

        fetch(proxyUrl, fetchOptions)
            .then(function (res) {
                if (abortTimer) clearTimeout(abortTimer);
                log('Proxy response status:', res.status);
                if (!res.ok) throw new Error('Proxy HTTP ' + res.status);
                return res.json();
            })
            .then(function (data) {
                log('Received data:', JSON.stringify(data));
                if (data && typeof data.percentage !== 'undefined') {
                    markLive($card);
                    updateWidget($card, data.percentage, animMs);
                } else if (data && data.code) {
                    // WP_Error forwarded as JSON
                    throw new Error(data.message || data.code);
                } else {
                    throw new Error('Unexpected JSON: ' + JSON.stringify(data));
                }
            })
            .catch(function (err) {
                if (abortTimer) clearTimeout(abortTimer);
                var msg = err && err.message ? err.message : String(err);
                markError($card, msg);
            });
    }

    /* ── Bootstrap ───────────────────────────────────────────────── */

    function initWidgets() {
        $('.fpb-widget').each(function () {
            var $card   = $(this);
            var refresh = parseInt($card.data('refresh'), 10) || 0;

            log('Init widget #' + $card.attr('id') + ' | refresh:', refresh + 's | timeout:', $card.data('timeout') + 's');

            fetchLiveData($card);

            if (refresh > 0) {
                setInterval(function () { fetchLiveData($card); }, refresh * 1000);
            }
        });
    }

    $(document).ready(initWidgets);

    // Elementor editor preview
    $(window).on('elementor/frontend/init', function () {
        if (typeof elementorFrontend !== 'undefined') {
            elementorFrontend.hooks.addAction(
                'frontend/element_ready/fund_progress_bar.default',
                function ($scope) {
                    var $card = $scope.find('.fpb-widget');
                    if ($card.length) fetchLiveData($card);
                }
            );
        }
    });

}(jQuery));
