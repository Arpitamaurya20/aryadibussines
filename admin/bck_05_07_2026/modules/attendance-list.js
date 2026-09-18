
function GenerateTodayAttendanceStats()
{
    $.ajax({
        url: "action/today_attendance_stats.php",
        type: "POST",
        data: 
            {
            },
        success: function (data) 
        {
           document.getElementById("today_attendance_stats").innerHTML = data;
        },
    });
}

function RefreshAttendance()
{
    var table = $('#view-attendance-records').DataTable();
    table.destroy();
    var param = "";
    var EmployeeID = document.getElementById("employee_name").value;
    var filter_date = document.getElementById("filter_date").value;
    param = "?filter_date=" + filter_date+"&EmployeeID="+EmployeeID;
    $('#view-attendance-records').dataTable({
            responsive: true,
            'processing': true,
            'serverSide': true,
            'ordering': false,
            'serverMethod': 'post',
            'ajax': {
                'url': 'action/view-attendance-sheet-post.php'+param
            },
            'columnDefs': [{
                "targets": [0],
                "className": "text-center"
            }],
            
            'columns': [
                {
                    data: 'EmployeeName'
                },
                {
                    data: 'RecordDate'
                },
                {
                    data: 'CheckInTime'
                },
                {
                    data: 'CheckOutTime'
                },
                {
                    data: 'Duration'
                },
                {
                    data: 'State'
                }
            ]
            });
           
    }
    function DeleteAttendance(id) {
        alertify.confirm('TechXpert ', 'Do you really want to delete Attendance', function() {
                $.post("action/delete_attendance.php", {
                        ID: id
                    },
                    function(data, status) {
                        var response = JSON.parse(data);
                        TechXAlert(response.message);
                        if (response.error == false)
                        {
                          setInterval(function(){
                            location.reload();
                          }, 2000);
                        }
                    });
            },
            function() {
                alertify.error('Deletion Cancelled')
            });
    }
    function AddAttendance() {

        document.getElementById("submit").innerHTML ="Submiting....";
        $.ajax({
            url: "action/add_attendance.php",
            type: "POST",
            data: $("#add_attendance_form").serialize(),
            success: function(data) {
                var response = JSON.parse(data);
                TechXAlert(response.message);
                if (response.error == false)
                {
                  setInterval(function(){
                    location.reload();
                  }, 2000);
                }
            },

        });
        return false;
    }

    function UpdateAttendance(ID,Hours) {
        // console.log(Hours);
    alertify.confirm(
    "TechXpert Are you sure you want to complete today work",
    function (e) {
        $.post(
          "action/update_attendance.php",
          {
            ID: ID,
            Hours: Hours,
          },
          function (data, status) {
            var response = JSON.parse(data);
            TechXAlert(response.message);
            if (response.error == false)
              setInterval(function () {
                location.reload();
              }, 2000);
          }
        );
      }
    );
    return false;
}

function ExportAttendanceData() 
{
    var EmployeeID = document.getElementById("employee_name").value;
    var filter_date = document.getElementById("filter_date").value;
    $.ajax({
        url: "action/export_attendance.php",
        type: "POST",
        data: 
            {
                EmployeeID:EmployeeID,
                filter_date:filter_date
            },
        success: function (data) {
        window.location.href = "report_attendance.xls";
        },
    });
    return false;
}


function ViewAttendanceList(EmployeeID) {
  $.post(
    "../controllers/setSession.php",
    {
      EmployeeID: EmployeeID,
    },
    function (data, status) {
      BasicURLRouter("view_employee_total_attendance.php");
    }
  );
}

function ViewEmployeeDetail(EmployeeID) {
  $.post(
    "../controllers/setSession.php",
    {
      EmployeeID: EmployeeID,
    },
    function (data, status) {
      BasicURLRouter("../employees/view_employee.php");
    }
  );
}