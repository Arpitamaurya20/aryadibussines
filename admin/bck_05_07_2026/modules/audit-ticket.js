var auditTicketTable = null;

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
    $("#sub_audit_id").select2();
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
  $("#sub_audit_id").html('<option value="">Please Select Sub Audit</option>');
  if (!masterAuditId) {
    return;
  }
  $.post("action/get_sub_audits.php", { master_audit_id: masterAuditId }, function (res) {
    if (!res.error && res.html) {
      $("#sub_audit_id").html(res.html);
      $("#sub_audit_id").trigger("change");
    }
  }, "json");
}

function raiseAuditTicket() {
  var corporate = $("#corporate_name").val();
  var branch = $("#branch_name").val();
  var masterAudit = $("#master_audit_id").val();
  var subAudit = $("#sub_audit_id").val();

  if (!corporate) { TechXAlert("Please Select Corporate"); return; }
  if (!branch) { TechXAlert("Please Select Branch"); return; }
  if (!masterAudit) { TechXAlert("Please Select Master Audit"); return; }
  if (!subAudit) { TechXAlert("Please Select Sub Audit"); return; }

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
  $.post("action/generate_audit_branch_report_pdf.php", { AuditTicketID: ticketId, Action: "Download" }, function (res) {
    if (res.error) {
      TechXAlert(res.message || "Unable to generate report");
      return;
    }
    if (res.pdfname) {
      window.open("reports/" + res.pdfname, "_blank");
    }
  }, "json").fail(function () {
    TechXAlert("PDF generation failed.");
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
