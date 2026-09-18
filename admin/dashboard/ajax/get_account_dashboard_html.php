<?php
@session_start();
require_once('../../includes/autoloader.inc.php');
include('../../controllers/common_controllers.php');

if (!function_exists('formatTrackerValue')) {
    function formatTrackerValue($count, $status) {
        if ($count == 0) {
            return "<span class='tracker-zero'>0</span>";
        }
        
        $status = strtolower($status);
        $textClass = 'tracker-val-blue'; // default
        
        if (strpos($status, 'close') !== false || strpos($status, 'approved') !== false) {
            $textClass = 'tracker-val-green'; // green
        } elseif (strpos($status, 'escalat') !== false || strpos($status, 'reject') !== false || strpos($status, 'cancel') !== false) {
            $textClass = 'tracker-val-red'; // red
        } elseif (strpos($status, 'hold') !== false || strpos($status, 'pending') !== false) {
            $textClass = 'tracker-val-orange'; // orange
        }
        
        return "<span class='tracker-val {$textClass}'>{$count}</span>";
    }
}

if(isset($_POST))
{
	$UserType = $_POST['UserType'];
	$Type = $_POST['Type'];
	$filter_date = $_POST['filter_date'];
    $StateName = $_POST['StateName'];
    $sql_in_state_string = $_POST['sql_in_state_string'];
	$conn = _connectodb();
	$StartDate = explode(" - ",$filter_date)[0];
	$EndDate = explode(" - ",$filter_date)[1];
	$data = array();
    $data['start_date'] = $StartDate;
    $data['end_date'] = $EndDate;
    $data['Type'] = $Type;
    $data['StateName'] = $StateName;
    $data['sql_in_state_string'] = $sql_in_state_string;
    $corporate_ticket_obj = new Corporateticket($conn);
    $ppm_ticket_obj = new Ppmtickets($conn);
    $company_obj = new Company($conn);
    $company_array = $company_obj->setCompanyArray('All');
    if($Type == "R&M" || $Type == "Supply" || $Type == "AMC" || $Type == "Projects")
    {
        $status_array = $corporate_ticket_obj->getCorporateTicketStatusArray("All");
        $account_wise_status = $corporate_ticket_obj->GetTicketsStatsbyAccounts($data);
    }
    else
    {
        $status_array = $ppm_ticket_obj->getPPMTicketStatusArray("All");
        $account_wise_status = $ppm_ticket_obj->GetPPMTicketsStatsbyAccounts($data);
    }
    
?>
<table id="daily_tracker_html">
    <thead>
        <tr>
            <th>Account</th>
            <?php 
            foreach($status_array as $status)
            {
                echo "<th>".$status['Status']."</th>";
            }
            if($UserType == "Admin" && 0)
            {
               echo "<th>Generate OTP to Start</th>";
               echo "<th>Generate OTP to Close</th>";
            }
            echo "<th>Total</th>";
            ?>
        </tr>
    </thead>
    <tbody>
        <?php  
        foreach($account_wise_status as $account=>$account_stats)
        {
            echo "<tr>";
            $AccountName = isset($company_array[$account]['CompanyName']) ? $company_array[$account]['CompanyName'] : "Unknown Company";
            echo "<td>".$AccountName."</td>";
            foreach($status_array as $status)
            {
                $count = isset($account_wise_status[$account][$status['Status']]) ? $account_wise_status[$account][$status['Status']] : 0;
                echo "<td>".formatTrackerValue($count, $status['Status'])."</td>";
            }
            if($UserType == "Admin" && 0)
            {
                $start_count = isset($account_wise_status[$account]['Generate OTP to Start']) ? $account_wise_status[$account]['Generate OTP to Start'] : 0;
                $close_count = isset($account_wise_status[$account]['Generate OTP to Close']) ? $account_wise_status[$account]['Generate OTP to Close'] : 0;
                echo "<td>".formatTrackerValue($start_count, 'Generate OTP to Start')."</td>";
                echo "<td>".formatTrackerValue($close_count, 'Generate OTP to Close')."</td>";
            }
            $total_count = isset($account_wise_status[$account]['Total']) ? $account_wise_status[$account]['Total'] : 0;
            echo "<td class='tracker-total'>".$total_count."</td>";
            echo "</tr>";

        }
        ?>
        
    </tbody>
</table>
<?php
}
?>