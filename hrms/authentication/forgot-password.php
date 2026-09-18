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
    <title><?= $_ProductName ?> Portal | Forget Password</title>
    <?php
        include("../include/common-head.php");
    ?>
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

                <div class="container-login100 w-100">
                    <div class="wrap-login100 p-6">
                        <form class="login100-form validate-form">
                        <div class="text-center login_logo">
                                <a href="index.html"><img src="../project-assets/images/prodlogoLtest.png"
                                        class="header-brand-img" alt="" style="width:50px; margin-bottom:20px;"></a>
                            </div>
                            <span class="login100-form-title pb-5">
                                Forgot Password
                            </span>
                            <p class="text-muted">Enter the email address registered on your account</p>
                            <div class="wrap-input100 validate-input input-group" data-bs-validate="Valid email is required: ex@abc.xyz">
                                <a href="javascript:void(0)" class="input-group-text bg-white text-muted">
                                    <i class="zmdi zmdi-email" aria-hidden="true"></i>
                                </a>
                                <input class="input100 border-start-0 ms-0 form-control" type="email" id="email" placeholder="Email">
                            </div>
                            <div class="submit">
                                <a class="btn btn-primary d-grid" onclick="ForgetPassword()" >Submit</a>
                            </div>
                            <div class="text-center mt-4">
                                <p class="text-dark mb-0 d-inline-flex">Forgot It ?<a class="text-primary ms-1" href="login">Send me Back</a></p>
                            </div>
                        </form>
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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/1.4.0/jquery.min.js"
        integrity="sha512-e4WJV+b4BBNgVWODO1v6KU6xbZRf/9acSAWSc9B+q/OiSXt6Q3MPhxPOntoGGfA+zQPbnYjhXwWIuX4PPZCtzw=="
        crossorigin="anonymous" referrerpolicy="no-referrer"></script>
        <script src="../project-assets/js/forget-password.js"></script>
</body>

</html>