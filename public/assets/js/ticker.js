/* Nullik Academy — Live USDT ticker with animated counters */
(function () {
  "use strict";

  function init() {
    var section = document.getElementById("usdtTicker");
    if (!section) return;

    var elPrice = document.getElementById("tkPrice");
    var elChange = document.getElementById("tkChange");

    function render(t) {
      if (elPrice) UI.animateCount(elPrice, t.price, UI.formatMoney);
      if (elChange) {
        var ch = Number(t.change_percent_24) || 0;
        elChange.textContent = (ch > 0 ? "+" : "") + UI.toPersian(ch.toFixed(2)) + "٪";
        elChange.classList.toggle("is-up", ch >= 0);
        elChange.classList.toggle("is-down", ch < 0);
      }
    }

    // Initial render from server-provided data attributes.
    render({
      price: section.getAttribute("data-price"),
      change_percent_24: section.getAttribute("data-change")
    });

    function refresh() {
      API.get("usdt-price").then(function (res) {
        if (res.ok && res.data && res.data.data && res.data.data.ticker) {
          render(res.data.data.ticker);
        }
      });
    }

    setInterval(refresh, 60000); // every 60s
  }

  if (document.readyState !== "loading") init();
  else document.addEventListener("DOMContentLoaded", init);
})();
