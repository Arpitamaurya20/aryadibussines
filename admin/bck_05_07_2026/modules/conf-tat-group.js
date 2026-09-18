
$(document).ready(function() {
    $("#nav_configuration").addClass("active");
    $("#nav_configuration").addClass("open");
    $("#nav_manage_tat_group").addClass("active");
});

function AddTATGroup() 
{
    $("#form_action").val("add");
    $("#tat_group_name").val("");
    $("#tat_group_default_days").val("");
    $("#tat_group_default_hours").val("");
    $("#add_tat_group_modal_heading").html("Add TAT Group Details");
    $("#add_tat_group_modal").modal("show");
}

function DeleteTATGroup(deleteid) 
{

    //alert(deleteid);
    alertify.confirm('TechXpert ', 'Do you really want to delete TAT Group', function() {
            $.post("action/delete_tat_group.php", {
                    deleteid: deleteid
                },
                 function(data, status) {
                        var response = JSON.parse(data);
                        if (response.error == false) {
                            alertify.alert('TechXpert ', "TAT Group has been Deleted");
                            setTimeout(function() {
                                location.href = "view-tat-groups";
                            }, 2000);
                            /*window.location.assign("user_dashboard.php");*/
                        } else {
                            alertify.alert(response.message);
                        }
                    });
        },
        function() {
            alertify.error('Deletion Cancelled')
        });
}

function EditTATGroup(TATGroupID)
{
  $.post("action/get-tat-group-details.php",
  {
    TATGroupID: TATGroupID
  },
  function (data, status) 
  {
        var response = JSON.parse(data);
        $("#tat_group_name").val(response.Name);
        $("#tat_group_default_days").val(response.DefaultTATDays);
        $("#tat_group_default_hours").val(response.DefaultTATHours);
        $("#form_id").val(TATGroupID);
        $("#form_action").val("update");
        $("#add_tat_group_modal").modal("show");
        $("#add_tat_group_modal_heading").html("Update TAT Group Details");
  });
}

function update_tat_group() 
{
    if($("#tat_group_name").val() == "")
    {
        TechXAlert("Please provide TAT Group Name");
        return false;
    }
    if($("#tat_group_default_days").val() == "")
    {
        TechXAlert("Please provide TAT Default Days, in case the TAT is just dependent on hours, mention 0 on TAT Days");
        return false;
    }
    if($("#tat_group_default_hours").val() == "")
    {
        TechXAlert("Please provide TAT Default Hours, in case the TAT is just dependent on days, mention 0 on TAT Hours");
        return false;
    }
    $.ajax({
        url: "./action/add_update_tat_group.php",
        type: "POST",
        data: $("#add_tat_group_form").serialize(),
        success: function(data) {
            var response = JSON.parse(data);
            alertify.alert("TechXpert", response.message);
            setTimeout(function() {
                location.href = "view-tat-groups";
            }, 2000);
            return false;
        },
    });

    return false;
}

