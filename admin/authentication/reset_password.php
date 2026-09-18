<!DOCTYPE html>

<html lang="en">

     <head>

        <title>

            Novologic Reset Password

        </title>

        <?php include('../includes/common_head_content.php'); ?>

    </head>

    <?php

        include('../controllers/common_controllers.php');

        include('auth_controller/authentication_controller.php');

    ?>

    <body>

        <div class="page-wrapper">

            <div class="page-inner bg-brand-gradient">

                <div class="page-content-wrapper bg-transparent m-0">

                    <div class="height-10 w-100 shadow-lg px-4 bg-brand-gradient">

                        <div class="d-flex align-items-center container p-0">

                           <div class="page-logo width-mobile-auto m-0 align-items-center justify-content-center p-0 bg-transparent bg-img-none shadow-0 height-9">
                                <a href="javascript:void(0)" class="page-logo-link press-scale-down d-flex align-items-center">
                                   <i class="fal fa-cube" style="color:white;font-size:21px;"></i>
                                    <span class="page-logo-text mr-1">Novologic</span>
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

                    <div class="flex-1" style="background: url(img/svg/pattern-1.svg) no-repeat center bottom fixed; background-size: cover;">

                        <div class="container py-4 py-lg-5 my-lg-5 px-4 px-sm-0">

                            <div class="row">

                                <?php

                                if(isset($_GET['token']))

                                {

                                    $token = $_GET['token'];

                                    $conn = _connectodb();

                                    setTimeZone();

                                    $token_information = getTokenInformation($token,$conn);

                                    if($token_information['error'] == false)

                                    {

                                ?>

                                        <div class="col-xl-12">

                                            <h2 class="fs-xxl fw-500 mt-4 text-white text-center">

                                              Reset Password

                                            </h2>

                                        </div>

                                        <div class="col-xl-6 ml-auto mr-auto">

                                            <div class="card p-4 rounded-plus bg-faded">

                                                <form id="js-reset" novalidate="">

                                                    <div class="form-group">

                                                        <label class="form-label" for="email">New Password</label>

                                                        <input type="password" id="password" class="form-control" placeholder="Password" required>

                                                            <div class="invalid-feedback">Please enter password.</div>   

                                                    </div>

                                                    <div class="form-group">

                                                        <label class="form-label" for="email">Confirm Password</label>

                                                        <input type="password" id="confirm_password" class="form-control" placeholder="Confirm Password" required>

                                                            <div class="invalid-feedback">Please enter confirm password.</div>   

                                                    </div>

                                                    <input type="hidden" id="email" value="<?php echo $token_information['Email']; ?>" />

                                                    <div class="row no-gutters">

                                                        <div class="col-md-4 ml-auto text-right">

                                                            <a id="js-reset-button" class="btn btn-danger text-white">Reset</a>

                                                        </div>

                                                    </div>

                                                </form>

                                            </div>

                                        </div>

                                <?php

                                    }

                                    else

                                    {

                                    ?>

                                        <div class="col-xl-12">

                                            <h2 class="fs-xxl fw-500 mt-4 text-white text-center">

                                              <?php echo $token_information['message']; ?>

                                            </h2>

                                         </div>

                                    <?php

                                    }

                                }

                                else

                                {

                                ?>

                                    <div class="col-xl-12">

                                        <h2 class="fs-xxl fw-500 mt-4 text-white text-center">

                                          Access Denied

                                        </h2>

                                    </div>

                                <?php

                                }

                                ?>



                            </div>

                        </div>

                        <div class="position-absolute pos-bottom pos-left pos-right p-3 text-center text-white">

                                2020 © Novologic by&nbsp;<a href='#' class='text-white opacity-40 fw-500' title='Stoneboy' target='_blank'>Stoneboy</a>

                        </div>

                    </div>

                </div>

            </div>

        </div>

        

        <?php include('../includes/common_scripts.php');?>

        <script>

            $("#js-reset-button").click(function(event)
            {
                var form = $("#js-reset")
                if (form[0].checkValidity() === false)
                {
                    event.preventDefault()
                    event.stopPropagation()
                     form.addClass('was-validated');
                    return false;
                }
                var email = document.getElementById("email").value;
                var password = document.getElementById("password").value;
                var confirm_password = document.getElementById("confirm_password").value;
                if(password == confirm_password)
                {

                   
                }
                else
                {
                    noboAlert("Password and Confirm Password doesn't match");
                    return false;
                }
                document.getElementById("js-reset-button").innerHTML = "Resetting";
                $.post("auth_controller/reset_password_action.php",
                {
                    email: email,
                    password: password
                },
                function(data, status)
                {
                   var response = JSON.parse(data);
                   if(response.error == false)
                   {

                        document.getElementById("js-login-btn").innerHTML = "Done";
                        alert(response.message);
                        window.location.href = "login.php";
                   }
                   else
                   {
                       noboAlert(response.message);
                       document.getElementById("js-login-btn").innerHTML = "Reset";

                   }

               });





                // Perform ajax submit here...

            });



        </script>

    </body>

</html>

