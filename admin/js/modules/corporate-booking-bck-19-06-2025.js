$(document).ready(function () {


  $("#booking_status").select2();
  $("#assignemployee_dropdown").select2();
  $("#ticket_status").select2();


  $('.js-thead-colors a').on('click', function () {
    var theadColor = $(this).attr("data-bg");
    console.log(theadColor);
    $('#dt-basic-example thead').removeClassPrefix('bg-').addClass(theadColor);
  });

  $('.js-tbody-colors a').on('click', function () {
    var theadColor = $(this).attr("data-bg");
    console.log(theadColor);
    $('#dt-basic-example').removeClassPrefix('bg-').addClass(theadColor);
  });

});

function openBookingAssignmodal() {
  $("#editassign_booking").modal();
  $('#due_date').datepicker({
    format: "yyyy-mm-dd",
    todayBtn: "linked",
    clearBtn: true,
    todayHighlight: true,
    autoclose: true,
    startDate: '+0d',
    beforeShowDay: function (date) {
      return [date >= new Date()] // Disable past dates
    }
  });
}
function checkforclosedatediv(status) {
  if (status == "Closed") {
    $('#close_date').datepicker({
      format: "yyyy-mm-dd",
      todayBtn: "linked",
      clearBtn: true,
      todayHighlight: true,
      autoclose: true,
    });
    document.getElementById("close_date_div").style.display = "";
  }
  else {
    document.getElementById("close_date_div").style.display = "none";
  }
}
// function ChangeTicketStatus_Assignment() {
//   var assignemployee = document.getElementById('assignemployee_dropdown').value;
//   if (assignemployee == "-1") {
//     TechXAlert("Please Assign Employee");
//     return false;
//   }

//   var remarks = document.getElementById("ticket_remarks").value;
//   var status = document.getElementById("ticket_status").value;

//   if (status == "Cancel" && remarks == "") {
//     TechXAlert("To cancel ticket, need to provide Remarks");
//     return false;
//   }

//   var due_date = document.getElementById("due_date").value;
//   if ((status == "Work In Progress" || status == "Assigned" || status == "Submitted for Closure") && due_date == "") {
//     TechXAlert("Kindly provide Due Date");
//     return false;
//   }

//   $("#assignment_change_btn").html("Please Wait....");
//   // Then, get the selected value
//   $.ajax({
//     url: "action/change_ticket_status.php",
//     type: "POST",
//     data: $("#ticket_assignment_form").serialize(),
//     success: function (data) {
//       var response = JSON.parse(data);
//       TechXAlert(response.message);
//       if (response.error == false) {
//         setInterval(function () {
//           location.reload();
//         }, 2000);
//       }
//       else {
//         $("#assignment_change_btn").html("Save & Change");
//       }
//     },
//   });
// }

function ChangeTicketStatus_Assignment() {
  var assignemployee = document.getElementById('assignemployee_dropdown').value;
  if (assignemployee == "-1") {
    TechXAlert("Please Assign Employee");
    return false;
  }

  var remarks = document.getElementById("ticket_remarks").value;
  var status = document.getElementById("ticket_status").value;

   var isReassign = (status === "__REASSIGN__");

  if (!isReassign && status == "Cancel" && remarks == "") {
    TechXAlert("To cancel ticket, need to provide Remarks");
    return false;
  }

  var due_date = document.getElementById("due_date").value;
  if (!isReassign && (status == "Work In Progress" || status == "Assigned" || status == "Submitted for Closure") && due_date == "") {
    TechXAlert("Kindly provide Due Date");
    return false;
  }
   
   var formData = $("#ticket_assignment_form").serialize();
   if (isReassign) {
    formData += "&IsReassign=1&TicketStatus="; // empty status
  }

  $("#assignment_change_btn").html("Please Wait....");
  // Then, get the selected value
  $.ajax({
    url: "action/change_ticket_status.php",
    type: "POST",
    data: formData,
    success: function (data) {
      var response = JSON.parse(data);
      TechXAlert(response.message);
      if (response.error == false) {
        setTimeout(function () {
          location.reload();
        }, 2000);
      }
      else {
        $("#assignment_change_btn").html("Save & Change");
      }
    },
  });
}

function OpenClientIDModal() {
  $("#edit_clientid").modal();
}

