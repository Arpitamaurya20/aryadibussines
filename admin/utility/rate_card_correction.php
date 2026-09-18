<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once('../includes/autoloader.inc.php');
$dbh = new Dbh();
$conn = $dbh->_connectodb();
include('../controllers/common_controllers.php');
$conn = _connectodb();
$conn->set_charset('utf8mb4');
$sql = "Select * from corporate_ticket_quotation_items where LineItemID NOT IN (Select ID from corporate_rate_card)";
$core = new Core();
$response = array();
$result = mysqli_query($conn, $sql);
if ($result) {
  if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) 
    {
        $LineItemID = $row['LineItemID'];
        $where = " where ID = $LineItemID";
        $numrows = $core->_getTotalRows($conn,'corporate_rate_card_old',$where);
        if($numrows > 0)
        {
          $sql_insert = "INSERT INTO corporate_rate_card (ID, CompanyID, Type, Category, SubCategory, LineItemName, Make, HSN, ARCCode, UoM, Price, Tax, CreatedDate, CreatedTime, CreatedBy, ARCItem, IsActive)
          SELECT ID, CompanyID, Type, Category, SubCategory, LineItemName, Make, HSN, ARCCode, UoM, Price, Tax, CreatedDate, CreatedTime, CreatedBy, ARCItem, IsActive
          FROM corporate_rate_card_old
          WHERE ID = $LineItemID";

          $result_insert = mysqli_query($conn, $sql_insert);

          // Execute the statement
          if ($result_insert) {
              echo "<br>Records inserted successfully.";
          } else {
              echo "Error: " . mysqli_error($conn);;
          }
        }
        else
        {
          echo "<br> $LineItemID not found in old.";
        }
    }
  }
} else {
  //echo $sql;
}

?>