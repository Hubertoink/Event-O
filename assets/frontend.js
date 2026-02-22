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
                });
            });
        });
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
                var duration = 400;
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
        initSingleAnimations();
        initBlockAnimations();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})();
