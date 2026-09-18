<?php
$roles_array = getEmployeeRolesArray($conn);
$divisions_array = getEmployeeDivisionArray($conn);
$employee_roles = getEmployeeRole($conn,$ID);
$employee_divisions = getEmployeeDivision($conn,$ID);
$employee_array = getEmployeeArray($conn);
$employee_role_string = "";
$employee_division_string = "";
$employee_roles_raw = array();
$employee_division_raw = array();
if(sizeof($employee_roles) > 0)
{
    $access_tab = true;
    foreach($employee_roles as $e_role)
    {
        if($employee_role_string != "")
            $employee_role_string =  $employee_role_string.", ".$e_role['Role'];
        else
            $employee_role_string = $e_role['Role'];
        array_push($employee_roles_raw,$e_role['Role']);
    }
}
else
{
    $employee_role_string = "Not Set";
}

if(sizeof($employee_divisions) > 0)
{
    $access_tab = true;
    foreach($employee_divisions as $e_division)
    {
        if($employee_division_string != "")
            $employee_division_string =  $employee_division_string.", ".$e_division['Division'];
        else
            $employee_division_string = $e_division['Division'];
        array_push($employee_division_raw,$e_division['Division']);
    }
}
else
{
    $employee_division_string = "Not Set";
}

$Supervisor = $employee_data['Supervisor'];
if($employee_data['Supervisor'] == "")
{
    $Supervisor = "Not Set";
}
?>
<div class="panel-container show">
    <div class="panel-content p-0">

        <table id="view-projects" class="table table-bordered table-hover table-striped w-100">
            <tbody>
                <tr>
                    <th>Role(s) </th>
                    <td>
                        <div class="form-group">
                            <p id="employee_roles"><?php echo $employee_role_string; ?></p>
                        </div>
                    </td>
                </tr>

                <tr>
                    <th>Supervisor</th>
                    <td>
                        <div class="form-group">
                            <p><?php echo $Supervisor; ?></p>
                        </div>
                    </td>
                </tr>

                <tr>
                    <th>Division(s)</th>
                    <td>
                        <div class="form-group">
                            <p id="employee_divisions"><?php echo $employee_division_string; ?></p>
                        </div>
                    </td>
                </tr>


            </tbody>

        </table>

        <div class="text-right">
            <a href="#" class="btn btn-info" style="margin-right:20px;"  onclick="openrolemodal()"
                >Edit</a>
        </div>

    </div>
</div>


<!-- edit modal  -->

<div class="modal fade bd-example-modal-lg" id="editrole" role="dialog"
    aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header pb-0 edit_header">
                <div class="tab_modal_heading">
                    <h2>Edit Your Role</h2>
                </div>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="role_supervisor_form">
                    <div id="wizard">
                        <section>
                            <div class="row">
                                <div class="col-md-12 col-12">
                                    <div class="form_div"> <label for="single-default">Supervisor
                                        </label>
                                        <select class="select2 form-control w-100" id="supervisor_dropdown" name="supervisor">
                                            <?php
                                            if($Supervisor == "Not Set")
                                            {
                                            ?>
                                                <option value="">Select One</option>
                                            <?php
                                            }
                                            foreach($employee_array as $employee)
                                            {
                                                if($ID == $employee['ID'])
                                                    continue;
                                                ?>
                                                    <option value="<?php echo $employee['ID'];?>"><?php echo $employee['Name'];?></option>
                                                <?php
                                            }
                                            ?>

                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6 col-12">
                                    <div class="form_div">

                                        <div class="form-group">
                                            <label class="form-label" for="multiple-basic">
                                                Select Your Role
                                            </label>
                                            <?php
                                            $disabled = "";
                                            if($employee_role_string == "Vendor")
                                            {
                                                $disabled = "disabled";
                                            }

                                            ?>
                                            <select class="select2 form-control form_input" multiple="multiple" id="role_dropdown" name="roles[]" <?php echo $disabled; ?>>

                                                <?php
                                                foreach($roles_array as $role)
                                                {
                                                    $selected = "";
                                                    if(in_array($role['Role'],$employee_roles_raw))
                                                        $selected = "selected = 'selected'";
                                                    ?>
                                                    <option value="<?php echo $role['Role']; ?>" <?php echo $selected; ?>>
                                                        <?php echo $role['Role']; ?></option>
                                                <?php
                                                }
                                                ?>
                                            </select>

                                            <?php
                                            if($disabled == "disabled")
                                            {
                                                ?>
                                                <input type="hidden" name="roles[]" value="<?php echo $employee_role_string; ?>" />
                                                <?php
                                            }
                                            ?>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6 col-12">
                                    <div class="form_div">

                                        <div class="form-group">
                                            <label class="form-label" for="multiple-basic">
                                                Select Your Division
                                            </label>
                                            <select class="select2 form-control form_input" multiple="multiple" id="division_dropdown" name="divisions[]">

                                                <?php
                                                foreach($divisions_array as $division)
                                                {
                                                    $selected = "";
                                                    if(in_array($division['Division'],$employee_division_raw))
                                                        $selected = "selected = 'selected'";
                                                    ?>
                                                    <option value="<?php echo $division['Division']; ?>" <?php echo $selected; ?>>
                                                        <?php echo $division['Division']; ?></option>
                                                <?php
                                                }
                                                ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                
                                <input type="hidden" name="EmployeeID" value="<?php echo $ID; ?>" />
                                <input type="hidden" name="EmployeeCurrentSupervisor" value="<?php echo $employee_data['Supervisor']; ?>" />
                            </div>


                        </section>



                        <section>
                            <div class="row justify-content-center mt-3">

                                <a class="form_btn form_submit pl-4 pr-4 pt-2 pb-2 text-white cursor-pointer" onclick="ChangeRole_Supervisor()">Save</a>

                            </div>
                        </section>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- edit modal  -->