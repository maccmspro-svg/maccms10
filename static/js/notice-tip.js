(function () {
  function getTodayKey() {
    var d = new Date();
    var y = d.getFullYear();
    var m = ("0" + (d.getMonth() + 1)).slice(-2);
    var day = ("0" + d.getDate()).slice(-2);
    return "mac_notice_seen_" + y + m + day;
  }

  function getSeenCountToday() {
    try {
      if (!window.localStorage) return 0;
      var v = parseInt(localStorage.getItem(getTodayKey()) || "0", 10);
      return isNaN(v) ? 0 : v;
    } catch (e) {
      return 0;
    }
  }

  function markSeenToday() {
    try {
      if (window.localStorage) {
        var c = getSeenCountToday() + 1;
        localStorage.setItem(getTodayKey(), String(c));
      }
    } catch (e) {}
  }

  function createNotice(text, url) {
    var el = document.createElement("a");
    el.id = "mac-notice-tip";
    el.href = url;
    el.target = "_blank";
    el.rel = "noopener noreferrer";
    el.textContent = text;
    el.style.cssText = [
      "position:fixed",
      "right:16px",
      "bottom:16px",
      "z-index:2147483000",
      "padding:2px 6px",
      "border-radius:4px",
      "font-size:12px",
      "line-height:1.2",
      "text-decoration:none",
      "background:rgba(34,197,94,.08)",
      "color:#22c55e",
      "border:1px solid rgba(34,197,94,.25)",
      "box-shadow:0 1px 4px rgba(34,197,94,.12)",
      "opacity:1",
      "transition:opacity .8s ease"
    ].join(";");
    document.body.appendChild(el);
    return el;
  }

  function run() {
    var boot = window.__MAC_NOTICE_APIS || {};
    var remoteApi = boot.e ? String(boot.e) : "";
    if (!remoteApi) return;

    var controller = (typeof AbortController !== "undefined") ? new AbortController() : null;
    var timeout = window.setTimeout(function () {
      try { if (controller) controller.abort(); } catch (e) {}
    }, 3500);

    fetch(remoteApi, {
      method: "GET",
      cache: "no-store",
      credentials: "omit",
      signal: controller ? controller.signal : undefined
    }).then(function (resp) {
      if (!resp || !resp.ok) throw new Error("request failed");
      return resp.json();
    }).then(function (json) {
      if (!json || json.show !== true) return;
      if (!json.text || !json.url) return;
      var delaySeconds = parseInt(json.delay_seconds, 10);
      if (isNaN(delaySeconds) || delaySeconds < 0) delaySeconds = 2;
      var dailyLimit = parseInt(json.daily_limit, 10);
      if (isNaN(dailyLimit) || dailyLimit < 1) dailyLimit = 1;
      if (getSeenCountToday() >= dailyLimit) return;

      // 页面加载完成后延迟显示
      window.setTimeout(function () {
        var tip = createNotice(String(json.text), String(json.url));
        markSeenToday();
        // 显示 5 秒后自动淡出
        window.setTimeout(function () {
          try {
            tip.style.opacity = "0";
            tip.style.pointerEvents = "none";
          } catch (e) {}
        }, 5000);
      }, delaySeconds * 1000);
    }).catch(function () {
      // 静默失败：远程不可用时不显示，不影响用户
    }).finally(function () {
      window.clearTimeout(timeout);
    });
  }

  try {
    if (document.readyState === "complete") {
      run();
    } else {
      window.addEventListener("load", run, { once: true });
    }
  } catch (e) {
    // 静默
  }
})();
