<div class="panel-container show">
    <div class="panel-content p-0">
        <!-- datatable start -->
        <div class="profile-grid-premium">
            <div class="profile-card-premium w-100">
                <div class="profile-card-hdr">
                    <i class="fal fa-money-check-alt"></i>
                    <h3>Compensation Details</h3>
                </div>
                <div class="row">
                    <!-- Left Column -->
                    <div class="col-md-6">
                        <div class="profile-field-row mb-3">
                            <span class="profile-lbl">Basic Salary</span>
                            <span class="profile-val"><?php echo $employee_data['Basic'] == "" ? "Not Set" : $employee_data['Basic']; ?></span>
                        </div>
                        <div class="profile-field-row mb-3">
                            <span class="profile-lbl">DA</span>
                            <span class="profile-val"><?php echo $employee_data['DA'] == "" ? "Not Set" : $employee_data['DA']; ?></span>
                        </div>
                        <div class="profile-field-row mb-3">
                            <span class="profile-lbl">HRA</span>
                            <span class="profile-val"><?php echo $employee_data['HRA'] == "" ? "Not Set" : $employee_data['HRA']; ?></span>
                        </div>
                        <div class="profile-field-row mb-3">
                            <span class="profile-lbl">Bonus</span>
                            <span class="profile-val"><?php echo $employee_data['Bonus'] == "" ? "Not Set" : $employee_data['Bonus']; ?></span>
                        </div>
                        <div class="profile-field-row mb-3">
                            <span class="profile-lbl">Conveyance Allowance</span>
                            <span class="profile-val"><?php echo $employee_data['ConvenienceAllowance'] == "" ? "Not Set" : $employee_data['ConvenienceAllowance']; ?></span>
                        </div>
                        <div class="profile-field-row mb-3">
                            <span class="profile-lbl">Gross</span>
                            <span class="profile-val text-primary" style="font-size: 18px;"><?php echo $employee_data['Gross'] == "" ? "Not Set" : '₹'.$employee_data['Gross']; ?></span>
                        </div>
                    </div>
                    <!-- Right Column -->
                    <div class="col-md-6">
                        <div class="profile-field-row mb-3">
                            <span class="profile-lbl">Health Insurance</span>
                            <span class="profile-val"><?php echo $employee_data['HealthInsurance'] == "" ? "Not Set" : $employee_data['HealthInsurance']; ?></span>
                        </div>
                        <div class="profile-field-row mb-3">
                            <span class="profile-lbl">EPF Number</span>
                            <span class="profile-val"><?php echo $employee_data['Epf_number'] == "" ? "Not Set" : $employee_data['Epf_number']; ?></span>
                        </div>
                        <div class="profile-field-row mb-3">
                            <span class="profile-lbl">ESIC Number</span>
                            <span class="profile-val"><?php echo $employee_data['Esic_number'] == "" ? "Not Set" : $employee_data['Esic_number']; ?></span>
                        </div>
                        <div class="profile-field-row mb-3">
                            <span class="profile-lbl">Others</span>
                            <span class="profile-val"><?php echo $employee_data['Others'] == "" ? "Not Set" : $employee_data['Others']; ?></span>
                        </div>
                        <div class="profile-field-row mb-3">
                            <span class="profile-lbl">In-Hand Salary</span>
                            <span class="profile-val text-success" style="font-size: 18px;"><?php echo $employee_data['InHandSalary'] == "" ? "Not Set" : '₹'.$employee_data['InHandSalary']; ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
        $word = "view_profile_details";
        if(strpos($_SERVER['REQUEST_URI'],$word) !== false) 
        {
        }
        else
        {
        ?>
        <div class="text-right mt-4 mb-3 mr-3">
            <a href="#" class="btn-premium" onclick="opensalarymodal()"><i class="fal fa-edit"></i> Edit Salary</a>
        </div>
        <?php
        }
        ?>
        <?php include __DIR__ . '/employee_payslips_section.php'; ?>
    </div>
