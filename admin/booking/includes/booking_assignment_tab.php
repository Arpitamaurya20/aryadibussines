<?php
$status_array = getBookingStatusArray($conn);
$employee_array = getAssignedList($conn);
//var_dump($employee_array);
$AssignedTo = $bookingdata['AssignedTo'];
$BookingStatus = $bookingdata['Status'];
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
                        if($AssignedTo == "")
                        {
                            echo "<b>Not Set</b>";
                        }
                        else
                        {
                            echo getEmployeeDetailsfromID($conn,$AssignedTo)['Name'];
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

            </tbody>

        </table>
        <div class="text-right">
            <a href="#" class="btn btn-info" style="margin-right:20px;" onclick="openBookingAssignmodal()">Edit</a>
        </div>

    </div>
</div>


<!-- edit modal  -->

<div class="modal fade bd-example-modal-lg" id="editassign_booking" role="dialog"
    aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header" style="background-color: #027dc1;
    color: #fff;">
                <div class="tab_modal_heading">
                    <h2>Edit Your Assign </h2>
                </div>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="opacity: 1;
    color: #fff;">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="booking_assignment_form">
                    <div id="wizard">
                        <section>
                            <div class="row">
                                <div class="col-md-6 col-12">
                                    <div class="form_div">
                                        <label for="AssignEmployee">Assign Employee</label>

                                        <select class="select2 form-control w-100" id="assignemployee_dropdown" name="assigned_employee">
                                            <?php
                                            if($AssignedTo == "")
                                            {
                                            ?>
                                                <option value="" selected>
                                                    Please Assign
                                                </option>
                                            <?php
                                            }
                                            foreach($employee_array as $employee)
                                            {
                                                $selected = "";
                                                if($ID == $employee['ID'])
                                                    continue;
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


                                <div class="col-md-6 col-12">
                                    <div class="form_div"> <label for="ChangeStatus">Change Status
                                        </label>
                                        <select name="booking_status" id="booking_status"
                                            class="form-control">
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
                                        </select>

                                    </div>
                                </div>
                                <input type="hidden" name="BookingID" value="<?php echo $ID;?>"/>


                            </div>
                            <section>
                                <div class="row justify-content-center mt-3">
                                    <a class="form_btn form_submit pl-4 pr-4 pt-2 pb-2 text-white cursor-pointer" style="background-color: #2196f3;" onclick="ChangeBookingStatus_Assignment()" value="Save">Save</a>
                                </div>
                            </section>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- edit modal  -->