function EditTicketType(TicketType) {
  $("#service_type").val(TicketType);
  $("#edit_ticket_type").modal();
}
function EditCorporateTicketStatus(TicketType) {
  $("#service_type").val(TicketType);
  $("#edit_corporate_ticket_status").modal();
}
function Corporate_ChangeTicketStatus()
{
   $.ajax({
    url: "action/change_ticket_status.php",
    type: "POST",
    data: $("#corporate_ticket_status_modal_form").serialize(),
    success: function (data) {
      var response = JSON.parse(data);
      TechXAlert(response.message);
      if (response.error == false) {
        setInterval(function () {
          location.reload();
        }, 2000);
      }
      else {
        $("#assignment_change_btn").html("Save & Change");
      }
    },
  });
}
function EditTicketBranch(CorporateID, BranchID) {
  $("#edit_branch_modal_branch_id").val(BranchID);
  $("#edit_ticket_branch_modal").modal();
  $("#edit_branch_modal_branch_id").select2();
}

function EditTicketService(TicketService) {
  //$("#ticket_service").val(TicketType);

  $("#edit_ticket_service_modal").modal();
  $("#service_name").select2();
  var selectElement = document.getElementById("service_name");

  // Loop through each option
  for (var i = 0; i < selectElement.options.length; i++) {
    // Check if the text of the option matches the input text
    if (selectElement.options[i].text === TicketService) {
      // Select the option
      selectElement.selectedIndex = i;
      // Optionally, trigger change event if necessary
      selectElement.dispatchEvent(new Event('change'));
      // Exit the loop since we found the match
      break;
    }
  }
}
function SelectService() {
  $.post("action/get_subservices.php", {
    ServiveID: $("#service_name").val()
  },
    function (data, status) {
      document.getElementById("sub_services_div").style.display = "block";
      document.getElementById("sub_service_name").innerHTML = data;
      $("#sub_service_name").select2();
    });
}
function GetSubCategories() {
  var selectElement = document.getElementById('service_name');
  var selectedOption = selectElement.options[selectElement.selectedIndex];
  var serviceId = selectedOption.getAttribute('data-id');
  $.post("action/get_subcategories.php", {
    CategoryID: serviceId
  },
    function (data, status) {
      document.getElementById("sub_services_div").style.display = "block";
      document.getElementById("sub_service_name").innerHTML = data;
      $("#sub_service_name").select2();
    });
}

function ChangeTicketClientID() {

  $("#clientid_change_btn").html("Please Wait....");
  // Then, get the selected value
  $.ajax({
    url: "action/client_id_action.php",
    type: "POST",
    data: $("#ticket_clientid_form").serialize(),
    success: function (data) {
      var response = JSON.parse(data);
      TechXAlert(response.message);
      if (response.error == false)
        setInterval(function () {
          location.reload();
        }, 2000);
    },
  });
}

function ChangeTicketType() {

  $("#clientid_change_btn").html("Please Wait....");
  // Then, get the selected value
  $.ajax({
    url: "action/change_ticket_type_action.php",
    type: "POST",
    data: $("#ticket_type_modal_form").serialize(),
    success: function (data) {
      var response = JSON.parse(data);
      TechXAlert(response.message);
      if (response.error == false)
        setInterval(function () {
          location.reload();
        }, 2000);
    },
  });
}
function ChangeTicketService() {
  $("#change_service_btn").html("Please Wait....");
  // Then, get the selected value
  $.ajax({
    url: "action/change_ticket_service.php",
    type: "POST",
    data: $("#ticket_service_form").serialize(),
    success: function (data) {
      var response = JSON.parse(data);
      TechXAlert(response.message);
      if (response.error == false)
        setInterval(function () {
          location.reload();
        }, 2000);
    },
  });
}
function ChangeTicketBranchAction() {
  $("#change_branch_btn").html("Please Wait....");
  // Then, get the selected value
  $.ajax({
    url: "action/change_ticket_branch.php",
    type: "POST",
    data: $("#modal_ticket_branch_form").serialize(),
    success: function (data) {
      var response = JSON.parse(data);
      TechXAlert(response.message);
      if (response.error == false)
        setInterval(function () {
          location.reload();
        }, 2000);
    },
  });
}



function OpenAddSparePart() {
  $("#add_sparepart").modal();
  $("#SparePartDecs").select2();
}

function ChangeTicketSparePart() {

  $("#sparepart_change_btn").html("Please Wait....");
  // Then, get the selected value
  $.ajax({
    url: "action/spare_part_action.php",
    type: "POST",
    data: $("#ticket_sparepart_form").serialize(),
    success: function (data) {
      var response = JSON.parse(data);
      TechXAlert(response.message);
      if (response.error == false)
        setInterval(function () {
          location.reload();
        }, 2000);
    },
  });
}


// function disablePastDate() {
//   alert("hello");
//   var today = new Date().toISOString().split('T')[0];
//   document.getElementById("due_date").setAttribute("min", today);
// }

function OpenFinanceModal($callType) {
  $("#service_call_type").val($callType);
  $("#service_call_type").select2()
  $("#finance_modal").modal();
}

