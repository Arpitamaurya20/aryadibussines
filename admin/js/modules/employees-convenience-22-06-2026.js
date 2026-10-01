$(document).ready(function() 
{
    $("#_Nav_Employee_Convenience").addClass("active");
    $("#_Nav_Employee_Convenience").addClass("open");
    $("#_Nav_Employee_Convenience").addClass("active");

    $('.js-thead-colors a').on('click', function() {
        var theadColor = $(this).attr("data-bg");
        console.log(theadColor);
        $('#view-employees-conveniencee thead').removeClassPrefix('bg-').addClass(theadColor);
    });

    $('.js-tbody-colors a').on('click', function() {
        var theadColor = $(this).attr("data-bg");
        console.log(theadColor);
        $('#view-employees-convenience').removeClassPrefix('bg-').addClass(theadColor);
    });

});


function ApproveConveyanceRecord(ID)
{
    $("#convenience_charge_ID").val(ID);
    $("#approve_conveyance_modal").modal();
}

function EmployeeConvenienceAction(ID,Status)
{
    $("#convenience_charge_ID").val(ID);
    $("#convenience_current_status").val(Status);
    $("#approve_conveyance_modal").modal();
}
function approve_conveyance_Action(action)
{
    var convenience_current_status = parseInt(document.getElementById("convenience_current_status").value);
    if(action == 1)
        convenience_next_status = (convenience_current_status+1)*action;
    if(action == -1)
         convenience_next_status = (convenience_current_status)*action;
    document.getElementById("convenience_next_status").value = convenience_next_status;
    let myForm = document.getElementById("convenience_approve_form");
    var formData = new FormData(myForm);

    $.ajax({
        url: "action/approve_action.php",
        type: "POST",
        data: formData,
        success: function (data) {
          var response = JSON.parse(data);
          TechXAlert(response.message);
          if(response.error == false)
            setInterval(function() {
                location.reload();
            }, 2000);
        },
            cache: false,
            contentType: false,
            processData: false,
      });
    return false;
}
function FilterEmployeeConvenienceData()
{
    var table = $('#view-employees-convenience').DataTable();
    table.destroy();
    var param = "";
    var employee_filter = document.getElementById("employee_filter").value;
    var c_status = document.getElementById("c_status").value;
    var filter_date = document.getElementById("filter_date").value;
    var Supervisor_EmployeeID = document.getElementById("Supervisor_EmployeeID").value;
    param = "?filter_date=" + filter_date+"&EmployeeID="+employee_filter+"&status="+c_status+"&Supervisor_EmployeeID="+Supervisor_EmployeeID;
    $('#view-employees-convenience').dataTable({
            responsive: true,
            'processing': true,
            'serverSide': true,
            'ordering': false,
            'serverMethod': 'post',
            'ajax': {
                'url': 'ajax/view-employees-convenience-post.php'+param
            },
            'columnDefs': [{
                "targets": [0],
                "className": "text-center"
            }],
            
            'columns': [{
                    "data": "id",
                    render: function(data, type, row, meta) {
                        return meta.row + meta.settings._iDisplayStart + 1;
                    }
                },
                {
                    data: 'Employee'
                },
                {
                    data: 'Journey'
                },
                {
                    data: 'Amount'
                },
                {
                    data: 'Remarks'
                },
                {
                    data: 'RecordDate'
                },
                {
                    data: 'Status'
                },
                {
                    data: 'Action'
                }
            ]


        });
}

function ExportEmployeeConvenienceData() {
  document.getElementById("filter_date_export").value = document.getElementById("filter_date").value;
  document.getElementById("employee_filter_export").value = document.getElementById("employee_filter").value;
  document.getElementById("status_export").value = document.getElementById("c_status").value;
  $.ajax({
      url: "action/export_employee_convenience_data.php",
      type: "POST",
      data: $("#export_form").serialize(),
      success: function (data) {
         window.location.href = "report.xls";
      },
  });
  return false;
}
  

  



