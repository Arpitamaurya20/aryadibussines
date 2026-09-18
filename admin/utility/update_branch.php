<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once('../includes/autoloader.inc.php');
$dbh = new Dbh();
$conn = $dbh->_connectodb();
include('../controllers/common_controllers.php');
$conn = _connectodb();
setTimeZone();
$dir = fopen("BranchName_20082024.csv", "r");
$core = new Core();
$CreatedDate = date("Y-m-d");
$CreatedTime = date("H:i:s");
$employee_obj = new Employee($conn);
$employees_array = $employee_obj->setEmployeeArrayByName("All");
$k=0;
$i = 1;
while (($data = fgetcsv($dir, 1000, ",")) !== FALSE) 
{
  if($k==0)
  {
    $k++;
    continue;
  }
  
  $BranchCode = cleantext($data[2]);
  echo "<br>".$i."-".$BranchCode;
  $i++;
  $filter = " where BranchCode = '$BranchCode'";
  $rows = $core->_getTotalRows($conn,'branch',$filter);
  if($rows>0)
  {
      echo  " - BranchCode Available";
      $branch_details = $core->_getTableDetails($conn,'branch',$filter);
      $BranchID = $branch_details['ID'];
      $sql_update = "";
      $BranchSite = $data[1];
      $BranchEmail = $data[3];
      $Branch = $data[3];
      $BranchMobile = cleantext($data[4]);
      $BranchAddress1 = cleantext($data[6]);
      $BranchAddress2 = cleantext($data[7]);
      $BranchCity = cleantext($data[8]);
      $filter_city = " where CityName = '$BranchCity'";
      $rows_city = $core->_getTotalRows($conn,'citydata',$filter_city);
      if($rows_city > 0)
      {
        echo " - City Available";
      }
      else
      {
        echo " - City Not Available";
        continue;
      }
      $BranchState = cleantext($data[9]);
      $filter_state = " where StateName = '$BranchState'";
      $rows_state = $core->_getTotalRows($conn,'state',$filter_state);
      if($rows_state > 0)
      {
        echo " - Sate Available";
      }
      else
      {
        echo " - Sate Not Available";
        continue;
      }
      $account_branch_manager = $core->cleantext($data[12]);
      if(isset($employees_array[$account_branch_manager]))
      {
        $AccountBranchManagerID = $employees_array[$account_branch_manager]['ID'];
        if($AccountBranchManagerID != "")
        {
          echo " - Employee Available";
          $update_param = "AccountBranchManager = '$AccountBranchManagerID'  where ID= $BranchID";
          //$response = $core->_UpdateTableRecords($conn,'branch', $update_param);
          // check if already account manager is added or not
          /*$filter = " where EmployeeID = $AccountBranchManagerID and Role = 'Account Branch Manager'";
          if($core->check_unique_identity_filter($conn,'user_roles', $filter))
          {
            $sql_insert_account_branch_manager_role = "INSERT INTO user_roles(EmployeeID,Role,CreatedDate,CreatedTime) VALUES ($AccountBranchManagerID,'Branch Account Manager','$CreatedDate','$CreatedTime')";
            $response = $core->_InsertTableRecords($conn, $sql_insert_account_branch_manager_role);
          }*/

         $rowData = [
                'BranchSite' => $BranchSite,
                'BranchEmail' => $BranchEmail,
                'BranchMobile' => $BranchMobile,
                'BranchAddress1'=>$BranchAddress1,
                'BranchAddress2'=>$BranchAddress2,
                'BranchCity'=>$BranchCity,
                'BranchState'=>$BranchState,
                'AccountBranchManager'=>$AccountBranchManagerID

            ];
            $whereCondition = [
                'ID' => $BranchID
            ];
            $response = $core->_UpdateTableRecords_prepare($conn, 'branch', $rowData, $whereCondition);
            if($response['error'] == false)
            {
              echo " - Branch Updated";
            }
            else
            {
              echo " - ".$response['message'];
            }
          
        }
      }
      else
      {
        echo " - Employee Not Found";
      }
      
      /*$sql_update = $sql_update." where BranchCode = '$BranchCode'";
      echo "<br><br>".$sql_update;
      $response = $core->_UpdateTableRecords($conn,'branch',$sql_update);
      echo "<br>".$response['message'];*/
      /*$sql = "INSERT INTO `corporate_rate_card`(`CompanyID`, `Type`, `Category`, `SubCategory`, `LineItemName`, `Make`, `HSN`, `ARCCode`, `UoM`, `Price`, `Tax`, `CreatedDate`, `CreatedTime`, `CreatedBy`)VALUES($CorporateID,'$Type','$Category','$SubCategory','$LineItemName','$Make','$HSN','$ARCCode','$UoM','$Price','$Tax','$CreatedDate','$CreatedTime','$CreatedBy')";
      $response = _InsertTableRecords($conn,$sql);
      print_r($response);*/

  }
  else
  {
    echo " - Not Found";
  }

  
  
}
?>