function UpdateTicketFinance() {
  var callTypeValue = document.getElementById('service_call_type').value;
  if (callTypeValue == '') {

    TechXAlert('Please Select Call Type.');
    return false;
  }

  var CUSpriceValue = document.getElementById('CustumerPrice').value;

  if (CUSpriceValue.trim() === '') {

    TechXAlert('Please Enter Custumer Price');
    return false;
  }

  var EXPpriceValue = document.getElementById('ExpensePrice').value;

  if (EXPpriceValue.trim() === '') {

    TechXAlert('Please Enter Expense Price');
    return false;
  }

  let myForm = document.getElementById("ticket_finance_form");
  var formData = new FormData(myForm);

  $("#finance_change_btn").html("Please Wait Submitting....");
  // Then, get the selected value
  $.ajax({
    url: "action/finance_action.php",
    type: "POST",
    data: formData,
    success: function (data) {
      var response = JSON.parse(data);
      TechXAlert(response.message);
      if (response.error == false)
        setInterval(function () {
          location.reload();
        }, 2000);
    },
    cache: false,
    contentType: false,
    processData: false,
  });
}

function UploadQuotation($callType) {
  $("#quotation_modal").modal();
}

function UploadQuotationAction() {
  // var callTypeValue = document.getElementById('service_call_type').value;
  // var priceValue = document.getElementById('CustumerPrice').value;

  // if (callTypeValue !== '' && priceValue.trim() === '') {

  //   TechXAlert('Price is mandatory for OCB call type.');
  //   return false; 
  // }
  let myForm = document.getElementById("ticket_quotation_form");
  var formData = new FormData(myForm);

  $("#upload_change_btn").html("Please Wait Uploading....");
  // Then, get the selected value
  $.ajax({
    url: "action/upload_quotation.php",
    type: "POST",
    data: formData,
    success: function (data) {
      var response = JSON.parse(data);
      TechXAlert(response.message);
      if (response.error == false)
        setInterval(function () {
          location.reload();
        }, 2000);
    },
    cache: false,
    contentType: false,
    processData: false,
  });
}

function OpenConversation() {
  $("#ticket_chat").modal();
}

function AddTicketConversation() {

  $("#sparepart_change_btn").html("Please Wait....");
  // Then, get the selected value
  $.ajax({
    url: "action/ticket_conversation.php",
    type: "POST",
    data: $("#ticket_conversation_form").serialize(),
    success: function (data) {
      var response = JSON.parse(data);
      TechXAlert(response.message);
      if (response.error == false)
        setInterval(function () {
          location.reload();
        }, 2000);
    },
  });
}

function ApproveQoutation(TicketID) {
  alertify.confirm(
    "TechXpert ",
    "Do you really want to Approve Quotation?",
    function () {
      $.post(
        "action/approve_qoutation.php",
        {
          ID: TicketID,
        },
        function (data, status) {
          var response = JSON.parse(data);
          TechXAlert(response.message);
          if (response.error == false) {
            setInterval(function () {
              location.reload();
            }, 2000);
          }
        }
      );
    },
    function () {
      alertify.error("Deletion Cancelled");
    }
  );
}

function RejectedQuotation(TicketID) {
  alertify.confirm(
    "TechXpert ",
    "Do you really want to Reject Quotation?",
    function () {
      $.post(
        "action/reject_qoutation.php",
        {
          ID: TicketID,
        },
        function (data, status) {
          var response = JSON.parse(data);
          TechXAlert(response.message);
          if (response.error == false) {
            setInterval(function () {
              location.reload();
            }, 2000);
          }
        }
      );
    },
    function () {
      alertify.error("Deletion Cancelled");
    }
  );
}

function EditTicketFinances(action) {
  document.getElementById("form_action").value = action;
  $("#ticket_finance_modal").modal();
}


function UpdateTicketFincanceCalculation(inputElement) {
  var inputValue = inputElement.value;
  var inputId = inputElement.id;
  var numericRegex = /^[0-9]*$/;
  if (numericRegex.test(inputValue)) {
    // Customer Costing
    var customer_no_of_visits = parseInt(document.getElementById("customer_no_of_visits").value) || 0;
    var customer_visit_charge = parseInt(document.getElementById("customer_visit_charge").value) || 0;
    var customer_material_cost = parseInt(document.getElementById("customer_material_cost").value) || 0;
    var customer_labour_cost = parseInt(document.getElementById("customer_labour_cost").value) || 0;
    var customer_total_cost = (customer_no_of_visits * customer_visit_charge) + customer_material_cost + customer_labour_cost;
    document.getElementById("total_cost_customer").value = customer_total_cost;

    // Self Costing
    var self_no_of_visits = parseInt(document.getElementById("self_no_of_visits").value) || 0;
    var self_visit_charge = parseInt(document.getElementById("self_visit_charge").value) || 0;
    var self_material_cost = parseInt(document.getElementById("self_material_cost").value) || 0;
    var self_labour_cost = parseInt(document.getElementById("self_labour_cost").value) || 0;
    var self_total_cost = (self_no_of_visits * self_visit_charge) + self_material_cost + self_labour_cost;
    document.getElementById("total_cost_self").value = self_total_cost;
  }
  else {
    // If the input is not numeric, remove non-numeric characters
    inputElement.value = inputValue.replace(/[^0-9]/g, '');
  }

}

