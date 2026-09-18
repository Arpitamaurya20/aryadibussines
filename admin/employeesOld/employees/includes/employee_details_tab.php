<div class="panel-container show">
    <div class="panel-content p-0">
        <!-- datatable start -->
        <table id="view-projects" class="table table-bordered table-hover table-striped w-100">
            <tbody>
                <tr>
                    <th>Date Of Joining </th>
                    <td>
                    <?php
                        if($employee_data['DateofJoining'] == "")
                            echo "Not Set";
                        else
                            echo $employee_data['DateofJoining']; ?>
                    </td>
                </tr>
                <tr>
                    <th>Profile Photo</th>
                    <td>
                        <?php
                        if($employee_data['ProfileImage'] == "")
                            echo "Not Set";
                        else
                        {
                            $Employee_ProfileImage = $employee_media.$employee_data['ProfileImage'];
                            ?>
                        <img src="<?php echo $Employee_ProfileImage; ?>" width="40px" />
                        <?php
                        }
                        ?>
                    </td>
                </tr>
                <tr>
                    <th>Name </th>
                    <td><?php echo $employee_data['Name']; ?>
                    </td>
                </tr>
                <tr>
                    <th>Designation</th>
                    <td><?php
                     if($employee_data['Designation'] == "")
                     echo "Not Set";
                 else
                     echo $employee_data['Designation'];
                     ?>
                    </td>
                </tr>
                <tr>
                    <th>Gender </th>
                    <td><?php
                     if($employee_data['Gender'] == "")
                     echo "Not Set";
                 else
                     echo $employee_data['Gender']; ?>
                    </td>
                </tr>
                <tr>
                    <th>ID </th>
                    <td><?php
                    if($employee_data['EmployeeNumber'] == "")
                    echo "Not Set";
                else
                     echo $employee_data['EmployeeNumber'];
                     ?>
                    </td>
                </tr>
                <tr>
                    <th>Department</th>
                    <td><?php
                    if($employee_data['Department'] == "")
                    echo "Not Set";
                else
                     echo $employee_data['Department'];
                     ?>
                    </td>
                </tr>

                <tr>
                    <th>Email</th>
                    <td><?php
                    if($employee_data['Email'] == "")
                    echo "Not Set";
                else
                     echo $employee_data['Email'];
                     ?>
                    </td>
                </tr>
                <tr>
                    <th>Contact Number</th>
                    <td><?php
                     if($employee_data['ContactNumber'] == "")
                     echo "Not Set";
                 else
                     echo $employee_data['ContactNumber'];
                     ?>
                    </td>
                </tr>
                <tr>
                    <th>UAN Number </th>
                    <td>
                        <?php
                        if($employee_data['UANNumber'] == "")
                            echo "Not Set";
                        else
                            echo $employee_data['UANNumber'];
                        ?>
                    </td>
                </tr>
        

                <tr>
                    <th>PAN</th>
                    <td><?php
                     if($employee_data['PAN'] == "")
                     echo "Not Set";
                 else
                     echo $employee_data['PAN'];
                     ?>
                    </td>
                </tr>
                <tr>
                    <th>PAN Photo</th>
                    <td>
                        <?php
                        if($employee_data['PANImage'] == "")
                            echo "Not Set";
                        else
                        {
                            $Employee_PANImage = $employee_media.$employee_data['PANImage'];
                            ?>
                        <img src="<?php echo $Employee_PANImage; ?>" width="40px" />
                        <?php
                        }
                        ?>
                    </td>
                </tr>
                <tr>
                    <th>Aadhar Card </th>
                    <td><?php
                    if($employee_data['Aadhar'] == "")
                     echo "Not Set";
                 else
                     echo $employee_data['Aadhar'];
                      ?>
                    </td>
                </tr>
                <tr>
                    <th>Aadhar Photo</th>
                    <td>
                        <?php
                        if($employee_data['AadharImage'] == "")
                            echo "Not Set";
                        else
                        {
                            $Employee_AadharImage = $employee_media.$employee_data['AadharImage'];
                            ?>
                        <img src="<?php echo $Employee_AadharImage; ?>" width="40px" />
                        <?php
                        }
                        ?>
                    </td>
                </tr>
                <tr>
                    <th>Police Verification Photo</th>
                    <td>
                        <?php
                        if($employee_data['PoliceVerificationImage'] == "")
                            echo "Not Set";
                        else
                        {
                            $Employee_PoliceVerificationImage = $employee_media.$employee_data['PoliceVerificationImage'];
                            ?>
                        <img src="<?php echo $Employee_PoliceVerificationImage; ?>" width="40px" />
                        <?php
                        }
                        ?>
                    </td>
                </tr>
                <tr>
                    <th>City  </th>
                    <td>
                        <?php
                        if($employee_data['City'] == "")
                            echo "Not Set";
                        else
                            echo $employee_data['City'];
                        ?>
                    </td>
                </tr>
                <tr>
                    <th>Bank Account Name</th>
                    <td><?php
                     if($employee_data['BankAccountName'] == "")
                     echo "Not Set";
                 else
                     echo $employee_data['BankAccountName'];
                     ?>
                    </td>
                </tr>
                <tr>
                    <th>Bank Account Number</th>
                    <td><?php
                     if($employee_data['BankAccountNumber'] == "")
                     echo "Not Set";
                 else
                     echo $employee_data['BankAccountNumber'];
                     ?>
                    </td>
                </tr>
            </tbody>
        </table>
        <div class="text-right">
            <a href="#" class="btn btn-info" style="margin-right:20px;" onclick="opendetailmodal()">Edit</a>
        </div>
    </div>
