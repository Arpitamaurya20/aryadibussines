<?php
$TicketQuotationApproval = 0;
if($UserType == "Corporate Admin")
{
    $TicketQuotationApproval = 1;
}
elseif($UserType == "Corporate User")
{
    $CorporateID = $_SESSION['Roles']['CorporateID'];
    //Get that person range
    $username = $_SESSION['pb_username'];
    $where_price_check = " where Email = '$username'";
    $corporate_users_detail = _getTableDetails($conn,'corporate_users',$where_price_check);
    $ApprovalMinRange = intval($corporate_users_detail['ApprovalMinRange']);
    $ApprovalMaxRange = intval($corporate_users_detail['ApprovalMaxRange']);
    
    //Check the customer price of the ticket
    if(isset($corporate_users_detail['CustomerPrice'])){
        if(intval($corporate_users_detail['CustomerPrice']) >=  $ApprovalMinRange && intval($corporate_users_detail['CustomerPrice']) <=  $ApprovalMaxRange)
        {
            $TicketQuotationApproval = 1;
        }
        //assign approval

    }
    
}
?>
<div class="panel-container show">
    <div class="panel-content p-0">
        <table id="view-finance" class="table table-bordered table-hover table-striped w-100">
            <tbody>
                <?php
                if(!$CityLead)
                {
                ?>
                <tr>
                    <th>Service Call Type </th>
                    <td>
                        <?php 
                        if($corporate_ticket_data['CallType'] == ""){
                            echo "Not Set";
                        }else{
                            echo $corporate_ticket_data['CallType'];
                        }
                         
                        ?>
                    </td>
                </tr>

                <tr>
                    <th>Customer Price</th>
                    <td>
                        <?php
                        if($corporate_ticket_data['CustumerPrice'] == ""){
                          echo "Not Set";
                        }else{
                            echo $corporate_ticket_data['CustumerPrice']; 
                        }
                         
                         ?>
                    </td>

                </tr>

                 <?php if($UserType == "Admin" || $CityLead == "1" || $TicketManager){?>
                <tr>
                    <th>Expense Price</th>
                    <td>
                        <?php 
                        if($corporate_ticket_data['ExpensePrice'] == ""){
                            echo "Not Set";
                        }else{
                            echo $corporate_ticket_data['ExpensePrice'];
                        }
                         
                        ?>
                    </td>

                </tr>

                <?php } ?>

                <tr>
                    <th>Description</th>
                    <td>
                        <?php 
                        if($corporate_ticket_data['Description'] == ""){

                            echo "Not Set";

                        }else{
                            echo $corporate_ticket_data['Description'];
                        }
                         
                        ?>
                    </td>

                </tr>
                 <tr>
                    <th>Quotation</th>
                    <td>
                    <?php
                        $extension = pathinfo($corporate_ticket_data['QuotationUpload'], PATHINFO_EXTENSION);
                        if($corporate_ticket_data['QuotationUpload'] == "") {
                            echo "Not Set";

                        } else {
                                if ($extension == 'pdf') {
                                  echo $corporate_ticket_data['QuotationUpload'];
                                }
                                elseif($extension != 'pdf')
                                {
                            ?>
                            <img src="media/<?php echo $corporate_ticket_data['QuotationUpload'];?>" alt=" " height="75" width="75">

                             <?php
                                }
                            ?>
                            <div>
                                <a class="badge badge-primary mt-3 ml-2" href="download-quotation.php?ID=<?php echo $corporate_ticket_data['QuotationUpload']; ?>" >
                                    Download
                                </a>
                            </div>
                         <?php
                            }
                         ?>   
                    </td>
                    <?php
                    if($UserType == "Admin" || $CityLead == 1 || $TicketManager){
                        if($corporate_ticket_data['CallType'] !== "Under AMC" &&  $corporate_ticket_data['CallType'] !== ""){
                         if($corporate_ticket_data['QuotationStatus'] == "NA" || $corporate_ticket_data['QuotationStatus'] == "Rejected"){
                         ?>
                    }
                    <td>
                        <span class="btn btn-primary" onclick="UploadQuotation()">Upload Quotation</span>
                    </td>

                    <?php
                          }
                         }
                     }
                     ?>

                </tr>

                <?php
                 if($UserType == "Corporate Branch User" || $UserType == "Corporate Admin" || $UserType == "Admin" || $UserType == "Corporate User"){

                 if(($corporate_ticket_data['CallType'] !== "Under AMC" && $corporate_ticket_data['QuotationStatus'] == "Approval Pending") && ($TicketQuotationApproval==1)){
                         ?>
                    <tr>
                        <th>Quotation Action</th>
                        <td>
                            <span class="btn btn-primary mr-3" onclick="ApproveQoutation(<?php echo $ID;?>)">Approve</span>
                            <span class="btn btn-danger" onclick="RejectedQuotation(<?php echo $ID;?>)">Reject</span>
                        </td>
                    </tr>     
                    

                <?php }} ?>
                     
                <tr>
                    <th>Quotation Status</th>
                    <td>
                        <a class="badge badge-primary" href=""><?php echo $corporate_ticket_data['QuotationStatus'];?></a>
                    </td>

                </tr> 
                <?php if($corporate_ticket_data['CallType'] !== "Under AMC" && $corporate_ticket_data['QuotationStatus'] !== "NA"){
                         ?>
                <tr>
                    <th>Ticket Chat</th>
                    <td>
                        <span class="p-2 badge badge-primary cursor-pointer" onclick="OpenConversation()">Open Conversation</span>
                    </td>

                </tr> 
                <?php } ?>

                <?php if($corporate_ticket_data['QuotationStatus'] == "Rejected"){ ?>
               
                <tr>
                    <th>Rejected Quotation</th>
                    <td class="d-flex">
                    <?php
                        $i = 0;
                        foreach($Reject_data_array as $Reject_data_value){
                        $extension = pathinfo($Reject_data_value['Quotation'], PATHINFO_EXTENSION);
                        if($Reject_data_value['Quotation'] == "") {
                            echo "Not Set";

                        } else {
                                if ($extension == 'pdf') {
                                  echo $Reject_data_value['Quotation'];
                                }
                                elseif($extension != 'pdf')
                                {
                            ?>
                                    
                            <div class="mr-3 mb-4">
                              <img src="media/<?php echo $Reject_data_value['Quotation'];?>" alt=" " height="75" width="75">

                             <?php
                                }
                            ?>
                                <div>
                                    <a class="badge badge-primary mt-3 ml-2" href="download-quotation.php?ID=<?php echo $Reject_data_value['Quotation']; ?>" >
                                        Download
                                    </a>
                                </div>
                            </div>
                         <?php
                            }
                            $i++;
                            }
                         ?>   
                    </td>

                </tr>

            <?php 
                }
            } // Not city lead
            ?>
            <tr>
                <td colspan="2">
                   <b style="font-size: 22px"> Ticket Finances &nbsp;&nbsp;</b>
                    <?php 
                    $T_VisitorNo = $T_VisitCharge = $T_MaterialCost = $T_LabourCost = $T_TotalPrice = $C_VisitorNo = $C_VisitCharge = $C_MaterialCost = $C_LabourCost = $C_TotalPrice = "";
                    if($Ticket_finance_data == null && ($Finance_Manager || $TicketManager || $CFO || ($UserType == "Admin")))
                    {
                        ?>
                            <a class="badge badge-primary text-white" onclick="EditTicketFinances('add');">Add</a>
                        <?php
                    }
                    else
                    {
                        $T_VisitorNo = $Ticket_finance_data['T_VisitorNo'];
                        $T_VisitCharge = $Ticket_finance_data['T_VisitCharge'];
                        $T_MaterialCost = $Ticket_finance_data['T_MaterialCost'];
                        $T_LabourCost = $Ticket_finance_data['T_LabourCost'];
                        $T_TotalPrice = $Ticket_finance_data['T_TotalPrice'];
                        $C_VisitorNo = $Ticket_finance_data['C_VisitorNo'];
                        $C_VisitCharge = $Ticket_finance_data['C_VisitCharge'];
                        $C_MaterialCost = $Ticket_finance_data['C_MaterialCost'];
                        $C_LabourCost = $Ticket_finance_data['C_LabourCost'];
                        $C_TotalPrice = $Ticket_finance_data['C_TotalPrice'];
                        $TicketFinanceStatus = $Ticket_finance_data['Status'];
                        $Remarks = $Ticket_finance_data['Remarks'];
                        if(($UserType == "Admin" || $UserType == "Sub-Admin"  || $CFO)|| $Ticket_finance_data['Status'] == 10 ||($TicketManager && $Ticket_finance_data['Status'] == 10) || $Ticket_finance_data['Status'] == 0)
                        {
                        ?>
                            <a class="badge badge-primary text-white" onclick="EditTicketFinances('edit');">Edit</a>
                        <?php
                        }
                        ?>
                            <span class="mt-3 d-block" style="font-size: 18px"> <b>Status : </b> <?php echo $finance_status_array[$TicketFinanceStatus]; ?> </span>
                        <?php
                        if(($Ticket_finance_data['Status'] == 1) && ($UserType == "Admin" || $Accounts_Manager))
                        {
                        ?>
                            <a class="badge badge-primary text-white" onclick="UpdateTicketStatus(2);">Update Status</a>
                        <?php
                        }
                        if(($Ticket_finance_data['Status'] == 2) && ($Finance_Manager || $UserType == "Admin" ))
                        {
                        ?>
                            <a class="badge badge-primary text-white" onclick="UpdateTicketStatus(3);">Update Status</a>
                        <?php
                        }
                        if(($Ticket_finance_data['Status'] == 3) && ($CFO || $UserType == "Admin" ))
                        {
                        ?>
                            <a class="badge badge-primary text-white" onclick="UpdateTicketStatus(4);">Update Status</a>
                        <?php
                        }
                        if(($Ticket_finance_data['Status'] == 4) && ($Accounts_Manager || $UserType == "Admin" ))
                        {
                        ?>
                            <a class="badge badge-primary text-white" onclick="UpdateTicketStatus(5);">Update Status</a>
                        <?php
                        }
                    }
                    ?>

                    
                </td> 

            <?php if($UserType == "Admin" || $UserType == "Sub-Admin" || $Finance_Manager || $Accounts_Manager || $Procurement_Manager || $CityLead || $CFO || $Account_Manager || $TicketManager)
            {
            ?>
                            
                            <tr>
                                <th>No. Of Visits (Customer)</th>
                                <td>
                                    <?php 
                                    if(isset($Ticket_finance_data['C_VisitorNo']))
                                        echo $Ticket_finance_data['C_VisitorNo'];
                                    else
                                        echo "Not Set";
                                    ?>
                                </td>
                            </tr>

                            <tr>
                                <th>Visit Charge (Customer)</th>
                                <td>
                                    <?php 
                                    if(isset($Ticket_finance_data['C_VisitCharge']))
                                        echo "&#8377;".$Ticket_finance_data['C_VisitCharge'];
                                    else
                                        echo "Not Set";
                                    ?>
                                </td>
                            </tr>

                            <tr>
                                <th>Material Cost (Customer)</th>
                                <td>
                                    <?php 
                                    if(isset($Ticket_finance_data['C_MaterialCost']))
                                        echo "&#8377;".$Ticket_finance_data['C_MaterialCost'];
                                    else
                                        echo "Not Set";
                                    ?>
                                </td>
                            </tr>
                            <tr>
                                <th>Labour Cost (Customer)</th>
                                <td>
                                    <?php 
                                    if(isset($Ticket_finance_data['C_LabourCost']))
                                        echo "&#8377;".$Ticket_finance_data['C_LabourCost'];
                                    else
                                        echo "Not Set";
                                    ?>
                                </td>
                            </tr>
                            
                            <tr>
                                <th>Total Price (Customer)</th>
                                <td>
                                    <?php 
                                    if(isset($Ticket_finance_data['C_TotalPrice']))
                                        echo "<b>&#8377;".$Ticket_finance_data['C_TotalPrice']."</b>";
                                    else
                                        echo "Not Set";
                                    ?>
                                </td>
                            </tr>
                            <tr>
                                <th>No. Of Visits (Internal)</th>
                                <td>
                                    <?php 
                                    if(isset($Ticket_finance_data['T_VisitorNo']))
                                        echo $Ticket_finance_data['T_VisitorNo'];
                                    else
                                        echo "Not Set";
                                    ?>
                                </td>
                                
                            </tr>

                            <tr>
                                <th>Visit Charge (Internal)</th>
                                <td>
                                    <?php 
                                    if(isset($Ticket_finance_data['T_VisitCharge']))
                                        echo "&#8377;".$Ticket_finance_data['T_VisitCharge'];
                                    else
                                        echo "Not Set";
                                    ?>
                                </td>
                            </tr>

                            <tr>
                                <th>Material Cost (Internal)</th>
                                <td>
                                    <?php 
                                    if(isset($Ticket_finance_data['T_MaterialCost']))
                                        echo "&#8377;".$Ticket_finance_data['T_MaterialCost'];
                                    else
                                        echo "Not Set";
                                    ?>
                                </td>
                            </tr>
                            <tr>
                                <th>Labour Cost (Internal)</th>
                                <td>
                                    <?php 
                                    if(isset($Ticket_finance_data['T_LabourCost']))
                                        echo "&#8377;".$Ticket_finance_data['T_LabourCost'];
                                    else
                                        echo "Not Set";
                                    ?>
                                </td>
                            </tr>
                          
                           
                            <tr>
                                <th>Total Price (Internal)</th>
                                <td>
                                    <?php 
                                    if(isset($Ticket_finance_data['T_TotalPrice']))
                                        echo "<b>&#8377;".$Ticket_finance_data['T_TotalPrice']."</b>";
                                    else
                                        echo "Not Set";
                                    ?>
                                </td>
                            </tr>
                            <tr>
                                <th>Remarks</th>
                                <td>
                                    <?php 
                                    if(isset($Ticket_finance_data['Remarks']))
                                        echo $Ticket_finance_data['Remarks'];
                                    else
                                        echo "Not Set";
                                    ?>
                                </td>
                            </tr>

                <?php 
            } 
            ?>
            </tbody>
        </table>


        

        <?php
        if(!$CityLead)
        {
        ?>
        <div class="text-right">
            <?php if($corporate_ticket_data['QuotationStatus'] !== "Approved"){
                if ($UserType == "Admin") {
                         ?>
            <a href="#" class="btn btn-primary" onclick="OpenFinanceModal('<?php echo $corporate_ticket_data['CallType']; ?>')" style="margin-right:20px;">Add & Edit Finance Details</a>
            <?php } }?>
        </div>
        <?php
        }
        ?>

        

    </div>
