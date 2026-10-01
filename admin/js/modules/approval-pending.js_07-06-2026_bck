
$(document).ready(function() {
    $("#nav_ticket_approval").addClass("active");
});

function addcategories() {
    $("#addcategories").modal();
}
$(document).ready(function() {

    $('#view-approval-pending').dataTable({
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

function ChangeApproval(ticketid) {

    // alert(ticketid);
    alertify.confirm('TechXpert ', 'Do you really want to Approve Ticket', function() {
            $.post("action/approve_action.php", {
                    TicketID: ticketid
                },
                function(data, status) {
                    // alert(data);
                    // alert(status);
                    status = status.trim()
                    if (status == 'success') {
                        alertify.alert('TechXpert ', "Ticket has been Approved");
                        setTimeout(function() {
                            location.href = "view-approval-pending-tickets";
                        }, 1000);
                        /*window.location.assign("user_dashboard.php");*/
                    } else {
                        alertify.alert(data);
                    }
                });

        },
        function() {
            alertify.error('Approval Cancelled')
        });
}

function RejectTicket(ticketid) {

    alert(ticketid);
    alertify.confirm('TechXpert ', 'Do you really want to Reject Ticket', function() {
            $.post("action/rejected_action.php", {
                    TicketID: ticketid
                },
                function(data, status) {
                    // alert(data);
                    // alert(status);
                    status = status.trim()
                    if (status == 'success') {
                        alertify.alert('TechXpert ', "Ticket has been Rejected");
                        setTimeout(function() {
                            location.href = "view-approval-pending-tickets";
                        }, 1000);
                        /*window.location.assign("user_dashboard.php");*/
                    } else {
                        alertify.alert(data);
                    }
                });

        },
        function() {
            alertify.error('Reject Cancelled')
        });
}

function View_ticket_details(id){
    $.post("action/get_ticket_details.php", {
        ID: id
    },
        function (data, status) {
            var response = JSON.parse(data);
            if (response.error == false) {
                var message = response.data.Message;
                var service_type = response.data.Type;

                $("#message").val(message);
                $("#service_type").val(service_type);
                $("#viewticketdetails").modal();
                

            }
        });
}


