<?php 
$employee_obj1 = new Employee($conn);
$data_leave['EmployeeID'] = $ID;
$EmployeeLeaveArray = $employee_obj1->getEmployeeLeaves($data_leave);
if($employee_data['DateofJoining'] == "")
{
    $current_year_leaves = "N.A.";
}
else
{
    $joiningDate = $employee_data['DateofJoining'];
    $totalAnnualLeaves = $employee_data['EmployeeLeave'];

    // Current year
    $currentYear = date('Y');
    $currentMonth = date('m');

    // Parse the joining date
    $joiningYear = date('Y', strtotime($joiningDate));
    $joiningMonth = date('m', strtotime($joiningDate));
    $joiningDay = date('d', strtotime($joiningDate));

    // Calculate leaves
    if ($joiningYear < $currentYear) {
        // If the joining year is before the current year, all leaves are applicable
        $current_year_leaves = $totalAnnualLeaves;
    } else {
        // Joining year is the current year, calculate months served
        $monthsServed = ($currentMonth - $joiningMonth);

        // Adjust the first month based on the joining date
        if ($joiningDay <= 15) {
            $monthsServed++; // Count the joining month if joined on or before the 15th
        }

        // Ensure months served is not negative
        $monthsServed = max($monthsServed, 0);

        // Calculate prorated leaves
        $current_year_leaves = ($totalAnnualLeaves / 12) * $monthsServed;

        $current_year_leaves = round($current_year_leaves, 2);
    }
}
?>
<main id="js-page-content" role="main" class="page-content">
    <!-- Page Container -->
    
    <div class="row">
        <div class="col-xl-12">
            <div class="profile-grid-premium mb-4">
                <div class="profile-card-premium w-100">
                    <div class="profile-card-hdr">
                        <i class="fal fa-calendar-check"></i>
                        <h3>Leave Balance Summary</h3>
                    </div>
                    <div class="row mt-2">
                        <div class="col-md-6">
                            <div class="profile-field-row">
                                <span class="profile-lbl">Your Assigned Leaves (Yearly)</span>
                                <span class="profile-val" style="font-size: 18px; color: #4f46e5;">
                                    <span id="employee_leave_display_text"><?php echo $employee_data['EmployeeLeave']; ?></span>
                                    <?php if($UserType == "Admin" || $HR) { ?>
                                        <a onclick="openUpdateLeavesModal(<?php echo $ID;?>)" class="cursor-pointer ml-2 text-primary" style="font-size: 14px; text-decoration: none;"><i class="fal fa-edit"></i> Edit</a>
                                    <?php } ?>
                                </span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="profile-field-row">
                                <span class="profile-lbl">Current Year Applicable Leaves</span>
                                <span class="profile-val text-success" style="font-size: 18px;"><?php echo $current_year_leaves; ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div id="panel-1" class="profile-card-premium" style="grid-column: 1 / -1;">
                <div class="profile-card-hdr d-flex justify-content-between align-items-center w-100 mb-0" style="padding-bottom: 0; border-bottom: none;">
                    <div class="d-flex align-items-center" style="gap: 12px;">
                        <i class="fal fa-calendar-alt"></i>
                        <h3>View Leave Configuration</h3>
                    </div>
                    <a href="#" onclick="OpenLeave_modal()" class="btn-premium" style="padding: 6px 16px; font-size: 13px;">
                        <i class="fal fa-plus"></i> Add Leave
                    </a>
                </div>  

                <div class="panel-container show">
                    <div class="panel-content">
                        <!-- datatable start -->
                        <table id="view-leave-configuration"
                            class="table table-bordered table-hover table-striped w-100">
                            <thead class="bg-primary-500">
                                <tr>
                                    <th>#</th>
                                   
                                    <th>Reason Of Leave</th>
                                    <th>From Date</th>
                                    <th>To Date</th>
                                    <th>Duration</th>
                                    <!--th>Update</th-->
                                    <th>Approval Status</th>
                                    <th>Delete</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                	$i=1;
                                	foreach($EmployeeLeaveArray as $EmpLeave)
                                	{
                                        $Status = $EmpLeave['Status'];
                                	    $EmployeeLeaveId  = $EmpLeave['ID'];
                                        $EmployeeID  = $EmpLeave['EmployeeID'];
                                        $ApproveID  = $EmpLeave['Approved'];
                                        $FromDate  = $EmpLeave['FromDate'];
                                        $ToDate  = $EmpLeave['ToDate'];
                                        if ($EmployeeID == $ID)
                                        {
                                ?>
                                <tr>
                                    <td><?php echo $i; ?></td>

                                    

                                    <td><?php echo $EmpLeave['ReasonOfLeave']; ?></td>

                                    <td><?php echo $EmpLeave['FromDate']; ?></td>

                                    <td><?php echo $EmpLeave['ToDate']; ?></td>

                                    <td><?php echo $EmpLeave['Duration']; ?></td>

                                    <!--td>
                                        <a onclick="UpdateLeave_modal('<?php echo $EmployeeLeaveId;?>')"
                                            class="cursor-pointer"><i class="fal fa-edit"
                                                aria-hidden="true"></i></a>
                                    </td-->

                                    <td>
                                        <?php 
                                        if ($Status=="Pending") 
                                        {
                                        ?>
                                            <span class="badge badge-warning cursor-pointer">Pending Supervisor</span>
                                        <?php
                                        }
                                        else if($Status=="SupervisorApproved")
                                        {
                                        ?>
                                            <span class="badge badge-info cursor-pointer">Supervisor Approved - Pending HR</span>
                                        <?php
                                        } 
                                        else if($Status=="Rejected") 
                                        { 
                                        ?>
                                            <span class="badge badge-danger cursor-pointer">Rejected</span>
                                        <?php
                                        }
                                        else if($Status=="Approved") 
                                        { 
                                        ?>
                                            <span class="badge badge-success cursor-pointer">Approved (HR Final)</span>
                                        <?php
                                        }
                                        else
                                        {

                                        }
                                        ?>
                                    </td>

                                    <td>
                                        <a onclick="DeleteLeave('<?php echo $EmployeeLeaveId;?>','<?php echo $Status;?>')"><i class="fal fa-trash" aria-hidden="true"></i></a>
                                    </td>
                                </tr>
                                <?php
                            	   $i++;
                                    }
                            	}
                            	?>
                            </tbody>
                        </table>
                        <!-- <?php echo $ID ?> -->
                        <input type="hidden" id="employee_id" name="EmployeeID" value="<?php echo $ID ?>">
                        <!-- datatable end -->
                    </div>
                </div>

            </div><!-- panel-1 -->
        </div><!-- col-xl-12 -->
    </div> <!-- row -->
    <!-- Datatable Container -->

    <!-- Modal -->
  <div class="modal fade" id="add_edit_leave_modal" role="dialog"
    aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header pb-0 edit_header">
                <div class="tab_modal_heading">
                    <h2>Add Leave</h2>
                </div>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="add_update_leave_form" mothod="POST" >
                    <div class="form-group">
                        <div class="row mt-3">
                            <div class="col-12">
                                <label class="form-label" for="date">From Date <span class="text-danger">*</span></label>
                                <input type="text" class="form-control col-xl-12 col-sm-12"
                                    name="from_date" id="from_date" placeholder="Select Leave Date" onchange="checkDates();" />
                            </div>
                        </div>

                        <div class="row mt-3">
                            <div class="col-12">
                                <label class="form-label" for="date">To Date <span class="text-danger">*</span></label>
                                <input type="text" class="form-control col-xl-12 col-sm-12"
                                    name="to_date" id="to_date" placeholder="Select Leave Date" onchange="checkDates();"/>
                            </div>
                        </div>
                        <div class="row mt-3" id="half_day_container" style="display:none;">
                            <div class="col-6">
                                <div class="custom-control custom-checkbox custom-control-inline">
                                    <input type="checkbox"name="half_day" class="custom-control-input" id="defaultInline2" checked="">
                                    <label class="custom-control-label" for="defaultInline2">Half Day</label>
                                </div>
                            </div>
                        </div>

                        <!--div class="row mt-3">
                            <div class="col-12">
                                <label for="categories">Type Of Leave <span class="text-danger">*</span></label>
                                <select class="select2 form-control w-100" id="type_of_leave"
                                name="type_of_leave">
                                <option value="-1">Search & Select</option>
                                    <?php
                                        $leaveTypes = array(
                                        'FullDay',
                                        'HalfDay'
                                        );

                                      foreach ($leaveTypes as $type) {
                                        echo "<option value='$type'>$type</option>";
                                      }
                                    ?>
                                </select>
                            </div>
                        </div--> 

                        <div class="row mt-3">
                           <div class="col-12">
                                <label for="spare_part">Reason<span class="text-danger">*</span></label>
                                <textarea class="form-control w-100" name="leave_reason" id="leave_reason" cols="30" rows="3" placeholder="Type Your Message"></textarea>
                            </div>      
                        </div>                    

                    </div>
                    <input type="hidden" id="employee_id" name="EmployeeID" value="<?php echo $ID ?>">
                    <input type="hidden" id="form_action" name="form_action" value="add" />
                    <input type="hidden" id="form_id" name="form_id" value="-1" />
                    <a  class="btn btn-primary text-white" onclick="AddUpdateLeave()">Submit</a>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- this overlay is activated only when mobile menu is triggered -->
<div class="page-content-overlay" data-action="toggle" data-class="mobile-nav-on"></div>



<!-- Update Employee Leave Modal -->

<div class="modal fade" id="employee_leave_modal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header pb-0 edit_header">
            <div class="tab_modal_heading">
                    <h2>Update Employee Leave</h2>
                </div>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body"> 
            <input type="hidden" id="Leave_EmployeeID" value="-1" />                       
                <div class="row">
                    <div class="col-lg-12">
                        <div class="form-group">
                            <label class="form-label">Allowed Leaves (Yearly)</label>
                            <input type="text" name="leaves_allowed" id="leaves_allowed" class="form-control" placeholder="Enter Number" onkeypress="return event.charCode >= 48 && event.charCode <= 57">
                        </div>
                    </div>

                   
                </div>

                <div class="row justify-content-center mt-3">

                    <a class="form_btn form_submit pl-4 pr-4 pt-2 pb-2 text-white" onclick="UpdateEmployeeLeave()">Save</a>

                </div>                
            </div>

        </div>
    </div>
</div>
  
