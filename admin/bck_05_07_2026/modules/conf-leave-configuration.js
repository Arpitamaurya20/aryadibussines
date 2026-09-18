$(document).ready(function() 
    {
        $("#nav_configuration").addClass("active");
        $("#nav_configuration").addClass("open");
        $("#nav_leave_configuration").addClass("active");
        $('#view-leave-configuration').dataTable({
            responsive: true
        });

        $('.js-thead-colors a').on('click', function() {
            var theadColor = $(this).attr("data-bg");
            console.log(theadColor);
            $('#dt-basic-example thead').removeClassPrefix('bg-').addClass(theadColor);
        });

        $('.js-tbody-colors a').on('click', function() {
            var theadColor = $(this).attr("data-bg");
            console.log(theadColor);
            $('#dt-basic-example').removeClassPrefix('bg-').addClass(theadColor);
        });

    });
function OpenLeave_modal() {
    $("#add_update_leave_form")[0].reset();
    $("#form_action").val("add");

    $("#add_edit_leave_modal").modal();
    $("#type_of_leave").select2();

}
function UpdateLeave_modal(leave_id)
{
    $.post("action/get_leave_details.php", {
        ID: leave_id
    },
    function(data, status) {
        var response = JSON.parse(data);
        if(response.error == false)
        {
            var TypeOfLeave = response.data.TypeOfLeave;
            var NumberOfLeave = response.data.NumberOfLeave;
            $("#type_of_leave").val(TypeOfLeave);
            $("#number_of_leave").val(NumberOfLeave);
            $("#form_action").val("Update");
            $("#form_id").val(leave_id);
            $("#type_of_leave").select2();

        }
    });
    $("#add_edit_leave_modal").modal();
}

function DeleteLeave(leave_id) {
    alertify.confirm('TechXpert ', 'Do you really want to delete Leave', function() {
            $.post("action/delete_leave.php", {
                    ID: leave_id
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

function AddUpdateLeave() {
    var TypeOfLeave = document.getElementById("type_of_leave").value;
    var NumberofLeave = document.getElementById("number_of_leave").value;
    if (TypeOfLeave == -1) {
      TechXAlert("Type Of Leave cannot be blank");
      return false;
    }
    if (NumberofLeave == "") {
        TechXAlert("Number Of Leave cannot be blank");
        return false;
    }
    document.getElementById("submit").innerHTML ="Submiting....";
    $.ajax({
        url: "action/add_update_leave.php",
        type: "POST",
        data: $("#add_update_leave_form").serialize(),
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

function ExportLeaveData() {
        $.ajax({
            url: "action/export_leave_data.php",
            type: "POST",
            data: $("#import_form").serialize(),
            success: function (data) {
            window.location.href = "report.xls";
            },
        });
        return false;
}