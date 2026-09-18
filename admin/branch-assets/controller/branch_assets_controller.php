
<?php
function getAllBranchAssets($conn,$BranchID)
{
	if($BranchID == -1)
	{
		$where = " where IsActive = 1";
	}
	else
	{
		$where = " where BranchID = $BranchID";
	}
	$response = _getTableRecords($conn,'branch_assets', $where);
	return $response;
}

function getAllBranchAssetsList($conn, $BranchID, $CorporateID)
{
    if($BranchID == -1)
    {
        if($CorporateID != -1)
        {
            return _getAllBranchAssets(
                $conn,
                $CorporateID,
                -1,
                "",
                ""
            );
        }
        else
        {
            $where = "WHERE IsActive = 1";
        }
    }
    else
    {
        $where = "WHERE BranchID = $BranchID AND IsActive = 1";
    }

    return _getTableRecords($conn, 'branch_assets', $where);
}

function InsertBranchAssets($conn,$data)
{
	$branch_id = $data["branch_id"];
	$equipment_name = $data["equipment_name"];
    $make = $data["make"];
	$model = $data["model"];
    $serial_no = $data["serial_no"];
	$capacity = $data["capacity"];
	$quantity = $data["quantity"];
    $uom = $data["uom"];
	$unit_rate = $data["unit_rate"];
    $amount = $data["amount"];
	$manufacturing_year = $data["manufacturing_year"];
	$equipment_age = $data["equipment_age"];
	$service_type = $data["service_type"];
	$categories = $data["categories"];
	$sub_categories = $data["sub_categories"];
	$tat = $data["tat"];
	$AMCStartDate = $data["amc_start_date"];
	$AMCEndDate = $data["amc_end_date"];
	$SOW = '';
	$floor_number = $data["floor_number"];
	$equipment_location = $data["equipment_location"];
    $description = $data["description"];
    $CreatedBy = $data['CreatedBy'];
    $PPMInterval = $data["PPMInterval"];
    $CreatedDate = date('Y-m-d');

    if (isset($_FILES['sow_img']['name'])  && $_FILES['sow_img']['name'] != '')
		{
			//echo $_FILES['sow_img']['name'];
			$extn_pan = explode('.', $_FILES["sow_img"]["name"]);
			$SOW   = $branch_id."_SOW.".$extn_pan[1];
			$path = "../media/".$SOW;
			//echo "<br>".$path;
			move_uploaded_file($_FILES["sow_img"]["tmp_name"], $path);
		}

    $branch_assets_query = "INSERT INTO branch_assets (BranchID,EquipmentName,Make,Model,SNo,Capacity,Qty,UoM,UnitRate,Amount,ManufacturingYear,EquipmentAge,ServiceType,Category,SubCategory,Tat,AMCStartDate,AMCEndDate,SOW,FloorNumber,EquipmentLocation,Description,PPMInterval,CreatedBy,CreatedDate ) VALUES('$branch_id','$equipment_name','$make','$model','$serial_no','$capacity','$quantity','$uom','$unit_rate','$amount','$manufacturing_year','$equipment_age','$service_type','$categories','$sub_categories','$tat','$AMCStartDate','$AMCEndDate','$SOW','$floor_number','$equipment_location','$description','$PPMInterval','$CreatedBy','$CreatedDate')";
    $response = _InsertTableRecords($conn, $branch_assets_query);

    $response['message'] = "Branch Assets Added to the System";
    return $response;
}

