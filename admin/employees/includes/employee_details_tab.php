<?php 
$Days_Array = array("Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday");
?>
<style>
.profile-grid-premium {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(340px, 1fr));
    gap: 24px;
    padding: 15px 0 25px 0;
}

.profile-card-premium {
    background: #ffffff;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
    padding: 24px;
    display: flex;
    flex-direction: column;
    gap: 20px;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.profile-card-premium:hover {
    transform: translateY(-4px);
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.08), 0 4px 6px -2px rgba(0, 0, 0, 0.04);
}

.profile-card-hdr {
    display: flex;
    align-items: center;
    gap: 12px;
    border-bottom: 1px solid #f1f5f9;
    padding-bottom: 12px;
    margin-bottom: 4px;
}

.profile-card-hdr i {
    font-size: 20px;
    color: #4f46e5;
}

.profile-card-hdr h3 {
    font-size: 15px;
    font-weight: 700;
    color: #1e293b;
    margin: 0;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.profile-field-row {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.profile-lbl {
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    color: #64748b;
    letter-spacing: 0.5px;
}

.profile-val {
    font-size: 14px;
    font-weight: 600;
    color: #1e2a4a;
}

.profile-val.not-set {
    color: #94a3b8;
    font-weight: 400;
    font-style: italic;
}

.profile-avatar-row {
    display: flex;
    align-items: center;
    gap: 16px;
    background: #f8fafc;
    padding: 16px;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
}

.profile-avatar-img {
    width: 64px;
    height: 64px;
    border-radius: 50%;
    object-fit: cover;
    border: 3px solid #fff;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.profile-avatar-meta {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.profile-avatar-meta h4 {
    font-size: 16px;
    font-weight: 700;
    color: #1e2a4a;
    margin: 0 0 4px 0;
}

.profile-avatar-meta p {
    font-size: 12px;
    color: #64748b;
    margin: 0;
}

.profile-doc-box {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 16px;
    background: #f8fafc;
    border-radius: 10px;
    border: 1px solid #e2e8f0;
}

.profile-doc-meta {
    display: flex;
    align-items: center;
    gap: 12px;
}

.profile-doc-meta i {
    font-size: 24px;
    color: #ef4444;
}

.profile-doc-details {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.profile-doc-name {
    font-size: 13px;
    font-weight: 600;
    color: #1e2a4a;
}

.profile-doc-val {
    font-size: 12px;
    color: #64748b;
}

.profile-doc-action a {
    font-size: 12px;
    font-weight: 700;
    color: #4f46e5;
    text-decoration: none;
    padding: 6px 12px;
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    transition: all 0.15s ease;
}

.profile-doc-action a:hover {
    background: #4f46e5;
    color: #fff;
    border-color: #4f46e5;
}
</style>

<div class="panel-container show">
    <div class="panel-content">
        
        <?php
        function renderProfileField($label, $value) {
            $formattedValue = ($value == "" || empty($value)) ? "<span class='profile-val not-set'>Not Set</span>" : "<span class='profile-val'>".htmlspecialchars($value)."</span>";
            return "
            <div class='profile-field-row'>
                <span class='profile-lbl'>{$label}</span>
                {$formattedValue}
            </div>";
        }
        ?>

        <div class="profile-grid-premium">
            <!-- 1. Personal Details Card -->
            <div class="profile-card-premium">
                <div class="profile-card-hdr">
                    <i class="fal fa-user"></i>
                    <h3>Personal Details</h3>
                </div>
                
                <div class="profile-avatar-row">
                    <?php
                    $profileImg = "tech-logo.jpg";
                    if ($employee_data['ProfileImage'] != "") {
                        $profileImg = $employee_media.$employee_data['ProfileImage'];
                    }
                    ?>
                    <img src="<?php echo $profileImg; ?>" class="profile-avatar-img" alt="Profile Image">
                    <div class="profile-avatar-meta">
                        <h4><?php echo htmlspecialchars($employee_data['Name']); ?></h4>
                        <p><?php echo htmlspecialchars($employee_data['Designation'] != "" ? $employee_data['Designation'] : "Employee"); ?></p>
                    </div>
                </div>

                <?php 
                echo renderProfileField("Father Name", $employee_data['FatherName']);
                echo renderProfileField("Gender", $employee_data['Gender']);
                ?>
            </div>

            <!-- 2. Employment Card -->
            <div class="profile-card-premium">
                <div class="profile-card-hdr">
                    <i class="fal fa-briefcase"></i>
                    <h3>Employment Information</h3>
                </div>

                <?php
                echo renderProfileField("Employee ID", $employee_data['EmployeeNumber']);
                echo renderProfileField("Date of Joining", $employee_data['DateofJoining']);
                echo renderProfileField("Department", $employee_data['Department']);
                echo renderProfileField("Weekly Off", $employee_data['WeeklyOff']);
                echo renderProfileField("City", $employee_data['City']);
                echo renderProfileField("State", $employee_data['State']);
                ?>
            </div>

            <!-- 3. Contact Details Card -->
            <div class="profile-card-premium">
                <div class="profile-card-hdr">
                    <i class="fal fa-id-card"></i>
                    <h3>Contact Information</h3>
                </div>

                <?php
                echo renderProfileField("Official Email", $employee_data['Email']);
                echo renderProfileField("Personal Email", $employee_data['PersonalEmail']);
                echo renderProfileField("Contact Number", $employee_data['ContactNumber']);
                ?>
            </div>

            <!-- 4. Documents Card -->
            <div class="profile-card-premium">
                <div class="profile-card-hdr">
                    <i class="fal fa-file-alt"></i>
                    <h3>Documents & Identity</h3>
                </div>

                <?php echo renderProfileField("UAN Number", $employee_data['UANNumber']); ?>

                <!-- PAN Card Box -->
                <div class="profile-doc-box">
                    <div class="profile-doc-meta">
                        <i class="fal fa-credit-card"></i>
                        <div class="profile-doc-details">
                            <span class="profile-doc-name">PAN Card</span>
                            <span class="profile-doc-val"><?php echo ($employee_data['PAN'] != "") ? htmlspecialchars($employee_data['PAN']) : "Not Set"; ?></span>
                        </div>
                    </div>
                    <?php if($employee_data['PANImage'] != "") { 
                        $panUrl = $employee_media . $employee_data['PANImage'];
                    ?>
                    <div class="profile-doc-action">
                        <a href="<?php echo $panUrl; ?>" target="_blank"><i class="fal fa-eye"></i> View</a>
                    </div>
                    <?php } ?>
                </div>

                <!-- Aadhar Card Box -->
                <div class="profile-doc-box">
                    <div class="profile-doc-meta">
                        <i class="fal fa-address-card"></i>
                        <div class="profile-doc-details">
                            <span class="profile-doc-name">Aadhar Card</span>
                            <span class="profile-doc-val"><?php echo ($employee_data['Aadhar'] != "") ? htmlspecialchars($employee_data['Aadhar']) : "Not Set"; ?></span>
                        </div>
                    </div>
                    <?php if($employee_data['AadharImage'] != "") { 
                        $aadharUrl = $employee_media . $employee_data['AadharImage'];
                    ?>
                    <div class="profile-doc-action">
                        <a href="<?php echo $aadharUrl; ?>" target="_blank"><i class="fal fa-eye"></i> View</a>
                    </div>
                    <?php } ?>
                </div>

                <!-- Police Verification Image Box -->
                <div class="profile-doc-box">
                    <div class="profile-doc-meta">
                        <i class="fal fa-shield-check"></i>
                        <div class="profile-doc-details">
                            <span class="profile-doc-name">Police Verification</span>
                            <span class="profile-doc-val"><?php echo ($employee_data['PoliceVerificationImage'] != "") ? "Uploaded" : "Not Set"; ?></span>
                        </div>
                    </div>
                    <?php if($employee_data['PoliceVerificationImage'] != "") { 
                        $policeUrl = $employee_media . $employee_data['PoliceVerificationImage'];
                    ?>
                    <div class="profile-doc-action">
                        <a href="<?php echo $policeUrl; ?>" target="_blank"><i class="fal fa-eye"></i> View</a>
                    </div>
                    <?php } ?>
                </div>
            </div>

            <!-- 5. Bank Details Card -->
            <div class="profile-card-premium">
                <div class="profile-card-hdr">
                    <i class="fal fa-university"></i>
                    <h3>Bank Details</h3>
                </div>

                <?php
                echo renderProfileField("Bank Account Name", $employee_data['BankAccountName']);
                echo renderProfileField("Bank Account Number", $employee_data['BankAccountNumber']);
                ?>
            </div>
        </div>

        <?php 
        if($HR || $UserType == "Admin")
        {
        ?>
        <div class="text-right mt-3 mb-3">
            <button class="btn btn-primary" style="padding: 10px 24px; border-radius: 8px; font-weight: 600;" onclick="opendetailmodal()">
                <i class="fal fa-edit"></i> Edit Details
            </button>
        </div>
        <?php 
        }
        ?>
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
                                    <div class="form_div"> <label for="father_name">Father Name </label>
                                        <input type="text" class="form_input"
                                            value="<?php echo $employee_data['FatherName']; ?>" name="father_name">
                                    </div>
                                </div>

                                <div class="col-md-6 col-12">
                                    <div class="form_div"> <label for="disignation">Designation </label>
                                        <input type="text" class="form_input"
                                            value="<?php echo $employee_data['Designation']; ?>" name="designation">
                                    </div>
                                </div>
                                <div class="col-md-6 col-12">
                                    <div class="form_div"> <label for="email">Official Email </label> <input type="text"
                                            id="email" class="form_input" value="<?php echo $employee_data['Email']; ?>"
                                            name="employee_email">
                                    </div>
                                </div>

                                <div class="col-md-6 col-12">
                                    <div class="form_div"> <label for="PersonalEmail">Personal Email </label> <input type="text"
                                            id="PersonalEmail" class="form_input" value="<?php echo $employee_data['PersonalEmail']; ?>"
                                            name="PersonalEmail">
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
                                            <option value="IT">IT</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="col-md-6 col-12">
                                    <div class="form_div"> <label for="contactnumber">Contact Number
                                        </label>
                                        <input type="text" onkeyup="validISNumber(basic)" class="form_input" name="employee_contact"
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
                                        <input type="text" onkeyup="validISNumber(basic)" class="form_input" name="bank_account_number"
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
                                        </label> <input onkeyup="validISNumber(basic)" type="text" class="form_input"
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
                                    <div class="form_div"> <label for="city">Weekly Off
                                        </label>
                                        <select name="weekly_off" class="select2 form-control w-100" id="weekly_off">
                                        <?php
                                        if($employee_data['WeeklyOff'] == "")
                                        {
                                            ?>
                                            <option value="">Please Select</option>
                                            <?php
                                        }
                                        foreach($Days_Array as $day)
                                        {
                                            $selected = "";
                                            if($day == $employee_data['WeeklyOff'])
                                                $selected = "selected";
                                        ?>
                                            <option value="<?php echo $day; ?>" <?php echo $selected; ?>>    <?php echo $day; ?></option>
                                        <?php
                                        }
                                        ?>
                                        </select>
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

                                 <div class="col-md-6 col-12">
                                    <div class="form_div"> <label for="state">State
                                        </label>
                                        <select name="employee_state" class="select2 form-control w-100" id="statedata">
                                        <?php
                                        if($employee_data['State'] == "")
                                        {
                                            ?>
                                            <option value="">Please Select</option>
                                            <?php
                                        }
                                        foreach($StateData as $statevalue)
                                        {
                                            $selected = "";
                                            if($statevalue == $employee_data['State'])
                                                $selected = "selected";
                                        ?>
                                            <option value="<?php echo $statevalue['StateName']; ?>" <?php echo $selected; ?>>    <?php echo $statevalue['StateName']; ?></option>
                                        <?php
                                        }
                                        ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <input type="hidden" name="EmployeeID" value="<?php echo $ID; ?>" />
                            <input type="hidden" name="temp_city" id="city_Select_value" value="<?php echo $employee_data['City']; ?>" />
                            <input type="hidden" name="temp_weekly_off" id="temp_weekly_off" value="<?php echo $employee_data['WeeklyOff']; ?>" />
                        </section>
                        <section>
                            <div class="row justify-content-center mt-3">
                                <a onclick = "UpdateEmployee()" id="update_basic_detail" class="text-white btn btn-primary">Update</a>
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