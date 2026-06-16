/* Nullik Academy — runtime config bootstrap */
(function () {
  "use strict";

  function readJSON(id, fallback) {
    var el = document.getElementById(id);
    if (!el) return fallback;
    try { return JSON.parse(el.textContent.trim()); }
    catch (e) { return fallback; }
  }

  var cfg = readJSON("app-config", {});
  var tools = readJSON("bootstrap-tools", []);

  window.NULLIK = {
    csrf: cfg.csrf || "",
    apiBase: cfg.apiBase || "/api",
    baseUrl: cfg.baseUrl || "",
    supportUrl: cfg.supportUrl || "#",
    otp: cfg.otp || { length: 4, expiry: 120, cooldown: 60 },
    payment: cfg.payment || {},
    tools: tools,
    state: {
      selectedTool: tools.length ? tools[0].slug : null,
      selectedPlan: null,
      plansCache: {},
      verifiedMobile: null,
      receiptToken: null
    }
  };
})();