</div>



<!-- <div class="comming_soon w-100">
    <img class="w-100" src="../img/coming-soon.png" alt="">
</div> -->


<!-- finance modal -->

<div class="modal fade bd-example-modal-lg" id="finance_modal" role="dialog" aria-labelledby="exampleModalLabel"
    aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header" style="background-color: #027dc1; color: #fff;">
                <div class="tab_modal_heading">
                    <h2>Edit Ticket Finance Details </h2>
                </div>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="opacity: 1; color: #fff;">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form method="post" id="ticket_finance_form">
                    <div id="wizard">
                        <section>
                            <div class="row">

                                <div class="col-lg-12">
                                        <div class="form-group">
                                                <label class="form-label" for="name">Service Call Type</label>
                                                <select class="select2 form-control w-100" name="CallType" id="service_call_type" value="<?php echo $corporate_ticket_data['CallType']; ?>">
                                                   
                                                    <option value="">Please Select Service Call Type</option>
                                                    <option value="OTR">OTR</option>
                                                    <option value="OCB">OCB</option>
                                                    <option value="Under AMC">Under AMC</option>
                                                </select>
                                        </div>
                                 </div>

                                 
                                 <div class="col-md-12 col-12">
                                    <div class="form_div form-group"> <label for="CustomerPrice">Customer Price
                                        </label>
                                        <input type="text" class="form-control" name="CustumerPrice"
                                                        id="CustumerPrice" placeholder="Please Fill Customer Price" value="<?php echo $corporate_ticket_data['CustumerPrice']; ?>">

                                    </div>
                                </div>

                                <div class="col-md-12 col-12">
                                    <div class="form_div form-group"> <label for="ExpensePrice">Expense Price
                                        </label>
                                        <input type="text" class="form-control" name="ExpensePrice" id="ExpensePrice" placeholder="Please Fill Expense Price" value="<?php echo $corporate_ticket_data['ExpensePrice']; ?>">

                                    </div>
                                </div>

                                <div class="col-lg-12">
                                            <div class="form-group">
                                                <label class="form-label" for="name">Description </label>
                                                <textarea class="form-control w-100" name="Description" id="Description" cols="30" rows="3" placeholder="Type Your Message"><?php echo $corporate_ticket_data['Description']; ?></textarea>

                                            </div>
                                </div>

                                
                                <input type="hidden" name="TicketID" value="<?php echo $ID;?>" />


                            </div>
                            <section>
                                <div class="row justify-content-center mt-3">
                                    <a class="form_btn form_submit pl-4 pr-4 pt-2 pb-2 text-white cursor-pointer" id="finance_change_btn"
                                        style="background-color: #2196f3;" onclick="UpdateTicketFinance()"
                                        value="Save">Update</a>
                                </div>
                            </section>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>