function UpdateBranchAssets($conn,$data)
{
	$branch_id = $data["branch_id"];
	$equipment_name = $data["equipment_name"];
    $make = $data["make"];
	$model = $data["model"];
    $serial_no = $data["serial_no"];
	$capacity = $data["capacity"];
	$quantity = $data["quantity"];
    $uom = $data["uom"];
	$unit_rate = $data["unit_rate"];
    $amount = $data["amount"];
	$manufacturing_year = $data["manufacturing_year"];
	$equipment_age = $data["equipment_age"];
	$service_type = $data["service_type"];
	$categories = $data["categories"];
	$sub_categories = $data["sub_categories"];
	$tat = $data["tat"];
	$AMCStartDate = $data["amc_start_date"];
	$AMCEndDate = $data["amc_end_date"];
	$SOW = '';
	$floor_number = $data["floor_number"];
	$equipment_location = $data["equipment_location"];
    $description = $data["description"];
	$branch_assets_id = $data['form_id'];
    $CreatedBy = $data['CreatedBy'];
    $CreatedDate = date('Y-m-d');
	$PPMInterval=$data['PPMInterval'];
    $old_branch_assets_details = GetBranchAssetsbyID($conn,$branch_assets_id);
    //var_dump($old_branch_assets_details);
    if($branch_id == $old_branch_assets_details['BranchID'] && $equipment_name == $old_branch_assets_details['EquipmentName'] && $make == $old_branch_assets_details['Make'] && $model == $old_branch_assets_details['Model'] && $serial_no == $old_branch_assets_details['SNo'] && $capacity == $old_branch_assets_details['Capacity']  && $quantity == $old_branch_assets_details['Qty']  && $uom == $old_branch_assets_details['UoM']  && $unit_rate == $old_branch_assets_details['UnitRate']  && $amount == $old_branch_assets_details['Amount']  && $manufacturing_year == $old_branch_assets_details['ManufacturingYear'] && $equipment_age == $old_branch_assets_details['EquipmentAge'] && $service_type == $old_branch_assets_details['ServiceType'] && $tat == $old_branch_assets_details['Tat'] && $categories == $old_branch_assets_details['Category'] && $sub_categories == $old_branch_assets_details['SubCategory'] && $floor_number == $old_branch_assets_details['FloorNumber'] && $equipment_location == $old_branch_assets_details['EquipmentLocation'] && $AMCStartDate == $old_branch_assets_details['AMCStartDate'] && $AMCEndDate == $old_branch_assets_details['AMCEndDate'] && $description == $old_branch_assets_details['Description'] && $SOW == $old_branch_assets_details['SOW'] && $PPMInterval== $old_branch_assets_details['PPMInterval'] )
    {
    	$response['message'] = "No changes to update";
    	$response['error'] = true;
    }
    else
    {

    	$update_param = " BranchID = '$branch_id',EquipmentName='$equipment_name', Make='$make', Model='$model', SNo='$serial_no',
		 Capacity = '$capacity',Qty='$quantity', UoM='$uom', UnitRate='$unit_rate', Amount='$amount', ManufacturingYear='$manufacturing_year',
		 EquipmentAge='$equipment_age', ServiceType='$service_type', Tat = '$tat', AMCStartDate = '$AMCStartDate', AMCEndDate = '$AMCEndDate' , Category = '$categories', SubCategory = '$sub_categories' ,FloorNumber='$floor_number', EquipmentLocation='$equipment_location', Description='$description', PPMInterval='$PPMInterval'
		  where ID= $branch_assets_id";
    	$response = _UpdateTableRecords($conn,'branch_assets', $update_param);

    	if($response['error'] == false)
    	{
    		$response['message'] = "Branch Assets Details Updated";
    	}
    }
	if (isset($_FILES['sow_img']['name'])  && $_FILES['sow_img']['name'] != '')
    {
        $extn_pan = explode('.', $_FILES["sow_img"]["name"]);
        $SOW   = $branch_id."_sow.".$extn_pan[1];
        $path = "../media/".$SOW;
        move_uploaded_file($_FILES["sow_img"]["tmp_name"], $path);
		$update_img = "UPDATE branch_assets SET SOW='$SOW' WHERE ID=$branch_assets_id";
	    $result = mysqli_query($conn, $update_img);
    }
    return $response;
}

function GetBranchAssetsbyID($conn,$ID)
{
	$where = " where ID = $ID";
	$company_details = _getTableDetails($conn,'branch_assets', $where);
	return $company_details;
}

function DeleteBranchAssets($conn,$data)
{
	// Get Region Details
	//$where = " where ID = $data[ID]";
	$where_update = " IsActive = 0 where ID = ".$data['ID'];
	//$query = delete_identity_filter($conn,"branch_assets",$where);
	$update_asset = _UpdateTableRecords($conn,'branch_assets',$where_update);
	return $update_asset;
}

function getTotalBranchAssets($conn, $BranchID)
{
	$sql = "Select COUNT(*) as branch_assets_count from branch_assets where BranchID = $BranchID";
	$result=mysqli_query($conn,$sql);
	if($result->num_rows>0)	
	{
		$row = $result->fetch_assoc();
		return $row['branch_assets_count'];
	}
	else
	{
		return 0;
	}
}

function _getAllBranchAssets($conn,$CorporateID,$BranchID,$searchQuery,$filter){
	$response = array();
	$where_corporate = "";
	if($CorporateID != -1)
	{
		$where_corporate = " AND b.CompanyID = $CorporateID";
	}
	$where_branch = "";
	if($BranchID != -1)
	{
		$where_branch = " AND b.ID = $BranchID";
	}
	$where = " where 1 ".$where_corporate.$where_branch.$filter;

	$sql = "SELECT ba.*,b.BranchSite,c.CompanyName FROM branch_assets ba INNER JOIN branch b ON ba.BranchID = b.ID INNER JOIN company c ON b.CompanyID = c.ID $where";
	//echo $sql;
	$result = mysqli_query($conn, $sql);
	if ($result) {
		if ($result->num_rows > 0) {
			while ($row = $result->fetch_assoc()) {
				array_push($response, $row);
			}
		}
	} else {
		//echo $sql;
	}
	return $response;
}


?>