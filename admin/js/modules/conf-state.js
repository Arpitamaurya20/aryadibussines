    function open_state_modal() {
        $("#state_modal_title").html("Add State");
        $("#add_update_state_form")[0].reset();
        $("#form_action").val("add");
        $("#state_head").select2();
        $("#state_corporate").select2();
        $("#region").select2();
        $("#add_edit_state_modal").modal();
    }
    function UpdateState_modal(state_id)
    {
        $("#state_modal_title").html("Update State");
        $.post("action/get_state_details.php", {
            ID: state_id
        },
        function(data, status) {
            var response = JSON.parse(data);
            if(response.error == false)
            {
                var state_name = response.data.StateName;
                var state_head = response.data.StateHead;
                var state_corporate_head = response.data.StateCorporateHead;
                var region = response.data.RegionID;
                $("#state_name").val(state_name);
                $("#state_head").val(state_head);
                $("#state_corporate").val(state_corporate_head);
                $("#region").val(region);
                $("#form_action").val("Update");
                $("#form_id").val(state_id);
                $("#state_head").select2();
                $("#state_corporate").select2();
                $("#region").select2();
                $("#add_edit_state_modal").modal();
            }
        });
    }

    $(document).ready(function() {

        $("#nav_configuration").addClass("active");
        $("#nav_configuration").addClass("open");
        $("#nav_state").addClass("active");
        // $('#view-states').dataTable({
        //     responsive: true
        // });

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
    function DeleteState(deleteid) {

        //alert(deleteid);
        alertify.confirm('TechXpert ', 'Do you really want to delete State', function() {
                $.post("action/delete_state.php", {
                        ID: deleteid
                    },
                    function(data, status) {
                        // alert(data);
                        // alert(status);
                        status = status.trim()
                        if (status == 'success') {
                            alertify.alert('TechXpert ', "State has been Deleted");
                            setTimeout(function() {
                                location.href = "view-state.php";
                            }, 2000);
                            /*window.location.assign("user_dashboard.php");*/
                        } else {
                            alertify.alert(data);
                        }
                    });

            },
            function() {
                alertify.error('Deletion Cancelled')
            });
    }
    function AddUpdateState()
    {
        var state_name = $("#state_name").val();
        if(state_name == "")
        {
            TechXAlert("State Name can't be blank");
            return false;
        }

        var region = $("#region").val();
        if(region == "-1")
        {
            TechXAlert("Please Select Region");
            return false;
        }
        document.getElementById("submit").innerHTML ="Submiting....";
        $.ajax({
            url: "action/add_update_state.php",
            type: "POST",
            data: $("#add_update_state_form").serialize(),
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

    function ExportStateData() {
        $.ajax({
            url: "action/export_state.php",
            type: "POST",
            data: $("#import_form").serialize(),
            success: function (data) {
            window.location.href = "report.xls";
            },
        });
        return false;
    }