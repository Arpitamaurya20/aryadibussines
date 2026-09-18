   <div class="panel-container show">
                                    <div class="panel-content">
                                        <!-- datatable start -->
                                        <table id="view-corporate-tickets"
                                            class="table table-bordered table-hover table-striped w-100">
                                            <thead>
                                                <tr>
                                                    <th>#</th>
                                                    <th>Ticket ID</th>
                                                    <th>Corporate / Branch</th>
                                                    <th>Type</th>
                                                    <th>Branch Asset </th>
                                                    <th>Message</th>
                                                    <th>Date / Time</th>
                                                    <th>Status</th>
                                                    <th>View Ticket</th>
                                                    <?php
                                                    if($techx_admin)
                                                    {
                                                    ?>
                                                        <th>Delete</th>
                                                    <?php
                                                    }
                                                    ?>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                    $i=1;

                                                    foreach($corporate_tickets_array as $corporate_tickets)
                                                    {
                                                    $id  = $corporate_tickets['ID'];

                                                    $CorporateID = $corporate_tickets['CorporateID'];
                                                    $Corporate = $company_array_key[$CorporateID]['CompanyName'];

                                                    $BranchID = $corporate_tickets['BranchID'];
                                                    $Branch = $branch_array_key[$BranchID]['BranchSite'];
                                                    $BranchAsset = "N.A.";
                                                    if($corporate_tickets['BranchAssetID'] != -1)
                                                    {
                                                        $BranchAssetID = $corporate_tickets['BranchAssetID'];
                                                        $BranchAsset = $branch_assets_array_key[$BranchAssetID]['EquipmentName'];
                                                    }
                                                    ?>
                                                <tr>
                                                    <td><?php echo $i; ?></td>
                                                    <td><?php echo $corporate_tickets['TicketID']; ?></td>
                                                    <td><?php echo $Corporate."<br>".$Branch; ?></td>
                                                    <td><?php echo $corporate_tickets['Type']; ?></td>
                                                    <td><?php echo $BranchAsset; ?></td>
                                                    <td><?php echo $corporate_tickets['Message']; ?></td>
                                                    <td>
                                                        <?php echo $corporate_tickets['CreatedDate']; ?> / <br>
                                                        <?php echo $corporate_tickets['CreatedTime']; ?>
                                                    </td>
                                                    <td>
                                                        <span
                                                            class="badge badge-danger cursor-pointer"><?php echo $corporate_tickets['Status']; ?></span>

                                                    </td>
                                                    <td>
                                                        <a onclick="ViewBookingDetails(<?php echo $id; ?>)">
                                                            <span class="badge badge-primary cursor-pointer">View
                                                            Ticket</span>
                                                        </a>
                                                    </td>
                                                    <?php
                                                    if($techx_admin)
                                                    {
                                                    ?>
                                                    <td><a onclick="DeleteCustomerDetail('<?php echo $id;?>')"><i
                                                                class="fal fa-trash" aria-hidden="true"></i>
                                                    </td>
                                                    <?php
                                                    }
                                                    ?>
                                                </tr>
                                                <?php
                                                    $i++;
                                                    }
                                                    ?>
                                            </tbody>

                                        </table>
                                        <!-- datatable end -->
                                    </div>
                                </div>