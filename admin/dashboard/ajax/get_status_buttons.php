<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
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
    if (strpos($statusLower, 'overdue') !== false) return '#EF4444'; // Red
    if (strpos($statusLower, 'closed') !== false || strpos($statusLower, 'completed') !== false) return '#10B981'; // Green
    if (strpos($statusLower, 'work in progress') !== false) return '#F59E0B'; // Amber
    if (strpos($statusLower, 'hold') !== false || strpos($statusLower, 'pending') !== false) return '#F59E0B'; // Amber
    if (strpos($statusLower, 'cancel') !== false) return '#64748B'; // Slate
    // Default Professional Blue
    return '#003f88'; 
}

function renderModernAnalyticsStatusCount($count, $color, $ticket_scope, $ticket_type, $ticket_status, $status_label, $section_label)
{
	$text_style = 'color: #1E293B; font-size: 1.6rem; font-weight: 800; line-height: 1.1; margin-bottom: 0px;';
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
?>
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

         <div id="panel-1" class="panel" style="margin-bottom:1% ;">
            <div class="panel-hdr">
                <h2>
                    PPM Status
                </h2>
            </div>
            <div class="panel-container show">
                <div class="panel-content bg-subtlelight-fade">
                    <div class="row">
                        <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
                            <div class="status-metric-card">
                                <?php 
                                $percent = $core->calculatePercentage($status_array['Overdue'],$status_array['Total']);
                                ?>
                                <div class="status-icon-box" style="background: rgba(239, 68, 68, 0.1); color: #EF4444;">
                                    <i data-lucide="alert-triangle"></i>
                                </div>
                                <div class="status-content">
                                    <div class="status-title">OVERDUE</div>
                                    <div class="status-value d-flex align-items-center">
                                        <?= renderModernAnalyticsStatusCount($status_array['Overdue'], 'red', 'ppm', 'PPM', 'Overdue', 'OVERDUE', 'PPM'); ?>
                                    </div>
                                    <div class="status-progress-wrap">
                                        <div class="status-progress-bar">
                                            <div class="status-progress-fill" style="width: <?php echo $percent; ?>%; background: #EF4444;"></div>
                                        </div>
                                        <span class="status-percent" style="color: #EF4444;"><?php echo $percent; ?>%</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    
                    <?php

                    foreach($ppm_status_array as $corporate_status)
                    {
                        $background_color = getProfessionalColorForStatus($corporate_status['Status']);
                        $dbStatus = $corporate_status['Status'];
                        $Status = $dbStatus;
                        $count = 0;
                        if(isset($status_array[$dbStatus]))
                        {
                            $count = $status_array[$dbStatus];
                        }
                        if($Status == "Raised")
                        {
                            $Status = "Open / Raised";
                        }
                        if($dbStatus == "Hold by Techxpert" && $CorporateID == 183)
                        {
                            $Status = "Hold by Innov";
                        }
                        $percent = $core->calculatePercentage($count,$status_array['Total']); 
                       ?>
                      
                        <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
                            <div class="status-metric-card">
                                <div class="status-icon-box" style="background: <?=$background_color;?>20; color: <?=$background_color;?>;">
                                    <i data-lucide="<?php echo getLucideIconForStatus($Status); ?>"></i>
                                </div>
                                <div class="status-content">
                                    <div class="status-title"><?php echo $Status; ?></div>
                                    <div class="status-value d-flex align-items-center">
                                        <?= renderModernAnalyticsStatusCount($count, $background_color, 'ppm', 'PPM', $dbStatus, $Status, 'PPM'); ?>
                                    </div>
                                    <div class="status-progress-wrap">
                                        <div class="status-progress-bar">
                                            <div class="status-progress-fill" style="width: <?php echo $percent; ?>%; background: <?=$background_color;?>;"></div>
                                        </div>
                                        <span class="status-percent" style="color: <?=$background_color;?>;"><?php echo $percent; ?>%</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                       <?php 
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
    <div id="panel-1" class="panel" style="margin-bottom:1% ;">
        <div class="panel-hdr">
            <h2>
                R&M Status
            </h2>
        </div>
        <div class="panel-container show">
            <div class="panel-content bg-subtlelight-fade">
                <div class="row">
                <?php
                foreach($corporate_status_array as $corporate_status)
                {
                    $background_color = getProfessionalColorForStatus($corporate_status['Status']);
                    $dbStatus = $corporate_status['Status'];
                    $Status = $dbStatus;
                    $count = 0;
                    
                    if(isset($status_array[$dbStatus]))
                    {
                        $count = $status_array[$dbStatus];
                    }
                    if($Status == "Raised")
                    {
                        $Status = "Open / Raised";
                    }
                    if($dbStatus == "Hold by Techxpert" && $CorporateID == 183)
                    {
                        $Status = "Hold by Innov";
                    }
                    $percent = $core->calculatePercentage($count,$status_array['Total']);
                   ?>
                        <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
                            <div class="status-metric-card">
                                <div class="status-icon-box" style="background: <?=$background_color;?>20; color: <?=$background_color;?>;">
                                    <i data-lucide="<?php echo getLucideIconForStatus($Status); ?>"></i>
                                </div>
                                <div class="status-content">
                                    <div class="status-title"><?php echo $Status; ?></div>
                                    <div class="status-value d-flex align-items-center">
                                        <?= renderModernAnalyticsStatusCount($count, $background_color, 'corporate', 'R&M', $dbStatus, $Status, 'R&M'); ?>
                                    </div>
                                    <div class="status-progress-wrap">
                                        <div class="status-progress-bar">
                                            <div class="status-progress-fill" style="width: <?php echo $percent; ?>%; background: <?=$background_color;?>;"></div>
                                        </div>
                                        <span class="status-percent" style="color: <?=$background_color;?>;"><?php echo $percent; ?>%</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                   <?php 
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
    <div id="panel-1" class="panel" style="margin-bottom:1% ;">
        <div class="panel-hdr">
            <h2>
                Projects Status
            </h2>
        </div>
        <div class="panel-container show">
            <div class="panel-content bg-subtlelight-fade">
                <div class="row">
                <?php
                foreach($corporate_status_array as $corporate_status)
                {
                    $background_color = getProfessionalColorForStatus($corporate_status['Status']);
                    $dbStatus = $corporate_status['Status'];
                    $Status = $dbStatus;
                    $count = 0;
                    
                    if(isset($status_array[$dbStatus]))
                    {
                        $count = $status_array[$dbStatus];
                    }
                    if($Status == "Raised")
                    {
                        $Status = "Open / Raised";
                    }
                    if($dbStatus == "Hold by Techxpert" && $CorporateID == 183)
                    {
                        $Status = "Hold by Innov";
                    }
                    $percent = $core->calculatePercentage($count,$status_array['Total']);
                   ?>
                        <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
                            <div class="status-metric-card">
                                <div class="status-icon-box" style="background: <?=$background_color;?>20; color: <?=$background_color;?>;">
                                    <i data-lucide="<?php echo getLucideIconForStatus($Status); ?>"></i>
                                </div>
                                <div class="status-content">
                                    <div class="status-title"><?php echo $Status; ?></div>
                                    <div class="status-value d-flex align-items-center">
                                        <?= renderModernAnalyticsStatusCount($count, $background_color, 'corporate', 'Projects', $dbStatus, $Status, 'Projects'); ?>
                                    </div>
                                    <div class="status-progress-wrap">
                                        <div class="status-progress-bar">
                                            <div class="status-progress-fill" style="width: <?php echo $percent; ?>%; background: <?=$background_color;?>;"></div>
                                        </div>
                                        <span class="status-percent" style="color: <?=$background_color;?>;"><?php echo $percent; ?>%</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                   <?php 
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
    <div id="panel-1" class="panel" style="margin-bottom:1% ;">
        <div class="panel-hdr">
            <h2>
                Supply Status
            </h2>
        </div>
        <div class="panel-container show">
            <div class="panel-content bg-subtlelight-fade">
                <div class="row">
                <?php
                foreach($corporate_status_array as $corporate_status)
                {
                    $background_color = getProfessionalColorForStatus($corporate_status['Status']);
                    $dbStatus = $corporate_status['Status'];
                    $Status = $dbStatus;
                    $count = 0;
                    
                    if(isset($status_array[$dbStatus]))
                    {
                        $count = $status_array[$dbStatus];
                    }
                    if($Status == "Raised")
                    {
                        $Status = "Open / Raised";
                    }
                    if($dbStatus == "Hold by Techxpert" && $CorporateID == 183)
                    {
                        $Status = "Hold by Innov";
                    }
                    $percent = $core->calculatePercentage($count,$status_array['Total']);
                   ?>
                        <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
                            <div class="status-metric-card">
                                <div class="status-icon-box" style="background: <?=$background_color;?>20; color: <?=$background_color;?>;">
                                    <i data-lucide="<?php echo getLucideIconForStatus($Status); ?>"></i>
                                </div>
                                <div class="status-content">
                                    <div class="status-title"><?php echo $Status; ?></div>
                                    <div class="status-value d-flex align-items-center">
                                        <?= renderModernAnalyticsStatusCount($count, $background_color, 'corporate', 'Supply', $dbStatus, $Status, 'Supply'); ?>
                                    </div>
                                    <div class="status-progress-wrap">
                                        <div class="status-progress-bar">
                                            <div class="status-progress-fill" style="width: <?php echo $percent; ?>%; background: <?=$background_color;?>;"></div>
                                        </div>
                                        <span class="status-percent" style="color: <?=$background_color;?>;"><?php echo $percent; ?>%</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                   <?php 
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
    <div id="panel-1" class="panel" style="margin-bottom:1% ;">
        <div class="panel-hdr">
            <h2>
                AMC Breakdown Status
            </h2>
        </div>
        <div class="panel-container show">
            <div class="panel-content bg-subtlelight-fade">
                <div class="row">
                <?php
                foreach($corporate_status_array as $corporate_status)
                {
                    $background_color = getProfessionalColorForStatus($corporate_status['Status']);
                    $dbStatus = $corporate_status['Status'];
                    $Status = $dbStatus;
                    $count = 0;
                    
                    if(isset($status_array[$dbStatus]))
                    {
                        $count = $status_array[$dbStatus];
                    }
                    if($Status == "Raised")
                    {
                        $Status = "Open / Raised";
                    }
                    if($dbStatus == "Hold by Techxpert" && $CorporateID == 183)
                    {
                        $Status = "Hold by Innov";
                    }
                    $percent = $core->calculatePercentage($count,$status_array['Total']);
                   ?>
                        <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
                            <div class="status-metric-card">
                                <div class="status-icon-box" style="background: <?=$background_color;?>20; color: <?=$background_color;?>;">
                                    <i data-lucide="<?php echo getLucideIconForStatus($Status); ?>"></i>
                                </div>
                                <div class="status-content">
                                    <div class="status-title"><?php echo $Status; ?></div>
                                    <div class="status-value d-flex align-items-center">
                                        <?= renderAnalyticsStatusCountBadge($count, $background_color, 'corporate', 'AMC', $dbStatus, $Status, 'AMC'); ?>
                                    </div>
                                    <div class="status-progress-wrap">
                                        <div class="status-progress-bar">
                                            <div class="status-progress-fill" style="width: <?php echo $percent; ?>%; background: <?=$background_color;?>;"></div>
                                        </div>
                                        <span class="status-percent" style="color: <?=$background_color;?>;"><?php echo $percent; ?>%</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                   <?php 
                }
                ?>
            </div>
            </div>
        </div>
    </div>
    <?php
}
?>