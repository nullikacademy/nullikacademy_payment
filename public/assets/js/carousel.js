/* Nullik Academy — Hero carousel (infinite, center-active, AJAX-driven) */
(function () {
  "use strict";

  var INTERVAL = 6000;

  function init() {
    var carousel = document.getElementById("heroCarousel");
    var track = document.getElementById("carouselTrack");
    var bar = document.getElementById("carouselBar");
    if (!carousel || !track) return;

    var items = Array.prototype.slice.call(track.querySelectorAll(".carousel__item"));
    if (!items.length) return;

    var active = 0;
    var paused = false;
    var rafId = null;
    var startTs = 0;
    var elapsed = 0;

    function center(index) {
      var item = items[index];
      var containerCenter = carousel.clientWidth / 2;
      var itemCenter = item.offsetLeft + item.offsetWidth / 2;
      var delta = containerCenter - itemCenter;
      track.style.transform = "translateX(" + delta + "px)";

      items.forEach(function (el, i) { el.classList.toggle("is-active", i === index); });
    }

    function setActive(index, fromUser) {
      active = (index + items.length) % items.length;
      center(active);
      var slug = items[active].getAttribute("data-slug");
      // Tint the progress bar with the active tool's color.
      var color = items[active].style.getPropertyValue("--tool-color");
      if (bar && color) { bar.style.background = color; }
      window.dispatchEvent(new CustomEvent("nullik:tool-change", { detail: { slug: slug, color: color } }));
      if (fromUser) { elapsed = 0; startTs = performance.now(); }
    }

    function next() { setActive(active + 1); }

    // Progress + auto-advance loop
    function loop(ts) {
      if (!startTs) startTs = ts;
      if (!paused) {
        elapsed += ts - startTs;
        var p = Math.min(elapsed / INTERVAL, 1);
        if (bar) bar.style.width = (p * 100) + "%";
        if (p >= 1) {
          elapsed = 0;
          if (bar) bar.style.width = "0%";
          next();
        }
      }
      startTs = ts;
      rafId = requestAnimationFrame(loop);
    }

    function pause() { paused = true; }
    function resume() { paused = false; startTs = performance.now(); }

    // Interactions
    items.forEach(function (el, i) {
      el.addEventListener("click", function () { setActive(i, true); });
    });

    carousel.addEventListener("mouseenter", pause);
    carousel.addEventListener("mouseleave", resume);
    carousel.addEventListener("touchstart", pause, { passive: true });
    carousel.addEventListener("touchend", function () { setTimeout(resume, 2000); }, { passive: true });

    // Pause when hovering / scrolling the plans section.
    var plans = document.getElementById("plans");
    if (plans) {
      plans.addEventListener("mouseenter", pause);
      plans.addEventListener("mouseleave", resume);
      plans.addEventListener("touchstart", pause, { passive: true });
    }
    // Pause while user actively scrolls.
    var scrollTimer = null;
    window.addEventListener("scroll", function () {
      pause();
      clearTimeout(scrollTimer);
      scrollTimer = setTimeout(resume, 900);
    }, { passive: true });

    // Swipe support
    var sx = 0;
    track.addEventListener("touchstart", function (e) { sx = e.touches[0].clientX; }, { passive: true });
    track.addEventListener("touchend", function (e) {
      var dx = e.changedTouches[0].clientX - sx;
      if (Math.abs(dx) > 40) { setActive(active + (dx < 0 ? 1 : -1), true); }
    }, { passive: true });

    // Recenter on resize.
    window.addEventListener("resize", function () { center(active); });

    // Init
    requestAnimationFrame(function () {
      center(0);
      setActive(0, false);
      rafId = requestAnimationFrame(loop);
    });
  }

  if (document.readyState !== "loading") init();
  else document.addEventListener("DOMContentLoaded", init);
})();