</div>
<!-- edit modal  -->
<div class="modal fade bd-example-modal-lg" id="editdetails" role="dialog"
    aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header pb-0 edit_header">
                <div class="tab_modal_heading">
                    <h2>Edit Your Details</h2>
                </div>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="update_employee_form">
                    <div id="wizard">
                        <section>
                            <div class="row">
                                <div class="col-md-6 col-12">
                                    <div class="form_div"> <label for="Gender">Gender
                                        </label>
                                        <select name="employee_gender" class="form_input" required>
                                            <?php
                                            $selected_his = "";
                                            $selected_her = "";
                                            if($employee_data['Gender'] == "His")
                                            {
                                                $selected_his = "selected";
                                            }
                                            if($employee_data['Gender'] == "Her")
                                            {
                                                $selected_her = "selected";
                                            }
                                            if($selected_his == "" && $selected_her == "")
                                            {
                                                ?>
                                                <option value="">Please Select</option>
                                                <?php
                                            }
                                            ?>
                                            <option value="His" <?php echo $selected_his; ?>>His</option>
                                            <option value="Her" <?php echo $selected_her; ?>>Her</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6 col-12">
                                    <div class="form_div"> <label for="phone">Employee ID </label>
                                        <input type="text" class="form_input"
                                            value="<?php echo $employee_data['EmployeeNumber']; ?>" name="employee_id">
                                    </div>
                                </div>
                                <div class="col-md-6 col-12">
                                    <div class="form_div"> <label for="phone">Date of Joining </label>
                                        <input type="text" class="form_input" id="edit_date_of_joining"
                                            value="<?php echo $employee_data['DateofJoining']; ?>"
                                            name="date_of_joining">
                                    </div>
                                </div>
                                <div class="col-md-6 col-12">
                                    <div class="row align-items-center">
                                        <div class="col-md-8">
                                            <div class="form-group mb-0">
                                                <label class="form-label">Profile Photo</label>
                                                <div class="custom-file">
                                                    <input type="file" id="" name="employee_profile_photo"
                                                        class="form-control">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <img src="media/<?php echo $employee_data['ProfileImage']; ?>" width="40px"
                                                alt="">
                                        </div>
                                    </div>
                                </div>
                                <!-- <div class="col-md-6 col-12">
                                    <div class="form-group form_div">
                                        <label class="form-label" for="add division">Add Division</label>
                                        <div class="col-12 p-0">
                                            <select name="add_division" id="division" class="form-control form_input">
                                                <option id="division_textbox"
                                                    value="<?
                                                    // php echo $employee_data['Division'];
                                                    ?>" selected>
                                                    <?php
                                                    //  echo $employee_data['Division'];
                                                      ?>
                                                </option>
                                                <?php
                                                        // foreach($division_array as $division)
                                                        // {
                                                        //     if($division['Division'] == "Vendor")
                                                        //         continue;
                                                        ?>
                                                <option value="<?php
                                                //  echo $division['Division'];
                                                 ?>">
                                                    <?php
                                                    // echo $division['Division'];
                                                    ?></option>
                                                <?php
                                                        // }
                                                        ?>
                                            </select>
                                        </div>
                                    </div>
                                </div> -->
                                <div class="col-md-6 col-12">
                                    <div class="form_div"> <label for="Employee_Name">Employee Name </label>
                                        <input type="text" class="form_input"
                                            value="<?php echo $employee_data['Name']; ?>" name="employee_name">
                                    </div>
                                </div>
                                <div class="col-md-6 col-12">
                                    <div class="form_div"> <label for="disignation">Designation </label>
                                        <input type="text" class="form_input"
                                            value="<?php echo $employee_data['Designation']; ?>" name="designation">
                                    </div>
                                </div>
                                <div class="col-md-6 col-12">
                                    <div class="form_div"> <label for="email">Employee Email </label> <input type="text"
                                            id="email" class="form_input" value="<?php echo $employee_data['Email']; ?>"
                                            name="employee_email">
                                    </div>
                                </div>
                                <div class="col-md-6 col-12">
                                    <div class="form_div"> <label for="department">Department
                                        </label>
                                        <select name="employee_department" id="employee_department" class="form_input"
                                            required>
                                            <option value="<?php echo $employee_data['Department']; ?>" selected>
                                                <?php echo $employee_data['Department']; ?>
                                            </option>
                                            <option value="Operation">Operation</option>
                                            <option value="HR">HR</option>
                                            <option value="Accountant">Accountant</option>
                                            <option value="Finance">Finance</option>
                                            <option value="Marketing">Marketing</option>
                                            <option value="Precurment">Precurment</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="col-md-6 col-12">
                                    <div class="form_div"> <label for="contactnumber">Contact Number
                                        </label>
                                        <input type="text" class="form_input" name="employee_contact"
                                            value="<?php echo $employee_data['ContactNumber']; ?>">
                                    </div>
                                </div>
                                <div class="col-md-6 col-12">
                                    <div class="form_div"> <label for="bankaccountname">Bank Account Name
                                        </label>
                                        <input type="text" class="form_input" name="bank_account_name"
                                            value="<?php echo $employee_data['BankAccountName']; ?>">
                                    </div>
                                </div>
                                <div class="col-md-6 col-12">
                                    <div class="form_div"> <label for="bankaccountnumber">Bank Account Number
                                        </label>
                                        <input type="text" class="form_input" name="bank_account_number"
                                            value="<?php echo $employee_data['BankAccountNumber']; ?>">
                                    </div>
                                </div>
                                <div class="col-md-6 col-12">
                                    <div class="form_div"> <label for="uannumber">UAN Number
                                        </label>
                                        <input type="text" class="form_input"
                                            value="<?php echo $employee_data['UANNumber']; ?>" name="employee_uan_number">
                                    </div>
                                </div>
            
                                <div class="col-md-6 col-12">
                                    <div class="form_div"> <label for="pannumber">PAN Number
                                        </label>
                                        <input type="text" class="form_input"
                                            value="<?php echo $employee_data['PAN']; ?>" name="employee_pan_number">
                                    </div>
                                </div>
                                <div class="col-md-6 col-12">
                                    <div class="row align-items-center">
                                        <div class="col-md-8">
                                            <div class="form-group mb-0">
                                                <label class="form-label">PAN Photo</label>
                                                <div class="custom-file">
                                                    <input type="file" id="" name="employee_pan_img"
                                                        class="form-control">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <img src="media/<?php echo $employee_data['PANImage']; ?>" width="40px"
                                                alt="">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6 col-12">
                                    <div class="form_div"> <label for="aadharnumber"> Aadhar Number
                                        </label> <input type="text" class="form_input"
                                            VALUE="<?php echo $employee_data['Aadhar']; ?>" name="employee_aadhar_number">
                                    </div>
                                </div>
                                <div class="col-md-6 col-12">
                                    <div class="row align-items-center">
                                        <div class="col-md-8">
                                            <div class="form-group mb-0">
                                                <label class="form-label">Aadhar Photo</label>
                                                <div class="custom-file">
                                                    <input type="file" id="" name="employee_addhar_img"
                                                        class="form-control">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <img src="media/<?php echo $employee_data['AadharImage']; ?>" width="40px"
                                                alt="">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6 col-12">
                                    <div class="row align-items-center">
                                        <div class="col-md-8">
                                            <div class="form-group mb-0">
                                                <label class="form-label">Police Verification Photo</label>
                                                <div class="custom-file">
                                                    <input type="file" id="" name="employee_police_verification"
                                                        class="form-control">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <img src="media/<?php echo $employee_data['PoliceVerificationImage']; ?>"
                                                width="40px" alt="">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6 col-12">
                                    <div class="form_div"> <label for="city">City
                                        </label>
                                        <select name="employee_city" class="select2 form-control w-100" id="citydata">
                                        <?php
                                        if($employee_data['City'] == "")
                                        {
                                            ?>
                                            <option value="">Please Select</option>
                                            <?php
                                        }
                                        foreach($citydata as $cityvalue)
                                        {
                                            $selected = "";
                                            if($cityvalue == $employee_data['City'])
                                                $selected = "selected";
                                        ?>
                                            <option value="<?php echo $cityvalue['CityName']; ?>" <?php echo $selected; ?>>    <?php echo $cityvalue['CityName']; ?></option>
                                        <?php
                                        }
                                        ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <input type="hidden" name="EmployeeID" value="<?php echo $ID; ?>" />
                            <input type="hidden" name="temp_city" id="city_Select_value" value="<?php echo $employee_data['City']; ?>" />
                        </section>
                        <section>
                            <div class="row justify-content-center mt-3">
                                <a onclick = "UpdateEmployee()" class="text-white btn btn-primary">Update</a>
                                <!-- <input type="submit" class="form_btn form_submit pl-4 pr-4 pt-2 pb-2" value="Save"> -->
                            </div>
                        </section>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<!-- edit modal  -->