function SaveTicketFinances() {
  let myForm = document.getElementById("n_ticket_finance_form");
  var formData = new FormData(myForm);

  $("#ticket_finance_change_btn").html("Please Wait Submitting....");
  // Then, get the selected value
  $.ajax({
    url: "action/ticket_finance_action.php",
    type: "POST",
    data: formData,
    success: function (data) {
      var response = JSON.parse(data);
      TechXAlert(response.message);
      if (response.error == false)
        setInterval(function () {
          location.reload();
        }, 2000);
    },
    cache: false,
    contentType: false,
    processData: false,
  });
}

function UpdateTicketStatus(Status) {
  document.getElementById("TicketNextStatus").value = Status;
  if (Status == 5) {
    $("#ticket_finance_modal_approval_button").html("Paid");
    $("#ticket_finance_modal_reject_button").hide();
  }
  $("#finance_status_modal").modal();
}

function ApproveTicketFinanceStatus(action) {
  document.getElementById("TicketApprovalStatus").value = action;
  let myForm = document.getElementById("ticket_finance_status_form");
  var formData = new FormData(myForm);

  // Then, get the selected value
  $.ajax({
    url: "action/update_ticket_finance_status.php",
    type: "POST",
    data: formData,
    success: function (data) {
      var response = JSON.parse(data);
      TechXAlert(response.message);
      if (response.error == false)
        setInterval(function () {
          location.reload();
        }, 2000);
    },
    cache: false,
    contentType: false,
    processData: false,
  });
}

var form = document.getElementById("n_ticket_finance_form");
var inputs = form.getElementsByTagName("input");

for (var i = 0; i < inputs.length; i++) {
  if (inputs[i].type === "text" && !inputs[i].readOnly) {
    inputs[i].addEventListener("change", function () {
      UpdateTicketFincanceCalculation(this);
    });
  }
}

function UploadTicketMedia(action) {
  $("#media_action").val(action);
  $("#upload_media").modal("show");
}
function UploadTicketMediaAction_old_25_may_2026() {
  var media_image = $("#media_image").val();
  if (media_image == "") {
    TechXAlert("Kindly Upload Image");
    return false;
  }
  var allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'pdf'];
  var fileExtension = media_image.split('.').pop().toLowerCase();

  if (allowedExtensions.includes(fileExtension)) {

  }
  else {
    TechXAlert('Invalid file type. Only images (jpg, jpeg, png, gif) are allowed.');
    $('#media_image').val(''); // Clear the file input
    return false;
  }
  let myForm = document.getElementById("ticket_media_upload_form");
  var formData = new FormData(myForm);
  $.ajax({
    url: "action/add-ticket-media.php",
    type: "POST",
    data: formData,
    success: function (data) {
      var response = JSON.parse(data);
      TechXAlert(response.message);
      if (response.error == false) {
        setInterval(function () {
          location.reload();
        }, 2000);
      }
    },
    cache: false,
    contentType: false,
    processData: false,
  });
  return false;
}

function UploadTicketMediaAction() {
  var $saveBtn = $("#clientid_change_btn");
  if ($saveBtn.data("uploading") === true) {
    return false;
  }

  var fileInput = document.getElementById("media_image");
  var media_image = fileInput ? fileInput.value : "";
  if (media_image == "") {
    TechXAlert("Kindly Upload Image");
    return false;
  }

  var allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
  var fileExtension = media_image.split('.').pop().toLowerCase();
  if (!allowedExtensions.includes(fileExtension)) {
    TechXAlert('Invalid file type. Only images (jpg, jpeg, png, gif, webp) are allowed.');
    $('#media_image').val('');
    return false;
  }

  var file = fileInput && fileInput.files ? fileInput.files[0] : null;
  var MAX_BYTES = 5 * 1024 * 1024;
  if (file && file.size > MAX_BYTES) {
    TechXAlert('File is too large. Maximum allowed size is 5 MB.');
    $('#media_image').val('');
    return false;
  }

  var myForm = document.getElementById("ticket_media_upload_form");
  var formData = new FormData(myForm);

  $saveBtn.data("uploading", true).css("pointer-events", "none").text("Uploading...");

  $.ajax({
    url: "action/add-ticket-media.php",
    type: "POST",
    data: formData,
    dataType: "json",
    cache: false,
    contentType: false,
    processData: false,
    success: function (response) {
      if (!response || typeof response !== "object") {
        TechXAlert("Unexpected server response. Please try again.");
        return;
      }
      TechXAlert(response.message || "");
      if (response.error === false) {
        $("#upload_media").modal("hide");
        setTimeout(function () {
          location.reload();
        }, 1500);
      }
    },
    error: function (xhr) {
      var msg = "Upload failed. Please try again.";
      if (xhr && xhr.status === 413) {
        msg = "File is too large to upload.";
      } else if (xhr && xhr.status) {
        msg = "Upload failed (HTTP " + xhr.status + "). Please try again.";
      }
      TechXAlert(msg);
    },
    complete: function () {
      $saveBtn.data("uploading", false).css("pointer-events", "").text("Save & Change");
    }
  });
  return false;
}


