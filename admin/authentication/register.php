<!DOCTYPE html>

<html lang="en">
    <head>
        <meta charset="utf-8">
        <title>
            Create an Account - Novologic
        </title>
        <?php include('../includes/common_head_content.php'); ?>
    </head>
    <body>
        <div class="page-wrapper">
            <div class="page-inner bg-brand-gradient">
                <div class="page-content-wrapper bg-transparent m-0">
                    <div class="height-10 w-100 shadow-lg px-4 bg-brand-gradient">
                        <div class="d-flex align-items-center container p-0">
                            <div class="page-logo width-mobile-auto m-0 align-items-center justify-content-center p-0 bg-transparent bg-img-none shadow-0 height-9">
                                <a href="javascript:void(0)" class="page-logo-link press-scale-down d-flex align-items-center">
                                    <span class="page-logo-text mr-1"><img src="../img/Chola-Logo.png"></span>
                                </a>
                            </div>
                            <span class="text-black opacity-50 ml-auto mr-2 hidden-sm-down">
                                Already a member?
                            </span>
                            <a href="login" class="btn-link text-black ml-auto ml-sm-0">
                                Login
                            </a>
                        </div>
                    </div>
                    <div class="flex-1" style="background: url(../img/svg/pattern-1.svg) no-repeat center bottom fixed; background-size: cover;">
                        <div class="container py-4 py-lg-5 my-lg-5 px-4 px-sm-0">
                            <div class="row">
                                <div class="col-xl-12">
                                    <h2 class="fs-xxl fw-500 mt-4 text-white text-center">
                                        Register now, its free!
                                        <small class="h3 fw-300 mt-3 mb-5 text-white opacity-60 hidden-sm-down">
                                            Your registration is free for a limited time. Register on Novologic for easy management
                                            <br>It is ready to go wherever you go!
                                        </small>
                                    </h2>
                                </div>
                                <div class="col-xl-6 ml-auto mr-auto">
                                    <div class="card p-4 rounded-plus bg-faded">
                                        <form id="js-login">
                                            <div class="form-group row">
                                                <label class="col-xl-12 form-label" for="fname">First and Last name</label>
                                                <div class="col-6 pr-1">
                                                    <input type="text" id="fname" class="form-control" placeholder="First Name">
                                                </div>
                                                <div class="col-6 pl-1">
                                                    <input type="text" id="lname" class="form-control" placeholder="Last Name">  
                                                </div>
                                            </div>
                                            <div class="form-group row">
                                                <label class="col-xl-12 form-label" for="fname">Contact Details</label>
                                                <div class="col-6 pr-1">
                                                    <input type="text" id="phonenumber" class="form-control" placeholder="Contact Number">
                                                </div>
                                                <div class="col-6 pl-1">
                                                    <input type="text" id="email" class="form-control" placeholder="Email">  
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label class="form-label" for="emailverify">Username</label>
                                                <input type="username" id="username" class="form-control" placeholder="Username">
                                                <div class="help-block">This will be used to login into Novologic</div>
                                            </div>
                                            <div class="form-group">
                                                <label class="form-label" for="userpassword">Password: </label>
                                                <input type="password" id="userpassword" class="form-control" placeholder="minimm 8 characters">
                                                <div class="help-block">password must be 8-20 characters long, contain letters and numbers, and must not contain spaces, special characters, or emoji.</div>
                                            </div>
                                           
                                            <div class="form-group demo">
                                                <div class="custom-control custom-checkbox">
                                                    <input type="checkbox" class="custom-control-input" id="terms">
                                                    <label class="custom-control-label" for="terms">I agree to terms & conditions</label>
                                                </div>
                                              
                                            </div>
                                            <div class="row no-gutters">
                                                <div class="col-md-4 ml-auto text-right">
                                                    <a id="login-btn" class="btn btn-block btn-danger btn-lg mt-3" style="color:#fff;" onclick="RegisterUser();">Register</a>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="position-absolute pos-bottom pos-left pos-right p-3 text-center text-white">
                           2020 © Cholamandalam&nbsp;
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php include('../includes/common_scripts.php');?>
        <script>
            function RegisterUser()
            {
                var fname = document.getElementById("fname").value;
                var lname = document.getElementById("lname").value;
                var phonenumber = document.getElementById("phonenumber").value;
                var email = document.getElementById("email").value;
                var username = document.getElementById("username").value;
                var user_password =document.getElementById("userpassword").value;
                //var company_name =document.getElementById("company_name").value;
                //var enterprise_id =document.getElementById("enterprise_id").value;
                 
                if(fname == "")
                {
                    noboAlert("Please enter First Name !");
                    return false;
                }
                if(phonenumber == "")
                {
                    noboAlert("Please enter Contact Number !");
                    return false;
                }
                if(!validateEmail(email))
                {
                    noboAlert("Please enter the correct email !");
                    return false;
                }
                if(username == "" && username.length<=4)
                {
                    noboAlert("Please enter valid username !");
                    return false;
                }
                if(!validatePassword(user_password))
                {
                    return false;
                }
               
                if(document.getElementById("terms").checked == false)
                {
                    noboAlert("You must agree to terms and conditions");
                    return false;
                }
              
                document.getElementById("login-btn").innerHTML = "Registering..";
                $.post("auth_controller/register_action.php",
                {
                    fname: fname,
                    lname: lname,
                    phonenumber:phonenumber,
                    email:email,
                    username: username,
                    user_password: user_password
                   
                },
                function(data, status)
                {
                   var response = JSON.parse(data);
                   
                   if(response.error == false)
                   {
                        alert(response.message);
                        window.location.href = "login.php";
                   }
                   else
                   {
                        alert(response.message);
                        document.getElementById("login-btn").innerHTML = "Register";
                   }
                });

            }
                
            function SearchCompany()
            {
                $("#look_up_enterprise").modal('toggle');
            }
            function SearchCompanies()
            {
                var search_text = document.getElementById("search_company_text").value;
                if(search_text == "")
                {
                    noboAlert("Please Search with a value!");
                    return false;
                }
                document.getElementById("initial_search_info").style.display = "none";
                
                $.post("auth_controller/search_enterprises.php",
                {
                    search_text: search_text
                },
                function(data, status)
                {
                    document.getElementById("s_company_info").innerHTML = data;
                    // noboAlert(response.message);
                    // setTimeout(function(){ location.reload(); }, 3000);
                }); 
            }
            function SelectEnterprise(selected_company_name,enterprise_id)
            {
                document.getElementById("company_name").value = selected_company_name;
                document.getElementById("enterprise_id").value = enterprise_id;
                $("#look_up_enterprise").modal('toggle');
            }

        </script>
    </body>
</html>

<div class="modal fade default-example-modal-right-sm" id="look_up_enterprise" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-right modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title h4">Select Company</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true"><i class="fal fa-times"></i></span>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Search Company </label>
                    <div class="input-group input-group-sm bg-white shadow-inset-2">
                        <div class="input-group-prepend">
                            <span class="input-group-text bg-transparent border-right-0 py-1 px-3 text-success">
                                <i class="fal fa-search"></i>
                            </span>
                        </div>
                        <input type="text" class="form-control border-left-0 bg-transparent pl-0" id="search_company_text" placeholder="Search Company">
                        <div class="input-group-append">
                            <button class="btn btn-default waves-effect waves-themed" onclick="SearchCompanies();" type="button">Search</button>
                        </div>
                    </div>
                </div>  
                <div class="card" id="initial_search_info">
                    <div class="card-body">
                        <p>Search Company using Name. <br>If company is not registered in the system, kindly enter its name. 
                    </div>
                </div>
                <div class="custom-scroll h-100">
                    <div id="s_company_info">
                    
                        
                    </div>
                </div>
                                       
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary">Save changes</button>
            </div>
        </div>
    </div>
</div>


