/* Nullik Academy — App orchestration (tool info, plans, navbar) */
(function () {
  "use strict";

  function toolBySlug(slug) {
    for (var i = 0; i < NULLIK.tools.length; i++) {
      if (NULLIK.tools[i].slug === slug) return NULLIK.tools[i];
    }
    return null;
  }

  /* ---------- Tool info (single-line description) ---------- */
  function updateToolInfo(tool) {
    var box = document.getElementById("toolInfo");
    var desc = document.getElementById("toolInfoDesc");
    if (!box || !desc || !tool) return;

    box.classList.add("is-fading");
    setTimeout(function () {
      desc.textContent = tool.description || "";
      box.classList.remove("is-fading");
    }, 220);
  }

  /* ---------- Plans ---------- */
  function planCardHTML(plan, tool) {
    var color = (tool && tool.color) || "#42A5FF";
    var logo = (tool && tool.logo) || "";
    var badge = plan.badge ? '<span class="plan-card__badge">' + plan.badge + "</span>" : "";
    return (
      '<article class="plan-card" data-plan-id="' + plan.id + '" tabindex="0" style="--tool-color:' + color + '">' +
        badge +
        '<span class="plan-card__check">✓</span>' +
        '<span class="plan-card__tool"><img src="' + logo + '" alt="' + (tool ? tool.name : "") + '" width="36" height="36"></span>' +
        '<h3 class="plan-card__name">' + plan.name + "</h3>" +
        '<span class="plan-card__duration">' + plan.duration + "</span>" +
        '<div class="plan-card__price">' +
          '<span class="plan-card__irt">' + plan.price_irt_formatted + "</span>" +
          '<span class="plan-card__irt-unit">تومان</span>' +
        "</div>" +
        '<span class="plan-card__usdt">' + UI.toPersian(plan.price_usdt) + " USDT</span>" +
        '<button class="btn btn--primary btn--block plan-card__cta">انتخاب و خرید</button>' +
      "</article>"
    );
  }

  function renderPlans(plans, tool) {
    var grid = document.getElementById("plansGrid");
    if (!grid) return;
    if (!plans.length) {
      grid.innerHTML = '<p class="section-sub" style="grid-column:1/-1;text-align:center">پلنی برای این ابزار موجود نیست.</p>';
      return;
    }
    grid.innerHTML = plans.map(function (p) { return planCardHTML(p, tool); }).join("");

    Array.prototype.forEach.call(grid.querySelectorAll(".plan-card"), function (card) {
      var id = Number(card.getAttribute("data-plan-id"));
      var plan = plans.filter(function (p) { return p.id === id; })[0];
      function choose() {
        Array.prototype.forEach.call(grid.querySelectorAll(".plan-card"), function (c) { c.classList.remove("is-selected"); });
        card.classList.add("is-selected");
        NULLIK.state.selectedPlan = plan;
        NULLIK.state.selectedTool = tool.slug;
        if (window.Checkout) window.Checkout.open(plan, tool);
      }
      card.addEventListener("click", choose);
      card.addEventListener("keydown", function (e) { if (e.key === "Enter") choose(); });
    });
  }

  function loadPlans(slug) {
    var grid = document.getElementById("plansGrid");
    var tool = toolBySlug(slug);
    if (!grid || !tool) return;

    if (NULLIK.state.plansCache[slug]) {
      renderPlans(NULLIK.state.plansCache[slug], tool);
      return;
    }

    grid.innerHTML = '<div class="plan-card skeleton-card"></div>'.repeat(4);

    API.get("tools/" + slug + "/plans").then(function (res) {
      if (res.ok && res.data && res.data.data) {
        var plans = res.data.data.plans || [];
        NULLIK.state.plansCache[slug] = plans;
        renderPlans(plans, tool);
      } else {
        grid.innerHTML = '<p class="section-sub" style="grid-column:1/-1;text-align:center">خطا در بارگذاری پلن‌ها.</p>';
      }
    });
  }

  window.addEventListener("nullik:tool-change", function (e) {
    var slug = e.detail.slug;
    var tool = toolBySlug(slug);
    NULLIK.state.selectedTool = slug;
    updateToolInfo(tool);
    loadPlans(slug);
  });

  /* ---------- Navbar ---------- */
  function initNavbar() {
    var navbar = document.getElementById("navbar");
    var burger = document.getElementById("navBurger");
    if (navbar) {
      var onScroll = function () { navbar.classList.toggle("is-scrolled", window.scrollY > 20); };
      window.addEventListener("scroll", onScroll, { passive: true });
      onScroll();
    }
    if (burger && navbar) {
      burger.addEventListener("click", function () {
        var open = navbar.classList.toggle("is-menu-open");
        burger.setAttribute("aria-expanded", open ? "true" : "false");
      });
    }
    // Smooth scroll for in-page links
    Array.prototype.forEach.call(document.querySelectorAll('a[href^="#"]'), function (a) {
      a.addEventListener("click", function (e) {
        var target = document.querySelector(a.getAttribute("href"));
        if (target) {
          e.preventDefault();
          target.scrollIntoView({ behavior: "smooth", block: "start" });
          if (navbar) navbar.classList.remove("is-menu-open");
        }
      });
    });
  }

  function init() {
    initNavbar();
    // Kick off first tool's plans even before carousel fires (fast paint).
    if (NULLIK.state.selectedTool) {
      var tool = toolBySlug(NULLIK.state.selectedTool);
      if (tool) { updateToolInfo(tool); loadPlans(tool.slug); }
    }
  }

  if (document.readyState !== "loading") init();
  else document.addEventListener("DOMContentLoaded", init);
})();
