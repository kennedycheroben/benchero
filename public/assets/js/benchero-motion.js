/**
 * ==========================================================================
 * BENCHERO — UNIFIED MOTION & TRANSITIONS ENGINE
 * ==========================================================================
 * Lightweight, high-performance, accessible page transition & UI motion.
 * Progressive enhancement: preserves standard server-rendered PHP navigation.
 * Zero-dependency, safe history/bfcache restoration, View Transitions support.
 * ==========================================================================
 */

(function () {
    'use strict';

    // 1. Feature & Preference Detection
    const prefersReducedMotion = () =>
        window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    const supportsViewTransitions = () =>
        'startViewTransition' in document ||
        (CSS && CSS.supports && CSS.supports('view-transition-name', 'root'));

    // 2. Top Progress Bar Manager
    const BencheroProgress = {
        bar: null,
        timer: null,

        init: function () {
            if (this.bar) return;
            this.bar = document.getElementById('benchero-progress-bar');
            if (!this.bar) {
                this.bar = document.createElement('div');
                this.bar.id = 'benchero-progress-bar';
                this.bar.setAttribute('aria-hidden', 'true');
                document.body.appendChild(this.bar);
            }
        },

        start: function () {
            if (prefersReducedMotion()) return;
            this.init();
            if (!this.bar) return;

            clearTimeout(this.timer);
            this.bar.classList.remove('complete');
            this.bar.classList.add('active');
            this.bar.style.width = '0%';

            // Rapid, smooth advancement
            requestAnimationFrame(() => {
                if (this.bar) {
                    this.bar.style.width = '35%';
                    this.timer = setTimeout(() => {
                        if (this.bar && this.bar.classList.contains('active')) {
                            this.bar.style.width = '75%';
                        }
                    }, 100);
                }
            });
        },

        finish: function () {
            if (!this.bar) return;
            clearTimeout(this.timer);
            this.bar.style.width = '100%';
            this.bar.classList.add('complete');
            this.timer = setTimeout(() => {
                this.reset();
            }, 300);
        },

        reset: function () {
            if (!this.bar) return;
            clearTimeout(this.timer);
            this.bar.classList.remove('active', 'complete');
            this.bar.style.width = '0%';
        }
    };

    // 3. Page Enter Transition Handler
    function handlePageEnter() {
        document.body.classList.remove('benchero-page-exiting');
        document.body.classList.add('benchero-page-entered');
        BencheroProgress.finish();
    }

    // 4. Link Navigation Interceptor (Page Exit)
    function isEligibleNavigation(link, event) {
        // Must be a valid anchor tag
        if (!link || link.tagName !== 'A') return false;

        // Mouse button checks: Primary left-click only
        if (event.button !== 0) return false;

        // Modifier keys: Allow standard browser behavior (open in new tab/window)
        if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return false;

        // If event was already prevented by another handler
        if (event.defaultPrevented) return false;

        // Check target attribute
        const target = link.getAttribute('target');
        if (target && target.toLowerCase() !== '_self') return false;

        // Exclude download links
        if (link.hasAttribute('download')) return false;

        // Exclude non-http(s) schemes: mailto, tel, javascript, etc.
        const href = link.getAttribute('href');
        if (!href || href === '#' || href.startsWith('javascript:') || href.startsWith('mailto:') || href.startsWith('tel:')) {
            return false;
        }

        // Exclude cross-origin links
        if (link.origin !== window.location.origin) return false;

        // Exclude same-page hash/anchor jumps
        if (link.pathname === window.location.pathname && link.search === window.location.search && link.hash) {
            return false;
        }

        // Exclude Bootstrap UI triggers and interactive controls
        if (link.hasAttribute('data-bs-toggle') || link.hasAttribute('data-bs-dismiss') || link.getAttribute('role') === 'button') {
            return false;
        }

        // Explicit opt-out attribute
        if (link.hasAttribute('data-no-transition') || link.closest('[data-no-transition]')) {
            return false;
        }

        // Exclude sensitive / transactional / external endpoints
        const pathname = link.pathname.toLowerCase();
        if (pathname.includes('/logout') || pathname.includes('/mpesa') || pathname.includes('/callback')) {
            return false;
        }

        return true;
    }

    function setupLinkTransitions() {
        document.addEventListener('click', function (event) {
            const link = event.target.closest('a');
            if (!isEligibleNavigation(link, event)) return;

            // If user prefers reduced motion, let native browser navigation proceed immediately
            if (prefersReducedMotion()) return;

            const targetUrl = link.href;

            // If browser supports cross-document View Transitions natively, show progress bar and let browser handle it
            if (supportsViewTransitions()) {
                BencheroProgress.start();
                return;
            }

            // Universal fallback transition: short 140ms exit animation
            event.preventDefault();
            BencheroProgress.start();
            document.body.classList.add('benchero-page-exiting');

            // Safety timeout: If browser does not navigate within 450ms, restore page state
            const safetyTimeout = setTimeout(() => {
                document.body.classList.remove('benchero-page-exiting');
                BencheroProgress.reset();
            }, 450);

            setTimeout(() => {
                window.location.href = targetUrl;
            }, 140);
        }, false);
    }

    // 5. Browser Back/Forward & BFCache (Page Show / Popstate)
    function setupBfcacheHandling() {
        window.addEventListener('pageshow', function (event) {
            // When navigating back/forward, page is often restored from bfcache (event.persisted)
            if (event.persisted) {
                document.body.classList.remove('benchero-page-exiting');
                BencheroProgress.reset();
            }
            document.body.classList.remove('benchero-page-exiting');
            BencheroProgress.finish();
            document.body.classList.add('benchero-page-entered');
        });

        window.addEventListener('popstate', function () {
            document.body.classList.remove('benchero-page-exiting');
            BencheroProgress.finish();
            BencheroProgress.reset();
        });
    }

    // 6. Section Reveal on Scroll (IntersectionObserver)
    function setupSectionReveals() {
        const revealElements = document.querySelectorAll('.benchero-reveal');
        if (!revealElements.length) return;

        // If reduced motion is preferred or IntersectionObserver is not available, reveal immediately
        if (prefersReducedMotion() || !('IntersectionObserver' in window)) {
            revealElements.forEach(el => el.classList.add('benchero-revealed'));
            return;
        }

        const observer = new IntersectionObserver((entries, obs) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('benchero-revealed');
                    obs.unobserve(entry.target);
                }
            });
        }, {
            threshold: 0.08,
            rootMargin: '0px 0px -30px 0px'
        });

        revealElements.forEach(el => observer.observe(el));

        // Safety fallback: reveal any remaining elements after 1.5s
        setTimeout(() => {
            revealElements.forEach(el => {
                if (!el.classList.contains('benchero-revealed')) {
                    el.classList.add('benchero-revealed');
                }
            });
        }, 1500);
    }

    // 7. Form Submission Loading Feedback (Double-Submit Prevention)
    function setupFormFeedback() {
        document.addEventListener('submit', function (event) {
            const form = event.target;
            if (!form || form.tagName !== 'FORM') return;

            // Find primary submit button
            const submitBtn = form.querySelector('button[type="submit"], input[type="submit"]');
            if (!submitBtn) return;

            // If form has client-side validation that failed, do not add loading state
            if (typeof form.checkValidity === 'function' && !form.checkValidity()) {
                return;
            }

            // Exclude logout or payment callback forms where spinner might interfere
            const action = form.getAttribute('action') || '';
            if (action.includes('/logout') || action.includes('/mpesa/callback')) {
                return;
            }

            // Add loading state to button
            submitBtn.classList.add('is-loading');

            // Safety reset after 6 seconds in case of network timeout
            setTimeout(() => {
                submitBtn.classList.remove('is-loading');
            }, 6000);
        }, false);
    }

    // 8. Image Graceful Loading
    function setupImageLoading() {
        const lazyImages = document.querySelectorAll('.benchero-img-lazy');
        lazyImages.forEach(img => {
            if (img.complete) {
                img.classList.add('is-loaded');
            } else {
                img.addEventListener('load', () => img.classList.add('is-loaded'), { once: true });
                img.addEventListener('error', () => img.classList.add('is-loaded'), { once: true });
            }
        });
    }

    // 9. Initializer
    function init() {
        try {
            BencheroProgress.init();
            handlePageEnter();
            setupLinkTransitions();
            setupBfcacheHandling();
            setupSectionReveals();
            setupFormFeedback();
            setupImageLoading();
        } catch (err) {
            // Fail-safe: ensure page is visible and functional even if an unexpected error occurs
            console.warn('[BencheroMotion] Motion initialization completed with warning:', err);
            document.body.classList.remove('benchero-page-exiting');
            document.body.classList.add('benchero-page-entered');
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    // Expose for external controls or testing
    window.BencheroMotion = {
        progress: BencheroProgress,
        version: '1.0.0'
    };
})();
