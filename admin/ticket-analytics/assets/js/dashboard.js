(function () {
  function byId(id) {
    return document.getElementById(id);
  }

  function getTypeChecks() {
    return Array.from(document.querySelectorAll("input[name='ticket_types']"));
  }

  function qstr() {
    const p = new URLSearchParams();
    ["start_date", "end_date", "status", "company_id", "branch_id"].forEach((k) => {
      const el = byId(k);
      if (el && el.value) p.set(k, el.value);
    });
    const checks = getTypeChecks().filter((x) => x.checked).map((x) => x.value);
    if (checks.length > 0) p.set("ticket_types", checks.join(","));
    return p.toString();
  }

  function setText(id, value) {
    const el = byId(id);
    if (el) el.textContent = value;
  }

  function fmtNum(v) {
    const n = Number(v || 0);
    return new Intl.NumberFormat("en-IN", { maximumFractionDigits: 2 }).format(n);
  }

  async function fetchJson(url) {
    const res = await fetch(url, { headers: { "X-Requested-With": "XMLHttpRequest" } });
    return res.json();
  }

  async function loadSummary() {
    const data = await fetchJson("api/kpi_summary.php?" + qstr());
    if (data.error) return;
    const d = data.data || {};
    setText("kpi_total", fmtNum(d.total_tickets));
    setText("kpi_open", fmtNum(d.open_tickets));
    setText("kpi_closed", fmtNum(d.closed_tickets));
    setText("kpi_closure_rate", fmtNum(d.closure_rate_pct) + "%");
    setText("kpi_tat", fmtNum(d.avg_tat_hours) + "h");
    setText("kpi_sla", fmtNum(d.sla_breach_count) + " (" + fmtNum(d.sla_breach_pct) + "%)");
    setText("kpi_backlog", fmtNum((d.aging_0_2 || 0) + (d.aging_3_7 || 0) + (d.aging_8_15 || 0) + (d.aging_16_plus || 0)));
    setText("kpi_expense", "Rs " + fmtNum(d.expense_price_total));
  }

  async function loadTrends() {
    const trend = await fetchJson("api/trend_series.php?" + qstr());
    const type = await fetchJson("api/type_breakdown.php?" + qstr());
    const status = await fetchJson("api/status_breakdown.php?" + qstr());

    const labels = (trend.data || []).map((x) => x.period);
    const opened = (trend.data || []).map((x) => Number(x.opened_count || 0));
    const closed = (trend.data || []).map((x) => Number(x.closed_count || 0));

    if (window.taTrendChart) window.taTrendChart.destroy();
    const trendCtx = byId("trend_chart");
    if (trendCtx) {
      window.taTrendChart = new Chart(trendCtx, {
        type: "line",
        data: {
          labels,
          datasets: [
            { label: "Opened", data: opened, borderColor: "#2663eb", backgroundColor: "rgba(38,99,235,0.18)", fill: true, tension: 0.35 },
            { label: "Closed", data: closed, borderColor: "#22a06b", backgroundColor: "rgba(34,160,107,0.18)", fill: true, tension: 0.35 },
          ],
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: { legend: { position: "top" } },
          scales: { y: { beginAtZero: true, grid: { color: "#edf2fb" } }, x: { grid: { color: "#f4f7fd" } } },
        },
      });
    }

    const blabels = (type.data || []).map((x) => x.ticket_type);
    const bvalues = (type.data || []).map((x) => Number(x.ticket_count || 0));
    if (window.taTypeChart) window.taTypeChart.destroy();
    const typeCtx = byId("type_chart");
    if (typeCtx) {
      window.taTypeChart = new Chart(typeCtx, {
        type: "pie",
        data: {
          labels: blabels,
          datasets: [{
            label: "Tickets",
            data: bvalues,
            backgroundColor: ["#2563eb", "#14b8a6", "#f59e0b", "#8b5cf6", "#ef4444", "#06b6d4"],
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: { legend: { position: "bottom" } },
        }
      });
    }

    const listEl = byId("type_breakdown_list");
    if (listEl) {
      listEl.innerHTML = "";
      (type.data || []).forEach((row) => {
        const li = document.createElement("li");
        li.textContent = `${row.ticket_type}: ${fmtNum(row.ticket_count)} (Closed ${fmtNum(row.closed_count)})`;
        listEl.appendChild(li);
      });
    }

    const slabels = (status.data || []).map((x) => x.status);
    const svalues = (status.data || []).map((x) => Number(x.ticket_count || 0));
    if (window.taStatusChart) window.taStatusChart.destroy();
    const statusCtx = byId("status_chart");
    if (statusCtx) {
      window.taStatusChart = new Chart(statusCtx, {
        type: "doughnut",
        data: {
          labels: slabels,
          datasets: [{
            label: "Status",
            data: svalues,
            backgroundColor: ["#22c55e", "#f59e0b", "#ef4444", "#06b6d4", "#8b5cf6", "#14b8a6", "#6366f1", "#64748b"],
          }],
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          cutout: "55%",
          plugins: { legend: { position: "bottom" } },
        }
      });
    }
  }

  async function loadDrilldown() {
    const res = await fetchJson("api/drilldown.php?" + qstr());
    const body = byId("drilldown_body");
    if (!body) return;
    body.innerHTML = "";
    (res.data || []).forEach((r) => {
      const tr = document.createElement("tr");
      tr.innerHTML =
        "<td>" + (r.ticket_code || "") + "</td>" +
        "<td>" + (r.ticket_type || "") + "</td>" +
        "<td>" + (r.status || "") + "</td>" +
        "<td>" + (r.priority || "-") + "</td>" +
        "<td>" + (r.company_name || "-") + "</td>" +
        "<td>" + (r.branch_name || "-") + "</td>" +
        "<td>" + (r.assigned_to_name || "-") + "</td>" +
        "<td>" + (r.created_on || "-") + "</td>" +
        "<td>" + (r.due_on || "-") + "</td>" +
        "<td>" + (r.closed_on || "-") + "</td>" +
        "<td>" + fmtNum(r.expense_price || 0) + "</td>";
      body.appendChild(tr);
    });
    setText("drilldown_count", (res.count || 0) + " rows");
  }

  async function loadAll() {
    const hasSelection = getTypeChecks().some((x) => x.checked);
    if (!hasSelection) {
      alert("Please select at least one ticket type.");
      return;
    }
    await loadSummary();
    await loadTrends();
    await loadDrilldown();
  }

  function bindTypeActions() {
    const btnAll = byId("ta_select_all_types");
    const btnClear = byId("ta_clear_all_types");
    if (btnAll) {
      btnAll.addEventListener("click", function () {
        getTypeChecks().forEach((x) => (x.checked = true));
      });
    }
    if (btnClear) {
      btnClear.addEventListener("click", function () {
        getTypeChecks().forEach((x) => (x.checked = false));
      });
    }
  }

  bindTypeActions();
  window.taLoadAll = loadAll;
})();
