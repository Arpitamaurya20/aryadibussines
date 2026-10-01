var auditTicketTable = null;
var auditTicketSelectedAudits = [];

$(document).ready(function () {
  if ($("#nav_audit_tickets").length) {
    $("#nav_audit_tickets").addClass("active open");
    if ($("#nav_audit_tickets_list").length) {
      $("#nav_audit_tickets_list").addClass("active");
    }
  }
  if ($("#nav_audit_tickets_raise").length && window.location.href.indexOf("view-raise-audit-ticket") !== -1) {
    $("#nav_audit_tickets_raise").addClass("active");
  }

  if ($("#corporate_name").length) {
    $("#corporate_name").select2();
    $("#branch_name").select2();
    $("#master_audit_id").select2();
    $("#sub_audit_id").select2({
      placeholder: "Select sub audit(s)",
      allowClear: true,
    });
  }

  if ($("#audit_ticket_table").length) {
    initAuditTicketTable();
  }
});

function initAuditTicketTable() {
  auditTicketTable = $("#audit_ticket_table").DataTable({
    processing: true,
    serverSide: true,
    ajax: {
      url: "ajax/audit-ticket-list-post.php",
      type: "POST",
      data: function (d) {
        d.filter_status = $("#filter_status").val() || "-1";
      },
    },
    columns: [
      { data: "TicketID" },
      { data: "CompanyName" },
      { data: "BranchSite" },
      { data: "AuditName" },
      { data: "SubAuditName" },
      { data: "Status" },
      { data: "LastStatus" },
      { data: "Progress" },
      { data: "CreatedDate" },
      { data: "Action", orderable: false },
    ],
    order: [[8, "desc"]],
  });
}

function reloadAuditTicketTable() {
  if (auditTicketTable) {
    auditTicketTable.ajax.reload();
  }
}

function atToggleFilter() {
  $("#at_filter_offcanvas").toggleClass("open");
}

function auditSelectCorporate() {
  var corporateId = $("#corporate_name").val();
  if (!corporateId) {
    $("#branch_div").hide();
    return;
  }
  $.post("action/get_branches.php", { CorporateID: corporateId }, function (data) {
    $("#branch_div").show();
    $("#branch_name").html(data);
    $("#branch_name").trigger("change");
  });
}

function auditSelectMasterAudit() {
  var masterAuditId = $("#master_audit_id").val();
  $("#sub_audit_id").empty();
  if (!masterAuditId) {
    return;
  }
  $.post("action/get_sub_audits.php", { master_audit_id: masterAuditId }, function (res) {
    if (!res.error && res.html) {
      var $temp = $("<select>" + res.html + "</select>");
      $temp.find("option:first").remove();
      $("#sub_audit_id").html($temp.html());
      $("#sub_audit_id").trigger("change");
    }
  }, "json");
}

function auditSyncSelectedAuditsInput() {
  $("#audits_json").val(JSON.stringify(auditTicketSelectedAudits));
}

function auditRenderSelectedAudits() {
  var $body = $("#selected_audits_body");
  $body.empty();

  if (!auditTicketSelectedAudits.length) {
    $body.append(
      '<tr id="selected_audits_empty"><td colspan="3" class="text-muted text-center">No audits added yet.</td></tr>'
    );
    auditSyncSelectedAuditsInput();
    return;
  }

  auditTicketSelectedAudits.forEach(function (audit, index) {
    $body.append(
      '<tr data-index="' + index + '">' +
        "<td>" + audit.MasterAuditName + "</td>" +
        "<td>" + audit.SubAuditName + "</td>" +
        '<td><button type="button" class="btn btn-xs btn-danger" onclick="auditRemoveSelectedAudit(' + index + ')"><i class="fal fa-times"></i></button></td>' +
      "</tr>"
    );
  });

  auditSyncSelectedAuditsInput();
}

