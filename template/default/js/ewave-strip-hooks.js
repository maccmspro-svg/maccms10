/**
 * 主页面防护：移除常见第三方统计脚本节点、隐藏「站长统计」文字链。
 * 无法清除跨域 iframe 内的广告（由后台解析地址决定，需在后台更换干净解析）。
 */
(function () {
  var blocked = /cnzz\.com|51\.la\/|umeng\.com|googletagmanager\.com|google-analytics\.com|hm\.baidu\.com|bdstatic\.com\/hm\.js/i;

  function stripScriptsIn(root) {
    if (!root || !root.querySelectorAll) return;
    root.querySelectorAll('script[src]').forEach(function (el) {
      try {
        if (blocked.test(el.src)) el.remove();
      } catch (e) {}
    });
  }

  function hideStatsLinks() {
    document.querySelectorAll('a').forEach(function (a) {
      try {
        var t = a.textContent || '';
        if (t.indexOf('站长统计') !== -1 || (a.href && /cnzz\.com/i.test(a.href))) {
          var box = a.closest('span,div,p,li,footer') || a;
          box.style.setProperty('display', 'none', 'important');
        }
      } catch (e) {}
    });
  }

  function stripIframeIfAccessible() {
    var ids = ['player_if', 'iframePlayer'];
    for (var i = 0; i < ids.length; i++) {
      var fr = document.getElementById(ids[i]) || document.querySelector('iframe[name="' + ids[i] + '"]');
      if (!fr) continue;
      try {
        var doc = fr.contentDocument || (fr.contentWindow && fr.contentWindow.document);
        if (doc) stripScriptsIn(doc);
      } catch (e) {}
    }
    document.querySelectorAll('iframe').forEach(function (fr) {
      try {
        var doc = fr.contentDocument || (fr.contentWindow && fr.contentWindow.document);
        if (doc) stripScriptsIn(doc);
      } catch (e) {}
    });
  }

  function run() {
    stripScriptsIn(document);
    hideStatsLinks();
    stripIframeIfAccessible();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', run);
  } else {
    run();
  }

  var obs = new MutationObserver(function () {
    stripScriptsIn(document);
    hideStatsLinks();
  });
  try {
    obs.observe(document.documentElement, { childList: true, subtree: true });
  } catch (e) {}

  setTimeout(function () {
    run();
    stripIframeIfAccessible();
  }, 1500);
  setTimeout(function () {
    stripIframeIfAccessible();
  }, 4000);
})();
