
$(document).ready(function() {
    $("#nav_configuration").addClass("active");
    $("#nav_configuration").addClass("open");
    $("#nav_manage_equipment").addClass("active");
});

function addequipment() {
    $("#addequipment").modal();
}
$(document).ready(function() {

    $('#view-equipment').dataTable({
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

function DeleteEquipment(deleteid) {

    //alert(deleteid);
    alertify.confirm('TechXpert ', 'Do you really want to delete Equipment', function() {
            $.post("action/delete_equipment.php", {
                    deleteid: deleteid
                },
                function(data, status) {
                    // alert(data);
                    // alert(status);
                    status = status.trim()
                    if (status == 'success') {
                        alertify.alert('TechXpert ', "Equipment has been Deleted");
                        setTimeout(function() {
                            location.href = "view-equipment.php";
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

function add_equipment() {

    $.ajax({
        url: "./action/add_equipment.php",
        type: "POST",
        data: $("#add_equipment").serialize(),
        success: function(data) {
            var response = JSON.parse(data);
            alertify.alert(response.message);
            setTimeout(function() {
                location.href = "view-equipment.php";
            }, 2000);
            return false;
        },
    });

    return false;
}
