(function () {
    'use strict';

    function ready(callback) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', callback);
        } else {
            callback();
        }
    }

    function initStudio(studio) {
        if (!studio || studio.dataset.studioReady === 'true') {
            return;
        }
        studio.dataset.studioReady = 'true';

        var menuToggle = studio.querySelector('[data-studio-menu-toggle]');
        var mobileMenu = studio.querySelector('[data-studio-mobile-menu]');
        if (menuToggle && mobileMenu) {
            menuToggle.addEventListener('click', function () {
                var open = menuToggle.getAttribute('aria-expanded') === 'true';
                menuToggle.setAttribute('aria-expanded', open ? 'false' : 'true');
                menuToggle.classList.toggle('is-open', !open);
                mobileMenu.hidden = open;
            });
        }

        var searchToggle = studio.querySelector('[data-studio-search-toggle]');
        var searchPanel = studio.querySelector('[data-studio-search-panel]');
        if (searchToggle && searchPanel) {
            searchToggle.addEventListener('click', function () {
                var hidden = searchPanel.hidden;
                searchPanel.hidden = !hidden;
                searchToggle.setAttribute('aria-expanded', hidden ? 'true' : 'false');
                if (hidden) {
                    var input = searchPanel.querySelector('input');
                    if (input) {
                        window.setTimeout(function () { input.focus(); }, 60);
                    }
                }
            });
        }

        studio.querySelectorAll('[data-studio-slider]').forEach(function (slider) {
            var section = slider.closest('.studio-section');
            var previous = section ? section.querySelector('[data-slider-prev]') : null;
            var next = section ? section.querySelector('[data-slider-next]') : null;
            var card = slider.querySelector('.studio-product-card');
            var reducedMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            var isRtl = studio.getAttribute('dir') === 'rtl' || window.getComputedStyle(studio).direction === 'rtl';
            var timer = null;

            var step = function () {
                var cardWidth = card ? card.getBoundingClientRect().width + 17 : 260;
                return Math.max(slider.clientWidth * 0.78, cardWidth);
            };
            var move = function (amount) {
                if (typeof slider.scrollBy === 'function') {
                    slider.scrollBy({ left: amount, behavior: 'smooth' });
                } else {
                    slider.scrollLeft += amount;
                }
            };
            var updateControls = function () {
                var overflow = slider.scrollWidth > slider.clientWidth + 8;
                if (previous) {
                    previous.hidden = !overflow;
                }
                if (next) {
                    next.hidden = !overflow;
                }
            };
            var currentDistance = function () {
                return Math.abs(slider.scrollLeft);
            };
            var atEnd = function () {
                return currentDistance() >= slider.scrollWidth - slider.clientWidth - 4;
            };

            updateControls();
            if (previous) {
                previous.addEventListener('click', function () { move(isRtl ? step() : -step()); });
            }
            if (next) {
                next.addEventListener('click', function () { move(isRtl ? -step() : step()); });
            }

            var stop = function () {
                if (timer) {
                    window.clearInterval(timer);
                    timer = null;
                }
            };
            var start = function () {
                if (timer || reducedMotion || slider.dataset.autoplay !== 'true' || slider.scrollWidth <= slider.clientWidth + 8) {
                    return;
                }
                timer = window.setInterval(function () {
                    if (atEnd()) {
                        if (typeof slider.scrollTo === 'function') {
                            slider.scrollTo({ left: isRtl ? 0 : slider.scrollWidth, behavior: 'smooth' });
                        } else {
                            slider.scrollLeft = isRtl ? 0 : slider.scrollWidth;
                        }
                    } else {
                        move(isRtl ? -step() : step());
                    }
                }, 4800);
            };
            slider.addEventListener('mouseenter', stop);
            slider.addEventListener('mouseleave', start);
            slider.addEventListener('focusin', stop);
            slider.addEventListener('focusout', function (event) {
                var related = event.relatedTarget;
                if (!related || !related.nodeType || !slider.contains(related)) {
                    start();
                }
            });
            window.addEventListener('resize', updateControls, { passive: true });
            slider.querySelectorAll('img').forEach(function (image) {
                image.addEventListener('load', updateControls, { once: true });
            });
            start();
        });

        studio.querySelectorAll('[data-studio-newsletter]').forEach(function (form) {
            form.addEventListener('submit', function (event) {
                event.preventDefault();
                var message = form.querySelector('[data-studio-newsletter-message]');
                if (!message) {
                    message = document.createElement('small');
                    message.setAttribute('data-studio-newsletter-message', 'true');
                    message.style.color = 'var(--studio-accent)';
                    message.style.display = 'block';
                    message.style.marginTop = '7px';
                    form.parentNode.appendChild(message);
                }
                message.textContent = 'برای اتصال خبرنامه، سرویس ایمیل خود را در تنظیمات فرم متصل کنید.';
            });
        });
    }

    ready(function () {
        document.querySelectorAll('.arena-studio').forEach(initStudio);
    });
}());
