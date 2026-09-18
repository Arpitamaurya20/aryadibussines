
$(document).ready(function() {
    $("#nav_configuration").addClass("active");
    $("#nav_configuration").addClass("open");
    $("#nav_manage_uom").addClass("active");
});

function adduom() {
    $("#adduom").modal();
}
$(document).ready(function() {

    $('#view-uom').dataTable({
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

function DeleteUOM(deleteid) {

    //alert(deleteid);
    alertify.confirm('TechXpert ', 'Do you really want to delete UOM', function() {
            $.post("action/delete_uom.php", {
                    deleteid: deleteid
                },
                 function(data, status) {
                        // alert(data);
                        // alert(status);
                        status = status.trim()
                        if (status == 'success') {
                            alertify.alert('TechXpert ', "UOM has been Deleted");
                            setTimeout(function() {
                                location.href = "view-uom.php";
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

function add_uom() {

    $.ajax({
        url: "./action/add_uom.php",
        type: "POST",
        data: $("#add_uom").serialize(),
        success: function(data) {
            var response = JSON.parse(data);
            alertify.alert("TechXpert", response.message);
            setTimeout(function() {
                location.href = "view-uom.php";
            }, 2000);
            return false;
        },
    });

    return false;
}

function ExportUOMData() {
        $.ajax({
            url: "action/export_uom.php",
            type: "POST",
            data: $("#import_form").serialize(),
            success: function (data) {
            window.location.href = "report.xls";
            },
        });
        return false;
    }
