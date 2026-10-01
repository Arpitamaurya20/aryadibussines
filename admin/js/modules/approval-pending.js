
$(document).ready(function() {
    $("#nav_ticket_approval").addClass("active");
});

function addcategories() {
    $("#addcategories").modal();
}

function ShowApprovalPendingAlert(message) {
    if (typeof TechXAlert === "function") {
        TechXAlert(message);
    } else if (typeof alertify !== "undefined") {
        alertify.alert('TechXpert ', message);
    }
}

$(document).ready(function() {

    $('#view-approval-pending').dataTable({
        responsive: true
    });
    $('#view-quote-approval-pending').dataTable({
        responsive: true
    });

    $('a[data-toggle="tab"]').on('shown.bs.tab', function() {
        $.fn.dataTable.tables({
            visible: true,
            api: true
        }).columns.adjust().responsive.recalc();
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

function SendTicketApprovalMail(ticketId, buttonEl) {
    if (buttonEl) {
        buttonEl.disabled = true;
        buttonEl.innerHTML = "Sending...";
    }

    $.post("../corporate-tickets/action/send-ticket-approval-mail.php", {
        TicketID: ticketId
    }, function(data) {
        var response = {};
        try {
            response = typeof data === "object" ? data : JSON.parse(data);
        } catch (e) {
            response = {
                error: true,
                message: "Unexpected response while sending approval mail."
            };
        }

        if (buttonEl) {
            buttonEl.disabled = false;
            buttonEl.innerHTML = '<i class="fal fa-paper-plane mr-1"></i> Send Mail';
        }

        ShowApprovalPendingAlert(response.message || "Unable to send approval mail.");
        if (response.error === false) {
            setTimeout(function() {
                location.reload();
            }, 1200);
        }
    }).fail(function() {
        if (buttonEl) {
            buttonEl.disabled = false;
            buttonEl.innerHTML = '<i class="fal fa-paper-plane mr-1"></i> Send Mail';
        }
        ShowApprovalPendingAlert("Unable to send approval mail. Please try again.");
    });
}

function ChangeApproval(ticketid) {

    // alert(ticketid);
    alertify.confirm('TechXpert ', 'Do you really want to Approve Ticket', function() {
            $.post("action/approve_action.php", {
                    TicketID: ticketid
                },
                function(data) {
                    var response = {};
                    try {
                        response = typeof data === "object" ? data : JSON.parse(data);
                    } catch (e) {
                        response = {
                            error: true,
                            message: "Unexpected approval response."
                        };
                    }

                    if (response.error === false) {
                        ShowApprovalPendingAlert(response.message || "Ticket has been Approved");
                        setTimeout(function() {
                            location.href = "view-approval-pending-tickets";
                        }, 1000);
                    } else {
                        ShowApprovalPendingAlert(response.message || "Unable to approve ticket.");
                    }
                });

        },
        function() {
            alertify.error('Approval Cancelled')
        });
}

function RejectTicket(ticketid) {

    alertify.confirm('TechXpert ', 'Do you really want to Cancel Ticket', function() {
            $.post("action/rejected_action.php", {
                    TicketID: ticketid
                },
                function(data) {
                    var response = {};
                    try {
                        response = typeof data === "object" ? data : JSON.parse(data);
                    } catch (e) {
                        response = {
                            error: true,
                            message: "Unexpected cancel response."
                        };
                    }

                    if (response.error === false) {
                        ShowApprovalPendingAlert(response.message || "Ticket has been Cancelled");
                        setTimeout(function() {
                            location.href = "view-approval-pending-tickets";
                        }, 1000);
                    } else {
                        ShowApprovalPendingAlert(response.message || "Unable to cancel ticket.");
                    }
                });

        },
        function() {
            alertify.error('Cancel action cancelled')
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

function OpenTicketDetails(ticketID) {
    $.post("../controllers/setSession.php", {
        TicketID: ticketID
    }, function() {
        window.location.href = "../corporate-tickets/view-corporate-tickets-details.php";
    });
}

function OpenQuoteDetails(ticketID) {
    $.post("../controllers/setSession.php", {
        TicketID: ticketID
    }, function() {
        window.location.href = "../corporate-tickets/view-corporate-tickets-details.php?open_tab=quotation";
    });
}


