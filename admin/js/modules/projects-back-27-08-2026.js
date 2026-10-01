function OpenProjectModal(action) 
{
  if(action == "add")
  {
    $("#project_name").val("");
    $("#project_start_date").val("");
    $("#project_end_date").val("");
    $("#project_manager").select2();
    $('#project_manager').val("-1").trigger('change');
    $("#form_id").val(-1);
    $("#project_status").val('Active');
  }
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
  $("#form_action").val(action);
  if(action == "add")
  {
    document.getElementById("add_ticket_number").style.display = "";
    document.getElementById("edit_ticket_number").style.display = "none";
    document.getElementById("project_modal_btn_delete").style.display = "none";
  }
  else
  {
    document.getElementById("add_ticket_number").style.display = "none";
    document.getElementById("edit_ticket_number").style.display = "";
    document.getElementById("project_modal_btn_delete").style.display = "";
  }
  $("#add_project_modal").modal();
}
function EditProject(ID)
{
  $("#form_id").val(ID);
  $.post("ajax/get_project_details.php",
  {
      ProjectID: ID
  },
  function(data, status)
  {
      var project_details = JSON.parse(data);
      $("#project_name").val(project_details.ProjectName);
      $("#project_start_date").val(project_details.StartDate);
      $("#project_end_date").val(project_details.EndDate);
      $('#project_manager').select2();
      $('#project_manager').val(project_details.ProjectManager).trigger('change');
      document.getElementById("ticket_reference_number_view").innerHTML = project_details.TicketNumber;
      $("#project_status").val(project_details.Status);
  });
  OpenProjectModal("edit");
}
function ShowTicketChangeBox()
{
    document.getElementById("add_ticket_number").style.display = "";
    document.getElementById("edit_ticket_number").style.display = "none";
}
function DeleteProject()
{
  var ProjectID = $("#form_id").val();
  alertify.confirm('TechXpert ', 'Do you really want to delete Project?', function () {
        $.post("action/delete_project.php", {
            ProjectID: ProjectID
        },
        function (data, status) {
            var response = JSON.parse(data);
            TechXAlert(response.message);
            $('#view-projects').DataTable().ajax.reload();
            $('#add_project_modal').modal('hide');
        });
    },
    function () {
        alertify.error('Deletion Cancelled')
    });
  return false;
}
function SaveProject()
{
  var project_name = document.getElementById("project_name").value;
  if (project_name == "") {
      TechXAlert("Please Enter Project Name");
      return false;
  }

  var project_start_date = document.getElementById("project_start_date").value;
  if (project_start_date == "") {
      TechXAlert("Please Enter Project Start Date");
      return false;
  }
  var project_end_date = document.getElementById("project_end_date").value;
  if (project_end_date == "") {
      TechXAlert("Please Enter Project End Date");
      return false;
  }
  var project_end_date = document.getElementById("project_end_date").value;
  if (project_end_date == "") {
      TechXAlert("Please Enter Project End Date");
      return false;
  }
  var startDate = new Date(project_start_date);
  var endDate = new Date(project_end_date);
  if (startDate >= endDate) {
    TechXAlert("End Date must be greater than Start Date");
    return false;
  }
  var project_manager = document.getElementById("project_manager").value;
  if (project_manager == -1) {
    TechXAlert("Please Select Project Manager");
    return false;
  }
 
  
  let myForm = document.getElementById("add_update_project_form");
  var formData = new FormData(myForm);
  document.getElementById("project_modal_btn").innerHTML ="Submiting....";
  $.ajax({
    url: "action/add_update_project.php",
    type: "POST",
    data: formData,
    success: function (data) {
      var response = JSON.parse(data);
      TechXAlert(response.message);
      if (response.error == false) {
        $('#view-projects').DataTable().ajax.reload();
        $('#add_project_modal').modal('hide');
        document.getElementById("project_modal_btn").innerHTML ="Save";
      }
      else
      {
        document.getElementById("project_modal_btn").innerHTML ="Save";
      }
    },
      cache: false,
      contentType: false,
      processData: false,
  });
  return false;
}