<!-- Ticket Finance Status Approval Modal -->
<div class="modal fade bd-example-modal-lg" id="finance_status_modal" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header" style="background-color: #027dc1; color: #fff;">
                <div class="tab_modal_heading">
                    <h2>Edit Ticket Finance Status </h2>
                </div>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="opacity: 1; color: #fff;">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form method="post" id="ticket_finance_status_form">
                    <div id="wizard">
                        <section>
                            <div class="row">

                                <div class="col-lg-12">
                                            <div class="form-group">
                                                <label class="form-label" for="name">Remarks </label>
                                                <textarea class="form-control w-100" name="remarks_ticket_status" id="remarks_ticket_status" cols="30" rows="3" placeholder="Type Your Remarks"></textarea>

                                            </div>
                                </div>

                                
                                <input type="hidden" name="TicketID" value="<?php echo $ID;?>" />
                                <input type="hidden" name="TicketNextStatus" id="TicketNextStatus" value="" />
                                <input type="hidden" name="TicketApprovalStatus" id="TicketApprovalStatus" value="" />

                            </div>
                            <section>
                                <div class="row justify-content-center mt-3">
                                    <a class="form_btn form_submit pl-4 pr-4 pt-2 pb-2 text-white cursor-pointer" 
                                        style="background-color: #2196f3;" onclick="ApproveTicketFinanceStatus(1)" id="ticket_finance_modal_approval_button"
                                        value="Save">Approve</a>
                                    <a class="form_btn form_submit pl-4 pr-4 pt-2 pb-2 text-white cursor-pointer ml-2 btn-danger" 
                                        style="background-color: #2196f3;" onclick="ApproveTicketFinanceStatus(-1)" id="ticket_finance_modal_reject_button"
                                        value="Save">Reject</a>
                                    <a class="form_btn form_submit pl-4 pr-4 pt-2 pb-2 text-white cursor-pointer ml-2 btn-danger" 
                                        style="background-color: #2196f3;" onclick="ApproveTicketFinanceStatus(10)" id="ticket_finance_modal_reject_button"
                                        value="Save">Negotiate</a>
                                </div>
                            </section>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Ticket Finances Modal -->

