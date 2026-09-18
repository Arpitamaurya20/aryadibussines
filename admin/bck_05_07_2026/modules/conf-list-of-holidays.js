$(document).ready(function() 
    {
        $("#nav_configuration").addClass("active");
        $("#nav_configuration").addClass("open");
        $("#nav_list_of_holidays").addClass("active");
        $('#view-list-of-holidays').dataTable({
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
    function openholidays_modal() {
        $("#add_update_holidays_form")[0].reset();
        $("#form_action").val("add");
    
        $("#add_edit_holidays_modal").modal();
        $("#holidays_date").datepicker({
            format: "yyyy-mm-dd",
            todayBtn: "linked",
            clearBtn: true,
            todayHighlight: true,
            autoclose: true,
          });

        $("#region_name").select2();
       

    }
    function UpdateHolidays_modal(holidays_id)
    {
        $("#holidays_date").datepicker({
            format: "yyyy-mm-dd",
            todayBtn: "linked",
            clearBtn: true,
            todayHighlight: true,
            autoclose: true,
          });
        $.post("action/get_holidays_details.php", {
            ID: holidays_id
        },
        function(data, status) {
            var response = JSON.parse(data);
            if(response.error == false)
            {
                var region_name = response.data.RegionName;
                var holidays_name = response.data.HolidaysName;
                var holidays_date = response.data.HolidaysDate;
                $("#region_name").val(region_name);
                $("#holidays_name").val(holidays_name);
                $("#holidays_date").val(holidays_date);
                $("#form_action").val("Update");
                $("#form_id").val(holidays_id);
                $("#region_name").select2();
            }
        });
        $("#add_edit_holidays_modal").modal();
    }
    function DeleteHolidays(holidays_id) {
        alertify.confirm('TechXpert ', 'Do you really want to delete Holiday', function() {
                $.post("action/delete_holidays.php", {
                        ID: holidays_id
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
    function AddUpdateHolidays() {

        var Holidaysregion = document.getElementById("region_name").value;
        var Holidaysname = document.getElementById("holidays_name").value;
        var Holidaysdate = document.getElementById("holidays_date").value;

        if (Holidaysregion == -1) {
          TechXAlert("Region cannot be blank");
          return false;
        }
        if (Holidaysname == "") {
            TechXAlert("Name cannot be blank");
            return false;
        }
        if (Holidaysdate == "") {

            TechXAlert("Date cannot be blank");
            return false;
        }
        document.getElementById("submit").innerHTML ="Submiting....";
        $.ajax({
            url: "action/add_update_holidays.php",
            type: "POST",
            data: $("#add_update_holidays_form").serialize(),
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

    function ExportHolidaysData() {
        $.ajax({
            url: "action/export_holidays.php",
            type: "POST",
            data: $("#import_form").serialize(),
            success: function (data) {
            window.location.href = "report.xls";
            },
        });
        return false;
    }