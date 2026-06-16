/* Nullik Academy — fetch API client */
(function () {
  "use strict";

  function buildUrl(endpoint) {
    var clean = String(endpoint).replace(/^\/+/, "").replace(/^api\//, "");
    return NULLIK.baseUrl + "/api/" + clean;
  }

  function request(method, endpoint, body, isForm) {
    var headers = {
      "X-CSRF-TOKEN": NULLIK.csrf,
      "X-Requested-With": "XMLHttpRequest",
      "Accept": "application/json"
    };
    var opts = { method: method, headers: headers, credentials: "same-origin" };

    if (body) {
      if (isForm) {
        opts.body = body; // FormData — browser sets multipart boundary
      } else {
        headers["Content-Type"] = "application/json";
        opts.body = JSON.stringify(body);
      }
    }

    return fetch(buildUrl(endpoint), opts)
      .then(function (res) {
        return res.json().catch(function () { return {}; }).then(function (data) {
          return { ok: res.ok, status: res.status, data: data };
        });
      })
      .catch(function () {
        return { ok: false, status: 0, data: { message: "خطا در ارتباط با سرور." } };
      });
  }

  window.API = {
    get: function (endpoint) { return request("GET", endpoint, null, false); },
    post: function (endpoint, body) { return request("POST", endpoint, body, false); },
    upload: function (endpoint, formData) { return request("POST", endpoint, formData, true); }
  };
})();
