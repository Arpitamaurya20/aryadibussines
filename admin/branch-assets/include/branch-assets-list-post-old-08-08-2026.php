<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
include("../../controllers/common_controllers.php");
include('../controller/branch_assets_controller.php');
require_once('../../includes/autoloader.inc.php');

@session_start();
$conn = _connectodb();

$categories_obj = new Categories($conn);
$categories_array = $categories_obj->setCategoriesArray();
$sub_categories_array = $categories_obj->setSubCategoriesArray();

## Read value
$draw = $_POST['draw'];
$row = $_POST['start'];
$rowperpage = $_POST['length']; // Rows display per page
$searchValue = $_POST['search']['value']; // Search value

$columnName = "ID";
$columnSortOrder = "DESC";
$BranchID = -1;
$CorporateID = -1;
$UserType = SessionCheck();

if(isset($_SESSION['BranchID']))
{
    $BranchID = $_SESSION['BranchID'];
}
if($UserType == "Corporate Branch User" || $UserType == "Corporate Admin")
{
    $corporate_user = true;
    $CorporateID = $_SESSION['Roles']['CorporateID'];

}

if (isset($_GET['CorporateID'])) 
{
    $CorporateID = $_GET['CorporateID'];
}
if (isset($_GET['BranchID'])) 
{
    $BranchID = $_GET['BranchID'];
}


$data = array();

$searchQuery = "";
if ($searchValue != '') {
    $searchQuery = " and (ba.EquipmentName like '%" . $searchValue . "%' or ba.ServiceType like '%" . $searchValue . "%' or ba.ID like '%" . $searchValue . "%') ";
}

$status = 1;
if(isset($_GET['status']))
{
    $status = intval($_GET['status']) === 0 ? 0 : 1;
}

$filter = " AND ba.IsActive = $status";
$filter = $filter . $searchQuery;

$branch_assets_details = _getAllBranchAssets($conn, $CorporateID,$BranchID,$searchQuery,$filter);
$totalRecords = count($branch_assets_details); // Get all records for corporate
$totalRecordwithFilter = $totalRecords; 


$filter = $filter . " limit " . $row . "," . $rowperpage;
$branch_assets_details = _getAllBranchAssets($conn, $CorporateID,$BranchID,$searchQuery,$filter);


## Process and format data for DataTable
foreach ($branch_assets_details as $branch_asset_data) 
{
    extract($branch_asset_data);


         ## ---------------- GET UNIQUE QR CODE FROM branchassets_qr_code TABLE ---------------- ##
    $UniqueCode = "";
    $qr_sql = "SELECT UniqueCode FROM branchassets_qr_code WHERE AssetID = ?";
    $stmt = $conn->prepare($qr_sql);
    $stmt->bind_param("i", $branch_asset_data['ID']);   // AssetID = $ID
    $stmt->execute();
    $qr_result = $stmt->get_result();

    if ($qr_result->num_rows > 0) {
        $qr_row = $qr_result->fetch_assoc();
        $UniqueCode = $qr_row['UniqueCode'];
    }


     ## If no QR yet
    if ($UniqueCode == "" || $UniqueCode == null) {
          $QR_HTML = "<span class='badge badge-danger'>Not Generated</span>";
    } else {

        // QR file name
        $qrImageName = "techxpertqr_" . $UniqueCode . ".png";

        // Server paths (for file_exists)
        $path1 = "../../branch-assets-qr-code/qrcode/" . $qrImageName;
        $path2 = "../../branch-assets-qr-code/qrcode_b2/" . $qrImageName;

        // Browser-accessible URLs
        $url1 = "../branch-assets-qr-code/qrcode/" . $qrImageName;
        $url2 = "../branch-assets-qr-code/qrcode_b2/" . $qrImageName;

        // Detect existing file
        if (file_exists($path1)) {
            $qrImageURL = $url1;
        } elseif (file_exists($path2)) {
            $qrImageURL = $url2;
        } else {
            $qrImageURL = "";
        }

        // Final HTML
        if ($qrImageURL != "") {
            $QR_HTML = "<a onclick=\"ShowQRImageModal('$qrImageURL')\"><span class='badge badge-info cursor-pointer'>View QR</span></a>";
        } else {
            $QR_HTML = "<span class='badge badge-warning'>QR Missing</span>";
        }
    }
    ## ----------------------------------------------------------------------- ##

    $CategoryName = "<span style='font-size:0.8em;'>Category - Not Set</span>";
    if ($Category != "" && $Category != '-1') {
        if (isset($categories_array[$Category])) {
            $CategoryName = "<span style='font-size:0.8em;'>Category - " . $categories_array[$Category]['CategoryName'] . "</span>";
        }
    }

    $SubCategoryName = "<span style='font-size:0.8em;'>Sub Category - Not Set</span>";
    if ($SubCategory != "" && $SubCategory != '-1') {
        if (isset($sub_categories_array[$SubCategory])) {
            $SubCategoryName = "<span style='font-size:0.8em;'>Sub Category - " . $sub_categories_array[$SubCategory]['SubCategoryName'] . "</span>";
        }
    }

    $Raise_ticket = "OpenRaiseAMCTicket('$ID','$BranchID','$CreatedBy')";


    $EquipmentName_html = cleantext($branch_asset_data['EquipmentName'])."&nbsp;<a onclick='ViewEquipmentDetails($ID)'><i class='fal fa-external-link'></i></a>";
    $data[] = array(
       "CompanyBranches"=>$branch_asset_data['CompanyName'] . '<br>' .$branch_asset_data['BranchSite'],
        "EquipmentID"=>$branch_asset_data['ID'],
        "EquipmentName" => $EquipmentName_html . '<br>' . $CategoryName . '<br>' . $SubCategoryName,
        "Make_Model" => $branch_asset_data['Make'] . "<br>" . $branch_asset_data['Model'],
        "SNo" => $branch_asset_data['SNo'],
        "Capacity" => $branch_asset_data['Capacity'],
        "ManufacturingYear" => $branch_asset_data['ManufacturingYear'],
        "ServiceType" => $branch_asset_data['ServiceType'],
        "FloorNumber_EquipmentLocation" => $branch_asset_data['FloorNumber'] . "<br>" . $branch_asset_data['EquipmentLocation'],
        "PPM" => "<a onclick='ViewPPMTickets(" . $branch_asset_data['ID'] . ")'><span class='badge badge-primary cursor-pointer'>PPM Tickets</span></a>",
        "AMCTicket" => "<a onclick=$Raise_ticket><span class='badge badge-primary cursor-pointer'>Raise AMC Ticket</span></a>",
        "QRImage" => $QR_HTML,
        "Update" => "<a onclick='UpdateBranch_modal(" . $branch_asset_data['ID'] . ")' class='cursor-pointer'><i class='fal fa-edit' aria-hidden='true'></i></a>",
        "Action" => $status == 1 ? "<a onclick='DeactivateBranchAsset(" . $branch_asset_data['ID'] . ")' title='Inactivate Asset'><i class='fal fa-ban text-danger' aria-hidden='true'></i></a>" : "<a onclick='ActivateBranchAsset(" . $branch_asset_data['ID'] . ")' title='Activate Asset'><i class='fal fa-check-circle text-success' aria-hidden='true'></i></a>"
    );
}
    

## Response for DataTable
$response = array(
    "draw" => intval($draw),
    "iTotalRecords" => $totalRecords,
    "iTotalDisplayRecords" => $totalRecordwithFilter,
    "aaData" => $data
);

echo json_encode($response);

?>
