(function () {
    function initCarousel(root) {
        var viewport = root.querySelector('.event-o-carousel-viewport');
        if (!viewport) return;

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

        applySlides();
        window.addEventListener('resize', applySlides, { passive: true });

        function scrollByCard(dir) {
            var card = root.querySelector('.event-o-card');
            if (!card) return;

            var cardWidth = card.getBoundingClientRect().width;
            var gap = 16;
            var delta = (cardWidth + gap) * dir;
            viewport.scrollBy({ left: delta, behavior: 'smooth' });
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
    }

    function initCopyButtons() {
        document.addEventListener('click', function (e) {
            // Handle both .event-o-share-copy and .event-o-share-instagram buttons
            var btn = e.target.closest('.event-o-share-copy, .event-o-share-instagram');
            if (!btn) return;

            var url = btn.getAttribute('data-url');
            if (!url) return;

            var isInstagram = btn.classList.contains('event-o-share-instagram');

            navigator.clipboard.writeText(url).then(function () {
                var originalHtml = btn.innerHTML;
                btn.innerHTML = '<svg viewBox="0 0 24 24" fill="currentColor" width="20" height="20"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>';
                btn.style.borderColor = '#4caf50';
                btn.style.color = '#4caf50';

                setTimeout(function () {
                    btn.innerHTML = originalHtml;
                    btn.style.borderColor = '';
                    btn.style.color = '';
                }, 2000);
            }).catch(function () {
                // Fallback for older browsers.
                var textarea = document.createElement('textarea');
                textarea.value = url;
                textarea.style.position = 'fixed';
                textarea.style.opacity = '0';
                document.body.appendChild(textarea);
                textarea.select();
                document.execCommand('copy');
                document.body.removeChild(textarea);
            });
        });
    }

    function initCalendarDropdowns() {
        document.addEventListener('click', function (e) {
            // Toggle calendar dropdown when clicking the calendar button
            var calendarBtn = e.target.closest('.event-o-share-calendar');
            if (calendarBtn) {
                var dropdown = calendarBtn.closest('.event-o-calendar-dropdown');
                if (dropdown) {
                    // Close all other dropdowns first
                    document.querySelectorAll('.event-o-calendar-dropdown.is-open').forEach(function (d) {
                        if (d !== dropdown) d.classList.remove('is-open');
                    });
                    // Toggle this dropdown
                    dropdown.classList.toggle('is-open');
                    e.preventDefault();
                    e.stopPropagation();
                }
                return;
            }

            // Close dropdowns when clicking outside
            if (!e.target.closest('.event-o-calendar-dropdown')) {
                document.querySelectorAll('.event-o-calendar-dropdown.is-open').forEach(function (d) {
                    d.classList.remove('is-open');
                });
            }
        });

        // Close dropdown when pressing Escape
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                document.querySelectorAll('.event-o-calendar-dropdown.is-open').forEach(function (d) {
                    d.classList.remove('is-open');
                });
            }
        });
    }

    function initGridSliders() {
        var grids = document.querySelectorAll('.event-o-grid');
        grids.forEach(function (grid) {
            var track = grid.querySelector('.event-o-grid-track');
            var dots = grid.querySelectorAll('.event-o-grid-dot');
            if (!track || dots.length === 0) return;

            // Click on dot scrolls to that card
            dots.forEach(function (dot) {
                dot.addEventListener('click', function () {
                    var index = parseInt(dot.getAttribute('data-index'), 10);
                    var cards = track.querySelectorAll('.event-o-grid-card');
                    if (cards[index]) {
                        cards[index].scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'start' });
                    }
                });
            });

            // Update dots on scroll
            var scrollTimeout;
            track.addEventListener('scroll', function () {
                clearTimeout(scrollTimeout);
                scrollTimeout = setTimeout(function () {
                    var cards = track.querySelectorAll('.event-o-grid-card');
                    var trackRect = track.getBoundingClientRect();
                    var activeIndex = 0;
                    cards.forEach(function (card, i) {
                        var cardRect = card.getBoundingClientRect();
                        var cardCenter = cardRect.left + cardRect.width / 2;
                        var trackCenter = trackRect.left + trackRect.width / 2;
                        if (Math.abs(cardCenter - trackCenter) < cardRect.width / 2) {
                            activeIndex = i;
                        }
                    });
                    dots.forEach(function (d, i) {
                        d.classList.toggle('is-active', i === activeIndex);
                    });
                }, 50);
            }, { passive: true });
        });
    }

    function initAccordionAnimations() {
        var accordions = document.querySelectorAll('.event-o-accordion-item');
        accordions.forEach(function (details) {
            var summary = details.querySelector('.event-o-accordion-summary');
            var panel = details.querySelector('.event-o-accordion-panel');
            if (!summary || !panel) return;

            var isAnimating = false;

            summary.addEventListener('click', function (e) {
                e.preventDefault();

                if (isAnimating) return;

                if (details.open) {
                    // Closing animation
                    isAnimating = true;
                    panel.style.gridTemplateRows = '1fr';
                    requestAnimationFrame(function () {
                        panel.style.gridTemplateRows = '0fr';
                    });

                    panel.addEventListener('transitionend', function handler() {
                        panel.removeEventListener('transitionend', handler);
                        details.open = false;
                        isAnimating = false;
                    }, { once: true });
                } else {
                    // Opening animation
                    details.open = true;
                    isAnimating = true;
                    panel.style.gridTemplateRows = '0fr';
                    requestAnimationFrame(function () {
                        requestAnimationFrame(function () {
                            panel.style.gridTemplateRows = '1fr';
                        });
                    });

                    panel.addEventListener('transitionend', function handler() {
                        panel.removeEventListener('transitionend', handler);
                        panel.style.gridTemplateRows = '';
                        isAnimating = false;
                    }, { once: true });
                }
            });
        });
    }

    function boot() {
        var carousels = document.querySelectorAll('.event-o-carousel');
        carousels.forEach(initCarousel);

        initCopyButtons();
        initCalendarDropdowns();
        initGridSliders();
        initAccordionAnimations();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})();
