function saveTicketApprovalSettings() {
  $.post("action/save-ticket-approval-settings.php", {
    IsEnabled: $("#IsEnabled").is(":checked") ? 1 : 0,
    SendOnRaise: $("#SendOnRaise").is(":checked") ? 1 : 0,
    TokenExpiryDays: $("#TokenExpiryDays").val(),
    FallbackApproverName: $("#FallbackApproverName").val(),
    FallbackApproverEmail: $("#FallbackApproverEmail").val(),
  }, function (data) {
    var response = {};
    try {
      response = typeof data === "object" ? data : JSON.parse(data);
    } catch (e) {
      response = { error: true, message: "Unexpected response while saving settings." };
    }
    TechXAlert(response.message || "Unable to save settings.");
    if (!response.error) {
      setTimeout(function () {
        location.href = "view-ticket-approval-config.php?tab=setup";
      }, 1200);
    }
  });
}

function initApproverSelect2() {
  var $company = $("#approver_company");
  var $state = $("#approver_state");

  if (!$company.length || !$state.length || !$.fn.select2) {
    return;
  }

  if ($company.hasClass("select2-hidden-accessible")) {
    $company.select2("destroy");
  }
  if ($state.hasClass("select2-hidden-accessible")) {
    $state.select2("destroy");
  }

  var select2Options = {
    dropdownParent: $("#approverModal"),
    width: "100%",
    allowClear: true,
  };

  $company.select2(
    $.extend({}, select2Options, {
      placeholder: "Search company...",
    })
  );

  $state.select2(
    $.extend({}, select2Options, {
      placeholder: "Search state...",
    })
  );
}

function setApproverSelectValues(companyId, stateName) {
  $("#approver_company").val(companyId).trigger("change");
  $("#approver_state").val(stateName || "").trigger("change");
}

function openApproverModal() {
  $("#approverModalTitle").text("Add State Approver");
  $("#approver_id").val("0");
  $("#approver_name").val("");
  $("#approver_email").val("");
  $("#approverModal").modal("show");
  initApproverSelect2();
  setApproverSelectValues("-1", "");
}

function quickAddApproverState(stateName) {
  openApproverModal();
  setApproverSelectValues("-1", stateName);
}

function editApprover(row) {
  $("#approverModalTitle").text("Edit State Approver");
  $("#approver_id").val(row.ID);
  $("#approver_name").val(row.ApproverName);
  $("#approver_email").val(row.ApproverEmail);
  $("#approverModal").modal("show");
  initApproverSelect2();
  setApproverSelectValues(row.CompanyID, row.StateName);
}

function saveApprover() {
  var state = $("#approver_state").val();
  var email = $("#approver_email").val();

  if (!state) {
    TechXAlert("Please select a state.");
    return;
  }
  if (!email) {
    TechXAlert("Please enter approver email.");
    return;
  }

  $.post("action/save-ticket-state-approver.php", {
    ID: $("#approver_id").val(),
    CompanyID: $("#approver_company").val(),
    StateName: state,
    ApproverName: $("#approver_name").val(),
    ApproverEmail: email,
  }, function (data) {
    var response = JSON.parse(data);
    TechXAlert(response.message);
    if (!response.error) {
      setTimeout(function () {
        location.href = "view-ticket-approval-config.php?tab=approvers";
      }, 1200);
    }
  });
}

function deleteApprover(id) {
  if (!confirm("Remove this state approver mapping?")) {
    return;
  }

  $.post("action/delete-ticket-state-approver.php", { ID: id }, function (data) {
    var response = JSON.parse(data);
    TechXAlert(response.message);
    if (!response.error) {
      setTimeout(function () {
        location.reload();
      }, 1200);
    }
  });
}

$(document).ready(function () {
  $("#nav_ticket_approval_config").addClass("active");

  $("#approverModal").on("shown.bs.modal", function () {
    initApproverSelect2();
  });

  if ($.fn.DataTable) {
    if ($("#approver_table").length) {
      $("#approver_table").DataTable({ pageLength: 25, order: [[2, "asc"]] });
    }
    if ($("#company_approval_table").length) {
      $("#company_approval_table").DataTable({ pageLength: 25, order: [[1, "asc"]] });
    }
    if ($("#coverage_table").length) {
      $("#coverage_table").DataTable({ pageLength: 25, order: [[1, "asc"]] });
    }
  }
});
