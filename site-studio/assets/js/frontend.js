(function () {
    'use strict';

    function ready(callback) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', callback);
        } else {
            callback();
        }
    }

    ready(function () {
        var studio = document.querySelector('.arena-studio');
        if (!studio) {
            return;
        }

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
            var step = function () {
                return Math.max(slider.clientWidth * 0.78, 260);
            };
            var isRtl = studio.getAttribute('dir') === 'rtl';
            var move = function (amount) {
                slider.scrollBy({ left: amount, behavior: 'smooth' });
            };
            if (previous) {
                previous.addEventListener('click', function () { move(isRtl ? step() : -step()); });
            }
            if (next) {
                next.addEventListener('click', function () { move(isRtl ? -step() : step()); });
            }

            var timer = null;
            var start = function () {
                if (timer || slider.dataset.autoplay !== 'true' || slider.scrollWidth <= slider.clientWidth + 8) {
                    return;
                }
                timer = window.setInterval(function () {
                    var atEnd = isRtl
                        ? Math.abs(slider.scrollLeft) >= slider.scrollWidth - slider.clientWidth - 4
                        : slider.scrollLeft + slider.clientWidth >= slider.scrollWidth - 4;
                    if (atEnd) {
                        slider.scrollTo({ left: isRtl ? 0 : slider.scrollWidth, behavior: 'smooth' });
                    } else {
                        move(isRtl ? -step() : step());
                    }
                }, 4800);
            };
            var stop = function () {
                if (timer) {
                    window.clearInterval(timer);
                    timer = null;
                }
            };
            slider.addEventListener('mouseenter', stop);
            slider.addEventListener('mouseleave', start);
            slider.addEventListener('focusin', stop);
            slider.addEventListener('focusout', start);
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
    });
}());