<div class="modal fade bd-example-modal-lg" id="ticket_finance_modal" role="dialog" aria-labelledby="exampleModalLabel"
    aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header" style="background-color: #027dc1; color: #fff;">
                <div class="tab_modal_heading">
                    <h2>Edit Ticket Finance Details </h2>
                </div>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="opacity: 1; color: #fff;">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form method="post" id="n_ticket_finance_form">
                    <div id="wizard">
                        <section>
                            <div class="row">
                             
                                 <div class="col-md-6 col-6">
                                    <div class="form_div form-group"> 
                                        <label>No. Of Visits (Customer)</label>
                                        <input type="text" class="form-control" name="C_VisitorNo" id="customer_no_of_visits" placeholder="No. Of Visits" value="<?php echo $C_VisitorNo; ?>">
                                    </div>
                                </div>

                                <div class="col-md-6 col-6">
                                    <div class="form_div form-group"> 
                                        <label>No. Of Visits (Techxpert)</label>
                                        <input type="text" class="form-control" name="T_VisitorNo" id="self_no_of_visits" placeholder="No. Of Visits" value="<?php echo $T_VisitorNo; ?>">
                                    </div>
                                </div>

                                <div class="col-md-6 col-6">
                                    <div class="form_div form-group"> 
                                        <label>Visit Charge (Customer)</label>
                                        <input type="text" class="form-control" name="C_VisitCharge" id="customer_visit_charge" placeholder="Visit Charge" value="<?php echo $C_VisitCharge; ?>">
                                    </div>
                                </div>
                                <div class="col-md-6 col-6">
                                    <div class="form_div form-group"> 
                                        <label>Visit Charge (Techxpert)</label>
                                        <input type="text" class="form-control" name="T_VisitCharge" id="self_visit_charge" placeholder="Visit Charge" value="<?php echo $T_VisitCharge; ?>">
                                    </div>
                                </div>

                                <div class="col-md-6 col-6">
                                    <div class="form_div form-group"> 
                                        <label>Material Cost (Customer)</label>
                                        <input type="text" class="form-control" name="C_MaterialCost" id="customer_material_cost" placeholder="Material Cost" value="<?php echo $C_MaterialCost; ?>">
                                    </div>
                                </div>
                                <div class="col-md-6 col-6">
                                    <div class="form_div form-group"> 
                                        <label>Material Cost (Techxpert)</label>
                                        <input type="text" class="form-control" name="T_MaterialCost" id="self_material_cost" placeholder="Material Cost" value="<?php echo $T_MaterialCost; ?>">
                                    </div>
                                </div>

                                <div class="col-md-6 col-6">
                                    <div class="form_div form-group"> 
                                        <label>Labour Cost (Customer)</label>
                                        <input type="text" class="form-control" name="C_LabourCost" id="customer_labour_cost" placeholder="Labour Cost" value="<?php echo $C_LabourCost; ?>">
                                    </div>
                                </div>
                                <div class="col-md-6 col-6">
                                    <div class="form_div form-group"> 
                                        <label>Labour Cost (TechXpert)</label>
                                        <input type="text" class="form-control" name="T_LabourCost" id="self_labour_cost" placeholder="Labour Cost" value="<?php echo $T_LabourCost; ?>">
                                    </div>
                                </div>

                                <div class="col-md-6 col-6">
                                    <div class="form_div form-group"> 
                                        <label>Total Cost (Customer)</label>
                                        <input type="text" class="form-control" name="C_TotalPrice" id="total_cost_customer" readonly="true" value="<?php echo $C_TotalPrice; ?>">
                                    </div>
                                </div>

                                <div class="col-md-6 col-6">
                                    <div class="form_div form-group"> 
                                        <label>Total Cost (TechXpert)</label>
                                        <input type="text" class="form-control" name="T_TotalPrice" id="total_cost_self" readonly="true" value="<?php echo $T_TotalPrice; ?>">
                                    </div>
                                </div>

                                
                                <input type="hidden" name="TicketID" value="<?php echo $ID;?>" />
                                <input type="hidden" name="form_action" id="form_action" value="add" />


                            </div>
                            <section>
                                <div class="row justify-content-center mt-3">
                                    <a class="form_btn form_submit pl-4 pr-4 pt-2 pb-2 text-white cursor-pointer" id="ticket_finance_change_btn"
                                        style="background-color: #2196f3;" onclick="SaveTicketFinances()"
                                        value="Save">Save</a>
                                </div>
                            </section>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>


