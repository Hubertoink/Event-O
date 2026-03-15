(function () {
    var frontend = window.EventOFrontend = window.EventOFrontend || {};

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
            var calendarBtn = e.target.closest('.event-o-share-calendar');
            if (calendarBtn) {
                var dropdown = calendarBtn.closest('.event-o-calendar-dropdown');
                if (dropdown) {
                    document.querySelectorAll('.event-o-calendar-dropdown.is-open').forEach(function (d) {
                        if (d !== dropdown) d.classList.remove('is-open');
                    });
                    dropdown.classList.toggle('is-open');
                    e.preventDefault();
                    e.stopPropagation();
                }
                return;
            }

            if (!e.target.closest('.event-o-calendar-dropdown')) {
                document.querySelectorAll('.event-o-calendar-dropdown.is-open').forEach(function (d) {
                    d.classList.remove('is-open');
                });
            }
        });

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

            dots.forEach(function (dot) {
                dot.addEventListener('click', function () {
                    var index = parseInt(dot.getAttribute('data-index'), 10);
                    var cards = track.querySelectorAll('.event-o-grid-card');
                    if (cards[index]) {
                        cards[index].scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'start' });
                    }
                });
            });

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
        var animating = new WeakSet();
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

            p.style.gridTemplateRows = '1fr';
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
                    animateClose(details);
                } else {
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

                    details.open = true;
                    animating.add(details);
                    panel.style.gridTemplateRows = '0fr';
                    void panel.offsetHeight;
                    panel.style.gridTemplateRows = '1fr';

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
            selects.forEach(function (select) {
                select.addEventListener('change', function () {
                    applyFilters(block);
                });
            });

            var tabs = block.querySelectorAll('.event-o-filter-tab');
            tabs.forEach(function (tab) {
                tab.addEventListener('click', function () {
                    var group = tab.closest('.event-o-filter-tab-group');
                    if (group) {
                        group.querySelectorAll('.event-o-filter-tab').forEach(function (t) {
                            t.classList.remove('is-active');
                        });
                    }
                    tab.classList.add('is-active');
                    applyFilters(block);
                    updateMobileFilterIndicator(block);
                });
            });

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

        var selects = block.querySelectorAll('.event-o-filter-select');
        selects.forEach(function (select) {
            var filterType = select.getAttribute('data-filter');
            var value = select.value;
            if (value) {
                activeFilters[filterType] = value;
            }
        });

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

            if (isProgram && !item.hasAttribute('data-was-hidden')) {
                item.setAttribute('data-was-hidden', item.classList.contains('is-hidden') ? '1' : '0');
            }

            if (isProgram) {
                var hasActiveFilter = Object.keys(activeFilters).length > 0;
                if (hasActiveFilter) {
                    item.classList.remove('is-hidden');
                    item.style.display = show ? '' : 'none';
                } else {
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

        if (isGrid) {
            var dots = block.querySelectorAll('.event-o-grid-dot');
            var visibleCards = block.querySelectorAll('.event-o-grid-card:not([style*="display: none"])');
            dots.forEach(function (dot, i) {
                dot.style.display = i < visibleCards.length ? '' : 'none';
                dot.classList.toggle('is-active', i === 0);
            });
        }

        if (isHero) {
            var heroDots = block.querySelectorAll('.event-o-hero-dot');
            var visibleSlides = block.querySelectorAll('.event-o-hero-slide:not([style*="display: none"])');
            heroDots.forEach(function (dot, i) {
                dot.style.display = i < visibleSlides.length ? '' : 'none';
                dot.classList.toggle('is-active', i === 0);
            });
        }

        if (isProgram) {
            var hasActiveProgramFilter = Object.keys(activeFilters).length > 0;
            var loadMoreWrap = block.querySelector('.event-o-program-loadmore-wrap');
            if (loadMoreWrap) {
                loadMoreWrap.style.display = hasActiveProgramFilter ? 'none' : '';
            }
        }

        var emptyMsg = block.querySelector('.event-o-filter-empty');
        if (visibleCount === 0) {
            if (!emptyMsg) {
                emptyMsg = document.createElement('p');
                emptyMsg.className = 'event-o-filter-empty';
                emptyMsg.textContent = 'Keine Veranstaltungen gefunden.';
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

    frontend.registerInit(function initCoreFrontendFeatures() {
        initCopyButtons();
        initCalendarDropdowns();
        initGridSliders();
        initAccordionAnimations();
        initFilters();
    });
})();
