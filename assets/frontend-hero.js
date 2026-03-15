(function () {
    var frontend = window.EventOFrontend = window.EventOFrontend || {};

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

            var speedAttr = hero.getAttribute('data-transition-speed') || 'medium';
            var transitionDuration = speedAttr === 'fast' ? 250 : speedAttr === 'slow' ? 800 : 400;

            var isDragging = false;
            var startX = 0;
            var startY = 0;
            var scrollStart = 0;
            var dragDelta = 0;
            var touchAxis = '';

            var cloneLast = realSlides[slideCount - 1].cloneNode(true);
            var cloneFirst = realSlides[0].cloneNode(true);
            cloneLast.classList.add('is-clone');
            cloneFirst.classList.add('is-clone');
            cloneLast.setAttribute('aria-hidden', 'true');
            cloneFirst.setAttribute('aria-hidden', 'true');
            cloneLast.removeAttribute('id');
            cloneFirst.removeAttribute('id');

            track.insertBefore(cloneLast, realSlides[0]);
            track.appendChild(cloneFirst);

            var allSlides = Array.prototype.slice.call(track.querySelectorAll('.event-o-hero-slide'));

            function domIndex(realIdx) {
                return realIdx + 1;
            }

            function jumpTo(dIdx) {
                viewport.classList.add('is-dragging');
                viewport.scrollLeft = allSlides[dIdx].offsetLeft;
                void viewport.offsetHeight;
                viewport.classList.remove('is-dragging');
            }

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
                    targetDomIdx = slideCount + 1;
                    needsReset = true;
                    resetToReal = 0;
                    currentIndex = 0;
                } else if (index < 0) {
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

            viewport.addEventListener('touchstart', function (e) {
                if (isAnimating) return;
                startX = e.touches[0].pageX;
                startY = e.touches[0].pageY;
                scrollStart = viewport.scrollLeft;
                dragDelta = 0;
                isDragging = true;
                touchAxis = '';
            }, { passive: true });

            viewport.addEventListener('touchmove', function (e) {
                if (!isDragging) return;

                var dx = e.touches[0].pageX - startX;
                var dy = e.touches[0].pageY - startY;

                if (!touchAxis) {
                    if (Math.abs(dx) < 10 && Math.abs(dy) < 10) {
                        return;
                    }

                    if (Math.abs(dy) > Math.abs(dx)) {
                        touchAxis = 'vertical';
                        isDragging = false;
                        viewport.classList.remove('is-dragging');
                        startAutoPlay();
                        return;
                    }

                    touchAxis = 'horizontal';
                    startX = e.touches[0].pageX;
                    startY = e.touches[0].pageY;
                    scrollStart = viewport.scrollLeft;
                    dragDelta = 0;
                    viewport.classList.add('is-dragging');
                    stopAutoPlay();
                    return;
                }

                if (touchAxis !== 'horizontal') {
                    return;
                }

                e.preventDefault();
                dragDelta = dx;
                viewport.scrollLeft = scrollStart - dx;
            }, { passive: false });

            viewport.addEventListener('touchend', function () {
                if (touchAxis !== 'horizontal' || !isDragging) {
                    isDragging = false;
                    touchAxis = '';
                    startAutoPlay();
                    return;
                }

                isDragging = false;
                touchAxis = '';
                viewport.classList.remove('is-dragging');

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

            viewport.addEventListener('touchcancel', function () {
                isDragging = false;
                touchAxis = '';
                viewport.classList.remove('is-dragging');
                startAutoPlay();
            }, { passive: true });

            dots.forEach(function (dot) {
                dot.addEventListener('click', function () {
                    var index = parseInt(this.getAttribute('data-index'), 10);
                    scrollToSlide(index);
                    startAutoPlay();
                });
            });

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
                var shortHeight = inner.scrollHeight;
                desc.classList.add('is-expanded');
                var fullHeight = inner.scrollHeight;
                inner.style.maxHeight = shortHeight + 'px';
                inner.offsetHeight;
                inner.style.maxHeight = fullHeight + 'px';
                btn.textContent = 'weniger';
                inner.addEventListener('transitionend', function handler() {
                    inner.style.maxHeight = 'none';
                    inner.removeEventListener('transitionend', handler);
                });
            } else {
                var currentHeight = inner.scrollHeight;
                inner.style.maxHeight = currentHeight + 'px';
                inner.offsetHeight;
                desc.classList.remove('is-expanded');
                var collapsedHeight = inner.scrollHeight;
                inner.style.maxHeight = collapsedHeight + 'px';
                btn.textContent = 'mehr…';
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

            if (rect.bottom < 0 || rect.top > winH) {
                ticking = false;
                return;
            }

            var progress = 1 - (rect.bottom / (winH + heroH));
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

    frontend.registerInit(function initHeroFrontendFeatures() {
        initHeroSliders();
        initProgramLoadMore();
        initEventImageCrossfades();
        initDescToggle();
        initHeroParallax();
        initSingleAnimations();
        initBlockAnimations();
    });
})();
