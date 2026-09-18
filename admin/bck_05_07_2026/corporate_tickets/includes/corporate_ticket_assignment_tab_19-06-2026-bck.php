<?php
include_once(__DIR__ . '/../controller/ticket_escalation_controller.php');
$te_createdBy = isset($_SESSION['pb_username']) ? $_SESSION['pb_username'] : 'system';
te_processTicketEscalation($conn, (int) $ID, $te_createdBy);
$te_escalation_summary = te_getEscalationSummary($conn, (int) $ID, $_SESSION);
$te_can_manual = te_userCanManualEscalate($_SESSION);
$te_ticket_open = te_isTicketOpenForEscalation($corporate_ticket_data);
$te_manual_options = te_getManualEscalationOptions($conn, $corporate_ticket_data);
$te_show_escalation = te_userCanViewEscalation($_SESSION)
    || $te_can_manual
    || !empty($te_escalation_summary['active'])
    || !empty($te_escalation_summary['history']);

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

        <?php if ($te_show_escalation) { ?>
        <div class="mt-4 border-top pt-3" id="te_escalation_panel">
            <h5 class="mb-3"><i class="fal fa-level-up-alt"></i> Due Date Escalation</h5>
            <p class="text-muted small mb-2">
                Tickets auto-escalate when the due date passes after assignment (Branch Manager → State Manager → CEO, <?php echo (int) TE_ESCALATION_RESPONSE_HOURS; ?>h per level).
                Use <strong>Manual Escalation</strong> below to escalate immediately without waiting for the due date.
            </p>

            <div id="te_escalation_manual" class="mb-3">
            <?php if ($te_can_manual) { ?>
                <?php if (!$te_ticket_open) { ?>
                    <div class="alert alert-info mb-0">
                        <strong>Manual escalation unavailable:</strong> assign an employee to this ticket first, and ensure the ticket is not Closed/Cancelled.
                    </div>
                <?php } elseif (count($te_manual_options) === 0) { ?>
                    <div class="alert alert-warning mb-0">
                        <strong>Manual escalation unavailable:</strong> no escalation contacts are configured for this branch/state.
                        Set <em>Account Branch Manager</em> on the branch, <em>State Corporate Head</em> on the state, and an employee with designation <em>CEO</em>.
                    </div>
                <?php } else { ?>
                <div class="card border-primary mb-0">
                    <div class="card-body py-3">
                        <h6 class="card-title mb-2"><i class="fal fa-hand-point-up"></i> Manual Escalation</h6>
                        <div class="form-row align-items-end">
                            <div class="form-group col-md-5 mb-2 mb-md-0">
                                <label for="te_manual_level">Escalate to</label>
                                <select id="te_manual_level" class="form-control form-control-sm">
                                    <option value="0">Next level in chain</option>
                                    <?php foreach ($te_manual_options as $te_opt) { ?>
                                    <option value="<?php echo (int) $te_opt['level']; ?>">
                                        <?php echo htmlspecialchars($te_opt['level_label'] . ' — ' . $te_opt['employee_name']); ?>
                                    </option>
                                    <?php } ?>
                                </select>
                            </div>
                            <div class="form-group col-md-5 mb-2 mb-md-0">
                                <label for="te_manual_remarks">Remarks (optional)</label>
                                <input type="text" id="te_manual_remarks" class="form-control form-control-sm" placeholder="e.g. Urgent — client follow-up needed">
                            </div>
                            <div class="form-group col-md-2 mb-0">
                                <button type="button" class="btn btn-warning btn-sm btn-block" id="te_manual_escalate_btn" onclick="teManualEscalate()">
                                    Escalate Now
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <?php } ?>
            <?php } else { ?>
                <div class="alert alert-secondary mb-0 small">
                    Manual escalation is available to Admin, Ticket Manager, City Lead, Branch Account Manager, State Corporate Lead, and Account Manager roles.
                </div>
            <?php } ?>
            </div>

            <div id="te_escalation_active" class="mb-3"></div>
            <h6 class="mb-2">Escalation History</h6>
            <div id="te_escalation_history"></div>
        </div>
        <?php } ?>

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