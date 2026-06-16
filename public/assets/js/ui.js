/* Nullik Academy — UI helpers (toast, digits, formatting) */
(function () {
  "use strict";

  var FA = ["۰", "۱", "۲", "۳", "۴", "۵", "۶", "۷", "۸", "۹"];

  function toPersian(input) {
    return String(input).replace(/[0-9]/g, function (d) { return FA[+d]; });
  }
  function toEnglish(input) {
    return String(input)
      .replace(/[۰-۹]/g, function (d) { return FA.indexOf(d); })
      .replace(/[٠-٩]/g, function (d) { return "٠١٢٣٤٥٦٧٨٩".indexOf(d); });
  }
  function formatMoney(num) {
    var n = Math.round(Number(num) || 0);
    return toPersian(n.toLocaleString("en-US")).replace(/,/g, "،");
  }

  var toastRoot = null;
  function toast(message, type) {
    if (!toastRoot) toastRoot = document.getElementById("toast-root");
    if (!toastRoot) return;
    var el = document.createElement("div");
    el.className = "toast toast--" + (type || "success");
    el.textContent = message;
    toastRoot.appendChild(el);
    setTimeout(function () {
      el.style.opacity = "0";
      el.style.transform = "translateY(20px)";
      el.style.transition = "0.3s";
      setTimeout(function () { el.remove(); }, 300);
    }, 2600);
  }

  function copyToClipboard(text) {
    if (navigator.clipboard && navigator.clipboard.writeText) {
      return navigator.clipboard.writeText(text);
    }
    return new Promise(function (resolve) {
      var ta = document.createElement("textarea");
      ta.value = text; ta.style.position = "fixed"; ta.style.opacity = "0";
      document.body.appendChild(ta); ta.select();
      try { document.execCommand("copy"); } catch (e) {}
      ta.remove(); resolve();
    });
  }

  // Animated number counter
  function animateCount(el, to, formatter, duration) {
    var from = Number(el.getAttribute("data-current") || 0);
    var start = performance.now();
    duration = duration || 800;
    function tick(now) {
      var p = Math.min((now - start) / duration, 1);
      var eased = 1 - Math.pow(1 - p, 3);
      var val = from + (to - from) * eased;
      el.textContent = formatter ? formatter(val) : Math.round(val);
      if (p < 1) requestAnimationFrame(tick);
      else el.setAttribute("data-current", to);
    }
    requestAnimationFrame(tick);
  }

  function setError(fieldId, message) {
    var el = document.getElementById(fieldId);
    if (el) el.textContent = message || "";
  }

  window.UI = {
    toPersian: toPersian,
    toEnglish: toEnglish,
    formatMoney: formatMoney,
    toast: toast,
    copy: copyToClipboard,
    animateCount: animateCount,
    setError: setError
  };
})();
