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
            var btn = e.target.closest('.event-o-share-copy');
            if (!btn) return;

            var url = btn.getAttribute('data-url');
            if (!url) return;

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
            // Dropdown selects
            var selects = block.querySelectorAll('.event-o-filter-select');
            selects.forEach(function (select) {
                select.addEventListener('change', function () {
                    applyFilters(block);
                });
            });

            // Tab / pill buttons
            var tabs = block.querySelectorAll('.event-o-filter-tab');
            tabs.forEach(function (tab) {
                tab.addEventListener('click', function () {
                    // Toggle active state within the same group
                    var group = tab.closest('.event-o-filter-tab-group');
                    if (group) {
                        group.querySelectorAll('.event-o-filter-tab').forEach(function (t) {
                            t.classList.remove('is-active');
                        });
                    }
                    tab.classList.add('is-active');
                    applyFilters(block);

                    // Update mobile toggle active indicator
                    updateMobileFilterIndicator(block);
                });
            });

            // Mobile filter toggle button
            initMobileFilterToggle(block);
        });
    }

    function initMobileFilterToggle(block) {
        var filterBar = block.querySelector('.event-o-filter-bar.is-tabs');
        if (!filterBar) return;

        var toggle = filterBar.querySelector('.event-o-filter-mobile-toggle');
        if (!toggle) return;

        toggle.addEventListener('click', function (e) {
            e.stopPropagation();
            var isOpen = filterBar.classList.toggle('is-mobile-open');
            toggle.classList.toggle('is-open', isOpen);
        });

        // Close on click outside
        document.addEventListener('click', function (e) {
            if (!filterBar.classList.contains('is-mobile-open')) return;
            if (filterBar.contains(e.target)) return;
            filterBar.classList.remove('is-mobile-open');
            toggle.classList.remove('is-open');
        });
    }

    function updateMobileFilterIndicator(block) {
        var filterBar = block.querySelector('.event-o-filter-bar.is-tabs');
        if (!filterBar) return;
        var toggle = filterBar.querySelector('.event-o-filter-mobile-toggle');
        if (!toggle) return;

        // Check if any tab group has a non-"Alle" filter active
        var hasActive = false;
        var tabGroups = filterBar.querySelectorAll('.event-o-filter-tab-group');
        tabGroups.forEach(function (group) {
            var activeTab = group.querySelector('.event-o-filter-tab.is-active');
            if (activeTab && activeTab.getAttribute('data-value') !== '') {
                hasActive = true;
            }
        });
        toggle.classList.toggle('has-active-filter', hasActive);
    }

    function applyFilters(block) {
        var activeFilters = {};

        // Read from dropdown selects
        var selects = block.querySelectorAll('.event-o-filter-select');
        selects.forEach(function (select) {
            var filterType = select.getAttribute('data-filter');
            var value = select.value;
            if (value) {
                activeFilters[filterType] = value;
            }
        });

        // Read from tab groups
        var tabGroups = block.querySelectorAll('.event-o-filter-tab-group');
        tabGroups.forEach(function (group) {
            var filterType = group.getAttribute('data-filter');
            var activeTab = group.querySelector('.event-o-filter-tab.is-active');
            if (activeTab) {
                var value = activeTab.getAttribute('data-value');
                if (value) {
                    activeFilters[filterType] = value;
                }
            }
        });

        // Determine item selector based on block type
        var items;
        var isListBlock = block.classList.contains('event-o-event-list');
        var isCarousel = block.classList.contains('event-o-carousel');
        var isGrid = block.classList.contains('event-o-grid');
        var isHero = block.classList.contains('event-o-hero');
        var isProgram = block.classList.contains('event-o-program');

        if (isListBlock) {
            items = block.querySelectorAll('.event-o-accordion-item');
        } else if (isCarousel) {
            items = block.querySelectorAll('.event-o-card');
        } else if (isGrid) {
            items = block.querySelectorAll('.event-o-grid-card');
        } else if (isHero) {
            items = block.querySelectorAll('.event-o-hero-slide');
        } else if (isProgram) {
            items = block.querySelectorAll('.event-o-program-item');
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

            // For program blocks: save original is-hidden state before first filter
            if (isProgram && !item.hasAttribute('data-was-hidden')) {
                item.setAttribute('data-was-hidden', item.classList.contains('is-hidden') ? '1' : '0');
            }

            if (isProgram) {
                var hasActiveFilter = Object.keys(activeFilters).length > 0;
                if (hasActiveFilter) {
                    // Remove is-hidden so filter controls visibility entirely
                    item.classList.remove('is-hidden');
                    item.style.display = show ? '' : 'none';
                } else {
                    // Restore original pagination state
                    item.style.display = '';
                    if (item.getAttribute('data-was-hidden') === '1') {
                        item.classList.add('is-hidden');
                    }
                }
            } else {
                item.style.display = show ? '' : 'none';
            }

            if (show && !(isProgram && !Object.keys(activeFilters).length && item.getAttribute('data-was-hidden') === '1')) {
                visibleCount++;
            }
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

        // For program blocks: hide/show load-more when filters are active
        if (isProgram) {
            var hasActiveFilter = Object.keys(activeFilters).length > 0;
            var loadMoreWrap = block.querySelector('.event-o-program-loadmore-wrap');
            if (loadMoreWrap) {
                loadMoreWrap.style.display = hasActiveFilter ? 'none' : '';
            }
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
            var track = hero.querySelector('.event-o-hero-track');
            var dots = hero.querySelectorAll('.event-o-hero-dot');
            if (!viewport || !track || !dots.length) return;

            var realSlides = Array.prototype.slice.call(track.querySelectorAll('.event-o-hero-slide'));
            var slideCount = realSlides.length;
            if (slideCount < 2) return;

            var currentIndex = 0;
            var autoPlayTimer;
            var isAnimating = false;
            var autoPlayEnabled = hero.getAttribute('data-autoplay') === '1';
            var autoPlayMs = (parseInt(hero.getAttribute('data-autoplay-interval'), 10) || 5) * 1000;

            // Transition speed
            var speedAttr = hero.getAttribute('data-transition-speed') || 'medium';
            var transitionDuration = speedAttr === 'fast' ? 250 : speedAttr === 'slow' ? 800 : 400;

            // Drag state
            var isDragging = false;
            var startX = 0;
            var scrollStart = 0;
            var dragDelta = 0;

            // --- Infinite scroll: clone first and last slides ---
            var cloneLast = realSlides[slideCount - 1].cloneNode(true);
            var cloneFirst = realSlides[0].cloneNode(true);
            cloneLast.classList.add('is-clone');
            cloneFirst.classList.add('is-clone');
            cloneLast.setAttribute('aria-hidden', 'true');
            cloneFirst.setAttribute('aria-hidden', 'true');
            // Remove IDs from clones to avoid duplicates
            cloneLast.removeAttribute('id');
            cloneFirst.removeAttribute('id');

            track.insertBefore(cloneLast, realSlides[0]);
            track.appendChild(cloneFirst);

            // All slides in DOM: [clone-last] [real-0] ... [real-N-1] [clone-first]
            var allSlides = Array.prototype.slice.call(track.querySelectorAll('.event-o-hero-slide'));

            // Real slide at realIndex → DOM index realIndex + 1
            function domIndex(realIdx) {
                return realIdx + 1;
            }

            // Instant jump (no animation, no transition)
            function jumpTo(dIdx) {
                viewport.classList.add('is-dragging');
                viewport.scrollLeft = allSlides[dIdx].offsetLeft;
                void viewport.offsetHeight;
                viewport.classList.remove('is-dragging');
            }

            // Start at the first real slide
            jumpTo(domIndex(0));

            function updateDots(index) {
                dots.forEach(function (dot, i) {
                    dot.classList.toggle('is-active', i === index);
                });
            }

            function easeOutCubic(t) {
                return 1 - Math.pow(1 - t, 3);
            }

            function animateScroll(from, to, callback) {
                var distance = to - from;
                if (Math.abs(distance) < 2) {
                    viewport.classList.remove('is-dragging');
                    if (callback) callback();
                    return;
                }
                viewport.classList.add('is-dragging');
                var duration = transitionDuration;
                var startTime = null;

                function step(timestamp) {
                    if (!startTime) startTime = timestamp;
                    var elapsed = timestamp - startTime;
                    var progress = Math.min(elapsed / duration, 1);
                    viewport.scrollLeft = from + distance * easeOutCubic(progress);
                    if (progress < 1) {
                        requestAnimationFrame(step);
                    } else {
                        viewport.classList.remove('is-dragging');
                        if (callback) callback();
                    }
                }
                requestAnimationFrame(step);
            }

            function scrollToSlide(index) {
                if (isAnimating) return;
                isAnimating = true;

                var targetDomIdx;
                var needsReset = false;
                var resetToReal = 0;

                if (index >= slideCount) {
                    // Forward past last → animate to clone-first, then jump to real-first
                    targetDomIdx = slideCount + 1;
                    needsReset = true;
                    resetToReal = 0;
                    currentIndex = 0;
                } else if (index < 0) {
                    // Backward past first → animate to clone-last, then jump to real-last
                    targetDomIdx = 0;
                    needsReset = true;
                    resetToReal = slideCount - 1;
                    currentIndex = slideCount - 1;
                } else {
                    targetDomIdx = domIndex(index);
                    currentIndex = index;
                }

                updateDots(currentIndex);

                var from = viewport.scrollLeft;
                var to = allSlides[targetDomIdx].offsetLeft;

                animateScroll(from, to, function () {
                    if (needsReset) {
                        // Silently reposition to the real slide
                        jumpTo(domIndex(resetToReal));
                    }
                    isAnimating = false;
                });
            }

            function startAutoPlay() {
                if (!autoPlayEnabled) return;
                stopAutoPlay();
                autoPlayTimer = setInterval(function () {
                    scrollToSlide(currentIndex + 1);
                }, autoPlayMs);
            }

            function stopAutoPlay() {
                if (autoPlayTimer) {
                    clearInterval(autoPlayTimer);
                    autoPlayTimer = null;
                }
            }

            // Mouse drag handlers
            viewport.addEventListener('mousedown', function (e) {
                if (e.target.closest('a, button')) return;
                if (isAnimating) return;
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

                var minDrag = 30;
                if (dragDelta < -minDrag) {
                    scrollToSlide(currentIndex + 1);
                } else if (dragDelta > minDrag) {
                    scrollToSlide(currentIndex - 1);
                } else {
                    scrollToSlide(currentIndex);
                }
                startAutoPlay();
            });

            // Touch drag handlers
            viewport.addEventListener('touchstart', function (e) {
                if (isAnimating) return;
                startX = e.touches[0].pageX;
                scrollStart = viewport.scrollLeft;
                dragDelta = 0;
                isDragging = true;
                viewport.classList.add('is-dragging');
                stopAutoPlay();
            }, { passive: true });

            viewport.addEventListener('touchmove', function (e) {
                if (!isDragging) return;
                e.preventDefault();
                var dx = e.touches[0].pageX - startX;
                dragDelta = dx;
                viewport.scrollLeft = scrollStart - dx;
            }, { passive: false });

            viewport.addEventListener('touchend', function () {
                if (!isDragging) return;
                isDragging = false;

                var minDrag = 30;
                if (dragDelta < -minDrag) {
                    scrollToSlide(currentIndex + 1);
                } else if (dragDelta > minDrag) {
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

            // Pause on hover
            hero.addEventListener('mouseenter', function () {
                if (!isDragging) stopAutoPlay();
            });
            hero.addEventListener('mouseleave', function () {
                if (!isDragging) startAutoPlay();
            });

            startAutoPlay();
        });
    }

    function initProgramLoadMore() {
        var programs = document.querySelectorAll('.event-o-program');
        programs.forEach(function (program) {
            var btn = program.querySelector('.event-o-program-loadmore');
            if (!btn) return;

            btn.addEventListener('click', function () {
                var hiddenItems = program.querySelectorAll('.event-o-program-item.is-hidden');
                hiddenItems.forEach(function (item) {
                    item.classList.remove('is-hidden');
                });
                // Hide the button after revealing all
                var wrap = btn.closest('.event-o-program-loadmore-wrap');
                if (wrap) wrap.style.display = 'none';
            });
        });
    }

    function initEventImageCrossfades() {
        var reducedMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        if (reducedMotion) return;

        var crossfades = document.querySelectorAll('[data-event-o-crossfade="1"]');
        crossfades.forEach(function (root) {
            var slides = root.querySelectorAll('.event-o-crossfade-slide');
            if (!slides || slides.length < 2) return;

            var current = 0;
            var intervalMs = parseInt(root.getAttribute('data-crossfade-interval') || '4500', 10);
            if (isNaN(intervalMs) || intervalMs < 1500) {
                intervalMs = 4500;
            }

            function isSlideReady(slide) {
                if (!slide || !slide.tagName || slide.tagName.toLowerCase() !== 'img') {
                    return true;
                }
                return !!(slide.complete && slide.naturalWidth > 0);
            }

            setInterval(function () {
                var next = (current + 1) % slides.length;
                var attempts = 0;

                while (attempts < slides.length && !isSlideReady(slides[next])) {
                    next = (next + 1) % slides.length;
                    attempts++;
                }

                if (next === current || !isSlideReady(slides[next])) {
                    return;
                }

                slides[current].classList.remove('is-active');
                current = next;
                slides[current].classList.add('is-active');
            }, intervalMs);
        });
    }

    function initDescToggle() {
        document.addEventListener('click', function (e) {
            var btn = e.target.closest('.event-o-desc-toggle');
            if (!btn) return;
            var desc = btn.closest('.event-o-desc-expandable');
            if (!desc) return;
            var inner = desc.querySelector('.event-o-desc-inner');
            if (!inner) return;

            var isExpanded = desc.classList.contains('is-expanded');

            if (!isExpanded) {
                // Expanding: measure short height, switch content, measure full height, animate
                var shortHeight = inner.scrollHeight;
                desc.classList.add('is-expanded');
                var fullHeight = inner.scrollHeight;
                // Set to short height first, then animate to full
                inner.style.maxHeight = shortHeight + 'px';
                // Force reflow
                inner.offsetHeight;
                inner.style.maxHeight = fullHeight + 'px';
                btn.textContent = 'weniger';
                // Clean up after transition
                inner.addEventListener('transitionend', function handler() {
                    inner.style.maxHeight = 'none';
                    inner.removeEventListener('transitionend', handler);
                });
            } else {
                // Collapsing: measure current height, switch content, animate to short height
                var currentHeight = inner.scrollHeight;
                inner.style.maxHeight = currentHeight + 'px';
                // Force reflow
                inner.offsetHeight;
                desc.classList.remove('is-expanded');
                var collapsedHeight = inner.scrollHeight;
                inner.style.maxHeight = collapsedHeight + 'px';
                btn.textContent = 'mehr\u2026';
                inner.addEventListener('transitionend', function handler() {
                    inner.style.maxHeight = '';
                    inner.removeEventListener('transitionend', handler);
                });
            }
        });
    }

    function initHeroParallax() {
        var reducedMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        if (reducedMotion) return;

        var hero = document.querySelector('.event-o-single-hero.event-o-parallax');
        if (!hero) return;

        var images = hero.querySelectorAll('.event-o-single-hero-img');
        if (!images.length) return;

        var ticking = false;

        function updateParallax() {
            var rect = hero.getBoundingClientRect();
            var heroH = hero.offsetHeight;
            var winH = window.innerHeight;

            // Only apply when hero is in viewport
            if (rect.bottom < 0 || rect.top > winH) {
                ticking = false;
                return;
            }

            // Progress: 0 when hero top is at viewport bottom, 1 when hero bottom is at viewport top
            var progress = 1 - (rect.bottom / (winH + heroH));
            // Shift range: move image up to 25% of its height
            var offset = (progress - 0.5) * heroH * 0.25;

            for (var i = 0; i < images.length; i++) {
                images[i].style.transform = 'translateY(' + offset.toFixed(1) + 'px) scale(1.08)';
            }
            ticking = false;
        }

        window.addEventListener('scroll', function () {
            if (!ticking) {
                ticking = true;
                requestAnimationFrame(updateParallax);
            }
        }, { passive: true });

        // Initial position
        updateParallax();
    }

    function initSingleAnimations() {
        var single = document.querySelector('.event-o-single[data-animation]');
        if (!single) return;
        var anim = single.getAttribute('data-animation');
        if (!anim || anim === 'none') return;

        var items = single.querySelectorAll('.eo-anim');
        if (!items.length) return;

        if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            items.forEach(function (el) { el.classList.add('is-visible'); });
            return;
        }

        var observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    var delay = entry.target.getAttribute('data-delay') || '0';
                    entry.target.style.animationDelay = delay + 'ms';
                    entry.target.classList.add('is-visible');
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.15, rootMargin: '0px 0px -40px 0px' });

        var delayStep = 120;
        items.forEach(function (el, i) {
            el.setAttribute('data-delay', String(i * delayStep));
            observer.observe(el);
        });
    }

    function initBlockAnimations() {
        var containers = document.querySelectorAll('[data-animation]');
        if (!containers.length) return;

        var reducedMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

        containers.forEach(function (container) {
            var anim = container.getAttribute('data-animation');
            if (!anim || anim === 'none') return;

            var items = container.querySelectorAll('.eo-block-anim');
            if (!items.length) return;

            if (reducedMotion) {
                items.forEach(function (el) { el.classList.add('is-visible'); });
                return;
            }

            var observer = new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    if (entry.isIntersecting) {
                        var delay = entry.target.getAttribute('data-delay') || '0';
                        entry.target.style.animationDelay = delay + 'ms';
                        entry.target.classList.add('is-visible');
                        observer.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.1, rootMargin: '0px 0px -30px 0px' });

            var delayStep = 100;
            items.forEach(function (el, i) {
                el.setAttribute('data-delay', String(i * delayStep));
                observer.observe(el);
            });
        });
    }

    /* ================================================================
     *  Event Calendar Block
     * ================================================================ */
    var CAL_MONTHS_DE = [
        'Januar','Februar','März','April','Mai','Juni',
        'Juli','August','September','Oktober','November','Dezember'
    ];
    var CAL_DAYS_SUN = ['SO','MO','DI','MI','DO','FR','SA'];
    var CAL_DAYS_MON = ['MO','DI','MI','DO','FR','SA','SO'];

    function calEscHtml(s) {
        var d = document.createElement('div');
        d.textContent = s;
        return d.innerHTML;
    }
    function calEscAttr(s) {
        return String(s)
            .replace(/&/g,'&amp;').replace(/"/g,'&quot;')
            .replace(/'/g,'&#39;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    }
    function calTodayStr() {
        var d = new Date();
        return d.getFullYear() + '-' +
            String(d.getMonth()+1).padStart(2,'0') + '-' +
            String(d.getDate()).padStart(2,'0');
    }
    function calIsMobile() {
        return window.matchMedia('(max-width: 768px)').matches;
    }

    function initEventCalendars() {
        var wrappers = document.querySelectorAll('.event-o-cal-wrap');
        for (var i = 0; i < wrappers.length; i++) {
            initSingleCalendar(wrappers[i]);
        }
    }

    function initSingleCalendar(wrap) {
        wrap.setAttribute('data-cal-inited', '1');
        var raw = wrap.getAttribute('data-events');
        var weekStart = parseInt(wrap.getAttribute('data-week-start') || '0', 10);
        var popupBlur = wrap.getAttribute('data-popup-blur') !== '0';
        var events = [];
        try { events = JSON.parse(raw) || []; } catch(e) { events = []; }

        // Group events by date
        var eventsMap = {};
        events.forEach(function(ev) {
            if (!ev.date) return;
            if (!eventsMap[ev.date]) eventsMap[ev.date] = [];
            eventsMap[ev.date].push(ev);
        });

        var state = {
            year: new Date().getFullYear(),
            month: new Date().getMonth(),
            weekStart: weekStart,
            popupBlur: popupBlur,
            subscribeUrl: wrap.getAttribute('data-subscribe-url') || '',
            eventsMap: eventsMap,
            popupTimer: null,
            activeEl: null
        };

        calRender(wrap, state);
    }

    function calRender(wrap, state) {
        wrap.innerHTML = '';

        /* Header: ‹ Month Year › */
        var header = document.createElement('div');
        header.className = 'event-o-cal-header';

        var prevBtn = document.createElement('button');
        prevBtn.className = 'event-o-cal-nav prev';
        prevBtn.innerHTML = '&#8249;';
        prevBtn.setAttribute('aria-label', 'Vorheriger Monat');
        prevBtn.addEventListener('click', function() {
            state.month--;
            if (state.month < 0) { state.month = 11; state.year--; }
            calRender(wrap, state);
        });

        var nextBtn = document.createElement('button');
        nextBtn.className = 'event-o-cal-nav next';
        nextBtn.innerHTML = '&#8250;';
        nextBtn.setAttribute('aria-label', 'Nächster Monat');
        nextBtn.addEventListener('click', function() {
            state.month++;
            if (state.month > 11) { state.month = 0; state.year++; }
            calRender(wrap, state);
        });

        var label = document.createElement('h2');
        label.className = 'event-o-cal-month-label';
        label.textContent = CAL_MONTHS_DE[state.month] + ' ' + state.year;

        header.appendChild(prevBtn);
        header.appendChild(label);

        /* Subscribe button (optional) */
        if (state.subscribeUrl) {
            var subWrap = document.createElement('div');
            subWrap.className = 'event-o-cal-subscribe';

            var subBtn = document.createElement('button');
            subBtn.className = 'event-o-cal-subscribe-btn';
            subBtn.setAttribute('type', 'button');
            subBtn.setAttribute('aria-label', 'Kalender abonnieren');
            subBtn.setAttribute('title', 'Kalender abonnieren');
            subBtn.innerHTML = '<svg viewBox="0 0 24 24" fill="currentColor" width="20" height="20"><path d="M19 4h-1V2h-2v2H8V2H6v2H5c-1.11 0-1.99.9-1.99 2L3 20c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 16H5V9h14v11zM9 11H7v2h2v-2zm4 0h-2v2h2v-2zm4 0h-2v2h2v-2zm-8 4H7v2h2v-2zm4 0h-2v2h2v-2zm4 0h-2v2h2v-2z"/></svg>';

            var subMenu = document.createElement('div');
            subMenu.className = 'event-o-cal-subscribe-menu';

            // Build Google Calendar subscribe URL (add by URL)
            var feedUrl = state.subscribeUrl;
            var googleSubUrl = 'https://www.google.com/calendar/render?cid=' + encodeURIComponent(feedUrl.replace(/^https?:/, 'webcal:'));
            var webcalUrl = feedUrl.replace(/^https?:/, 'webcal:');
            var outlookSubUrl = 'https://outlook.live.com/calendar/0/addfromweb?url=' + encodeURIComponent(feedUrl) + '&name=' + encodeURIComponent('Events');

            subMenu.innerHTML =
                '<a href="' + calEscAttr(googleSubUrl) + '" target="_blank" rel="noopener noreferrer" class="event-o-cal-subscribe-option">' +
                    '<svg viewBox="0 0 24 24" fill="currentColor" width="16" height="16"><path d="M19.5 3.5L18 2l-1.5 1.5L15 2l-1.5 1.5L12 2l-1.5 1.5L9 2 7.5 3.5 6 2 4.5 3.5 3 2v20l1.5-1.5L6 22l1.5-1.5L9 22l1.5-1.5L12 22l1.5-1.5L15 22l1.5-1.5L18 22l1.5-1.5L21 22V2l-1.5 1.5zM19 19.09H5V4.91h14v14.18zM6 15h12v2H6zm0-4h12v2H6zm0-4h12v2H6z"/></svg>' +
                    '<span>Google Kalender</span>' +
                '</a>' +
                '<a href="' + calEscAttr(outlookSubUrl) + '" target="_blank" rel="noopener noreferrer" class="event-o-cal-subscribe-option">' +
                    '<svg viewBox="0 0 24 24" fill="currentColor" width="16" height="16"><path d="M19 4h-1V2h-2v2H8V2H6v2H5c-1.11 0-1.99.9-1.99 2L3 20c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 16H5V9h14v11zM9 11H7v2h2v-2zm4 0h-2v2h2v-2zm4 0h-2v2h2v-2zm-8 4H7v2h2v-2zm4 0h-2v2h2v-2zm4 0h-2v2h2v-2z"/></svg>' +
                    '<span>Outlook Kalender</span>' +
                '</a>' +
                '<a href="' + calEscAttr(webcalUrl) + '" class="event-o-cal-subscribe-option">' +
                    '<svg viewBox="0 0 24 24" fill="currentColor" width="16" height="16"><path d="M17 12h-5v5h5v-5zM16 1v2H8V1H6v2H5c-1.11 0-1.99.9-1.99 2L3 19c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2h-1V1h-2zm3 18H5V8h14v11z"/></svg>' +
                    '<span>iCal / Apple</span>' +
                '</a>';

            subBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                subWrap.classList.toggle('is-open');
            });

            document.addEventListener('click', function(e) {
                if (!e.target.closest('.event-o-cal-subscribe')) {
                    subWrap.classList.remove('is-open');
                }
            });

            subWrap.appendChild(subBtn);
            subWrap.appendChild(subMenu);
            header.appendChild(subWrap);
        }

        header.appendChild(nextBtn);
        wrap.appendChild(header);

        /* Grid */
        var grid = document.createElement('div');
        grid.className = 'event-o-cal-grid';

        // Weekday header
        var days = state.weekStart === 1 ? CAL_DAYS_MON : CAL_DAYS_SUN;
        days.forEach(function(d) {
            var dow = document.createElement('div');
            dow.className = 'event-o-cal-dow';
            dow.textContent = d;
            grid.appendChild(dow);
        });

        // Calendar math
        var firstDay = new Date(state.year, state.month, 1);
        var daysInMonth = new Date(state.year, state.month + 1, 0).getDate();
        var startDow = firstDay.getDay();
        if (state.weekStart === 1) startDow = (startDow + 6) % 7;

        var today = calTodayStr();

        // Empty cells before 1st
        for (var e = 0; e < startDow; e++) {
            var emptyCell = document.createElement('div');
            emptyCell.className = 'event-o-cal-day event-o-cal-day-empty';
            emptyCell.dataset.col = String(e);
            grid.appendChild(emptyCell);
        }

        // Day cells
        for (var day = 1; day <= daysInMonth; day++) {
            var dateStr = state.year + '-' +
                String(state.month + 1).padStart(2, '0') + '-' +
                String(day).padStart(2, '0');

            var col = (startDow + day - 1) % 7;
            var row = Math.floor((startDow + day - 1) / 7);

            var cell = document.createElement('div');
            cell.className = 'event-o-cal-day';
            cell.dataset.col = String(col);
            cell.dataset.row = String(row);
            cell.dataset.date = dateStr;
            if (dateStr === today) cell.classList.add('is-today');

            var num = document.createElement('div');
            num.className = 'event-o-cal-day-num';
            num.textContent = day;
            cell.appendChild(num);

            var dayEvents = state.eventsMap[dateStr];
            if (dayEvents && dayEvents.length) {
                cell.classList.add('has-events');
                if (dayEvents.length === 2) cell.classList.add('event-count-2');
                if (dayEvents.length >= 3) cell.classList.add('event-count-3plus');
                cell._dayEvents = dayEvents;

                dayEvents.forEach(function(ev) {
                    var el = document.createElement('div');
                    el.className = 'event-o-cal-event';
                    if (ev.cancelled) el.classList.add('is-cancelled');
                    if (ev.soldOut) el.classList.add('is-sold-out');
                    if (ev.categoryColor) el.style.setProperty('--ev-cat-color', ev.categoryColor);
                    el.dataset.eventId = ev.id;

                    var html = '';
                    if (ev.time) {
                        html += '<span class="event-o-cal-event-time">' + calEscHtml(ev.time) + '</span>';
                    }
                    html += '<span class="event-o-cal-event-title">' + calEscHtml(ev.title) + '</span>';
                    el.innerHTML = html;

                    el._eventData = ev;
                    el._dayEvents = dayEvents;
                    cell.appendChild(el);
                });
            }

            grid.appendChild(cell);
        }

        // Fill remaining cells for last week
        var totalCells = startDow + daysInMonth;
        var rest = totalCells % 7;
        if (rest > 0) {
            for (var r = rest; r < 7; r++) {
                var fillCell = document.createElement('div');
                fillCell.className = 'event-o-cal-day event-o-cal-day-empty';
                fillCell.dataset.col = String(r);
                grid.appendChild(fillCell);
            }
        }

        // Popup element (absolutely positioned inside grid)
        var popup = document.createElement('div');
        popup.className = 'event-o-cal-popup';
        popup.style.display = 'none';
        grid.appendChild(popup);

        wrap.appendChild(grid);

        // Store popupBlur on popup element for calShowPopup
        popup._popupBlur = state.popupBlur;

        // Attach interaction listeners
        calAttachListeners(wrap, grid, popup, state);
    }

    function calAttachListeners(wrap, grid, popup, state) {
        var mobile = calIsMobile();

        if (mobile) {
            /* Mobile: Tap → show popup, tap popup link → navigate */
            wrap.addEventListener('click', function(e) {
                var dayEl = e.target.closest('.event-o-cal-day.has-events');
                var popupLink = e.target.closest('.event-o-cal-popup-link');
                var popupItem = e.target.closest('.event-o-cal-popup-item');

                if (dayEl && !e.target.closest('.event-o-cal-popup')) {
                    e.preventDefault();
                    e.stopPropagation();
                    calShowPopup(grid, popup, dayEl, dayEl._dayEvents || [], true);
                    state.activeEl = dayEl;
                    return;
                }
                if (popupLink || popupItem) return;

                calHidePopup(popup, state);
            });

            document.addEventListener('click', function(e) {
                if (!e.target.closest('.event-o-cal-wrap')) {
                    calHidePopup(popup, state);
                }
            });
        } else {
            /* Desktop: Hover → popup on neighbor cells.
               Use day-cell level tracking for stability. */
            var HOVER_DELAY = 300;

            var dayCells = grid.querySelectorAll('.event-o-cal-day.has-events');
            for (var ci = 0; ci < dayCells.length; ci++) {
                (function(cell) {
                    cell.addEventListener('mouseenter', function() {
                        clearTimeout(state.popupTimer);
                        clearTimeout(state.showTimer);
                        var dayEvts = cell._dayEvents || [];
                        if (!dayEvts.length) return;
                        state.hoverCell = cell;
                        // Show immediately if popup already visible for this cell, else short delay
                        if (popup.style.display === 'flex' && state.activeCell === cell) {
                            return; // already showing for this cell
                        }
                        state.showTimer = setTimeout(function() {
                            calShowPopup(grid, popup, cell, dayEvts, false);
                            state.activeCell = cell;
                        }, 120);
                    });
                    cell.addEventListener('mouseleave', function() {
                        clearTimeout(state.showTimer);
                        state.hoverCell = null;
                        state.popupTimer = setTimeout(function() {
                            calHidePopup(popup, state);
                            state.activeCell = null;
                        }, HOVER_DELAY);
                    });
                })(dayCells[ci]);
            }

            // Event items inside cells: highlight active + click navigation
            var eventEls = grid.querySelectorAll('.event-o-cal-event');
            for (var ei = 0; ei < eventEls.length; ei++) {
                (function(eventEl) {
                    eventEl.style.cursor = 'pointer';
                    eventEl.addEventListener('mouseenter', function() {
                        clearTimeout(state.popupTimer);
                        clearTimeout(state.showTimer);
                        var dayEvts = eventEl._dayEvents || [eventEl._eventData];
                        var activeId = eventEl._eventData && eventEl._eventData.id ? eventEl._eventData.id : null;
                        var cell = eventEl.closest('.event-o-cal-day');
                        calShowPopup(grid, popup, cell || eventEl, dayEvts, false, activeId);
                        state.activeCell = cell;
                    });
                    eventEl.addEventListener('click', function() {
                        var d = eventEl._eventData;
                        if (d && d.url) window.location.href = d.url;
                    });
                })(eventEls[ei]);
            }

            popup.addEventListener('mouseenter', function() {
                clearTimeout(state.popupTimer);
                clearTimeout(state.showTimer);
            });
            popup.addEventListener('mouseleave', function() {
                state.popupTimer = setTimeout(function() {
                    // Only hide if not back on the source cell
                    if (!state.hoverCell) {
                        calHidePopup(popup, state);
                        state.activeCell = null;
                    }
                }, HOVER_DELAY);
            });
        }

        // Escape closes popup
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') calHidePopup(popup, state);
        });
    }

    function calShowPopup(grid, popup, sourceEl, dayEvents, mobile, activeEventId) {
        if (!dayEvents || !dayEvents.length) return;

        var cell = sourceEl.classList && sourceEl.classList.contains('event-o-cal-day')
            ? sourceEl
            : sourceEl.closest('.event-o-cal-day');
        if (!cell) return;
        var col = parseInt(cell.dataset.col, 10);

        var ev = dayEvents[0];
        if (activeEventId) {
            for (var k = 0; k < dayEvents.length; k++) {
                if (String(dayEvents[k].id) === String(activeEventId)) {
                    ev = dayEvents[k];
                    break;
                }
            }
        }

        // Build time string
        function buildTimeStr(item) {
            if (item.time && item.timeEnd) return item.time + ' – ' + item.timeEnd + ' Uhr';
            if (item.time) return 'ab ' + item.time + ' Uhr';
            if (item.timeEnd) return 'bis ' + item.timeEnd + ' Uhr';
            return '';
        }
        var timeStr = buildTimeStr(ev);

        // Status badge
        var statusHtml = '';
        if (ev.cancelled) {
            statusHtml = '<span class="event-o-cal-popup-badge cancelled">Abgesagt</span>';
        } else if (ev.soldOut) {
            statusHtml = '<span class="event-o-cal-popup-badge sold-out">Ausgebucht</span>';
        }

        // Category badge
        var categoryHtml = '';
        if (ev.category) {
            var catStyle = ev.categoryColor ? ' style="--cat-color:' + calEscAttr(ev.categoryColor) + '"' : '';
            categoryHtml = '<div class="event-o-cal-popup-category"' + catStyle + '>' + calEscHtml(ev.category) + '</div>';
        }

        // Venue
        var venueHtml = '';
        if (ev.venue) {
            venueHtml = '<div class="event-o-cal-popup-venue"><span>📍</span> ' + calEscHtml(ev.venue) + '</div>';
        }

        // Background image (blur controlled by setting)
        var bgHtml = '';
        if (ev.image) {
            popup.classList.add('has-image');
            var blurClass = popup._popupBlur ? ' is-blurred' : '';
            bgHtml = '<div class="event-o-cal-popup-bg' + blurClass + '" style="background-image:url(' + calEscAttr(ev.image) + ')"></div>';
        } else {
            popup.classList.remove('has-image');
        }

        // Excerpt
        var excerptHtml = '';
        if (ev.excerpt) {
            excerptHtml = '<div class="event-o-cal-popup-excerpt">' + calEscHtml(ev.excerpt) + '</div>';
        }

        if (dayEvents.length > 1) {
            // Multi-event popup
            var itemsHtml = dayEvents.map(function(item) {
                var itemTime = buildTimeStr(item);
                var itemStatus = '';
                if (item.cancelled) {
                    itemStatus = '<span class="event-o-cal-popup-badge cancelled">Abgesagt</span>';
                } else if (item.soldOut) {
                    itemStatus = '<span class="event-o-cal-popup-badge sold-out">Ausgebucht</span>';
                }
                var itemCat = '';
                if (item.category) {
                    var iCatStyle = item.categoryColor ? ' style="--cat-color:' + calEscAttr(item.categoryColor) + '"' : '';
                    itemCat = '<span class="event-o-cal-popup-item-cat"' + iCatStyle + '>' + calEscHtml(item.category) + '</span>';
                }
                return '<a href="' + calEscAttr(item.url || '#') + '" class="event-o-cal-popup-item">'
                    + '<div class="event-o-cal-popup-item-title">' + calEscHtml(item.title) + ' <span class="event-o-cal-popup-arrow">→</span></div>'
                    + (itemTime ? '<div class="event-o-cal-popup-item-time">' + calEscHtml(itemTime) + '</div>' : '')
                    + itemCat
                    + itemStatus
                    + '</a>';
            }).join('');

            popup.innerHTML = bgHtml
                + '<div class="event-o-cal-popup-multi">'
                + '<div class="event-o-cal-popup-multi-head">' + dayEvents.length + ' Events an diesem Tag</div>'
                + itemsHtml
                + '</div>';
        } else {
            popup.innerHTML =
                bgHtml +
                '<a href="' + calEscAttr(ev.url || '#') + '" class="event-o-cal-popup-link">' +
                    '<div class="event-o-cal-popup-title">' +
                        calEscHtml(ev.title) +
                        ' <span class="event-o-cal-popup-arrow">→</span>' +
                    '</div>' +
                    (timeStr ? '<div class="event-o-cal-popup-time">' + calEscHtml(timeStr) + '</div>' : '') +
                    categoryHtml +
                    venueHtml +
                    excerptHtml +
                    statusHtml +
                '</a>';
        }

        /* Positioning */
        if (mobile) {
            popup.classList.add('is-mobile');
            popup.classList.remove('is-desktop');

            var cellW = cell.offsetWidth;
            var cellH = cell.offsetHeight;
            var gridGapM = parseFloat(window.getComputedStyle(grid).columnGap || window.getComputedStyle(grid).gap || '0') || 0;
            var pw = 3 * cellW + 2 * gridGapM;
            var ph = 2 * cellH + gridGapM;
            var topPos = cell.offsetTop + cellH + gridGapM;
            var leftPos = cell.offsetLeft + cellW / 2 - pw / 2;

            if (leftPos < 4) leftPos = 4;
            if (leftPos + pw > grid.offsetWidth - 4) leftPos = grid.offsetWidth - pw - 4;
            if (topPos + ph > grid.offsetHeight) {
                topPos = Math.max(0, cell.offsetTop - ph - gridGapM);
            }

            var arrowLeft = (cell.offsetLeft + cellW / 2) - leftPos;
            popup.style.setProperty('--arrow-left', arrowLeft + 'px');

            popup.style.left = leftPos + 'px';
            popup.style.top = topPos + 'px';
            popup.style.width = pw + 'px';
            popup.style.height = ph + 'px';
        } else {
            popup.classList.add('is-desktop');
            popup.classList.remove('is-mobile');

            var cw = cell.offsetWidth;
            var ch = cell.offsetHeight;
            var gridGap = parseFloat(window.getComputedStyle(grid).columnGap || window.getComputedStyle(grid).gap || '0') || 0;
            var spanW = 2 * cw + gridGap;
            var spanH = 2 * ch + gridGap;
            var left;

            if (col <= 4) {
                left = cell.offsetLeft + cw + gridGap;
            } else {
                left = cell.offsetLeft - spanW - gridGap;
            }
            if (left < 0) left = 0;
            if (left + spanW > grid.offsetWidth) left = grid.offsetWidth - spanW;

            var topPosDesktop = cell.offsetTop;
            if (topPosDesktop + spanH > grid.offsetHeight) {
                topPosDesktop = Math.max(0, grid.offsetHeight - spanH);
            }

            popup.style.left = left + 'px';
            popup.style.top = topPosDesktop + 'px';
            popup.style.width = spanW + 'px';
            popup.style.height = spanH + 'px';
        }

        popup.style.display = 'flex';
        requestAnimationFrame(function() {
            popup.classList.add('is-visible');
        });
    }

    function calHidePopup(popup, state) {
        popup.classList.remove('is-visible');
        setTimeout(function() {
            popup.style.display = 'none';
            popup.classList.remove('is-mobile', 'is-desktop');
        }, 200);
        state.activeEl = null;
        state.activeCell = null;
    }

    /* Expose for editor use */
    window.eventOCalendarInit = initSingleCalendar;

    function boot() {
        var carousels = document.querySelectorAll('.event-o-carousel');
        carousels.forEach(initCarousel);

        initCopyButtons();
        initCalendarDropdowns();
        initGridSliders();
        initHeroSliders();
        initEventImageCrossfades();
        initAccordionAnimations();
        initFilters();
        initProgramLoadMore();
        initDescToggle();
        initHeroParallax();
        initSingleAnimations();
        initBlockAnimations();
        initEventCalendars();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})();
