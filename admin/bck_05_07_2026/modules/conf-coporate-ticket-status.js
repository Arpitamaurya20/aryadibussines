function addstatus() {
    $("#addstatus").modal();
}
$(document).ready(function() {
    $("#nav_configuration").addClass("active");
    $("#nav_configuration").addClass("open");
    $("#nav_corporate_ticket_status").addClass("active");
    $('#view-corporate-tickets-status').dataTable({
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
function DeleteCorporateStatus(deleteid) {

    //alert(deleteid);
    alertify.confirm('TechXpert ', 'Do you really want to delete Corporate Tickets Status', function() {
            $.post("action/delete_corporate_ticket_status.php", {
                    ID: deleteid
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
function AddBookingStatus() {
    document.getElementById("submit").innerHTML ="Submiting....";
    $.ajax({
        url: "./action/add_corporate_ticket_status.php",
        type: "POST",
        data: $("#add_corporate_ticket_status").serialize(),
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

function ExportCorporateTicketStatusData() {
    $.ajax({
        url: "action/export_corporate_booking_status.php",
        type: "POST",
        data: $("#import_form").serialize(),
        success: function (data) {
        window.location.href = "report.xls";
        },
    });
    return false;
}