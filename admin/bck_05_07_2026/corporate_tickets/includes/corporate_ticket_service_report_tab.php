<?php
error_reporting(E_ALL);
ini_set("display_errors", 1);
$service_report_obj = new Servicereport($conn);
$service_report_details = $service_report_obj->GetServiceReportDetails($ID);
$edit_service_report = 0;
if($UserType == "Admin" || $UserType == "Corporate Admin"|| $Accounts_Manager ||  $TicketManager || $CityLead)
{
    $edit_service_report = 1;
}

?>
<div class="row">
    <div class="col-xl-12">
        <div id="panel-1" class="panel">
            <div class="panel-hdr">
                <h2>
                    View Service Report Details &nbsp;&nbsp;
                </h2>

                <?php
                if($BookingStatus == "Closed" || $BookingStatus == "Work In Progress")
                {
                    if($service_report_details != null)
                    {
                        if($edit_service_report)
                        {
                        ?>
                            <button type="button" onclick="Open_GenerateReportModal(<?php echo $ID; ?>);" class="btn btn-sm btn-success ml-3 mr-3">Edit Service Report</button>
                        <?php
                        }
                        ?>
                         <button type="button" onclick="GenerateServiceReportPDF(<?php echo $service_report_details['ID']; ?>,'Download');" class="btn btn-sm btn-success ml-3 mr-3" id="donwload_report_pdf">Download</button>
                         <?php 
                        if($edit_service_report)
                        {
                        ?>
                         <button type="button" onclick="GenerateServiceReportPDF(<?php echo $service_report_details['ID']; ?>,'Send');" class="btn btn-sm btn-success ml-3 mr-3" id="send_report_pdf">Send</button>
                        <?php
                        }
                    }
                    else
                    {
                        if($edit_service_report)
                        {
                ?>
                        <button type="button" onclick="Open_GenerateReportModal(<?php echo $ID; ?>);" class="btn btn-sm btn-info ml-3 mr-3">Generate Service Report</button>
                <?php 
                        }
                    }
                } 
                ?>

            </div>



            <div class="panel-container show">
                <div class="panel-content" id="service_report_view">
                    <?php
                      if($service_report_details != null)
                      {
                        ?>
                        <input type="hidden" id="report_service_type" value="<?php echo $corporate_ticket_data['Type']; ?>" />
                    <div class="panel-container show">
                        <div class="panel-content p-0">
                            <table id="view-projects" class="table table-bordered table-hover table-striped w-100">
                                <thead>
                                    <tr>
                                    </tr>
                                </thead>
                                <tbody>

                                    <tr>
                                        <th>Ticket ID </th>
                                        <td>
                                            <?php echo $corporate_ticket_data['TicketID']; ?>
                                        </td>
                                    </tr>

                                    <tr>
                                        <th>Client Ticket Reference </th>
                                        <td>
                                            <?php echo $corporate_ticket_data['ClientTicketID']; ?>
                                        </td>
                                    </tr>

                                    <tr>
                                        <th>Problem Reported By Client</th>
                                        <td>
                                             <?php 
                                             if($service_report_details['ProblemReportedByClient'] != ""){
                                              echo $service_report_details['ProblemReportedByClient']; 
                                             }else{
                                                echo "N/A";
                                             }
                                              ?>
                                        </td>
                                    </tr>

                                   
                                    <tr>
                                        <th>Observation</th>
                                        <td>
                                             <?php 
                                             if($service_report_details['Observation'] != ""){
                                              echo $service_report_details['Observation']; 
                                             }else{
                                                echo "N/A";
                                             }
                                              ?>
                                        </td>
                                    </tr>

                                    <tr>
                                        <th>Action Taken</th>
                                        <td>
                                             <?php 
                                             if($service_report_details['ActionTaken'] != ""){
                                              echo $service_report_details['ActionTaken']; 
                                             }else{
                                                echo "N/A";
                                             }
                                              ?>
                                        </td>
                                    </tr>

                                    <!-- Service Report Details -->
                                    <?php
                                    if($corporate_ticket_data['Type'] == "AMC") 
                                    {
                                        if($corporate_ticket_data['CategoryName'] == "HVAC")
                                        {
                                            $ServiceReportID = $service_report_details['ID'];
                                            $where = " where ServiceReportID = $ServiceReportID";
                                            $hvac_general_service_report_data = _getTableDetails($conn,'hvac_general_service_report',$where);
                                            if($hvac_general_service_report_data != null)
                                            {
                                                ?>
                                                <tr>
                                                    <th>Asset Condition</th>
                                                    <td>
                                                         <?php 
                                                         if($hvac_general_service_report_data['AssetCondition'] != ""){
                                                          echo $hvac_general_service_report_data['AssetCondition']; 
                                                         }else{
                                                            echo "N/A";
                                                         }
                                                          ?>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th>Grill Temperature (C/F)</th>
                                                    <td>
                                                         <?php 
                                                         if($hvac_general_service_report_data['GrillTemperature'] != ""){
                                                          echo $hvac_general_service_report_data['GrillTemperature']; 
                                                         }else{
                                                            echo "N/A";
                                                         }
                                                          ?>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th>Ambient Temperature (C/F)</th>
                                                    <td>
                                                         <?php 
                                                         if($hvac_general_service_report_data['AmbientTemperature'] != ""){
                                                          echo $hvac_general_service_report_data['AmbientTemperature']; 
                                                         }else{
                                                            echo "N/A";
                                                         }
                                                          ?>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th>Compressor current(AMPS)</th>
                                                    <td>
                                                         <?php 
                                                         if($hvac_general_service_report_data['Compressor'] != ""){
                                                          echo $hvac_general_service_report_data['Compressor']; 
                                                         }else{
                                                            echo "N/A";
                                                         }
                                                          ?>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th>Voltage (Volts)</th>
                                                    <td>
                                                         <?php 
                                                         if($hvac_general_service_report_data['Voltage'] != ""){
                                                          echo $hvac_general_service_report_data['Voltage']; 
                                                         }else{
                                                            echo "N/A";
                                                         }
                                                          ?>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th>Room Temperature (C/F)</th>
                                                    <td>
                                                         <?php 
                                                         if($hvac_general_service_report_data['RoomTemperature'] != ""){
                                                          echo $hvac_general_service_report_data['RoomTemperature']; 
                                                         }else{
                                                            echo "N/A";
                                                         }
                                                          ?>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th>Indoor Fan Motor Current (Amps)</th>
                                                    <td>
                                                         <?php 
                                                         if($hvac_general_service_report_data['IndoorFan'] != ""){
                                                          echo $hvac_general_service_report_data['IndoorFan']; 
                                                         }else{
                                                            echo "N/A";
                                                         }
                                                          ?>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th>Compressor Discharge Pressure (PSI)</th>
                                                    <td>
                                                         <?php 
                                                         if($hvac_general_service_report_data['CompressorDischarge'] != ""){
                                                          echo $hvac_general_service_report_data['CompressorDischarge']; 
                                                         }else{
                                                            echo "N/A";
                                                         }
                                                          ?>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th>Return Air Temperature (C/F)</th>
                                                    <td>
                                                         <?php 
                                                         if($hvac_general_service_report_data['ReturnAirTemperature'] != ""){
                                                          echo $hvac_general_service_report_data['ReturnAirTemperature']; 
                                                         }else{
                                                            echo "N/A";
                                                         }
                                                          ?>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th>Supply Air Temperature (C/F)</th>
                                                    <td>
                                                         <?php 
                                                         if($hvac_general_service_report_data['SupplyAirTemperature'] != ""){
                                                          echo $hvac_general_service_report_data['SupplyAirTemperature']; 
                                                         }else{
                                                            echo "N/A";
                                                         }
                                                          ?>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th>Total Current (Amps)</th>
                                                    <td>
                                                         <?php 
                                                         if($hvac_general_service_report_data['TotalCurrent'] != ""){
                                                          echo $hvac_general_service_report_data['TotalCurrent']; 
                                                         }else{
                                                            echo "N/A";
                                                         }
                                                          ?>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th>Condenser Air Outlet (C/F)</th>
                                                    <td>
                                                         <?php 
                                                         if($hvac_general_service_report_data['CondenserAirOutlet'] != ""){
                                                          echo $hvac_general_service_report_data['CondenserAirOutlet']; 
                                                         }else{
                                                            echo "N/A";
                                                         }
                                                          ?>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th>Outdoor Fan Motor Current (Amps)</th>
                                                    <td>
                                                         <?php 
                                                         if($hvac_general_service_report_data['OutdoorFan'] != ""){
                                                          echo $hvac_general_service_report_data['OutdoorFan']; 
                                                         }else{
                                                            echo "N/A";
                                                         }
                                                          ?>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th>Compressor Suction Pressure (PSI)</th>
                                                    <td>
                                                         <?php 
                                                         if($hvac_general_service_report_data['CompressorSuction'] != ""){
                                                          echo $hvac_general_service_report_data['CompressorSuction']; 
                                                         }else{
                                                            echo "N/A";
                                                         }
                                                          ?>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th>Condenser Air Inlet (C/F)</th>
                                                    <td>
                                                         <?php 
                                                         if($hvac_general_service_report_data['CondenserAirInlet'] != ""){
                                                          echo $hvac_general_service_report_data['CondenserAirInlet']; 
                                                         }else{
                                                            echo "N/A";
                                                         }
                                                          ?>
                                                    </td>
                                                </tr>
                                                <?php
                                            }
                                        }
                                    }

                                    ?>


                                    <tr>
                                        <th>Remarks</th>
                                        <td>
                                             <?php 
                                             if($service_report_details['Remarks'] != ""){
                                              echo $service_report_details['Remarks']; 
                                             }else{
                                                echo "N/A";
                                             }
                                              ?>
                                        </td>
                                    </tr>

                                    <tr>
                                        <th>Client Representative</th>
                                        <td>
                                             <?php 
                                             if($service_report_details['ClientRepresentative'] != ""){
                                              echo $service_report_details['ClientRepresentative']; 
                                             }else{
                                                echo "N/A";
                                             }
                                              ?>
                                        </td>
                                    </tr>

                                    <tr>
                                        <th>Client Representative Contact</th>
                                        <td>
                                             <?php 
                                             if($service_report_details['ClientRepresentativeContact'] != ""){
                                              echo $service_report_details['ClientRepresentativeContact']; 
                                             }else{
                                                echo "N/A";
                                             }
                                              ?>
                                        </td>
                                    </tr>

                                    <tr>
                                        <th>Client Representative Emails</th>
                                        <td>
                                             <?php 
                                             if($service_report_details['ClientRepresentativeEmails'] != ""){
                                              echo $service_report_details['ClientRepresentativeEmails']; 
                                             }else{
                                                echo "N/A";
                                             }
                                              ?>
                                        </td>
                                    </tr>

                                    <tr>
                                        <th>Client Representative Designation</th>
                                        <td>
                                             <?php 
                                             if($service_report_details['ClientRepresentativeDesignation'] != ""){
                                              echo $service_report_details['ClientRepresentativeDesignation']; 
                                             }else{
                                                echo "N/A";
                                             }
                                              ?>
                                        </td>
                                    </tr>

                                    <tr>
                                        <th>Client Signature</th>
                                                <td>
                                                    <?php 
                                                    if($service_report_details['ClientSignature'] != ""){
                                                        $ClientSignature = $service_report_details['ClientSignature'];
                                                        $imageDate = $service_report_details['CreatedDate'];
                                                        $imageTime = $service_report_details['CreatedTime'];
                                                        
                                                        echo '<div style="width:100px; height:200px; margin-top:10px;" class="client_signature">
                                                                <img style="width:100px;" src="../media/signature/'.$ClientSignature.'">
                                                              </div>
                                                              <div class="mt-2 d-flex">
                                                                <span class="badge bg-warning">'.$imageDate.'</span>
                                                                <span class="badge bg-info me-3">'.$imageTime.'</span>
                                                              </div>';

                                                    } else {
                                                        echo "N/A";
                                                    }
                                                    ?>
                                                </td>

                                    </tr>

                                    <tr>
                                        <th>Created Date</th>
                                        <td>
                                             <?php 
                                             if($service_report_details['CreatedDate'] != ""){
                                              echo $service_report_details['CreatedDate']; 
                                             }else{
                                                echo "N/A";
                                             }
                                              ?>
                                        </td>
                                    </tr>

                                    <tr>
                                        <th>Created Time</th>
                                        <td>
                                             <?php 
                                             if($service_report_details['CreatedTime'] != ""){
                                              echo $service_report_details['CreatedTime']; 
                                             }else{
                                                echo "N/A";
                                             }
                                              ?>
                                        </td>
                                    </tr>

                                    <tr>
                                        <th>Created By</th>
                                        <td>
                                             <?php 
                                             if($service_report_details['CreatedBy'] != ""){
                                              echo $service_report_details['CreatedBy']; 
                                             }else{
                                                echo "N/A";
                                             }
                                              ?>
                                        </td>
                                    </tr>
                                      

                                </tbody>

                            </table>

                            <div class="text-right">
                                <!--a href="#" class="btn btn-danger" style="margin-right:20px;" data-toggle="modal"
                data-target="#editaccess">Delete</a-->
                            </div>
                        </div>
                    </div>

                    <?php  }
                          else
                          {
                            ?>
                    <h5 class='text-center'> Currently there are no Service Report Details associated</h2>
                        <?php
                          }
                          ?>

                </div>
            </div>

        </div>
        <!-- panel-1 -->
    </div><!-- col-xl-12 -->
