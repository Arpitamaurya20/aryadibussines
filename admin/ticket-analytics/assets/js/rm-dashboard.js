(function () {
  function byId(id) { return document.getElementById(id); }
  function fmt(v) { return new Intl.NumberFormat("en-IN", { maximumFractionDigits: 2 }).format(Number(v || 0)); }
  function num(v) { return Number(v || 0); }
  function p(params) {
    const s = new URLSearchParams();
    s.set("start_date", byId("start_date").value);
    s.set("end_date", byId("end_date").value);
    return s.toString();
  }
  async function getData() {
    const res = await fetch("api/rm_dashboard.php?" + p(), { headers: { "X-Requested-With": "XMLHttpRequest" } });
    return res.json();
  }
  function setKpi(id, v) { const el = byId(id); if (el) el.textContent = v; }
  function chartInstanceKey(id) { return id + "_inst"; }
  function colorAt(i) {
    const colors = ["#22c55e", "#f59e0b", "#ef4444", "#8b5cf6", "#06b6d4", "#6366f1", "#14b8a6", "#2563eb", "#7c3aed", "#f97316"];
    return colors[i % colors.length];
  }
  function makeChart(id, type, chartLabels, chartData, datasetLabel) {
    const el = byId(id);
    if (!el) return null;
    const key = chartInstanceKey(id);
    if (window[key]) {
      window[key].destroy();
      window[key] = null;
    }
    if (!chartLabels.length || !chartData.length) {
      return null;
    }
    const bg = chartLabels.map((_, i) => colorAt(i));
    const options = {
      responsive: true,
      maintainAspectRatio: false,
      plugins: { legend: { position: type === "line" ? "top" : "bottom" } },
    };
    if (type === "bar" || type === "line") {
      options.scales = {
        y: { beginAtZero: true, grid: { color: "#edf2fb" } },
        x: { grid: { color: type === "line" ? "#f4f7fd" : "transparent" } },
      };
    }
    window[key] = new Chart(el, {
      type,
      data: {
        labels: chartLabels,
        datasets: [{
          label: datasetLabel || "Count",
          data: chartData,
          backgroundColor: bg,
          borderColor: bg,
          fill: type === "line",
          tension: type === "line" ? 0.35 : 0,
        }],
      },
      options,
    });
    return window[key];
  }
  function fillTable(rows) {
    const body = byId("rm_table_body");
    if (!body) return;
    body.innerHTML = "";
    rows.forEach((r) => {
      const tr = document.createElement("tr");
      tr.innerHTML =
        `<td>${r.ticket_code || ""}</td><td>${r.status || ""}</td><td>${r.priority || ""}</td><td>${r.service_name || ""}</td><td>${r.subservice_name || ""}</td><td>${r.company_name || ""}</td><td>${r.branch_name || ""}</td><td>${r.assigned_to_name || ""}</td><td>${r.created_on || ""}</td><td>${r.due_on || ""}</td><td>${r.closed_on || ""}</td><td>${fmt(r.expense_price)}</td><td>${fmt(r.customer_price)}</td>`;
      body.appendChild(tr);
    });
    setKpi("rm_rows", `${rows.length} rows`);
  }
  function chartLabels(rows) { return (rows || []).map((x) => String(x.label || "Unknown")); }
  function chartValues(rows) { return (rows || []).map((x) => num(x.value)); }
  function countBy(rows, key, limit) {
    const map = new Map();
    (rows || []).forEach((r) => {
      const label = (r[key] && String(r[key]).trim()) ? String(r[key]).trim() : "Unknown";
      map.set(label, (map.get(label) || 0) + 1);
    });
    return Array.from(map.entries())
      .map(([label, value]) => ({ label, value }))
      .sort((a, b) => b.value - a.value)
      .slice(0, limit || 10);
  }

  async function load() {
    let payload;
    try {
      payload = await getData();
    } catch (e) {
      console.error("R&M API fetch failed", e);
      alert("R&M API fetch failed. Please refresh once.");
      return;
    }
    if (payload.error) {
      alert(payload.message || "R&M API returned error.");
      return;
    }
    const d = payload.data || {};
    const s = d.summary || {};
    setKpi("k_total", fmt(s.total_tickets));
    setKpi("k_open", fmt(s.open_tickets));
    setKpi("k_closed", fmt(s.closed_tickets));
    setKpi("k_closure", fmt(s.closure_rate_pct) + "%");
    setKpi("k_tat", fmt(s.avg_tat_hours) + "h");
    setKpi("k_sla", fmt(s.sla_breach_count) + ` (${fmt(s.sla_breach_pct)}%)`);
    setKpi(
      "k_backlog",
      fmt(num(s.aging_0_2) + num(s.aging_3_7) + num(s.aging_8_15) + num(s.aging_16_plus))
    );
    setKpi("k_expense", "Rs " + fmt(s.expense_price_total));
    setKpi("k_customer", "Rs " + fmt(s.customer_price_total));
    setKpi("k_margin", "Rs " + fmt(s.margin_total));
    setKpi("k_cpt", "Rs " + fmt(s.cost_per_ticket));

    const totalTickets = num(s.total_tickets);
    const emptyMsg = byId("rm_empty_msg");
    if (emptyMsg) {
      emptyMsg.style.display = totalTickets > 0 ? "none" : "block";
    }

    const trend = d.trend || [];
    const trendChart = makeChart(
      "rm_trend_chart",
      "line",
      trend.map((x) => x.period),
      trend.map((x) => num(x.opened_count)),
      "Opened"
    );
    if (trendChart) {
      trendChart.data.datasets.push({
        label: "Closed",
        data: trend.map((x) => num(x.closed_count)),
        borderColor: "#22a06b",
        backgroundColor: "rgba(34,160,107,.18)",
        fill: true,
        tension: 0.35,
      });
      trendChart.update();
    }

    const drillRows = d.drilldown || [];
    const debugEl = byId("rm_debug_counts");
    if (debugEl) {
      debugEl.textContent =
        `Data counts -> trend:${(d.trend || []).length}, status:${(d.status || []).length}, priority:${(d.priority || []).length}, companies:${(d.company || []).length}, branches:${(d.branch || []).length}, assignees:${(d.assignee || []).length}, services:${(d.service || []).length}, drilldown:${drillRows.length}`;
    }

    const statusRows = (d.status && d.status.length) ? d.status : countBy(drillRows, "status", 15);
    const priorityRows = (d.priority && d.priority.length) ? d.priority : countBy(drillRows, "priority", 10);
    const companyRows = (d.company && d.company.length) ? d.company : countBy(drillRows, "company_name", 10);
    const branchRows = (d.branch && d.branch.length) ? d.branch : countBy(drillRows, "branch_name", 10);
    const assigneeRows = (d.assignee && d.assignee.length) ? d.assignee : countBy(drillRows, "assigned_to_name", 10);
    const serviceRows = (d.service && d.service.length) ? d.service : countBy(drillRows, "service_name", 10);

    makeChart("rm_status_chart", "doughnut", chartLabels(statusRows), chartValues(statusRows), "Status");
    makeChart("rm_priority_chart", "bar", chartLabels(priorityRows), chartValues(priorityRows), "Priority");
    makeChart("rm_company_chart", "bar", chartLabels(companyRows), chartValues(companyRows), "Companies");
    makeChart("rm_branch_chart", "bar", chartLabels(branchRows), chartValues(branchRows), "Branches");
    makeChart("rm_assignee_chart", "bar", chartLabels(assigneeRows), chartValues(assigneeRows), "Assignees");
    makeChart("rm_service_chart", "bar", chartLabels(serviceRows), chartValues(serviceRows), "Services");
    fillTable(drillRows);
  }

  window.loadRmDashboard = load;
})();
