<?php 
$site_visit_data = $site_visits_obj->GetSiteVisitCompleteDetail($SiteVisitID);
extract($site_visit_data);
if($ReportNumber == "")
{
    $ReportNumber = "Not Set";
}
if($ContactPersonEmail == "")
{
    $ContactPersonEmail = "Not Set";
}
if($ContactPersonPhone == "")
{
    $ContactPersonPhone = "Not Set";
}
if($CompletedDate != "")
{
    $CompletedOn = $CompletedDate." | ".$CompletedTime;
}
else
{
    $CompletedOn = "Not Set";
}
?>
<div class="panel-container show">
    <div class="panel-content p-0">
        <!-- datatable start -->
        <table id="view-projects" class="table table-bordered table-hover table-striped w-100">
            <tbody>
                <tr>
                    <th>Company Account </th>
                    <td>
                    <?php echo $CompanyName; ?>
                    </td>
                </tr>
                <tr>
                    <th>Branch</th>
                    <td>
                    <?php echo $BranchSite; ?>
                    </td>
                </tr>
                <tr>
                    <th>Created On </th>
                    <td><?php echo $CreatedDate." | ".$CreatedTime; ?></td>
                </tr>
                <tr>
                    <th>Status</th>
                    <td><?php echo $Status; ?></td>
                </tr>
                <tr>
                    <th>Completed On</th>
                    <td><?php echo $CompletedOn; ?></td>
                </tr>
                <tr>
                    <th>Visit Title </th>
                    <td><?php echo $VisitTitle; ?></td>
                </tr>
                <tr>
                    <th>Contact Person </th>
                    <td><?php echo $ContactPerson; ?></td>
                </tr>
                <tr>
                   <th>Contact Person Email </th>
                   <td><?php echo $ContactPersonEmail; ?></td>
                </tr>
                <tr>
                   <th>Contact Person Contact </th>
                   <td><?php echo $ContactPersonPhone; ?></td>
                </tr>

                <tr>
                    <th>Summary</th>
                    <td><?php echo $Summary; ?></td>
                </tr>
                <tr>
                    <th>Created By</th>
                    <td><?php echo $CreatedBy; ?></td>
                </tr>
                <tr>
                    <th>Client Signature </th>
                    <td>
                        <?php 
                        if($ClientSignature != "")
                        {
                                                      
                            echo '<div style="width:100px; height:200px; margin-top:10px;" class="client_signature">
                                    <img style="width:100px;" src="../media/signature/'.$ClientSignature.'">
                                  </div>';

                        } 
                        else 
                        {
                            echo "N/A";
                        }
                        ?>
                    </td>
                </tr>
        

               
            </tbody>
        </table>
       
    </div>
</div>
