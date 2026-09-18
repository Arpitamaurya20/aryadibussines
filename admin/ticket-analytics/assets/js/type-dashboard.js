(function () {
  function byId(id) { return document.getElementById(id); }
  function fmt(v) { return new Intl.NumberFormat("en-IN", { maximumFractionDigits: 2 }).format(Number(v || 0)); }
  function colorAt(i) {
    const colors = ["#23a526", "#f4b400", "#1e88e5", "#e91e63", "#ef6c00", "#7e57c2", "#26a69a", "#e53935", "#1565c0", "#6d4c41"];
    return colors[i % colors.length];
  }
  function q() {
    const p = new URLSearchParams();
    p.set("start_date", byId("start_date").value);
    p.set("end_date", byId("end_date").value);
    p.set("type", byId("ticket_type").value);
    return p.toString();
  }
  async function api() {
    const res = await fetch("api/type_dashboard.php?" + q(), { headers: { "X-Requested-With": "XMLHttpRequest" } });
    return res.json();
  }
  function setText(id, value) { const el = byId(id); if (el) el.textContent = value; }
  function chart(id, type, labels, data, label) {
    const el = byId(id);
    if (!el) return;
    if (window[id + "_inst"]) window[id + "_inst"].destroy();
    const colors = labels.map((_, i) => colorAt(i));
    window[id + "_inst"] = new Chart(el, {
      type,
      data: { labels, datasets: [{ label: label || "Count", data, backgroundColor: colors, borderColor: colors, fill: type === "line" }] },
      options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: "bottom" } } }
    });
  }
  function renderStatusCards(rows, total) {
    const wrap = byId("status_cards_wrap");
    if (!wrap) return;
    wrap.innerHTML = "";
    (rows || []).forEach((r, i) => {
      const pct = total > 0 ? ((Number(r.value || 0) / total) * 100).toFixed(2) : "0.00";
      const card = document.createElement("div");
      card.className = "ta-status-card";
      card.style.background = `linear-gradient(135deg, ${colorAt(i)} 0%, ${colorAt(i)}cc 100%)`;
      card.innerHTML = `<div class="ta-status-count">${fmt(r.value)}</div><div class="ta-status-label">${r.label}</div><div class="ta-status-pct">${pct}%</div>`;
      wrap.appendChild(card);
    });
  }
  function table(rows) {
    const body = byId("type_table_body");
    if (!body) return;
    body.innerHTML = "";
    (rows || []).forEach((r) => {
      const tr = document.createElement("tr");
      tr.innerHTML = `<td>${r.ticket_code || ""}</td><td>${r.status || ""}</td><td>${r.priority || ""}</td><td>${r.company_name || ""}</td><td>${r.branch_name || ""}</td><td>${r.assigned_to || ""}</td><td>${r.created_on || ""}</td><td>${r.due_on || ""}</td><td>${r.closed_on || ""}</td>`;
      body.appendChild(tr);
    });
    setText("table_rows", `${(rows || []).length} rows`);
  }
  function labels(rows) { return (rows || []).map(r => r.label); }
  function values(rows) { return (rows || []).map(r => Number(r.value || 0)); }

  async function loadTypeDashboard() {
    const payload = await api();
    if (payload.error) return;
    const d = payload.data || {};
    const s = d.summary || {};
    setText("dashboard_title_type", payload.type || "R&M");
    setText("k_total", fmt(s.total_tickets));
    setText("k_open", fmt(s.open_tickets));
    setText("k_closed", fmt(s.closed_tickets));
    setText("k_closure", fmt(s.closure_rate_pct) + "%");
    setText("k_tat", fmt(s.avg_tat_hours) + "h");
    setText("k_sla", fmt(s.sla_breach_count) + ` (${fmt(s.sla_breach_pct)}%)`);

    renderStatusCards(d.status_cards || [], Number(s.total_tickets || 0));

    const trend = d.trend || [];
    chart("trend_chart", "line", trend.map(x => x.period), trend.map(x => Number(x.opened_count || 0)), "Opened");
    if (window.trend_chart_inst) {
      window.trend_chart_inst.data.datasets.push({
        label: "Closed",
        data: trend.map(x => Number(x.closed_count || 0)),
        borderColor: "#1e88e5",
        backgroundColor: "rgba(30,136,229,.2)",
        fill: true
      });
      window.trend_chart_inst.update();
    }
    chart("status_chart", "bar", labels(d.status_distribution), values(d.status_distribution), "Status");
    chart("regional_chart", "doughnut", labels(d.regional_distribution), values(d.regional_distribution), "Regional");
    chart("companies_chart", "bar", labels(d.top_companies), values(d.top_companies), "Companies");
    chart("branches_chart", "bar", labels(d.top_branches), values(d.top_branches), "Branches");
    chart("assignees_chart", "bar", labels(d.top_assignees), values(d.top_assignees), "Assignees");
    chart("priority_chart", "bar", labels(d.top_priorities), values(d.top_priorities), "Priority");
    table(d.drilldown || []);
  }

  window.loadTypeDashboard = loadTypeDashboard;
})();
