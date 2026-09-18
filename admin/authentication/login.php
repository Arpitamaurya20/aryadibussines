<!DOCTYPE html>
<html lang="en">

<head>

    <?php
    /*ini_set('log_errors', 1);
    ini_set('error_log', 'error_log.txt');
    error_reporting(E_ALL);*/

    include('../includes/common_head_content.php');
    include('../controllers/common_controllers.php');
    include('../includes/autoloader.inc.php');
    $session_state = SessionCheck();
    if ($session_state != "Error") {
        if ($session_state == "Corporate Admin") {
            header("Location:../dashboard/analytics_dashboard");
        } else if ($session_state == "Corporate Branch User") {
            header("Location:../dashboard/analytics_dashboard.php");
        } else {
            header("Location:../dashboard/analytics_dashboard.php");
        }
    }
    $host = $_SERVER['HTTP_HOST'];
    $hostParts = explode('.', $host);
    $product_configuration = array();
    $subdomain = "";
    $ProductName = "Aryadibusiness";
    if (count($hostParts) > 2) {
        // Get the first part (subdomain)
        $subdomain = $hostParts[0];
        $dbh = new Dbh();
        $conn = $dbh->_connectodb();
        $conf = new Config($conn);
        $product_configuration = $conf->GetConfigParametersfromURL($subdomain);
        $ProductName = $product_configuration['ProductName'];
    }
    $logoImg = "tech-logo.jpg";
    $logoPath = "../img/";
    $isPoonawalla = ($subdomain === "poonawalla" || strpos($host, 'poonawalla') !== false || strpos($host, 'ponawalla') !== false);
    if (isset($product_configuration['logo'])) {
        $logoImg = "innov_logo.png";
    }
    if (strpos($host, 'aryadibusiness.com') !== false) {
        $logoImg = "innov_logo.png";
    }
    if ($isPoonawalla) {
        $logoImg = "pfl-logo.svg";
        $logoPath = "../../images/";
        $ProductName = "Poonawalla Fincorp";
    }
    ?>
    <title>
        <?= $ProductName; ?> Login
    </title>
    <style type="text/css">
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');

        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8fafc;
            margin: 0;
            padding: 0;
        }

        .bg-saas-gradient {
            position: relative;
            overflow-x: hidden;
            min-height: 100vh;
        }

        .blue-bg-top {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 50vh;
            background-color: #010614ff;
            z-index: 0;
            overflow: hidden;
        }

        .blue-bg-top::before {
            content: '';
            position: absolute;
            top: -10%;
            left: 0;
            right: 0;
            bottom: 0;
            background-image: url('../img/aryadi.png');
            background-repeat: no-repeat;
            background-position: center;
            background-size: 60%;
            opacity: 0.25;
            pointer-events: none;
        }

        .blue-bg-top::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(29, 78, 216, 0.2);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            pointer-events: none;
        }

        .top-banner,
        .saas-navbar,
        .saas-login-container {
            position: relative;
            z-index: 10;
        }

        .saas-login-container {
            min-height: calc(100vh - 100px);
            display: flex;
            flex-direction: column;
            align-items: center;
            padding-top: 40px;
        }

        .breadcrumb-text {
            color: rgba(255, 255, 255, 0.7);
            font-size: 13px;
            margin-bottom: 8px;
            text-align: center;
        }

        .page-title {
            color: white;
            font-weight: 700;
            font-size: 36px;
            margin-bottom: 40px;
            text-align: center;
        }

        .login-card {
            background: #ffffff;
            border-radius: 20px;
            padding: 50px 40px;
            width: 100%;
            max-width: 480px;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.15);
            position: relative;
            z-index: 10;
            text-align: center;
        }

        .form-logo {
            max-width: 110px;
            height: auto;
            margin: 0 auto 15px auto;
            display: block;
        }

        .login-header {
            font-weight: 700;
            font-size: 26px;
            color: #111827;
            margin-bottom: 10px;
        }

        .login-subtitle {
            font-size: 14px;
            color: #6b7280;
            margin-bottom: 30px;
            line-height: 1.5;
        }

        .form-control-saas {
            border-radius: 10px;
            border: 1px solid #e5e7eb;
            padding: 14px 20px;
            font-size: 15px;
            height: auto;
            color: #374151;
            box-shadow: none !important;
        }

        .form-control-saas:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1) !important;
        }

        .password-wrapper {
            position: relative;
        }

        .password-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
            cursor: pointer;
            font-size: 18px;
            z-index: 10;
        }

        .forgot-link {
            font-size: 13px;
            color: #3b82f6;
            text-decoration: none;
            float: left;
            margin-top: 5px;
        }

        .forgot-link:hover {
            text-decoration: underline;
        }

        #login-btn {
            background: linear-gradient(135deg, #3f87f4ff 0%, #070b41ff 100%);
            border: none;
            color: white;
            font-weight: 600;
            border-radius: 10px;
            padding: 12px 24px;
            font-size: 16px;
            box-shadow: 0 10px 25px rgba(10, 13, 37, 0.3);
            transition: transform 0.2s, box-shadow 0.2s;
            width: 150px;
            display: block;
            margin: 0 auto;
            cursor: pointer;
        }

        #login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 30px rgba(10, 13, 37, 0.4);
            color: white;
        }

        .divider {
            display: flex;
            align-items: center;
            text-align: center;
            color: #9ca3af;
            font-size: 13px;
            margin: 30px 0 20px;
            clear: both;
            padding-top: 20px;
        }

        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            border-bottom: 1px solid #e5e7eb;
        }

        .divider:not(:empty)::before {
            margin-right: .5em;
        }

        .divider:not(:empty)::after {
            margin-left: .5em;
        }

        .social-btns {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-bottom: 30px;
        }

        .social-btn {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            border: 1px solid #e5e7eb;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            color: #374151;
            background: white;
            transition: all 0.2s;
            cursor: pointer;
        }

        .social-btn:hover {
            border-color: #3b82f6;
            background: #f8fafc;
        }

        .social-btn.google:hover {
            color: #ea4335;
        }

        .social-btn.linkedin:hover {
            color: #0a66c2;
        }

        .social-btn.facebook:hover {
            color: #1877f2;
        }

        .signup-text {
            font-size: 14px;
            color: #6b7280;
            margin-top: 30px;
        }

        .signup-link {
            color: #3b82f6;
            font-weight: 500;
            text-decoration: none;
        }

        .signup-link:hover {
            text-decoration: underline;
        }

        .footer-text {
            margin-top: 40px;
            padding-bottom: 20px;
            text-align: center;
            color: rgba(255, 255, 255, 0.6);
            font-size: 12px;
        }
    </style>
    <?php
    if (isset($product_configuration['favicon'])) {
    ?>
        <link rel="icon" type="image/png" sizes="32x32" href="../img/favicon/<?= $product_configuration['favicon']; ?>">
    <?php
    }
    if ($ProductName != "Aryadibusiness") {
        include("../css/client_generated_css.php");
    }
    ?>
