/* Nullik Academy — Multi-step checkout engine */
(function () {
  "use strict";

  var el = {};
  var current = 1;
  var totalSteps = 6;
  var plan = null;
  var tool = null;
  var resendTimer = null;

  function $(id) { return document.getElementById(id); }

  function cache() {
    el.modal = $("checkout");
    el.body = document.body;
    el.steps = Array.prototype.slice.call(el.modal.querySelectorAll(".cstep"));
    el.segs = Array.prototype.slice.call(el.modal.querySelectorAll(".progress-seg"));
    // step 1
    el.mobileInput = $("mobileInput");
    el.sendOtpBtn = $("sendOtpBtn");
    // step 2
    el.otpInputs = $("otpInputs");
    el.otpBoxes = Array.prototype.slice.call(el.otpInputs.querySelectorAll(".otp-box"));
    el.otpMobileLabel = $("otpMobileLabel");
    el.resendBtn = $("resendOtpBtn");
    el.editMobileBtn = $("editMobileBtn");
    el.countdown = $("otpCountdown");
    // step 3
    el.firstName = $("firstNameInput");
    el.lastName = $("lastNameInput");
    el.personalNext = $("personalNextBtn");
    // step 4
    el.accountTitle = $("accountTitle");
    el.email = $("emailInput");
    el.password = $("passwordInput");
    el.passwordField = $("passwordField");
    el.passwordToggle = $("passwordToggle");
    el.orgField = $("orgField");
    el.org = $("orgInput");
    el.accountNext = $("accountNextBtn");
    // step 5
    el.reviewCard = $("reviewCard");
    el.payNote = $("payNote");
    el.paidBtn = $("paidBtn");
    // step 6
    el.uploader = $("uploader");
    el.receiptInput = $("receiptInput");
    el.placeholder = $("uploaderPlaceholder");
    el.preview = $("receiptPreview");
    el.submitBtn = $("submitOrderBtn");
  }

  /* ---------- Navigation ---------- */
  function goto(step) {
    current = Math.max(1, Math.min(totalSteps, step));
    el.steps.forEach(function (s) {
      s.classList.toggle("is-active", Number(s.getAttribute("data-step")) === current);
    });
    el.segs.forEach(function (seg) {
      var n = Number(seg.getAttribute("data-seg"));
      seg.classList.toggle("is-active", n === current);
      seg.classList.toggle("is-done", n < current);
    });
    el.modal.querySelector(".checkout__dialog").scrollTop = 0;
  }

  function open(selectedPlan, selectedTool) {
    plan = selectedPlan; tool = selectedTool;

    var isOrg = plan.mode_type === "organization_id";
    el.passwordField.hidden = isOrg;
    el.orgField.hidden = !isOrg;

    el.accountTitle.textContent = "اطلاعات حساب " + tool.name + " خود را وارد کنید";
    el.payNote.innerHTML = 'لطفا مبلغ <b class="pay-note__amount">' + plan.price_irt_formatted +
      ' تومان</b> به شماره کارت یا شماره شبای بالا واریز نمایید.';

    el.modal.classList.add("is-open");
    el.modal.setAttribute("aria-hidden", "false");
    el.body.classList.add("is-locked");

    if (NULLIK.state.verifiedMobile) {
      el.mobileInput.value = UI.toPersian(NULLIK.state.verifiedMobile);
      goto(3); // skip mobile + otp
    } else {
      goto(1);
    }
  }

  function close() {
    el.modal.classList.remove("is-open");
    el.modal.setAttribute("aria-hidden", "true");
    el.body.classList.remove("is-locked");
  }

  /* ---------- Step 1: mobile ---------- */
  function validMobile(v) { return /^(?:0|98|\+98)?9\d{9}$/.test(v); }
  function normalizeMobile(v) {
    v = UI.toEnglish(v).replace(/\D/g, "");
    if (v.indexOf("98") === 0 && v.length === 12) v = "0" + v.slice(2);
    else if (v.charAt(0) === "9" && v.length === 10) v = "0" + v;
    return v;
  }

  function sendOtp(resend) {
    var raw = UI.toEnglish(el.mobileInput.value).replace(/\D/g, "");
    if (!validMobile(raw)) { UI.setError("mobileError", "شماره موبایل معتبر نیست."); return; }
    UI.setError("mobileError", "");
    var mobile = normalizeMobile(raw);
    var btn = resend ? el.resendBtn : el.sendOtpBtn;
    btn.disabled = true;

    API.post("send-otp", { mobile: mobile }).then(function (res) {
      btn.disabled = false;
      if (res.ok && res.data.success) {
        NULLIK.state.pendingMobile = mobile;
        el.otpMobileLabel.textContent = UI.toPersian(mobile);
        startCountdown(res.data.data.resend_cooldown || NULLIK.otp.cooldown);
        if (!resend) { goto(2); el.otpBoxes[0].focus(); }
        UI.toast("کد تأیید ارسال شد", "success");
        if (res.data.data.debug_code) UI.toast("کد تست: " + res.data.data.debug_code, "success");
      } else {
        UI.setError("mobileError", res.data.message || "خطا در ارسال کد.");
      }
    });
  }

  /* ---------- Step 2: OTP ---------- */
  function startCountdown(seconds) {
    clearInterval(resendTimer);
    var remaining = seconds;
    el.resendBtn.disabled = true;
    el.countdown.textContent = UI.toPersian(remaining);
    resendTimer = setInterval(function () {
      remaining--;
      el.countdown.textContent = UI.toPersian(remaining);
      if (remaining <= 0) { clearInterval(resendTimer); el.resendBtn.disabled = false; }
    }, 1000);
  }

  function otpValue() {
    return el.otpBoxes.map(function (b) { return UI.toEnglish(b.value); }).join("");
  }

  function verifyOtp() {
    var code = otpValue();
    if (code.length !== NULLIK.otp.length) return;
    API.post("verify-otp", { mobile: NULLIK.state.pendingMobile, code: code }).then(function (res) {
      if (res.ok && res.data.success) {
        el.otpInputs.classList.remove("is-error");
        el.otpInputs.classList.add("is-success");
        NULLIK.state.verifiedMobile = NULLIK.state.pendingMobile;
        UI.toast("شماره تأیید شد", "success");
        setTimeout(function () { goto(3); }, 500);
      } else {
        el.otpInputs.classList.add("is-error");
        var msg = res.data.message || "کد نادرست است.";
        if (res.data.remaining != null) msg += " (" + UI.toPersian(res.data.remaining) + " تلاش باقی‌مانده)";
        UI.setError("otpError", msg);
        el.otpBoxes.forEach(function (b) { b.value = ""; b.classList.remove("is-filled"); });
        el.otpBoxes[0].focus();
        setTimeout(function () { el.otpInputs.classList.remove("is-error"); }, 600);
      }
    });
  }

  function initOtpBoxes() {
    el.otpBoxes.forEach(function (box, i) {
      box.addEventListener("input", function () {
        box.value = UI.toEnglish(box.value).replace(/\D/g, "").slice(0, 1);
        box.classList.toggle("is-filled", box.value !== "");
        UI.setError("otpError", "");
        if (box.value && i < el.otpBoxes.length - 1) el.otpBoxes[i + 1].focus();
        if (otpValue().length === NULLIK.otp.length) verifyOtp();
      });
      box.addEventListener("keydown", function (e) {
        if (e.key === "Backspace" && !box.value && i > 0) el.otpBoxes[i - 1].focus();
      });
      box.addEventListener("paste", function (e) {
        e.preventDefault();
        var text = UI.toEnglish((e.clipboardData || window.clipboardData).getData("text")).replace(/\D/g, "");
        el.otpBoxes.forEach(function (b, j) { b.value = text[j] || ""; b.classList.toggle("is-filled", !!text[j]); });
        if (text.length >= NULLIK.otp.length) verifyOtp();
      });
    });
  }

  /* ---------- Step 3: personal ---------- */
  function validateName(v) { return /^[؀-ۿ\sA-Za-z‌]+$/.test(v.trim()) && v.trim().length > 0; }
  function personalNext() {
    var ok = true;
    if (!validateName(el.firstName.value)) { UI.setError("firstNameError", "نام را به‌درستی وارد کنید."); ok = false; }
    else UI.setError("firstNameError", "");
    if (!validateName(el.lastName.value)) { UI.setError("lastNameError", "نام خانوادگی را به‌درستی وارد کنید."); ok = false; }
    else UI.setError("lastNameError", "");
    if (ok) goto(4);
  }

  /* ---------- Step 4: account ---------- */
  function validEmail(v) { return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v); }
  function accountNext() {
    var ok = true;
    if (!validEmail(el.email.value.trim())) { UI.setError("emailError", "ایمیل معتبر نیست."); ok = false; }
    else UI.setError("emailError", "");

    if (plan.mode_type === "organization_id") {
      if (!el.org.value.trim()) { UI.setError("orgError", "شناسه سازمانی الزامی است."); ok = false; }
      else UI.setError("orgError", "");
    } else {
      if (el.password.value.length < 4) { UI.setError("passwordError", "رمز عبور حداقل ۴ کاراکتر باشد."); ok = false; }
      else UI.setError("passwordError", "");
    }
    if (ok) { buildReview(); goto(5); }
  }

  /* ---------- Step 5: review ---------- */
  function buildReview() {
    var rows = [
      ["ابزار", tool.name],
      ["پلن", plan.name],
      ["مدت", plan.duration],
      ["ایمیل", el.email.value.trim()]
    ];
    if (plan.mode_type === "organization_id") rows.push(["شناسه سازمانی", el.org.value.trim()]);
    else rows.push(["__password__", el.password.value]);

    var html = rows.map(function (r) {
      if (r[0] === "__password__") {
        return '<div class="review-row"><span>رمز عبور</span>' +
          '<span class="review-row__pwd"><span id="reviewPwd" data-real="' + escapeHtml(r[1]) + '">••••••••</span>' +
          '<button type="button" id="reviewPwdToggle" aria-label="نمایش رمز">نمایش</button></span></div>';
      }
      return '<div class="review-row"><span>' + r[0] + "</span><span dir=\"auto\">" + escapeHtml(r[1]) + "</span></div>";
    }).join("");

    html += '<div class="review-row review-row--total"><span>مبلغ قابل پرداخت</span><span>' +
      plan.price_irt_formatted + " تومان</span></div>";

    el.reviewCard.innerHTML = html;

    var toggle = $("reviewPwdToggle");
    if (toggle) {
      toggle.addEventListener("click", function () {
        var span = $("reviewPwd");
        var shown = span.getAttribute("data-shown") === "1";
        span.textContent = shown ? "••••••••" : span.getAttribute("data-real");
        span.setAttribute("data-shown", shown ? "0" : "1");
        toggle.textContent = shown ? "نمایش" : "مخفی";
      });
    }
  }

  function escapeHtml(s) {
    return String(s).replace(/[&<>"']/g, function (c) {
      return { "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[c];
    });
  }

  /* ---------- Step 6: receipt ---------- */
  function handleFile(file) {
    if (!file) return;
    var allowed = ["image/jpeg", "image/png", "image/webp"];
    if (allowed.indexOf(file.type) === -1) { UI.setError("receiptError", "فرمت فایل مجاز نیست."); return; }
    if (file.size > 10 * 1024 * 1024) { UI.setError("receiptError", "حجم فایل بیش از ۱۰ مگابایت است."); return; }
    UI.setError("receiptError", "");

    var reader = new FileReader();
    reader.onload = function (e) {
      el.preview.src = e.target.result;
      el.preview.hidden = false;
      el.placeholder.hidden = true;
    };
    reader.readAsDataURL(file);

    var fd = new FormData();
    fd.append("receipt", file);
    fd.append("_token", NULLIK.csrf);
    el.submitBtn.disabled = true;
    el.uploader.classList.add("is-loading");

    API.upload("upload-receipt", fd).then(function (res) {
      el.uploader.classList.remove("is-loading");
      if (res.ok && res.data.success) {
        NULLIK.state.receiptToken = res.data.data.token;
        el.submitBtn.disabled = false;
        UI.toast("رسید بارگذاری شد", "success");
      } else {
        UI.setError("receiptError", res.data.message || "خطا در بارگذاری.");
      }
    });
  }

  function submitOrder() {
    if (!NULLIK.state.receiptToken) { UI.setError("receiptError", "ابتدا رسید را بارگذاری کنید."); return; }
    el.submitBtn.disabled = true;
    el.submitBtn.textContent = "در حال ثبت...";

    var payload = {
      plan_id: plan.id,
      mobile: NULLIK.state.verifiedMobile,
      first_name: el.firstName.value.trim(),
      last_name: el.lastName.value.trim(),
      email: el.email.value.trim(),
      receipt_token: NULLIK.state.receiptToken
    };
    if (plan.mode_type === "organization_id") payload.organization_id = el.org.value.trim();
    else payload.password = el.password.value;

    API.post("order/create", payload).then(function (res) {
      if (res.ok && res.data.success) {
        window.location.href = NULLIK.baseUrl + "/success/" + encodeURIComponent(res.data.data.order.order_number);
      } else {
        el.submitBtn.disabled = false;
        el.submitBtn.textContent = "ثبت نهایی سفارش";
        UI.toast(res.data.message || "خطا در ثبت سفارش", "error");
      }
    });
  }

  /* ---------- Copy buttons (bank card) ---------- */
  function initCopyButtons() {
    Array.prototype.forEach.call(document.querySelectorAll(".copy-btn"), function (btn) {
      btn.addEventListener("click", function () {
        UI.copy(btn.getAttribute("data-copy")).then(function () {
          btn.classList.add("is-copied");
          var old = btn.textContent; btn.textContent = "کپی شد";
          UI.toast("کپی شد", "success");
          setTimeout(function () { btn.classList.remove("is-copied"); btn.textContent = old; }, 1500);
        });
      });
    });
  }

  /* ---------- Bind ---------- */
  function bind() {
    el.sendOtpBtn.addEventListener("click", function () { sendOtp(false); });
    el.resendBtn.addEventListener("click", function () { sendOtp(true); });
    el.editMobileBtn.addEventListener("click", function () { clearInterval(resendTimer); goto(1); el.mobileInput.focus(); });
    el.mobileInput.addEventListener("input", function () {
      el.mobileInput.value = UI.toPersian(UI.toEnglish(el.mobileInput.value).replace(/\D/g, "").slice(0, 11));
    });
    initOtpBoxes();

    el.personalNext.addEventListener("click", personalNext);
    el.accountNext.addEventListener("click", accountNext);
    el.passwordToggle.addEventListener("click", function () {
      el.password.type = el.password.type === "password" ? "text" : "password";
    });

    el.paidBtn.addEventListener("click", function () { goto(6); });

    el.uploader.addEventListener("click", function () { el.receiptInput.click(); });
    el.uploader.addEventListener("keydown", function (e) { if (e.key === "Enter") el.receiptInput.click(); });
    el.receiptInput.addEventListener("change", function () { handleFile(el.receiptInput.files[0]); });
    ["dragover", "dragenter"].forEach(function (ev) {
      el.uploader.addEventListener(ev, function (e) { e.preventDefault(); el.uploader.classList.add("is-drag"); });
    });
    ["dragleave", "drop"].forEach(function (ev) {
      el.uploader.addEventListener(ev, function (e) { e.preventDefault(); el.uploader.classList.remove("is-drag"); });
    });
    el.uploader.addEventListener("drop", function (e) {
      if (e.dataTransfer.files.length) handleFile(e.dataTransfer.files[0]);
    });

    el.submitBtn.addEventListener("click", submitOrder);

    Array.prototype.forEach.call(el.modal.querySelectorAll("[data-prev]"), function (b) {
      b.addEventListener("click", function () { goto(current - 1); });
    });
    Array.prototype.forEach.call(el.modal.querySelectorAll("[data-checkout-close]"), function (b) {
      b.addEventListener("click", close);
    });
    document.addEventListener("keydown", function (e) {
      if (e.key === "Escape" && el.modal.classList.contains("is-open")) close();
    });

    initCopyButtons();
  }

  function init() {
    if (!$("checkout")) return;
    cache();
    bind();
    window.Checkout = { open: open, close: close };
  }

  if (document.readyState !== "loading") init();
  else document.addEventListener("DOMContentLoaded", init);
})();