function auditAddSelectedAudits() {
  var masterAuditId = $("#master_audit_id").val();
  var masterAuditName = $("#master_audit_id option:selected").text();
  var subAuditIds = $("#sub_audit_id").val() || [];

  if (!masterAuditId) {
    TechXAlert("Please Select Master Audit");
    return;
  }
  if (!subAuditIds.length) {
    TechXAlert("Please Select at least one Sub Audit");
    return;
  }

  subAuditIds.forEach(function (subAuditId) {
    var subAuditName = $("#sub_audit_id option[value='" + subAuditId + "']").text();
    var exists = auditTicketSelectedAudits.some(function (item) {
      return String(item.SubAuditID) === String(subAuditId);
    });
    if (exists) {
      return;
    }
    auditTicketSelectedAudits.push({
      MasterAuditID: parseInt(masterAuditId, 10),
      SubAuditID: parseInt(subAuditId, 10),
      MasterAuditName: masterAuditName,
      SubAuditName: subAuditName,
    });
  });

  $("#sub_audit_id").val(null).trigger("change");
  auditRenderSelectedAudits();
}

function auditRemoveSelectedAudit(index) {
  auditTicketSelectedAudits.splice(index, 1);
  auditRenderSelectedAudits();
}

function raiseAuditTicket() {
  var corporate = $("#corporate_name").val();
  var branch = $("#branch_name").val();

  if (!corporate) { TechXAlert("Please Select Corporate"); return; }
  if (!branch) { TechXAlert("Please Select Branch"); return; }
  if (!auditTicketSelectedAudits.length) { TechXAlert("Please add at least one audit to the ticket"); return; }

  auditSyncSelectedAuditsInput();
  $("#raise_audit_ticket_btn").prop("disabled", true).text("Submitting...");

  $.post("action/raise_audit_ticket.php", $("#raise_audit_ticket_form").serialize(), function (res) {
    $("#raise_audit_ticket_btn").prop("disabled", false).text("Raise Audit Ticket");
    if (res.error) {
      TechXAlert(res.message || "Unable to raise ticket");
      return;
    }
    TechXAlert(res.message + " Ticket: " + res.TicketID);
    window.location.href = "view-audit-ticket-details?id=" + res.ID;
  }, "json").fail(function () {
    $("#raise_audit_ticket_btn").prop("disabled", false).text("Raise Audit Ticket");
    TechXAlert("Request failed. Please try again.");
  });
}

function generateAuditReport(ticketId) {
  auditTicketGeneratePdfWithProgress(
    ticketId,
    "action/generate_audit_branch_report_pdf.php",
    "Generating Branch Report PDF",
    "Building cover, checklist sections and charts…"
  );
}

function generateAuditReportPdf2(ticketId) {
  auditTicketGeneratePdfWithProgress(
    ticketId,
    "action/generate_audit_esa_report_pdf2.php",
    "Generating ESA Report PDF2",
    "Building ESA segments, tables and dashboard…"
  );
}

function auditTicketHidePdfProgress() {
  $("#at-pdf-progress-overlay").remove();
  if (window._atPdfProgressTimer) {
    clearInterval(window._atPdfProgressTimer);
    window._atPdfProgressTimer = null;
  }
}

