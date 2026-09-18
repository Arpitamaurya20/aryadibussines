<?php
// $ID is from parent page view_employee.php referring to Employee ID
$emp_access_details = getAccessDetails($conn,$ID);
$access = "";
if($emp_access_details == null)
{
    $username = "Not Set";
    $password = "Not Set";
    $access_button_text = "Set Access";
    $access = "Not Set";
}
else
{
    $username = $emp_access_details['UserName'];
    $access_button_text = "Edit Username";
    $access = "Set";
}
?>
<div class="panel-container show">
    <div class="panel-content p-0">
        <?php
        if($access_tab == false)
        {
            ?>
        <p class="text-center"><strong>Kindly set Employee Role to Assign Password</strong></p>
        <?php
        }
        else
        {
        ?>
        <table class="table table-bordered table-hover table-striped w-100">
            <thead>
                <tr>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <th>User Name </th>
                    <td><?php echo $username; ?></td>
                </tr>

                <!--  <tr>
                    <th>Password</th>
                    <td><?php echo $password; ?></td>
                </tr> -->
                <?php
                if($password != "Not Set")
                {
                ?>
                <tr>
                    <th>Change Password</th>
                    <td>
                        <a href="#" data-toggle="modal" data-target="#resetpassword">
                            <span class="badge badge-primary">Reset
                                Password</span>
                        </a>
                    </td>
                </tr>
                <?php
                }
                ?>

            </tbody>

        </table>
        <div class="text-right">
            <a href="#" class="btn btn-info" style="margin-right:20px;" data-toggle="modal"
                data-target="#editaccess"><?php echo $access_button_text;?></a>
        </div>
        <?php
        }
        ?>

    </div>
</div>


<!-- edit modal  -->

<div class="modal fade bd-example-modal-lg" id="editaccess" tabindex="-1" role="dialog"
    aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header pb-0 edit_header">
                <div class="tab_modal_heading">
                    <h2>Edit Your Access</h2>
                </div>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="access_form">
                    <div id="wizard">
                        <section>
                            <div class="row">
                                <div class="col-md-6 col-12">
                                    <div class="form_div">
                                        <label for="username">Username</label>
                                        <?php
                                        if($username == "Not Set")
                                        {
                                        ?>

                                        <input type="text" id="username" name="username" class="form_input"
                                            placeholder="Username">
                                        <?php
                                        }
                                        else
                                        {
                                        ?>
                                        <input type="text" id="username" name="username" class="form_input"
                                            value="<?php echo $username;?>">
                                        <?php
                                        }
                                        ?>
                                    </div>
                                </div>
                                <?php
                                if($password == "Not Set")
                                {
                                ?>
                                <div class="col-md-6 col-12">
                                    <div class="form_div"> <label for="password">Password
                                        </label>
                                        <input type="text" id="password" name="password" class="form_input"
                                            placeholder="*********">
                                    </div>
                                </div>
                                <?php
                                }
                                ?>
                                <input type="hidden" name="old_username" id="old_username"
                                    value="<?php echo $username; ?>" />
                                <input type="hidden" name="access" id="access_input" value="<?php echo $access; ?>" />
                                <input type="hidden" name="EmployeeID" value="<?php echo $ID; ?>" />
                            </div>
                            </categ>
                            <section>
                                <div class="row justify-content-center mt-3">
                                    <a class="form_btn form_submit pl-4 pr-4 pt-2 pb-2 text-white cursor-pointer"
                                        value="Save" onclick="ChangePassword()">Save</a>
                                </div>
                            </section>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- edit modal  -->


<!-- Reset Modal -->

<div class="modal fade" id="resetpassword" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
    aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header pb-0 edit_header">
            <div class="tab_modal_heading">
                    <h2>Reset Your Password</h2>
                </div>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="reset_password">
                    <form id="reset_password_form">
                        <div class="row">
                            <div class="col-lg-12">
                                <div class="form-group">
                                    <label class="form-label" for="password">Password</label>
                                    <input type="text" name="reset_passsword" id="reset_passsword" class="form-control" placeholder="Enter Password">
                                </div>
                            </div>

                            <div class="col-lg-12">
                                <div class="form-group">
                                    <label class="form-label" for="re-password">Re-type Password</label>
                                    <input type="text" name="reset_confirm_passsword" id="reset_confirm_passsword" class="form-control" placeholder="Re-type Password" required>
                                </div>
                            </div>
                            <input type="hidden" name="reset_password_username" value="<?php echo $username;?>">
                        </div>

                        <div class="row justify-content-center mt-3">

                            <a class="form_btn form_submit pl-4 pr-4 pt-2 pb-2 text-white" onclick="ResetPassword()">Save</a>

                        </div>

                    </form>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- Reset Modal -->