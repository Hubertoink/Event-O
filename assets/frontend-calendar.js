(function () {
    var frontend = window.EventOFrontend = window.EventOFrontend || {};

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

    function calGetCategoryColors(item) {
        if (!item || !Array.isArray(item.categories)) return [];

        return item.categories
            .map(function (category) {
                return category && category.color ? String(category.color).trim() : '';
            })
            .filter(function (color, index, colors) {
                return color !== '' && colors.indexOf(color) === index;
            });
    }

    function calBuildCategoryGradient(item) {
        var colors = calGetCategoryColors(item);
        if (!colors.length) return item && item.categoryColor ? item.categoryColor : '';
        if (colors.length === 1) return colors[0];

        return 'linear-gradient(135deg, ' + colors.map(function (color, index) {
            var position = Math.round((index / (colors.length - 1)) * 100);
            return color + ' ' + position + '%';
        }).join(', ') + ')';
    }

    function calRenderCategoryLabels(item, itemClass, separatorClass) {
        if (!item || !Array.isArray(item.categories) || !item.categories.length) return '';

        return item.categories.map(function (category, index) {
            if (!category || !category.name) return '';

            var label = '<span class="' + itemClass + '"' + (category.color ? ' style="color:' + calEscAttr(category.color) + '"' : '') + '>' + calEscHtml(category.name) + '</span>';
            if (index === 0) return label;
            return '<span class="' + separatorClass + '"> / </span>' + label;
        }).join('');
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
        var desktopPopupMatrix = wrap.getAttribute('data-desktop-popup-matrix') || '3x3';
        var events = [];
        try { events = JSON.parse(raw) || []; } catch(e) { events = []; }

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
            desktopPopupMatrix: (desktopPopupMatrix === '3x2' ? '3x2' : '3x3'),
            subscribeUrl: wrap.getAttribute('data-subscribe-url') || '',
            eventsMap: eventsMap,
            popupTimer: null,
            hideAnimationTimer: null,
            activeEl: null
        };

        calRender(wrap, state);
    }

    function calRender(wrap, state) {
        if (wrap._bodyPopup && wrap._bodyPopup.parentNode) {
            wrap._bodyPopup.parentNode.removeChild(wrap._bodyPopup);
            wrap._bodyPopup = null;
        }

        wrap.innerHTML = '';

        var header = document.createElement('div');
        header.className = 'event-o-cal-header';

        var titleNav = document.createElement('div');
        titleNav.className = 'event-o-cal-title-nav';

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

        titleNav.appendChild(prevBtn);
        titleNav.appendChild(label);
        titleNav.appendChild(nextBtn);
        header.appendChild(titleNav);

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

        wrap.appendChild(header);

        var grid = document.createElement('div');
        grid.className = 'event-o-cal-grid';

        var days = state.weekStart === 1 ? CAL_DAYS_MON : CAL_DAYS_SUN;
        days.forEach(function(d) {
            var dow = document.createElement('div');
            dow.className = 'event-o-cal-dow';
            dow.textContent = d;
            grid.appendChild(dow);
        });

        var firstDay = new Date(state.year, state.month, 1);
        var daysInMonth = new Date(state.year, state.month + 1, 0).getDate();
        var startDow = firstDay.getDay();
        if (state.weekStart === 1) startDow = (startDow + 6) % 7;

        var today = calTodayStr();

        for (var e = 0; e < startDow; e++) {
            var emptyCell = document.createElement('div');
            emptyCell.className = 'event-o-cal-day event-o-cal-day-empty';
            emptyCell.dataset.col = String(e);
            grid.appendChild(emptyCell);
        }

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
                var maxVisibleDayEvents = 2;
                var visibleDayEvents = dayEvents.slice(0, maxVisibleDayEvents);
                var hiddenDayEventsCount = Math.max(0, dayEvents.length - visibleDayEvents.length);

                cell.classList.add('has-events');
                if (dayEvents.length === 2) cell.classList.add('event-count-2');
                if (dayEvents.length >= 3) cell.classList.add('event-count-3plus');
                cell._dayEvents = dayEvents;

                var dots = document.createElement('div');
                dots.className = 'event-o-cal-day-dots';
                dayEvents.forEach(function (ev) {
                    var dot = document.createElement('span');
                    dot.className = 'event-o-cal-day-dot';
                    var dotGradient = calBuildCategoryGradient(ev);
                    if (dotGradient) dot.style.background = dotGradient;
                    dots.appendChild(dot);
                });
                cell.appendChild(dots);

                var eventsWrap = document.createElement('div');
                eventsWrap.className = 'event-o-cal-day-events';

                visibleDayEvents.forEach(function (ev) {
                    var el = document.createElement('div');
                    el.className = 'event-o-cal-event';
                    if (ev.cancelled) el.classList.add('is-cancelled');
                    if (ev.soldOut) el.classList.add('is-sold-out');
                    var eventGradient = calBuildCategoryGradient(ev);
                    if (eventGradient) el.style.background = eventGradient;
                    el.dataset.eventId = ev.id;

                    var html = '';
                    if (ev.time) {
                        html += '<span class="event-o-cal-event-time">' + calEscHtml(ev.time) + '</span>';
                    }
                    html += '<span class="event-o-cal-event-title">' + calEscHtml(ev.title) + '</span>';
                    el.innerHTML = html;

                    el._eventData = ev;
                    el._dayEvents = dayEvents;
                    eventsWrap.appendChild(el);
                });

                if (hiddenDayEventsCount > 0) {
                    var moreEl = document.createElement('div');
                    moreEl.className = 'event-o-cal-more';
                    moreEl.textContent = '+' + hiddenDayEventsCount;
                    moreEl.title = hiddenDayEventsCount + ' weitere Events';
                    eventsWrap.appendChild(moreEl);
                }

                cell.appendChild(eventsWrap);
            }

            grid.appendChild(cell);
        }

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

        var popup = document.createElement('div');
        popup.className = 'event-o-cal-popup';
        popup.style.display = 'none';
        popup._ownerWrap = wrap;
        grid.appendChild(popup);

        wrap.appendChild(grid);

        popup._popupBlur = state.popupBlur;

        calAttachListeners(wrap, grid, popup, state);
    }

    function calAttachListeners(wrap, grid, popup, state) {
        var mobile = calIsMobile();

        if (wrap._calHandlers) {
            if (wrap._calHandlers.wrapClick) {
                wrap.removeEventListener('click', wrap._calHandlers.wrapClick);
            }
            if (wrap._calHandlers.documentClick) {
                document.removeEventListener('click', wrap._calHandlers.documentClick);
            }
            if (wrap._calHandlers.windowScroll) {
                window.removeEventListener('scroll', wrap._calHandlers.windowScroll);
            }
            if (wrap._calHandlers.documentKeydown) {
                document.removeEventListener('keydown', wrap._calHandlers.documentKeydown);
            }
        }

        wrap._calHandlers = {};

        if (mobile) {
            var handleWrapClick = function(e) {
                var dayEl = e.target.closest('.event-o-cal-day.has-events');
                var popupLink = e.target.closest('.event-o-cal-popup-link');
                var popupItem = e.target.closest('.event-o-cal-popup-item');

                if (dayEl && !e.target.closest('.event-o-cal-popup')) {
                    e.preventDefault();
                    e.stopPropagation();

                    if (state.activeEl === dayEl && popup.style.display !== 'none') {
                        calHidePopup(popup, state);
                        return;
                    }

                    calShowPopup(grid, popup, dayEl, dayEl._dayEvents || [], true, null, state);
                    state.activeEl = dayEl;
                    return;
                }
                if (popupLink || popupItem) return;

                calHidePopup(popup, state);
            };
            wrap.addEventListener('click', handleWrapClick);
            wrap._calHandlers.wrapClick = handleWrapClick;

            var handleDocumentClick = function(e) {
                if (!e.target.closest('.event-o-cal-wrap')) {
                    calHidePopup(popup, state);
                }
            };
            document.addEventListener('click', handleDocumentClick);
            wrap._calHandlers.documentClick = handleDocumentClick;

            var handleWindowScroll = function() {
                if (popup.style.display !== 'none') {
                    calHidePopup(popup, state);
                }
            };
            window.addEventListener('scroll', handleWindowScroll, { passive: true });
            wrap._calHandlers.windowScroll = handleWindowScroll;
        } else {
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
                        if (popup.style.display === 'flex' && state.activeCell === cell) {
                            return;
                        }
                        state.showTimer = setTimeout(function() {
                            calShowPopup(grid, popup, cell, dayEvts, false, null, state);
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

                    cell.addEventListener('focusin', function() {
                        clearTimeout(state.popupTimer);
                        clearTimeout(state.showTimer);
                        var dayEvts = cell._dayEvents || [];
                        if (!dayEvts.length) return;
                        calShowPopup(grid, popup, cell, dayEvts, false, null, state);
                        state.activeCell = cell;
                    });
                })(dayCells[ci]);
            }

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
                        calShowPopup(grid, popup, cell || eventEl, dayEvts, false, activeId, state);
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
                    if (!state.hoverCell) {
                        calHidePopup(popup, state);
                        state.activeCell = null;
                    }
                }, HOVER_DELAY);
            });
        }

        var handleDocumentKeydown = function(e) {
            if (e.key === 'Escape') calHidePopup(popup, state);
        };
        document.addEventListener('keydown', handleDocumentKeydown);
        wrap._calHandlers.documentKeydown = handleDocumentKeydown;
    }

    function calShowPopup(grid, popup, sourceEl, dayEvents, mobile, activeEventId, state) {
        if (!dayEvents || !dayEvents.length) return;

        if (popup._hideAnimationTimer) {
            clearTimeout(popup._hideAnimationTimer);
            popup._hideAnimationTimer = null;
        }

        popup.style.display = 'none';

        var cell = sourceEl.classList && sourceEl.classList.contains('event-o-cal-day')
            ? sourceEl
            : sourceEl.closest('.event-o-cal-day');
        if (!cell) return;

        var ev = dayEvents[0];
        if (activeEventId) {
            for (var k = 0; k < dayEvents.length; k++) {
                if (String(dayEvents[k].id) === String(activeEventId)) {
                    ev = dayEvents[k];
                    break;
                }
            }
        }

        function buildTimeStr(item) {
            var parts = [];
            if (item.beginTime) {
                if (item.time) parts.push('Einlass ' + item.time + ' Uhr');
                parts.push('Beginn ' + item.beginTime + ' Uhr');
                return parts.join(' · ');
            }
            if (item.time && item.timeEnd) return item.time + ' – ' + item.timeEnd + ' Uhr';
            if (item.time) return 'ab ' + item.time + ' Uhr';
            if (item.timeEnd) return 'bis ' + item.timeEnd + ' Uhr';
            return '';
        }
        var timeStr = buildTimeStr(ev);

        var statusHtml = '';
        if (ev.cancelled) {
            statusHtml = '<span class="event-o-cal-popup-badge cancelled">' + calEscHtml(ev.statusLabel || 'Abgesagt') + '</span>';
        } else if (ev.soldOut) {
            statusHtml = '<span class="event-o-cal-popup-badge sold-out">' + calEscHtml(ev.statusLabel || 'Ausverkauft') + '</span>';
        }

        var categoryHtml = '';
        if (ev.category) {
            var categoryLabels = calRenderCategoryLabels(ev, 'event-o-cal-popup-category-item', 'event-o-cal-popup-category-sep');
            categoryHtml = '<div class="event-o-cal-popup-category">' + (categoryLabels || calEscHtml(ev.category)) + '</div>';
        }

        var venueHtml = '';
        if (ev.venue) {
            venueHtml = '<div class="event-o-cal-popup-venue"><svg class="event-o-icon" viewBox="0 0 24 24" fill="currentColor" width="14" height="14"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg> ' + calEscHtml(ev.venue) + '</div>';
        }

        var bgHtml = '';
        if (ev.image) {
            popup.classList.add('has-image');
            var blurClass = popup._popupBlur ? ' is-blurred' : '';
            bgHtml = '<div class="event-o-cal-popup-bg' + blurClass + '" style="background-image:url(' + calEscAttr(ev.image) + ')"></div>';
        } else {
            popup.classList.remove('has-image');
        }

        var excerptHtml = '';
        if (ev.excerpt) {
            excerptHtml = '<div class="event-o-cal-popup-excerpt">' + calEscHtml(ev.excerpt) + '</div>';
        }

        if (dayEvents.length > 1) {
            var itemsHtml = dayEvents.map(function(item) {
                var itemTime = buildTimeStr(item);
                var itemStatus = '';
                if (item.cancelled) {
                    itemStatus = '<span class="event-o-cal-popup-badge cancelled">' + calEscHtml(item.statusLabel || 'Abgesagt') + '</span>';
                } else if (item.soldOut) {
                    itemStatus = '<span class="event-o-cal-popup-badge sold-out">' + calEscHtml(item.statusLabel || 'Ausverkauft') + '</span>';
                }
                var itemCat = '';
                if (item.category) {
                    var itemCategoryLabels = calRenderCategoryLabels(item, 'event-o-cal-popup-item-cat-part', 'event-o-cal-popup-item-cat-sep');
                    itemCat = '<span class="event-o-cal-popup-item-cat">' + (itemCategoryLabels || calEscHtml(item.category)) + '</span>';
                }
                return '<a href="' + calEscAttr(item.url || '#') + '" class="event-o-cal-popup-item">'
                    + itemStatus
                    + '<div class="event-o-cal-popup-item-title">' + calEscHtml(item.title) + ' <span class="event-o-cal-popup-arrow">→</span></div>'
                    + (itemTime ? '<div class="event-o-cal-popup-item-time">' + calEscHtml(itemTime) + '</div>' : '')
                    + itemCat
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
                    statusHtml +
                    '<div class="event-o-cal-popup-title">' +
                        calEscHtml(ev.title) +
                        ' <span class="event-o-cal-popup-arrow">→</span>' +
                    '</div>' +
                    (timeStr ? '<div class="event-o-cal-popup-time">' + calEscHtml(timeStr) + '</div>' : '') +
                    categoryHtml +
                    venueHtml +
                    excerptHtml +
                '</a>';
        }

        if (mobile) {
            if (popup.parentNode !== document.body) {
                document.body.appendChild(popup);
                if (popup._ownerWrap) {
                    popup._ownerWrap._bodyPopup = popup;
                }
            }

            popup.classList.add('is-mobile');
            popup.classList.remove('is-desktop');

            var viewportWidth = window.innerWidth || document.documentElement.clientWidth || 360;
            var viewportHeight = window.innerHeight || document.documentElement.clientHeight || 640;
            var popupWidth = Math.min(460, Math.max(320, viewportWidth - 24));
            var popupMaxHeight = Math.max(280, viewportHeight - 32);

            popup.style.removeProperty('--arrow-left');
            popup.style.left = '50%';
            popup.style.top = '50%';
            popup.style.width = popupWidth + 'px';
            popup.style.height = 'auto';
            popup.style.maxHeight = popupMaxHeight + 'px';
        } else {
            if (popup.parentNode !== grid) {
                grid.appendChild(popup);
                if (popup._ownerWrap) {
                    popup._ownerWrap._bodyPopup = null;
                }
            }

            popup.classList.add('is-desktop');
            popup.classList.remove('is-mobile');

            var cw = cell.offsetWidth;
            var ch = cell.offsetHeight;
            var gridGap = parseFloat(window.getComputedStyle(grid).columnGap || window.getComputedStyle(grid).gap || '0') || 0;
            var matrix = state && state.desktopPopupMatrix === '3x2' ? { cols: 3, rows: 2 } : { cols: 3, rows: 3 };
            var spanW = matrix.cols * cw + Math.max(0, matrix.cols - 1) * gridGap;
            var spanH = matrix.rows * ch + Math.max(0, matrix.rows - 1) * gridGap;
            var left;
            var rightSideLeft = cell.offsetLeft + cw + gridGap;
            var leftSideLeft = cell.offsetLeft - spanW - gridGap;
            var centeredLeft = cell.offsetLeft - ((spanW - cw) / 2);
            var fitsRight = rightSideLeft + spanW <= grid.offsetWidth;
            var fitsLeft = leftSideLeft >= 0;

            if (fitsRight) {
                left = rightSideLeft;
            } else if (fitsLeft) {
                left = leftSideLeft;
            } else {
                left = centeredLeft;
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
            popup.style.maxHeight = '';
        }

        popup.style.display = 'flex';
        requestAnimationFrame(function() {
            popup.classList.add('is-visible');
        });
    }

    function calHidePopup(popup, state) {
        popup.classList.remove('is-visible');

        if (state.hideAnimationTimer) {
            clearTimeout(state.hideAnimationTimer);
        }

        state.hideAnimationTimer = setTimeout(function() {
            popup.style.display = 'none';
            popup.classList.remove('is-mobile', 'is-desktop');
            popup.style.maxHeight = '';
            state.hideAnimationTimer = null;
            popup._hideAnimationTimer = null;
        }, 200);
        popup._hideAnimationTimer = state.hideAnimationTimer;
        state.activeEl = null;
        state.activeCell = null;
    }

    window.eventOCalendarInit = initSingleCalendar;

    frontend.registerInit(function initCalendarFrontendFeatures() {
        initEventCalendars();
    });
})();
