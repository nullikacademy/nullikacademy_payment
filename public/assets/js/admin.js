/* Nullik Academy — Admin panel scripts */
(function () {
  "use strict";

  function $(id) { return document.getElementById(id); }
  function on(elm, ev, fn) { if (elm) elm.addEventListener(ev, fn); }

  /* ---------- Password reveal toggles ---------- */
  function initToggles() {
    document.querySelectorAll("[data-toggle]").forEach(function (btn) {
      on(btn, "click", function () {
        var input = $(btn.getAttribute("data-toggle"));
        if (input) input.type = input.type === "password" ? "text" : "password";
      });
    });
    document.querySelectorAll(".reveal__btn").forEach(function (btn) {
      on(btn, "click", function () {
        var span = btn.parentNode.querySelector(".reveal__masked");
        var shown = span.getAttribute("data-shown") === "1";
        span.textContent = shown ? "••••••••" : span.getAttribute("data-real");
        span.setAttribute("data-shown", shown ? "0" : "1");
        btn.textContent = shown ? "نمایش" : "مخفی";
      });
    });
  }

  /* ---------- Logout ---------- */
  function initLogout() {
    on($("logoutBtn"), "click", function () {
      fetch(NULLIK.baseUrl + "/admin/logout", {
        method: "POST",
        headers: { "X-CSRF-TOKEN": NULLIK.csrf, "X-Requested-With": "XMLHttpRequest" },
        credentials: "same-origin"
      }).then(function (r) { return r.json(); }).then(function (d) {
        window.location.href = (d.data && d.data.redirect) || NULLIK.baseUrl + "/admin/login";
      });
    });
  }

  /* Generic admin POST to an absolute /admin path. */
  function adminPost(path, body, isForm) {
    var headers = { "X-CSRF-TOKEN": NULLIK.csrf, "X-Requested-With": "XMLHttpRequest", "Accept": "application/json" };
    var opts = { method: "POST", headers: headers, credentials: "same-origin" };
    if (body) {
      if (isForm) { opts.body = body; }
      else { headers["Content-Type"] = "application/json"; opts.body = JSON.stringify(body); }
    }
    return fetch(NULLIK.baseUrl + path, opts).then(function (r) {
      return r.json().catch(function () { return {}; }).then(function (d) { return { ok: r.ok, status: r.status, data: d }; });
    });
  }
  function adminSend(method, path, body) {
    var headers = { "X-CSRF-TOKEN": NULLIK.csrf, "X-Requested-With": "XMLHttpRequest", "Content-Type": "application/json", "Accept": "application/json" };
    return fetch(NULLIK.baseUrl + path, {
      method: method, headers: headers, credentials: "same-origin", body: body ? JSON.stringify(body) : null
    }).then(function (r) { return r.json().catch(function () { return {}; }).then(function (d) { return { ok: r.ok, data: d }; }); });
  }

  /* Real login handler (replaces placeholder above). */
  function initLoginReal() {
    var form = $("loginForm");
    if (!form) return;
    form.onsubmit = function (e) {
      e.preventDefault();
      UI.setError("loginError", "");
      var btn = $("loginBtn"); btn.disabled = true; btn.textContent = "در حال ورود...";
      adminPost("/admin/login", {
        username: $("username").value.trim(),
        password: $("password").value,
        remember: $("remember").checked ? 1 : 0
      }).then(function (res) {
        if (res.ok && res.data.success) {
          window.location.href = res.data.data.redirect;
        } else {
          UI.setError("loginError", res.data.message || "خطا در ورود.");
          btn.disabled = false; btn.textContent = "ورود";
        }
      });
    };
  }

  /* ---------- Order status / notes ---------- */
  function initOrderForms() {
    var sf = $("statusForm");
    if (sf) {
      on(sf, "submit", function (e) {
        e.preventDefault();
        var id = sf.getAttribute("data-order");
        adminPost("/admin/orders/" + id + "/status", {
          status: sf.status.value, note: sf.note.value
        }).then(function (res) {
          if (res.ok && res.data.success) { UI.toast("وضعیت به‌روزرسانی شد", "success"); setTimeout(function(){location.reload();}, 700); }
          else UI.toast(res.data.message || "خطا", "error");
        });
      });
    }
    var nf = $("noteForm");
    if (nf) {
      on(nf, "submit", function (e) {
        e.preventDefault();
        var id = nf.getAttribute("data-order");
        var note = nf.note.value.trim();
        if (!note) return;
        adminPost("/admin/orders/" + id + "/notes", { note: note }).then(function (res) {
          if (res.ok && res.data.success) {
            var d = res.data.data;
            var li = document.createElement("li");
            li.className = "note-item";
            li.innerHTML = "<p>" + d.note + "</p><span class='note-item__meta'>" + d.admin_name + " · " + UI.toPersian(d.created_at) + "</span>";
            $("noteList").insertBefore(li, $("noteList").firstChild);
            nf.note.value = "";
            UI.toast("یادداشت ثبت شد", "success");
          } else UI.toast(res.data.message || "خطا", "error");
        });
      });
    }
  }

  /* ---------- Modal helpers ---------- */
  function openModal(id) { var m = $(id); if (m) { m.classList.add("is-open"); m.setAttribute("aria-hidden", "false"); } }
  function closeModal(m) { m.classList.remove("is-open"); m.setAttribute("aria-hidden", "true"); }
  function initModals() {
    document.querySelectorAll(".admin-modal").forEach(function (m) {
      m.querySelectorAll("[data-modal-close]").forEach(function (b) { on(b, "click", function () { closeModal(m); }); });
    });
  }

  /* ---------- Tools CRUD ---------- */
  function initTools() {
    var form = $("toolForm");
    if (!form) return;
    var colorText = $("toolColor");
    var colorPicker = $("toolColorPicker");

    // Keep the color picker and the text field in sync.
    function isHex(v) { return /^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/.test(v); }
    if (colorPicker) {
      on(colorPicker, "input", function () { colorText.value = colorPicker.value; });
      on(colorText, "input", function () { if (isHex(colorText.value)) colorPicker.value = colorText.value; });
    }

    function fill(data) {
      $("toolId").value = data.id || "";
      $("toolName").value = data.name || "";
      $("toolSlug").value = data.slug || "";
      $("toolDesc").value = data.description || "";
      var color = data.color || "#0076FA";
      colorText.value = color;
      if (colorPicker && isHex(color)) colorPicker.value = color;
      $("toolSort").value = data.sort_order || 0;
      $("toolStatus").value = data.status || "active";
      $("toolModalTitle").textContent = data.id ? "ویرایش ابزار" : "ابزار جدید";
    }
    on($("newToolBtn"), "click", function () { fill({}); openModal("toolModal"); });
    document.querySelectorAll("[data-edit-tool]").forEach(function (b) {
      on(b, "click", function () { fill(JSON.parse(b.closest("tr").getAttribute("data-tool"))); openModal("toolModal"); });
    });
    document.querySelectorAll("[data-toggle-tool]").forEach(function (b) {
      on(b, "click", function () {
        adminPost("/admin/tools/" + b.getAttribute("data-toggle-tool") + "/toggle", {}).then(function (res) {
          if (res.ok && res.data.success) location.reload(); else UI.toast("خطا", "error");
        });
      });
    });
    document.querySelectorAll("[data-delete-tool]").forEach(function (b) {
      on(b, "click", function () {
        if (!confirm("حذف این ابزار؟")) return;
        adminSend("DELETE", "/admin/tools/" + b.getAttribute("data-delete-tool")).then(function (res) {
          if (res.ok && res.data.success) { UI.toast("حذف شد", "success"); b.closest("tr").remove(); }
          else UI.toast(res.data.message || "خطا", "error");
        });
      });
    });
    on(form, "submit", function (e) {
      e.preventDefault();
      UI.setError("toolError", "");
      var id = $("toolId").value;
      var fd = new FormData(form);
      fd.append("_token", NULLIK.csrf);
      var path = id ? "/admin/tools/" + id : "/admin/tools";
      adminPost(path, fd, true).then(function (res) {
        if (res.ok && res.data.success) { UI.toast("ذخیره شد", "success"); setTimeout(function(){location.reload();}, 600); }
        else UI.setError("toolError", res.data.message || "خطا در ذخیره.");
      });
    });
  }

  /* ---------- Plans CRUD ---------- */
  function initPlans() {
    var form = $("planForm");
    if (!form) return;
    var rateEl = $("usdt-rate");
    var rate = rateEl ? (JSON.parse(rateEl.textContent).rate || 0) : 0;

    function recalc() {
      var usdt = parseFloat($("planUsdt").value) || 0;
      $("planIrtPreview").value = UI.formatMoney(usdt * rate) + " تومان";
    }
    on($("planUsdt"), "input", recalc);

    function fill(d) {
      $("planId").value = d.id || "";
      $("planTool").value = d.tool_id || ($("planTool").options[0] ? $("planTool").options[0].value : "");
      $("planName").value = d.name || "";
      $("planBadge").value = d.badge || "";
      $("planDuration").value = d.duration || "";
      $("planDays").value = d.duration_days || 30;
      $("planUsdt").value = d.price_usdt || "";
      $("planMode").value = d.mode_type || "email_password";
      $("planStatus").value = d.status || "active";
      $("planSort").value = d.sort_order || 0;
      $("planFeatured").checked = !!d.is_featured;
      $("planModalTitle").textContent = d.id ? "ویرایش پلن" : "پلن جدید";
      recalc();
    }
    on($("newPlanBtn"), "click", function () { fill({}); openModal("planModal"); });
    document.querySelectorAll("[data-edit-plan]").forEach(function (b) {
      on(b, "click", function () { fill(JSON.parse(b.closest("tr").getAttribute("data-plan"))); openModal("planModal"); });
    });
    document.querySelectorAll("[data-delete-plan]").forEach(function (b) {
      on(b, "click", function () {
        if (!confirm("حذف این پلن؟")) return;
        adminSend("DELETE", "/admin/plans/" + b.getAttribute("data-delete-plan")).then(function (res) {
          if (res.ok && res.data.success) { UI.toast("حذف شد", "success"); b.closest("tr").remove(); }
          else UI.toast(res.data.message || "خطا", "error");
        });
      });
    });
    on(form, "submit", function (e) {
      e.preventDefault();
      UI.setError("planError", "");
      var id = $("planId").value;
      var body = {
        tool_id: $("planTool").value, name: $("planName").value, badge: $("planBadge").value,
        duration: $("planDuration").value, duration_days: $("planDays").value,
        price_usdt: $("planUsdt").value, mode_type: $("planMode").value,
        status: $("planStatus").value, sort_order: $("planSort").value,
        is_featured: $("planFeatured").checked ? 1 : 0
      };
      var p = id ? adminSend("PUT", "/admin/plans/" + id, body) : adminSend("POST", "/admin/plans", body);
      p.then(function (res) {
        if (res.ok && res.data.success) { UI.toast("ذخیره شد", "success"); setTimeout(function(){location.reload();}, 600); }
        else UI.setError("planError", res.data.message || "خطا در ذخیره.");
      });
    });
  }

  /* ---------- Users CRUD ---------- */
  function initUsers() {
    var form = $("userForm");
    if (!form) return;
    function fill(d) {
      $("userId").value = d.id || "";
      $("userUsername").value = d.username || "";
      $("userUsername").disabled = !!d.id;
      $("userFullName").value = d.full_name || "";
      $("userPassword").value = "";
      $("userRole").value = d.role || "manager";
      $("userStatus").value = d.status || "active";
      $("userModalTitle").textContent = d.id ? "ویرایش مدیر" : "مدیر جدید";
    }
    on($("newUserBtn"), "click", function () { fill({}); openModal("userModal"); });
    document.querySelectorAll("[data-edit-user]").forEach(function (b) {
      on(b, "click", function () { fill(JSON.parse(b.closest("tr").getAttribute("data-user"))); openModal("userModal"); });
    });
    document.querySelectorAll("[data-delete-user]").forEach(function (b) {
      on(b, "click", function () {
        if (!confirm("حذف این مدیر؟")) return;
        adminSend("DELETE", "/admin/users/" + b.getAttribute("data-delete-user")).then(function (res) {
          if (res.ok && res.data.success) { UI.toast("حذف شد", "success"); b.closest("tr").remove(); }
          else UI.toast(res.data.message || "خطا", "error");
        });
      });
    });
    on(form, "submit", function (e) {
      e.preventDefault();
      UI.setError("userError", "");
      var id = $("userId").value;
      var body = {
        username: $("userUsername").value.trim(), full_name: $("userFullName").value.trim(),
        password: $("userPassword").value, role: $("userRole").value, status: $("userStatus").value
      };
      var p = id ? adminSend("PUT", "/admin/users/" + id, body) : adminSend("POST", "/admin/users", body);
      p.then(function (res) {
        if (res.ok && res.data.success) { UI.toast("ذخیره شد", "success"); setTimeout(function(){location.reload();}, 600); }
        else UI.setError("userError", res.data.message || "خطا در ذخیره.");
      });
    });
  }

  /* ---------- Settings ---------- */
  function initSettings() {
    var form = $("settingsForm");
    if (form) {
      on(form, "submit", function (e) {
        e.preventDefault();
        var body = {
          site_title: form.site_title.value,
          site_description: form.site_description.value,
          online_gateway_enabled: form.online_gateway_enabled.checked ? 1 : 0
        };
        adminPost("/admin/settings", body).then(function (res) {
          UI.toast(res.data.message || (res.ok ? "ذخیره شد" : "خطا"), res.ok ? "success" : "error");
        });
      });
    }
    on($("refreshPriceBtn"), "click", function () {
      var b = $("refreshPriceBtn"); b.disabled = true; b.textContent = "در حال بروزرسانی...";
      adminPost("/admin/settings/refresh-price", {}).then(function (res) {
        b.disabled = false; b.textContent = "بروزرسانی نرخ و قیمت پلن‌ها";
        if (res.ok && res.data.success) { UI.toast(res.data.message, "success"); setTimeout(function(){location.reload();}, 900); }
        else UI.toast(res.data.message || "خطا", "error");
      });
    });
  }

  /* ---------- Dashboard charts (canvas, no deps) ---------- */
  function initCharts() {
    var dataEl = $("chart-data");
    if (!dataEl) return;
    var data = JSON.parse(dataEl.textContent);
    drawBars($("chartDays"), data.days, "#42A5FF");
    drawBars($("chartTools"), data.tools, "#0076FA");
  }
  function drawBars(canvas, items, color) {
    if (!canvas || !items) return;
    var dpr = window.devicePixelRatio || 1;
    var w = canvas.clientWidth, h = canvas.height;
    canvas.width = w * dpr; canvas.height = h * dpr;
    var ctx = canvas.getContext("2d"); ctx.scale(dpr, dpr);
    ctx.clearRect(0, 0, w, h);
    if (!items.length) { ctx.fillStyle = "#6B7B9C"; ctx.font = "14px Vazirmatn"; ctx.textAlign = "center"; ctx.fillText("داده‌ای موجود نیست", w/2, h/2); return; }
    var max = Math.max.apply(null, items.map(function (i) { return i.value; })) || 1;
    var pad = 30, bw = (w - pad * 2) / items.length * 0.6, gap = (w - pad * 2) / items.length;
    items.forEach(function (it, i) {
      var bh = (h - pad * 2) * (it.value / max);
      var x = pad + i * gap + (gap - bw) / 2, y = h - pad - bh;
      var grad = ctx.createLinearGradient(0, y, 0, h - pad);
      grad.addColorStop(0, color); grad.addColorStop(1, "rgba(0,118,250,0.2)");
      ctx.fillStyle = grad;
      roundRect(ctx, x, y, bw, bh, 6); ctx.fill();
      ctx.fillStyle = "#9FB0CE"; ctx.font = "10px Vazirmatn"; ctx.textAlign = "center";
      ctx.fillText(UI.toPersian(it.value), x + bw / 2, y - 6);
    });
  }
  function roundRect(ctx, x, y, w, h, r) {
    if (h < 0) h = 0; r = Math.min(r, h / 2 || 0);
    ctx.beginPath();
    ctx.moveTo(x + r, y); ctx.arcTo(x + w, y, x + w, y + h, r);
    ctx.arcTo(x + w, y + h, x, y + h, r); ctx.arcTo(x, y + h, x, y, r);
    ctx.arcTo(x, y, x + w, y, r); ctx.closePath();
  }

  /* ---------- Sidebar (mobile) ---------- */
  function initSidebar() {
    on($("adminBurger"), "click", function () {
      var sb = $("adminSidebar"); if (sb) sb.classList.toggle("is-open");
    });
  }

  function init() {
    initToggles();
    initLoginReal();
    initLogout();
    initSidebar();
    initModals();
    initOrderForms();
    initTools();
    initPlans();
    initUsers();
    initSettings();
    initCharts();
  }

  if (document.readyState !== "loading") init();
  else document.addEventListener("DOMContentLoaded", init);
})();
