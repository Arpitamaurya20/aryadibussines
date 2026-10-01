<?php
include("../../controllers/common_controllers.php");
include('../controller/branch_assets_controller.php');

$conn = _connectodb();
setTimeZone();

$output = "";
$where = " WHERE IsActive = 1";

$branch_assets_details = _getTableRecords($conn, 'branch_assets', $where);

if ($branch_assets_details && count($branch_assets_details) > 0) {

    $output .= '
    <table class="table" border="1">
        <tr>
            <th>Branch</th>
            <th>Equipment Name</th>
            <th>Equipment Number</th>
            <th>Make</th>
            <th>Model</th>
            <th>Serial No</th>
            <th>Capacity</th>
            <th>Qty</th>
            <th>UOM Name</th>
            <th>UOM Number</th>
            <th>Unit Rate</th>
            <th>Amount</th>
            <th>Manufacturing Year</th>
            <th>Equipment Age</th>
            <th>Service Type</th>
            <th>Category</th>
            <th>CategoryNumber</th>
            <th>Sub Category</th>
            <th>Sub Category Number</th>
            <th>TAT</th>
            <th>AMC Start Date</th>
            <th>AMC End Date</th>
            <th>Floor Number</th>
            <th>Equipment Location</th>
            <th>Description</th>
            <th>Created By</th>
            <th>Created Date</th>
            <th>Created Time</th>
        </tr>
    ';

    foreach ($branch_assets_details as $BranchAssetsdata) {

        /* ---------- Branch ---------- */
        $BranchName = '';
        if (!empty($BranchAssetsdata["BranchID"])) {
            $BranchID = (int)$BranchAssetsdata["BranchID"];
            $BranchData = _getTableDetails($conn, 'branch', " WHERE ID = $BranchID");
            $BranchName = $BranchData ? $BranchData["BranchSite"] : '';
        }

        /* ---------- Category ---------- */
        $CategoryName = '';
        if (!empty($BranchAssetsdata["Category"])) {
            $CategoryID = (int)$BranchAssetsdata["Category"];
            $CategoryData = _getTableDetails(
                $conn,
                'manage_categories',
                " WHERE ID = $CategoryID AND IsActive = 1"
            );
            $CategoryName = $CategoryData ? $CategoryData["CategoriesName"] : '';
        }

        /* ---------- Sub Category ---------- */
        $SubCategoryName = '';
        if (!empty($BranchAssetsdata["SubCategory"])) {
            $SubCategoryID = (int)$BranchAssetsdata["SubCategory"];
            $SubCategoryData = _getTableDetails(
                $conn,
                'manage_subcategories',
                " WHERE ID = $SubCategoryID"
            );
            $SubCategoryName = $SubCategoryData ? $SubCategoryData["SubCategoriesName"] : '';
        }

        /* ---------- Equipment Age ---------- */
        $EquipmentAge = '';
        if (!empty($BranchAssetsdata["ManufacturingYear"])) {
            $EquipmentAge = date('Y') - (int)$BranchAssetsdata["ManufacturingYear"];
        }
        $UomName='';
        if (!empty($BranchAssetsdata["UoM"])) {
            $UoMID = (int)$BranchAssetsdata["UoM"];
            $UoMData = _getTableDetails(
                $conn,
                'manage_uom',
                " WHERE ID = $UoMID"
            );
            $UomName = $UoMData ? $UoMData["UOMName"] : '';
        }

        $output .= '<tr>
            <td>' . $BranchName . '</td>
            <td>' . $BranchAssetsdata["EquipmentName"] . '</td>
            <td>' . $BranchAssetsdata["ID"] . '</td>
            <td>' . $BranchAssetsdata["Make"] . '</td>
            <td>' . $BranchAssetsdata["Model"] . '</td>
            <td>' . $BranchAssetsdata["SNo"] . '</td>
            <td>' . $BranchAssetsdata["Capacity"] . '</td>
            <td>' . $BranchAssetsdata["Qty"] . '</td>
            <td>' . $UomName . '</td>
            <td>' . $BranchAssetsdata["UoM"] . '</td>
             <td>' . $BranchAssetsdata["UnitRate"] . '</td>
            <td>' . $BranchAssetsdata["Amount"] . '</td>
            <td>' . $BranchAssetsdata["ManufacturingYear"] . '</td>
            <td>' . $EquipmentAge . ' Years</td>
            <td>' . $BranchAssetsdata["ServiceType"] . '</td>
            <td>' . $CategoryName . '</td>
            <td>' . $BranchAssetsdata["Category"] . '</td>
            <td>' . $SubCategoryName . '</td>
             <td>' . $BranchAssetsdata["SubCategory"] . '</td>
            <td>' . $BranchAssetsdata["Tat"] . '</td>
            <td>' . $BranchAssetsdata["AMCStartDate"] . '</td>
            <td>' . $BranchAssetsdata["AMCEndDate"] . '</td>
            <td>' . $BranchAssetsdata["FloorNumber"] . '</td>
            <td>' . $BranchAssetsdata["EquipmentLocation"] . '</td>
            <td>' . $BranchAssetsdata["Description"] . '</td>
            <td>' . $BranchAssetsdata["CreatedBy"] . '</td>
            <td>' . $BranchAssetsdata["CreatedDate"] . '</td>
            <td>' . $BranchAssetsdata["CreatedTime"] . '</td>
        </tr>';
    }

    $output .= '</table>';

} else {
    $output = "<table border='1'><tr><td>No Data Available</td></tr></table>";
}

/* ---------- Write Excel File ---------- */
$myfile = fopen("../report.xls", "w");
fwrite($myfile, $output);
fclose($myfile);
?>
