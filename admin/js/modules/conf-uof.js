
$(document).ready(function() {
    $("#nav_configuration").addClass("active");
    $("#nav_configuration").addClass("open");
    $("#nav_manage_uof").addClass("active");
});

function adduof() {
    $("#adduof").modal();
}
$(document).ready(function() {

    $('#view-uof').dataTable({
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

function DeleteUOF(deleteid) {

    //alert(deleteid);
    alertify.confirm('TechXpert ', 'Do you really want to delete UOF', function() {
            $.post("action/delete_uof.php", {
                    deleteid: deleteid
                },
                function(data, status) {
                    // alert(data);
                    // alert(status);
                    status = status.trim()
                    if (status == 'success') {
                        alertify.alert('TechXpert ', "UOF has been Deleted");
                        setTimeout(function() {
                            location.href = "view-uof.php";
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

function add_uof() {

    $.ajax({
        url: "./action/add_uof.php",
        type: "POST",
        data: $("#add_uof").serialize(),
        success: function(data) {
            var response = JSON.parse(data);
            alertify.alert(response.message);
            setTimeout(function() {
                location.href = "view-uof.php";
            }, 2000);
            return false;
        },
    });

    return false;
}
