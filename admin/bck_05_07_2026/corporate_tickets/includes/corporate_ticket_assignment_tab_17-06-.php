<?php
$status_array = getAllCorporateTicketStatus($conn);
$BookingStatus = $corporate_ticket_data['Status'];

$employee_array = getAssignedList($conn);
$AssignedTo = $corporate_ticket_data['AssignedTo'];
// var_dump($employee_array);

$status_array = _getTableRecords($conn,'corporate_tickets_status','where AccountBranchManager = 1'); 
if($UserType == "Admin" || $TicketManager)
{
    $status_array = _getTableRecords($conn,'corporate_tickets_status','where IsActive = 1'); 
}


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
                            echo getEmployeeDetailsfromID($conn, $AssignedTo)['Name'] . "</br>" . getEmployeeDetailsfromID($conn, $AssignedTo)['ContactNumber'];

                        }
                        ?>
                    </td>

                </tr>


                <tr>
                    <th>Current Status</th>
                    <td>
                        <?php echo $BookingStatus; ?>
                        <?php
                        /*if($UserType == "Corporate Admin")
                        {
                        ?>
                            <a href="#" class="ml-5 badge badge-primary cursor-pointer" style="margin-right:20px;" onclick="EditCorporateTicketStatus()">Change Ticket Status</a>
                        <?php
                        }*/
                        ?> 
                    </td>

                </tr>

                 <tr>
                    <th>Ticket Due Date</th>
                    <td>

                        <?php 
                             
                         if($corporate_ticket_data['DueDate'] == "")
                        {
                            echo "<b>Not Set</b>";
                        }
                        else
                        {
                            echo $corporate_ticket_data['DueDate'];
                        }

                         ?>
                    </td>

                </tr>

                <tr>
                    <th>Remarks</th>
                    <td>

                        <?php 
                             
                        if($corporate_ticket_data['Remarks'] == "")
                        {
                            echo "<b>Not Set</b>";
                        }
                        else
                        {
                            echo $corporate_ticket_data['Remarks'];
                        }

                         ?>
                    </td>

                </tr>

            </tbody>

        </table>
        <?php
       if($UserType == "Corporate Branch User")
       {

        ?>
        <div></div>
        <?php
       }
       else
       {
            if($BookingStatus == "Closed" && $CityLead)
            {

            }
            else
            {
                ?>
                <div class='text-right'>
                    <a href='#' class='btn btn-info' style='margin-right:20px;' onclick='openBookingAssignmodal()'>Edit</a>
                </div>
                <?php
            }
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
                <form id="ticket_assignment_form">
                    <div id="wizard">
                        <section>
                            <div class="row">

                                <div class="col-md-4 col-12">
                                    <div class="form_div">
                                        <label for="AssignEmployee">Assign Employee</label>

                                        <select class="select2 form-control w-100" id="assignemployee_dropdown"
                                            name="AssignedTo">
                                            
                                            <option value="-1" selected>
                                                Please Assign
                                            </option>
                                            <?php
                                           
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
                                        <select name="TicketStatus" id="ticket_status" class="form-control select2" onchange="checkforclosedatediv(this.value)">
                                            <?php
                                            foreach($status_array as $status)
                                            {
                                                $selected = "";
                                                if($BookingStatus == $status['Status'])
                                                    $selected = "selected";
                                            ?>
                                            <option value="<?php echo $status['Status'];?>" <?php echo $selected; ?>>
                                                <?php echo $status['Status'];?></option>
                                            <?php
                                            }
                                            ?>
                                             <!-- <option value="__REASSIGN__">Reassign Ticket</option> -->
                                        </select>

                                    </div>
                                </div>

                                <div class="col-md-4 col-12">
                                    <div class="form_div"> <label for="Duedate">Ticket Due Date
                                        </label>
                                        <input type="text" class="form-control" name="DueDate" id="due_date" value="<?php echo $corporate_ticket_data['DueDate']; ?>" placeholder="Select Due Date">

                                    </div>
                                </div>
                            </div>
                            <div class="row mt-4">
                                <div class="col-md-4 col-12">
                                    <div class="form_div"> <label for="remarks">Remarks</label>
                                        <textarea id="ticket_remarks" class="form-control"  name="ticket_remarks"><?php echo $corporate_ticket_data['Remarks']; ?></textarea>

                                    </div>
                                </div>
                                 <div class="col-md-4 col-12" id="close_date_div" style="display:none;">
                                    <div class="form_div"> <label for="">Ticket Close Date</label>
                                        <input type="text" class="form-control" name="CloseDate" id="close_date" value="" placeholder="Select Due Date">
                                    </div>
                                </div>

                                <input type="hidden" name="TicketID" value="<?php echo $ID;?>" />


                            </div>
                           
                            <section>
                                <div class="row justify-content-center mt-3">
                                    <a class="form_btn form_submit pl-4 pr-4 pt-2 pb-2 text-white cursor-pointer" id="assignment_change_btn"
                                        style="background-color: #2196f3;" onclick="ChangeTicketStatus_Assignment()"
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
<?php
$corporate_status_array_modal = _getTableRecords($conn,'corporate_tickets_status','where Corporate = 1'); 
?>
<div class="modal fade bd-example-modal-lg" id="edit_corporate_ticket_status" role="dialog" aria-labelledby="exampleModalLabel"
    aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header" style="background-color: #027dc1;
    color: #fff;">
                <div class="tab_modal_heading">
                    <h2>Change Status </h2>
                </div>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="opacity: 1;
    color: #fff;">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form method="post" id="corporate_ticket_status_modal_form">
                    <div id="wizard">
                        <section>
                            <div class="row">

                                 <div class="col-md-12 col-12">
                                    <div class="form-group">
                                        <label class="form-label" for="name">Select New Status <span
                                                class="text-danger">*</span></label>
                                            <select class="form-control w-100" name="TicketStatus" id="corporate_ticket_status_modal">
                                                <option value="<?=$BookingStatus;?>"><?=$BookingStatus;?></option>
                                                <?php 
                                                foreach($corporate_status_array_modal as $i_corporate_status)
                                                {
                                                    ?>
                                                    <option value="<?=$i_corporate_status['Status'];?>"><?=$i_corporate_status['Status'];?></option>
                                                    
                                                    <?php
                                                }
                                                ?>
                                                
                                            </select>
                                    </div>
                                </div>
                                <input type="hidden" class="form-control" name="DueDate" value="<?php echo $corporate_ticket_data['DueDate']; ?>">
                                <input type="hidden" name="AssignedTo" value="<?php echo $AssignedTo;?>" />
                                <input type="hidden" name="TicketID" value="<?php echo $ID;?>" />
                                <input type="hidden" class="form-control" name="ticket_remarks" value="<?php echo $corporate_ticket_data['Remarks'];?>" />

                            </div>
                        </section>
                            <section>
                                <div class="row justify-content-center mt-3">
                                    <a class="form_btn form_submit pl-4 pr-4 pt-2 pb-2 text-white cursor-pointer" id="clientid_change_btn"
                                        style="background-color: #2196f3;" onclick="Corporate_ChangeTicketStatus()"
                                        value="Save">Save & Change</a>
                                </div>
                            </section>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>