</div> <!-- row -->


<div class="modal fade bd-example-modal-sm" id="generate_service_report_modal" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header" style="background-color: #027dc1; color: #fff;">
                <div class="tab_modal_heading">
                    <h2>Update Service Details</h2>
                </div>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"
                    style="opacity: 1; color: #fff;">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form method="post" id="generate_service_report_form" onsubmit="return false;">
                    <input type="hidden" name = "ServiceReportTicketID"  id="ServiceReportTicketID" value="<?php echo $ID;?>" />
                    <input type="hidden" name = "ServiceReportID" id="ServiceReportID" value="-1" />
                    <input type="hidden" name = "ServiceReportAction" id="ServiceReportAction" value="Draft" />
                    <div class="row">
                        <div class="form-group col-md-6 col-12">
                            <label for="recipient-name" class="col-form-label">Techxpert Ticket No. <span
                                    class="text-danger">*<span></span></span></label>
                            <input type="text" class="form-control" name="techxpert_ticket_number" id="ser_ticket_number"
                                readonly="true">
                        </div>
                        <div class="form-group col-md-6 col-12">
                            <label for="recipient-name" class="col-form-label">Client Ticket Reference No. </span></span></label>
                            <input type="text" class="form-control" name="client_ticket_reference_number"
                                id="ser_client_ticket_reference_number" readonly="true">
                        </div>
                        <div class="form-group col-md-6 col-12">
                            <label for="recipient-name" class="col-form-label">Registered Date <span
                                    class="text-danger">*<span></span></span></label>
                            <input type="text" class="form-control" name="RegisteredDate"
                                id="ser_registered_date" placeholder="Enter Completed Date" readonly="true">
                        </div>
                        
                        <div class="form-group col-md-6 col-12">
                            <label for="recipient-name" class="col-form-label">Completed Date <span
                                    class="text-danger">*<span></span></span></label>
                            <input type="text" class="form-control" name="CompletedDate"
                                id="ser_completed_date" placeholder="Enter Completed Date" readonly="true">
                        </div>

                        <div class="form-group col-md-12">
                            <label for="recipient-name" class="col-form-label">Problem Reported by Client: <span
                                    class="text-danger">*<span></span></span></label>
                            <textarea class="form-control" placeholder="Please Enter Problem Reported" rows="2"
                                name="ProblemReportedByClient" id="ser_problem_reported"></textarea>
                        </div>
                        <div class="form-group col-md-12">
                            <label for="recipient-name" class="col-form-label">Observation: <span
                                    class="text-danger">*<span></span></span></label>
                            <textarea class="form-control" placeholder="Please Enter Observation" rows="2"
                                name="ser_observation" id="ser_observation"></textarea>
                        </div>
                        <div class="form-group col-md-12">
                            <label for="recipient-name" class="col-form-label">Action Taken: <span
                                    class="text-danger">*<span></span></span></label>
                            <textarea class="form-control" placeholder="Please Enter Action Taken" rows="2"
                                name="ActionTaken" id="action_taken"></textarea>
                        </div>

                        <div class="form-group col-md-12">
                            <label for="recipient-name" class="col-form-label">Remarks: </label>
                            <textarea class="form-control" placeholder="Please Enter Remarks" rows="2" name="Remarks"
                                id="ser_remarks"></textarea>
                        </div>


                        <div class="form-group col-md-6 col-12">
                            <label for="recipient-name" class="col-form-label">Onsite Client Representative Name
                                (Verified By) </label>
                            <input type="text" class="form-control" name="ClientRepresentative" id="ser_onsite_client"
                                placeholder="Enter Onsite Client Representative Name (Verified By)">
                        </div>

                        <div class="form-group col-md-6 col-12">
                            <label for="recipient-name" class="col-form-label">Client Representative Contact </label>
                            <input type="text" class="form-control" name="ClientRepresentativeContact"
                                id="ser_onsite_client_contact" placeholder="Enter Client Representative Contact">
                        </div>

                        <div class="form-group col-md-6 col-12">
                            <label for="recipient-name" class="col-form-label">Client Representative Emails </label>
                            <input type="text" class="form-control" name="ClientRepresentativeEmails"
                                id="ser_onsite_client_email" placeholder="Enter Client Representative Emails">
                        </div>

                        <div class="form-group col-md-6 col-12">
                            <label for="recipient-name" class="col-form-label">Client Representative Designation</label>
                            <input type="text" class="form-control" name="ClientRepresentativeDesignation"
                                id="ser_onsite_client_designation" placeholder="Enter Client Representative Designation">
                        </div>



                        <div class="mt-5 col-12 text-center">
                            <button class="btn btn-success text-white" id="addUpdateServiceBtn" onclick="AddUpdateServiceReport('Submit')">Save</button>
                            
                            <!-- <a href="#"  class="btn btn-success">Submit</a> -->
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>