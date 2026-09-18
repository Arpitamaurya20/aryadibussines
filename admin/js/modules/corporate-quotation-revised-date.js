function initQuotationRevisedDatePickers() {
  var datepickerOptions = {
    format: "yyyy-mm-dd",
    todayBtn: "linked",
    clearBtn: true,
    todayHighlight: true,
    autoclose: true,
    startDate: "+0d",
    beforeShowDay: function (date) {
      return [date >= new Date()];
    }
  };

  if ($("#quotation-revised-date").length && !$("#quotation-revised-date").data("datepicker")) {
    $("#quotation-revised-date").datepicker(datepickerOptions);
  }

  if ($("#quotation-revised-expiry-date").length && !$("#quotation-revised-expiry-date").data("datepicker")) {
    $("#quotation-revised-expiry-date").datepicker(datepickerOptions);
  }
}

function openQuotationRevisedDateModal() {
  initQuotationRevisedDatePickers();
  $("#quotation_revised_date_modal").modal("show");
}

function saveQuotationRevisedDates() {
  var quotationId = $("#TicketQuotationID").val();
  var revisedDate = $.trim($("#quotation-revised-date").val());
  var expiryDate = $.trim($("#quotation-revised-expiry-date").val());

  if (!quotationId || quotationId === "-1") {
    TechXAlert("Quotation not found.");
    return false;
  }

  if (!revisedDate) {
    TechXAlert("Please select revised date.");
    return false;
  }

  if (!expiryDate) {
    TechXAlert("Please select expiry date.");
    return false;
  }

  if (expiryDate < revisedDate) {
    TechXAlert("Expiry date cannot be before revised date.");
    return false;
  }

  var $saveBtn = $("#save_quotation_revised_date_button");
  $saveBtn.text("Saving...");

  $.ajax({
    url: "action/update_quotation_revised_dates.php",
    type: "POST",
    dataType: "json",
    data: {
      QuotationID: quotationId,
      QuotationDate: revisedDate,
      QuotationExpiryDate: expiryDate
    },
    success: function (responseData) {
      if (responseData.error === false || responseData.error === "false") {
        $("#quotation_revised_date_modal").modal("hide");
        TechXAlert(responseData.message || "Quotation dates updated successfully.");
        setTimeout(function () {
          location.reload();
        }, 2000);
      } else {
        TechXAlert(responseData.message || "Failed to update quotation dates.");
      }
    },
    error: function () {
      TechXAlert("Failed to update quotation dates. Please try again.");
    },
    complete: function () {
      $saveBtn.text("Save");
    }
  });
}
