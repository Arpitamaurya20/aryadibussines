<div class="panel-container show">
    <div class="panel-content p-0">
        <!-- datatable start -->
        <table id="view-projects" class="table table-bordered table-hover table-striped w-100">
            <tbody>
                <tr>
                    <th>Basic</th>
                    <td><?php
                     if($employee_data['Basic'] == "")
                     echo "Not Set";
                     else
                     echo $employee_data['Basic'];
                     ?>
                    </td>
                </tr>
                <tr>
                    <th>DA</th>
                    <td><?php
                     if($employee_data['DA'] == "")
                     echo "Not Set";
                     else
                     echo $employee_data['DA'];
                     ?>
                    </td>
                </tr>
                <tr>
                    <th>HRA</th>
                    <td><?php
                     if($employee_data['HRA'] == "")
                     echo "Not Set";
                     else
                     echo $employee_data['HRA'];
                     ?>
                    </td>
                </tr>
                <tr>
                    <th>Bonus</th>
                    <td><?php
                     if($employee_data['Bonus'] == "")
                     echo "Not Set";
                     else
                     echo $employee_data['Bonus'];
                     ?>
                    </td>
                </tr>
                <tr>
                    <th>Health Insurance</th>
                    <td><?php
                     if($employee_data['HealthInsurance'] == "")
                     echo "Not Set";
                     else
                     echo $employee_data['HealthInsurance'];
                     ?>
                    </td>
                </tr>
                <tr>
                    <th>EPF Number </th>
                    <td>
                        <?php
                        if($employee_data['Epf_number'] == "")
                            echo "Not Set";
                        else
                            echo $employee_data['Epf_number'];
                        ?>
                    </td>
                </tr>
                <tr>
                    <th>ESIC Number </th>
                    <td>
                        <?php
                        if($employee_data['Esic_number'] == "")
                            echo "Not Set";
                        else
                            echo $employee_data['Esic_number'];
                        ?>
                    </td>
                </tr>
                <tr>
                    <th>Others</th>
                    <td><?php
                     if($employee_data['Others'] == "")
                     echo "Not Set";
                     else
                     echo $employee_data['Others'];
                     ?>
                    </td>
                </tr>

            </tbody>
        </table>
        <div class="text-right">
            <a href="#" class="btn btn-info" style="margin-right:20px;" onclick="opensalarymodal()">Edit</a>
        </div>
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
                                        <input type="text" class="form_input"
                                            value="<?php echo $employee_data['Basic']; ?>" name="basic">
                                    </div>
                                </div>
                                <div class="col-md-6 col-12">
                                    <div class="form_div"> <label for="da">DA </label>
                                        <input type="text" class="form_input"
                                            value="<?php echo $employee_data['DA']; ?>" name="da">
                                    </div>
                                </div>
                                <div class="col-md-6 col-12">
                                    <div class="form_div"> <label for="hra">HRA </label>
                                        <input type="text" class="form_input"
                                            value="<?php echo $employee_data['HRA']; ?>" name="hra">
                                    </div>
                                </div>
                                <div class="col-md-6 col-12">
                                    <div class="form_div"> <label for="bonus">Bonus </label>
                                        <input type="text" class="form_input"
                                            value="<?php echo $employee_data['Bonus']; ?>" name="bonus">
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
                                        <input type="text" class="form_input"
                                            value="<?php echo $employee_data['HealthInsurance']; ?>" name="health_insurance">
                                    </div>
                                </div>
                                <div class="col-md-6 col-12">
                                    <div class="form_div"> <label for="others">Others </label>
                                        <input type="text" class="form_input"
                                            value="<?php echo $employee_data['Others']; ?>" name="others">
                                    </div>
                                </div>
                            </div>
                            <input type="hidden" name="EmployeeID" value="<?php echo $ID; ?>" />
                        </section>
                        <section>
                            <div class="row justify-content-center mt-3">
                                <a onclick = "UpdateEmployeeSalary()" class="text-white btn btn-primary">Update</a>
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