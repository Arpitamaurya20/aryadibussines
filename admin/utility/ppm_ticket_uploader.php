<?php
include('../controllers/common_controllers.php');
$conn = _connectodb();
setTimeZone();
$CreatedDate = date('Y-m-d');
$CreatedTime = date('H:i:s');
$where = " where IsActive = 1";
$Company_temp_array = _getTableRecords($conn, 'company', $where);
foreach ($Company_temp_array as $Companydata) 
{
    $company_name = $Companydata['CompanyName'];
    $Company_array[$company_name]['ID'] = $Companydata['ID'];
}
$branches_array = array();
$where = " where IsParsed = 0";
$ppm_tickets_uploader_array = _getTableRecords($conn, 'temp_ppm_tickets_uploader', $where);
$k = 1;
foreach($ppm_tickets_uploader_array as $ppm_tickets_uploader)
{
  $ID = $ppm_tickets_uploader['ID'];
  echo "<hr>";
  echo " Parsing Row ".$k;
  echo "<br>";
  $to_be_inserted = true;
  $CompanyName = cleantext($ppm_tickets_uploader['CompanyName']);
  if(isset($Company_array[$CompanyName]['ID']))
  {
	     $CompanyID = $Company_array[$CompanyName]['ID'];
    	echo " Company ID - ".$CompanyID." : ";
    	// Get Branches of this Company ID
    	if(!isset($branches_array[$CompanyID]))
    	{
    		$where = " where CompanyID = $CompanyID";
    		$branches = _getTableRecords($conn,'branch',$where);
    		foreach($branches as $branch)
    		{
    			$branch_name = $branch['BranchSite'];
    			$branches_array[$CompanyID][$branch_name]['ID'] = $branch['ID'];
    		}
    	}
  }
  else
  {
  		echo " Company name invalid";
    	$to_be_inserted = false;
  }

  if(!$to_be_inserted)
    continue;

  $BranchName = cleantext($ppm_tickets_uploader['BranchName']);
	if(isset($branches_array[$CompanyID][$BranchName]))
	{
		$BranchID = $branches_array[$CompanyID][$BranchName]['ID'];
		echo " Branch ID - ".$BranchID." : ";
	}
	else
	{
		echo " Branch name invalid";
		$to_be_inserted = false;
	}
  if(!$to_be_inserted)
    continue;

	// Get Branch Assets
  $branch_assets = _getTableRecords($conn,'branch_assets',' where IsActive = 1 and BranchID = '.$BranchID);
  echo " Assets - ".sizeof($branch_assets);
  foreach($branch_assets as $asset)
  {
      $BranchAssetID = $asset['ID'];
      $PPMDate = $ppm_tickets_uploader['PPMQTR1'];
      $sql_ppm_ticket = "INSERT INTO ppm_tickets(CorporateID,BranchID,BranchAssetID,PPMDate,CreatedDate,CreatedTime,CreatedBy) VALUES ($CompanyID,$BranchID,$BranchAssetID,'$PPMDate','$CreatedDate','$CreatedTime','system')";
      $insert_ticket = _InsertTableRecords($conn,$sql_ppm_ticket);
      $intert_ticket_id = $insert_ticket['last_insert_id'];
      $formatted_id = sprintf('%06d', $intert_ticket_id);
      $TicketID = "CS-PPM-".$formatted_id;
      $query_parameter = " TicketID = '$TicketID' where ID = $intert_ticket_id";
      _UpdateTableRecords($conn,'ppm_tickets',$query_parameter); 
      echo $sql_ppm_ticket."<br>";

      $PPMDate = $ppm_tickets_uploader['PPMQTR2'];
      $sql_ppm_ticket = "INSERT INTO ppm_tickets(CorporateID,BranchID,BranchAssetID,PPMDate,CreatedDate,CreatedTime,CreatedBy) VALUES ($CompanyID,$BranchID,$BranchAssetID,'$PPMDate','$CreatedDate','$CreatedTime','system')";
      $insert_ticket = _InsertTableRecords($conn,$sql_ppm_ticket); 
      $intert_ticket_id = $insert_ticket['last_insert_id'];
      $formatted_id = sprintf('%06d', $intert_ticket_id);
      $TicketID = "CS-PPM-".$formatted_id;
      $query_parameter = " TicketID = '$TicketID' where ID = $intert_ticket_id";
      _UpdateTableRecords($conn,'ppm_tickets',$query_parameter);
      echo $sql_ppm_ticket."<br>"; 

      $PPMDate = $ppm_tickets_uploader['PPMQTR3'];
      $sql_ppm_ticket = "INSERT INTO ppm_tickets(CorporateID,BranchID,BranchAssetID,PPMDate,CreatedDate,CreatedTime,CreatedBy) VALUES ($CompanyID,$BranchID,$BranchAssetID,'$PPMDate','$CreatedDate','$CreatedTime','system')";
      $insert_ticket = _InsertTableRecords($conn,$sql_ppm_ticket); 
      $intert_ticket_id = $insert_ticket['last_insert_id'];
      $formatted_id = sprintf('%06d', $intert_ticket_id);
      $TicketID = "CS-PPM-".$formatted_id;
      $query_parameter = " TicketID = '$TicketID' where ID = $intert_ticket_id";
      _UpdateTableRecords($conn,'ppm_tickets',$query_parameter);
      echo $sql_ppm_ticket."<br>"; 

      $PPMDate = $ppm_tickets_uploader['PPMQTR4'];
      $sql_ppm_ticket = "INSERT INTO ppm_tickets(CorporateID,BranchID,BranchAssetID,PPMDate,CreatedDate,CreatedTime,CreatedBy) VALUES ($CompanyID,$BranchID,$BranchAssetID,'$PPMDate','$CreatedDate','$CreatedTime','system')";
      $insert_ticket = _InsertTableRecords($conn,$sql_ppm_ticket); 
      $intert_ticket_id = $insert_ticket['last_insert_id'];
      $formatted_id = sprintf('%06d', $intert_ticket_id);
      $TicketID = "CS-PPM-".$formatted_id;
      $query_parameter = " TicketID = '$TicketID' where ID = $intert_ticket_id";
      _UpdateTableRecords($conn,'ppm_tickets',$query_parameter);  
      echo $sql_ppm_ticket."<br>";
  }
  $sql_update_ppm_tickets_uploader = " IsParsed = 1 where ID = $ID";
  _UpdateTableRecords($conn,'temp_ppm_tickets_uploader',$sql_update_ppm_tickets_uploader);
}
?>