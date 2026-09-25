// ==========================================================
// js/script.js
// Shared JavaScript for the public site (and copied into
// admin/js, trainer/js for their panels).
// Main job: toggle password fields between hidden/visible.
// ==========================================================

document.addEventListener("DOMContentLoaded", function () {
    // Find every eye icon with class "toggle-password"
    const toggles = document.querySelectorAll(".toggle-password");

    toggles.forEach(function (icon) {
        icon.addEventListener("click", function () {
            // The icon must have data-target="idOfPasswordInput"
            const targetId = icon.getAttribute("data-target");
            const input = document.getElementById(targetId);
            if (!input) return;

            if (input.type === "password") {
                input.type = "text";
                icon.classList.remove("bi-eye");
                icon.classList.add("bi-eye-slash");
            } else {
                input.type = "password";
                icon.classList.remove("bi-eye-slash");
                icon.classList.add("bi-eye");
            }
        });
    });

    // Auto-hide Bootstrap alert messages after 4 seconds
    const alerts = document.querySelectorAll(".auto-hide-alert");
    alerts.forEach(function (alertBox) {
        setTimeout(function () {
            alertBox.style.transition = "opacity .5s ease";
            alertBox.style.opacity = "0";
            setTimeout(() => alertBox.remove(), 500);
        }, 4000);
    });

    // ==========================================================
    // Batch picker: live overlap + max-selection guard.
    // This is a CONVENIENCE layer only — the real, authoritative
    // check always happens server-side in PHP (find_batch_conflict()
    // in includes/batch-functions.php). This just stops a user from
    // clicking something that PHP would reject anyway, so they get
    // instant feedback instead of a page reload with an error.
    // Works on any container holding checkboxes with class
    // "batch-check-input" and data-start / data-end (HH:MM:SS) attrs.
    // ==========================================================
    const batchContainer = document.querySelector("[data-batch-picker]");
    if (batchContainer) {
        const maxBatches = parseInt(batchContainer.getAttribute("data-max-batches") || "3", 10);
        const boxes = batchContainer.querySelectorAll(".batch-check-input");

        function timesOverlap(s1, e1, s2, e2) {
            return (s1 < e2) && (s2 < e1);
        }

        function refreshBatchState() {
            const checked = Array.from(boxes).filter(b => b.checked);
            const checkedCount = checked.length;

            boxes.forEach(function (box) {
                if (box.checked) {
                    box.disabled = false;
                    return;
                }
                // Disable if selecting this would exceed the max
                if (checkedCount >= maxBatches) {
                    box.disabled = true;
                    return;
                }
                // Disable if it overlaps any currently-checked batch
                const conflicts = checked.some(c => timesOverlap(
                    box.dataset.start, box.dataset.end, c.dataset.start, c.dataset.end
                ));
                box.disabled = conflicts;
            });
        }

        boxes.forEach(box => box.addEventListener("change", refreshBatchState));
        refreshBatchState(); // run once on load (handles pre-checked edit forms)

        // ==========================================================
        // Live payment breakdown. Only activates if the page also
        // has a duration <select class="duration-select"> and a
        // breakdown container #paymentBreakdown — pages without a
        // duration field (like admin's edit-member.php, where batches
        // change but duration doesn't) simply won't show this, no
        // errors either way.
        // This is a CONVENIENCE preview only. The actual amount due
        // is always calculated authoritatively server-side in
        // includes/payment-functions.php — this just lets someone
        // see the cost before submitting, so there are no surprises.
        // ==========================================================
        const durationSelect = document.querySelector(".duration-select");
        const breakdownBox = document.getElementById("paymentBreakdown");

        if (durationSelect && breakdownBox) {
            const breakdownList = document.getElementById("breakdownList");
            const breakdownDuration = document.getElementById("breakdownDuration");
            const breakdownTotal = document.getElementById("breakdownTotal");

            function refreshBreakdown() {
                const checked = Array.from(boxes).filter(b => b.checked);
                const duration = parseInt(durationSelect.value, 10) || 1;

                if (checked.length === 0) {
                    breakdownBox.style.display = "none";
                    return;
                }
                breakdownBox.style.display = "block";

                let monthlyTotal = 0;
                breakdownList.innerHTML = "";
                checked.forEach(function (box) {
                    const fee = parseFloat(box.dataset.fee || "0");
                    monthlyTotal += fee;
                    const name = box.dataset.name || "Batch";
                    const li = document.createElement("li");
                    li.className = "d-flex justify-content-between";
                    li.innerHTML = "<span>" + name + "</span><span>Rs. " + fee.toFixed(2) + " / month</span>";
                    breakdownList.appendChild(li);
                });

                const grandTotal = monthlyTotal * duration;
                breakdownDuration.textContent = duration;
                breakdownTotal.textContent = grandTotal.toFixed(2);
            }

            boxes.forEach(box => box.addEventListener("change", refreshBreakdown));
            durationSelect.addEventListener("change", refreshBreakdown);
            refreshBreakdown(); // run once on load
        }
    }

    // ==========================================================
    // Mobile panel sidebar toggle (admin/trainer/member panels).
    // On desktop this code runs but has no visible effect, since
    // the CSS media query that makes the sidebar an off-canvas
    // drawer only applies below 992px — so this is safe to run
    // unconditionally on every page.
    // ==========================================================
    const sidebarToggle = document.getElementById("sidebarToggle");
    const panelSidebar = document.getElementById("panelSidebar");
    const panelOverlay = document.getElementById("panelOverlay");

    if (sidebarToggle && panelSidebar && panelOverlay) {
        function openSidebar() {
            panelSidebar.classList.add("panel-sidebar-open");
            panelOverlay.classList.add("panel-overlay-visible");
        }
        function closeSidebar() {
            panelSidebar.classList.remove("panel-sidebar-open");
            panelOverlay.classList.remove("panel-overlay-visible");
        }
        sidebarToggle.addEventListener("click", function () {
            if (panelSidebar.classList.contains("panel-sidebar-open")) {
                closeSidebar();
            } else {
                openSidebar();
            }
        });
        panelOverlay.addEventListener("click", closeSidebar);
    }
});
