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
        // Shared set so we can reset animation state of any item externally
        var animating = new WeakSet();
        // Transition duration in CSS is .35s – use 400ms as safety timeout
        var TRANSITION_MS = 400;

        function finishAnimation(details, panel) {
            details._accordionTimer = 0;
            panel.style.gridTemplateRows = '';
            animating.delete(details);
        }

        function forceClose(details) {
            var p = details.querySelector('.event-o-accordion-panel');
            if (details._accordionTimer) { clearTimeout(details._accordionTimer); details._accordionTimer = 0; }
            details.open = false;
            if (p) p.style.gridTemplateRows = '';
            animating.delete(details);
        }

        function animateClose(details) {
            var p = details.querySelector('.event-o-accordion-panel');
            if (!p || !details.open) return;
            animating.add(details);

            // Force the starting value so the transition always has a real delta
            p.style.gridTemplateRows = '1fr';
            // Read layout to flush the value before changing it
            void p.offsetHeight;
            p.style.gridTemplateRows = '0fr';

            function done() {
                if (details._accordionTimer) { clearTimeout(details._accordionTimer); details._accordionTimer = 0; }
                details.open = false;
                finishAnimation(details, p);
            }

            p.addEventListener('transitionend', function handler(ev) {
                if (ev.target !== p) return;
                p.removeEventListener('transitionend', handler);
                done();
            });
            details._accordionTimer = setTimeout(done, TRANSITION_MS);
        }

        var accordions = document.querySelectorAll('.event-o-accordion-item');
        accordions.forEach(function (details) {
            var summary = details.querySelector('.event-o-accordion-summary');
            var panel = details.querySelector('.event-o-accordion-panel');
            if (!summary || !panel) return;

            summary.addEventListener('click', function (e) {
                e.preventDefault();

                if (animating.has(details)) return;

                if (details.open) {
                    // Closing animation
                    animateClose(details);
                } else {
                    // --- single-open: close siblings first ---
                    var blockRoot = details.closest('.event-o-event-list');
                    var singleOpen = blockRoot && blockRoot.getAttribute('data-single-open') === '1';

                    if (singleOpen && blockRoot) {
                        var openSiblings = blockRoot.querySelectorAll('.event-o-accordion-item[open]');
                        var detailsRect = details.getBoundingClientRect();
                        var removedHeight = 0;

                        openSiblings.forEach(function (sibling) {
                            if (sibling === details) return;
                            var sp = sibling.querySelector('.event-o-accordion-panel');
                            if (sp && sibling.getBoundingClientRect().top < detailsRect.top) {
                                removedHeight += sp.getBoundingClientRect().height;
                            }
                            forceClose(sibling);
                        });

                        if (removedHeight > 0) {
                            window.scrollBy(0, -removedHeight);
                        }
                    }

                    // Opening animation
                    details.open = true;
                    animating.add(details);
                    panel.style.gridTemplateRows = '0fr';
                    void panel.offsetHeight; // flush
                    panel.style.gridTemplateRows = '1fr';

                    // Scroll so the opened item aligns with the top of the viewport
                    details.scrollIntoView({ behavior: 'smooth', block: 'start' });

                    function openDone() {
                        if (details._accordionTimer) { clearTimeout(details._accordionTimer); details._accordionTimer = 0; }
                        finishAnimation(details, panel);
                    }

                    panel.addEventListener('transitionend', function handler(ev) {
                        if (ev.target !== panel) return;
                        panel.removeEventListener('transitionend', handler);
                        openDone();
                    });
                    details._accordionTimer = setTimeout(openDone, TRANSITION_MS);
                }
            });
        });
    }

    function initFilters() {
        var blocks = document.querySelectorAll('.event-o.has-filters');
        blocks.forEach(function (block) {
            var selects = block.querySelectorAll('.event-o-filter-select');
            if (selects.length === 0) return;

            selects.forEach(function (select) {
                select.addEventListener('change', function () {
                    applyFilters(block);
                });
            });
        });
    }

    function applyFilters(block) {
        var selects = block.querySelectorAll('.event-o-filter-select');
        var activeFilters = {};
        selects.forEach(function (select) {
            var filterType = select.getAttribute('data-filter');
            var value = select.value;
            if (value) {
                activeFilters[filterType] = value;
            }
        });

        // Determine item selector based on block type
        var items;
        var isListBlock = block.classList.contains('event-o-event-list');
        var isCarousel = block.classList.contains('event-o-carousel');
        var isGrid = block.classList.contains('event-o-grid');
        var isHero = block.classList.contains('event-o-hero');

        if (isListBlock) {
            items = block.querySelectorAll('.event-o-accordion-item');
        } else if (isCarousel) {
            items = block.querySelectorAll('.event-o-card');
        } else if (isGrid) {
            items = block.querySelectorAll('.event-o-grid-card');
        } else if (isHero) {
            items = block.querySelectorAll('.event-o-hero-slide');
        }

        if (!items) return;

        var visibleCount = 0;

        items.forEach(function (item) {
            var show = true;

            if (activeFilters.category) {
                var cats = (item.getAttribute('data-categories') || '').split(',');
                if (cats.indexOf(activeFilters.category) === -1) show = false;
            }
            if (activeFilters.venue) {
                var venues = (item.getAttribute('data-venues') || '').split(',');
                if (venues.indexOf(activeFilters.venue) === -1) show = false;
            }
            if (activeFilters.organizer) {
                var orgs = (item.getAttribute('data-organizers') || '').split(',');
                if (orgs.indexOf(activeFilters.organizer) === -1) show = false;
            }

            item.style.display = show ? '' : 'none';
            if (show) visibleCount++;
        });

        // For list blocks: hide month headers that have no visible items
        if (isListBlock) {
            var headers = block.querySelectorAll('.event-o-month');
            headers.forEach(function (header) {
                var nextEl = header.nextElementSibling;
                var hasVisible = false;
                while (nextEl && !nextEl.classList.contains('event-o-month')) {
                    if (nextEl.classList.contains('event-o-accordion-item') && nextEl.style.display !== 'none') {
                        hasVisible = true;
                        break;
                    }
                    nextEl = nextEl.nextElementSibling;
                }
                header.style.display = hasVisible ? '' : 'none';
            });
        }

        // For grid blocks: update dots
        if (isGrid) {
            var dots = block.querySelectorAll('.event-o-grid-dot');
            var visibleCards = block.querySelectorAll('.event-o-grid-card:not([style*="display: none"])');
            dots.forEach(function (dot, i) {
                dot.style.display = i < visibleCards.length ? '' : 'none';
                dot.classList.toggle('is-active', i === 0);
            });
        }

        // For hero blocks: update dots
        if (isHero) {
            var heroDots = block.querySelectorAll('.event-o-hero-dot');
            var visibleSlides = block.querySelectorAll('.event-o-hero-slide:not([style*="display: none"])');
            heroDots.forEach(function (dot, i) {
                dot.style.display = i < visibleSlides.length ? '' : 'none';
                dot.classList.toggle('is-active', i === 0);
            });
        }

        // Show/hide empty message
        var emptyMsg = block.querySelector('.event-o-filter-empty');
        if (visibleCount === 0) {
            if (!emptyMsg) {
                emptyMsg = document.createElement('p');
                emptyMsg.className = 'event-o-filter-empty';
                emptyMsg.textContent = 'Keine Veranstaltungen gefunden.';
                // Insert after filter bar
                var filterBar = block.querySelector('.event-o-filter-bar');
                if (filterBar && filterBar.nextSibling) {
                    block.insertBefore(emptyMsg, filterBar.nextSibling);
                } else {
                    block.appendChild(emptyMsg);
                }
            }
            emptyMsg.style.display = '';
        } else if (emptyMsg) {
            emptyMsg.style.display = 'none';
        }
    }

    function initHeroSliders() {
        var heroes = document.querySelectorAll('.event-o-hero');
        heroes.forEach(function (hero) {
            var viewport = hero.querySelector('.event-o-hero-viewport');
            var dots = hero.querySelectorAll('.event-o-hero-dot');
            if (!viewport || !dots.length) return;

            var slides = hero.querySelectorAll('.event-o-hero-slide');
            var slideCount = slides.length;
            var currentIndex = 0;
            var autoPlayInterval;

            // Drag state
            var isDragging = false;
            var startX = 0;
            var scrollStart = 0;
            var dragDelta = 0;

            function updateDots(index) {
                dots.forEach(function (dot, i) {
                    dot.classList.toggle('is-active', i === index);
                });
            }

            function scrollToSlide(index, skipSnap) {
                if (index < 0) index = slideCount - 1;
                if (index >= slideCount) index = 0;
                
                currentIndex = index;
                var slide = slides[currentIndex];
                if (!slide) return;

                var targetLeft = slide.offsetLeft;
                var startLeft = viewport.scrollLeft;
                var distance = targetLeft - startLeft;

                updateDots(currentIndex);

                // If already at target, just re-enable snap
                if (Math.abs(distance) < 2) {
                    viewport.classList.remove('is-dragging');
                    return;
                }

                // Keep snap disabled during animation to prevent snap-back
                viewport.classList.add('is-dragging');

                var duration = 350;
                var startTime = null;

                function easeOutCubic(t) {
                    return 1 - Math.pow(1 - t, 3);
                }

                function animate(timestamp) {
                    if (!startTime) startTime = timestamp;
                    var elapsed = timestamp - startTime;
                    var progress = Math.min(elapsed / duration, 1);
                    var easedProgress = easeOutCubic(progress);

                    viewport.scrollLeft = startLeft + (distance * easedProgress);

                    if (progress < 1) {
                        requestAnimationFrame(animate);
                    } else {
                        // Re-enable snap after animation completes
                        viewport.classList.remove('is-dragging');
                    }
                }

                requestAnimationFrame(animate);
            }

            function startAutoPlay() {
                stopAutoPlay();
                autoPlayInterval = setInterval(function() {
                    scrollToSlide(currentIndex + 1);
                }, 5000);
            }

            function stopAutoPlay() {
                if (autoPlayInterval) {
                    clearInterval(autoPlayInterval);
                }
            }

            // Mouse drag handlers
            viewport.addEventListener('mousedown', function (e) {
                // Don't interfere with button/link clicks
                if (e.target.closest('a, button')) return;
                isDragging = true;
                startX = e.pageX;
                scrollStart = viewport.scrollLeft;
                dragDelta = 0;
                viewport.classList.add('is-dragging');
                stopAutoPlay();
                e.preventDefault();
            });

            document.addEventListener('mousemove', function (e) {
                if (!isDragging) return;
                var dx = e.pageX - startX;
                dragDelta = dx;
                viewport.scrollLeft = scrollStart - dx;
            });

            document.addEventListener('mouseup', function () {
                if (!isDragging) return;
                isDragging = false;
                // Don't remove is-dragging here — scrollToSlide handles it
                // after the animation finishes to prevent snap-back

                // Small drag threshold: even a little drag triggers next/prev
                var minDrag = 30;
                if (dragDelta < -minDrag && currentIndex < slideCount - 1) {
                    scrollToSlide(currentIndex + 1);
                } else if (dragDelta > minDrag && currentIndex > 0) {
                    scrollToSlide(currentIndex - 1);
                } else {
                    scrollToSlide(currentIndex);
                }
                startAutoPlay();
            });

            // Touch drag handlers
            viewport.addEventListener('touchstart', function (e) {
                startX = e.touches[0].pageX;
                scrollStart = viewport.scrollLeft;
                dragDelta = 0;
                stopAutoPlay();
            }, { passive: true });

            viewport.addEventListener('touchmove', function (e) {
                dragDelta = e.touches[0].pageX - startX;
            }, { passive: true });

            viewport.addEventListener('touchend', function () {
                // Small drag threshold for touch too
                var minDrag = 30;
                if (dragDelta < -minDrag && currentIndex < slideCount - 1) {
                    scrollToSlide(currentIndex + 1);
                } else if (dragDelta > minDrag && currentIndex > 0) {
                    scrollToSlide(currentIndex - 1);
                } else {
                    scrollToSlide(currentIndex);
                }
                startAutoPlay();
            }, { passive: true });

            dots.forEach(function (dot) {
                dot.addEventListener('click', function () {
                    var index = parseInt(this.getAttribute('data-index'), 10);
                    scrollToSlide(index);
                    startAutoPlay();
                });
            });

            viewport.addEventListener('scroll', function () {
                if (isDragging || viewport.classList.contains('is-dragging')) return;
                clearTimeout(viewport.scrollTimeout);
                viewport.scrollTimeout = setTimeout(function () {
                    var scrollLeft = viewport.scrollLeft;
                    var slideWidth = viewport.clientWidth;
                    var newIndex = Math.round(scrollLeft / slideWidth);
                    if (newIndex !== currentIndex && newIndex >= 0 && newIndex < slideCount) {
                        currentIndex = newIndex;
                        updateDots(currentIndex);
                    }
                }, 100);
            }, { passive: true });

            // Pause on hover (only if not dragging)
            hero.addEventListener('mouseenter', function () {
                if (!isDragging) stopAutoPlay();
            });
            hero.addEventListener('mouseleave', function () {
                if (!isDragging) startAutoPlay();
            });

            startAutoPlay();
        });
    }

    function boot() {
        var carousels = document.querySelectorAll('.event-o-carousel');
        carousels.forEach(initCarousel);

        initCopyButtons();
        initCalendarDropdowns();
        initGridSliders();
        initHeroSliders();
        initAccordionAnimations();
        initFilters();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})();
