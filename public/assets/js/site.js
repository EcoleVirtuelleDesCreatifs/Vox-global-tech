/* VOX TECH GLOBAL — interactions du site public (JavaScript natif, sans dépendance).
   Thème clair/sombre, en-tête, méga-menu, recherche instantanée, slider « À la une »,
   rails, animations d'apparition, sommaire, partage, mesure de lecture. */
(function () {
  "use strict";
  var $ = function (s, c) { return (c || document).querySelector(s); };
  var $$ = function (s, c) { return Array.prototype.slice.call((c || document).querySelectorAll(s)); };
  var REDUCED = window.matchMedia && window.matchMedia("(prefers-reduced-motion: reduce)").matches;
  var root = document.documentElement;
  var site = $(".site");

  /* ---------- Thème ---------- */
  function isDark() { var a = root.getAttribute("data-theme"); return a ? a === "dark" : !!(window.matchMedia && window.matchMedia("(prefers-color-scheme: dark)").matches); }
  $$("[data-theme-toggle]").forEach(function (b) {
    b.addEventListener("click", function () {
      var t = isDark() ? "light" : "dark"; root.setAttribute("data-theme", t);
      try { localStorage.setItem("vx-theme", t); } catch (e) {}
    });
  });

  /* ---------- Toast ---------- */
  var toastEl = $(".vx-toast"), toastT;
  function toast(msg) { if (!toastEl) return; toastEl.textContent = msg; toastEl.hidden = false; clearTimeout(toastT); toastT = setTimeout(function () { toastEl.hidden = true; }, 2800); }

  /* ---------- En-tête : compact, masqué au défilement ---------- */
  var mh = $("#mh"), lastY = 0, drawer = $("#drawer"), burger = $("[data-drawer]");
  var progress = $(".progress-read"), backTop = $("[data-top]"), parallax = $$("[data-parallax]");
  function onScroll() {
    var y = window.scrollY || 0;
    if (mh) {
      mh.classList.toggle("compact", y > 40);
      var menuOpen = (drawer && !drawer.hidden) || openMega || (so && !so.hidden);
      mh.classList.toggle("hide", !menuOpen && y > 220 && y > lastY);
    }
    if (backTop) backTop.hidden = y < 600;
    if (progress) {
      var art = $(".ap-main") || document.body, r = art.getBoundingClientRect();
      var p = Math.min(1, Math.max(0, (-r.top + window.innerHeight * .3) / Math.max(1, r.height - window.innerHeight * .4)));
      progress.style.transform = "scaleX(" + p + ")";
    }
    if (!REDUCED) parallax.forEach(function (el) { var k = parseFloat(el.getAttribute("data-parallax")) || 0; var rr = el.parentNode.getBoundingClientRect(); el.style.transform = "translate3d(0," + (-(rr.top) * k * .15) + "px,0)"; });
    maxScroll = Math.max(maxScroll, Math.round((y + window.innerHeight) / Math.max(1, document.documentElement.scrollHeight) * 100));
    lastY = y;
  }
  var ticking = false, maxScroll = 0;
  window.addEventListener("scroll", function () { if (!ticking) { ticking = true; requestAnimationFrame(function () { onScroll(); ticking = false; }); } }, { passive: true });
  if (backTop) backTop.addEventListener("click", function () { window.scrollTo({ top: 0, behavior: REDUCED ? "auto" : "smooth" }); });

  /* ---------- Tiroir mobile ---------- */
  if (burger && drawer) burger.addEventListener("click", function () {
    var open = drawer.hidden; drawer.hidden = !open; burger.setAttribute("aria-expanded", open ? "true" : "false");
    document.body.style.overflow = open ? "hidden" : ""; mh.classList.remove("hide");
  });

  /* ---------- Méga-menu ---------- */
  var openMega = null, megaT;
  function showMega(id) {
    clearTimeout(megaT);
    $$("[data-mega-panel]").forEach(function (p) { p.hidden = p.getAttribute("data-mega-panel") !== id; });
    $$("[data-mega]").forEach(function (a) { var on = a.getAttribute("data-mega") === id; a.classList.toggle("open", on); a.setAttribute("aria-expanded", on ? "true" : "false"); });
    openMega = id;
  }
  function hideMega(delay) {
    clearTimeout(megaT);
    megaT = setTimeout(function () { $$("[data-mega-panel]").forEach(function (p) { p.hidden = true; }); $$("[data-mega]").forEach(function (a) { a.classList.remove("open"); a.setAttribute("aria-expanded", "false"); }); openMega = null; }, delay || 0);
  }
  $$("[data-mega]").forEach(function (a) {
    a.addEventListener("mouseenter", function () { showMega(a.getAttribute("data-mega")); });
    a.addEventListener("focus", function () { showMega(a.getAttribute("data-mega")); });
  });
  $$("[data-mega-close]").forEach(function (a) { a.addEventListener("mouseenter", function () { hideMega(0); }); });
  $$("[data-mega-panel]").forEach(function (p) { p.addEventListener("mouseenter", function () { clearTimeout(megaT); }); });
  if (mh) mh.addEventListener("mouseleave", function () { hideMega(180); });

  /* ---------- Recherche instantanée ---------- */
  var so = $("#search-overlay");
  if (so) {
    var input = $("#so-in"), res = $(".so-res", so), sugg = $(".so-sugg", so), api = so.getAttribute("data-api"), timer, ctl;
    var openSearch = function () { so.hidden = false; document.body.style.overflow = "hidden"; setTimeout(function () { input.focus(); }, 30); };
    var closeSearch = function () { so.hidden = true; document.body.style.overflow = ""; };
    $$("[data-search-open]").forEach(function (b) { b.addEventListener("click", openSearch); });
    $$("[data-search-close]").forEach(function (b) { b.addEventListener("click", closeSearch); });
    so.addEventListener("click", function (e) { if (e.target === so) closeSearch(); });
    $$("[data-suggest]", so).forEach(function (b) { b.addEventListener("click", function () { input.value = b.getAttribute("data-suggest"); run(); input.focus(); }); });
    var esc = function (s) { return String(s).replace(/[&<>"']/g, function (c) { return { "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[c]; }); };
    var run = function () {
      var q = input.value.trim();
      if (q.length < 2) { res.hidden = true; sugg.hidden = false; return; }
      if (ctl) ctl.abort(); ctl = window.AbortController ? new AbortController() : null;
      fetch(api + "?q=" + encodeURIComponent(q), { headers: { Accept: "application/json" }, signal: ctl ? ctl.signal : undefined })
        .then(function (r) { return r.json(); }).then(function (d) {
          sugg.hidden = true; res.hidden = false;
          if (!d.results.length) { res.innerHTML = '<p class="muted p-3 mb-0">Aucun résultat pour « ' + esc(q) + ' ».</p>'; return; }
          res.innerHTML = d.results.map(function (a) { return '<a class="so-item" href="' + esc(a.url) + '"><span class="type">' + esc(a.type) + '</span><span class="t">' + esc(a.title) + '</span><span class="meta">' + esc(a.meta) + "</span></a>"; }).join("")
            + '<a class="so-all" href="' + esc(d.all) + '">Voir tous les résultats <svg class="svg-i"><use href="#i-arrow"/></svg></a>';
        }).catch(function () {});
    };
    input.addEventListener("input", function () { clearTimeout(timer); timer = setTimeout(run, 180); });
    document.addEventListener("keydown", function (e) {
      var tag = (e.target.tagName || "").toLowerCase();
      if (e.key === "/" && tag !== "input" && tag !== "textarea" && tag !== "select") { e.preventDefault(); openSearch(); }
      if (e.key === "Escape") { if (!so.hidden) closeSearch(); hideMega(0); if (drawer && !drawer.hidden) burger.click(); }
    });
  }

  /* ---------- Slider « À la une » ---------- */
  $$("[data-slider]").forEach(function (hs) {
    var slides = $$("[data-slide]", hs), steps = $$("[data-go]", hs), stage = $(".hs-stage", hs);
    var n = slides.length, idx = 0, progressV = 0, last = 0, paused = false, stopped = REDUCED, drag = 0, dragging = false, moved = false, x0 = 0, y0 = 0, t0 = 0, DUR = 7000;
    if (stopped) hs.classList.add("stopped");
    function rel(i) { var r = (i - idx) % n; if (r > n / 2) r -= n; if (r < -n / 2) r += n; return r; }
    function layout() {
      slides.forEach(function (s, i) {
        var r = rel(i), vis = Math.abs(r) <= 1;
        s.style.transform = "translate3d(" + (r * 100 + drag) + "%,0,0)"; s.style.visibility = vis ? "visible" : "hidden";
        if (!vis) s.style.transition = "none"; else s.style.transition = "";
        var bg = $(".hs-bg", s); if (bg) bg.style.transform = "translate3d(" + (-(r * 100 + drag) * .45) + "%,0,0)";
        s.classList.toggle("on", i === idx); s.setAttribute("aria-hidden", i === idx ? "false" : "true");
        $$("a", s).forEach(function (a) { if (!a.classList.contains("hs-link")) a.tabIndex = i === idx ? 0 : -1; });
      });
      steps.forEach(function (b, i) { b.classList.toggle("on", i === idx); b.setAttribute("aria-current", i === idx ? "true" : "false"); var bar = $(".hs-bar i", b); if (bar) bar.style.transform = "scaleX(" + (i === idx ? progressV : 0) + ")"; });
    }
    function go(i) { idx = ((i % n) + n) % n; progressV = 0; layout(); }
    steps.forEach(function (b) { b.addEventListener("click", function () { go(+b.getAttribute("data-go")); }); });
    var nx = $("[data-next]", hs), pv = $("[data-prev]", hs), pa = $("[data-pause]", hs);
    if (nx) nx.addEventListener("click", function () { go(idx + 1); });
    if (pv) pv.addEventListener("click", function () { go(idx - 1); });
    if (pa) pa.addEventListener("click", function () { stopped = !stopped; hs.classList.toggle("stopped", stopped); pa.setAttribute("aria-label", stopped ? "Relancer le défilement" : "Mettre en pause"); });
    hs.addEventListener("mouseenter", function () { paused = true; }); hs.addEventListener("mouseleave", function () { paused = false; });
    hs.addEventListener("focusin", function () { paused = true; }); hs.addEventListener("focusout", function () { paused = false; });
    hs.addEventListener("keydown", function (e) { if (e.key === "ArrowRight") go(idx + 1); if (e.key === "ArrowLeft") go(idx - 1); });
    stage.addEventListener("pointerdown", function (e) { if (e.button !== undefined && e.button !== 0) return; dragging = true; moved = false; x0 = e.clientX; y0 = e.clientY; t0 = performance.now(); });
    stage.addEventListener("pointermove", function (e) {
      if (!dragging) return; var dx = e.clientX - x0, dy = e.clientY - y0;
      if (!moved && Math.abs(dy) > Math.abs(dx) && Math.abs(dy) > 8) { dragging = false; drag = 0; layout(); return; }
      if (Math.abs(dx) > 6) { moved = true; stage.classList.add("dragging"); }
      drag = dx / (hs.offsetWidth || 1) * 100; layout();
    });
    var up = function () {
      if (!dragging) return; dragging = false; stage.classList.remove("dragging");
      var v = drag / Math.max(1, performance.now() - t0) * 100;
      if (drag < -18 || v < -6) go(idx + 1); else if (drag > 18 || v > 6) go(idx - 1);
      drag = 0; layout();
    };
    window.addEventListener("pointerup", up); stage.addEventListener("pointercancel", up);
    stage.addEventListener("click", function (e) { if (moved) { e.preventDefault(); e.stopPropagation(); moved = false; } }, true);
    stage.addEventListener("dragstart", function (e) { e.preventDefault(); });
    function loop(t) {
      var dt = last ? Math.min(t - last, 100) : 0; last = t;
      if (!paused && !stopped && !dragging && !document.hidden && n > 1) {
        progressV += dt / DUR; if (progressV >= 1) go(idx + 1);
        var cur = steps[idx] && $(".hs-bar i", steps[idx]); if (cur) cur.style.transform = "scaleX(" + progressV + ")";
      }
      requestAnimationFrame(loop);
    }
    layout(); requestAnimationFrame(loop);
  });

  /* ---------- Rails horizontaux ---------- */
  $$("[data-rail]").forEach(function (rail) {
    var tr = $(".rail-track", rail), p = $("[data-rail-prev]", rail), nx = $("[data-rail-next]", rail);
    var upd = function () { p.disabled = tr.scrollLeft < 8; nx.disabled = tr.scrollLeft + tr.clientWidth > tr.scrollWidth - 8; };
    p.addEventListener("click", function () { tr.scrollBy({ left: -tr.clientWidth * .8, behavior: REDUCED ? "auto" : "smooth" }); });
    nx.addEventListener("click", function () { tr.scrollBy({ left: tr.clientWidth * .8, behavior: REDUCED ? "auto" : "smooth" }); });
    tr.addEventListener("scroll", upd, { passive: true }); window.addEventListener("resize", upd); upd();
  });

  /* ---------- Apparition au défilement ---------- */
  var rv = $$(".rv");
  if ("IntersectionObserver" in window && !REDUCED) {
    var io = new IntersectionObserver(function (es) { es.forEach(function (e) { if (e.isIntersecting) { e.target.classList.add("rv-in"); io.unobserve(e.target); } }); }, { rootMargin: "0px 0px -8% 0px" });
    rv.forEach(function (el, i) { el.style.transitionDelay = (i % 4) * 60 + "ms"; io.observe(el); });
  } else rv.forEach(function (el) { el.classList.add("rv-in"); });

  /* ---------- Sommaire d'article ---------- */
  var tocLinks = $$("[data-toc]");
  if (tocLinks.length && "IntersectionObserver" in window) {
    var heads = tocLinks.map(function (a) { return document.getElementById(a.getAttribute("data-toc")); }).filter(Boolean);
    var tio = new IntersectionObserver(function (es) {
      es.forEach(function (e) { if (e.isIntersecting) tocLinks.forEach(function (a) { a.classList.toggle("on", a.getAttribute("data-toc") === e.target.id); }); });
    }, { rootMargin: "-20% 0px -70% 0px" });
    heads.forEach(function (h) { tio.observe(h); });
  }

  /* ---------- Partage ---------- */
  $$("[data-share]").forEach(function (b) {
    b.addEventListener("click", function () {
      var url = b.getAttribute("data-url"), title = b.getAttribute("data-title");
      if (navigator.share) { navigator.share({ title: title, url: url }).catch(function () {}); return; }
      var done = function () { $("span", b).textContent = "Lien copié"; toast("Lien copié dans le presse-papiers"); setTimeout(function () { $("span", b).textContent = "Partager"; }, 2500); };
      if (navigator.clipboard) navigator.clipboard.writeText(url).then(done, done); else { prompt("Copiez le lien :", url); }
    });
  });

  /* ---------- Divers ---------- */
  $$("form[data-autosubmit] select").forEach(function (s) { s.addEventListener("change", function () { s.form.submit(); }); });
  $$("[data-count]").forEach(function (t) { var c = document.getElementById(t.getAttribute("data-count")); t.addEventListener("input", function () { if (c) c.textContent = t.value.length; }); });
  var adm = $("[data-ad-mobile]");
  if (adm) { try { if (sessionStorage.getItem("vx-ad-closed")) adm.hidden = true; } catch (e) {} var cl = $("[data-ad-close]", adm); if (cl) cl.addEventListener("click", function () { adm.hidden = true; try { sessionStorage.setItem("vx-ad-closed", "1"); } catch (e) {} }); }
  $$("form").forEach(function (f) { f.addEventListener("submit", function () { var b = $("button[type=submit]", f); if (b && f.checkValidity && f.noValidate) { setTimeout(function () { b.disabled = true; }, 0); setTimeout(function () { b.disabled = false; }, 6000); } }); });

  /* ---------- Mesure de lecture (sans cookie) ---------- */
  if (site) {
    var pv = site.getAttribute("data-pv"), collect = site.getAttribute("data-collect"), active = 0, lastAct = Date.now(), sent = false;
    if (pv && pv !== "0" && collect && navigator.sendBeacon) {
      ["scroll", "mousemove", "keydown", "touchstart", "click"].forEach(function (ev) { window.addEventListener(ev, function () { lastAct = Date.now(); }, { passive: true }); });
      setInterval(function () { if (!document.hidden && Date.now() - lastAct < 30000) active++; }, 1000);
      var send = function () {
        var fd = new FormData(); fd.append("pv", pv); fd.append("d", active); fd.append("s", Math.min(100, maxScroll));
        navigator.sendBeacon(collect, fd); sent = true;
      };
      document.addEventListener("visibilitychange", function () { if (document.hidden) send(); });
      window.addEventListener("pagehide", function () { if (!sent || active > 0) send(); });
    }
  }
  onScroll();
})();
