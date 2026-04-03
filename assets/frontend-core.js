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

    function updateGridExcerptLayout(scope) {
        var root = scope || document;
        var grids = root.classList && root.classList.contains('event-o-grid') ? [root] : root.querySelectorAll('.event-o-grid');

        Array.prototype.forEach.call(grids, function (grid) {
            var cards = grid.querySelectorAll('.event-o-grid-card');

            cards.forEach(function (card) {
                var media = card.querySelector('.event-o-grid-media');
                var overlay = card.querySelector('.event-o-grid-overlay');
                var excerpt = card.querySelector('.event-o-grid-excerpt');

                if (!media || !overlay || !excerpt) return;

                var overlayStyle = window.getComputedStyle(overlay);
                var excerptStyle = window.getComputedStyle(excerpt);
                var paddingTop = parseFloat(overlayStyle.paddingTop || '0') || 0;
                var paddingBottom = parseFloat(overlayStyle.paddingBottom || '0') || 0;
                var lineHeight = parseFloat(excerptStyle.lineHeight || '0') || ((parseFloat(excerptStyle.fontSize || '14') || 14) * 1.5);
                var availableHeight = media.clientHeight - paddingTop - paddingBottom;
                var maxLines = Math.max(1, Math.floor((availableHeight - 6) / lineHeight));

                excerpt.style.webkitLineClamp = String(Math.min(maxLines, 8));
                excerpt.style.maxHeight = Math.max(lineHeight, Math.min(maxLines, 8) * lineHeight) + 'px';
            });
        });
    }

    function initGridExcerptLayout() {
        updateGridExcerptLayout(document);

        var resizeTimer = 0;
        window.addEventListener('resize', function () {
            window.clearTimeout(resizeTimer);
            resizeTimer = window.setTimeout(function () {
                updateGridExcerptLayout(document);
            }, 80);
        }, { passive: true });

        window.addEventListener('load', function () {
            updateGridExcerptLayout(document);
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

    function updateListHeadings(block) {
        var monthHeaders = block.querySelectorAll('.event-o-month');

        monthHeaders.forEach(function (header) {
            var nextEl = header.nextElementSibling;
            var hasVisible = false;

            while (nextEl && !nextEl.classList.contains('event-o-month') && !nextEl.classList.contains('event-o-past-heading')) {
                if (nextEl.classList.contains('event-o-accordion-item') && nextEl.style.display !== 'none' && !nextEl.classList.contains('is-hidden')) {
                    hasVisible = true;
                    break;
                }
                nextEl = nextEl.nextElementSibling;
            }

            header.style.display = (header.classList.contains('is-hidden') || !hasVisible) ? 'none' : '';
        });

        var pastHeadings = block.querySelectorAll('.event-o-past-heading');

        pastHeadings.forEach(function (header) {
            var nextEl = header.nextElementSibling;
            var hasVisible = false;

            while (nextEl) {
                if (nextEl.classList.contains('event-o-accordion-item') && nextEl.style.display !== 'none' && !nextEl.classList.contains('is-hidden')) {
                    hasVisible = true;
                    break;
                }
                nextEl = nextEl.nextElementSibling;
            }

            header.style.display = (header.classList.contains('is-hidden') || !hasVisible) ? 'none' : '';
        });
    }

    function syncListLoadMoreButton(block) {
        var wrap = block.querySelector('.event-o-list-loadmore-wrap');
        if (!wrap) return;

        var hiddenTargets = block.querySelectorAll('.event-o-list-loadmore-target.is-hidden');
        wrap.style.display = hiddenTargets.length ? '' : 'none';
    }

    function initListLoadMore() {
        var lists = document.querySelectorAll('.event-o-event-list[data-load-more="1"]');

        lists.forEach(function (block) {
            if (block.getAttribute('data-event-o-list-loadmore-init') === '1') {
                updateListHeadings(block);
                syncListLoadMoreButton(block);
                return;
            }

            var btn = block.querySelector('.event-o-list-loadmore');
            var step = parseInt(block.getAttribute('data-load-more-step') || '0', 10);

            if (!btn || !step) {
                return;
            }

            btn.addEventListener('click', function () {
                var hiddenTargets = block.querySelectorAll('.event-o-list-loadmore-target.is-hidden');
                var revealedEvents = 0;

                for (var i = 0; i < hiddenTargets.length; i++) {
                    hiddenTargets[i].classList.remove('is-hidden');
                    if (hiddenTargets[i].classList.contains('event-o-accordion-item')) {
                        revealedEvents++;
                    }
                    if (revealedEvents >= step) {
                        break;
                    }
                }

                updateListHeadings(block);
                syncListLoadMoreButton(block);
            });

            block.setAttribute('data-event-o-list-loadmore-init', '1');
            updateListHeadings(block);
            syncListLoadMoreButton(block);
        });
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

        var hasActiveFilters = Object.keys(activeFilters).length > 0;
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

            if ((isProgram || isListBlock) && !item.hasAttribute('data-was-hidden')) {
                item.setAttribute('data-was-hidden', item.classList.contains('is-hidden') ? '1' : '0');
            }

            if (isProgram || isListBlock) {
                if (hasActiveFilters) {
                    item.classList.remove('is-hidden');
                    item.style.display = show ? '' : 'none';
                } else {
                    item.style.display = '';
                    if (item.getAttribute('data-was-hidden') === '1') {
                        item.classList.add('is-hidden');
                    } else {
                        item.classList.remove('is-hidden');
                    }
                }
            } else {
                item.style.display = show ? '' : 'none';
            }

            if (show && !((isProgram || isListBlock) && !hasActiveFilters && item.getAttribute('data-was-hidden') === '1')) {
                visibleCount++;
            }
        });

        if (isListBlock) {
            var headers = block.querySelectorAll('.event-o-month, .event-o-past-heading');
            headers.forEach(function (header) {
                if (!header.hasAttribute('data-was-hidden')) {
                    header.setAttribute('data-was-hidden', header.classList.contains('is-hidden') ? '1' : '0');
                }

                if (hasActiveFilters) {
                    header.classList.remove('is-hidden');
                } else if (header.getAttribute('data-was-hidden') === '1') {
                    header.classList.add('is-hidden');
                } else {
                    header.classList.remove('is-hidden');
                }
            });

            updateListHeadings(block);

            var listLoadMoreWrap = block.querySelector('.event-o-list-loadmore-wrap');
            if (listLoadMoreWrap) {
                listLoadMoreWrap.style.display = hasActiveFilters ? 'none' : '';
                if (!hasActiveFilters) {
                    syncListLoadMoreButton(block);
                }
            }
        }

        if (isGrid) {
            var dots = block.querySelectorAll('.event-o-grid-dot');
            var visibleCards = block.querySelectorAll('.event-o-grid-card:not([style*="display: none"])');
            dots.forEach(function (dot, i) {
                dot.style.display = i < visibleCards.length ? '' : 'none';
                dot.classList.toggle('is-active', i === 0);
            });

            updateGridExcerptLayout(block);
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
            var loadMoreWrap = block.querySelector('.event-o-program-loadmore-wrap');
            if (loadMoreWrap) {
                loadMoreWrap.style.display = hasActiveFilters ? 'none' : '';
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

    function initImageLightboxes() {
        var frontendSettings = window.EventOFrontendSettings || {};
        var single = document.querySelector('.event-o-single');
        var listImages = document.querySelectorAll('.event-o-featured-image-wrap');
        var programImages = document.querySelectorAll('.event-o-program-image');

        if ((!single || frontendSettings.singleLightboxEnabled === false) && !listImages.length && !programImages.length) {
            return;
        }

        function ensureLightbox() {
            if (window.EventOImageLightbox) {
                return window.EventOImageLightbox;
            }

            var body = document.body;
            if (!body) return null;

            var overlay = document.createElement('div');
            overlay.className = 'event-o-lightbox';
            overlay.setAttribute('hidden', 'hidden');
            overlay.innerHTML = [
                '<div class="event-o-lightbox-backdrop" data-lightbox-close="1"></div>',
                '<div class="event-o-lightbox-dialog" role="dialog" aria-modal="true" aria-label="Bildansicht" tabindex="-1">',
                '<button type="button" class="event-o-lightbox-nav event-o-lightbox-prev" aria-label="Vorheriges Bild">',
                '<span aria-hidden="true">&lsaquo;</span>',
                '</button>',
                '<figure class="event-o-lightbox-figure">',
                '<img class="event-o-lightbox-image" alt="">',
                '<figcaption class="event-o-lightbox-caption"></figcaption>',
                '</figure>',
                '<button type="button" class="event-o-lightbox-nav event-o-lightbox-next" aria-label="Naechstes Bild">',
                '<span aria-hidden="true">&rsaquo;</span>',
                '</button>',
                '</div>'
            ].join('');
            body.appendChild(overlay);

            var dialog = overlay.querySelector('.event-o-lightbox-dialog');
            var image = overlay.querySelector('.event-o-lightbox-image');
            var caption = overlay.querySelector('.event-o-lightbox-caption');
            var prevButton = overlay.querySelector('.event-o-lightbox-prev');
            var nextButton = overlay.querySelector('.event-o-lightbox-next');
            var currentGroup = [];
            var currentIndex = 0;
            var previousActiveElement = null;

            function isLikelyImageUrl(url) {
                return /\.(apng|avif|gif|jpe?g|png|svg|webp)(\?.*)?$/i.test(url || '');
            }

            function normalizeItem(item) {
                if (!item || !item.src) return null;

                return {
                    src: item.src,
                    alt: item.alt || '',
                    caption: item.caption || item.alt || ''
                };
            }

            function updateView() {
                var item = currentGroup[currentIndex];
                if (!item) return;

                image.src = item.src;
                image.alt = item.alt || '';
                caption.textContent = item.caption || '';
                caption.hidden = caption.textContent === '';

                var hasMultiple = currentGroup.length > 1;
                prevButton.hidden = !hasMultiple;
                nextButton.hidden = !hasMultiple;
            }

            function openLightbox(group, index) {
                currentGroup = group.map(normalizeItem).filter(Boolean);
                if (!currentGroup.length) return;

                currentIndex = Math.max(0, Math.min(index || 0, currentGroup.length - 1));
                previousActiveElement = document.activeElement;
                updateView();

                overlay.hidden = false;
                body.classList.add('event-o-lightbox-open');
                window.requestAnimationFrame(function () {
                    overlay.classList.add('is-open');
                    dialog.focus();
                });
            }

            function closeLightbox() {
                overlay.classList.remove('is-open');
                overlay.hidden = true;
                body.classList.remove('event-o-lightbox-open');
                image.removeAttribute('src');

                if (previousActiveElement && typeof previousActiveElement.focus === 'function') {
                    previousActiveElement.focus();
                }
            }

            function showRelative(step) {
                if (currentGroup.length < 2) return;
                currentIndex = (currentIndex + step + currentGroup.length) % currentGroup.length;
                updateView();
            }

            function bindTrigger(trigger, getGroup, getIndex) {
                if (!trigger || trigger.getAttribute('data-event-o-lightbox-bound') === '1') return;

                trigger.setAttribute('data-event-o-lightbox-bound', '1');
                trigger.classList.add('event-o-lightbox-trigger');

                trigger.addEventListener('click', function (event) {
                    var interactiveTarget = event.target.closest('a, button');
                    if (interactiveTarget && interactiveTarget !== trigger) return;
                    event.preventDefault();
                    openLightbox(getGroup(), getIndex());
                });

                trigger.addEventListener('keydown', function (event) {
                    if (event.key !== 'Enter' && event.key !== ' ') return;
                    event.preventDefault();
                    openLightbox(getGroup(), getIndex());
                });
            }

            function createExpandIcon(className) {
                var expandIcon = document.createElement('span');
                expandIcon.className = className;
                expandIcon.setAttribute('aria-hidden', 'true');
                expandIcon.innerHTML = '<svg viewBox="0 0 24 24" fill="currentColor" width="28" height="28"><path d="M21 3h-6v2h3.59L13 10.59 14.41 12 20 6.41V10h2V3zM3 21h6v-2H5.41L11 13.41 9.59 12 4 17.59V14H2v7z"/></svg>';
                return expandIcon;
            }

            overlay.addEventListener('click', function (event) {
                if (event.target.closest('.event-o-lightbox-nav')) return;
                if (event.target.closest('.event-o-lightbox-image')) {
                    closeLightbox();
                    return;
                }
                if (!event.target.closest('.event-o-lightbox-figure')) {
                    closeLightbox();
                }
            });

            prevButton.addEventListener('click', function () {
                showRelative(-1);
            });

            nextButton.addEventListener('click', function () {
                showRelative(1);
            });

            document.addEventListener('keydown', function (event) {
                if (overlay.hidden) return;

                if (event.key === 'Escape') {
                    event.preventDefault();
                    closeLightbox();
                    return;
                }

                if (event.key === 'ArrowLeft') {
                    event.preventDefault();
                    showRelative(-1);
                }

                if (event.key === 'ArrowRight') {
                    event.preventDefault();
                    showRelative(1);
                }
            });

            window.EventOImageLightbox = {
                bindTrigger: bindTrigger,
                createExpandIcon: createExpandIcon,
                isLikelyImageUrl: isLikelyImageUrl
            };

            return window.EventOImageLightbox;
        }

        function collectImageItems(container) {
            if (!container) return [];

            return Array.prototype.map.call(container.querySelectorAll('img'), function (img) {
                return {
                    src: img.currentSrc || img.getAttribute('src') || '',
                    alt: img.getAttribute('alt') || '',
                    caption: img.getAttribute('alt') || ''
                };
            }).filter(function (item) {
                return item.src !== '';
            });
        }

        function getActiveIndex(container) {
            if (!container) return 0;

            var slides = Array.prototype.slice.call(container.querySelectorAll('img'));
            var activeIndex = slides.findIndex(function (img) {
                return img.classList.contains('is-active');
            });

            return activeIndex >= 0 ? activeIndex : 0;
        }

        function appendExpandIcon(host, className) {
            if (!host || host.querySelector('.event-o-image-lightbox-expand, .event-o-single-hero-expand')) {
                return;
            }

            host.appendChild(lightbox.createExpandIcon(className));
        }

        var lightbox = ensureLightbox();
        if (!lightbox) return;

        if (single && single.getAttribute('data-event-o-lightbox-init') !== '1' && frontendSettings.singleLightboxEnabled !== false) {
            var hero = single.querySelector('.event-o-single-hero');
            if (hero) {
                var heroImages = Array.prototype.slice.call(hero.querySelectorAll('.event-o-single-hero-img'));
                var heroItems = heroImages.map(function (img) {
                    return {
                        src: img.currentSrc || img.getAttribute('src') || '',
                        alt: img.getAttribute('alt') || '',
                        caption: img.getAttribute('alt') || ''
                    };
                }).filter(function (item) {
                    return item.src !== '';
                });

                if (heroItems.length) {
                    hero.setAttribute('tabindex', '0');
                    hero.setAttribute('role', 'button');
                    hero.setAttribute('aria-label', 'Bild in Vollansicht oeffnen');
                    hero.classList.add('event-o-single-hero-lightbox');
                    appendExpandIcon(hero, 'event-o-single-hero-expand event-o-image-lightbox-expand');

                    lightbox.bindTrigger(hero, function () {
                        return heroItems;
                    }, function () {
                        var activeIndex = heroImages.findIndex(function (img) {
                            return img.classList.contains('is-active');
                        });
                        return activeIndex >= 0 ? activeIndex : 0;
                    });
                }
            }

            Array.prototype.forEach.call(single.querySelectorAll('.event-o-content img'), function (img) {
                var src = img.currentSrc || img.getAttribute('src') || '';
                if (!src) return;

                var parentLink = img.closest('a[href]');
                if (parentLink) {
                    var href = parentLink.getAttribute('href') || '';
                    if (href && !lightbox.isLikelyImageUrl(href) && href !== src) {
                        return;
                    }
                }

                img.setAttribute('tabindex', '0');
                img.setAttribute('role', 'button');
                img.setAttribute('aria-label', 'Bild in Vollansicht oeffnen');

                lightbox.bindTrigger(img, function () {
                    return [{
                        src: parentLink && lightbox.isLikelyImageUrl(parentLink.getAttribute('href') || '') ? parentLink.getAttribute('href') : src,
                        alt: img.getAttribute('alt') || '',
                        caption: img.getAttribute('alt') || ''
                    }];
                }, function () {
                    return 0;
                });
            });

            single.setAttribute('data-event-o-lightbox-init', '1');
        }

        Array.prototype.forEach.call(listImages, function (wrap) {
            if (!wrap || wrap.getAttribute('data-event-o-lightbox-init') === '1') return;

            var imageGroup = wrap.querySelector('.event-o-featured-image');
            var items = collectImageItems(imageGroup);
            if (!items.length) return;

            wrap.classList.add('event-o-image-lightbox-host');
            wrap.setAttribute('tabindex', '0');
            wrap.setAttribute('role', 'button');
            wrap.setAttribute('aria-label', 'Bild in Vollansicht oeffnen');
            appendExpandIcon(wrap, 'event-o-image-lightbox-expand');

            lightbox.bindTrigger(wrap, function () {
                return items;
            }, function () {
                return getActiveIndex(imageGroup);
            });

            wrap.setAttribute('data-event-o-lightbox-init', '1');
        });

        Array.prototype.forEach.call(programImages, function (wrap) {
            if (!wrap || wrap.getAttribute('data-event-o-lightbox-init') === '1') return;

            var imageGroup = wrap.querySelector('.event-o-program-image-fade, .event-o-crossfade');
            var items = collectImageItems(imageGroup);
            if (!items.length) return;

            var trigger = wrap.querySelector('a[href]') || wrap;

            wrap.classList.add('event-o-image-lightbox-host');
            appendExpandIcon(wrap, 'event-o-image-lightbox-expand');

            if (trigger === wrap) {
                wrap.setAttribute('tabindex', '0');
                wrap.setAttribute('role', 'button');
            }
            trigger.setAttribute('aria-label', 'Bild in Vollansicht oeffnen');

            lightbox.bindTrigger(trigger, function () {
                return items;
            }, function () {
                return getActiveIndex(imageGroup);
            });

            wrap.setAttribute('data-event-o-lightbox-init', '1');
        });
    }

    frontend.registerInit(function initCoreFrontendFeatures() {
        initCopyButtons();
        initCalendarDropdowns();
        initGridSliders();
        initGridExcerptLayout();
        initAccordionAnimations();
        initFilters();
        initListLoadMore();
        initImageLightboxes();
    });
})();