<!-- quotation modal  -->

<div class="modal fade bd-example-modal-lg" id="quotation_modal" role="dialog" aria-labelledby="exampleModalLabel"
    aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header" style="background-color: #027dc1; color: #fff;">
                <div class="tab_modal_heading">
                    <h2>Upload Quotation</h2>
                </div>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="opacity: 1; color: #fff;">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form method="post" id="ticket_quotation_form">
                    <div id="wizard">
                            <div class="row">
                                
                                <div class="col-md-12 col-12">
                                    <div class="form_div form-group"> <label  class="form-label" for="quotation_upload">Quotation Upload </label>
                                        <input type="file" class="form-control" accept=".pdf" id="quotation_upload" name="quotation_upload" value="<?php echo $corporate_ticket_data['QuotationUpload']; ?>" >
                                    </div>
                                </div>

                                <input type="hidden" name="TicketID" value="<?php echo $ID;?>" />


                            </div>
                            <section>
                                <div class="row justify-content-center mt-3">
                                    <a class="form_btn form_submit pl-4 pr-4 pt-2 pb-2 text-white cursor-pointer" id="upload_change_btn"
                                        style="background-color: #2196f3;" onclick="UploadQuotationAction()"
                                        value="Save">Upload</a>
                                </div>
                            </section>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>