function UpdateQuotation(CorporateID) {
  loadLineItems(CorporateID);
  $("#filter_category").select2();
  document.getElementById("add_non_arc_button").style.display = "";
}

function loadLineItems(CorporateID) {
  document.getElementById("rate_card_line_item_panel").style.display = "";
  var table = $('#view-q-rate-card').DataTable();
  table.destroy();
  param = "CompanyID=" + CorporateID;

  var typeObject = document.getElementById("filter_type");
  if (typeObject !== null) {
    var filter_type = document.getElementById("filter_type").value;
    param = param + "&type=" + filter_type;
  }

  var categoryObject = document.getElementById("filter_category");
  if (categoryObject !== null) {
    var filter_category = document.getElementById("filter_category").value;
    param = param + "&category=" + filter_category;
  }

  var subcategoryObject = document.getElementById("filter_subcategory");
  if (subcategoryObject !== null) {
    var filter_subcategory = document.getElementById("filter_subcategory").value;
    param = param + "&subcategory=" + filter_subcategory;
  }

  var i = 1;
  var columns = [
    {
      "data": "id",
      render: function (data, type, row, meta) {
        return meta.row + meta.settings._iDisplayStart + 1;
      }
    },
    { data: 'Type' },
    { data: 'Category_SubCategory' },
    { data: 'LineItemName' },
    { data: 'Make' },
    { data: 'HSN' },
    { data: 'ARCCode' },
    { data: 'UoM' },
    { data: 'Price' },
    { data: 'Tax' },
    { data: 'Add' }
  ];

  $('#view-q-rate-card').dataTable({
    responsive: true,
    'processing': true,
    'serverSide': true,
    'ordering': false,
    'serverMethod': 'post',
    'ajax': {
      'url': 'ajax/q-rate-card-list-post.php?' + param
    },
    'columnDefs': [{
      "targets": [0],
      "className": "text-center"
    }],
    "order": [
      [1, 'asc']
    ],
    'columns': columns


  });
}


function GetQuotationFilterSubCategories(CategoryName) {
  var selectElement = document.getElementById('filter_category');
  var selectedOption = selectElement.options[selectElement.selectedIndex];
  var categoryId = selectedOption.getAttribute('data-filter-category-id');
  $.post("../company/action/get_subcategories_filter.php",
    {
      "CategoryID": categoryId
    },
    function (data, status) {
      document.getElementById("subcategory_div").innerHTML = data;
      $("#filter_subcategory").select2();
    })
}

function AddToQuotation(RateCardID) {
  $("#LineItemID").val(RateCardID);
  $("#qty_modal").modal("show");
}
function checkQuantity(input) {
  if (input.value < 1) {
    input.value = 1;
  }
}
function UpdateQuotationDisplay(QuotationID) {
  $.post("includes/corporate_ticket_quotation_detail.php",
    {
      "ajax": 1,
      "QuotationID": QuotationID
    },
    function (data, status) {
      document.getElementById("quotation_view").innerHTML = data;
    })
}
function AddLineItemtoQuotation() {
  var TicketQuotationID = $("#TicketQuotationID").val();
  var LineItemID = $("#LineItemID").val();
  var quantity = $("#quantity").val();
  var TicketID = $("#Q_TicketID").val();
  $.post("action/add_line_item_to_quotation.php",
    {
      "TicketQuotationID": TicketQuotationID,
      "LineItemID": LineItemID,
      "quantity": quantity,
      "TicketID": TicketID
    },
    function (data, status) {
      response_data = JSON.parse(data);
      if (response_data.error == false) {
        if (TicketQuotationID == -1) {
          var QuotationID = response_data.QuotationID;
          $("#TicketQuotationID").val(QuotationID);
          UpdateQuotationDisplay(QuotationID);
          $("#update_quotation_button_text").text("Edit Quotation");
          $("#status_text").html("<span class='badge badge-danger'>Draft</span>");
          document.getElementById("update_quotation_status_button").style.display = "";
        }
        else {
          UpdateQuotationDisplay(TicketQuotationID);
        }
      }
      TechXAlert(response_data.message);

      $("#qty_modal").modal("hide");
    })
}

