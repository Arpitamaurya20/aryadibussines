function openQuotationExpectedBudgetModal() {
  $("#quotation_expected_budget_modal").modal("show");
}

function saveQuotationExpectedBudget() {
  var quotationId = $("#TicketQuotationID").val();
  var expectedBudget = $.trim($("#quotation-expected-budget-input").val());

  if (!quotationId || quotationId === "-1") {
    TechXAlert("Quotation not found.");
    return false;
  }

  if (!expectedBudget) {
    TechXAlert("Please enter expected budget.");
    return false;
  }

  if (!/^\d+(\.\d{1,2})?$/.test(expectedBudget)) {
    TechXAlert("Please enter a valid budget amount.");
    return false;
  }

  var $saveBtn = $("#save_quotation_expected_budget_button");
  $saveBtn.text("Saving...");

  $.ajax({
    url: "action/update_quotation_expected_budget.php",
    type: "POST",
    dataType: "json",
    data: {
      QuotationID: quotationId,
      expectedbudget: expectedBudget
    },
    success: function (responseData) {
      if (responseData.error === false || responseData.error === "false") {
        $("#quotation_expected_budget_modal").modal("hide");
        TechXAlert(responseData.message || "Expected budget updated successfully.");
        setTimeout(function () {
          location.reload();
        }, 2000);
      } else {
        TechXAlert(responseData.message || "Failed to update expected budget.");
      }
    },
    error: function () {
      TechXAlert("Failed to update expected budget. Please try again.");
    },
    complete: function () {
      $saveBtn.text("Save");
    }
  });
}
