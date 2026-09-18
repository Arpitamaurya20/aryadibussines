(function () {
  const cfg = window.JLL_WALLBOARD || {};
  const refreshSec = Math.max(60, parseInt(cfg.refreshSeconds, 10) || 120);
  const apiUrl = cfg.apiUrl || "api/live_data.php";
  const wallboardKey = cfg.key || "";
  let countdown = refreshSec;

  const charts = {};
  const CHART_COLORS = [
    "#e4002b", "#00a9a5", "#3b82f6", "#f59e0b", "#8b5cf6",
    "#10b981", "#ec4899", "#06b6d4", "#84cc16", "#f97316",
  ];

  function el(id) { return document.getElementById(id); }
  function fmt(n) { return new Intl.NumberFormat("en-IN").format(Number(n || 0)); }
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

  function chartDefaults() {
    return {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          labels: { color: "#94a3b8", font: { size: 10, family: "DM Sans" }, boxWidth: 12 },
        },
      },
      scales: {
        x: {
          ticks: { color: "#64748b", font: { size: 10 } },
          grid: { color: "rgba(255,255,255,.06)" },
        },
        y: {
          ticks: { color: "#64748b", font: { size: 10 } },
          grid: { color: "rgba(255,255,255,.06)" },
          beginAtZero: true,
        },
      },
    };
  }

  function destroyChart(id) {
    if (charts[id]) {
      charts[id].destroy();
      delete charts[id];
    }
  }

  function labelsFromRows(rows) {
    return (rows || []).map((r) => r.label || "Unknown");
  }

  function valuesFromRows(rows) {
    return (rows || []).map((r) => Number(r.value || 0));
  }

  function renderDoughnut(canvasId, rows, title) {
    const canvas = el(canvasId);
    if (!canvas) return;
    destroyChart(canvasId);
    const labels = labelsFromRows(rows);
    const values = valuesFromRows(rows);
    if (!labels.length) {
      const ctx = canvas.getContext("2d");
      ctx.clearRect(0, 0, canvas.width, canvas.height);
      return;
    }
    charts[canvasId] = new Chart(canvas, {
      type: "doughnut",
      data: {
        labels,
        datasets: [{
          data: values,
          backgroundColor: CHART_COLORS.slice(0, labels.length),
          borderWidth: 0,
          hoverOffset: 6,
        }],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: "62%",
        plugins: {
          legend: {
            position: "right",
            labels: { color: "#94a3b8", font: { size: 9 }, boxWidth: 10, padding: 6 },
          },
          title: title ? { display: false } : undefined,
        },
      },
    });
  }

  function renderTrendChart(canvasId, trend) {
    const canvas = el(canvasId);
    if (!canvas || !trend) return;
    destroyChart(canvasId);
    charts[canvasId] = new Chart(canvas, {
      type: "line",
      data: {
        labels: trend.labels || [],
        datasets: [
          {
            label: "Opened",
            data: trend.opened || [],
            borderColor: "#3b82f6",
            backgroundColor: "rgba(59,130,246,.15)",
            fill: true,
            tension: 0.35,
            pointRadius: 4,
            pointBackgroundColor: "#3b82f6",
          },
          {
            label: "Closed",
            data: trend.closed || [],
            borderColor: "#10b981",
            backgroundColor: "rgba(16,185,129,.12)",
            fill: true,
            tension: 0.35,
            pointRadius: 4,
            pointBackgroundColor: "#10b981",
          },
        ],
      },
      options: chartDefaults(),
    });
  }

  function renderBarList(container, rows, valueKey) {
    if (!container) return;
    const key = valueKey || "value";
    if (!rows || !rows.length) {
      container.innerHTML = '<li class="jll-empty">No activity today</li>';
      return;
    }
    const m = maxVal(rows, key);
    container.innerHTML = rows.map((r) => {
      const v = Number(r[key] ?? r.value ?? 0);
      const pct = Math.max(4, Math.round((v / m) * 100));
      return `<li class="jll-bar-item">
        <span>${esc(r.label)}</span><strong>${fmt(v)}</strong>
        <div class="jll-bar-track"><div class="jll-bar-fill" style="width:${pct}%"></div></div>
      </li>`;
    }).join("");
  }

  function renderChips(container, rows) {
    if (!container) return;
    if (!rows || !rows.length) {
      container.innerHTML = '<span class="jll-chip"><b>0</b>None today</span>';
      return;
    }
    container.innerHTML = rows.map((r) =>
      `<span class="jll-chip"><b>${fmt(r.value)}</b>${esc(r.label)}</span>`
    ).join("");
  }

  function renderTypeMatrix(container, rows) {
    if (!container) return;
    if (!rows || !rows.length) {
      container.innerHTML = "<tr><td colspan='3' class='jll-empty'>No ticket activity today</td></tr>";
      return;
    }
    container.innerHTML = rows.map((r) => `
      <tr>
        <td><strong>${esc(r.ticket_type)}</strong></td>
        <td class="jll-num-open">${fmt(r.opened)}</td>
        <td class="jll-num-closed">${fmt(r.closed)}</td>
      </tr>
    `).join("");
  }

  function renderEmployeeTable(container, rows) {
    if (!container) return;
    if (!rows || !rows.length) {
      container.innerHTML = "<tr><td colspan='4' class='jll-empty'>No technician activity today</td></tr>";
      return;
    }
    container.innerHTML = rows.map((r, i) => `
      <tr>
        <td class="jll-rank">#${i + 1}</td>
        <td>${esc(r.label)}</td>
        <td class="jll-num-open">${fmt(r.opened_today)}</td>
        <td class="jll-num-closed">${fmt(r.closed_today)}</td>
      </tr>
    `).join("");
  }

  function renderTopSitesList(container, rows) {
    if (!container) return;
    if (!rows || !rows.length) {
      container.innerHTML = '<div class="jll-empty">No branch sites with open tickets</div>';
      return;
    }
    const top = rows.slice(0, 20);
    const max = Math.max(1, ...top.map((r) => Number(r.total_active || 0)));
    container.innerHTML = `<ul class="jll-site-list">${top.map((r) => {
      const v = Number(r.total_active || 0);
      const pct = Math.max(4, Math.round((v / max) * 100));
      const loc = [r.branch_city, r.branch_state].filter(Boolean).join(", ");
      return `<li class="jll-site-item">
        <div class="jll-site-item-head">
          <div class="jll-site-names">
            <span class="jll-site-name">${esc(r.branch_name)}</span>
            <span class="jll-site-contract">${esc(r.contract_name)}</span>
            ${loc ? `<span class="jll-site-meta">${esc(loc)}${r.branch_code && r.branch_code !== "—" ? " · " + esc(r.branch_code) : ""}</span>` : ""}
          </div>
          <strong class="jll-site-count">${fmt(v)}</strong>
        </div>
        <div class="jll-bar-track"><div class="jll-bar-fill" style="width:${pct}%"></div></div>
      </li>`;
    }).join("")}</ul>`;
  }

  function renderContractBranchSummary(container, rows) {
    if (!container) return;
    if (!rows || !rows.length) {
      container.innerHTML = "<tr><td colspan='7' class='jll-empty'>No branch account data</td></tr>";
      return;
    }
    container.innerHTML = rows.map((r) => `
      <tr>
        <td class="jll-cell-contract">${esc(r.contract_name)}</td>
        <td>${fmt(r.branch_count)}</td>
        <td><strong>${fmt(r.total_active)}</strong></td>
        <td>${fmt(r.corp_active)}</td>
        <td>${fmt(r.ppm_active)}</td>
        <td class="jll-num-open">${fmt(r.opened_today)}</td>
        <td class="jll-num-closed">${fmt(r.closed_today)}</td>
      </tr>
    `).join("");
  }

  function renderBranchAccountsTable(container, rows) {
    if (!container) return;
    if (!rows || !rows.length) {
      container.innerHTML = "<tr><td colspan='8' class='jll-empty'>No open tickets at branch level</td></tr>";
      return;
    }
    container.innerHTML = rows.map((r) => {
      const loc = [r.branch_city, r.branch_state].filter((x) => x && x !== "—").join(", ");
      const today = (Number(r.opened_today) > 0 || Number(r.closed_today) > 0)
        ? `<span class="jll-num-open">${fmt(r.opened_today)}</span> / <span class="jll-num-closed">${fmt(r.closed_today)}</span>`
        : "—";
      return `
      <tr>
        <td class="jll-cell-contract">${esc(r.contract_name)}</td>
        <td class="jll-cell-branch"><strong>${esc(r.branch_name)}</strong></td>
        <td>${esc(r.branch_code)}</td>
        <td class="jll-cell-location">${esc(loc || "—")}</td>
        <td>${fmt(r.corp_active)}</td>
        <td>${fmt(r.ppm_active)}</td>
        <td><strong>${fmt(r.total_active)}</strong></td>
        <td class="jll-cell-today">${today}</td>
      </tr>`;
    }).join("");
  }

  function switchBranchTab(tabId) {
    document.querySelectorAll(".jll-tab").forEach((btn) => {
      const active = btn.dataset.tab === tabId;
      btn.classList.toggle("active", active);
      btn.setAttribute("aria-selected", active ? "true" : "false");
    });
    document.querySelectorAll(".jll-tab-pane").forEach((pane) => {
      pane.classList.toggle("active", pane.dataset.tab === tabId);
    });
    try {
      localStorage.setItem("jll_branch_tab", tabId);
    } catch (e) { /* ignore */ }
  }

  function initBranchTabs() {
    const tabs = document.querySelectorAll(".jll-tab");
    if (!tabs.length) return;
    let saved = "sites";
    try {
      saved = localStorage.getItem("jll_branch_tab") || "sites";
    } catch (e) { /* ignore */ }
    if (["sites", "contracts", "branches"].includes(saved)) {
      switchBranchTab(saved);
    }
    tabs.forEach((btn) => {
      btn.addEventListener("click", () => switchBranchTab(btn.dataset.tab));
    });
  }

  function renderPpmUpcoming(container, rows) {
    if (!container) return;
    if (!rows || !rows.length) {
      container.innerHTML = "<tr><td colspan='7' class='jll-empty'>No PPM scheduled in next 14 days</td></tr>";
      return;
    }
    container.innerHTML = rows.map((r) => `
      <tr>
        <td><strong>${esc(r.ticket_code)}</strong></td>
        <td>${esc(r.ppm_date)}</td>
        <td>${esc(r.status)}</td>
        <td>${esc(r.company_name)}</td>
        <td>${esc(r.branch_name)}</td>
        <td>${esc(r.branch_state)}</td>
        <td>${esc(r.assigned_to)}</td>
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
      container.innerHTML = '<div class="jll-empty">No JLL activity recorded today yet.</div>';
      return;
    }
    const tagClass = (t) => (t === "opened" ? "jll-tag-open" : t === "closed" ? "jll-tag-closed" : "jll-tag-status");
    const tagLabel = (t) => (t === "opened" ? "OPENED" : t === "closed" ? "CLOSED" : "STATUS");
    const rowHtml = (it) => `
      <div class="jll-ticket-row">
        <span class="${tagClass(it.type)}">${tagLabel(it.type)}</span>
        <span>${esc(it.code)}</span>
        <span>${esc(it.ttype)} · ${esc(it.status)} · ${esc(it.who)}</span>
        <span>${esc(it.co)} · ${esc(it.extra)}${it.time ? " · " + esc(it.time) : ""}</span>
      </div>`;
    const html = items.map(rowHtml).join("");
    container.innerHTML = `<div class="jll-ticker-inner">${html}${html}</div>`;
  }

  function applyData(payload) {
    const d = payload.data || {};
    const s = d.summary || {};
    const corp = d.corporate || {};

    if (el("jll_corp_name") && corp.name) {
      el("jll_corp_name").textContent = corp.name;
    }
    if (el("jll_date")) el("jll_date").textContent = d.today || "—";
    if (el("jll_updated")) el("jll_updated").textContent = d.generated_at || "—";
    if (el("jll_contracts_count")) {
      el("jll_contracts_count").textContent = fmt(s.contract_count || 0) + " contracts";
    }
    if (el("jll_branches_count")) {
      el("jll_branches_count").textContent = fmt(s.branch_account_count || 0) + " branch accounts";
    }

    if (el("k_active")) el("k_active").textContent = fmt(s.total_active);
    if (el("k_opened")) el("k_opened").textContent = fmt(s.opened_today);
    if (el("k_closed")) el("k_closed").textContent = fmt(s.closed_today);
    if (el("k_corp_active")) el("k_corp_active").textContent = fmt(s.corporate_active);
    if (el("k_ppm_active")) el("k_ppm_active").textContent = fmt(s.ppm_active);
    if (el("k_ppm_overdue")) el("k_ppm_overdue").textContent = fmt(s.ppm_overdue);
    if (el("k_ppm_compliance")) el("k_ppm_compliance").textContent = (s.ppm_compliance_pct ?? 0) + "%";
    if (el("k_status_updates")) el("k_status_updates").textContent = fmt(s.status_updates_today);

    renderTrendChart("chart_trend", d.trend_7day);
    renderDoughnut("chart_type_backlog", d.type_backlog);
    renderDoughnut("chart_corp_status", (d.status_distribution || {}).corporate);
    renderDoughnut("chart_ppm_status", (d.status_distribution || {}).ppm);

    renderTypeMatrix(el("jll_type_matrix_body"), d.type_matrix);
    renderChips(el("jll_workflow"), d.workflow_today);
    renderTopSitesList(el("jll_top_sites"), d.branch_accounts);
    renderContractBranchSummary(el("jll_contract_branch_summary"), d.contract_branch_summary);
    renderBranchAccountsTable(el("jll_branch_accounts"), d.branch_accounts);
    renderEmployeeTable(el("jll_employees"), d.top_employees_today);
    renderPpmUpcoming(el("jll_ppm_upcoming"), d.ppm_upcoming);
    renderTicker(el("jll_ticker"), d.recent_opened, d.recent_closed, d.status_history_feed);
  }

  async function fetchLive() {
    let url = apiUrl + (apiUrl.indexOf("?") >= 0 ? "&" : "?") + "_=" + Date.now();
    if (wallboardKey && url.indexOf("key=") < 0) {
      url += "&key=" + encodeURIComponent(wallboardKey);
    }
    const res = await fetch(url, { cache: "no-store" });
    const text = await res.text();
    if (!res.ok) throw new Error("API HTTP " + res.status);
    let json;
    try {
      json = JSON.parse(text);
    } catch (e) {
      const preview = String(text || "").replace(/\s+/g, " ").trim().slice(0, 120);
      throw new Error("Invalid JSON" + (preview ? ": " + preview : ""));
    }
    if (json.error) throw new Error(json.message || "API error");
    applyData(json);
    const errBox = el("jll_error");
    if (errBox) errBox.style.display = "none";
  }

  function tickCountdown() {
    countdown -= 1;
    if (countdown <= 0) {
      countdown = refreshSec;
      fetchLive().catch(showError);
    }
    const node = el("jll_countdown");
    if (node) {
      node.textContent = Math.floor(countdown / 60) + ":" + String(countdown % 60).padStart(2, "0");
    }
  }

  function showError(err) {
    const box = el("jll_error");
    if (box) {
      box.style.display = "flex";
      box.textContent = "Could not load JLL data. Retrying… " + (err && err.message ? err.message : "");
    }
    console.error("JLL Wallboard:", err);
  }

  function inFullscreen() {
    return !!(document.fullscreenElement || document.webkitFullscreenElement);
  }

  function updateFullscreenButton() {
    const btn = el("jll_fullscreen_btn");
    if (!btn) return;
    btn.textContent = inFullscreen() ? "Exit Fullscreen" : "Fullscreen";
  }

  async function toggleFullscreen() {
    try {
      const root = document.documentElement;
      if (!inFullscreen()) {
        if (root.requestFullscreen) await root.requestFullscreen();
        else if (root.webkitRequestFullscreen) root.webkitRequestFullscreen();
      } else if (document.exitFullscreen) await document.exitFullscreen();
      else if (document.webkitExitFullscreen) document.webkitExitFullscreen();
    } catch (err) {
      showError(new Error("Fullscreen blocked."));
    } finally {
      updateFullscreenButton();
    }
  }

  function start() {
    initBranchTabs();
    const fsBtn = el("jll_fullscreen_btn");
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