function AddTask(ProjectID)
{
  $(".task_start_date").datepicker({
    format: "yyyy-mm-dd",
    todayBtn: "linked",
    clearBtn: true,
    todayHighlight: true,
    autoclose: true,
  });
  $(".task_end_date").datepicker({
    format: "yyyy-mm-dd",
    todayBtn: "linked",
    clearBtn: true,
    todayHighlight: true,
    autoclose: true,
  });
  $("#project_id").val(ProjectID);
  $("#add_task_modal").modal();
}
let rowCounter = 1; // Initialize a counter for the rows
function AddMoreTask()
{
    var current_counter = $("#task_counter").val();
    next_counter = parseInt(current_counter)+1;
    $.post("ajax/task_form.php",
    {
        counter: next_counter,
    },
    function(data, status)
    {
        $("#task_div_ui").append(data);
        $(".task_start_date").datepicker({
          format: "yyyy-mm-dd",
          todayBtn: "linked",
          clearBtn: true,
          todayHighlight: true,
          autoclose: true,
        });
        $(".task_end_date").datepicker({
          format: "yyyy-mm-dd",
          todayBtn: "linked",
          clearBtn: true,
          todayHighlight: true,
          autoclose: true,
        });
    });
    $("#task_counter").val(next_counter);
}
function DeleteTask(counter)
{
  var div_id = "task_row_"+counter;
  $("#"+div_id).remove();
}
function SaveTask()
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
      TechXAlert(response.message);
      if (response.error == false) {
        $('#view-projects').DataTable().ajax.reload();
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

function ViewTasks(ProjectID)
{
  $.post(
    "../controllers/setSession.php",
    {
      ProjectID: ProjectID,
    },
    function (data, status) {
      BasicURLRouter("view-project-tasks");
    }
  );
}

function EditTaskDates(TaskID,TaskStatus)
{
  $("#task_id").val(TaskID);
  $("#project_task_status").val(TaskStatus);
  $("#task_status_modal").modal();
  $.post("ajax/generate_task_dates_status.php",
    {
      TaskID: TaskID,
    },
    function (data, status) 
    {
      document.getElementById("task_div_ui").innerHTML = data;
      
    }
  );
}

function SaveTaskProgress()
{
  let myForm = document.getElementById("task_daily_progress_form");
  var formData = new FormData(myForm);
  $.ajax({
    url: "action/update_daily_task_progress.php",
    type: "POST",
    data: formData,
    success: function (data) {
      var response = JSON.parse(data);
      TechXAlert(response.message);
      if (response.error == false) 
      {
        $('#view-project-tasks').DataTable().ajax.reload();
        $('#task_status_modal').modal('hide');
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
}

function DeleteProjectTask()
{
  var TaskID = $("#task_id").val();
  alertify.confirm('TechXpert ', 'Do you really want to delete Task?', function () {
        $.post("action/delete_project_task.php", {
            TaskID: TaskID
        },
        function (data, status) {
            var response = JSON.parse(data);
            TechXAlert(response.message);
            $('#view-project-tasks').DataTable().ajax.reload();
            $('#task_status_modal').modal('hide');
        });
    },
    function () {
        alertify.error('Deletion Cancelled')
    });
  return false;
}

function ExportProjectsData()
{
    alert("Coming Soon!");
}


function SaveTaskProgressDPR()
{
  let myForm = document.getElementById("task_daily_progress_form");
  var formData = new FormData(myForm);
  $.ajax({
    url: "action/update_daily_task_progress.php",
    type: "POST",
    data: formData,
    success: function (data) {
      var response = JSON.parse(data);
      TechXAlert(response.message);
      if (response.error == false) 
      {
        $('#view-project-tasks').DataTable().ajax.reload();
        $('#task_status_modal').modal('hide');
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
}


function DownloadDprReport(TicketID) {
  // Use form submission for automatic download (works better than AJAX for file downloads)
  var form = $('<form>', {
    'method': 'POST',
    'action': './action/generate-dpr-report-pro.php',
    'target': '_blank'
  });
  
  form.append($('<input>', {
    'type': 'hidden',
    'name': 'TicketID',
    'value': TicketID
  }));
  
  form.append($('<input>', {
    'type': 'hidden',
    'name': 'Action',
    'value': 'Download'
  }));
  
  $('body').append(form);
  form.submit();
  form.remove();
}


function SendDprReport(TicketID) {
  // Use form submission for automatic download (works better than AJAX for file downloads)
  var form = $('<form>', {
    'method': 'POST',
    'action': './action/generate-dpr-report-pro.php',
    'target': '_blank'
  });
  
  form.append($('<input>', {
    'type': 'hidden',
    'name': 'TicketID',
    'value': TicketID
  }));
  
  form.append($('<input>', {
    'type': 'hidden',
    'name': 'Action',
    'value': 'Send'
  }));
  
  $('body').append(form);
  form.submit();
  form.remove();
}