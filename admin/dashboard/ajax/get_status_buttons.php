<?php
ini_set('display_errors', 0);
error_reporting(0);
@session_start();
include('../../includes/autoloader.inc.php');
include('../controller/dashboard_controller.php');
$CorporateID = $_POST['CorporateID'];
$filter_date = $_POST['filter_date'];
$state_filter = $_POST['state_filter'];
$region_filter = $_POST['region_filter'];

$dbh = new Dbh();
$conn = $dbh->_connectodb();

$core = new Core();

$corporate_tickets_obj = new Corporateticket($conn);   
$corporate_status_array = $corporate_tickets_obj->getCorporateTicketStatusArray('All'); 


$company_obj = new Company($conn);
$Tendor = "";
if($CorporateID != -1)
{
    $company_details = $company_obj->GetCompanyDetailsbyID($CorporateID);
    $Tendor = $company_details['CompanyTendor'];
}
$AMC = false;
if(strpos($Tendor,'AMC') !== false)
{
    $AMC = true;
}
if($CorporateID == -1)
{
    $AMC = true;
}

// State Corporate Lead
$filter = array();
$sql_in_state_string = "";
if(isset($_POST['sql_in_state_string']))
    $sql_in_state_string = $_POST['sql_in_state_string'];
$sql_in_branch_account_string = "";
if(isset($_POST['sql_in_branch_account_string']))
    $sql_in_branch_account_string = $_POST['sql_in_branch_account_string'];
$filter['sql_in_state_string'] = $sql_in_state_string;
$filter['sql_in_branch_account_string'] = $sql_in_branch_account_string;
$filter['filter_date'] = $filter_date;
$filter['state'] = $state_filter;
$filter['region'] = $region_filter;
applyAnalyticsBranchFilter($filter);

function getLucideIconForStatus($status) {
    $statusLower = strtolower(trim($status));
    if (strpos($statusLower, 'overdue') !== false) return 'alert-triangle';
    if (strpos($statusLower, 'closed') !== false) return 'check-circle-2';
    if (strpos($statusLower, 'planned') !== false) return 'calendar-clock';
    if (strpos($statusLower, 'assigned') !== false) return 'user-check';
    if (strpos($statusLower, 'work in progress') !== false) return 'clock';
    if (strpos($statusLower, 'open') !== false || strpos($statusLower, 'raised') !== false) return 'folder-open';
    if (strpos($statusLower, 'cancel') !== false) return 'x-circle';
    if (strpos($statusLower, 'submitted') !== false) return 'check-square';
    if (strpos($statusLower, 'hold') !== false) return 'pause-circle';
    if (strpos($statusLower, 'completed') !== false) return 'check-circle';
    if (strpos($statusLower, 'pending') !== false) return 'hourglass';
    return 'activity'; // default icon
}

function getProfessionalColorForStatus($status) {
    $statusLower = strtolower(trim($status));
    if (strpos($statusLower, 'overdue') !== false) return '#E11D48';
    if (strpos($statusLower, 'closed') !== false || strpos($statusLower, 'completed') !== false) return '#059669';
    if (strpos($statusLower, 'work in progress') !== false) return '#D97706';
    if (strpos($statusLower, 'hold by customer') !== false) return '#EA580C';
    if (strpos($statusLower, 'hold') !== false || strpos($statusLower, 'pending') !== false) return '#CA8A04';
    if (strpos($statusLower, 'cancel') !== false) return '#475569';
    if (strpos($statusLower, 'assigned') !== false) return '#2563EB';
    if (strpos($statusLower, 'planned') !== false) return '#7C3AED';
    if (strpos($statusLower, 'submitted') !== false) return '#0D9488';
    if (strpos($statusLower, 'open') !== false || strpos($statusLower, 'raised') !== false) return '#0284C7';
    return '#0369A1';
}

function kpiSoftBackground($hex)
{
    $hex = ltrim((string)$hex, '#');
    if (strlen($hex) !== 6) {
        return 'rgba(3, 105, 161, 0.12)';
    }
    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));
    return 'rgba('.$r.','.$g.','.$b.',0.14)';
}