function DeleteLineItemFromQuotation(QuotationLineItemID, QuotationID) {
  $.post("action/delete_line_item_from_quotation.php",
    {
      "QuotationLineItemID": QuotationLineItemID
    },
    function (data, status) {
      response_data = JSON.parse(data);
      TechXAlert(response_data.message);
      UpdateQuotationDisplay(QuotationID);
    })
}

function EditLineItemFromQuotation(lineItemID, quotationID) {
  $.ajax({
    url: "ajax/get_line_item.php",
    type: "POST",
    data: { line_item_id: lineItemID, quotation_id: quotationID },
    success: function(response) {
      var data = JSON.parse(response);

      $("#editLineItemID").val(data.RateCardID);
      $("#editQuotationID").val(data.QuotationID);
      $("#editQuotationItems").val(lineItemID);
      $("#editType").val(data.Type);
      $("#editCategory").val(data.Category);

      // Load subcategories for this category and select the current one
      var category_id = $("#editCategory option:selected").data('id');
      var subcategoryDropdown = $("#editSubCategory");
      subcategoryDropdown.empty();
      subcategoryDropdown.append('<option value="">Please Select</option>');

      $.post("../company/action/get_subcategories_rate_card.php", {
          CategoryID: category_id
      }, function(subData) {
          subcategoryDropdown.append(subData);
          subcategoryDropdown.append('<option value="Others">Others</option>');
          subcategoryDropdown.val(data.SubCategory); // select current subcategory
      });

      $("#editLineItemName").val(data.LineItemName);
      $("#editMake").val(data.Make);
      $("#editHSN").val(data.HSN);
      $("#editUOM").val(data.UoM);
      $("#editPrice").val(data.Price);
      $("#editTax").val(data.Tax);
      $("#editQty").val(data.Qty);

      $("#editLineItemModal").modal("show");
    }
  });
}

// handle update
function updatelineitemTest()
{
  alert("Test");
}


function updateLineItem()
{
  console.log("Click");
  $.ajax({
    url: "action/update_line_item.php",
    type: "POST",
    data: {
      line_item_id: $("#editLineItemID").val(),
      quotation_id: $("#editQuotationID").val(),
      type: $("#editType").val(),
      category: $("#editCategory").val(),
      subcategory: $("#editSubCategory").val(),
      lineItemName: $("#editLineItemName").val(),
      make: $("#editMake").val(),
      hsn: $("#editHSN").val(),
      uom: $("#editUOM").val(),
      price: $("#editPrice").val(),
      tax: $("#editTax").val(),
      qty: $("#editQty").val(),
      RateCardID:$("#editLineItemID").val(),
      QuotationItemID:$("#editQuotationItems").val()
    },
    success: function(resp) {
      var resp=JSON.parse(resp);
      if(resp['error']==false){
        TechXAlert(resp['message']);
        location.reload();
      } else {
        TechXAlert("Failed to update line item.");
      }
    }
  });
}

// $("#updateLineItem").on("click", function() {
//   $.ajax({
//     url: "action/update_line_item.php",
//     type: "POST",
//     data: {
//       line_item_id: $("#editLineItemID").val(),
//       quotation_id: $("#editQuotationID").val(),
//       type: $("#editType").val(),
//       category: $("#editCategory").val(),
//       subcategory: $("#editSubCategory").val(),
//       lineItemName: $("#editLineItemName").val(),
//       make: $("#editMake").val(),
//       hsn: $("#editHSN").val(),
//       uom: $("#editUOM").val(),
//       price: $("#editPrice").val(),
//       tax: $("#editTax").val(),
//       qty: $("#editQty").val(),
//       RateCardID:$("#editLineItemID").val(),
//       QuotationItemID:$("#editQuotationItems").val()
//     },
//     success: function(resp) {
//       var resp=JSON.parse(resp);
//       if(resp['error']==false){
//         TechXAlert(resp['message']);
//         location.reload();
//       } else {
//         TechXAlert("Failed to update line item.");
//       }
//     }
//   });
// });

// -----new to change when reqred--------
// function UpdateQuotationStatus(QuotationStatus) {
//   $("#new_quotation_status").val(QuotationStatus);
//   var quoteCompanyDiv = document.getElementById("quotation-quote-company-div");
//   if(QuotationStatus == "Quote Sent Approval Pending")
//   {
//      if (quoteCompanyDiv) {
//       quoteCompanyDiv.style.display = "";
//     }