function auditTicketShowPdfProgress(title, subtitle) {
  auditTicketHidePdfProgress();
  var html = ''
    + '<div id="at-pdf-progress-overlay" style="position:fixed;inset:0;z-index:99999;background:rgba(15,23,42,.55);display:flex;align-items:center;justify-content:center;">'
    + '  <div style="width:min(420px,92vw);background:#fff;border-radius:12px;box-shadow:0 20px 50px rgba(0,0,0,.25);padding:22px 22px 18px;font-family:Poppins,Segoe UI,sans-serif;">'
    + '    <div style="display:flex;align-items:center;gap:12px;margin-bottom:12px;">'
    + '      <div style="width:38px;height:38px;border-radius:50%;border:3px solid #dbeafe;border-top-color:#027dc1;animation:atPdfSpin 0.9s linear infinite;"></div>'
    + '      <div>'
    + '        <div style="font-size:15px;font-weight:700;color:#0f172a;">' + title + '</div>'
    + '        <div id="at-pdf-progress-sub" style="font-size:12px;color:#64748b;margin-top:2px;">' + subtitle + '</div>'
    + '      </div>'
    + '    </div>'
    + '    <div style="height:10px;background:#e2e8f0;border-radius:999px;overflow:hidden;">'
    + '      <div id="at-pdf-progress-bar" style="height:100%;width:8%;background:linear-gradient(90deg,#027dc1,#0ea5e9);transition:width .35s ease;"></div>'
    + '    </div>'
    + '    <div style="display:flex;justify-content:space-between;margin-top:8px;font-size:11px;color:#64748b;">'
    + '      <span id="at-pdf-progress-label">Preparing…</span>'
    + '      <span id="at-pdf-progress-pct">8%</span>'
    + '    </div>'
    + '  </div>'
    + '</div>'
    + '<style>@keyframes atPdfSpin{to{transform:rotate(360deg)}}</style>';
  $("body").append(html);

  var pct = 8;
  var steps = [
    { at: 18, label: "Loading ticket & checklist…" },
    { at: 35, label: "Rendering report sections…" },
    { at: 55, label: "Applying charts / tables…" },
    { at: 72, label: "Adding border & watermark…" },
    { at: 88, label: "Finalizing PDF file…" }
  ];
  var stepIdx = 0;
  window._atPdfProgressTimer = setInterval(function () {
    if (pct >= 92) {
      return;
    }
    pct += Math.max(1, Math.round((92 - pct) * 0.08));
    if (pct > 92) {
      pct = 92;
    }
    while (stepIdx < steps.length && pct >= steps[stepIdx].at) {
      $("#at-pdf-progress-label").text(steps[stepIdx].label);
      stepIdx++;
    }
    $("#at-pdf-progress-bar").css("width", pct + "%");
    $("#at-pdf-progress-pct").text(pct + "%");
  }, 450);
}

function auditTicketSetPdfProgressDone() {
  $("#at-pdf-progress-bar").css("width", "100%");
  $("#at-pdf-progress-pct").text("100%");
  $("#at-pdf-progress-label").text("Report ready");
  $("#at-pdf-progress-sub").text("Opening PDF…");
}

function auditTicketGeneratePdfWithProgress(ticketId, actionUrl, title, subtitle) {
  auditTicketShowPdfProgress(title, subtitle);
  $.ajax({
    url: actionUrl,
    method: "POST",
    dataType: "json",
    timeout: 600000,
    data: { AuditTicketID: ticketId, Action: "Download" }
  }).done(function (res) {
    auditTicketSetPdfProgressDone();
    setTimeout(function () {
      auditTicketHidePdfProgress();
      if (!res || res.error) {
        TechXAlert((res && res.message) ? res.message : "Unable to generate report");
        return;
      }
      if (res.pdfname) {
        window.open("reports/" + res.pdfname, "_blank");
      }
    }, 350);
  }).fail(function () {
    auditTicketHidePdfProgress();
    TechXAlert("PDF generation failed. Please try again.");
  });
}

function assignAuditTechnician(ticketId, isReassign) {
  var techId = $("#at_technician_id").val();
  if (!techId) {
    TechXAlert("Please select a technician");
    return;
  }
  $.post("action/assign_audit_technician.php", {
    AuditTicketID: ticketId,
    TechnicianID: techId,
    Remarks: $("#at_assign_remarks").val(),
    reassign: isReassign ? 1 : 0
  }, function (res) {
    if (res.error) {
      TechXAlert(res.message || "Unable to assign technician");
      return;
    }
    TechXAlert(res.message);
    window.location.reload();
  }, "json").fail(function () {
    TechXAlert("Assignment request failed.");
  });
}

function updateAuditTicketStatus(ticketId) {
  var status = $("#audit_ticket_status").val();
  if (!status) {
    TechXAlert("Please select a status");
    return;
  }
  $.post("action/update_audit_ticket_status.php", {
    AuditTicketID: ticketId,
    Status: status,
    Remarks: "Status updated from ticket detail page"
  }, function (res) {
    if (res.error) {
      TechXAlert(res.message || "Unable to update status");
      return;
    }
    TechXAlert(res.message || "Status updated");
    window.location.reload();
  }, "json").fail(function () {
    TechXAlert("Status update failed.");
  });
}
