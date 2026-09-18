<?php
include("../../controllers/common_controllers.php");
include("../../corporate-tickets/controller/corporate_tickets_controller.php");

if (isset($_POST['CorporateID'])) {
    $conn = _connectodb();
    $branchList = getBranchByCorporateID($conn, $_POST['CorporateID']);
    echo '<option value="">Please Select Branch</option>';
    foreach ($branchList as $branch) {
        echo '<option value="' . (int) $branch['ID'] . '">' . htmlspecialchars($branch['BranchSite']) . '</option>';
    }
}
