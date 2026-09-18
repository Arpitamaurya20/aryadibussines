<?php
$branch_obj = new Branch($conn);
$branch_details = $branch_obj->getBranchDetailsbySite($corporate_ticket_data['BranchSite']);
$config_obj = new Config($conn);
$conf_data = array();
$conf_data['CorporateID'] = $CorporateID;
$fields_data = $config_obj->getAllConfigurableFields($conf_data);
?>
<div class="panel-container show">
    <div class="panel-content p-0">
        <?php
        $canApproveCorporateTicket = (
            isset($showOnlyDetailsForCorporateApproval)
            && $showOnlyDetailsForCorporateApproval
            && ($UserType == "Admin" || $UserType == "Corporate Admin")
        );
        if($canApproveCorporateTicket)
        {
        ?>
        <div class="alert alert-warning d-flex align-items-center justify-content-between">
            <div>
                <strong>Corporate approval pending.</strong>
                Approve this ticket to push it into the main ticket bucket for Branch Account Manager and State Manager.
            </div>
            <div class="d-flex">
                <button type="button" class="btn btn-success mr-2" id="approve_corporate_ticket_btn" onclick="ApproveCorporateTicket(<?php echo (int)$PrimaryID; ?>)">
                    Approve & Push to Main Bucket
                </button>
                <button type="button" class="btn btn-danger" id="reject_corporate_ticket_btn" onclick="RejectCorporateTicket(<?php echo (int)$PrimaryID; ?>)">
                    Cancel Ticket
                </button>
            </div>
        </div>
        <?php
        }
        ?>
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
                    <th>Corporate</th>
                    <td> <?php echo $corporate_ticket_data['CompanyName']; ?></td>
                </tr>

                <tr>
                    <th>Branch </th>
                    <td> <?php echo $corporate_ticket_data['BranchSite'];
                    if($UserType == "Admin" ||$UserType == "Corporate Admin" || $TicketManager || $UserType == "Corporate Admin")
                    {
                    ?>
                        <a href='#' class='ml-5 badge badge-primary cursor-pointer' style='margin-right:20px;' onclick='EditTicketBranch("<?php echo $corporate_ticket_data['CorporateID']; ?>","<?php echo $corporate_ticket_data['BranchID']; ?>")'>Edit Ticket Branch</a>
                    <?php
                    }
                    ?>
                    </td>
                </tr>
                <tr>
                    <th>Branch City</th>
                    <td> <?php echo $branch_details['BranchCity']; ?></td>
                </tr>
                <tr>
                    <th>Branch Address</th>
                    <td> <?php echo $branch_details['BranchAddress1']; ?></td>
                </tr>
                <tr>
                    <th>Service Type </th>
                    <td> <?php echo $corporate_ticket_data['Type']; 
                    $TicketType = $corporate_ticket_data['Type'];
                    if($UserType == "Admin" ||$UserType == "Corporate Admin" || $TicketManager)
                    {
                    ?>
                        <a href='#' class='ml-5 badge badge-primary cursor-pointer' style='margin-right:20px;' onclick='EditTicketType("<?php echo $TicketType; ?>")'>Edit Ticket Type</a>
                    <?php
                    }
                    ?>


                    </td>
                </tr>
                <?php 
                if($corporate_ticket_data['Type'] == "AMC")
                {
                    $Make = "Not Set";
                    if($corporate_ticket_data['Make'] != "")
                    {
                        $Make = $corporate_ticket_data['Make'];
                    }

                    $Model = "Not Set";
                    if($corporate_ticket_data['Model'] != "")
                    {
                        $Model = $corporate_ticket_data['Model'];
                    }

                    $SNo = "Not Set";
                    if($corporate_ticket_data['SNo'] != "")
                    {
                        $SNo = $corporate_ticket_data['SNo'];
                    }

                ?>
                <tr>
                    <th>Asset Information</th>

                    <td> <?php echo "Make - ".$Make; ?> | <?php echo "Model - ".$Model; ?> | <?php echo "SNo - ".$SNo; ?> </td>
                </tr>
                <tr>
                    <th>Category</th>

                    <td>
                        <?php 
                        if(isset($corporate_ticket_data['CategoryName']))
                        {
                            echo $corporate_ticket_data['CategoryName'];
                        }
                        else
                        {
                            echo "Not Set";
                        }
                        ?>
                        <input type="hidden" id="TicketCategoryName" value="<?php echo $corporate_ticket_data['CategoryName'];?>" />
                    </td>
                </tr>
                <?php    
                }
                else
                {
                ?>
                <tr>
                    <th>Service </th>
                    <td> <?php echo $corporate_ticket_data['Service']; ?>
                    <?php
                    if($UserType == "Admin" ||$UserType == "Corporate Admin" || $TicketManager)
                    {
                    ?>
                        <a href='#' class='ml-5 badge badge-primary cursor-pointer' style='margin-right:20px;' onclick='EditTicketService("<?php echo $corporate_ticket_data['Service']; ?>")'>Edit Service</a>
                    <?php
                    }
                    ?>
                     </td>
                </tr>
                <tr>
                    <th>Sub Service </th>
                    <td> <?php echo $corporate_ticket_data['Subservice']; ?></td>
                </tr>
                <?php 
                if($corporate_ticket_data['Subservice'] == "Others")
                {
                    ?>
                    <tr>
                        <th>Sub Service(Others) </th>
                        <td> <?php echo $corporate_ticket_data['SubService_Others']; ?></td>
                    </tr>
                    <?php
                }
                ?>
                <?php
                }
                if($corporate_ticket_data['Type'] == "AMC")
                {
                ?>
                <tr>
                    <th>Branch Asset</th>
                    <td> <?php echo $corporate_ticket_data['EquipmentName']; ?></td>
                </tr>
                <?php
                }
                ?>
                <tr>
                    <th>Client Ticket ID </th>
                    <td> <?php
                    if($corporate_ticket_data['ClientTicketID'] == ''){
                        echo 'Not Set';
                    }else{
                        echo $corporate_ticket_data['ClientTicketID'];
                    }
                    if($UserType == "Admin" ||$UserType == "Corporate Admin" || $TicketManager)
                    {
                    ?>
                        <a href='#' class='ml-5 badge badge-primary cursor-pointer' style='margin-right:20px;' onclick='OpenClientIDModal()'>Edit Client Ticket ID</a>
                    <?php
                    }
                    ?>
                    </td>
                    
                </tr>
                <tr>
                    <th>Priority</th>
                    <td> <?php echo $corporate_ticket_data['Priority'];?></td>
                    
                </tr>

                <?php
                 if($corporate_ticket_data['Type'] == "AMC"){
                 if($UserType == "Admin" ||$UserType == "Corporate Admin" || $CityLead == 1){?>
                <tr>
                    <th>SparePart</th>
                    <td> <?php
                        echo $corporate_ticket_data['SparePart'];
                     ?>
                        <a href='#' class='ml-5 badge badge-primary cursor-pointer' style='margin-right:20px;' onclick='OpenAddSparePart()'>Add Spare Part</a> 
                     </td>
                    
                </tr>

            <?php } } ?>

                <tr>
                    <th>Message </th>
                    <td> <?php echo $corporate_ticket_data['Message']; ?></td>
                </tr>


                <tr>
                    <th>Booking Date </th>
                    <td> <?php echo $corporate_ticket_data['CreatedDate']; ?></td>
                </tr>

                <tr>
                    <th>Booking Time </th>
                    <td> <?php echo $corporate_ticket_data['CreatedTime']; ?></td>
                </tr>

                <tr>
                    <th>Created By </th>
                    <td> <?php echo $corporate_ticket_data['CreatedBy']; ?></td>
                </tr>
                <tr>
                    <th>Close Date </th>
                    <td> 
                        <?php
                        if($corporate_ticket_data['CloseDate']!="")
                        {
                            echo $corporate_ticket_data['CloseDate'];
                        }
                        else
                        {
                            echo "N.A.";
                        }
                        ?>
                    </td>
                </tr>

                <tr>
                    <th>Close Time </th>
                    <td> 
                        <?php
                        if($corporate_ticket_data['CloseTime']!="")
                        {
                            echo $corporate_ticket_data['CloseTime'];
                        }
                        else
                        {
                            echo "N.A.";
                        }
                        ?>
                    </td>
                </tr>
                <?php
                // Get Configurable option
                foreach($fields_data as $field)
                {
                    ?>
                    <tr>
                        <th><?php echo $field['Title']; ?></th>
                        <td>
                            <?php
                             echo $corporate_ticket_data[$field['Param']];
                            ?>
                        </td>
                    </tr>
                    <?php
                }
                ?>
                <tr>
                    <th>Ticket Attachment</th>
                    <td>
                    <?php 
                    if($corporate_ticket_data['TicketAttachment'] == "")
                    {
                        echo "Not Set";
                    }
                    else
                    {
                    ?>
                        <a href="../media/raise_ticket_media/<?php echo $corporate_ticket_data['TicketAttachment'];?>">Download</a>
                    <?php
                    }
                        
                    ?>
                    </td>
                </tr>
                <tr>
                    <th>Ticket Image</th>
                    <td>
                    <?php 
                    $imageDate = "";
                    $imageTime = "";
                        foreach ($TicketMediaData as $TicketMedia) {
                            $action = $TicketMedia['Action'];
                            if ($action=="Raised Ticket" || $action == "Service Report") {
                                if($TicketMedia['Image'] !== "")
                                {
                                   
                                    $imagePath = "../media/ticket_media/" . $TicketMedia['Image'];
                                    $imageExtension = pathinfo($imagePath, PATHINFO_EXTENSION);
                                    $imageDate=$TicketMedia['CreatedDate'];

                                    $imageTime=$TicketMedia['CreatedTime'];

                                    
                                    
                                ?>
                                <div class="d-inline-block me-3 mb-3">
                                    <a href="<?php echo $imagePath; ?>" target="_blank">
                                        <?php if($imageExtension == 'pdf')
                                        {
                                        ?>
                                            <i class="fas fa-download"></i>
                                        <?php
                                        } 
                                        else
                                        {
                                        ?>
                                            <img class="img p-5" src="<?php echo $imagePath; ?>" alt="img" height="200" width="200" loading="lazy">
                                        <?php
                                        } ?>
                                    </a>
                                               <div class="mt-2 d-flex">
                                                    <span class="badge bg-warning"><?php echo $imageDate?></span>
                                                    <span class="badge bg-info me-3"><?php echo $imageTime?></span>
                                                     
                                                </div>
                                                
                                    </div>
                                
                                <?php 
                                }
                                else
                                {?>
                                    <span>Not Set</span>

                                 <?php
                                }
                              }
                            }
                            if($UserType == "Admin" ||$UserType == "Corporate Admin" || $CityLead || $Account_Manager || $Branch_Account_Manager || $TicketManager || $Procurement)
                            {
                            ?>
                                <a href='#' class='ml-2 badge badge-primary cursor-pointer' style='margin-right:20px;' onclick='UploadTicketMedia("Raised Ticket")'>Upload</a>
                            <?php
                            }
                      ?>

                           
                    </td>
                </tr>
                 <tr>
                    <th>Before pic </th>
                    <td>
                        <?php 
                        $imageDate = "";
                        $imageTime = "";
                            foreach ($TicketMediaData as $TicketMedia) {
                                $action = $TicketMedia['Action'];
                                
                                if ($action=="pre_img") {
                                    if($TicketMedia['Image'] !== "")
                                    {
                                          $imageFile = $TicketMedia['Image'];
                                        $imagePath = "../media/ticket_media/" . $TicketMedia['Image'];
                                        $imageExtension = pathinfo($imagePath, PATHINFO_EXTENSION);
                                        $imageDate=$TicketMedia['CreatedDate'];
                                        $imageTime=$TicketMedia['CreatedTime'];
                                        $imageExtension = 'jpg'; // <- Force correct extension if you know it
                                        $downloadUrl = "download-image?file=" . urlencode($imageFile) . "&name=" . urlencode("ticket_image.$imageExtension");
                                        // $downloadUrl = "download-image?file=" . urlencode($imageFile) . "&name=" . urlencode("ticket_image.$imageExtension");
                                    
                                    ?>
                                    <div class="d-inline-block me-3 mb-3">
                                        <a href="<?php echo $imagePath; ?>" target="_blank">
                                            <?php if($imageExtension == 'pdf')
                                            {
                                            ?>
                                                <i class="fas fa-download" ></i>
                                            <?php
                                            } 
                                            else
                                            {
                                            ?>
                                                <img class="img p-5" src="<?php echo $imagePath; ?>" alt="img" height="200" width="200" loading="lazy">
                                            <?php
                                            } ?>
                                        </a>

                                                <div class="mt-2 d-flex">
                                                    <span class="badge bg-warning"><?php echo $imageDate?></span>
                                                    <span class="badge bg-info me-3"><?php echo $imageTime?></span>
                                                </div>
                                                <a href="<?php echo $downloadUrl; ?>" class="btn btn-sm btn-success mt-2"><i class="fas fa-download" ></i></a>

                                        </div>
                                        
                                    <?php 
                                    }else{?>
                                        <span>Not Set</span>

                                    <?php
                                    }
                                }
                                }
                                if($UserType == "Admin" ||$UserType == "Corporate Admin" || $CityLead || $Account_Manager || $Branch_Account_Manager || $TicketManager || $Procurement)
                                {
                                ?>
                                    <a href='#' class='ml-2 badge badge-primary cursor-pointer' style='margin-right:20px;' onclick='UploadTicketMedia("pre_img")'>Upload</a>
                                <?php
                                }
                        ?>
                                   
                        </td>
                    </tr>
                    

                <tr>
                    <th>After pic</th>
                    <td><?php 
                      $imageDate = "";
                      $imageTime = "";
                        foreach ($TicketMediaData as $TicketMedia) {
                            $action = $TicketMedia['Action'];
                            if ($action=="post_img") {
                                if($TicketMedia['Image'] !== "")
                                {    
                                    $imageFile = $TicketMedia['Image'];
                                    $imagePath = "../media/ticket_media/" . $TicketMedia['Image'];
                                    $imageExtension = pathinfo($imagePath, PATHINFO_EXTENSION);
                                    $imageDate=$TicketMedia['CreatedDate'];

                                    $imageTime=$TicketMedia['CreatedTime'];
                                    $imageExtension = 'jpg'; // <- Force correct extension if you know it
                                    $downloadUrl = "download-image?file=" . urlencode($imageFile) . "&name=" . urlencode("ticket_image.$imageExtension");
                                ?>
                                <div class="d-inline-block me-3 mb-3">
                                    <a href="<?php echo $imagePath; ?>" target="_blank">
                                        <?php if($imageExtension == 'pdf')
                                        {
                                        ?>
                                            <i class="fas fa-download" ></i>
                                        <?php
                                        } 
                                        else
                                        {
                                        ?>
                                            <img class="img p-5" src="<?php echo $imagePath; ?>" alt="img" height="200" width="200" loading="lazy">
                                        <?php
                                        } ?>
                                    </a>
                                    <div class="mt-2 d-flex">
                                            <span class="badge bg-warning"><?php echo $imageDate?></span>
                                            <span class="badge bg-info me-3"><?php echo $imageTime?></span>
                                    </div>
                                     <a href="<?php echo $downloadUrl; ?>" class="btn btn-sm btn-success mt-2"><i class="fas fa-download" ></i></a>
                                    </div>
                                <?php 
                                }else{?>
                                    <span>Not Set</span>

                                 <?php
                                }
                              }
                            }
                        if($UserType == "Admin" ||$UserType == "Corporate Admin" || $CityLead || $Account_Manager || $Branch_Account_Manager || $TicketManager || $Procurement)
                        {
                        ?>
                            <a href='#' class='ml-2 badge badge-primary cursor-pointer' style='margin-right:20px;' onclick='UploadTicketMedia("post_img")'>Upload</a>
                        <?php
                        }
                        ?>
                    </td>
                </tr>

                <tr>
                        <th>Service Report Picture </th>
                        <td>
                            <?php 
                            $imageDate = "";
                            $imageTime = "";
                                foreach ($TicketMediaData as $TicketMedia) {
                                    $action = $TicketMedia['Action'];
                                    
                                    if ($action=="Service_Report") {
                                        if($TicketMedia['Image'] !== "")
                                        {
                                            $imageFile = $TicketMedia['Image'];
                                            $imagePath = "../media/ticket_media/" . $TicketMedia['Image'];
                                            $imageExtension = pathinfo($imagePath, PATHINFO_EXTENSION);
                                            $imageDate=$TicketMedia['CreatedDate'];

                                            $imageTime=$TicketMedia['CreatedTime'];
                                            $imageExtension = 'jpg'; // <- Force correct extension if you know it
                                            $downloadUrl = "download-image?file=" . urlencode($imageFile) . "&name=" . urlencode("Service_Report_Picture.$imageExtension");
                                        
                                        ?>
                                        <div class="d-inline-block me-3 mb-3">
                                            <a href="<?php echo $imagePath; ?>" target="_blank">
                                                <?php if($imageExtension == 'pdf')
                                                {
                                                ?>
                                                    <i class="fas fa-download" ></i>
                                                <?php
                                                } 
                                                else
                                                {
                                                ?>
                                                    <img class="img p-5" src="<?php echo $imagePath; ?>" alt="img" height="200" width="200" loading="lazy">
                                                <?php
                                                } ?>
                                            </a>

                                                    <div class="mt-2 d-flex">
                                                        <span class="badge bg-warning"><?php echo $imageDate?></span>
                                                        <span class="badge bg-info me-3"><?php echo $imageTime?></span>
                                                    </div>

                                                     <a href="<?php echo $downloadUrl; ?>" class="btn btn-sm btn-success mt-2"><i class="fas fa-download" ></i></a>

                                            </div>
                                            
                                        <?php 
                                        }else{?>
                                            <span>Not Set</span>

                                        <?php
                                        }
                                    }
                                    }
                                    if($UserType == "Admin" ||$UserType == "Corporate Admin" || $CityLead || $Account_Manager || $Branch_Account_Manager || $TicketManager || $Procurement)
                                    {
                                    ?>
                                        <a href='#' class='ml-2 badge badge-primary cursor-pointer' style='margin-right:20px;' onclick='UploadTicketMedia("Service_Report")'>Upload</a>
                                    <?php
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


<!-- client id modal  -->

<div class="modal fade bd-example-modal-lg" id="edit_clientid" role="dialog" aria-labelledby="exampleModalLabel"
    aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header" style="background-color: #027dc1;
    color: #fff;">
                <div class="tab_modal_heading">
                    <h2>Edit Client Ticket ID </h2>
                </div>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="opacity: 1;
    color: #fff;">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form method="post" id="ticket_clientid_form">
                    <div id="wizard">
                        <section>
                            <div class="row">

                                 <div class="col-md-12 col-12">
                                    <div class="form_div"> <label for="ClientTicketID">Client Ticket ID
                                        </label>
                                        <input type="text" class="form-control" name="ClientTicketID"
                                                        id="ClientTicketID" value="<?php echo $corporate_ticket_data['ClientTicketID']; ?>" placeholder="Please Fill Client Ticket ID">

                                    </div>
                                </div>

                                <input type="hidden" name="TicketID" value="<?php echo $ID;?>" />


                            </div>
                            <section>
                                <div class="row justify-content-center mt-3">
                                    <a class="form_btn form_submit pl-4 pr-4 pt-2 pb-2 text-white cursor-pointer" id="clientid_change_btn"
                                        style="background-color: #2196f3;" onclick="ChangeTicketClientID()"
                                        value="Save">Save & Change</a>
                                </div>
                            </section>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade bd-example-modal-lg" id="edit_ticket_type" role="dialog" aria-labelledby="exampleModalLabel"
    aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header" style="background-color: #027dc1;
    color: #fff;">
                <div class="tab_modal_heading">
                    <h2>Edit Client Ticket Type </h2>
                </div>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="opacity: 1;
    color: #fff;">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form method="post" id="ticket_type_modal_form">
                    <div id="wizard">
                        <section>
                            <div class="row">

                                 <div class="col-md-12 col-12">
                                    <div class="form-group">
                                        <label class="form-label" for="name">Service Type <span
                                                class="text-danger">*</span></label>
                                            <select class="form-control w-100" name="service_type" id="service_type">
                                                <option value="R&M">R&M</option>
                                                <option value="Projects">Projects</option>
                                                <option value="Supply">Supply</option>
                                            </select>
                                    </div>
                                </div>

                                <input type="hidden" name="TicketID" value="<?php echo $ID;?>" />


                            </div>
                            <section>
                                <div class="row justify-content-center mt-3">
                                    <a class="form_btn form_submit pl-4 pr-4 pt-2 pb-2 text-white cursor-pointer" id="clientid_change_btn"
                                        style="background-color: #2196f3;" onclick="ChangeTicketType()"
                                        value="Save">Save & Change</a>
                                </div>
                            </section>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Edit Ticket Service Modal -->
<div class="modal fade bd-example-modal-lg" id="edit_ticket_service_modal" role="dialog" aria-labelledby="exampleModalLabel"
    aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header" style="background-color: #027dc1;
    color: #fff;">
                <div class="tab_modal_heading">
                    <h2>Edit Ticket Service </h2>
                </div>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="opacity: 1;
    color: #fff;">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form method="post" id="ticket_service_form">
                    <div id="wizard">
                        <section>
                            <div class="row">

                                        <div class="col-lg-12">
                                            <div class="form-group">
                                                <label class="form-label" for="number">Service <span
                                                        class="text-danger">*</span></label>
                                                        <select onchange="GetSubCategories()" class="form-control w-100" name="service_name" class="select2" id="service_name">
                                                            <?php
                                                            foreach($categories_array as $category)
                                                            {
                                                            ?>
                                                             <option value="<?php echo $category['CategoriesName']; ?>" data-id="<?php echo $category['ID']; ?>"><?php echo $category['CategoriesName']; ?></option>
                                                            <?php
                                                            }
                                                        ?>
                                                </select>
                                             </div>
                                                
                                        </div>

                                        <div class="col-lg-12" id="sub_services_div" style="display: none;">
                                            <div class="form-group">
                                                <label class="form-label" for="number">Sub Service <span
                                                        class="text-danger">*</span></label>
                                                        <select class="form-control w-100" name="sub_service_name" class="select2"
                                                    id="sub_service_name">
                                                    <option value="">Please Select Sub Service</option>
                                                </select>
                                            </div>
                                        </div>

                                <input type="hidden" name="TicketID" value="<?php echo $ID;?>" />


                            </div>
                            <section>
                                <div class="row justify-content-center mt-3">
                                    <a class="form_btn form_submit pl-4 pr-4 pt-2 pb-2 text-white cursor-pointer" id="change_service_btn"
                                        style="background-color: #2196f3;" onclick="ChangeTicketService()"
                                        value="Save">Save & Change</a>
                                </div>
                            </section>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>


<!-- Edit Ticket Branch Modal -->
<div class="modal fade bd-example-modal-lg" id="edit_ticket_branch_modal" role="dialog" aria-labelledby="exampleModalLabel"
    aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header" style="background-color: #027dc1;
    color: #fff;">
                <div class="tab_modal_heading">
                    <h2>Edit Ticket Branch </h2>
                </div>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="opacity: 1;
    color: #fff;">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form method="post" id="modal_ticket_branch_form">
                    <div id="wizard">
                        <section>
                            <div class="row">

                                        <div class="col-lg-12">
                                            <div class="form-group">
                                                <label class="form-label" for="number">Branch <span
                                                        class="text-danger">*</span></label>
                                                        <select class="form-control w-100" name="edit_branch_modal_branch_id" class="select2" id="edit_branch_modal_branch_id">
                                                            <option value="">Please Select Branch</option>
                                                            <?php
                                                                  foreach($branch_array as $m_branch_id=>$modal_branch)
                                                                  {
                                                            ?>
                                                                        <option value="<?php echo $m_branch_id; ?>"><?php echo $modal_branch['BranchName']; ?></option>
                                                            <?php
                                                                    }
                                                             ?>
                                                        </select>
                                             </div>
                                                
                                        </div>
                                <input type="hidden" name="TicketID" value="<?php echo $ID;?>" />
                            </div>
                            <section>
                                <div class="row justify-content-center mt-3">
                                    <a class="form_btn form_submit pl-4 pr-4 pt-2 pb-2 text-white cursor-pointer" id="change_branch_btn"
                                        style="background-color: #2196f3;" onclick="ChangeTicketBranchAction()"
                                        value="Save">Save & Change</a>
                                </div>
                            </section>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- upload media -->
<div class="modal fade bd-example-modal-lg" id="upload_media" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header" style="background-color: #027dc1;color: #fff;">
                <div class="tab_modal_heading">
                    <h2>Upload Ticket Media </h2>
                </div>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="opacity: 1;color: #fff;">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="ticket_media_upload_form">
                    <div id="wizard">
                            <div class="row">
                                <div class="col-md-12">
                                    <input type="hidden" name="TicketID" value="<?php echo $PrimaryID;?>" />
                                    <input type="hidden" name="media_action" id="media_action" value="" />
                                    <div class="form-group mb-0">
                                        
                                            <label class="form-label">Media</label>
                                            <div class="custom-file">
                                                <input type="file" id="media_image" name="media_image" class="form-control" />
                                            </div>
                                    </div>
                                </div>
                            </div>
                            <section>
                                <div class="row justify-content-center mt-3">
                                    <a class="form_btn form_submit pl-4 pr-4 pt-2 pb-2 text-white cursor-pointer" id="clientid_change_btn"
                                        style="background-color: #2196f3;" onclick="UploadTicketMediaAction()" value="Save">Save & Change</a>
                                </div>
                            </section>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- add spare part  -->

<div class="modal fade bd-example-modal-lg" id="add_sparepart" role="dialog" aria-labelledby="exampleModalLabel"
    aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header" style="background-color: #027dc1;
    color: #fff;">
                <div class="tab_modal_heading">
                    <h2>Add Spare Part </h2>
                </div>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="opacity: 1;
    color: #fff;">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form method="post" id="ticket_sparepart_form">
                    <div id="wizard">
                        <section>
                            <div class="row">

                                 <div class="col-md-12 col-12">
                                    <div class="form_div"> <label for="SparePartDecs">Spare Part Include Status
                                        </label>

                                        <select class="form-control select2" name="SparePartDecs"
                                                        id="SparePartDecs">
                                            <option value="Yes">Yes</option>
                                            <option value="No">No</option>
                                        </select>

                                        <!-- <input type="text" class="form-control" name="SparePartDecs"
                                                        id="SparePartDecs" value="<?php echo $corporate_ticket_data['SparePart']; ?>" placeholder="Please Spare Part Status"> -->

                                    </div>
                                </div>

                                <input type="hidden" name="TicketID" value="<?php echo $ID;?>" />
                                

                            </div>
                            <section>
                                <div class="row justify-content-center mt-3">
                                    <a class="form_btn form_submit pl-4 pr-4 pt-2 pb-2 text-white cursor-pointer" id="sparepart_change_btn"
                                        style="background-color: #2196f3;" onclick="ChangeTicketSparePart()"
                                        value="Save">Save & Update</a>
                                </div>
                            </section>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>