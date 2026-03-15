(function () {
    var frontend = window.EventOFrontend = window.EventOFrontend || {};

    function initCarousel(root) {
        var viewport = root.querySelector('.event-o-carousel-viewport');
        var track = root.querySelector('.event-o-carousel-track');
        if (!viewport || !track) return;

        var resizeFrame = null;
        var autoPlayHandle = null;
        var autoPlayLastTs = 0;
        var autoPlayPaused = false;
        var settleTimeout = null;
        var loopEnabled = false;
        var loopSegmentWidth = 0;
        var loopStartCard = null;
        var loopAfterCard = null;
        var autoPlay = root.getAttribute('data-autoplay') === '1';

        function baseSlides() {
            var raw = parseInt(root.getAttribute('data-slides') || '3', 10);
            if (isNaN(raw) || raw < 1) return 3;
            return Math.min(6, raw);
        }

        function effectiveSlides() {
            var w = viewport.getBoundingClientRect().width;
            if (w < 600) return 1;
            if (w < 900) return Math.min(2, baseSlides());
            return baseSlides();
        }

        function applySlides() {
            root.style.setProperty('--event-o-slides', String(effectiveSlides()));
        }

        function getBaseCard() {
            return track.querySelector('.event-o-card:not(.event-o-card--clone)') || track.querySelector('.event-o-card');
        }

        function getCardStep() {
            var card = getBaseCard();
            if (!card) return 0;

            return card.getBoundingClientRect().width + 20;
        }

        function normalizeLoopPosition() {
            if (!loopEnabled || loopSegmentWidth <= 0) return;

            while (viewport.scrollLeft < loopSegmentWidth) {
                viewport.scrollLeft += loopSegmentWidth;
            }

            while (viewport.scrollLeft >= loopSegmentWidth * 2) {
                viewport.scrollLeft -= loopSegmentWidth;
            }
        }

        function measureLoop() {
            if (!loopEnabled || !loopStartCard || !loopAfterCard) return;

            loopSegmentWidth = loopAfterCard.offsetLeft - loopStartCard.offsetLeft;
            if (loopSegmentWidth > 0 && viewport.scrollLeft <= 0) {
                viewport.scrollLeft = loopSegmentWidth;
            }
        }

        function markClone(card, segment) {
            card.classList.add('event-o-card--clone');
            card.setAttribute('aria-hidden', 'true');
            card.setAttribute('data-loop-segment', segment);

            card.querySelectorAll('a, button, input, select, textarea, [tabindex]').forEach(function (node) {
                node.setAttribute('tabindex', '-1');
            });
        }

        function setupInfiniteLoop() {
            if (!autoPlay || track.getAttribute('data-loop-ready') === '1') return;

            var originals = Array.prototype.slice.call(track.children);
            if (originals.length < 2) return;

            var groupCopies = Math.max(2, Math.ceil((baseSlides() + 1) / originals.length) + 1);

            var beforeFragment = document.createDocumentFragment();
            var middleFragment = document.createDocumentFragment();
            var afterFragment = document.createDocumentFragment();
            var afterClones = [];

            for (var beforeIndex = 0; beforeIndex < groupCopies; beforeIndex++) {
                originals.forEach(function (card) {
                    var clone = card.cloneNode(true);
                    markClone(clone, 'before');
                    beforeFragment.appendChild(clone);
                });
            }

            for (var middleIndex = 1; middleIndex < groupCopies; middleIndex++) {
                originals.forEach(function (card) {
                    middleFragment.appendChild(card.cloneNode(true));
                });
            }

            for (var afterIndex = 0; afterIndex < groupCopies; afterIndex++) {
                originals.forEach(function (card) {
                    var clone = card.cloneNode(true);
                    markClone(clone, 'after');
                    afterClones.push(clone);
                    afterFragment.appendChild(clone);
                });
            }

            track.insertBefore(beforeFragment, track.firstChild);
            track.appendChild(middleFragment);
            track.appendChild(afterFragment);
            track.setAttribute('data-loop-ready', '1');

            loopEnabled = true;
            loopStartCard = originals[0];
            loopAfterCard = afterClones[0];
        }

        function refreshLayout() {
            applySlides();
            measureLoop();

            window.requestAnimationFrame(function () {
                applySlides();
                measureLoop();

                if (loopEnabled) {
                    normalizeLoopPosition();
                    return;
                }

                var maxScroll = Math.max(0, viewport.scrollWidth - viewport.clientWidth);
                if (viewport.scrollLeft > maxScroll) {
                    viewport.scrollLeft = maxScroll;
                }
            });
        }

        function scheduleLayoutRefresh() {
            if (resizeFrame) {
                window.cancelAnimationFrame(resizeFrame);
            }

            resizeFrame = window.requestAnimationFrame(function () {
                refreshLayout();
                resizeFrame = null;
            });
        }

        function smoothSettleToCard() {
            var step = getCardStep();
            if (step <= 0) return;

            var baseOffset = loopEnabled ? loopSegmentWidth : 0;
            var relative = viewport.scrollLeft - baseOffset;

            if (loopEnabled && loopSegmentWidth > 0) {
                while (relative < 0) {
                    relative += loopSegmentWidth;
                }
                while (relative >= loopSegmentWidth) {
                    relative -= loopSegmentWidth;
                }
            }

            var target = Math.round(relative / step) * step + baseOffset;
            viewport.style.scrollBehavior = 'smooth';
            viewport.scrollTo({ left: target, behavior: 'smooth' });

            window.clearTimeout(settleTimeout);
            settleTimeout = window.setTimeout(function () {
                viewport.style.scrollBehavior = '';
                if (loopEnabled) {
                    normalizeLoopPosition();
                }
                if (autoPlayPaused) {
                    viewport.classList.remove('is-autoplaying');
                }
            }, 260);
        }

        function scrollByCard(dir) {
            var step = getCardStep();
            if (step <= 0) return;

            viewport.scrollBy({ left: step * dir, behavior: 'smooth' });
        }

        setupInfiniteLoop();
        refreshLayout();

        window.addEventListener('resize', scheduleLayoutRefresh, { passive: true });

        if (typeof ResizeObserver !== 'undefined') {
            var resizeObserver = new ResizeObserver(function () {
                scheduleLayoutRefresh();
            });
            resizeObserver.observe(root);
            resizeObserver.observe(viewport);
            resizeObserver.observe(track);
        }

        if (loopEnabled) {
            viewport.addEventListener('scroll', function () {
                if (!autoPlayPaused) {
                    normalizeLoopPosition();
                }
            }, { passive: true });
        }

        root.addEventListener('click', function (e) {
            var btn = e.target.closest('.event-o-carousel-nav');
            if (!btn) return;
            var dir = btn.getAttribute('data-dir');
            if (dir === 'prev') scrollByCard(-1);
            if (dir === 'next') scrollByCard(1);
        });

        root.addEventListener('keydown', function (e) {
            if (e.key === 'ArrowLeft') scrollByCard(-1);
            if (e.key === 'ArrowRight') scrollByCard(1);
        });

        if (autoPlay) {
            var interval = Math.max(1, parseInt(root.getAttribute('data-autoplay-interval') || '5', 10));

            function autoPlaySpeed() {
                var step = getCardStep();
                if (step <= 0) return 0;

                return step / interval;
            }

            function stepAutoPlay(timestamp) {
                if (!autoPlayHandle) {
                    return;
                }

                if (autoPlayPaused) {
                    autoPlayLastTs = timestamp;
                    autoPlayHandle = window.requestAnimationFrame(stepAutoPlay);
                    return;
                }

                if (!autoPlayLastTs) {
                    autoPlayLastTs = timestamp;
                }

                var delta = timestamp - autoPlayLastTs;
                autoPlayLastTs = timestamp;
                viewport.scrollLeft += autoPlaySpeed() * (delta / 1000);

                if (loopEnabled) {
                    normalizeLoopPosition();
                } else {
                    var maxScroll = Math.max(0, viewport.scrollWidth - viewport.clientWidth);
                    if (viewport.scrollLeft >= maxScroll - 1) {
                        viewport.scrollLeft = 0;
                    }
                }

                autoPlayHandle = window.requestAnimationFrame(stepAutoPlay);
            }

            function startAutoPlay() {
                autoPlayPaused = false;
                window.clearTimeout(settleTimeout);
                viewport.style.scrollBehavior = '';
                viewport.classList.add('is-autoplaying');

                if (loopEnabled) {
                    measureLoop();
                    normalizeLoopPosition();
                }

                if (!autoPlayHandle) {
                    autoPlayLastTs = 0;
                    autoPlayHandle = window.requestAnimationFrame(stepAutoPlay);
                }
            }

            function stopAutoPlay() {
                autoPlayPaused = true;
                smoothSettleToCard();
            }

            root.addEventListener('mouseenter', stopAutoPlay);
            root.addEventListener('mouseleave', startAutoPlay);
            root.addEventListener('touchstart', stopAutoPlay, { passive: true });
            root.addEventListener('touchend', function () {
                window.setTimeout(startAutoPlay, 2000);
            });
            root.addEventListener('focusin', stopAutoPlay);
            root.addEventListener('focusout', startAutoPlay);

            startAutoPlay();
        }
    }

    frontend.registerInit(function initCarouselBlocks() {
        document.querySelectorAll('.event-o-carousel').forEach(initCarousel);
    });
})();
