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
<table id="daily_tracker_html">
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
            echo "<td>".$currentDate->format('Y-m-d')."</td>";
            foreach($status_array as $status)
            {
                $count = isset($daily_tracker_status[$date_in_process][$status['Status']]) ? $daily_tracker_status[$date_in_process][$status['Status']] : 0;
                echo "<td>".formatTrackerValue($count, $status['Status'])."</td>";
            }
            if($UserType == "Admin" && 0)
            {
                $start_count = isset($daily_tracker_status[$date_in_process]['Generate OTP to Start']) ? $daily_tracker_status[$date_in_process]['Generate OTP to Start'] : 0;
                $close_count = isset($daily_tracker_status[$date_in_process]['Generate OTP to Close']) ? $daily_tracker_status[$date_in_process]['Generate OTP to Close'] : 0;
                echo "<td>".formatTrackerValue($start_count, 'Generate OTP to Start')."</td>";
                echo "<td>".formatTrackerValue($close_count, 'Generate OTP to Close')."</td>";
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