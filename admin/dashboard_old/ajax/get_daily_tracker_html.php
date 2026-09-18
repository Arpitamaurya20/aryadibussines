<?php
@session_start();
require_once('../../includes/autoloader.inc.php');
include('../../controllers/common_controllers.php');
if(isset($_POST))
{
	$UserType = $_POST['UserType'];
	$CorporateID = $_POST['CorporateID'];
	$filter_date = $_POST['filter_date'];
	$conn = _connectodb();
	$StartDate = explode(" - ",$filter_date)[0];
	$EndDate = explode(" - ",$filter_date)[1];
	$data = array();
    $data['start_date'] = $StartDate;
    $data['end_date'] = $EndDate;
    $data['CorporateID'] = $CorporateID;
    $corporate_ticket_obj = new Corporateticket($conn);
    $status_array = $corporate_ticket_obj->getCorporateTicketStatusArray("All");
    $data['CorporateID'] = $CorporateID;
    $daily_tracker_status = $corporate_ticket_obj->GetDailyTicketStatsbyStatus($data);
?>
<table class="table m-0 table-bordered" id="daily_tracker_html">
    <thead>
        <tr>
            <th>Date</th>
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
            ?>
        </tr>
    </thead>
    <tbody>
        <?php  
        $currentDate = new DateTime($EndDate);
        $endDate = new DateTime($StartDate);
        
        while ($currentDate >= $endDate) {
            $date_in_process = $currentDate->format('Y-m-d');
            echo "<tr>";
            echo "<td style='min-width:95px;'>".$currentDate->format('Y-m-d')."</td>";
            foreach($status_array as $status)
            {
                if(isset($daily_tracker_status[$date_in_process][$status['Status']]))
                {
                    echo "<td>".$daily_tracker_status[$date_in_process][$status['Status']]."</td>";
                }
                else
                {
                    echo "<td>0</td>";
                }
            }
            if($UserType == "Admin" && 0)
            {
                if(isset($daily_tracker_status[$date_in_process]['Generate OTP to Start']))
                {
                    echo "<td>".$daily_tracker_status[$date_in_process]['Generate OTP to Start']."</td>";
                }
                else
                {
                    echo "<td>0</td>";
                }
                if(isset($daily_tracker_status[$date_in_process]['Generate OTP to Close']))
                {
                    echo "<td>".$daily_tracker_status[$date_in_process]['Generate OTP to Close']."</td>";
                }
                else
                {
                    echo "<td>0</td>";
                }
            }
            echo "</tr>";

            $currentDate->modify('-1 day');
        }
        ?>
        
    </tbody>
</table>
<?php
}
?>