<!-- ticket chat  -->

<div class="modal fade bd-example-modal-lg" id="ticket_chat" role="dialog" aria-labelledby="exampleModalLabel"
    aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header" style="background-color: #027dc1; color: #fff;">
                <div class="tab_modal_heading">
                    <h2>Ticket Conversation</h2>
                </div>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="opacity: 1; color: #fff;">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form method="post" id="ticket_conversation_form">
                    <div id="wizard">
                            <div class="row">
                                
                                <div class="col-md-12 col-12">
                                     <div id="" class="">
                                        <div>
                                            <div class="portlet-body chat-widget" style="width: auto; height: 300px; overflow-x: hidden; overflow-y:auto;">
                                                 <?php
                                                    $i=1;
                                                    foreach($Ticket_conversation_data as $Ticket_conversation_value)
                                                    {

                                                    $id  = $Ticket_conversation_value['ID'];
                                                    ?>
                                                <div class="row">
                                                    <div class="col-lg-12">
                                                        <div class="media">
                                                            <img style="width: 50px; height: 50px; border-radius: 50%;" class="mr-2 media-object img-circle img-chat"
                                                                    src="https://bootdey.com/img/Content/avatar/avatar1.png" alt="">
                                                            <div class="media-body">
                                                                
                                                                <p><?php echo $Ticket_conversation_value['Message']; ?></p>
                                                                <h5 class="media-heading">Author:&nbsp;<?php echo $Ticket_conversation_value['CreatedBy']; ?>
                                                                    <span class="small pull-right ml-2">Date:&nbsp;<?php echo $Ticket_conversation_value['CreatedDate']; ?>&nbsp; Time:&nbsp;<?php echo $Ticket_conversation_value['CreatedTime']; ?></span>
                                                                </h5>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <hr>
                                                <?php
                                                    $i++;
                                                    }
                                                    ?>
                                              
                                            </div>
                                        </div>
                                        <div class="portlet-footer mt-3">
                                                <div class="form-group">
                                                    <textarea class="form-control" name="message" placeholder="Type Your Message..."></textarea>
                                                </div>
                                                 <div class="row justify-content-center mt-3">
                                                    <a class="form_btn form_submit pl-4 pr-4 pt-2 pb-2 text-white cursor-pointer" id="upload_change_btn"
                                                        style="background-color: #2196f3;" onclick="AddTicketConversation()"
                                                        value="Save">Send</a>
                                                </div>
                                                <input type="hidden" name="TicketID" value="<?php echo $ID;?>" />
                                        </div>
                                    </div>
                                </div>

                                


                            </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>