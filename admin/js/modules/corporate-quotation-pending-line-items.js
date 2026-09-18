function parsePendingAjaxResponse(data) {
  if (data && typeof data === "object") {
    return data;
  }
  if (typeof data === "string" && data !== "") {
    try {
      return JSON.parse(data);
    } catch (e) {
      return { error: true, message: "Invalid server response." };
    }
  }
  return { error: true, message: "Invalid server response." };
}

function isPendingAjaxSuccess(responseData) {
  return responseData.error === false || responseData.error === "false" || responseData.error === 0;
}

function refreshPendingQuotationItemsView(quotationId) {
  if (typeof UpdateQuotationDisplay === "function" && quotationId) {
    UpdateQuotationDisplay(quotationId);
  }
}

function applyPendingQuotationStatusUpdate(quotationId, status) {
  if (status !== "Quote Pending Company Admin Approval") {
    return;
  }
  if (typeof getQuotationStatusBadgeHtml === "function") {
    $("#status_text").html(getQuotationStatusBadgeHtml(status));
  }
  $("#quotation_post_submit_actions").hide();
  $(".quotation-pending-line-item-actions").hide();
  $(".quotation-client-approval-actions, .quotation-state-approval-actions, .quotation-finance-approval-actions, .quotation-reject-actions").hide();
  $(".quotation-document-actions").show();
  $("#quotation_company_admin_actions").show();
  refreshPendingQuotationItemsView(quotationId);
}

function resetPendingNonArcForm() {
  var $table = $("#dynamicTablePending tbody");
  if (!$table.length) {
    return;
  }

  var $firstRow = $table.find("tr:first").clone();
  $firstRow.find("input").val("");
  $firstRow.find("select").prop("selectedIndex", 0);
  $firstRow.find(".pending-subcategory-dropdown").html('<option value="">Please Select</option>');
  $table.html($firstRow);
}

function openPendingApprovalRateCardModal(CorporateID) {
  $("#pending_approval_rate_card_modal").modal("show");
  if ($("#pending_filter_category").length && !$("#pending_filter_category").hasClass("select2-hidden-accessible")) {
    $("#pending_filter_category").select2({ dropdownParent: $("#pending_approval_rate_card_modal") });
  }
  loadPendingApprovalLineItems(CorporateID);
}

function loadPendingApprovalLineItems(CorporateID) {
  var param = "CompanyID=" + CorporateID;

  var filterTypeEl = document.getElementById("pending_filter_type");
  if (filterTypeEl !== null) {
    param = param + "&type=" + filterTypeEl.value;
  }

  var filterCategoryEl = document.getElementById("pending_filter_category");
  if (filterCategoryEl !== null) {
    param = param + "&category=" + filterCategoryEl.value;
  }

  var filterSubcategoryEl = document.getElementById("pending_filter_subcategory");
  if (filterSubcategoryEl !== null) {
    param = param + "&subcategory=" + filterSubcategoryEl.value;
  }

  if ($.fn.DataTable.isDataTable("#view-q-rate-card-pending")) {
    $("#view-q-rate-card-pending").DataTable().destroy();
  }

  $("#view-q-rate-card-pending").dataTable({
    responsive: true,
    processing: true,
    serverSide: true,
    ordering: false,
    serverMethod: "post",
    ajax: {
      url: "ajax/q-rate-card-list-pending-post.php?" + param
    },
    columnDefs: [{
      targets: [0],
      className: "text-center"
    }],
    order: [[1, "asc"]],
    columns: [
      {
        data: "id",
        render: function (data, type, row, meta) {
          return meta.row + meta.settings._iDisplayStart + 1;
        }
      },
      { data: "Type" },
      { data: "Category_SubCategory" },
      { data: "LineItemName" },
      { data: "Make" },
      { data: "HSN" },
      { data: "ARCCode" },
      { data: "UoM" },
      { data: "Price" },
      { data: "Tax" },
      { data: "Add" }
    ]
  });
}

