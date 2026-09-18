<?php
require_once('../include/autoloader.inc.php');
$conf = new Conf();
$_ProductName = $conf->_ProductName;
$_ProductLogo = $conf->_ProductLogo;
$email='';
if(isset($_GET['email']))
{
   $email=$_GET['email']; 
}
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
    <title><?= $_ProductName ?> Portal | Set Password</title>
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
                             <input class="input100 border-start-0 form-control ms-0" type="hidden"
                                    name="email" id="email" value="<?php echo $email;?>" readonly>
                            <div class="text-center login_logo">
                                <a href="index.html"><img src="../project-assets/images/prodlogoLtest.png"
                                        class="header-brand-img" alt="" style="width:50px; margin-bottom:20px;"></a>
                            </div>
                            <span class="login100-form-title pb-5">
                                Set Your Password
                            </span>
                            <p class="text-muted">Enter the password must be 8 to 16 character</p>
                            <div class="wrap-input100 validate-input input-group pt-2" id="Password-toggle">
                                <a href="javascript:void(0)" class="input-group-text bg-white text-muted">
                                    <i class="zmdi zmdi-eye text-muted" aria-hidden="true"></i>
                                </a>
                                <input class="input100 border-start-0 form-control ms-0" type="password"
                                    placeholder="Enter Password" name="password" id="password">
                            </div>
                            <div class="wrap-input100 validate-input input-group pt-3" id="Password-toggle1">
                                <a href="javascript:void(0)" class="input-group-text bg-white text-muted">
                                    <i class="zmdi zmdi-eye text-muted" aria-hidden="true"></i>
                                </a>
                                <input class="input100 border-start-0 form-control ms-0" type="password"
                                    placeholder="Confirm Password" name="confirmpassword" id="confirmpassword">
                            </div>
                            <div class="submit pt-2">
                                <a class="btn btn-primary d-grid" onclick="SetPassword()" >Submit</a>
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
    <script src="../theme-assets/js/show-password.min.js"></script>
    <script src="../project-assets/js/set-password.js"></script>
</body>

</html>