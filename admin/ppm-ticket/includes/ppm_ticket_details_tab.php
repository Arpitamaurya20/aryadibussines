<div class="panel-container show">
    <div class="panel-content p-0">
        <table id="view-projects" class="table table-bordered table-hover table-striped w-100">
            <thead>
                <tr>
                </tr>
            </thead>
            <tbody>
                <input type="hidden" id="BranchAssetCategoryID" value="<?=$BranchAssetCategoryID;?>">
                <input type="hidden" id="UseDynamicPPM" value="<?php echo isset($useDynamicPpm) ? (int) $useDynamicPpm : 0; ?>">
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
                    <td> <?php echo $corporate_ticket_data['BranchSite']; ?></td>
                </tr>
                <tr>
                    <th>PPM Date</th>
                    <td> <?php echo $corporate_ticket_data['PPMDate']; ?></td>
                </tr>
                <tr>
                    <th>Branch Asset</th>
                    <td> <?php echo $corporate_ticket_data['EquipmentName']; ?></td>
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
                    <th>Ticket Image</th>
                    <td>
                    <?php 
                        foreach ($TicketMediaData as $TicketMedia) {
                            $action = $TicketMedia['Action'];
                            if ($action=="Raised Ticket") {
                                if($TicketMedia['Image'] !== ""){
                                    $imageFile = $TicketMedia['Image'];
                                        $imagePath = "../media/ppm_ticket_media/" . $TicketMedia['Image'];
                                        $imageExtension = pathinfo($imagePath, PATHINFO_EXTENSION);
                                        $imageDate=$TicketMedia['CreatedDate'];
                                        $imageTime=$TicketMedia['CreatedTime'];
                                        $imageExtension = 'jpg'; // <- Force correct extension if you know it
                                        $downloadUrl = "download-image?file=" . urlencode($imageFile) . "&name=" . urlencode("ticket_image.$imageExtension");
                                ?>
                                 <a href="../media/ppm_ticket_media/<?php echo $TicketMedia['Image'];?>" ><img class="img" href="../media/ppm_ticket_media/<?php echo $TicketMedia['Image'];?>" class="p-5" src="../media/ppm_ticket_media/<?php echo $TicketMedia['Image'];?>" alt="img" height="75" width="75"></a>
                                 <a href="<?php echo $downloadUrl; ?>" class="btn btn-sm btn-success mt-2">
                                        <i class="fas fa-download"></i>
                                    </a>
                                <?php }else{?>
                                    <span>Not Set</span>

                                 <?php
                                }
                              }
                            }
                            if($UserType == "Admin" ||$UserType == "Corporate Admin" || $CityLead || $Account_Manager || $Branch_Account_Manager || $TicketManager)
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
                        foreach ($TicketMediaData as $TicketMedia) {
                            $action = $TicketMedia['Action'];
                            if ($action=="pre_img") {
                                if($TicketMedia['Image'] !== ""){
                                    $imageFile = $TicketMedia['Image'];
                                        $imagePath = "../media/ppm_ticket_media/" . $TicketMedia['Image'];
                                        $imageExtension = pathinfo($imagePath, PATHINFO_EXTENSION);
                                        $imageDate=$TicketMedia['CreatedDate'];
                                        $imageTime=$TicketMedia['CreatedTime'];
                                        $imageExtension = 'jpg'; // <- Force correct extension if you know it
                                        $downloadUrl = "download-image?file=" . urlencode($imageFile) . "&name=" . urlencode("ticket_image.$imageExtension");
                                        // $downloadUrl = "download-image?file=" . urlencode($imageFile) . "&name=" . urlencode("ticket_image.$imageExtension");
                                ?>
                                 <div class="text-center" style="display:inline-block; margin:10px;">
                                    <a href="../media/ppm_ticket_media/<?php echo $TicketMedia['Image'];?>" target="_blank">
                                        <img class="img p-2" 
                                            src="../media/ppm_ticket_media/<?php echo $TicketMedia['Image'];?>" 
                                            alt="img" height="75" width="75">
                                    </a>
                                    <br>
                                    <a href="<?php echo $downloadUrl; ?>" class="btn btn-sm btn-success mt-2">
                                        <i class="fas fa-download"></i>
                                    </a>
                                </div>

                                 
                                <?php }else{?>
                                    <span>Not Set</span>

                                 <?php
                                }
                              }
                            }
                            if($UserType == "Admin" ||$UserType == "Corporate Admin" || $CityLead || $Account_Manager || $Branch_Account_Manager || $TicketManager)
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
                        foreach ($TicketMediaData as $TicketMedia) {
                            $action = $TicketMedia['Action'];
                            if ($action=="post_img") {
                                if($TicketMedia['Image'] !== ""){
                                    $imageFile = $TicketMedia['Image'];
                                        $imagePath = "../media/ppm_ticket_media/" . $TicketMedia['Image'];
                                        $imageExtension = pathinfo($imagePath, PATHINFO_EXTENSION);
                                        $imageDate=$TicketMedia['CreatedDate'];
                                        $imageTime=$TicketMedia['CreatedTime'];
                                        $imageExtension = 'jpg'; // <- Force correct extension if you know it
                                        $downloadUrl = "download-image?file=" . urlencode($imageFile) . "&name=" . urlencode("ticket_image.$imageExtension");
                                        // $downloadUrl = "download-image?file=" . urlencode($imageFile) . "&name=" . urlencode("ticket_image.$imageExtension");
                                ?>
                                 <!-- <a href="../media/ppm_ticket_media/<?php echo $TicketMedia['Image'];?>" ><img class="img" href="../media/ppm_ticket_media/<?php echo $TicketMedia['Image'];?>" class="p-5" src="../media/ppm_ticket_media/<?php echo $TicketMedia['Image'];?>" alt="img" height="75" width="75"></a> -->
                                 <div class="text-center" style="display:inline-block; margin:10px;">
                                 <a href="../media/ppm_ticket_media/<?php echo $TicketMedia['Image'];?>" ><img class="img" href="../media/ppm_ticket_media/<?php echo $TicketMedia['Image'];?>" class="p-5" src="../media/ppm_ticket_media/<?php echo $TicketMedia['Image'];?>" alt="img" height="75" width="75"></a>
                                <br>
                                    <a href="<?php echo $downloadUrl; ?>" class="btn btn-sm btn-success mt-2">
                                        <i class="fas fa-download"></i>
                                    </a>
                                </div>
                                <?php }else{?>
                                    <span>Not Set</span>

                                 <?php
                                }
                              }
                            }
                        if($UserType == "Admin" ||$UserType == "Corporate Admin" || $CityLead || $Account_Manager || $Branch_Account_Manager || $TicketManager)
                        {
                        ?>
                            <a href='#' class='ml-2 badge badge-primary cursor-pointer' style='margin-right:20px;' onclick='UploadTicketMedia("post_img")'>Upload</a>
                        <?php
                        }
                        ?>
                    </td>
                </tr>

                <tr>
                    <th>Service Report Picture</th>
                    <td><?php 
                        foreach ($TicketMediaData as $TicketMedia) {
                            $action = $TicketMedia['Action'];
                            if ($action=="Service_Report") {
                                if($TicketMedia['Image'] !== ""){
                                    $imageFile = $TicketMedia['Image'];
                                        $imagePath = "../media/ppm_ticket_media/" . $TicketMedia['Image'];
                                        $imageExtension = pathinfo($imagePath, PATHINFO_EXTENSION);
                                        $imageDate=$TicketMedia['CreatedDate'];
                                        $imageTime=$TicketMedia['CreatedTime'];
                                        $imageExtension = 'jpg'; // <- Force correct extension if you know it
                                        $downloadUrl = "download-image?file=" . urlencode($imageFile) . "&name=" . urlencode("ticket_image.$imageExtension");
                                ?>
                                 <a href="../media/ppm_ticket_media/<?php echo $TicketMedia['Image'];?>" ><img class="img" href="../media/ppm_ticket_media/<?php echo $TicketMedia['Image'];?>" class="p-5" src="../media/ppm_ticket_media/<?php echo $TicketMedia['Image'];?>" alt="img" height="75" width="75"></a>
                                  <a href="<?php echo $downloadUrl; ?>" class="btn btn-sm btn-success mt-2">
                                        <i class="fas fa-download"></i>
                                    </a>
                                <?php }else{?>
                                    <span>Not Set</span>

                                 <?php
                                }
                              }
                            }
                        if($UserType == "Admin" ||$UserType == "Corporate Admin" || $CityLead || $Account_Manager || $Branch_Account_Manager || $TicketManager)
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
                                    <input type="hidden" name="TicketID" value="<?php echo $ID;?>" />
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