function GetPendingQuotationFilterSubCategories() {
  var selectElement = document.getElementById("pending_filter_category");
  if (!selectElement) {
    return;
  }

  var selectedOption = selectElement.options[selectElement.selectedIndex];
  var categoryId = selectedOption.getAttribute("data-pending-filter-category-id");

  $.post("../company/action/get_subcategories_filter.php", {
    CategoryID: categoryId
  }, function (data) {
    document.getElementById("pending_subcategory_div").innerHTML = data.replace(/filter_subcategory/g, "pending_filter_subcategory");
    $("#pending_filter_subcategory").select2({ dropdownParent: $("#pending_approval_rate_card_modal") });
  });
}

function setPendingAddLineItemButtonState(isBusy) {
  var $btn = $("#pending_add_line_item_btn");
  if (!$btn.length) {
    return;
  }

  if (isBusy) {
    $btn.data("adding", true)
      .attr("aria-disabled", "true")
      .css({ "pointer-events": "none", opacity: "0.7" })
      .text("Adding...");
  } else {
    $btn.data("adding", false)
      .attr("aria-disabled", "false")
      .css({ "pointer-events": "", opacity: "" })
      .text("Add");
  }
}

function closePendingQtyModal() {
  $("#pending_qty_modal").modal("hide");

  setTimeout(function () {
    if ($(".modal.show").length) {
      $("body").addClass("modal-open");
    }
  }, 300);
}

function AddToQuotationPending(RateCardID) {
  $("#pending_LineItemID").val(RateCardID);
  $("#pending_quantity").val(1);
  setPendingAddLineItemButtonState(false);
  $("#pending_qty_modal").modal("show");
}

function checkPendingQuantity(input) {
  if (input.value < 1) {
    input.value = 1;
  }
}

function AddLineItemtoQuotationPending() {
  var $btn = $("#pending_add_line_item_btn");
  if ($btn.data("adding")) {
    return false;
  }

  var TicketQuotationID = $("#TicketQuotationID").val();
  var LineItemID = $("#pending_LineItemID").val();
  var quantity = $("#pending_quantity").val();
  var TicketID = $("#Q_TicketID").val();

  if (!LineItemID) {
    TechXAlert("Please select a line item.");
    return false;
  }

  if (!quantity || parseInt(quantity, 10) < 1) {
    TechXAlert("Please enter a valid quantity.");
    return false;
  }

  setPendingAddLineItemButtonState(true);

  $.ajax({
    url: "action/add_line_item_to_quotation_pending.php",
    type: "POST",
    dataType: "json",
    data: {
      TicketQuotationID: TicketQuotationID,
      LineItemID: LineItemID,
      quantity: quantity,
      TicketID: TicketID
    },
    success: function (responseData) {
      responseData = parsePendingAjaxResponse(responseData);
      if (isPendingAjaxSuccess(responseData)) {
        closePendingQtyModal();
        refreshPendingQuotationItemsView(TicketQuotationID);
        applyPendingQuotationStatusUpdate(TicketQuotationID, responseData.QuotationStatus);
      }
      TechXAlert(responseData.message || "Unable to add line item.");
    },
    error: function (xhr) {
      var responseData = parsePendingAjaxResponse(xhr.responseText);
      TechXAlert(responseData.message || "Failed to add line item. Please try again.");
    },
    complete: function () {
      setPendingAddLineItemButtonState(false);
    }
  });

  return false;
}

function openPendingApprovalNonArcModal(CorporateID) {
  $("#Quotation_Corporate_ID_Pending").val(CorporateID);
  $("#rateCardModalPending").modal("show");
}

