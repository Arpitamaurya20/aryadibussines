<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once("../corporate-tickets/controller/corporate_tickets_controller.php");
require_once('../controllers/common_controllers.php');
require_once('../includes/autoloader.inc.php');
$dbh = new Dbh();
$conn = $dbh->_connectodb();
$core = new Core();
$branch = new Branch($conn);
$branches_array = array();
$core->setTimeZone();
$branches_array_raw = $branch->setBranchArrayByCorporateID(152,'Active');
foreach($branches_array_raw as $ID=>$branch)
{
    $BranchCode = $branch['BranchCode'];
    $branches_array[$BranchCode] = $ID;  
}
$branch_invalid_array = array();

$uploader_tickets = $core->_getTableRecords($conn,'corporate_tickets_uploader',' where IsParsed = 0');
$uploader_tickets_array = array();
foreach($uploader_tickets as $ticket)
{
  $ClientTicketID = $ticket['ClientTicketID'];
  $uploader_tickets_array[$ClientTicketID] = $ticket;
}

$sql_ct = " where CorporateID = 152";
$ct_tickets = $core->_getTableRecords($conn,'corporate_tickets',$sql_ct);
$ct_tickets_array = array();
foreach($ct_tickets as $ticket)
{
  $ClientTicketID = $ticket['ClientTicketID'];
  $ct_tickets_array[$ClientTicketID] = $ticket;
}

if(1)
{
  $k=0;
  foreach($uploader_tickets as $ticket)
  {
    $Reason = "";
    extract($ticket);
    if($IsParsed)
    {
      continue;
    }
    else if(isset($ct_tickets_array[$ClientTicketID]))
    {
      // Do nothing
      $inserted = 0;
      $parsed = 1;
      echo "<br>Duplicate Ticket<br>";
      $sql = " IsParsed = $parsed,Inserted = $inserted where ID = $ID";
      $core->_UpdateTableRecords($conn,'corporate_tickets_uploader',$sql);
    }
    else
    {
        $inserted = 0;
        $parsed = 1;
        if($BranchID == -1)
        {
          $Reason = $Reason."Invalid Branch";
          echo "<br>".$Reason;
        }
        else
        {

          //Get City Branches
          $where = " where ID = $BranchID";
          $result_branch_details = $core->_getTableDetails($conn,'branch',$where);
          $CityName = $result_branch_details['BranchCity'];
          $BranchSite = $result_branch_details['BranchSite'];


          //Get City Corporate Lead
          $where = " where CityName = '$CityName'";
          $result_city_lead = $core->_getTableDetails($conn,'citydata',$where);
          $CorporateLead = $result_city_lead['CorporateLead'];
          //echo $ClientTicketID." - Lead - ".$CorporateLead."<br>";
          $Type = "R&M";
          $BranchAssetID = -1;
          $CreatedTime = date("H:i:s");

          $Message = $Description;
          $Message = $conn->real_escape_string($Message);

          $sql = "INSERT INTO corporate_tickets(CorporateID,BranchID,Type,BranchAssetID,Service,Subservice,Message,ClientTicketID,Description,CreatedDate,CreatedTime,CreatedBy,AssignedTo,Priority,Status,Remarks) VALUES (152,$BranchID,'$Type',$BranchAssetID,'$Category','$SubCategory','$Message','$ClientTicketID','','$CreatedDate','$CreatedTime','parser',$CorporateLead,'$Priority','$Status','')";
          echo $sql."<br>";

           $insert_ticket = $core->_InsertTableRecords($conn,$sql);
           if($insert_ticket['error'] == false)
           {

             $inserted = 1;
             $last_insert_id = $insert_ticket['last_insert_id'];
             $formatted_id = sprintf('%06d', $last_insert_id);
             if($Type == "AMC")
             {
                $TicketID = "CS-AMC-".$formatted_id;
             }
             else
             {
                $TicketID = "CS-RM-".$formatted_id;
             }

             $query_parameter = " TicketID = '$TicketID' where ID = $last_insert_id";
             $core->_UpdateTableRecords($conn,'corporate_tickets',$query_parameter);


             // Insert into history
             $data_history['TicketID'] = $last_insert_id;
             $data_history['CreatedDate'] = $CreatedDate;
             $data_history['CreatedTime'] = $CreatedTime;
             $data_history['CreatedBy'] = "parser";
             $data_history['Status'] = $Status;
             $data_history['AssignedTo'] = $CorporateLead;
             RecordTicketHistory($conn,$data_history);
           }


        }
        $sql = " IsParsed = $parsed,Inserted = $inserted where ID = $ID";
        $core->_UpdateTableRecords($conn,'corporate_tickets_uploader',$sql);

    }
    /*$k++;
    if($k==500)
    {
      break;
    }*/

  }
}

if(0)
{
  $dir = fopen("IDFC_tickets.csv", "r");
  $k=0;$i=0;
  while (($data = fgetcsv($dir, 3000, ",")) !== FALSE) 
  {
    $to_be_inserted = true;
    if($k<1)
    {
      $k++;
      continue;
    }
    $to_be_inserted = true;
    $ClientTicketID = $core->cleantext($data[0]);
    if(isset($uploader_tickets_array[$ClientTicketID]))
    {
      continue;
    }
    $BranchCode = $core->cleantext($data[1]);
    if(isset($branches_array[$BranchCode]))
    {
        $BranchID = $branches_array[$BranchCode];
    }
    else
    {
        $BranchID = -1;
        $to_be_inserted = false;
       /* if(in_array($BranchCode, $branch_invalid_array))
        {

        }
        else
        {
          array_push($branch_invalid_array,$BranchCode);
        }*/
    }
    $Category = $core->cleantext($data[2]);
    $SubCategory = $core->cleantext($data[3]);
    $Status = $core->cleantext($data[5]);
    if($Status == "In Progress")
    {
      $Status = "Work In Progress";
    }
    if($Status == "New")
    {
      $Status = "Raised";
    }


    $Priority = $core->cleantext($data[4]);
    if($Priority == "Critical")
    {
      $Priority = "High";
    }
    
    $Description = $core->cleantext($data[8]);
    $CreatedDate_original = $core->cleantext($data[6]);

    // Create a DateTime object from the original date string
    //$date_obj = DateTime::createFromFormat('m/d/Y', $CreatedDate_original);

    // Format the DateTime object to the desired format
    $CreatedDate = $CreatedDate_original;

    /*echo "<hr>";
    echo $k."<br>";
    echo $ClientTicketID."<br>";
    echo $BranchID."<br>";
    echo $Category."<br>";
    echo $SubCategory."<br>";
    echo $Status."<br>";
    echo $Priority."<br>";
    echo $Description."<br>";
    echo $CreatedDate_original."<br>";
    echo $CreatedDate."<br>";
    echo "<hr>";*/
    $k++;
    if($k==1000)
    {
      break;
    }
    $sql = "INSERT INTO corporate_tickets_uploader(ClientTicketID,BranchCode,BranchID,Category,SubCategory,Status,Priority,Description,CreatedDate_original,CreatedDate) VALUES ('$ClientTicketID','$BranchCode',$BranchID,'$Category','$SubCategory','$Status','$Priority','$Description','$CreatedDate_original','$CreatedDate')";
    $response = $core->_InsertTableRecords($conn,$sql);
    if($response['error'] == true)
    {
      echo "<br>".$ClientTicketID." Error ".$response['message'];
    }
    else
    {
      $i++;
    }
  }
  echo $i." rows are inserted";
}



//print_r($branch_invalid_array);

//fclose($dir);
//mysqli_close($connection);


?>

