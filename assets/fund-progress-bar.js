/**
 * Fund Progress Bar – Front-end JavaScript
 *
 * Strategy (Lazy Load):
 *  1. Page renders immediately with the PHP-injected default value (no JS needed).
 *  2. After DOM ready, JS fires an async fetch with a configurable long timeout.
 *  3. The rest of the page is NEVER blocked — fetch runs in background.
 *  4. When (if) the API responds, ONLY the widget's numbers/bar re-animate.
 *  5. If API fails/times out → keep showing the default value + subtle error badge.
 */
(function ($) {
    'use strict';

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

    function updateWidget($card, percentage, animMs) {
        var circumference = parseFloat($card.data('circumference')) || 314.16;
        percentage = Math.max(0, Math.min(100, parseFloat(percentage) || 0));

        /* Circular ring */
        var $ring = $card.find('.fpb-circle-progress');
        if ($ring.length) {
            var offset = circumference - (percentage / 100) * circumference;
            $ring.css('transition', 'stroke-dashoffset ' + animMs + 'ms cubic-bezier(0.34,1.3,0.64,1)');
            requestAnimationFrame(function () { $ring.css('stroke-dashoffset', offset); });
        }

        /* Circular counter */
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

    function markLive($card) {
        $card.addClass('fpb-live-ok').removeClass('fpb-error');
        $card.find('.fpb-live-dot').addClass('fpb-dot-ok');
        $card.find('.fpb-live-label').text('live ✓');
        $card.find('.fpb-error').hide();
    }

    function markError($card) {
        $card.addClass('fpb-error fpb-live-ok');
        $card.find('.fpb-live-dot').addClass('fpb-dot-error');
        $card.find('.fpb-live-label').text('offline');
        $card.find('.fpb-error').fadeIn(300);
    }

    /* Background fetch — does NOT block page rendering */
    function fetchLiveData($card) {
        var apiUrl    = $card.data('api');
        var timeoutMs = (parseInt($card.data('timeout'), 10) || 15) * 1000;
        var animMs    = parseInt($card.data('anim'), 10) || 1800;
        if (!apiUrl) return;

        var controller = new AbortController();
        var timer = setTimeout(function () { controller.abort(); }, timeoutMs);

        fetch(apiUrl, { method: 'GET', signal: controller.signal, headers: { 'Accept': 'application/json' } })
            .then(function (res) {
                clearTimeout(timer);
                if (!res.ok) throw new Error('HTTP ' + res.status);
                return res.json();
            })
            .then(function (data) {
                if (data && typeof data.percentage !== 'undefined') {
                    markLive($card);
                    updateWidget($card, data.percentage, animMs);
                } else { throw new Error('bad payload'); }
            })
            .catch(function (err) {
                clearTimeout(timer);
                markError($card);
                console.warn('[FundProgressBar] fetch failed:', err && err.message);
            });
    }

    function initWidgets() {
        $('.fpb-widget').each(function () {
            var $card   = $(this);
            var refresh = parseInt($card.data('refresh'), 10) || 0;

            fetchLiveData($card);   // lazy: fires immediately, doesn't block page

            if (refresh > 0) {
                setInterval(function () { fetchLiveData($card); }, refresh * 1000);
            }
        });
    }

    $(document).ready(initWidgets);

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
