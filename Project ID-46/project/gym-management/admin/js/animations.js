// ==========================================================
// js/animations.js
// Pure presentation layer — scroll-reveal + number count-up.
// Works on EXISTING classes already in the markup, so most
// pages need zero changes to get the effect. Respects
// prefers-reduced-motion (skips animation, shows content
// immediately, still runs count-up instantly with no easing).
// ==========================================================

document.addEventListener("DOMContentLoaded", function () {
    const prefersReducedMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;

    // ---- Scroll-reveal ----
    const revealTargets = document.querySelectorAll(
        ".gym-card, .stat-card, .auth-card, .batch-check, .gym-hero h1, .gym-hero p, .gym-hero .btn"
    );

    if (prefersReducedMotion || !("IntersectionObserver" in window)) {
        revealTargets.forEach(el => el.classList.add("gp-in-view"));
    } else {
        const observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry, index) {
                if (entry.isIntersecting) {
                    // Small stagger so grouped cards don't all pop at once
                    setTimeout(() => entry.target.classList.add("gp-in-view"), index * 60);
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.1, rootMargin: "0px 0px -40px 0px" });

        revealTargets.forEach(el => observer.observe(el));
    }

    // ---- Number count-up for dashboard stat cards ----
    // Opt-in only: add data-count="123" to any element to enable.
    // Existing stat cards that show text (batch names, member codes)
    // are untouched since they simply won't have this attribute.
    const counters = document.querySelectorAll("[data-count]");
    counters.forEach(function (el) {
        const target = parseInt(el.getAttribute("data-count"), 10);
        if (isNaN(target)) return;

        if (prefersReducedMotion) {
            el.textContent = target;
            return;
        }

        const duration = 900;
        const start = performance.now();

        function tick(now) {
            const progress = Math.min((now - start) / duration, 1);
            const eased = 1 - Math.pow(1 - progress, 3); // ease-out-cubic
            el.textContent = Math.round(eased * target);
            if (progress < 1) requestAnimationFrame(tick);
        }
        requestAnimationFrame(tick);
    });
});