//     document.getElementById("quotation-tc-div").style.display = "";
//      document.getElementById("expected-budget-div").style.display = "";
//     document.getElementById("quotation-expiry-date-div").style.display = "";
//     $('#quotation-expiry-date').datepicker({
//       format: "yyyy-mm-dd",
//       todayBtn: "linked",
//       clearBtn: true,
//       todayHighlight: true,
//       autoclose: true,
//       startDate: '+0d',
//       beforeShowDay: function (date) {
//         return [date >= new Date()] // Disable past dates
//       }
//     });
//   }
//   else
//   {
//     if (quoteCompanyDiv) {
//       quoteCompanyDiv.style.display = "none";
//     }
//     document.getElementById("quotation-tc-div").style.display = "none";
//     document.getElementById("quotation-expiry-date-div").style.display = "none";
//      document.getElementById("expected-budget-div").style.display = "none";
//   }
//   $("#save_submit_quotation_modal").modal("show");
// }


// -------------------


function UpdateQuotationStatus(QuotationStatus) {
  $("#new_quotation_status").val(QuotationStatus);

  const quoteCompanyDiv = document.getElementById("quotation-quote-company-div");
  const tcDiv = document.getElementById("quotation-tc-div");
  const budgetDiv = document.getElementById("expected-budget-div");
  const expiryDiv = document.getElementById("quotation-expiry-date-div");

  if (QuotationStatus === "Quote Sent Approval Pending") {

    if (quoteCompanyDiv) quoteCompanyDiv.style.display = "";
    if (tcDiv) tcDiv.style.display = "";
    if (budgetDiv) budgetDiv.style.display = "";
    if (expiryDiv) expiryDiv.style.display = "";

  } else {

    if (quoteCompanyDiv) quoteCompanyDiv.style.display = "none";
    if (tcDiv) tcDiv.style.display = "none";
    if (budgetDiv) budgetDiv.style.display = "none";
    if (expiryDiv) expiryDiv.style.display = "none";
  }

  $("#save_submit_quotation_modal").modal("show");
}

function ModifyQuotationAction() {
  var QuotationID = $("#TicketQuotationID").val();
  var Remarks = $("#quotation-remarks").val();
  var QuotationStatus = $("#new_quotation_status").val();
  var quotation_tc = "";
  var CreatedBy = "";
   var expected_budget=$("#expected-budget").val();
  if(document.getElementById("quotation-tc"))
  {
    quotation_tc = $("#quotation-tc").val();
  }
  var quotation_expiry_date = "";
  if(document.getElementById("quotation-expiry-date"))
  {
    quotation_expiry_date = $("#quotation-expiry-date").val();
  }
  if(document.getElementById("CreatedBy"))
  {
    CreatedBy = $("#CreatedBy").val();
  }

   var quoteCompanyId = "";
  if (document.getElementById("quotation-quote-company-id")) {
    quoteCompanyId = $("#quotation-quote-company-id").val();
  }
  if (QuotationStatus === "Quote Sent Approval Pending") {
    if (!quoteCompanyId || quoteCompanyId === "") {
      TechXAlert("Please select quote company name.");
      $("#saving_quotation_modal_button").text("Save");
      return;
    }
  }

  $("#saving_quotation_modal_button").text("Saving...");
  $.post("action/change_quotation_status.php",
    {
      "QuotationID": QuotationID,
      "Remarks": Remarks,
      "QuotationStatus": QuotationStatus,
      "QuotationTC":quotation_tc,
      "QuotationExpiryDate":quotation_expiry_date,
      "CreatedBy":CreatedBy,
      "expectedbudget":expected_budget,
      "QuoteCompanyDetailsID": quoteCompanyId
    },
    function (data, status) 
    {
      if(QuotationStatus == "Quote Sent Approval Pending")
      {
        GenerateQuotation(QuotationID,'Send');
      } 
      response_data = JSON.parse(data);
      TechXAlert(response_data.message);
      $("#save_submit_quotation_modal").modal("hide");
      setTimeout(function () {
        location.reload();
      }, 2000);
    })
}

function QuotationShowRemarks(QuotationID) {
    $.post("action/get_quotation_history.php",
    {
      "QuotationID": QuotationID
    },
    function (data, status) {
      $("#quotation_remarks_modal_body").html(data);
      $("#remarks_quotation_modal").modal("show");
    })

}

function GenerateQuotation(QuotationID,Action) {
    if(QuotationID == -1)
    {
      TechXAlert("Please Refresh the page and they try downloading!");
      return false;
    }
    $.post("action/generate_quotation_pdf.php",
    {
      "QuotationID": QuotationID,
      "Action":Action
    },
    function (data, status) {
      data_response = JSON.parse(data);
      if(Action == "Download")
      {
        window.open("quotations/"+data_response.pdfname, '_blank');
      }
    })

}

