(function () {
  const cfg = window.STATE_DASHBOARD_CONFIG || {};
  const workforceApiUrl = cfg.workforceApiUrl || "ajax/get_mis_workforce_data.php";
  const ticketsApiUrl = cfg.ticketsApiUrl || "ajax/get_mis_tickets_data.php";
  const filterOptionsUrl = cfg.filterOptionsUrl || "ajax/get_mis_filter_options.php";
  let reportLoading = false;

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

  function renderBarList(container, rows, valueKey, emptyText) {
    if (!container) return;
    const key = valueKey || "value";
    if (!rows || !rows.length) {
      container.innerHTML = `<li class="smd-empty">${emptyText || "No data in selected period"}</li>`;
      return;
    }
    const m = maxVal(rows, key);
    container.innerHTML = rows.map((r) => {
      const v = Number(r[key] ?? r.value ?? 0);
      const pct = Math.max(4, Math.round((v / m) * 100));
      return `<li class="smd-bar-item">
        <span>${esc(r.label)}</span><strong>${fmt(v)}</strong>
        <div class="smd-bar-track"><div class="smd-bar-fill" style="width:${pct}%"></div></div>
      </li>`;
    }).join("");
  }

  function renderChips(container, rows) {
    if (!container) return;
    if (!rows || !rows.length) {
      container.innerHTML = '<span class="smd-chip"><b>0</b> None in period</span>';
      return;
    }
    container.innerHTML = rows.map((r) =>
      `<span class="smd-chip"><b>${fmt(r.value)}</b>${esc(r.label)}</span>`
    ).join("");
  }

  function renderTypeMatrix(container, rows) {
    if (!container) return;
    if (!rows || !rows.length) {
      container.innerHTML = "<tr><td colspan='3' class='smd-empty'>No ticket activity in selected period</td></tr>";
      return;
    }
    container.innerHTML = rows.map((r) => `
      <tr>
        <td><strong>${esc(r.ticket_type)}</strong></td>
        <td class="smd-num-open">${fmt(r.opened)}</td>
        <td class="smd-num-closed">${fmt(r.closed)}</td>
      </tr>
    `).join("");
  }

  function renderEmployeeTable(container, rows) {
    if (!container) return;
    if (!rows || !rows.length) {
      container.innerHTML = "<tr><td colspan='4' class='smd-empty'>No technician activity in period</td></tr>";
      return;
    }
    container.innerHTML = rows.map((r, i) => `
      <tr>
        <td class="smd-rank">#${i + 1}</td>
        <td>${esc(r.label)}</td>
        <td class="smd-num-open">${fmt(r.opened)}</td>
        <td class="smd-num-closed">${fmt(r.closed)}</td>
      </tr>
    `).join("");
  }

  function renderWorkforceEmployees(container, rows) {
    if (!container) return;
    if (!rows || !rows.length) {
      container.innerHTML = "<tr><td colspan='8' class='smd-empty'>No employees in this state</td></tr>";
      return;
    }
    container.innerHTML = rows.map((r) => `
      <tr>
        <td><strong>${esc(r.name)}</strong><br><span class="text-muted small">${esc(r.employee_number || "")}</span></td>
        <td>${r.is_technician ? '<span class="mis-role-tech">Technician</span>' : esc(r.role_label || "Employee")}</td>
        <td>${esc(r.designation || "—")}</td>
        <td class="smd-num-open">${fmt(r.tickets_assigned)}</td>
        <td class="smd-num-closed">${fmt(r.tickets_closed)}</td>
        <td class="mis-num-wip">${fmt(r.tickets_wip)}</td>
        <td>${fmt(r.convenience_count)} <span class="text-muted small">${fmtMoney(r.convenience_amount)}</span></td>
        <td>${fmtMoney(r.in_hand_salary)}</td>
      </tr>
    `).join("");
  }

  function renderWorkforceSummary(wf) {
    wf = wf || {};
    const conv = wf.convenience || {};
    if (el("wf_employees")) el("wf_employees").textContent = fmt(wf.total_employees);
    if (el("wf_technicians")) el("wf_technicians").textContent = fmt(wf.technician_count);
    if (el("wf_conv_count")) el("wf_conv_count").textContent = fmt(conv.total_count);
    if (el("wf_conv_amount")) el("wf_conv_amount").textContent = fmtMoney(conv.total_amount);
    if (el("wf_conv_paid_count")) el("wf_conv_paid_count").textContent = fmt(conv.payment_done_count);
    if (el("wf_conv_paid_amount")) el("wf_conv_paid_amount").textContent = fmtMoney(conv.payment_done_amount);
  }

  function renderFeed(container, opened, closed, history) {
    if (!container) return;
    const items = [];
    (history || []).forEach((r) => {
      items.push({
        type: "status", code: r.ticket_code, ttype: r.ticket_type, status: r.status,
        who: r.assigned_to, co: r.company_name, extra: r.branch_name || "",
        time: (r.event_date || "") + (r.event_time ? " " + r.event_time : ""),
      });
    });
    (opened || []).forEach((r) => {
      items.push({
        type: "opened", code: r.ticket_code, ttype: r.ticket_type, status: r.status,
        who: r.assigned_to, co: r.company_name, extra: r.branch_name,
        time: (r.created_date || "") + (r.created_time ? " " + r.created_time : ""),
      });
    });
    (closed || []).forEach((r) => {
      items.push({
        type: "closed", code: r.ticket_code, ttype: r.ticket_type, status: r.status,
        who: r.assigned_to, co: r.company_name, extra: r.branch_name,
        time: r.close_date || "",
      });
    });
    if (!items.length) {
      container.innerHTML = '<div class="smd-empty">No activity in the selected period.</div>';
      return;
    }
    const tagClass = (t) => (t === "opened" ? "smd-tag-open" : t === "closed" ? "smd-tag-closed" : "smd-tag-status");
    const tagLabel = (t) => (t === "opened" ? "OPENED" : t === "closed" ? "CLOSED" : "STATUS");
    container.innerHTML = items.slice(0, 50).map((it) => `
      <div class="smd-feed-row">
        <span class="smd-tag ${tagClass(it.type)}">${tagLabel(it.type)}</span>
        <div>
          <strong>${esc(it.code)}</strong> · ${esc(it.ttype)} · ${esc(it.status)}<br>
          <span class="text-muted">${esc(it.who)} · ${esc(it.extra)} · ${esc(it.co)}${it.time ? " · " + esc(it.time) : ""}</span>
        </div>
      </div>
    `).join("");
  }

  function updateScopeHero(stateName) {
    const box = el("smd_scope_summary");
    if (!box) return;
    if (!stateName) {
      box.innerHTML = '<span class="smd-scope-chip smd-scope-chip--muted"><i class="fa-solid fa-filter"></i> No state loaded yet</span>' +
        '<p class="smd-scope-hint">Choose a state and period, then click <strong>Load report</strong>.</p>';
      return;
    }
    box.innerHTML = '<span class="smd-scope-chip"><i class="fa-solid fa-map-location-dot"></i> ' + esc(stateName) + '</span>' +
      '<p class="smd-scope-hint">Showing data for this state only.</p>';
  }

  function getSelectedState() {
    const stateSel = el("smd_state");
    return stateSel ? String(stateSel.value || "").trim() : "";
  }

  function parseCustomDateRange(raw) {
    raw = String(raw || "").trim();
    if (!raw) return null;
    const isoPair = raw.match(/^(\d{4}-\d{2}-\d{2})\s*(?:[-–]|to)\s*(\d{4}-\d{2}-\d{2})$/i);
    if (isoPair) {
      return { start: isoPair[1], end: isoPair[2] };
    }
    const parts = raw.split(/\s+(?:[-–]|to)\s+/i);
    if (parts.length === 2) {
      return { start: parts[0].trim(), end: parts[1].trim() };
    }
    return null;
  }

  function readCustomDateRange() {
    const input = el("smd_date_range");
    if (!input) return null;
    if (window.jQuery && window.moment) {
      const drp = jQuery(input).data("daterangepicker");
      if (drp && drp.startDate && drp.endDate) {
        return {
          start: drp.startDate.format("YYYY-MM-DD"),
          end: drp.endDate.format("YYYY-MM-DD"),
        };
      }
    }
    return parseCustomDateRange(input.value);
  }

  function initDateRangePicker() {
    const input = el("smd_date_range");
    if (!input || !window.jQuery || !jQuery.fn.daterangepicker || !window.moment) return;

    const $input = jQuery(input);
    const existing = $input.data("daterangepicker");
    if (existing) {
      existing.remove();
    }

    const parsed = parseCustomDateRange(input.value);
    const opts = {
      locale: { format: "YYYY-MM-DD", separator: " - " },
      autoUpdateInput: true,
      linkedCalendars: false,
      showDropdowns: true,
    };
    if (parsed) {
      opts.startDate = moment(parsed.start, "YYYY-MM-DD");
      opts.endDate = moment(parsed.end, "YYYY-MM-DD");
    }

    $input.daterangepicker(opts);
  }

  function getFilterParams() {
    const params = new URLSearchParams();
    const stateName = getSelectedState();
    if (!stateName) throw new Error("Please select a state.");
    params.set("state", stateName);

    const preset = el("smd_preset");
    params.set("preset", preset ? preset.value : "current_fy");

    if (preset && preset.value === "custom") {
      const range = readCustomDateRange();
      if (range) {
        params.set("start_date", range.start);
        params.set("end_date", range.end);
      }
    }

    const corp = el("smd_corporate");
    if (corp && corp.value && corp.value !== "0") params.set("corporate_id", corp.value);
    const branch = el("smd_branch");
    if (branch && branch.value && branch.value !== "0") params.set("branch_id", branch.value);
    const tt = el("smd_ticket_type");
    if (tt && tt.value) params.set("ticket_type", tt.value);
    return params;
  }

  function setReportLoading(isLoading) {
    reportLoading = isLoading;
    const overlay = el("smd_loading");
    const content = el("smd_content");
    const placeholder = el("smd_placeholder");
    const ticketsLoading = el("mis_tickets_loading");
    const ticketsBody = el("mis_tickets_body");
    const btn = el("smd_apply_btn");

    if (overlay) overlay.style.display = isLoading ? "block" : "none";
    if (placeholder && isLoading) placeholder.style.display = "none";
    if (content && isLoading) content.style.display = "";
    if (content) content.style.opacity = isLoading ? "0.92" : "1";
    if (ticketsLoading) ticketsLoading.style.display = isLoading ? "flex" : "none";
    if (ticketsBody) ticketsBody.classList.toggle("mis-tickets-pending", isLoading);
    if (btn) {
      btn.disabled = isLoading;
      btn.textContent = isLoading ? "Loading…" : "Load report";
    }
  }

  function applyWorkforceData(d) {
    const period = d.period || {};
    updateScopeHero(d.selected_state || getSelectedState());
    if (el("smd_period_label")) el("smd_period_label").textContent = period.label || "—";
    if (el("smd_period_range") && period.start && period.end) {
      el("smd_period_range").textContent = period.start + " — " + period.end;
    }
    if (el("smd_updated")) el("smd_updated").textContent = d.generated_at || "—";
    renderWorkforceSummary(d.workforce);
    renderWorkforceEmployees(el("mis_employee_rows"), d.employees);
    const placeholder = el("smd_placeholder");
    const content = el("smd_content");
    if (placeholder) placeholder.style.display = "none";
    if (content) content.style.display = "";
  }

  function applyTicketsData(d) {
    const s = d.summary || {};
    const ctx = d.context || {};
    const period = d.period || {};

    if (el("smd_period_label") && period.label) el("smd_period_label").textContent = period.label;
    if (el("smd_period_range") && period.start && period.end) {
      el("smd_period_range").textContent = period.start + " — " + period.end;
    }
    if (el("smd_updated") && d.generated_at) el("smd_updated").textContent = d.generated_at;

    if (el("k_opened")) el("k_opened").textContent = fmt(s.opened);
    if (el("k_closed")) el("k_closed").textContent = fmt(s.closed);
    if (el("k_quote_sent")) el("k_quote_sent").textContent = fmt(s.quote_sent);
    if (el("k_quote_approved")) el("k_quote_approved").textContent = fmt(s.quote_approved);
    if (el("k_quote_pending")) el("k_quote_pending").textContent = fmt(s.quote_pending);
    if (el("k_quote_rejected")) el("k_quote_rejected").textContent = fmt(s.quote_rejected);
    if (el("k_quote_sent_amt")) el("k_quote_sent_amt").textContent = fmtMoney(s.quote_sent_amount);
    if (el("k_quote_approved_amt")) el("k_quote_approved_amt").textContent = fmtMoney(s.quote_approved_amount);
    if (el("k_quote_pending_amt")) el("k_quote_pending_amt").textContent = fmtMoney(s.quote_pending_amount);
    if (el("k_quote_rejected_amt")) el("k_quote_rejected_amt").textContent = fmtMoney(s.quote_rejected_amount);

    if (el("qc_sent_count")) el("qc_sent_count").textContent = fmt(s.quote_sent);
    if (el("qc_approved_count")) el("qc_approved_count").textContent = fmt(s.quote_approved);
    if (el("qc_pending_count")) el("qc_pending_count").textContent = fmt(s.quote_pending);
    if (el("qc_rejected_count")) el("qc_rejected_count").textContent = fmt(s.quote_rejected);
    if (el("qc_sent_amount")) el("qc_sent_amount").textContent = fmtMoney(s.quote_sent_amount);
    if (el("qc_approved_amount")) el("qc_approved_amount").textContent = fmtMoney(s.quote_approved_amount);
    if (el("qc_pending_amount")) el("qc_pending_amount").textContent = fmtMoney(s.quote_pending_amount);
    if (el("qc_rejected_amount")) el("qc_rejected_amount").textContent = fmtMoney(s.quote_rejected_amount);
    if (el("k_status_updates")) el("k_status_updates").textContent = fmt(s.status_updates);

    if (el("ctx_branches")) el("ctx_branches").textContent = fmt(ctx.branches);
    if (el("ctx_companies")) el("ctx_companies").textContent = fmt(ctx.companies);

    renderChips(el("smd_workflow"), d.workflow);
    renderChips(el("smd_status_moves"), d.status_moves);
    renderTypeMatrix(el("smd_type_matrix_body"), d.type_matrix);
    renderChips(el("smd_opened_status"), d.opened_by_status);
    renderChips(el("smd_closed_status"), d.closed_by_status);
    renderEmployeeTable(el("smd_employee_rows"), d.top_employees);
    renderBarList(el("smd_company_bars"), d.top_companies);
    renderBarList(el("smd_branch_bars"), d.top_branches);
    renderBarList(el("smd_type_opened"), d.type_opened);
    renderBarList(el("smd_type_closed"), d.type_closed);
    renderFeed(el("smd_feed"), d.recent_opened, d.recent_closed, d.status_history_feed);

    if (typeof window.renderStateDashboardCharts === "function") {
      const charts = d.charts || {};
      const ticketsBody = el("mis_tickets_body");
      if (ticketsBody) ticketsBody.classList.remove("mis-tickets-pending");
      window.renderStateDashboardCharts(charts);
    }

    const ticketsLoading = el("mis_tickets_loading");
    if (ticketsLoading) ticketsLoading.style.display = "none";
  }

  async function fetchJson(url) {
    const res = await fetch(url, { cache: "no-store", credentials: "same-origin" });
    const text = await res.text();
    let json = null;
    try {
      json = text ? JSON.parse(text) : null;
    } catch (e) {
      throw new Error(res.ok ? "Invalid response from server" : "Server error (HTTP " + res.status + ")");
    }
    if (!res.ok || (json && json.error)) {
      const msg = (json && json.message) ? json.message : ("Request failed (HTTP " + res.status + ")");
      throw new Error(msg);
    }
    if (!json) {
      throw new Error("Empty response from server");
    }
    return json;
  }

  async function fetchData() {
    const params = getFilterParams();
    params.set("_", String(Date.now()));
    const qs = params.toString();

    const wfJson = await fetchJson(workforceApiUrl + "?" + qs);
    applyWorkforceData(wfJson.data || {});

    const ticketsLoading = el("mis_tickets_loading");
    const ticketsBody = el("mis_tickets_body");
    if (ticketsLoading) ticketsLoading.style.display = "flex";
    if (ticketsBody) ticketsBody.classList.add("mis-tickets-pending");

    const tkJson = await fetchJson(ticketsApiUrl + "?" + qs);
    applyTicketsData(tkJson.data || {});

    const errBox = el("smd_error");
    if (errBox) errBox.style.display = "none";
  }

  function showError(err) {
    const box = el("smd_error");
    if (box) {
      box.style.display = "block";
      box.textContent = (err && err.message ? err.message : "Could not load report.");
    }
    console.error("MIS report:", err);
  }

  function populateSelect(selectEl, options, defaultLabel, valueKey, labelKey, dataAttrs) {
    if (!selectEl) return;
    selectEl.innerHTML = "";
    const first = document.createElement("option");
    first.value = "0";
    first.textContent = defaultLabel;
    selectEl.appendChild(first);
    (options || []).forEach((opt) => {
      const o = document.createElement("option");
      o.value = String(opt[valueKey]);
      o.textContent = String(opt[labelKey]);
      if (dataAttrs) {
        dataAttrs.forEach((attr) => {
          if (opt[attr] != null) o.setAttribute("data-" + attr.replace(/_/g, "-"), String(opt[attr]));
        });
      }
      selectEl.appendChild(o);
    });
    selectEl.disabled = false;
  }

  async function loadFilterOptionsForState(stateName) {
    const corp = el("smd_corporate");
    const branch = el("smd_branch");
    if (!stateName) {
      if (corp) { corp.innerHTML = '<option value="0">Select state first</option>'; corp.disabled = true; }
      if (branch) { branch.innerHTML = '<option value="0">Select state first</option>'; branch.disabled = true; }
      return;
    }
    const url = filterOptionsUrl + "?state=" + encodeURIComponent(stateName) + "&_=" + Date.now();
    const json = await fetchJson(url);
    populateSelect(corp, json.companies, "All companies", "id", "name");
    populateSelect(branch, json.branches, "All branches", "id", "name", ["corporate_id"]);
    if (window.jQuery && jQuery.fn.select2) {
      jQuery(corp).trigger("change.select2");
      jQuery(branch).trigger("change.select2");
    }
    filterBranchesByCompany();
  }

  function filterBranchesByCompany() {
    const corp = el("smd_corporate");
    const branch = el("smd_branch");
    if (!corp || !branch || branch.tagName !== "SELECT") return;
    const corpId = String(corp.value || "0");
    let needReset = false;
    Array.from(branch.options).forEach((opt, idx) => {
      if (idx === 0) { opt.disabled = false; return; }
      const match = corpId === "0" || String(opt.getAttribute("data-corporate")) === corpId;
      opt.disabled = !match;
      if (!match && opt.selected) needReset = true;
    });
    if (needReset) {
      branch.value = "0";
      if (window.jQuery && jQuery.fn.select2) jQuery(branch).val("0").trigger("change");
    }
  }

  function initSearchableSelects() {
    if (!window.jQuery || !jQuery.fn.select2) return;
    const select2Opts = { width: "100%", minimumResultsForSearch: 0, dropdownAutoWidth: false };
    jQuery(".smd-select2").each(function () {
      const $el = jQuery(this);
      if ($el.data("select2")) $el.select2("destroy");
      $el.select2(select2Opts);
    });
    jQuery(".smd-select2").on("select2:select", function () { jQuery(this).select2("close"); });
  }

  function bindFilters() {
    initSearchableSelects();

    const preset = el("smd_preset");
    const customWrap = document.querySelector(".smd-custom-dates");
    if (preset) {
      const onPresetChange = function () {
        const isCustom = preset.value === "custom";
        if (customWrap) customWrap.style.display = isCustom ? "" : "none";
        if (isCustom) initDateRangePicker();
      };
      preset.addEventListener("change", onPresetChange);
      if (window.jQuery) jQuery("#smd_preset").on("change", onPresetChange);
    }

    initDateRangePicker();

    const onStateChange = function () {
      const stateName = getSelectedState();
      loadFilterOptionsForState(stateName).catch(showError);
    };
    if (window.jQuery) {
      jQuery("#smd_state").on("change", onStateChange);
      jQuery("#smd_corporate").on("change", filterBranchesByCompany);
    } else {
      const stateSel = el("smd_state");
      if (stateSel) stateSel.addEventListener("change", onStateChange);
      const corp = el("smd_corporate");
      if (corp) corp.addEventListener("change", filterBranchesByCompany);
    }

    const btn = el("smd_apply_btn");
    if (btn) {
      btn.addEventListener("click", function () {
        if (reportLoading) return;
        try {
          getFilterParams();
        } catch (e) {
          showError(e);
          return;
        }
        setReportLoading(true);
        const stateName = getSelectedState();
        loadFilterOptionsForState(stateName).catch(function () { /* optional; report can still load */ });
        fetchData().catch(showError).finally(() => setReportLoading(false));
      });
    }
  }

  function start() {
    bindFilters();
    const placeholder = el("smd_placeholder");
    const content = el("smd_content");
    if (placeholder) placeholder.style.display = "";
    if (content) content.style.display = "none";
    if (el("smd_loading")) el("smd_loading").style.display = "none";
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", start);
  } else {
    start();
  }
})();
