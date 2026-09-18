<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <?php
        require_once('../controllers/common_controllers.php');
        require_once('../includes/common_head_content.php');
        require_once('../includes/autoloader.inc.php');
        setTimeZone();
    ?>

    <meta charset="utf-8">
    <title>Send Ticket OTP</title>

    <style>
        body {
            background: #f4f6f9;
            font-family: Arial, sans-serif;
        }

        .container-box {
            width: 450px;
            margin: 80px auto;
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 0 10px #00000020;
        }

        .btn-primary {
            width: 100%;
            padding: 10px;
        }

        #response_box {
            margin-top: 15px;
            padding: 10px;
            border-radius: 5px;
            display: none;
            font-weight: bold;
        }

        .success_box {
            background: #d4edda;
            color: #155724;
        }

        .error_box {
            background: #f8d7da;
            color: #721c24;
        }
    </style>
</head>

<body>

    <div class="container-box">
        <h3 class="text-center">Send OTP for Ticket</h3>
        <hr>

        <label>Enter Ticket ID (CS-R&M-102232)</label>
        <input type="text" id="TicketID" class="form-control" placeholder="Enter Ticket ID like 102232">

        <button class="btn btn-primary mt-3" onclick="SendOTP()">Send OTP</button>

        <div id="response_box"></div>
    </div>

    <script src="../js/jquery/jquery-3.5.1.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <script>
function SendOTP() {
    let TicketID = $("#TicketID").val().trim();

    if (TicketID == "") {
        ShowResponse("error", "Please enter Ticket ID");
        return;
    }

    $.ajax({
        url: "action/send_ticket_otp.php",
        type: "POST",
        data: { TicketID: TicketID },
        dataType: "json",
        success: function (res) {
            if (res.status == "success") {

                let msg = `
                    Ticket OTP: <b>${res.TicketOTP}</b><br>
                    Ticket Close OTP: <b>${res.TicketCloseOTP}</b>
                `;

                ShowResponse("success", msg);
            } 
            else {
                ShowResponse("error", res.message);
            }
        },
        error: function () {
            ShowResponse("error", "Something went wrong!");
        }
    });
}

function ShowResponse(type, msg) {
    let box = $("#response_box");
    box.removeClass("success_box error_box");

    if (type == "success") box.addClass("success_box");
    else box.addClass("error_box");

    box.html(msg).fadeIn();
}
</script>

   

</body>

</html>
