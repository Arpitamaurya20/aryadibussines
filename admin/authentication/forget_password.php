<?php session_start(); ?>

<!DOCTYPE html>

<html lang="en">
     <head>
        <title>
            Novologic Forget Password
        </title>
        <?php 
        include('../includes/common_head_content.php'); 
        ?>
    </head>

    <body>
        <div class="page-wrapper">
            <div class="page-inner bg-brand-gradient">
                <div class="page-content-wrapper bg-transparent m-0">
                    <div class="height-10 w-100 shadow-lg px-4 bg-brand-gradient">
                        <div class="d-flex align-items-center container p-0">
                                                 
                                 <div
                                    class="d-flex width-mobile-auto m-0 align-items-center justify-content-center p-0 bg-transparent bg-img-none shadow-0 height-9">
                                    <a href="javascript:void(0)"
                                        class="page-logo-link press-scale-down d-flex align-items-center">
                                        <span class="page-logo-text mr-1 mt-1" id="logoImg"><img width="px"
                                                src="../img/tech-logo.jpg"></span>
                                    </a>
                                </div>
  
                            
                            <span class="text-white opacity-50 ml-auto mr-2 hidden-sm-down">
                                Already a member?
                            </span>
                            <a href="login.php" class="btn-link text-white ml-auto ml-sm-0">
                                Secure Login
                            </a>
                        </div>
                    </div>
                    <div class="flex-1">
                        <div class="container py-4 py-lg-5 my-lg-5 px-4 px-sm-0">
                            <div class="row">
                                <div class="col-xl-12">
                                    <h2 class="fs-xxl fw-500 mt-4 text-white text-center">
                                       Forgot your Password ?
                                       
                                    </h2>
                                </div>
                                    <div class="card p-4 rounded-plus bg-faded">
                                        <form class="card mt-4" id="sendmail" onsubmit="return false;">
                                            <div class="card-body">
                                              <div class="form-group">
                                                <label for="email-for-pass">Enter your Username or Email Address</label>
                                                <input class="form-control" type="text" id="email" name="user_id" required="" ><small class="form-text text-muted">Enter the email address you used during the registration on BBBootstrap.com. Then we'll email a link to this address.</small>
                                              </div>
                                            </div>
                                            <div class="card-footer">
                                              <button class="btn btn-success" id="reset_password" onclick="SendMail()" type="submit">Forget Password</button>
                                              <a href="login.php" class="btn btn-danger" type="submit">Back to Login </a>
                                            </div>
                                        </form>
                                    </div>
                                <!-- </div> -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

<script>
    function SendMail() {
       $("#reset_password").html("Please Wait..");
        $.ajax({
            url: "reset_password_mail.php",
            type: "POST",
            data: $("#sendmail").serialize(),
            success: function (data) {
                var response = JSON.parse(data);
                alert(response.message);
                if (response.error == false) {
                    setInterval(function () {
                        location.reload();
                    }, 2000);
                }
            },
        });
}
</script>
 <script src="https://code.jquery.com/jquery-3.6.4.min.js" integrity="sha256-oP6HI9z1XaZNBrJURtCoUT5SUnxFr8s3BzRl+cbzUq8=" crossorigin="anonymous"></script>
    </body>
</html>

