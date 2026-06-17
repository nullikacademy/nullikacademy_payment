/* Nullik Academy — Live USDT ticker with animated counters */
(function () {
  "use strict";

  function init() {
    var section = document.getElementById("usdtTicker");
    if (!section) return;

    var elPrice = document.getElementById("tkPrice");

    function render(t) {
      if (elPrice) UI.animateCount(elPrice, t.price, UI.formatMoney);
    }

    // Initial render from server-provided data attribute.
    render({ price: section.getAttribute("data-price") });

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
