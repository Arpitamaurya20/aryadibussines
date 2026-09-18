<?php
$where = " where BookingID = $ID";
$hourly_service_booking_array = _getTableDetails($conn,'hourly_service_booking',$where);

$SubServiceID = '';
if($bookingdata['SubService'] != ""){
    $subservice_name = $bookingdata['SubService'];
    $where = " where title = '$subservice_name'";
    $subservice_details = _getTableDetails($conn,'subservice',$where);
    $SubServiceID = $subservice_details['ID'];
}
    
if(!empty($hourly_service_booking_array)){

    
    // echo $SubServiceID;

?>

<table class="table table-bordered table-hover table-striped w-100">

    <tbody>


        <tr>
            <th>Sub Service Name </th>
            <td>
                <?php 
                    if($bookingdata['SubService'] == "")
                        echo "Not Set";
                    else
                        echo $bookingdata['SubService'];
                ?>
            </td>
        </tr>
        <tr>
            <th>Hourly Price </th>
            <td>
                <?php 
                    if($hourly_service_booking_array['HourlyPrice'] == "")
                        echo "Not Set";
                    else
                        echo $hourly_service_booking_array['HourlyPrice'];
                ?>
            </td>
        </tr>

        <tr>
            <th>Start Date / Time </th>
            <td>
                <?php 
                    if($hourly_service_booking_array['StartDate'] == "" )
                        echo "N/A";
                    else
                        echo $hourly_service_booking_array['StartDate']." / ".$hourly_service_booking_array['StartTime'];
                ?>
            </td>
        </tr>


        <tr>
            <th>End Date / Time </th>
            <td>
                <?php 
                    if($hourly_service_booking_array['EndDate'] == "")
                        echo "N/A";
                    else
                       echo $hourly_service_booking_array['EndDate']." / ".$hourly_service_booking_array['EndTime'];
                ?>
            </td>
        </tr>

        <tr>
            <th>Duration</th>
            <td>
                <?php 
                    if($hourly_service_booking_array['Duration'] == "")
                        echo "N/A";
                    else
                        echo $hourly_service_booking_array['Duration'];
                ?>
            </td>
        </tr>

        <tr>
            <th>Total Amount </th>
            <td>
                <?php 
                    if($hourly_service_booking_array['TotalPrice'] == "")
                        echo "N/A";
                    else
                        echo $hourly_service_booking_array['TotalPrice'];
                ?>
            </td>
        </tr>

        <tr>
            <th>Created By</th>
            <td>
                <?php 
                    if($hourly_service_booking_array['CreatedBy'] == "")
                        echo "N/A";
                    else
                        echo $hourly_service_booking_array['CreatedBy'];
                ?>
            </td>
        </tr>

    </tbody>

</table>

<?php 
}
else
{
    if($bookingdata['SubService'] != ""){ 
        $sub_service_name = $bookingdata['SubService'];
        $filter = " Where title= '$sub_service_name' AND hourlyservice = 'Yes'";
        $check = check_unique_identity_filter($conn,'subservice', $filter);
        if(!$check){
?>

<div class="text-right">
    <a href="#" class="btn btn-info" style="margin-right:20px;" onclick="openhourlyDetails_modal()">Add Hourly
        Details</a>
</div>

<?php
        }
    }
 }
  ?>


<!-- modal start  -->

<div class="modal fade bd-example-modal-lg" id="add_hourly_service_details" role="dialog"
    aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header" style="background-color: #027dc1;color: #fff;">
                <div class="tab_modal_heading">
                    <h2>Hourly Service Details </h2>
                </div>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"
                    style="opacity: 1;color: #fff;">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="hourly_service_details_form">
                    <div id="wizard">
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label>Start Date <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control" name="start_date" id="start_date">
                            </div>

                            <div class="col-md-12 mb-3">
                                <label>End Date <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control" name="end_date" id="end_date">
                            </div>

                            <div class="col-md-12 mb-3">
                                <label>Start Time <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control" name="start_time" id="start_time">
                            </div>

                            <div class="col-md-12 mb-3">
                                <label>End Time <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control" name="end_time" id="end_time">
                            </div>

                            <div class="col-12 text-center">
                                <button id="company_btn" class="btn btn-primary"
                                    onclick="return AddHourlyDetailsTable()">Add </button>
                            </div>

                            <input type="hidden" name="BookingID" value='<?php echo $ID; ?>'>
                            <input type="hidden" name="SubServiceID" value='<?php echo $SubServiceID; ?>'>

                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- modal end  -->