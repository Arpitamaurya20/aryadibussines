<?php
@session_start();
require_once('../../includes/autoloader.inc.php');
include('../../controllers/common_controllers.php');
require_once __DIR__ . '/../inc/daily_tracker_helpers.php';

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
    $daily_tracker_status = $corporate_ticket_obj->GetDailyTicketStatsbyStatus($data);

    $grand_total = 0;
    $kpi_closed = 0;
    $kpi_open = 0;
    $kpi_hold = 0;
    $kpi_cancel = 0;
    $loopDate = new DateTime($EndDate);
    $loopEnd = new DateTime($StartDate);
    while ($loopDate >= $loopEnd) {
        $date_in_process = $loopDate->format('Y-m-d');
        foreach ($status_array as $status) {
            $count = isset($daily_tracker_status[$date_in_process][$status['Status']]) ? (int)$daily_tracker_status[$date_in_process][$status['Status']] : 0;
            $grand_total += $count;
            $tone = trackerTone($status['Status']);
            if ($tone === 'ok') { $kpi_closed += $count; }
            elseif ($tone === 'wait') { $kpi_hold += $count; }
            elseif ($tone === 'bad') { $kpi_cancel += $count; }
            else { $kpi_open += $count; }
        }
        $loopDate->modify('-1 day');
    }
    $day_count = (new DateTime($StartDate))->diff(new DateTime($EndDate))->days + 1;
    echo '<div id="dt_kpi_payload" hidden'
        .' data-total="'.(int)$grand_total.'"'
        .' data-closed="'.(int)$kpi_closed.'"'
        .' data-open="'.(int)$kpi_open.'"'
        .' data-hold="'.(int)$kpi_hold.'"'
        .' data-cancel="'.(int)$kpi_cancel.'"'
        .' data-days="'.(int)$day_count.'"></div>';
    echo '<div class="dt-loading">Updating…</div>';
    echo renderDailyTrackerTable($status_array, $daily_tracker_status, $StartDate, $EndDate);
}
