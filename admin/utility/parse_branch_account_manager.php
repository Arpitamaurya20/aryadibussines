<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
require_once('../includes/autoloader.inc.php');
$dbh = new Dbh();
$conn = $dbh->_connectodb();

$core = new Core();
$core->setTimeZone();
$CreatedDate = date("Y-m-d");
$CreatedTime = date("H:i:s");
$employee_obj = new Employee($conn);
$employees_array = $employee_obj->setEmployeeArrayByName("All");
//print_r($employees_array);
//die();
$dir = fopen("AccountBranchManager_07_08.csv", "r");
$k=0;
while (($data = fgetcsv($dir, 1000, ",")) !== FALSE) 
{
	$Branch_Name = $data[0];
	$where = " where BranchSite = '$Branch_Name'";
	$Branchdetails = $core->_getTableDetails($conn,'branch',$where);
	echo "<br>".$Branch_Name."-";
	if($Branchdetails != null)
	{
		/*$BranchID = $Branchdetails['ID'];
		$AccountBranchManagerID = 14;
		$update_param = "AccountBranchManager = '$AccountBranchManagerID'  where ID= $BranchID";
		$response = $core->_UpdateTableRecords($conn,'branch', $update_param);
		if($response['error'] == false)
		{
			echo "Updated";
		}
		else
		{
			echo "Error";
		}*/
		if(1)
		{
			if(1)
			{
				$BranchID = $Branchdetails['ID'];
				$account_branch_manager = $core->cleantext($data[1]);
				if(isset($employees_array[$account_branch_manager]))
				{
					$AccountBranchManagerID = $employees_array[$account_branch_manager]['ID'];
					echo " ".$BranchID." ".$AccountBranchManagerID;
					if($AccountBranchManagerID != "")
					{
						$update_param = "AccountBranchManager = '$AccountBranchManagerID'  where ID= $BranchID";
		    			$response = $core->_UpdateTableRecords($conn,'branch', $update_param);
						// check if already account manager is added or not
						$filter = " where EmployeeID = $AccountBranchManagerID and Role = 'Account Branch Manager'";
						if($core->check_unique_identity_filter($conn,'user_roles', $filter))
						{
							$sql_insert_account_branch_manager_role = "INSERT INTO user_roles(EmployeeID,Role,CreatedDate,CreatedTime) VALUES ($AccountBranchManagerID,'Branch Account Manager','$CreatedDate','$CreatedTime')";
							$response = $core->_InsertTableRecords($conn, $sql_insert_account_branch_manager_role);
						}
						
					}
				}
				else
				{
					echo " Employee Not Found";
				}
			}
			else
			{
				echo " Already Set";
			}
			
		}
	}
	else
	{
		echo "Not Found";
	}
	$k++;
	if($k==2500)
	{
		break;
	}
	
}
?>