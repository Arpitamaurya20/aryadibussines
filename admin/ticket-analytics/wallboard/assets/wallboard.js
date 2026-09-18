(function () {
  const cfg = window.WALLBOARD_CONFIG || {};
  const refreshSec = Math.max(60, parseInt(cfg.refreshSeconds, 10) || 300);
  const apiUrl = cfg.apiUrl || "api/live_data.php";
  const wallboardKey = cfg.key || "";
  let countdown = refreshSec;

  function el(id) { return document.getElementById(id); }
  function fmt(n) { return new Intl.NumberFormat("en-IN").format(Number(n || 0)); }
  function fmtMoney(n) {
    return "₹ " + new Intl.NumberFormat("en-IN", { maximumFractionDigits: 0 }).format(Number(n || 0));
  }
  function esc(s) {
    const d = document.createElement("div");
    d.textContent = s == null ? "" : String(s);
    return d.innerHTML;
  }

  function maxVal(rows, key) {
    let m = 1;
    (rows || []).forEach((r) => {
      const v = Number(r[key] ?? r.value ?? 0);
      if (v > m) m = v;
    });
    return m;
  }

  function renderBarList(container, rows, valueKey) {
    if (!container) return;
    const key = valueKey || "value";
    if (!rows || !rows.length) {
      container.innerHTML = '<li class="wb-empty">No activity today</li>';
      return;
    }
    const m = maxVal(rows, key);
    container.innerHTML = rows.map((r) => {
      const v = Number(r[key] ?? r.value ?? 0);
      const pct = Math.max(4, Math.round((v / m) * 100));
      return `<li class="wb-bar-item">
        <span>${esc(r.label)}</span><strong>${fmt(v)}</strong>
        <div class="wb-bar-track"><div class="wb-bar-fill" style="width:${pct}%"></div></div>
      </li>`;
    }).join("");
  }

  function renderChips(container, rows) {
    if (!container) return;
    if (!rows || !rows.length) {
      container.innerHTML = '<span class="wb-chip"><b>0</b> None today</span>';
      return;
    }
    container.innerHTML = rows.map((r) =>
      `<span class="wb-chip"><b>${fmt(r.value)}</b>${esc(r.label)}</span>`
    ).join("");
  }

  function renderTypeMatrix(container, rows) {
    if (!container) return;
    if (!rows || !rows.length) {
      container.innerHTML = "<tr><td colspan='3' class='wb-empty'>No ticket activity today</td></tr>";
      return;
    }
    container.innerHTML = rows.map((r) => `
      <tr>
        <td><strong>${esc(r.ticket_type)}</strong></td>
        <td class="wb-num-open">${fmt(r.opened)}</td>
        <td class="wb-num-closed">${fmt(r.closed)}</td>
      </tr>
    `).join("");
  }

  function renderEmployeeTable(container, rows) {
    if (!container) return;
    if (!rows || !rows.length) {
      container.innerHTML = "<tr><td colspan='4' class='wb-empty'>No employee activity today</td></tr>";
      return;
    }
    container.innerHTML = rows.map((r, i) => `
      <tr>
        <td class="wb-rank">#${i + 1}</td>
        <td>${esc(r.label)}</td>
        <td class="wb-num-open">${fmt(r.opened_today)}</td>
        <td class="wb-num-closed">${fmt(r.closed_today)}</td>
      </tr>
    `).join("");
  }

  function renderTicker(container, opened, closed, history) {
    if (!container) return;
    const items = [];
    (history || []).forEach((r) => {
      items.push({
        type: "status",
        code: r.ticket_code,
        ttype: r.ticket_type,
        status: r.status,
        who: r.assigned_to,
        co: r.company_name,
        extra: r.branch_state,
        time: r.event_time || "",
      });
    });
    (opened || []).forEach((r) => {
      items.push({
        type: "opened",
        code: r.ticket_code,
        ttype: r.ticket_type,
        status: r.status,
        who: r.assigned_to,
        co: r.company_name,
        extra: r.branch_state,
        time: r.created_time || "",
      });
    });
    (closed || []).forEach((r) => {
      items.push({
        type: "closed",
        code: r.ticket_code,
        ttype: r.ticket_type,
        status: r.status,
        who: r.assigned_to,
        co: r.company_name,
        extra: r.branch_state,
        time: "",
      });
    });
    if (!items.length) {
      container.innerHTML = '<div class="wb-empty">No tickets or status changes recorded today yet.</div>';
      return;
    }
    const tagClass = (t) => (t === "opened" ? "wb-tag-open" : t === "closed" ? "wb-tag-closed" : "wb-tag-status");
    const tagLabel = (t) => (t === "opened" ? "OPENED" : t === "closed" ? "CLOSED" : "STATUS");
    const rowHtml = (it) => `
      <div class="wb-ticket-row">
        <span class="${tagClass(it.type)}">${tagLabel(it.type)}</span>
        <span>${esc(it.code)}</span>
        <span>${esc(it.ttype)} · ${esc(it.status)}</span>
        <span>${esc(it.who)} · ${esc(it.extra)}</span>
        <span>${esc(it.co)}${it.time ? " · " + esc(it.time) : ""}</span>
      </div>`;
    const html = items.map(rowHtml).join("");
    container.innerHTML = `<div class="wb-ticker-inner">${html}${html}</div>`;
  }

  function applyData(payload) {
    const d = payload.data || {};
    const s = d.summary || {};

    if (el("wb_date")) el("wb_date").textContent = d.today || "—";
    if (el("wb_updated")) el("wb_updated").textContent = d.generated_at || "—";

    if (el("k_opened_today")) el("k_opened_today").textContent = fmt(s.opened_today);
    if (el("k_closed_today")) el("k_closed_today").textContent = fmt(s.closed_today);
    if (el("k_quote_sent")) el("k_quote_sent").textContent = fmt(s.quote_sent_approval_today);
    if (el("k_quote_approved")) el("k_quote_approved").textContent = fmt(s.quote_approved_today);
    if (el("k_quote_amount")) el("k_quote_amount").textContent = fmtMoney(s.quote_approved_amount);
    if (el("k_status_updates")) el("k_status_updates").textContent = fmt(s.status_updates_today);

    renderChips(el("wb_workflow_today"), d.workflow_today);
    renderChips(el("wb_status_moves"), d.status_moves_today);
    renderTypeMatrix(el("wb_type_matrix_body"), d.type_matrix);
    renderChips(el("wb_opened_status"), d.opened_today_by_status);
    renderChips(el("wb_closed_status"), d.closed_today_by_status);
    renderBarList(el("wb_states_today"), d.top_states_today);
    renderEmployeeTable(el("wb_employee_rows"), d.top_employees_today);
    renderBarList(el("wb_company_bars"), d.top_companies_today);
    renderBarList(el("wb_branch_bars"), d.top_branches_today);
    renderBarList(el("wb_type_opened"), d.type_opened_today);
    renderBarList(el("wb_type_closed"), d.type_closed_today);
    renderTicker(el("wb_ticker"), d.recent_opened, d.recent_closed, d.status_history_feed);
  }

  async function fetchLive() {
    let url = apiUrl + (apiUrl.indexOf("?") >= 0 ? "&" : "?") + "_=" + Date.now();
    if (wallboardKey && url.indexOf("key=") < 0) {
      url += "&key=" + encodeURIComponent(wallboardKey);
    }
    const res = await fetch(url, { cache: "no-store" });
    const text = await res.text();
    if (!res.ok) {
      throw new Error("API HTTP " + res.status);
    }
    let json;
    try {
      json = JSON.parse(text);
    } catch (e) {
      const preview = String(text || "").replace(/\s+/g, " ").trim().slice(0, 120);
      throw new Error("Invalid JSON from API" + (preview ? ": " + preview : ""));
    }
    if (json.error) throw new Error(json.message || "API error");
    applyData(json);
    const errBox = el("wb_error");
    if (errBox) errBox.style.display = "none";
  }

  function tickCountdown() {
    countdown -= 1;
    if (countdown <= 0) {
      countdown = refreshSec;
      fetchLive().catch(showError);
    }
    const node = el("wb_countdown");
    if (node) {
      node.textContent = Math.floor(countdown / 60) + ":" + String(countdown % 60).padStart(2, "0");
    }
  }

  function showError(err) {
    const box = el("wb_error");
    if (box) {
      box.style.display = "flex";
      box.textContent = "Could not load data. Retrying… " + (err && err.message ? err.message : "");
    }
    console.error("Wallboard:", err);
  }

  function inFullscreen() {
    return !!(document.fullscreenElement || document.webkitFullscreenElement);
  }

  function updateFullscreenButton() {
    const btn = el("wb_fullscreen_btn");
    if (!btn) return;
    btn.textContent = inFullscreen() ? "Exit Fullscreen" : "Fullscreen";
  }

  async function toggleFullscreen() {
    try {
      const root = document.documentElement;
      if (!inFullscreen()) {
        if (root.requestFullscreen) {
          await root.requestFullscreen();
        } else if (root.webkitRequestFullscreen) {
          root.webkitRequestFullscreen();
        }
      } else if (document.exitFullscreen) {
        await document.exitFullscreen();
      } else if (document.webkitExitFullscreen) {
        document.webkitExitFullscreen();
      }
    } catch (err) {
      showError(new Error("Fullscreen blocked. Allow fullscreen in browser."));
    } finally {
      updateFullscreenButton();
    }
  }

  function start() {
    const fsBtn = el("wb_fullscreen_btn");
    if (fsBtn) fsBtn.addEventListener("click", toggleFullscreen);
    document.addEventListener("fullscreenchange", updateFullscreenButton);
    document.addEventListener("webkitfullscreenchange", updateFullscreenButton);
    updateFullscreenButton();
    fetchLive().catch(showError);
    setInterval(tickCountdown, 1000);
    setInterval(function () {
      fetchLive().catch(showError);
      countdown = refreshSec;
    }, refreshSec * 1000);
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", start);
  } else {
    start();
  }
})();
