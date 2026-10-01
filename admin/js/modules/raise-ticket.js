$(document).ready(function () {
  $("#nav_raise_ticket").addClass("active");
});

$("#corporate_name").select2();
$("#branch_name").select2();
$("#service_name").select2();
$("#service_type").select2();
$("#priority").select2();
var ServiceType = document.getElementById("service_type");
ServiceType.addEventListener("input", function () {
  convertToUpperCase(ServiceType);
});

function convertToUpperCase(inputElement) {
  // get the value of the input field
  var inputValue = inputElement.value;
  // convert the value to uppercase
  var uppercaseValue = inputValue.toUpperCase();
  // update the input field with the uppercase value
  inputElement.value = uppercaseValue;
}

function showRaiseTicketProgress(message) {
  hideRaiseTicketProgress();

  var overlay = document.createElement("div");
  overlay.id = "raise-ticket-progress-overlay";
  overlay.style.cssText =
    "position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(15,23,42,0.45);display:flex;align-items:center;justify-content:center;z-index:10000;";

  overlay.innerHTML =
    '<div style="background:#fff;border-radius:12px;padding:28px 32px;min-width:320px;max-width:90%;box-shadow:0 20px 45px rgba(0,0,0,0.18);text-align:center;">' +
    '<div class="spinner-border text-primary mb-3" role="status" style="width:3rem;height:3rem;"></div>' +
    '<div style="font-size:18px;font-weight:600;color:#111827;margin-bottom:8px;">Processing Ticket</div>' +
    '<div style="font-size:14px;color:#4b5563;line-height:1.5;">' +
    (message || "Please wait while your ticket is being raised...") +
    "</div></div>";

  document.body.appendChild(overlay);
}

function hideRaiseTicketProgress() {
  var overlay = document.getElementById("raise-ticket-progress-overlay");
  if (overlay) {
    overlay.remove();
  }
}

function resetRaiseTicketButton() {
  var button = document.getElementById("raise_ticket_btn");
  if (button) {
    button.innerHTML = "Raise";
    button.style.pointerEvents = "";
    button.classList.remove("disabled");
  }
}

function disableRaiseTicketButton() {
  var button = document.getElementById("raise_ticket_btn");
  if (button) {
    button.innerHTML = "Submitting...";
    button.style.pointerEvents = "none";
    button.classList.add("disabled");
  }
}

function RaiseTicket() {
  // corporate validation
  var Corporate = document.getElementById("corporate_name").value;
  if (Corporate === "") {
    TechXAlert("Please Select Corporate");
    return false;
  }
  var Branch = document.getElementById("branch_name").value;
  if (Branch === "") {
    TechXAlert("Please Select Branch");
    return false;
  }
  var CheckServiceType = document.getElementById("service_type").value;
  if (CheckServiceType === "") {
    TechXAlert("Please Select Service Type");
    return false;
  }
  var Service = document.getElementById("service_name").value;
  if (Service === "") {
    TechXAlert("Please Fill Service Name");
    return false;
  }

  var SubService = document.getElementById("sub_service_name").value;
  if(SubService == "Others")
  {
    var SubService_Others = document.getElementById("sub_service_others").value;
    if(SubService_Others == "")
    {
      TechXAlert("Please Fill Sub Services(Others)");
      return false;
    }
  }

  // Get all input fields with the mandatoryfield="Yes" attribute
  var mandatoryFields = document.querySelectorAll('input[mandatoryfield="Yes"]');
  var allFilled = true; 
  // Loop through the mandatory fields to check their values
  mandatoryFields.forEach(function(field) {
      if (!field.value) { // Check if the field is empty
          
          // You can add more actions here, like highlighting the field, etc.
          allFilled = false;
      }
  });

  if (!allFilled) 
  {
      TechXAlert("Please fill in all mandatory fields");
      event.preventDefault(); // Prevent form submission if any mandatory field is empty
      return false;
  }


  disableRaiseTicketButton();
  showRaiseTicketProgress("Raising ticket and sending approval mail if required...");

  let myForm = document.getElementById("raise_ticket_form");
  var formData = new FormData(myForm);
  $.ajax({
    url: "action/add-ticket-action.php",
    type: "POST",
    data: formData,
    success: function (data) {
      hideRaiseTicketProgress();

      var response = {};
      try {
        response = typeof data === "object" ? data : JSON.parse(data);
      } catch (e) {
        response = {
          error: true,
          message: "Unexpected server response. Please try again.",
        };
      }

      TechXAlert(response.message || "Unable to raise ticket.");

      if (response.error == false) {
        setTimeout(function () {
          location.reload();
        }, 2000);
      } else {
        resetRaiseTicketButton();
      }
    },
    error: function () {
      hideRaiseTicketProgress();
      resetRaiseTicketButton();
      TechXAlert("Unable to raise ticket. Please check your connection and try again.");
    },
    cache: false,
    contentType: false,
    processData: false,
  });
  return false;
}


  function SelectService() {
        $.post("action/get_subservices.php", {
                ServiveID: $("#service_name").val()
            },
            function(data, status) {
                document.getElementById("sub_services_div").style.display = "block";
                document.getElementById("sub_service_name").innerHTML = data;
                $("#sub_service_name").select2();
            });
    }
    function GetSubCategories() 
    {
        var selectElement = document.getElementById('service_name');
        var selectedOption = selectElement.options[selectElement.selectedIndex];
        var serviceId = selectedOption.getAttribute('data-id');
          $.post("action/get_subcategories.php", {
                  CategoryID: serviceId
              },
              function(data, status) {
                  document.getElementById("sub_services_div").style.display = "block";
                  document.getElementById("sub_service_name").innerHTML = data;
                  $("#sub_service_name").select2();
              });
    }

 function SelectCorporate() 
 {
    
    $.post("action/get_branches.php", {
            CorporateID: $("#corporate_name").val()
    },
    function(data, status) {
        document.getElementById("branch_div").style.display = "block";
        document.getElementById("branch_name").innerHTML = data;

        $.post("ajax/get_corporate_configurable_fields.php", 
        {
            CorporateID: $("#corporate_name").val()
        },
        function(data, status) {
            document.getElementById("configurable-fields").innerHTML = data;
            $(".add_date_condition").datepicker({
              format: "yyyy-mm-dd",
              todayBtn: "linked",
              clearBtn: true,
              todayHighlight: true,
              autoclose: true,
            });
        });

    });
}

function displayOthersSubserviceTextBox(SubService)
{
  if(SubService == "Others")
  {
    document.getElementById("sub_services_others_div").style.display = "";
  }
  else
  {
    document.getElementById("sub_services_others_div").style.display = "none";
  }
}
    



