<?php
@session_start();
require_once('../../includes/autoloader.inc.php');
include('../../controllers/common_controllers.php');
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
<table class="table m-0 table-bordered" id="daily_tracker_html">
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
            $AccountName = $company_array[$account]['CompanyName'];
            echo "<td style='min-width:95px;'>".$AccountName."</td>";
            foreach($status_array as $status)
            {
                if(isset($account_wise_status[$account][$status['Status']]))
                {
                    echo "<td>".$account_wise_status[$account][$status['Status']]."</td>";
                }
                else
                {
                    echo "<td>0</td>";
                }
            }
            if($UserType == "Admin" && 0)
            {
                if(isset($account_wise_status[$account]['Generate OTP to Start']))
                {
                    echo "<td>".$account_wise_status[$account]['Generate OTP to Start']."</td>";
                }
                else
                {
                    echo "<td>0</td>";
                }
                if(isset($account_wise_status[$account]['Generate OTP to Close']))
                {
                    echo "<td>".$account_wise_status[$account]['Generate OTP to Close']."</td>";
                }
                else
                {
                    echo "<td>0</td>";
                }
            }
            echo "<td>".$account_wise_status[$account]['Total']."</td>";
            echo "</tr>";

        }
        ?>
        
    </tbody>
</table>
<?php
}
?>