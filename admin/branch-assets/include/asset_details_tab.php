<?php 
$sql = "SELECT ba.*,b.BranchSite,c.CompanyName FROM `branch_assets` ba INNER JOIN branch b ON ba.BranchID = b.ID INNER JOIN company c ON b.CompanyID = c.ID WHERE ba.ID = $EquipmentID";
$branch_asset_details = $core->_getSQLDetails($conn,$sql);
$categories_obj = new Categories($conn);
$categories_array = $categories_obj->setCategoriesArray();
$sub_categories_array = $categories_obj->setSubCategoriesArray();
$CategoryName = "Not Set";
$CategoryID = $branch_asset_details['Category'];
if(isset($categories_array[$CategoryID]['CategoryName']))
{
    $CategoryName = $categories_array[$CategoryID]['CategoryName'];
}
$SubCategoryName = "Not Set";
$SubCategoryID = $branch_asset_details['SubCategory'];
if(isset($categories_array[$SubCategoryID]['SubCategoriesName']))
{
    $SubCategoryName = $categories_array[$SubCategoryID]['SubCategoriesName'];
}
?>

<div class="panel-container show">
    <div class="panel-content p-0">
        <table id="view-projects" class="table table-bordered table-hover table-striped w-100">
            <thead>
                <tr>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <th>Company Account</th>
                    <td>
                        <?php echo $branch_asset_details['CompanyName']; ?>
                    </td>
                </tr>
                <tr>
                    <th>Branch </th>
                    <td>
                        <?php echo $branch_asset_details['BranchSite']; ?>
                    </td>
                </tr>
                <tr>
                    <th>Equipment Name </th>
                    <td>
                        <?php echo $branch_asset_details['EquipmentName']; ?>
                    </td>
                </tr>

                <tr>
                    <th>Make</th>
                    <td> <?php echo $core->getValueorNotSet($branch_asset_details['Make']); ?></td>
                </tr>

                <tr>
                    <th>Model </th>
                    <td> <?php echo $core->getValueorNotSet($branch_asset_details['Model']); ?></td>
                </tr>
                <tr>
                    <th>SNo</th>
                    <td> <?php echo $core->getValueorNotSet($branch_asset_details['SNo']); ?></td>
                </tr>
                <tr>
                    <th>Capacity</th>
                    <td> <?php echo $core->getValueorNotSet($branch_asset_details['Capacity']); ?></td>
                </tr>

                <tr>
                    <th>Qty</th>
                    <td> <?php echo $core->getValueorNotSet($branch_asset_details['Qty']); ?></td>
                </tr>

                <tr>
                    <th>UoM</th>
                    <td> <?php echo $core->getValueorNotSet($branch_asset_details['UoM']); ?></td>
                </tr>

                <tr>
                    <th>Unit Rate</th>
                    <td> <?php echo $core->getValueorNotSet($branch_asset_details['UnitRate']); ?></td>
                </tr>

                <tr>
                    <th>Amount</th>
                    <td> <?php echo $core->getValueorNotSet($branch_asset_details['Amount']); ?></td>
                </tr>
                <tr>
                    <th>Manufacturing Year</th>
                    <td> <?php echo $core->getValueorNotSet($branch_asset_details['ManufacturingYear']); ?></td>
                </tr>
                <tr>
                    <th>Equipment Age</th>
                    <td> <?php echo $core->getValueorNotSet($branch_asset_details['EquipmentAge']); ?></td>
                </tr>
                <tr>
                    <th>Service Type</th>
                    <td> <?php echo $core->getValueorNotSet($branch_asset_details['ServiceType']); ?></td>
                </tr>
                <tr>
                    <th>Category</th>
                    <td> <?php echo $CategoryName; ?></td>
                </tr>
                <tr>
                    <th>Sub Category</th>
                    <td> <?php echo $SubCategoryName; ?></td>
                </tr>
                <tr>
                    <th>Floor Number</th>
                    <td> <?php echo $core->getValueorNotSet($branch_asset_details['FloorNumber']); ?></td>
                </tr>
                <tr>
                    <th>Equipment Location</th>
                    <td> <?php echo $core->getValueorNotSet($branch_asset_details['EquipmentLocation']); ?></td>
                </tr>
                <tr>
                    <th>Description</th>
                    <td> <?php echo $core->getValueorNotSet($branch_asset_details['Description']); ?></td>
                </tr>
                <tr>
                    <th>Created Information</th>
                    <td> <?php echo $branch_asset_details['CreatedBy']." ".$branch_asset_details['CreatedDate']."|".$branch_asset_details['CreatedTime'] ; ?></td>
                </tr>
                
                


            </tbody>

        </table>
        <div class="text-right">
            <!--a href="#" class="btn btn-danger" style="margin-right:20px;" data-toggle="modal"
                data-target="#editaccess">Delete</a-->
        </div>
    </div>
</div>