function renderModernAnalyticsStatusCount($count, $color, $ticket_scope, $ticket_type, $ticket_status, $status_label, $section_label)
{
	$text_style = 'color: #0F172A; font-size: 1.55rem; font-weight: 800; line-height: 1.1; margin-bottom: 0px;';
	$base_class = 'd-inline-block';
	if((int)$count > 0)
	{
		return '<span class="'.$base_class.' cursor-pointer analytics-status-ticket-link" style="'.$text_style.'"'
			.' data-ticket-scope="'.htmlspecialchars($ticket_scope, ENT_QUOTES, 'UTF-8').'"'
			.' data-ticket-type="'.htmlspecialchars($ticket_type, ENT_QUOTES, 'UTF-8').'"'
			.' data-ticket-status="'.htmlspecialchars($ticket_status, ENT_QUOTES, 'UTF-8').'"'
			.' data-status-label="'.htmlspecialchars($status_label, ENT_QUOTES, 'UTF-8').'"'
			.' data-section-label="'.htmlspecialchars($section_label, ENT_QUOTES, 'UTF-8').'"'
			.' title="Click to view tickets">'.(int)$count.'</span>';
	}
	return '<span class="'.$base_class.'" style="'.$text_style.'">'.(int)$count.'</span>';
}

function renderAnalyticsKpiTile($count, $color, $ticket_scope, $ticket_type, $ticket_status, $status_label, $section_label, $percent)
{
	$colorEsc = htmlspecialchars((string)$color, ENT_QUOTES, 'UTF-8');
	$soft = htmlspecialchars(kpiSoftBackground($color), ENT_QUOTES, 'UTF-8');
	$labelEsc = htmlspecialchars((string)$status_label, ENT_QUOTES, 'UTF-8');
	$pct = htmlspecialchars((string)$percent, ENT_QUOTES, 'UTF-8');
	$countHtml = renderModernAnalyticsStatusCount($count, $color, $ticket_scope, $ticket_type, $ticket_status, $status_label, $section_label);
	return '<div class="col-xl-3 col-lg-4 col-sm-6 ad-kpi-col">'
		.'<div class="status-metric-card" style="--kpi:'.$colorEsc.';--kpi-soft:'.$soft.'">'
		.'<div class="status-icon-box"></div>'
		.'<div class="status-content">'
		.'<div class="status-title">'.$labelEsc.'</div>'
		.'<div class="status-value">'.$countHtml.'</div>'
		.'<div class="status-progress-wrap"><div class="status-progress-bar"><div class="status-progress-fill" style="width:'.(float)$percent.'%"></div></div><span class="status-percent">'.$pct.'%</span></div>'
		.'</div></div></div>';
}
?>
<style>
.ad-status-panel{border:none;border-radius:16px;box-shadow:0 8px 24px rgba(15,23,42,.05);overflow:hidden;background:#fff}
.ad-status-panel .panel-hdr{background:#fff;border-bottom:1px solid #F1F5F9;padding:14px 18px}
.ad-status-panel .panel-content{background:#fff!important;padding:16px 16px 6px!important}
.ad-kpi-col{margin-bottom:14px}
.status-metric-card{display:flex;align-items:center;gap:14px;background:#fff;border:1px solid #E8EEF4;border-radius:16px;padding:16px 16px 14px;height:100%;min-height:108px;box-shadow:0 6px 18px rgba(15,23,42,.05)}
.status-icon-box{width:48px;height:48px;border-radius:14px;background:var(--kpi-soft,#E8F4FD)!important;flex-shrink:0;position:relative}
.status-icon-box::after{content:"";position:absolute;left:50%;top:50%;width:12px;height:12px;margin:-6px 0 0 -6px;border-radius:4px;background:var(--kpi,#0369A1)}
.status-content{flex:1;min-width:0}
.status-title{font-size:11px;font-weight:700;letter-spacing:.4px;text-transform:uppercase;color:#64748B;margin-bottom:4px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.status-value{font-size:24px;font-weight:800;color:#0F172A;line-height:1.1}
.status-value span{font-size:24px!important;font-weight:800!important;color:#0F172A!important}
.status-progress-wrap{display:flex;align-items:center;gap:10px;margin-top:10px}
.status-progress-bar{flex:1;height:5px;background:#EEF2F7;border-radius:99px;overflow:hidden}
.status-progress-fill{height:100%;border-radius:99px;background:var(--kpi,#0369A1)!important}
.status-percent{font-size:11px;font-weight:700;color:var(--kpi,#0369A1);min-width:42px;text-align:right}
.status-metric-card:hover{border-color:#D7DEE8;box-shadow:0 10px 24px rgba(15,23,42,.1)}
</style>
    <?php
    if($AMC)
    {
        $ppm_ticket_obj = new Ppmtickets($conn);   
        $ppm_status_array = $ppm_ticket_obj->getPPMTicketStatusArray('All');
        $status_array = $ppm_ticket_obj->getPPMTicketStatusArrayGorupedByTicketStatus($CorporateID,$filter);
        // var_dump($status_array);
        ?>
        <!--div id="panel-1" class="panel" style="margin-bottom:1% ;">
            <div class="panel-hdr">
                <h2>
                    PPM Status
                </h2>
            </div>
            <div class="panel-container show">
                <div class="panel-content bg-subtlelight-fade">
                    <button type="button" class="btn btn-xs waves-effect waves-themed mb-2 ml-2 text-white analytics-button" style="background:red;cursor:unset;">Overdue
                            <span class="badge bg-primary-500 ml-2 font-size-1-2em"><?= $status_array['Overdue']; ?></span>
                        </button-->
                    <?php

                    /*foreach($ppm_status_array as $corporate_status)
                    {
                        $background_color = getProfessionalColorForStatus($corporate_status['Status']);
                        $Status = $corporate_status['Status'];
                        $count = 0;
                        if(isset($status_array[$Status]))
                        {
                            $count = $status_array[$Status];
                        }
                        if($Status == "Raised")
                        {
                            $Status = "Open / Raised";
                        }
                        if($Status == "Hold by Techxpert" && $CorporateID == 183)
                        {
                            $Status = "Hold by Innov";
                        }*/
                        
                       ?>
                       <!--button type="button" class="btn btn-xs waves-effect waves-themed mb-2 ml-2 text-white analytics-button" style="background:<?=$background_color;?>;cursor:unset;"><?php echo $Status; ?>
                            <span class="badge bg-primary-500 ml-2"><?= $count; ?></span>
                        </button-->
                       <!-- <div class="col-sm-6 col-xl-3">
                            <div class="p-3 rounded overflow-hidden position-relative text-white mb-g" style="background:<?=$background_color;?>">
                                <div class="">
                                    <h3 class="display-5 d-block l-h-n m-0 fw-500">
                                        <?= $count; ?>
                                        <small class="m-0 l-h-n"><?php echo $Status; ?></small>
                                    </h3>
                                </div>
                                
                            </div>
                        </div> -->
                       <?php 
                    //}
                    ?>
                <!--/div>
            </div>
        </div-->

         <div class="panel ad-status-panel" style="margin-bottom:16px;">
            <div class="panel-hdr">
                <h2>PPM Status</h2>
            </div>
            <div class="panel-container show">
                <div class="panel-content">
                    <div class="row">
                    <?php
                    $percent = $core->calculatePercentage($status_array['Overdue'],$status_array['Total']);
                    echo renderAnalyticsKpiTile($status_array['Overdue'], getProfessionalColorForStatus('Overdue'), 'ppm', 'PPM', 'Overdue', 'Overdue', 'PPM', $percent);
                    foreach($ppm_status_array as $corporate_status)
                    {
                        $background_color = getProfessionalColorForStatus($corporate_status['Status']);
                        $dbStatus = $corporate_status['Status'];
                        $Status = $dbStatus;
                        $count = isset($status_array[$dbStatus]) ? $status_array[$dbStatus] : 0;
                        if($Status == "Raised") { $Status = "Open / Raised"; }
                        if($dbStatus == "Hold by Techxpert" && $CorporateID == 183) { $Status = "Hold by Innov"; }
                        $percent = $core->calculatePercentage($count,$status_array['Total']);
                        echo renderAnalyticsKpiTile($count, $background_color, 'ppm', 'PPM', $dbStatus, $Status, 'PPM', $percent);
                    }
                    ?>
                    </div>
                </div>
            </div>
        </div>

        




        <?php
    }
    $filter['Type'] = "R&M";
    $status_array = $corporate_tickets_obj->getTicketStatusArrayGorupedByTicketStatus($CorporateID,$filter);
?>
    <div class="panel ad-status-panel" style="margin-bottom:16px;">
        <div class="panel-hdr">
            <h2>R&amp;M Status</h2>
        </div>
        <div class="panel-container show">
            <div class="panel-content">
                <div class="row">
                <?php
                foreach($corporate_status_array as $corporate_status)
                {
                    $background_color = getProfessionalColorForStatus($corporate_status['Status']);
                    $dbStatus = $corporate_status['Status'];
                    $Status = $dbStatus;
                    $count = isset($status_array[$dbStatus]) ? $status_array[$dbStatus] : 0;
                    if($Status == "Raised") { $Status = "Open / Raised"; }
                    if($dbStatus == "Hold by Techxpert" && $CorporateID == 183) { $Status = "Hold by Innov"; }
                    $percent = $core->calculatePercentage($count,$status_array['Total']);
                    echo renderAnalyticsKpiTile($count, $background_color, 'corporate', 'R&M', $dbStatus, $Status, 'R&M', $percent);
                }
                ?>
                </div>
            </div>
        </div>
    </div>
<?php
$filter['Type'] = "Projects";
$status_array = $corporate_tickets_obj->getTicketStatusArrayGorupedByTicketStatus($CorporateID,$filter);
if($status_array['Total'] > 0)
{
    ?>
    <div class="panel ad-status-panel" style="margin-bottom:16px;">
        <div class="panel-hdr">
            <h2>Projects Status</h2>
        </div>
        <div class="panel-container show">
            <div class="panel-content">
                <div class="row">
                <?php
                foreach($corporate_status_array as $corporate_status)
                {
                    $background_color = getProfessionalColorForStatus($corporate_status['Status']);
                    $dbStatus = $corporate_status['Status'];
                    $Status = $dbStatus;
                    $count = isset($status_array[$dbStatus]) ? $status_array[$dbStatus] : 0;
                    if($Status == "Raised") { $Status = "Open / Raised"; }
                    if($dbStatus == "Hold by Techxpert" && $CorporateID == 183) { $Status = "Hold by Innov"; }
                    $percent = $core->calculatePercentage($count,$status_array['Total']);
                    echo renderAnalyticsKpiTile($count, $background_color, 'corporate', 'Projects', $dbStatus, $Status, 'Projects', $percent);
                }
                ?>
                </div>
            </div>
        </div>
    </div>
    <?php
}

$filter['Type'] = "Supply";
$status_array = $corporate_tickets_obj->getTicketStatusArrayGorupedByTicketStatus($CorporateID,$filter);
if($status_array['Total'] > 0)
{
    ?>
    <div class="panel ad-status-panel" style="margin-bottom:16px;">
        <div class="panel-hdr">
            <h2>Supply Status</h2>
        </div>
        <div class="panel-container show">
            <div class="panel-content">
                <div class="row">
                <?php
                foreach($corporate_status_array as $corporate_status)
                {
                    $background_color = getProfessionalColorForStatus($corporate_status['Status']);
                    $dbStatus = $corporate_status['Status'];
                    $Status = $dbStatus;
                    $count = isset($status_array[$dbStatus]) ? $status_array[$dbStatus] : 0;
                    if($Status == "Raised") { $Status = "Open / Raised"; }
                    if($dbStatus == "Hold by Techxpert" && $CorporateID == 183) { $Status = "Hold by Innov"; }
                    $percent = $core->calculatePercentage($count,$status_array['Total']);
                    echo renderAnalyticsKpiTile($count, $background_color, 'corporate', 'Supply', $dbStatus, $Status, 'Supply', $percent);
                }
                ?>
                </div>
            </div>
        </div>
    </div>
    <?php
}
$filter['Type'] = "AMC";
$status_array = $corporate_tickets_obj->getTicketStatusArrayGorupedByTicketStatus($CorporateID,$filter);
if($status_array['Total'] > 0)
{
    ?>
    <div class="panel ad-status-panel" style="margin-bottom:16px;">
        <div class="panel-hdr">
            <h2>AMC Breakdown Status</h2>
        </div>
        <div class="panel-container show">
            <div class="panel-content">
                <div class="row">
                <?php
                foreach($corporate_status_array as $corporate_status)
                {
                    $background_color = getProfessionalColorForStatus($corporate_status['Status']);
                    $dbStatus = $corporate_status['Status'];
                    $Status = $dbStatus;
                    $count = isset($status_array[$dbStatus]) ? $status_array[$dbStatus] : 0;
                    if($Status == "Raised") { $Status = "Open / Raised"; }
                    if($dbStatus == "Hold by Techxpert" && $CorporateID == 183) { $Status = "Hold by Innov"; }
                    $percent = $core->calculatePercentage($count,$status_array['Total']);
                    echo renderAnalyticsKpiTile($count, $background_color, 'corporate', 'AMC', $dbStatus, $Status, 'AMC', $percent);
                }
                ?>
                </div>
            </div>
        </div>
    </div>
    <?php
}
?>