</div>
<!-- edit modal  -->
<div class="modal fade bd-example-modal-lg" id="salarydetails" role="dialog"
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
                <form id="update_employee_salary_form">
                    <div id="wizard">
                        <section>
                            <div class="row">
                                <div class="col-md-6 col-12">
                                    <div class="form_div"> <label for="basic">Basic </label>
                                        <input type="text" class="form_input" id="basic"
                                            value="<?php echo $employee_data['Basic']; ?>" onkeyup="validISNumber(basic)" name="basic">
                                    </div>
                                </div>
                                <div class="col-md-6 col-12">
                                    <div class="form_div"> <label for="da">DA </label>
                                        <input type="text" class="form_input" id="da"
                                            value="<?php echo $employee_data['DA']; ?>" onkeyup="validISNumber(basic)" name="da">
                                    </div>
                                </div>
                                <div class="col-md-6 col-12">
                                    <div class="form_div"> <label for="hra">HRA </label>
                                        <input type="text" class="form_input" id="hra"
                                            value="<?php echo $employee_data['HRA']; ?>" onkeyup="validISNumber(basic)" name="hra">
                                    </div>
                                </div>
                                <div class="col-md-6 col-12">
                                    <div class="form_div"> <label for="bonus">Bonus </label>
                                        <input type="text" class="form_input" id="bonus"
                                            value="<?php echo $employee_data['Bonus']; ?>" onkeyup="validISNumber(basic)" name="bonus">
                                    </div>
                                </div>
                                <div class="col-md-6 col-12">
                                    <div class="form_div"> <label for="convenience_allowance">Conveyance </label>
                                        <input type="text" class="form_input" id="convenience_allowance"
                                            value="<?php echo $employee_data['ConvenienceAllowance']; ?>" onkeyup="validISNumber(basic)" name="convenience_allowance">
                                    </div>
                                </div>

                                <div class="col-md-6 col-12">
                                    <div class="form_div"> <label for="epfnumber">EPF Number
                                        </label>
                                        <input type="text" class="form_input"
                                            value="<?php echo $employee_data['Epf_number']; ?>" name="employee_epf_number">
                                    </div>
                                </div>
                                <div class="col-md-6 col-12">
                                    <div class="form_div"> <label for="esicnumber">ESIC Number
                                        </label>
                                        <input type="text" class="form_input"
                                            value="<?php echo $employee_data['Esic_number']; ?>" name="employee_esic_number">
                                    </div>
                                </div>
                                <div class="col-md-6 col-12">
                                    <div class="form_div"> <label for="healthinsurance">Health Insurance
                                        </label>
                                        <input type="text" class="form_input" id="health_insurance"
                                            value="<?php echo $employee_data['HealthInsurance']; ?>" name="health_insurance" onkeyup="validISNumber(basic)">
                                    </div>
                                </div>
                                <div class="col-md-6 col-12">
                                    <div class="form_div"> <label for="others">Others </label>
                                        <input type="text" class="form_input" id="others"
                                            value="<?php echo $employee_data['Others']; ?>" name="others" onkeyup="validISNumber(basic)">
                                    </div>
                                </div>
                                <div class="col-md-6 col-12">
                                    <div class="form_div"> <label for="gross">Gross </label>
                                        <input type="text" class="form_input" id="gross"
                                            value="<?php echo $employee_data['Gross']; ?>" name="gross" onkeyup="validISNumber(basic)">
                                    </div>
                                </div>
                                <div class="col-md-6 col-12">
                                    <div class="form_div"> <label for="in_hand_salary">In-Hand Salary </label>
                                        <input type="text" class="form_input" id="in_hand_salary"
                                            value="<?php echo $employee_data['InHandSalary']; ?>" name="in_hand_salary" onkeyup="validISNumber(basic)">
                                    </div>
                                </div>
                            </div>
                            <input type="hidden" name="EmployeeID" value="<?php echo $ID; ?>" />
                        </section>
                        
                            <section>
                                <div class="row justify-content-center mt-3">
                                    <a onclick = "UpdateEmployeeSalary()" id="update_salary" class="text-white btn btn-primary">Update</a>
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