<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Project Tasks</title>
    <link rel="stylesheet" href="../css/bootstrap.min.css">
    <link rel="stylesheet" href="../js/datagrid/datatables/datatables.bundle.css">
    <link rel="stylesheet" href="../js/formplugins/bootstrap-datepicker/bootstrap-datepicker.css">

    <style>
        .modal_header {
            background-color: #003f88;
            color: #fff;
        }

        .modal_header button {
            opacity: 1;
            color: #fff;
        }

        .edit_header {
            background-color: #027dc1;
            color: #fff;
        }

        .form_submit {
            background-color: #2196f3;
            color: #fff;
            border: none;
            border-radius: 4px;
        }

        .edit_header .close {
            opacity: 1 !important;
            color: #fff;
        }

        .tab_modal_heading h2 {
            font-size: 18px;
            text-align: center;
            color: #fff;
            font-weight: 500;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <!-- Page Container -->
    <div class="row">
        <div class="col-xl-12">
            <div id="panel-1" class="panel">
                <div class="panel-hdr">
                    <h2>View Project Tasks</h2>
                     <a onclick="ViewProjectDetails(<?php echo $_SESSION['TicketID'] ?>)" class="btn btn-primary text-white" style="margin-right:20px;">View/Update Project Details</a>
                    <a onclick="DownloadDprReport(<?php echo $_SESSION['TicketID']  ?>)" class="btn btn-success text-white" style="margin-right:20px;">Download latest Report</a>
                    <a onclick="SendDprReport(<?php echo $_SESSION['TicketID']  ?>)" class="btn btn-warning text-white" style="margin-right:20px;">Send</a>
                    <a onclick="OpenTaskModal()" class="btn btn-info text-white" style="margin-right:20px;">Add TASK</a>

                </div>
                <div class="panel-container show">
                    <div class="panel-content">
                        <!-- datatable start -->
                        <table id="view-project-tasks" class="table table-bordered table-hover table-striped w-100">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Task Title</th>
                                    <th>Start Date</th>
                                    <th>Expected End Date</th>
                                    <th>Actual End Date</th>
                                    <th>Progress</th>
                                    <th>Cost</th>
                                    <th>Status</th>
                                    <!-- <th>Edit</th> -->
                                </tr>
                            </thead>
                        </table>
                        <!-- datatable end -->
                    </div>
                </div>
            </div><!-- panel-1 -->
        </div><!-- col-xl-12 -->
    </div> <!-- row -->

    <script src="../js/jquery-3.6.0.min.js"></script>
    <script src="../js/bootstrap.bundle.min.js"></script>
    <script src="../js/datagrid/datatables/datatables.bundle.js"></script>
    <script src="../js/formplugins/bootstrap-datepicker/bootstrap-datepicker.js"></script>
    <script src="../js/modules/projects.js?v=20260708a"></script>

    <script>
        $(document).ready(function() {
            // Initialize DataTable
            $('#view-project-tasks').DataTable({
                responsive: true,
                processing: true,
                serverSide: true,
                ordering: false,
                serverMethod: 'post',
                ajax: {
                    url: 'ajax/dpr_view_ajax.php'
                },
                columnDefs: [{
                    targets: [0],
                    className: "text-center"
                }],
                columns: [
                    { 
                        data: "id",
                        render: function(data, type, row, meta) {
                            return meta.row + meta.settings._iDisplayStart + 1;
                        }
                    },
                    { data: 'TaskTitle' },
                    { data: 'StartDate' },
                    { data: 'ExpectedEndDate' },
                    { data: 'ActualEndDate' },
                    { data: 'Progress' },
                    { data: 'Cost' },
                    { data: 'Status' }
                ]
            });

            // Highlight menu
            $("#js-nav-menu").addClass("active open");
            // $("#nav_projects").addClass("active");
        });

                                function OpenTaskModal() {
                                  let TicketID = $("#ticket_id").val();

                                  if (!TicketID) {
                                    TechXAlert("Ticket ID missing!");
                                    return;
                                  }

                                  $.ajax({
                                    url: "./ajax/check-project-exists.php",
                                    method: "POST",
                                    data: { TicketID: TicketID },
                                    dataType: "json",
                                    success: function (response) {
                                      if (response.exists) {
                                        $("#add_task_modal").modal("show");
                                      } else {
                                        $("#projectDetailsModal").modal("show");
                                        $("#modal_ticket_id").val(TicketID);
                                        $("#project_start_date").datepicker({
                                          format: "yyyy-mm-dd",
                                          todayBtn: "linked",
                                          clearBtn: true,
                                          todayHighlight: true,
                                          autoclose: true,
                                        });
                                        $("#project_end_date").datepicker({
                                          format: "yyyy-mm-dd",
                                          todayBtn: "linked",
                                          clearBtn: true,
                                          todayHighlight: true,
                                          autoclose: true,
                                        });
                                      }
                                    },
                                    error: function (xhr, status, error) {
                                      console.error(error);
                                      TechXAlert("Error checking project information.");
                                    }
                                  });
                                }


        // Initialize datepickers inside modal
        function initializeDatePickers(modalSelector) {
            $(modalSelector + " .task_start_date").datepicker({
                format: "yyyy-mm-dd",
                todayBtn: "linked",
                clearBtn: true,
                todayHighlight: true,
                autoclose: true,
            });
            $(modalSelector + " .task_end_date").datepicker({
                format: "yyyy-mm-dd",
                todayBtn: "linked",
                clearBtn: true,
                todayHighlight: true,
                autoclose: true,
            });
        }

        // Add more tasks dynamically
        function AddMoreTask() {
            let current_counter = parseInt($("#task_counter").val());
            let next_counter = current_counter + 1;

            $.post("ajax/task_form.php", { counter: next_counter }, function(data, status) {
                $("#task_div_ui_add").append(data);
                initializeDatePickers("#add_task_modal");
            });

            $("#task_counter").val(next_counter);
        }

        // Prevent form submission default
        $("#add_task_form, #task_daily_progress_form").on("submit", function(e) {
            e.preventDefault();
        });


           function SryncLineItemTask() {
            let TicketID = $("#ticket_id").val();

            if (!TicketID) {
                TechXAlert("Ticket ID missing!");
                return;
            }

            TechXAlert("Syncing line items...");

            $.ajax({
                url: "../../api/load-line-item.php",
                method: "POST",
                data: { TicketID: TicketID },
                dataType: "json",
                success: function(response) {
                    if (response.data && response.data.length > 0) {
                        let counter = 0;
                        $("#task_div_ui_add").html(""); // clear old tasks

                        // Use async/await style chaining to ensure order
                        (async function addLineItemsSequentially() {
                            for (const item of response.data) {
                                counter++;
                                await $.post("ajax/task_form.php", { counter: counter }, function(html) {
                                    // Append task block
                                    $("#task_div_ui_add").append(html);
                                    // Fill Task Name *after* appending
                                    let selector = `#task_row_${counter} textarea[name='task_name[]']`;
                                    $(selector).val(item.LineItemName);

                                    // Optional: fill other fields if you want
                                    // $(`#task_row_${counter} input[name='task_start_date[]']`).val("...");
                                    // $(`#task_row_${counter} input[name='task_end_date[]']`).val("...");

                                    // Reinitialize datepickers
                                    initializeDatePickers("#add_task_modal");
                                });
                            }

                            // Update task counter after all items are added
                            $("#task_counter").val(counter);
                            TechXAlert(`${response.data.length} line items synced as tasks.`);
                        })();
                    } else {
                        TechXAlert("No line items found for this ticket.");
                    }
                },
                error: function(xhr, status, error) {
                    console.error(error);
                    TechXAlert("Error syncing line items.");
                }
            });
        }



    </script>

                <!-- Edit Task Modal -->
                <div class="modal fade" id="task_status_modal" role="dialog" aria-hidden="true">
                    <div class="modal-dialog modal-lg" role="document">
                        <div class="modal-content">
                            <form id="task_daily_progress_form">
                                <div class="modal-header modal_header">
                                    <h5 class="modal-title"> Edit Tasks </h5>
                                    <div class="ml-3">
                                        <select class="form-control" name="project_task_status" id="project_task_status">
                                            <option value="">Select Task Status</option>
                                            <option value="To Start">To Start</option>
                                            <option value="In Progress">In Progress</option>
                                            <option value="On Hold">On Hold</option>
                                            <option value="Completed">Completed</option>
                                        </select>
                                    </div>    
                                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                                </div>
                                <div class="modal-body">
                                    <div class="form-group" id="task_div_ui"></div>
                                    <input type="hidden" id="task_id" name="task_id" value="-1" />
                                    <button type="button" class="btn btn-primary" id="project_edit_task_modal_btn" onclick="SaveTaskProgressDPR()">Save</button>
                                    <?php if($UserType == "Admin"){ ?>
                                        <button type="button" class="btn btn-danger" id="project_modal_btn" onclick="DeleteProjectTask()">Delete</button>
                                    <?php } ?>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

    <!-- Add Task Modal -->
                    <div class="modal fade" id="add_task_modal" role="dialog" aria-hidden="true">
                        <div class="modal-dialog modal-xl" role="document">
                            <div class="modal-content">
                                <div class="modal-header modal_header">
                    <h5 class="modal-title">Create Task</h5>

                    <!-- Add Task Button -->
                    <a onclick="AddMoreTask()" class="btn btn-sm btn-light text-dark ml-3">
                        <i class="fas fa-plus mr-1"></i> Add Task
                    </a>

                    <!-- Sync Line Item Button -->
                    <a onclick="SryncLineItemTask()" class="btn btn-sm btn-info text-white ml-2">
                        <i class="fas fa-sync-alt mr-1"></i> Sync Line Item
                    </a>

                    <!-- Close Button -->
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <div class="modal-body">
                    <form id="add_task_form">
                        <div class="form-group" id="task_div_ui_add">
                            <?php include('ajax/task_form.php'); ?>
                        </div>
                        <input type="hidden" id="ticket_id" name="ticket_id" value="<?php echo $_SESSION['TicketID']  ?>" />
                        <button type="button" class="btn btn-primary" id="project_task_modal_btn" onclick="SaveTaskNew()">Save</button>
                        <input type="hidden" name="task_counter" id="task_counter" value="1" />
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
<script src="../js/formplugins/bootstrap-datepicker/bootstrap-datepicker.js"></script>
<script>
    
function SaveTaskNew()
{
  $("input[name='task_end_date[]']").each(function() 
  {
    if($(this).val() == "") 
    {
        TechXAlert("End Date can't be blank for any task!");
        return false;
    }
  });
  $("input[name='task_start_date[]']").each(function() 
  {
    if($(this).val() == "") 
    {
        TechXAlert("Start Date can't be blank for any task!");
        return false;
    }
  });
  
  $("textarea[name='task_name[]']").each(function() {
    if ($(this).val().trim() == "") {
        TechXAlert("Task Description can't be blank for any task!");
        return false; // Exit the loop early if a blank value is found
    }
  });
  let myForm = document.getElementById("add_task_form");
  var formData = new FormData(myForm);
  $.ajax({
    url: "action/add_tasks.php",
    type: "POST",
    data: formData,
    success: function (data) {
      var response = JSON.parse(data);
      console.log(response);
      TechXAlert(response.message);
      if (response.error == false) {
        $('#view-project-tasks').DataTable().ajax.reload();
        $('#add_task_modal').modal('hide');
      }
      else
      {
        
      }
    },
      cache: false,
      contentType: false,
      processData: false,
  });
  return false;
  return false;
}

//  function saveprojectdetails() {
//   let form = $("#projectDetailsForm");
//   let formData = form.serialize();

//   // --- Validation ---
//   let isValid = true;
//   let errorMessage = "";

//   const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
//   const phonePattern = /^\d{10}$/;

//   // Reset invalid styles
//   form.find("input").removeClass("is-invalid");

//   // --- Check for empty fields ---
//   form.find("input").each(function () {
//     const value = $(this).val().trim();
//     const label = $(this).closest(".form-group").find("label").text().replace("*", "").trim();
//     if (value === "") {
//       isValid = false;
//       errorMessage += `• ${label} cannot be empty\n`;
//       $(this).addClass("is-invalid");
//     }
//   });

//   // --- Email Validations ---
//   const emailFields = [
//     { selector: '[name="CustomerManagerEmail"]', label: "Customer Manager Email" },
//     { selector: '[name="CustomerSupervisorEmail"]', label: "Customer Supervisor Email" },
//     { selector: '[name="TechXpertManagerEmail"]', label: "TechXpert Manager Email" },
//   ];

//   emailFields.forEach(f => {
//     const val = $(f.selector).val().trim();
//     if (val !== "" && !emailPattern.test(val)) {
//       isValid = false;
//       errorMessage += `• Invalid ${f.label}\n`;
//       $(f.selector).addClass("is-invalid");
//     }
//   });

//   // --- Phone Validations ---
//   const phoneFields = [
//     { selector: '[name="CustomerManagerPhone"]', label: "Customer Manager Phone" },
//     { selector: '[name="CustomerSupervisorPhone"]', label: "Customer Supervisor Phone" },
//     { selector: '[name="TechXpertManagerPhone"]', label: "TechXpert Manager Phone" },
//   ];

//   phoneFields.forEach(f => {
//     const val = $(f.selector).val().trim();
//     if (val !== "" && !phonePattern.test(val)) {
//       isValid = false;
//       errorMessage += `• ${f.label} must be a 10-digit number\n`;
//       $(f.selector).addClass("is-invalid");
//     }
//   });

//   // --- If validation fails ---
//   if (!isValid) {
//     TechXAlert("⚠️ Please fix the following issues:\n\n" + errorMessage);
//     return;
//   }

//   // --- Submit via AJAX ---
//   $.ajax({
//     url: "./action/add_update_project_info.php",
//     type: "POST",
//     data: formData,
//     dataType: "json",
//     beforeSend: function () {
//       $("#saveProjectDetails").prop("disabled", true).text("Saving...");
//     },
//     success: function (response) {
//       $("#saveProjectDetails").prop("disabled", false).text("Save Project");
//       if (response.error) {
//         TechXAlert(response.message || "Error saving project!");
//       } else {
//         $("#projectDetailsModal").modal("hide");
//         TechXAlert("✅ " + response.message);
//         SryncLineItemTask();
//       }
//     },
//     error: function (xhr, status, error) {
//       $("#saveProjectDetails").prop("disabled", false).text("Save Project");
//       console.error(error);
//       TechXAlert("❌ Failed to save project details.");
//     },
//   });
// }

function saveprojectdetails() {
  let form = $("#projectDetailsForm");
  let formData = form.serialize();

  let isValid = true;
  let errorMessage = "";

  const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  const phonePattern = /^\d{10}$/;

  // Reset previous errors
  form.find("input").removeClass("is-invalid");

  /* =========================
     REQUIRED FIELD VALIDATION
     ========================= */
  const requiredFields = [
    { selector: '[name="ProjectName"]', label: "Project Name" },
    { selector: '[name="StartDate"]', label: "Start Date" },
    { selector: '[name="EndDate"]', label: "End Date" },

    { selector: '[name="CustomerManagerName"]', label: "Customer Manager Name" },
    { selector: '[name="CustomerManagerEmail"]', label: "Customer Manager Email" },
    { selector: '[name="CustomerManagerPhone"]', label: "Customer Manager Phone" },

    { selector: '[name="CustomerSupervisorEmail"]', label: "Customer Supervisor Email" },
    { selector: '[name="CustomerSupervisorPhone"]', label: "Customer Supervisor Phone" },

    { selector: '[name="TechXpertManagerEmail"]', label: "TechXpert Manager Email" },
    { selector: '[name="TechXpertManagerPhone"]', label: "TechXpert Manager Phone" },
  ];

  requiredFields.forEach(f => {
    const el = $(f.selector);
    if (el.val().trim() === "") {
      isValid = false;
      errorMessage += `• ${f.label} cannot be empty\n`;
      el.addClass("is-invalid");
    }
  });

  /* =========================
     EMAIL VALIDATION
     ========================= */
  const emailFields = [
    { selector: '[name="CustomerManagerEmail"]', label: "Customer Manager Email" },
    { selector: '[name="CustomerSupervisorEmail"]', label: "Customer Supervisor Email" },
    { selector: '[name="TechXpertManagerEmail"]', label: "TechXpert Manager Email" },
    { selector: '[name="OtherEmail"]', label: "Other Email(s)" },
    { selector: '[name="TechxpertOtherEmail"]', label: "TechXpert Other Email(s)" },
  ];

  emailFields.forEach(f => {
    const val = $(f.selector).val().trim();
    if (val !== "") {
      const emails = val.split(",");
      emails.forEach(e => {
        if (!emailPattern.test(e.trim())) {
          isValid = false;
          errorMessage += `• Invalid ${f.label}\n`;
          $(f.selector).addClass("is-invalid");
        }
      });
    }
  });

  /* =========================
     PHONE VALIDATION
     ========================= */
  const phoneFields = [
    { selector: '[name="CustomerManagerPhone"]', label: "Customer Manager Phone" },
    { selector: '[name="CustomerSupervisorPhone"]', label: "Customer Supervisor Phone" },
    { selector: '[name="TechXpertManagerPhone"]', label: "TechXpert Manager Phone" },
    { selector: '[name="OtherPhonenumber"]', label: "Other Phone Number(s)" },
    { selector: '[name="TechxpertOtherPhonenumber"]', label: "TechXpert Other Phone Number(s)" },
  ];

  phoneFields.forEach(f => {
    const val = $(f.selector).val().trim();
    if (val !== "") {
      const phones = val.split(",");
      phones.forEach(p => {
        if (!phonePattern.test(p.trim())) {
          isValid = false;
          errorMessage += `• ${f.label} must be 10-digit numbers\n`;
          $(f.selector).addClass("is-invalid");
        }
      });
    }
  });

  /* =========================
     STOP IF ERROR
     ========================= */
  if (!isValid) {
    TechXAlert("⚠️ Please fix the following issues:\n\n" + errorMessage);
    return;
  }

  /* =========================
     AJAX SUBMIT
     ========================= */
  $.ajax({
    url: "./action/add_update_project_info.php",
    type: "POST",
    data: formData,
    dataType: "json",
    beforeSend: function () {
      $("#saveProjectDetails").prop("disabled", true).text("Saving...");
    },
    success: function (response) {
      $("#saveProjectDetails").prop("disabled", false).text("Save Project");
      if (response.error) {
        TechXAlert(response.message || "Error saving project!");
      } else {
        $("#projectDetailsModal").modal("hide");
        TechXAlert("✅ " + response.message);
        SryncLineItemTask();
      }
    },
    error: function () {
      $("#saveProjectDetails").prop("disabled", false).text("Save Project");
      TechXAlert("❌ Failed to save project details.");
    }
  });
}



function ViewProjectDetails(ticketID) {

    $.ajax({
        url: "action/get_project_details.php",
        type: "POST",
        data: { TicketID: ticketID },
        dataType: "json",
        success: function (response) {

            if (response.status == "success") {

                let p = response.project;
                let t = response.team;

                // ===== Project Table Data =====
                $("input[name='ProjectName']").val(p?.ProjectName || "");
                $("input[name='StartDate']").val(p?.StartDate || "");
                $("input[name='EndDate']").val(p?.EndDate || "");
                $("input[name='TicketNumber']").val(p?.TicketNumber || "");

                $("#modal_ticket_id").val(ticketID);

                // ===== Team Details Data =====
                $("input[name='CustomerManagerName']").val(t?.CustomerManagerName || "");
                $("input[name='CustomerManagerEmail']").val(t?.CustomerManagerEmail || "");
                $("input[name='CustomerManagerPhone']").val(t?.CustomerManagerPhone || "");

                $("input[name='CustomerSupervisorEmail']").val(t?.CustomerSupervisorEmail || "");
                $("input[name='CustomerSupervisorPhone']").val(t?.CustomerSupervisorPhone || "");

                $("input[name='TechXpertManagerEmail']").val(t?.TechXpertManagerEmail || "");
                $("input[name='TechXpertManagerPhone']").val(t?.TechXpertManagerPhone || "");

                // ===== Newly Added Fields =====
                $("input[name='OtherEmail']").val(t?.OtherEmail || "");
                $("input[name='OtherPhonenumber']").val(t?.OtherPhonenumber || "");

                $("input[name='TechxpertOtherEmail']").val(t?.TechxpertOtherEmail || "");
                $("input[name='TechxpertOtherPhonenumber']").val(t?.TechxpertOtherPhonenumber || "");

                // Update button text
                $("#saveProjectDetails").text("Update Project");

                // Show modal
                $("#projectDetailsModal").modal("show");

            } else {
                alert("No project details found!");
            }
        }
    });
}


</script>



<!-- Project Info Modal -->
<!-- Project Info Modal -->
<div class="modal fade" id="projectDetailsModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      
      <div class="modal-header modal_header">
        <h5 class="modal-title">Enter Project Details</h5>
        <button type="button" class="close" data-dismiss="modal">&times;</button>
      </div>

      <div class="modal-body">
        <form id="projectDetailsForm">
          <input type="hidden" id="modal_ticket_id" name="TicketID">

          <!-- Project Information -->
          <h5 class="mb-3">Project Information</h5>
          <div class="form-row">
            <div class="form-group col-md-6">
              <label>Project Name <span class="text-danger">*</span></label>
              <input type="text" name="ProjectName" class="form-control" required>
            </div>
            <div class="form-group col-md-3">
              <label>Start Date <span class="text-danger">*</span></label>
              <input type="text" id="project_start_date" name="StartDate" class="form-control" autocomplete="off" required>
            </div>
            <div class="form-group col-md-3">
              <label>End Date <span class="text-danger">*</span></label>
              <input type="text" id="project_end_date" name="EndDate" class="form-control" autocomplete="off" required>
            </div>
          </div>

          <div class="form-group">
            <label>Ticket Reference</label>
            <input type="text" name="TicketNumber" class="form-control">
          </div>

          <hr>

          <!-- Customer Side Details -->
          <div class="p-3 mb-3" style="background:#f3f7ff; border-radius:8px; border:1px solid #d6e4ff;">
          <h5 class="mb-3">Customer Side Details</h5>

          <div class="form-row">
            <div class="form-group col-md-4">
              <label>Manager Name <span class="text-danger">*</span></label>
              <input type="text" name="CustomerManagerName" class="form-control">
            </div>
            <div class="form-group col-md-4">
              <label>Manager Email <span class="text-danger">*</span></label>
              <input type="email" name="CustomerManagerEmail" class="form-control">
            </div>
            <div class="form-group col-md-4">
              <label>Manager Phone <span class="text-danger">*</span></label>
              <input type="text" name="CustomerManagerPhone" class="form-control">
            </div>
          </div>

         

          <div class="form-row">
            <div class="form-group col-md-6">
              <label>Supervisor Email <span class="text-danger">*</span></label>
              <input type="email" name="CustomerSupervisorEmail" class="form-control">
            </div>
            <div class="form-group col-md-6">
              <label>Supervisor Phone <span class="text-danger">*</span></label>
              <input type="text" name="CustomerSupervisorPhone" class="form-control">
            </div>
          </div>


           <!-- Other Customer Contact -->
          <div class="form-row">
                <div class="form-group col-md-6">
                    <label>Other Emails (comma separated)</label>
                    <input type="text" name="OtherEmail" class="form-control" placeholder="z1@gmail.com,z2@gmail.com,z3@gmail.com,">
                </div>
                <div class="form-group col-md-6">
                    <label>Other Phone Numbers (comma separated)</label>
                    <input type="text" name="OtherPhonenumber" class="form-control" placeholder="90xxxxxxxx,91xxxxxxxxxxxx">
                </div>
            </div>


          <hr>
      </div>

          <!-- TechXpert Side Details -->
          <div class="p-3 mb-3" style="background:#fff7e6; border-radius:8px; border:1px solid #ffe7c2;">
          <h5 class="mb-3">TechXpert Side Details</h5>

          <div class="form-row">
            <div class="form-group col-md-6">
              <label>Manager Email <span class="text-danger">*</span></label>
              <input type="email" name="TechXpertManagerEmail" class="form-control">
            </div>
            <div class="form-group col-md-6">
              <label>Manager Phone <span class="text-danger">*</span></label>
              <input type="text" name="TechXpertManagerPhone" class="form-control">
            </div>
          </div>

          <!-- Other TechXpert Contact -->
         <div class="form-row">
    <div class="form-group col-md-6">
        <label>Other Emails (comma separated)</label>
        <input type="text" name="TechxpertOtherEmail" class="form-control" placeholder="z1@gmail.com,z2@gmail.com,z3@gmail.com,">
    </div>
    <div class="form-group col-md-6">
        <label>Other Phone Numbers (comma separated)</label>
        <input type="text" name="TechxpertOtherPhonenumber" class="form-control" placeholder="90xxxxxxxx,91xxxxxxxxxxxx">
    </div>
</div>

      </div>

          <input type="hidden" id="project_ticket_number" name="project_ticket_number" 
                 value="<?php echo $_SESSION['TicketID'] ?? ''; ?>">

        </form>
      </div>

      <div class="modal-footer">
        <button class="btn btn-primary" id="saveProjectDetails" onclick="saveprojectdetails()">Save Project</button>
        <button class="btn btn-secondary" data-dismiss="modal">Cancel</button>
      </div>

    </div>
  </div>
</div>



