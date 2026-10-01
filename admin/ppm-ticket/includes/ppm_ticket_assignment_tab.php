<?php
$ppmticket = new Ppmtickets($conn);
$status_array = _getTableRecords($conn,'ppm_ticket_status','where AccountBranchManager = 1'); 
if($UserType == "Admin" || $TicketManager)
{
    $status_array = _getTableRecords($conn,'ppm_ticket_status','where IsActive = 1'); 
}

//$status_array = $ppmticket->getPPMTicketStatusArray("All");
$corporate_ticket_data = is_array($corporate_ticket_data) ? $corporate_ticket_data : array();
$BookingStatus = isset($corporate_ticket_data['Status']) ? $corporate_ticket_data['Status'] : '';

$employee_array = getAssignedList($conn);
$AssignedTo = isset($corporate_ticket_data['AssignedTo']) ? $corporate_ticket_data['AssignedTo'] : '';
//var_dump($employee_array);

?>

<div class="panel-container show">
    <div class="panel-content p-0">
        <!-- datatable start -->
        <table id="view-projects" class="table table-bordered table-hover table-striped w-100">
            <tbody>
                <tr>
                    <th> Employee Assigned</th>
                    <td>
                        <?php
                        if($AssignedTo == "" || $AssignedTo == -1)
                        {
                            echo "<b>Not Set</b>";
                        }
                        else
                        {
                            $assignedEmployee = getEmployeeDetailsfromID($conn, $AssignedTo);
                            if (is_array($assignedEmployee) && !empty($assignedEmployee['Name'])) {
                                echo htmlspecialchars($assignedEmployee['Name']);
                            } else {
                                echo "<b>Not Set</b>";
                            }
                        }
                        ?>
                    </td>

                </tr>


                <tr>
                    <th>Current Status</th>
                    <td>
                        <?php echo $BookingStatus; ?>
                    </td>

                </tr>

                <tr>
                    <th>Ticket Due Date</th>
                    <td>

                        <?php 
                             
                         if(empty($corporate_ticket_data['DueDate']))
                        {
                            echo "<b>Not Set</b>";
                        }
                        else
                        {
                            echo htmlspecialchars($corporate_ticket_data['DueDate']);
                        }

                         ?>
                    </td>

                </tr>

            </tbody>

        </table>
        <?php
       if($UserType == "Corporate Adminnnn"){



       ?>
       <div></div>
        <?php
       }else{
        ?>
          <div class='text-right'>
          <a href='#' class='btn btn-info' style='margin-right:20px;' onclick='openPPMTicketAssignmodal()'>Edit</a>
         </div>

         <?php
       }
        ?>

    </div>
</div>


<!-- edit modal  -->

<div class="modal fade bd-example-modal-lg" id="editassign_booking" role="dialog" aria-labelledby="exampleModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header" style="background-color: #027dc1;
    color: #fff;">
                <div class="tab_modal_heading">
                    <h2>Edit Ticket Status </h2>
                </div>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="opacity: 1;
    color: #fff;">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="ppm_ticket_assignment_form">
                    <div id="wizard">
                        <section>
                            <div class="row">

                                <div class="col-md-4 col-12">
                                    <div class="form_div">
                                        <label for="AssignEmployee">Assign Employee</label>

                                        <select class="select2 form-control w-100" id="assignemployee_dropdown"
                                            name="AssignedTo">
                                            <?php
                                            if($AssignedTo == "" || $AssignedTo == -1)
                                            {
                                            ?>
                                            <option value="-1" selected>
                                                Please Assign
                                            </option>
                                            <?php
                                            }
                                            foreach($employee_array as $employee)
                                            {
                                                $selected = "";
                                                if($AssignedTo == $employee['ID'])
                                                    $selected = "selected";
                                                ?>
                                            <option value="<?php echo $employee['ID'];?>" <?php echo $selected; ?>>
                                                <?php echo $employee['Name'];?>
                                            </option>
                                            <?php
                                            }
                                            ?>
                                        </select>

                                    </div>
                                </div>

                                <div class="col-md-4 col-12">
                                    <div class="form_div"> <label for="ChangeStatus">Change Status
                                        </label>
                                        <select name="TicketStatus" id="ticket_status" class="form-control select2">
                                            <?php
                                            foreach($status_array as $status)
                                            {
                                                $selected = "";
                                                if($BookingStatus == $status['Status'])
                                                    $selected = "selected";
                                                if (!$TicketManager && $status['Status'] != "Assigned") 
                                                    {
                                                        continue;
                                                    }
                                            ?>
                                            <option value="<?php echo $status['Status'];?>" <?php echo $selected; ?>>
                                                <?php echo $status['Status'];?></option>
                                            <?php
                                            }
                                            ?>
                                        </select>

                                    </div>
                                </div>

                                <div class="col-md-4 col-12">
                                    <div class="form_div"> <label for="Duedate">Ticket Due Date
                                        </label>
                                        <input type="text" class="form-control" name="DueDate"
                                                        id="due_date" placeholder="<?php
                                                        if(empty($corporate_ticket_data['DueDate'])){
                                                            echo "Select Due Date";
                                                        }else{
                                                            echo htmlspecialchars($corporate_ticket_data['DueDate']);
                                                        }
                                                        ?>"
                                                        value = "<?php echo htmlspecialchars($corporate_ticket_data['DueDate'] ?? '');?>"
                                                        />

                                    </div>
                                </div>

                                <input type="hidden" name="TicketID" value="<?php echo $ID;?>" />


                            </div>
                            <section>
                                <div class="row justify-content-center mt-3">
                                    <a class="form_btn form_submit pl-4 pr-4 pt-2 pb-2 text-white cursor-pointer" id="assignment_change_btn"
                                        style="background-color: #2196f3;" onclick="ChangePPMTicketStatusAssignment()"
                                        value="Save">Save & Change</a>
                                </div>
                            </section>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- edit modal  -->