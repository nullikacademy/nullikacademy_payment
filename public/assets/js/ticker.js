/* Nullik Academy — Live USDT ticker with animated counters */
(function () {
  "use strict";

  function init() {
    var section = document.getElementById("usdtTicker");
    if (!section) return;

    var elPrice = document.getElementById("tkPrice");
    var elHigh = document.getElementById("tkHigh");
    var elLow = document.getElementById("tkLow");
    var elChange = document.getElementById("tkChange");

    function render(t) {
      UI.animateCount(elPrice, t.price, UI.formatMoney);
      UI.animateCount(elHigh, t.high_24, UI.formatMoney);
      UI.animateCount(elLow, t.low_24, UI.formatMoney);

      var ch = Number(t.change_percent_24) || 0;
      elChange.textContent = (ch > 0 ? "+" : "") + UI.toPersian(ch.toFixed(2)) + "٪";
      elChange.classList.toggle("is-up", ch >= 0);
      elChange.classList.toggle("is-down", ch < 0);
    }

    // Initial render from server-provided data attributes.
    render({
      price: section.getAttribute("data-price"),
      high_24: section.getAttribute("data-high"),
      low_24: section.getAttribute("data-low"),
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
