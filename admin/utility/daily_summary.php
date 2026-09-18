<?php 
chdir(dirname(__FILE__));
require '../mail/PHPMailer-master/src/Exception.php';
require '../mail/PHPMailer-master/src/PHPMailer.php';
require '../mail/PHPMailer-master/src/SMTP.php';

require ('../mail/include/mail-config.php');
require ('../mail/include/common-mail-design.php');

include('../controllers/common_controllers.php');
include('../corporate-tickets/controller/corporate_tickets_controller.php');
include('../company/controller/company_controller.php');
include('../branch/controller/branch_controller.php');
include('../branch-assets/controller/branch_assets_controller.php');
include('../dashboard/controller/dashboard_controller.php');
$conn = _connectodb();
setTimeZone();
$currentDate = date('Y-m-d'); // Get the current date
$previousDate = date('Y-m-d', strtotime($currentDate . ' -1 day')); // Subtract one day from the current date

//echo $previousDate; // Output the previous date
// get corporate tickets
$corporate_array = getAllCompanies($conn);
$company_array_key = generateArraywithKey($corporate_array);

//print_r($company_array_key);

$branches = getAllBranches($conn,-1);
$branch_array_key = generateArraywithKey($branches);

$branch_assets = getAllBranchAssets($conn,-1);
$branch_assets_array_key = generateArraywithKey($branch_assets);

$where = " where CreatedDate = '$previousDate'";
$corporate_tickets = _getTableRecords($conn,'corporate_tickets',$where);

$current_status = GetGroupedTicketStatusByCorporate($conn,$corporate_array);
//var_dump($current_status);
$html_status = "";
$html_status .= '<table border="0" cellpadding="0" cellspacing="0" style="border-collapse: collapse; width: 100%; max-width: 600px;font-family:\'Verdana\'">';
$complete_status = array();
foreach($current_status as $corporate_array_temp)
{
	//echo "<br><hr>";
	$html_status .= "<th colspan='2' style='padding: 10px; background-color: #f0f0f0; text-align: center;'>";
	$html_status .= $corporate_array_temp['CorporateName'];
	$html_status .= "</th>";
	//echo $corporate_array_temp['CorporateName'];
	foreach($corporate_array_temp as $status=>$status_number)
	{
		if($status!="" && $status!="CorporateName")
		{
			//echo "<br>";
			$html_status .= "<tr>";
			$html_status .= "<td style='padding: 10px; border: 1px solid #cccccc;'>".$status."</td>";
			if(isset($complete_status[$status]))
			{
				$complete_status[$status] = $complete_status[$status]+$status_number;
			}
			else
			{
				$complete_status[$status] = $status_number;
			}
			$html_status .= "<td style='padding: 10px; border: 1px solid #cccccc;'>".$status_number."</td>";
			$html_status .= "</tr>";
		}
	}

	
}
$html_status .= "<th colspan='2' style='padding: 10px; background-color: #f0f0f0; text-align: center;'>";
$html_status .= "Total Status";
$html_status .= "</th>";
foreach($complete_status as $status_key=>$stat_num)
{
	$html_status .= "<tr>";
	$html_status .= "<td style='padding: 10px; border: 1px solid #cccccc;'>".$status_key."</td>";
	$html_status .= "<td style='padding: 10px; border: 1px solid #cccccc;'>".$stat_num."</td>";
	$html_status .= "</tr>";
}
$html_status .= "</table>";


//print_r($corporate_tickets);
$html = '<table border="0" cellpadding="0" cellspacing="0" style="border-collapse: collapse; width: 100%; max-width: 600px; font-family:\'Verdana\'">';
$html = $html."<tr>";
$html = $html."<th style='padding: 10px; background-color: #f0f0f0; text-align: center;'>TicketID</th>";
$html = $html."<th style='padding: 10px; background-color: #f0f0f0; text-align: center;'>Corporate</th>";
$html = $html."<th style='padding: 10px; background-color: #f0f0f0; text-align: center;'>Branch</th>";
$html = $html."<th style='padding: 10px; background-color: #f0f0f0; text-align: center;'>Type</th>";;
$html = $html."<th style='padding: 10px; background-color: #f0f0f0; text-align: center;'>Prioirity</th>";
$html = $html."<th style='padding: 10px; background-color: #f0f0f0; text-align: center;'>Status</th>";
$html = $html."</tr>";
foreach($corporate_tickets as $corporate_ticket)
{
	extract($corporate_ticket);
 	$Corporate = $company_array_key[$CorporateID]['CompanyName'];
    $Branch = $branch_array_key[$BranchID]['BranchSite'];
	$html = $html."<tr>";
	$html = $html."<td style='padding: 10px; border: 1px solid #cccccc;'>".$TicketID."</th>";
	$html = $html."<td style='padding: 10px; border: 1px solid #cccccc;'>".$Corporate."</th>";
	$html = $html."<td style='padding: 10px; border: 1px solid #cccccc;'>".$Branch."</th>";
	$html = $html."<td style='padding: 10px; border: 1px solid #cccccc;'>".$Type."</th>";
	$html = $html."<td style='padding: 10px; border: 1px solid #cccccc;'>".$Priority."</th>";
	$html = $html."<td style='padding: 10px; border: 1px solid #cccccc;'>".$Status."</th>";
	$html = $html."</tr>";
}
$html = $html."</table>";



$mail->Subject  = 'TechXpert Corporate : Daily Summary Email ['.$previousDate.']';
$body_middle = "
					<tr>
					  <td height='20' style='line-height:1px;font-size:1px'>&nbsp;</td>
					</tr>
					<tr>
					  <td style='vertical-align:bottom;padding-bottom:2%;'><span style='font-size:20px;color:#444e61;font-weight:bold;'>Overall Summary >>
						</td>
					</tr>
					$html_status
					<tr>
					  <td height='24' style='line-height:1px;font-size:1px'>&nbsp;</td>
					</tr>

					<tr>
					  <td height='20' style='line-height:1px;font-size:1px'>&nbsp;</td>
					</tr>
					<tr>
					  <td style='vertical-align:bottom;padding-bottom:2%;'><span style='font-size:16px;color:#444e61;font-weight:bold'>Please find the Corporate Tickets raised dated $previousDate:
						</td>
					</tr>
					$html
					<tr>
					  <td height='24' style='line-height:1px;font-size:1px'>&nbsp;</td>
					</tr>
					
					<br>
					";

$mail->Body = $mail_header.$body_middle;
$mail->addAddress("ceo@techxpertindia.in");
$mail->addAddress("pgfiry@gmail.com");
if(!$mail->send())
{
	echo "Error";
	//$result_array["error"] = true;
	//$result_array["message"] = "Error occured";
	//echo "There is some technical error right now. Our technical team is working on it to get the services back. Thank you for your patience.";
}
else
{
	echo "Mail Sent";
}
?>