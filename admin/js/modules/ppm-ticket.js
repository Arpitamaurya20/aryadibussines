// View PPM ticket Details JS Start

$(document).ready(function () {
  /*$("#nav_corporate").addClass("open");
  $("#nav_corporate").addClass("active");
  $("#nav_branch").addClass("active");*/

  $("#booking_status").select2();
  $("#assignemployee_dropdown").select2();
  $("#ticket_status").select2();

  $('#view-ppm-details').dataTable({
    responsive: true
  });

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

function openPPMTicketAssignmodal() {
  $("#editassign_booking").modal();
  var TicketManager = $("#TicketManager_Access").val();
  console.log(TicketManager);
  if(TicketManager == true)
  {
    $('#due_date').datepicker({
                format: "yyyy-mm-dd",
                todayBtn: "linked",
                clearBtn: true,
                todayHighlight: true,
                autoclose: true,
            });
  }
  else
  {
    $("#due_date").attr("readonly", true);
  }
  
}
function ChangePPMTicketStatusAssignment() {
  var assignemployee = document.getElementById('assignemployee_dropdown').value;
  if(assignemployee == "-1"){
    TechXAlert("Please Assign Employee");
    return false;
  }
  var status = document.getElementById("ticket_status").value;
  var due_date = document.getElementById("due_date").value;
  if((status == "Work In Progress" || status == "Closed" || status == "Assigned" || status == "Submitted for Closure") && due_date == "")
  {
    TechXAlert("Kindly provide Due Date");
    return false;
  }
  $("#assignment_change_btn").html("Please Wait....");
  $.ajax({
    url: "action/change_ticket_status.php",
    type: "POST",
    data: $("#ppm_ticket_assignment_form").serialize(),
    dataType: "json",
    success: function (response) {
      if (!response || typeof response !== "object") {
        TechXAlert("Unexpected server response. Please try again.");
        $("#assignment_change_btn").html("Save & Change");
        return;
      }
      TechXAlert(response.message);
      if (response.error == false) {
        setTimeout(function () {
          location.reload();
        }, 2000);
      } else {
        $("#assignment_change_btn").html("Save & Change");
      }
    },
    error: function () {
      TechXAlert("Unable to update ticket assignment. Please try again.");
      $("#assignment_change_btn").html("Save & Change");
    },
  });
}


function EditPPMDate() {
  $("#edit_ppm_date").modal();
  $('#ppm_date').datepicker({
                format: "yyyy-mm-dd",
                todayBtn: "linked",
                clearBtn: true,
                todayHighlight: true,
                autoclose: true,
            });
}

function ChangePPMDate() {
  $("#ppm_date_change_btn").html("Please Wait....");
  $.ajax({
    url: "action/change_ppm_date.php",
    type: "POST",
    data: $("#ppm_ticket_date_form").serialize(),
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

// View PPM ticket Details JS End



// view PPM ticket Js Start 


$(document).ready(function() {
        /*$("#nav_corporate").addClass("open");
        $("#nav_corporate").addClass("active");
        $("#nav_branch").addClass("active");*/

        $("#view-ppm").dataTable({
            responsive: true,
        });

        $(".js-thead-colors a").on("click", function() {
            var theadColor = $(this).attr("data-bg");
            console.log(theadColor);
            $("#dt-basic-example thead").removeClassPrefix("bg-").addClass(theadColor);
        });

        $(".js-tbody-colors a").on("click", function() {
            var theadColor = $(this).attr("data-bg");
            console.log(theadColor);
            $("#dt-basic-example").removeClassPrefix("bg-").addClass(theadColor);
        });
    });

    function addFields() {
        // var productName = document.getElementById("name").value;
        $("#saving_btn").css("display","block");
        var dateToday = new Date(); 
        var numFields = parseInt(document.getElementById("number_of_days").value);
        var container = document.getElementById("field_container");
        container.innerHTML = "";
        for (var i = 0; i < numFields; i++) {

            container.innerHTML +=
                '<div class="col-12 mt-3"><label>Select Date <span class="text-danger">*</span></label><input type="text" class="form-control ppm_date" name="ppmdate[]" id="ppm' +
                (i + 1) + '" placeholder="Select PPM Date"></div>';

            $('.ppm_date').datepicker({
                format: "yyyy-mm-dd",
                todayBtn: "linked",
                clearBtn: true,
                todayHighlight: true,
                autoclose: true,
                startDate:'+0d',
            });

        }
        
    }

   function RaisePPMTickets() {
            let date = document.getElementsByClassName('ppm_date').value;
            if(date == ""){
              TechXAlert("Please Select All PPM date");
              return false;
            }
            $("#saving_btn").html("Saving....");
              $.ajax({
                    url: "action/add_ppm_ticket.php",
                    type: "POST",
                    data: $("#raise_ppm_ticket").serialize(),
                    success: function (data) {
                         var response = JSON.parse(data);
                         TechXAlert(response.message);
                        $("#raiseamcticket").modal("hide");
                        if (response.error == false) {
                            setInterval(function () {
                                location.reload();
                            }, 2000);
                        }
                },
            });
            return false;
    }

    function openPPM_modal(BranchAssetID, BranchID, CorporateID, CreatedBy) {
        $('#corporate_modal_id').val(CorporateID);
        $('#branch_modal_id').val(BranchID);
        $('#branch_asset_modal_id').val(BranchAssetID);
        $('#created_by_modal').val(CreatedBy);
        $("#branch_modal_title").html("Add PPM");
        $("#raise_ppm_ticket")[0].reset();
        $("#form_action").val("add");
        $("#add_edit_arc_modal").modal();
    }

    function ViewPPMTicketDetails(TicketID) {
        $.post(
            "../controllers/setSession.php", {
                TicketID: TicketID,
            },
            function(data, status) {
                window.open("view-ppm-tickets-details.php",'_blank');
            }
        );
    }

    $('.ppm_date').datepicker({
                format: "yyyy-mm-dd",
                todayBtn: "linked",
                clearBtn: true,
                todayHighlight: true,
                autoclose: true,
                minDate: 0,

            });



// View PPM ticket JS End

  function ExportPPMTicketData() {
  $.ajax({
      url: "action/export_ppm_ticket.php",
      type: "POST",
      data: $("#import_form").serialize(),
      success: function (data) {
          window.location.href = "report.xls";
      },
  });
  return false;
}



function UploadTicketMedia(action)
{
  $("#media_action").val(action);
  $("#upload_media").modal("show");
}
function UploadTicketMediaAction()
{
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

function GeneratePPMServiceReportPDF(ServiceReportID,Action)
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
  var url = "../corporate-tickets/action/generate_ppm_service_report_pdf.php";
  var useDynamicPpm = $("#UseDynamicPPM").length ? $("#UseDynamicPPM").val() : "0";
  var hasDynamicReport = $("#HasDynamicReport").length ? $("#HasDynamicReport").val() : "0";
  if (useDynamicPpm == "1" || hasDynamicReport == "1") {
    url = "../dynamic-ppm/action/generate_dynamic_ppm_service_report_pdf.php";
  } else {
  var BranchAssetCategoryID = $("#BranchAssetCategoryID").val();
  if(BranchAssetCategoryID == 34)
  {
    url = "../corporate-tickets/action/generate_ppm_hvac_service_report_pdf.php"
  }
  if(BranchAssetCategoryID == 8)
  {
    url = "../corporate-tickets/action/generate_ppm_cctv_service_report_pdf.php"
  }

  if(BranchAssetCategoryID == 23)
  {
    url = "../corporate-tickets/action/generate_ppm_ep_service_report_pdf.php"
  }

   if(BranchAssetCategoryID == 43)
  {
    url = "../corporate-tickets/action/generate_ppm_fas_service_report_pdf.php"
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
      window.open("../corporate-tickets/reports/"+data_response.pdfname, '_blank');
    }
    else
    {
      TechXAlert("Report Sent to Client");
      $("#send_report_pdf").text("Send");
    }

  })
}