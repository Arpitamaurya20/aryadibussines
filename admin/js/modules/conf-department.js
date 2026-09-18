$(document).ready(function() 
    {
        $("#nav_configuration").addClass("active");
        $("#nav_configuration").addClass("open");
        $("#nav_department").addClass("active");
        $('#view-departments').dataTable({
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
    function opendepartment_modal() {
        $("#add_update_department_form")[0].reset();
        $("#form_action").val("add");
        $("#department_head").select2();
        $("#add_edit_department_modal").modal();
    }
    function UpdateDepartment_modal(department_id)
    {
        $.post("action/get_department_details.php", {
            ID: department_id
        },
        function(data, status) {
            var response = JSON.parse(data);
            if(response.error == false)
            {
                var department_name = response.data.DepartmentName;
                var department_head = response.data.DepartmentHead;
                $("#department_name").val(department_name);
                $("#department_head").val(department_head);
                $("#form_action").val("Update");
                $("#form_id").val(department_id);
            }
        });
        $("#add_edit_department_modal").modal();
    }
    function DeleteDepartment(department_id) {
        alertify.confirm('TechXpert ', 'Do you really want to delete Department', function() {
                $.post("action/delete_department.php", {
                        ID: department_id
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
    function AddUpdateDepartment() {
        document.getElementById("submit").innerHTML ="Submiting....";
        $.ajax({
            url: "action/add_update_department.php",
            type: "POST",
            data: $("#add_update_department_form").serialize(),
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

    function ExportDepartmentData() {
        $.ajax({
            url: "action/export_department.php",
            type: "POST",
            data: $("#import_form").serialize(),
            success: function (data) {
            window.location.href = "report.xls";
            },
        });
        return false;
    }