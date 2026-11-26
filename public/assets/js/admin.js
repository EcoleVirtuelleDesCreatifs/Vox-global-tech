/* VOX TECH GLOBAL — interactions de l'espace d'administration (JavaScript natif, sans dépendance).
   Thème, menu mobile, notifications, recherche, confirmations, actions groupées, éditeur d'article
   (compteurs, aperçu Google, sous-rubriques, couverture), envois automatiques, aperçu newsletter, info-bulles. */
(function () {
  "use strict";
  var $ = function (s, c) { return (c || document).querySelector(s); };
  var $$ = function (s, c) { return Array.prototype.slice.call((c || document).querySelectorAll(s)); };
  var root = document.documentElement;
  var debounce = function (fn, ms) { var t; return function () { var a = arguments, me = this; clearTimeout(t); t = setTimeout(function () { fn.apply(me, a); }, ms); }; };

  /* ---------- Thème ---------- */
  function isDark() { var a = root.getAttribute("data-theme"); return a ? a === "dark" : !!(window.matchMedia && window.matchMedia("(prefers-color-scheme: dark)").matches); }
  $$("[data-theme-toggle]").forEach(function (b) {
    b.addEventListener("click", function () { var t = isDark() ? "light" : "dark"; root.setAttribute("data-theme", t); try { localStorage.setItem("vx-theme", t); } catch (e) {} });
  });

  /* ---------- Toast ---------- */
  var toastEl = $(".vx-toast"), toastT;
  function toast(msg) { if (!toastEl) return; toastEl.textContent = msg; toastEl.hidden = false; clearTimeout(toastT); toastT = setTimeout(function () { toastEl.hidden = true; }, 2800); }

  /* ---------- Messages flash ---------- */
  $$(".flash-msg [data-dismiss]").forEach(function (b) { b.addEventListener("click", function () { b.parentNode.remove(); }); });
  $$(".flash-msg:not(.err)").forEach(function (f) { setTimeout(function () { f.style.transition = "opacity .4s"; f.style.opacity = "0"; setTimeout(function () { f.remove(); }, 400); }, 7000); });

  /* ---------- Menu mobile ---------- */
  var nav = $("#adm-nav");
  $$("[data-adm-menu]").forEach(function (b) {
    b.addEventListener("click", function (e) { e.stopPropagation(); if (nav) { nav.classList.toggle("open"); b.setAttribute("aria-expanded", nav.classList.contains("open")); } });
  });
  document.addEventListener("click", function (e) { if (nav && nav.classList.contains("open") && !nav.contains(e.target)) nav.classList.remove("open"); });

  /* ---------- Notifications ---------- */
  var nb = $("[data-notif]"), np = $(".notif-pop");
  if (nb && np) {
    nb.addEventListener("click", function (e) { e.stopPropagation(); np.hidden = !np.hidden; nb.setAttribute("aria-expanded", String(!np.hidden)); });
    document.addEventListener("click", function (e) { if (!np.hidden && !np.contains(e.target)) { np.hidden = true; nb.setAttribute("aria-expanded", "false"); } });
  }

  /* ---------- Recherche globale ---------- */
  var si = $("[data-api]"), hits = si ? si.parentNode.querySelector(".adm-hits") : null, ctrl;
  if (si && hits) {
    var esc = function (s) { return String(s).replace(/[&<>"']/g, function (c) { return { "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[c]; }); };
    var run = debounce(function () {
      var q = si.value.trim();
      if (q.length < 2) { hits.hidden = true; return; }
      if (ctrl && ctrl.abort) ctrl.abort();
      ctrl = window.AbortController ? new AbortController() : null;
      fetch(si.getAttribute("data-api") + "?q=" + encodeURIComponent(q), { headers: { Accept: "application/json" }, credentials: "same-origin", signal: ctrl ? ctrl.signal : undefined })
        .then(function (r) { return r.json(); })
        .then(function (list) {
          hits.innerHTML = list.length ? list.map(function (h) { return '<a href="' + esc(h.href) + '"><span class="k">' + esc(h.k) + "</span>" + esc(h.t) + "</a>"; }).join("") : '<p class="muted m-2">Aucun résultat pour « ' + esc(q) + " ».</p>";
          hits.hidden = false;
        }).catch(function () {});
    }, 220);
    si.addEventListener("input", run);
    si.addEventListener("keydown", function (e) {
      var links = $$("a", hits), i = links.indexOf(document.activeElement);
      if (e.key === "Escape") { hits.hidden = true; si.blur(); }
      if (e.key === "ArrowDown" && links.length) { e.preventDefault(); links[0].focus(); }
      if (e.key === "Enter" && links.length) { e.preventDefault(); location.href = links[0].href; }
    });
    hits.addEventListener("keydown", function (e) {
      var links = $$("a", hits), i = links.indexOf(document.activeElement);
      if (e.key === "ArrowDown") { e.preventDefault(); (links[i + 1] || links[0]).focus(); }
      if (e.key === "ArrowUp") { e.preventDefault(); i > 0 ? links[i - 1].focus() : si.focus(); }
      if (e.key === "Escape") { hits.hidden = true; si.focus(); }
    });
    document.addEventListener("click", function (e) { if (!hits.hidden && !hits.contains(e.target) && e.target !== si) hits.hidden = true; });
    document.addEventListener("keydown", function (e) {
      if (e.key === "/" && !/INPUT|TEXTAREA|SELECT/.test((document.activeElement || {}).tagName || "")) { e.preventDefault(); si.focus(); }
    });
  }

  /* ---------- Confirmation avant action ---------- */
  var box = $("[data-confirm-box]"), pending = null, lastFocus = null;
  function closeConfirm() { if (box) box.hidden = true; pending = null; if (lastFocus) lastFocus.focus(); }
  if (box) {
    $("[data-confirm-no]", box).addEventListener("click", closeConfirm);
    box.addEventListener("click", function (e) { if (e.target === box) closeConfirm(); });
    document.addEventListener("keydown", function (e) { if (e.key === "Escape" && !box.hidden) closeConfirm(); });
    $("[data-confirm-yes]", box).addEventListener("click", function () {
      var el = pending; closeConfirm();
      if (!el) return;
      el.setAttribute("data-confirmed", "1");
      if (el.tagName === "A") { location.href = el.href; return; }
      var form = el.form || (el.getAttribute("form") ? document.getElementById(el.getAttribute("form")) : el.closest("form"));
      if (!form) return;
      if (form.requestSubmit) form.requestSubmit(el.type === "submit" ? el : undefined); else el.click();
      setTimeout(function () { el.removeAttribute("data-confirmed"); }, 500);
    });
  }
  document.addEventListener("click", function (e) {
    var el = e.target.closest ? e.target.closest("[data-confirm]") : null;
    if (!el || el.disabled || el.getAttribute("data-confirmed")) return;
    // Bouton d'actions groupées : ne confirmer que s'il y a une sélection
    if (!box) { if (!window.confirm(el.getAttribute("data-confirm"))) e.preventDefault(); return; }
    e.preventDefault();
    pending = el; lastFocus = el;
    $("#cf-t", box).textContent = el.getAttribute("data-confirm");
    $("[data-confirm-text]", box).textContent = el.getAttribute("data-confirm-text") || "Cette action est définitive.";
    $("[data-confirm-yes]", box).textContent = el.getAttribute("data-confirm-label") || "Confirmer";
    box.hidden = false;
    $("[data-confirm-yes]", box).focus();
  }, true);

  /* ---------- Afficher / masquer un bloc ---------- */
  document.addEventListener("click", function (e) {
    var b = e.target.closest ? e.target.closest("[data-toggle]") : null;
    if (!b) return;
    var t = document.getElementById(b.getAttribute("data-toggle"));
    if (!t) return;
    t.hidden = !t.hidden;
    $$('[data-toggle="' + t.id + '"]').forEach(function (x) { x.setAttribute("aria-expanded", String(!t.hidden)); });
    if (!t.hidden) { var f = t.querySelector("input:not([type=hidden]), textarea, select"); if (f) f.focus(); t.scrollIntoView({ block: "nearest", behavior: "smooth" }); }
  });

  /* ---------- Envoi automatique des filtres ---------- */
  $$("form[data-autosubmit]").forEach(function (f) {
    var submit = function () { f.requestSubmit ? f.requestSubmit() : f.submit(); };
    f.addEventListener("change", function (e) { if (e.target.matches("select, input[type=checkbox], input[type=radio], input[type=date]")) submit(); });
    var deb = debounce(submit, 600);
    $$("input[type=search]", f).forEach(function (i) { i.addEventListener("input", deb); });
  });
  $$("form[data-upload] input[type=file]").forEach(function (i) {
    i.addEventListener("change", function () { if (i.files.length) { toast("Envoi de " + i.files.length + " fichier(s)…"); i.form.submit(); } });
  });

  /* ---------- Actions groupées ---------- */
  $$("form[data-bulk]").forEach(function (f) {
    var bar = $(".bulk-bar", f), n = $("[data-bulk-n]", f), all = $("[data-check-all]", f);
    var boxes = function () { return $$('input[name="ids[]"]', f); };
    var sync = function () {
      var c = boxes().filter(function (b) { return b.checked; }).length;
      if (n) n.textContent = c;
      if (bar) bar.hidden = c === 0;
      if (all) { all.checked = c > 0 && c === boxes().length; all.indeterminate = c > 0 && c < boxes().length; }
    };
    f.addEventListener("change", function (e) {
      if (e.target === all) boxes().forEach(function (b) { b.checked = all.checked; });
      sync();
    });
    var clr = $("[data-bulk-clear]", f);
    if (clr) clr.addEventListener("click", function () { boxes().forEach(function (b) { b.checked = false; }); sync(); });
    sync();
  });

  /* ---------- Compteurs de caractères et miroirs ---------- */
  $$("[data-counter]").forEach(function (i) {
    var max = +i.getAttribute("data-counter"), out = i.id ? $('.char-c[data-for="' + i.id + '"]') : null;
    var upd = function () { var l = (i.value || i.getAttribute("placeholder") || "").length; if (out) { out.textContent = l + " / " + max; out.classList.toggle("over", l > max); } };
    i.addEventListener("input", upd); upd();
  });
  $$("[data-mirror]").forEach(function (o) {
    var src = document.getElementById(o.getAttribute("data-mirror"));
    if (src) src.addEventListener("input", function () { o.textContent = src.value; });
  });

  /* ---------- Copier dans le presse-papiers ---------- */
  document.addEventListener("click", function (e) {
    var b = e.target.closest ? e.target.closest("[data-copy]") : null;
    if (!b) return;
    var v = b.getAttribute("data-copy");
    var done = function () { toast("Adresse copiée."); };
    if (navigator.clipboard && window.isSecureContext) navigator.clipboard.writeText(v).then(done, function () { window.prompt("Copiez l’adresse :", v); });
    else window.prompt("Copiez l’adresse :", v);
  });

  /* ---------- Éditeur d'article ---------- */
  var ed = $("form[data-editor]");
  if (ed) {
    var slugify = function (s) { return String(s || "").normalize("NFD").replace(/[̀-ͯ]/g, "").toLowerCase().replace(/['’]/g, "-").replace(/[^a-z0-9]+/g, "-").replace(/^-+|-+$/g, "").slice(0, 90); };
    var val = function (id) { var x = document.getElementById(id); return x ? x.value.trim() : ""; };
    var ph = function (id) { var x = document.getElementById(id); return x ? (x.getAttribute("placeholder") || "") : ""; };
    // Mots et temps de lecture
    var txt = $("[data-words]", ed), wc = txt ? document.getElementById(txt.getAttribute("data-words")) : null, rd = $("[data-read]", ed);
    var words = function () {
      if (!txt) return;
      var n = (txt.value.replace(/^(##|>|Q ?:|R ?:)\s*/gm, "").match(/[\p{L}\p{N}’'-]+/gu) || []).length;
      if (wc) wc.textContent = n.toLocaleString("fr-FR");
      if (rd) rd.textContent = Math.max(1, Math.round(n / 230));
    };
    if (txt) { txt.addEventListener("input", words); words(); }
    // Aperçu Google
    var st = $("[data-serp-t]", ed), sd = $("[data-serp-d]", ed), su = $("[data-serp-url]", ed);
    var serp = function () {
      if (st) st.textContent = val("e-st") || val("e-t") || "Titre de l’article";
      if (sd) { var d = val("e-sd") || val("e-c") || ph("e-sd"); sd.textContent = d.length > 158 ? d.slice(0, 155).replace(/\s+\S*$/, "") + "…" : (d || "Description de l’article"); }
      if (su) su.textContent = val("e-sl") || slugify(val("e-t")) || "nouvel-article";
    };
    ["e-t", "e-c", "e-st", "e-sd", "e-sl"].forEach(function (id) { var x = document.getElementById(id); if (x) x.addEventListener("input", serp); });
    var slugIn = document.getElementById("e-sl");
    if (slugIn) slugIn.addEventListener("blur", function () { if (slugIn.value) slugIn.value = slugify(slugIn.value); serp(); });
    serp();
    // Blocs propres à un type de contenu
    var ty = $("[data-type-select]", ed);
    var types = function () { $$("[data-show-type]", ed).forEach(function (b) { b.hidden = !ty || ty.value !== b.getAttribute("data-show-type"); }); };
    if (ty) { ty.addEventListener("change", types); types(); }
    // Sous-rubriques dépendantes de la rubrique
    var rs = $("[data-rub-select]", ed), ss = $("[data-sub-select]", ed);
    var subs = function (keep) {
      if (!rs || !ss) return;
      var opt = rs.options[rs.selectedIndex], list = [];
      try { list = JSON.parse(opt.getAttribute("data-subs") || "[]"); } catch (e) {}
      var cur = keep ? ss.getAttribute("data-current") : "";
      ss.innerHTML = '<option value="">Aucune</option>';
      list.forEach(function (s) { var o = document.createElement("option"); o.value = o.textContent = s; if (s === cur) o.selected = true; ss.appendChild(o); });
    };
    if (rs) { rs.addEventListener("change", function () { subs(false); }); subs(true); }
    // Couverture : aperçu immédiat
    var cp = $("[data-cover-prev]", ed), ci = $("[data-cover-input]", ed), cs = $('select[name="cover_id"]', ed);
    var setCover = function (url) { if (!cp) return; cp.style.backgroundImage = url ? "url('" + url + "')" : ""; cp.innerHTML = url ? "" : "<span>Visuel graphique automatique</span>"; };
    if (ci) ci.addEventListener("change", function () {
      var f = ci.files && ci.files[0];
      if (!f) return;
      if (f.size > 8 * 1024 * 1024) { toast("Image trop lourde (8 Mo maximum)."); ci.value = ""; return; }
      var r = new FileReader(); r.onload = function () { setCover(r.result); }; r.readAsDataURL(f);
      if (cs) cs.value = "";
    });
    if (cs) cs.addEventListener("change", function () { var o = cs.options[cs.selectedIndex]; setCover(o ? o.getAttribute("data-url") : ""); });
    // Protection contre la perte de modifications
    var dirty = false, sending = false;
    ed.addEventListener("input", function () { dirty = true; });
    ed.addEventListener("change", function () { dirty = true; });
    ed.addEventListener("submit", function () { sending = true; });
    window.addEventListener("beforeunload", function (e) { if (dirty && !sending) { e.preventDefault(); e.returnValue = ""; } });
    // Raccourci Ctrl/Cmd + S : enregistrer
    document.addEventListener("keydown", function (e) {
      if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === "s") { e.preventDefault(); var b = $('button[name="action"][value="save"]', ed); if (b) ed.requestSubmit ? ed.requestSubmit(b) : b.click(); }
    });
  }

  /* ---------- Aperçu de la newsletter ---------- */
  var nl = $("form[data-nl-form]");
  if (nl) {
    var esc2 = function (s) { var d = document.createElement("div"); d.textContent = s || ""; return d.innerHTML; };
    var order = [];
    $$("[data-nl-art]", nl).forEach(function (c) { if (c.checked) order.push(c.value); });
    var prev = function () {
      $("[data-nl-prev-subject]", nl).textContent = $("[data-nl-subject]", nl).value;
      $("[data-nl-prev-intro]", nl).textContent = $("[data-nl-intro]", nl).value;
      var map = {}; $$("[data-nl-art]", nl).forEach(function (c) { map[c.value] = c; });
      $("[data-nl-prev-arts]", nl).innerHTML = order.map(function (id) {
        var c = map[id]; if (!c) return "";
        return '<div class="mail-art"><span class="kicker"><span class="type">' + esc2(c.getAttribute("data-kicker")) + "</span></span><strong>" + esc2(c.getAttribute("data-title")) + '</strong><span class="meta">' + esc2(c.getAttribute("data-chapo")) + '</span><span class="mail-btn">Lire l’article</span></div>';
      }).join("") || '<p class="muted">Sélectionnez au moins un article.</p>';
    };
    nl.addEventListener("change", function (e) {
      if (e.target.matches("[data-nl-art]")) {
        var i = order.indexOf(e.target.value);
        if (e.target.checked && i < 0) order.push(e.target.value);
        if (!e.target.checked && i >= 0) order.splice(i, 1);
      }
      prev();
    });
    nl.addEventListener("input", prev);
    // Conserver l'ordre de sélection à l'envoi
    nl.addEventListener("submit", function () {
      $$("[data-nl-art]", nl).forEach(function (c) { c.disabled = true; });
      order.forEach(function (id) { var h = document.createElement("input"); h.type = "hidden"; h.name = "articles[]"; h.value = id; nl.appendChild(h); });
      setTimeout(function () { $$("[data-nl-art]", nl).forEach(function (c) { c.disabled = false; }); $$('input[type=hidden][name="articles[]"]', nl).forEach(function (h) { h.remove(); }); }, 100);
    });
    prev();
  }

  /* ---------- Info-bulles des graphiques ---------- */
  var tip = document.createElement("div");
  tip.className = "chart-tip"; tip.hidden = true; tip.setAttribute("role", "tooltip");
  document.body.appendChild(tip);
  document.addEventListener("mousemove", function (e) {
    var g = e.target.closest ? e.target.closest(".vx-chart .pt[data-tip]") : null;
    if (!g) { tip.hidden = true; return; }
    tip.textContent = g.getAttribute("data-tip");
    tip.hidden = false;
    var x = Math.min(window.innerWidth - tip.offsetWidth - 8, e.clientX + 14);
    tip.style.position = "fixed"; tip.style.left = x + "px"; tip.style.top = (e.clientY - tip.offsetHeight - 10) + "px";
  });

  /* ---------- Désactiver les boutons pendant l'envoi (évite les doubles envois) ---------- */
  document.addEventListener("submit", function (e) {
    var f = e.target;
    if (f.hasAttribute("data-autosubmit") || f.method.toLowerCase() === "get") return;
    setTimeout(function () { $$('button[type="submit"], button:not([type])', f).forEach(function (b) { b.disabled = true; }); }, 0);
    setTimeout(function () { $$('button[type="submit"], button:not([type])', f).forEach(function (b) { b.disabled = false; }); }, 8000);
  });
})();
