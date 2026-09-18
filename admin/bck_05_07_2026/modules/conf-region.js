$(document).ready(function()
    {
        $("#nav_configuration").addClass("active");
        $("#nav_configuration").addClass("open");
        $("#nav_region").addClass("active");
        $('#view-regions').dataTable({
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
    function openregion_modal() {
        $("#region_modal_title").html("Add Region");
        $("#add_update_region_form")[0].reset();
        $("#form_action").val("add");
        $("#region_head").select2();
        $("#region_corporate").select2();
        $("#add_edit_region_modal").modal();
    }
    function UpdateRegion_modal(region_id)
    {
        $("#region_modal_title").html("Update Region");
        $.post("action/get_region_details.php", {
            ID: region_id
        },
        function(data, status) {
            var response = JSON.parse(data);
            if(response.error == false)
            {
                var region_name = response.data.RegionName;
                var region_head = response.data.RegionHead;
                var region_corporate = response.data.RegionCorporateHead;
                $("#region_name").val(region_name);
                $("#region_head").val(region_head);
                $("#region_corporate").val(region_corporate);
                $("#form_action").val("Update");
                $("#form_id").val(region_id);
                $("#region_head").select2();
                $("#region_corporate").select2();
                $("#add_edit_region_modal").modal();
            }
        });
        
    }
    function DeleteRegion(region_id) {
        alertify.confirm('TechXpert ', 'Do you really want to delete Region', function() {
                $.post("action/delete_region.php", {
                        ID: region_id
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
    function AddUpdateRegion() {
        var region_name = $("#region_name").val();
        if(region_name == "")
        {
            TechXAlert("Region Name can't be blank");
            return false;
        }
        document.getElementById("submit").innerHTML ="Submiting....";
        $.ajax({
            url: "action/add_update_region.php",
            type: "POST",
            data: $("#add_update_region_form").serialize(),
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

    function ExportRegionData() {
        $.ajax({
            url: "action/export_region.php",
            type: "POST",
            data: $("#import_form").serialize(),
            success: function (data) {
            window.location.href = "report.xls";
            },
        });
        return false;
    }