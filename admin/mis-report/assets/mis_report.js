(function () {
  function el(id) {
    return document.getElementById(id);
  }

  function getFilterSummary() {
    const parts = [];
    const period = el("smd_period_label");
    const range = el("smd_period_range");
    if (period && period.textContent) {
      parts.push("Period: " + period.textContent.trim());
    }
    if (range && range.textContent) {
      parts.push(range.textContent.trim());
    }

    const stateSel = el("smd_state");
    if (stateSel && stateSel.value) {
      const opt = stateSel.options[stateSel.selectedIndex];
      parts.push("State: " + (opt ? opt.text : stateSel.value));
    }

    const corp = el("smd_corporate");
    if (corp && corp.value && corp.value !== "0") {
      const opt = corp.options[corp.selectedIndex];
      parts.push("Company: " + (opt ? opt.text : corp.value));
    }

    const branch = el("smd_branch");
    if (branch && branch.value && branch.value !== "0") {
      const opt = branch.options[branch.selectedIndex];
      parts.push("Branch: " + (opt ? opt.text : branch.value));
    }

    const tt = el("smd_ticket_type");
    if (tt && tt.value) {
      parts.push("Ticket type: " + tt.value);
    }

    return parts.join(" · ");
  }

  function updatePrintHeader() {
    const header = el("mis_print_header");
    if (!header) return;
    const summary = getFilterSummary();
    const updated = el("smd_updated");
    header.innerHTML =
      "<h2 style=\"margin:0 0 0.25rem;font-size:1.25rem;\">MIS Report</h2>" +
      "<div style=\"font-size:0.85rem;\">" + summary + "</div>" +
      (updated && updated.textContent
        ? "<div style=\"font-size:0.8rem;color:#64748b;\">Generated: " + updated.textContent + "</div>"
        : "");
  }

  function bindPrint() {
    const btn = el("smd_print_btn");
    if (!btn) return;
    btn.addEventListener("click", function () {
      updatePrintHeader();
      window.print();
    });
  }

  function bindNav() {
    if (window.jQuery) {
      jQuery(document).ready(function () {
        jQuery("#js-nav-menu").addClass("active open");
        jQuery("#nav_mis_report").addClass("active");
      });
    }
  }

  function observeDataRefresh() {
    const updated = el("smd_updated");
    if (!updated || typeof MutationObserver === "undefined") return;
    const obs = new MutationObserver(updatePrintHeader);
    obs.observe(updated, { childList: true, characterData: true, subtree: true });
  }

  function start() {
    bindPrint();
    bindNav();
    observeDataRefresh();
    updatePrintHeader();
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", start);
  } else {
    start();
  }
})();
