(function (global) {
  const PALETTE = [
    "#2563eb", "#059669", "#f59e0b", "#dc2626", "#7c3aed",
    "#0891b2", "#db2777", "#65a30d", "#ea580c", "#4f46e5",
  ];

  const instances = {};

  function destroyChart(id) {
    if (instances[id]) {
      instances[id].destroy();
      delete instances[id];
    }
  }

  function labelsFromRows(rows, labelKey) {
    return (rows || []).map((r) => String(r[labelKey || "label"] ?? ""));
  }

  function valuesFromRows(rows, valueKey) {
    return (rows || []).map((r) => Number(r[valueKey || "value"] ?? 0));
  }

  function pieDataset(rows, valueKey) {
    const key = valueKey || "value";
    const total = (rows || []).reduce((s, r) => s + Number(r[key] || 0), 0);
    if (total <= 0) return null;
    return {
      labels: labelsFromRows(rows),
      datasets: [{
        data: valuesFromRows(rows, key),
        backgroundColor: PALETTE,
        borderWidth: 2,
        borderColor: "#fff",
      }],
    };
  }

  function renderLine(id, labels, datasets, yLabel) {
    const canvas = document.getElementById(id);
    if (!canvas || typeof Chart === "undefined") return;
    destroyChart(id);
    const labelList = labels || [];
    const chartType = labelList.length === 1 ? "bar" : "line";
    instances[id] = new Chart(canvas, {
      type: chartType,
      data: { labels: labelList, datasets },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: { mode: "index", intersect: false },
        plugins: {
          legend: { position: "top" },
          tooltip: {
            callbacks: {
              label(ctx) {
                const v = ctx.parsed.y;
                if (yLabel === "₹") {
                  return ctx.dataset.label + ": ₹ " + new Intl.NumberFormat("en-IN").format(v);
                }
                return ctx.dataset.label + ": " + new Intl.NumberFormat("en-IN").format(v);
              },
            },
          },
        },
        scales: {
          y: {
            beginAtZero: true,
            ticks: {
              callback(v) {
                if (yLabel === "₹" && v >= 100000) return "₹" + (v / 100000).toFixed(1) + "L";
                if (yLabel === "₹") return "₹" + new Intl.NumberFormat("en-IN").format(v);
                return v;
              },
            },
          },
        },
      },
    });
  }

  function renderBar(id, labels, datasets, horizontal) {
    const canvas = document.getElementById(id);
    if (!canvas || typeof Chart === "undefined") return;
    destroyChart(id);
    const type = horizontal ? "bar" : "bar";
    const indexAxis = horizontal ? "y" : "x";
    instances[id] = new Chart(canvas, {
      type,
      data: { labels: labels || [], datasets },
      options: {
        indexAxis,
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { position: "top" } },
        scales: { x: { beginAtZero: true }, y: { beginAtZero: true } },
      },
    });
  }

  function renderPie(id, rows, valueKey, doughnut) {
    const canvas = document.getElementById(id);
    if (!canvas || typeof Chart === "undefined") return;
    destroyChart(id);
    const data = pieDataset(rows, valueKey);
    if (!data) {
      const ctx = canvas.getContext("2d");
      ctx.clearRect(0, 0, canvas.width, canvas.height);
      return;
    }
    instances[id] = new Chart(canvas, {
      type: doughnut ? "doughnut" : "pie",
      data,
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { position: "right", labels: { boxWidth: 12, font: { size: 11 } } },
          tooltip: {
            callbacks: {
              label(ctx) {
                const v = ctx.parsed;
                const isMoney = valueKey === "value" && id.indexOf("quote") >= 0;
                if (isMoney) return ctx.label + ": ₹ " + new Intl.NumberFormat("en-IN").format(v);
                return ctx.label + ": " + new Intl.NumberFormat("en-IN").format(v);
              },
            },
          },
        },
      },
    });
  }

  function renderStateDashboardCharts(charts) {
    if (!charts) return;
    const c = charts;

    renderLine("chart_monthly_trend", c.monthly_trend?.labels, [
      { label: "Opened", data: c.monthly_trend?.opened || [], borderColor: "#2563eb", backgroundColor: "rgba(37,99,235,0.12)", fill: true, tension: 0.35 },
      { label: "Closed", data: c.monthly_trend?.closed || [], borderColor: "#059669", backgroundColor: "rgba(5,150,105,0.12)", fill: true, tension: 0.35 },
    ]);

    renderLine("chart_monthly_quotes", c.monthly_quotes?.labels, [
      { label: "Approved value", data: c.monthly_quotes?.approved_value || [], borderColor: "#059669", backgroundColor: "rgba(5,150,105,0.15)", fill: true, tension: 0.35 },
      { label: "Sent for approval", data: c.monthly_quotes?.sent_value || [], borderColor: "#f59e0b", backgroundColor: "rgba(245,158,11,0.15)", fill: true, tension: 0.35 },
    ], "₹");

    renderPie("chart_ticket_type_pie", c.ticket_type_pie, "value", true);
    renderPie("chart_quote_value_pie", c.quote_value_pie, "value", true);
    renderPie("chart_opened_status_pie", c.opened_status_pie, "value", false);

    renderBar("chart_type_comparison", c.type_comparison?.labels, [
      { label: "Opened", data: c.type_comparison?.opened || [], backgroundColor: "rgba(37,99,235,0.75)" },
      { label: "Closed", data: c.type_comparison?.closed || [], backgroundColor: "rgba(5,150,105,0.75)" },
    ]);

    renderBar("chart_top_branches", labelsFromRows(c.top_branches), [
      { label: "Activity", data: valuesFromRows(c.top_branches), backgroundColor: "rgba(79,70,229,0.8)" },
    ], true);

    renderBar("chart_top_companies", labelsFromRows(c.top_companies), [
      { label: "Activity", data: valuesFromRows(c.top_companies), backgroundColor: "rgba(8,145,178,0.8)" },
    ], true);

    const techLabels = labelsFromRows(c.top_technicians);
    renderBar("chart_top_technicians", techLabels, [
      { label: "Opened", data: (c.top_technicians || []).map((r) => r.opened || 0), backgroundColor: "rgba(37,99,235,0.75)" },
      { label: "Closed", data: (c.top_technicians || []).map((r) => r.closed || 0), backgroundColor: "rgba(5,150,105,0.75)" },
    ], true);

    renderBar("chart_workflow_bar", labelsFromRows(c.workflow_bar), [
      { label: "Status movements", data: valuesFromRows(c.workflow_bar), backgroundColor: "rgba(124,58,237,0.8)" },
    ]);
  }

  global.renderStateDashboardCharts = renderStateDashboardCharts;
})(window);
