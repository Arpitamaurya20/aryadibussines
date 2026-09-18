<?php
// Enable error reporting
error_reporting(E_ALL);

// Display errors
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

require_once('../includes/autoloader.inc.php');
$dbh = new Dbh();
$conn = $dbh->_connectodb();
include('../controllers/common_controllers.php');
$conn = _connectodb();
setTimeZone();
$dir = fopen("GST_jll.csv", "r");
$CorporateID = 152;
$core = new Core();
$CreatedDate = date("Y-m-d");
$CreatedTime = date("H:i:s");
$CreatedBy = "parser";
$k=0;
$where = " where CompanyName like '%JLL%'";
$company_array = $core->_getTableRecords($conn,'company',$where);

while (($data = fgetcsv($dir, 1000, ",")) !== FALSE) 
{
  
  $State = cleantext($data[0]);

  // check for State
  $State = strtoupper($State);
  $filter = " where StateName = '$State'";
  $numrows = $core->_getTotalRows($conn,'state',$filter);
  if($numrows > 0)
  {

    $GST = cleantext($data[1]);
    $Address = cleantext($data[3]);

    // check if alreayd exist
    foreach($company_array as $company)
    {
      $CompanyID = $company['ID'];
      $filter = " where CompanyID = $CompanyID and CompanyState = '$State'";
      $numrows_gst = $core->_getTotalRows($conn,'company_state_gst',$filter);
      if($numrows_gst>0)
      {
        echo "<br> $State entry already exist";
      }
      else
      {

         $rowData = [
                      'CompanyID' => $CompanyID,
                      'CompanyState' => $State,
                      'GST' => $GST,
                      'Address' => $Address,
                      'CreatedDate' => $CreatedDate,
                      'CreatedTime' => $CreatedTime,
                      'CreatedBy' => $CreatedBy
                  ];
                  $response = $core->_InsertTableRecords_prepare($conn, 'company_state_gst', $rowData);
        print_r($response);
        echo "<br>";
      }
    }
  }
  else
  {
    echo "<br>$State not found";
  }
  
  
}
?>