$(document).ready(function () {
  $("#pending_qty_modal").on("hidden.bs.modal", function () {
    setPendingAddLineItemButtonState(false);
  });

  $("#addRowPending").on("click", function () {
    var $firstRow = $("#dynamicTablePending tbody tr:first");
    var $newRow = $firstRow.clone();
    $newRow.find("input").val("");
    $newRow.find("select").prop("selectedIndex", 0);
    $newRow.find(".pending-subcategory-dropdown").html('<option value="">Please Select</option>');
    $("#dynamicTablePending tbody").append($newRow);
  });

  $(document).on("click", ".pending-remove-row", function () {
    if ($("#dynamicTablePending tbody tr").length > 1) {
      $(this).closest("tr").remove();
    }
  });

  $(document).on("change", ".pending-category-dropdown", function () {
    var categoryId = $(this).find("option:selected").data("id");
    var subcategoryDropdown = $(this).closest("tr").find(".pending-subcategory-dropdown");
    subcategoryDropdown.empty();
    subcategoryDropdown.append('<option value="">Please Select</option>');

    $.post("../company/action/get_subcategories_rate_card.php", {
      CategoryID: categoryId
    }, function (data) {
      subcategoryDropdown.append(data);
      subcategoryDropdown.append('<option value="Others">Others</option>');
    });
  });

  $(document).on("input", ".pending-numeric-input", function () {
    if (!/^\d*\.?\d*$/.test(this.value)) {
      this.value = this.value.replace(/[^0-9.]/g, "").replace(/(\..*)\./g, "$1");
    }
  });

  $("#saveRowsPending").on("click", function () {
    var data = [];
    var isValid = true;
    var CorporateID = $("#Quotation_Corporate_ID_Pending").val();
    var TicketID = $("#Q_TicketID").val();
    var TicketQuotationID = $("#TicketQuotationID").val();

    $("#dynamicTablePending tbody tr").each(function () {
      var row = {
        type: $(this).find('select[name="pending_type[]"]').val(),
        category: $(this).find('select[name="pending_category[]"]').val(),
        category_id: $(this).find('select[name="pending_category[]"] option:selected').data("id"),
        subcategory: $(this).find('select[name="pending_subcategory[]"]').val(),
        lineItemName: $(this).find('input[name="pending_lineItemName[]"]').val(),
        make: $.trim($(this).find('input[name="pending_make[]"]').val()),
        hsn: $(this).find('input[name="pending_hsn[]"]').val(),
        uom: $(this).find('select[name="pending_uom[]"]').val(),
        price: $(this).find('input[name="pending_price[]"]').val(),
        tax: $(this).find('input[name="pending_tax[]"]').val(),
        qty: $(this).find('input[name="pending_qty[]"]').val(),
        CorporateID: CorporateID,
        QuotationID: TicketQuotationID,
        TicketID: TicketID
      };

      if (!row.make) {
        row.make = "N.A.";
      }
      if (!row.hsn) {
        row.hsn = "0";
      }
      if (!row.uom) {
        row.uom = "EA";
      }
      if (!row.tax) {
        row.tax = "0";
      }

      if (row.category === "" || row.lineItemName === "" || row.price === "" || row.qty === "") {
        isValid = false;
        return false;
      }
      data.push(row);
    });

    if (!isValid) {
      TechXAlert("Please fill out all required fields (Category, Line Item Name, Price, Qty) in each row.");
      return;
    }

    if (!TicketQuotationID || TicketQuotationID === "-1") {
      TechXAlert("Quotation not found.");
      return;
    }

    var $saveBtn = $("#saveRowsPending");
    $saveBtn.prop("disabled", true).text("Saving...");

    $.ajax({
      url: "action/insert_non-arc_items_pending.php",
      type: "POST",
      dataType: "json",
      data: JSON.stringify(data),
      contentType: "application/json; charset=utf-8",
      success: function (responseData) {
        responseData = parsePendingAjaxResponse(responseData);
        if (isPendingAjaxSuccess(responseData)) {
          $("#rateCardModalPending").modal("hide");
          resetPendingNonArcForm();
          refreshPendingQuotationItemsView(TicketQuotationID);
          applyPendingQuotationStatusUpdate(TicketQuotationID, responseData.QuotationStatus);
        }
        TechXAlert(responseData.message || "Unable to save non-ARC items.");
      },
      error: function (xhr) {
        var responseData = parsePendingAjaxResponse(xhr.responseText);
        TechXAlert(responseData.message || "Failed to save non-ARC items. Please try again.");
      },
      complete: function () {
        $saveBtn.prop("disabled", false).text("Save");
      }
    });
  });
});