</head>

<body class="bg-saas-gradient">
    <div class="blue-bg-top"></div>





    <div class="saas-login-container">


        <div class="page-title">Login</div>

        <div class="login-card">
            <img src="../img/aryadi.png" alt="Aryadibusiness Logo" class="form-logo">
            <div class="login-header">Sign in to Aryadibusiness</div>
            <div class="login-subtitle">Enter your credentials to access your account dashboard and manage your services.</div>

            <form id="js-login">
                <div class="form-group mb-4 text-left">
                    <input id="username" class="form-control form-control-saas" placeholder="Enter your email or phone number" value="">
                </div>

                <div class="form-group mb-4 text-left password-wrapper">
                    <input type="password" id="password" class="form-control form-control-saas" placeholder="Password" value="" required>
                    <i class="fal fa-eye-slash password-toggle" id="togglePassword"></i>
                </div>

                <div class="clearfix mb-4">
                    <a href="forget_password.php" class="forgot-link">Forgot Password?</a>
                </div>

                <div class="clearfix">
                    <a id="login-btn" class="btn" onclick="Login()">Sign in <i class="fal fa-arrow-right ml-1"></i></a>
                </div>
            </form>
        </div>

        <div class="footer-text">
            <?php
            if ($subdomain == "innov" || $host == "aryadibusiness.com" || $host == "www.aryadibusiness.com") {
                echo 'Copyright © 2024 Innovsource Services Pvt. Ltd. All Rights Reserved.';
            } else if ($isPoonawalla) {
                echo 'Copyright © 2024 Poonawalla Fincorp Limited. All Rights Reserved.';
            } else {
                echo 'Copyright © 2024 TECHXPERT Multiple Services LLP. All rights reserved.';
            }
            ?>
        </div>
    </div>

    <?php include('../includes/common_scripts.php'); ?>
    <script>
        // Password visibility toggle
        document.getElementById('togglePassword').addEventListener('click', function() {
            const passwordInput = document.getElementById('password');
            const icon = this;
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            } else {
                passwordInput.type = 'password';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            }
        });

        function Login() {
            var username = document.getElementById("username").value;
            var password = document.getElementById("password").value;
            if (username == "") {
                TechXAlert("Please enter User Name !");
                return false;
            }
            if (password == "") {
                TechXAlert("Please Password !");
                return false;
            }
            document.getElementById("login-btn").innerHTML = "Logging In..";
            $.post("auth_controller/action_login.php", {
                    username: username,
                    password: password
                },
                function(data, status) {
                    var response = JSON.parse(data);
                    if (response.error == false) {
                        if (response.UserType == "Corporate Admin") {
                            window.location = "../dashboard/analytics_dashboard";
                        } else if (response.UserType == "Corporate Branch User") {
                            window.location = "../dashboard/analytics_dashboard.php";
                        } else {
                            window.location = "../dashboard/analytics_dashboard";
                        }
                    } else {
                        TechXAlert(response.message);
                        document.getElementById("login-btn").innerHTML = "Sign in";
                    }
                });
        }
        // Get the input field
        var password_text_box = document.getElementById("password");
        // Execute a function when the user releases a key on the keyboard
        if (password_text_box) {
            password_text_box.addEventListener("keyup", function(event) {
                // Number 13 is the "Enter" key on the keyboard
                if (event.keyCode === 13) {
                    // Cancel the default action, if needed
                    event.preventDefault();
                    // Trigger the button element with a click
                    Login();
                }
            });
        }
    </script>
</body>

</html>