function AddNonARCItem(CorporateID) {
  $("#rateCardModal").modal();
  $("#Quotation_Corporate_ID").val(CorporateID);
}

function Open_GenerateReportModal(TicketID) 
{
  $.post("ajax/get_ticket_details.php",
  {
    "TicketID": TicketID
  },
  function (data, status) 
  {
    var data_response = JSON.parse(data);  
    $("#ser_ticket_number").val(data_response.ticket_details.TicketID);
    $("#ser_client_ticket_reference_number").val(data_response.ticket_details.ClientTicketID);
    $("#ser_registered_date").val(data_response.ticket_details.CreatedDate);
    $("#ser_completed_date").val(data_response.ticket_details.CloseDate);
    $("#ser_problem_reported").val(data_response.ticket_details.Message);
    $("#ser_onsite_client").val(data_response.branch_details.SiteIncharge);
    $("#ser_onsite_client_contact").val(data_response.branch_details.BranchMobile);
    $("#ser_onsite_client_email").val(data_response.branch_details.BranchEmail);
    if(data_response.service_report_exist == false)
    {
      $("#ServiceReportID").val("-1");
    }
    else
    {
      $("#ServiceReportID").val(data_response.service_report_details.ID);
      $("#ser_problem_reported").val(data_response.service_report_details.ProblemReportedByClient);
      $("#ser_observation").val(data_response.service_report_details.Observation);
      $("#action_taken").val(data_response.service_report_details.ActionTaken);
      $("#ser_remarks").val(data_response.service_report_details.Remarks);
      $("#ser_onsite_client").val(data_response.service_report_details.ClientRepresentative);
      $("#ser_onsite_client_contact").val(data_response.service_report_details.ClientRepresentativeContact);
      $("#ser_onsite_client_email").val(data_response.service_report_details.ClientRepresentativeEmails);
      $("#ser_onsite_client_designation").val(data_response.service_report_details.ClientRepresentativeDesignation);
    }
    $('.generate_report_date').datepicker({
      format: "yyyy-mm-dd",
      todayBtn: "linked",
      clearBtn: true,
      todayHighlight: true,
      autoclose: true,
      endDate: new Date() // Disable future dates
    });
    $("#generate_service_report_modal").modal("show");
  })
}

function AddUpdateServiceReport(Action)
{
  $("#ServiceReportAction").val(Action);
  if(Action == "Submit")
  {
    var ser_problem_reported = $("#ser_problem_reported").val();
    if(ser_problem_reported == "")
    {
      TechXAlert("Kindly Enter Problem reported");
      return false;
    }
    var ser_observation = $("#ser_observation").val();
    if(ser_observation == "")
    {
      TechXAlert("Kindly Enter Observation");
      return false;
    } 
    var action_taken = $("#action_taken").val();
    if(action_taken == "")
    {
      TechXAlert("Kindly Enter Action Taken");
      return false;
    } 
  }
  $.ajax({
    url: "action/update_service_report.php",
    type: "POST",
    data: $("#generate_service_report_form").serialize(),
    success: function (data) 
    {
      data_response = JSON.parse(data);
      if(data_response.error == false)
      {
        /*if(Action == "Submit")
        {
          GenerateServiceReportPDF(data_response.ServiceReportID);
        }*/
      }
      setInterval(function () {
          location.reload();
        }, 1000);
      // var response = JSON.parse(data);
      // TechXAlert(response.message);
      // if (response.error == false) {
        
      // }
      // else {
      //   $("#assignment_change_btn").html("Save & Change");
      // }
    },
  });
}

function GenerateServiceReportPDF(ServiceReportID,Action)
{
  var service_type = $("#report_service_type").val();
  if(Action == "Download")
  {
    $("#donwload_report_pdf").text("Downloading...");
  }
  else
  {
    $("#send_report_pdf").text("Sending...");
  }
  //var url = "action/generate_amc_service_report_pdf.php";
  var url = "action/generate_service_report_pdf.php";
  if(service_type == "AMC")
  {
    url = "action/generate_amc_service_report_pdf.php";
    if($("#TicketCategoryName").length)
    {
      var TicketCategoryName = $("#TicketCategoryName").val();
      if(TicketCategoryName == "HVAC")
      {
        url = "action/generate_amc_service_report_pdf.php";
      }
    }
  }
  $.post(url,
  {
    "ServiceReportID": ServiceReportID,
    "Action":Action
  },
  function (data, status) 
  { 
    data_response = JSON.parse(data);
    console.log(data_response);
    if(Action == "Download")
    {
      $("#donwload_report_pdf").text("Download Report");
      window.open("reports/"+data_response.pdfname, '_blank');
    }
    else
    {
      TechXAlert("Report Sent to Client");
      $("#send_report_pdf").text("Send");
    }

  })
}