// =========================================================
// TechShop Leap — Client-side Script
// =========================================================
document.addEventListener("DOMContentLoaded", function () {

    // ---------- Mobile nav toggle ----------
    var navToggle = document.getElementById("navToggle");
    var mainNav   = document.getElementById("mainNav");
    if (navToggle && mainNav) {
        navToggle.addEventListener("click", function () {
            mainNav.classList.toggle("open");
        });
    }

    // ---------- Generic form validation ----------
    document.querySelectorAll("form[data-validate]").forEach(function (form) {
        form.addEventListener("submit", function (e) {
            var valid = true;
            form.querySelectorAll("[required]").forEach(function (input) {
                clearErr(input);
                if (!input.value.trim()) { showErr(input, "This field is required."); valid = false; }
                else if (input.type === "email" && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(input.value)) {
                    showErr(input, "Please enter a valid email."); valid = false;
                } else if (input.type === "password" && input.dataset.minlength && input.value.length < +input.dataset.minlength) {
                    showErr(input, "Password must be at least " + input.dataset.minlength + " characters."); valid = false;
                }
            });
            var pass = form.querySelector("#password"), conf = form.querySelector("#confirm_password");
            if (pass && conf) { clearErr(conf); if (pass.value !== conf.value) { showErr(conf, "Passwords do not match."); valid = false; } }
            if (!valid) e.preventDefault();
        });
    });
    function showErr(el, msg) {
        clearErr(el);
        var d = document.createElement("div"); d.className = "form-error"; d.textContent = msg;
        el.insertAdjacentElement("afterend", d); el.style.borderColor = "#e5484d";
    }
    function clearErr(el) {
        el.style.borderColor = "";
        var n = el.nextElementSibling;
        if (n && n.classList.contains("form-error")) n.remove();
    }

    // ---------- Confirm-before-delete forms ----------
    document.querySelectorAll("form[data-confirm]").forEach(function (form) {
        form.addEventListener("submit", function (e) {
            if (!confirm(form.getAttribute("data-confirm") || "Are you sure?")) e.preventDefault();
        });
    });

    // =====================================================
    // CAROUSEL
    // =====================================================
    var slides = document.querySelectorAll(".carousel-slide");
    var dots   = document.querySelectorAll(".carousel-dot");
    if (slides.length > 0) {
        var current = 0;
        var timer;

        function goTo(n) {
            slides[current].classList.remove("active");
            dots[current] && dots[current].classList.remove("active");
            current = (n + slides.length) % slides.length;
            slides[current].classList.add("active");
            dots[current] && dots[current].classList.add("active");
        }

        function startAuto() {
            timer = setInterval(function () { goTo(current + 1); }, 3000);
        }

        function resetAuto() { clearInterval(timer); startAuto(); }

        var prevBtn = document.getElementById("carouselPrev");
        var nextBtn = document.getElementById("carouselNext");
        if (prevBtn) prevBtn.addEventListener("click", function () { goTo(current - 1); resetAuto(); });
        if (nextBtn) nextBtn.addEventListener("click", function () { goTo(current + 1); resetAuto(); });

        dots.forEach(function (dot, i) {
            dot.addEventListener("click", function () { goTo(i); resetAuto(); });
        });

        startAuto();
    }

    // =====================================================
    // PURCHASE MODAL (cart.php)
    // =====================================================
    var purchaseModal = document.getElementById("purchaseModal");
    var successModal  = document.getElementById("successModal");
    var openPurchaseBtn = document.getElementById("openPurchaseBtn");

    if (openPurchaseBtn && purchaseModal) {
        openPurchaseBtn.addEventListener("click", function () {
            purchaseModal.classList.add("open");
        });
    }

    // Close modals
    document.querySelectorAll(".modal-close, .modal-cancel").forEach(function (btn) {
        btn.addEventListener("click", function () {
            if (purchaseModal) purchaseModal.classList.remove("open");
            if (successModal)  successModal.classList.remove("open");
        });
    });

    // Close on overlay click
    [purchaseModal, successModal].forEach(function (modal) {
        if (!modal) return;
        modal.addEventListener("click", function (e) {
            if (e.target === modal) modal.classList.remove("open");
        });
    });

    // Confirm purchase button
    var confirmPurchaseBtn = document.getElementById("confirmPurchaseBtn");
    if (confirmPurchaseBtn) {
        confirmPurchaseBtn.addEventListener("click", function () {
            var address = document.getElementById("purchaseAddress").value.trim();
            if (!address) {
                alert("Please enter your delivery address.");
                return;
            }
            confirmPurchaseBtn.disabled = true;
            confirmPurchaseBtn.textContent = "Processing...";

            fetch("purchase_action.php", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: "address=" + encodeURIComponent(address)
            })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.success) {
                    purchaseModal.classList.remove("open");
                    successModal.classList.add("open");
                } else {
                    alert(data.message || "Something went wrong.");
                    confirmPurchaseBtn.disabled = false;
                    confirmPurchaseBtn.textContent = "Purchase";
                }
            })
            .catch(function () {
                alert("Network error. Please try again.");
                confirmPurchaseBtn.disabled = false;
                confirmPurchaseBtn.textContent = "Purchase";
            });
        });
    }

    // After success, reload page to clear cart
    var successOkBtn = document.getElementById("successOkBtn");
    if (successOkBtn) {
        successOkBtn.addEventListener("click", function () {
            window.location.reload();
        });
    }

    // =====================================================
    // PROFILE PICTURE UPLOAD (profile.php)
    // =====================================================
    var picInput = document.getElementById("profilePicInput");
    if (picInput) {
        picInput.addEventListener("change", function () {
            var file = picInput.files[0];
            if (!file) return;
            if (file.type !== "image/png") {
                alert("Only PNG images are allowed.");
                picInput.value = "";
                return;
            }
            var formData = new FormData();
            formData.append("profile_pic", file);

            fetch("upload_profile_pic.php", { method: "POST", body: formData })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.success) {
                    // Update all profile images on the page
                    document.querySelectorAll(".profile-avatar, .profile-circle-nav").forEach(function (img) {
                        img.src = data.path + "?t=" + Date.now();
                    });
                } else {
                    alert(data.message || "Upload failed.");
                }
            });
        });
    }

    // =====================================================
    // ADMIN TABS (profile.php admin view)
    // =====================================================
    document.querySelectorAll(".admin-tab-btn").forEach(function (btn) {
        btn.addEventListener("click", function () {
            document.querySelectorAll(".admin-tab-btn").forEach(function (b) { b.classList.remove("active"); });
            document.querySelectorAll(".admin-tab-pane").forEach(function (p) { p.style.display = "none"; });
            btn.classList.add("active");
            var target = document.getElementById(btn.dataset.tab);
            if (target) target.style.display = "block";
        });
    });

});
