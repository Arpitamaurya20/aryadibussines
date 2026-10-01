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
  var media_image = $("#media_image").val();
  if(media_image == "")
  {
    TechXAlert("Kindly Upload Image");
    return false;
  }
  var allowedExtensions = ['jpg', 'jpeg', 'png', 'gif','pdf'];
  var fileExtension = media_image.split('.').pop().toLowerCase();

  if (allowedExtensions.includes(fileExtension)) {
    
  } 
  else 
  {
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
                setInterval(function() {
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