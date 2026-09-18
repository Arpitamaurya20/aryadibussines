<?php
require_once('../include/autoloader.inc.php');
$conf = new Conf();
$_ProductName = $conf->_ProductName;
$_ProductLogo = $conf->_ProductLogo;
?>
<!doctype html>
<html lang="en" dir="ltr">

<head>
    <!-- META DATA -->
    <meta charset="UTF-8">
    <meta name='viewport' content='width=device-width, initial-scale=1.0, user-scalable=0'>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="description" content="">
    <meta name="author" content="">
    <meta name="keywords" content="">
    <title><?= $_ProductName ?> Portal Login</title>
    <?php
    include("../include/common-head.php");
    $dbh = new Dbh();
    $conn = $dbh->_connectodb();
    $authentication = new Authentication($conn);

    $session = new Session($conn);
    // $UserType = $session->SessionCheck_redirect();
    
    if ($UserType !== false) {
        if ($UserType == "System Admin") {
            header("Location:../dashboard/admin-dashboard");
        }
        if ($UserType == "Counsellor") {
            header("Location:../dashboard/admin-dashboard");
        }
        if ($UserType == "Content Team") {
            header("Location:../dashboard/admin-dashboard");
        }
        if ($UserType == "Book Dispatch") {
            header("Location:../dashboard/admin-dashboard");
        }
        if ($UserType == "Academic Head") {
            header("Location:../dashboard/admin-dashboard");
        }

        if ($UserType == "Acountant") {
            header("Location:../dashboard/admin-dashboard");
        }
        if ($UserType == "Admission") {
            header("Location:../dashboard/admin-dashboard");
        }
        if ($UserType == "Manager") 
        {
             header("Location:../dashboard/admin-dashboard");
        }
        if ($UserType == "Marketing") {
            header("Location:../dashboard/admin-dashboard");
        }
        if ($UserType == "Center Manager") {
            header("Location:../dashboard/admin-dashboard");
        }
        if ($UserType == "Center Manager") {
            header("Location:../dashboard/admin-dashboard");
        }
        if ($UserType == "Lead Counsellor") {
            header("Location:../dashboard/admin-dashboard");
        }
        if ($UserType == "Batch Manager") {
            header("Location:../dashboard/admin-dashboard");
        }
        if ($UserType == "Telecaller") {
            header("Location:../dashboard/admin-dashboard");
        }
        /* else
         {
             header("Location:../dashboard/admin_dashboard.php");
         } */
    }
    ?>

    <style>
        font,
        br {
            display: none;
        }
    </style>

</head>
<script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-6496901533964255"
     crossorigin="anonymous"></script>

<body class="app sidebar-mini ltr login-img">

    <!-- BACKGROUND-IMAGE -->
    <div class="">

        <div id="global-loader">
            <div class="d-flex align-items-center w-100 h-100">
                <div class="spinner2">
                    <div class="cube1"></div>
                    <div class="cube2"></div>
                </div>
            </div>

        </div>

        <!-- PAGE -->
        <div class="page">
            <div class="container">
                <!-- Theme-Layout -->

                <div class="row">
                    <div class="col-lg-4 col-md-6 col-12 bg-white p-6">
                        <form class="login100-form validate-form" id="form-login">
                            <div class="text-center login_logo">
                                <a href="index.html"><img src="<?= $_ProductLogo ?>" class="header-brand-img" alt=""
                                        style="width:200px; margin-bottom:20px;"></a>
                            </div>
                            <span class="login100-form-title pb-5">
                                Login
                            </span>
                            <div class="wrap-input100 validate-input input-group pt-3">
                                <a href="javascript:void(0)" class="input-group-text bg-white text-muted">
                                    <i class="zmdi zmdi-email text-muted" aria-hidden="true"></i>
                                </a>
                                <input class="input100 border-start-0 form-control ms-0" type="Text"
                                    placeholder="Enter Email" name="email" id="email">
                            </div>
                            <div class="wrap-input100 validate-input input-group pt-5" id="Password-toggle">
                                <a href="javascript:void(0)" class="input-group-text bg-white text-muted" onclick="passwordtoggle()">
                                    <i class="zmdi zmdi-eye text-muted" aria-hidden="true"></i>
                                </a>
                                <input class="input100 border-start-0 form-control ms-0" type="password"
                                    placeholder="Enter Password" name="password" id="password">
                            </div>
                             <div class="text-end pt-4">
                                <p class="mb-0"><a href="forgot-password" class="text-primary ms-1">Forgot
                                        Password?</a></p>
                            </div>
                            <div class="container-login100-form-btn">
                                <a onclick="login();" id="login_btn"
                                    class="login100-form-btn btn-primary cursor-pointer">
                                    Login
                                </a>
                            </div>

                        </form>
                    </div>
                    <div class="col-lg-8 col-md-6 col-12 p-6" style="background:#1d2372;">
                        <div class="d-flex align-items-center">
                            <img src="../project-assets/images/login-3.png" class="w-100" alt="">

                        </div>

                    </div>
                </div>
                <!-- CONTAINER CLOSED -->
            </div>
        </div>
        <!-- End PAGE -->

    </div>
    <!-- BACKGROUND-IMAGE CLOSED -->

    <?php
    include("../include/common-script.php");
    ?>
    <script src="../project-assets/js/login.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/1.4.0/jquery.min.js"
        integrity="sha512-e4WJV+b4BBNgVWODO1v6KU6xbZRf/9acSAWSc9B+q/OiSXt6Q3MPhxPOntoGGfA+zQPbnYjhXwWIuX4PPZCtzw=="
        crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <!-- SHOW PASSWORD JS -->
    <script src="../theme-assets/js/show-password.min.js"></script>
    <script>
        var Submit_input = document.getElementById("email");

        Submit_input.addEventListener("keypress", function (event) {

            if (event.key === "Enter") {

                event.preventDefault();

                document.getElementById("login_btn").click();

            }

        });

        var Submit_inputpassword = document.getElementById("password");

        Submit_inputpassword.addEventListener("keypress", function (event) {

            if (event.key === "Enter") {

                event.preventDefault();

                document.getElementById("login_btn").click();

            }

        });



        var Submit_btn = document.getElementById("login_btn");

        Submit_btn.addEventListener("keypress", function (event) {

            if (event.key === "Enter") {

                event.preventDefault();

                document.getElementById("login_btn").click();

            }

        });
    </script>

     <script>
    function passwordtoggle() { 
        
        var passwordField = $("#password");
        var fieldType = passwordField.attr("type") === "password" ? "text" : "password";
        var newPasswordField = passwordField.clone().attr("type", fieldType);
        passwordField.replaceWith(newPasswordField);
        $("#Password-toggle i").toggleClass("zmdi-eye zmdi-eye-off");
    }
</script>
</body>

</html>