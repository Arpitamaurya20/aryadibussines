<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
@session_start();
include('../../includes/autoloader.inc.php');
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
                        $background_color = $corporate_status['Color'];
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
                        <div class="col-3">
                            <div class="px-3 py-0 d-flex align-items-center">
                                <?php 
                                $percent = $core->calculatePercentage($status_array['Overdue'],$status_array['Total']);
                                ?>
                                <div class="js-easy-pie-chart color-primary-500 d-inline-flex" data-percent="<?php echo $percent;?>" data-piesize="50" data-linewidth="5" data-scalelength="2"><canvas height="75" width="75" style="height: 50px; width: 50px;"></canvas></div>
                                <span class="d-inline-block ml-2 text-muted">OVERDUE</span>
                                <div class="ml-auto d-inline-flex align-items-center">
                                    <div class="d-inline-flex flex-column small ml-2">
                                        <span class="d-inline-block badge badge-success text-center p-1 width-6 font-size-1-2em" style="background:red;"><?= $status_array['Overdue']; ?></span>
                                        <span class="d-inline-block badge bg-fusion-300 text-center p-1 width-6 mt-1 font-size-1-2em" style="background:red;">
                                            <?php 
                                             
                                            echo $percent."%";
                                            ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    
                    <?php

                    foreach($ppm_status_array as $corporate_status)
                    {
                        $background_color = $corporate_status['Color'];
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
                        }
                        $percent = $core->calculatePercentage($count,$status_array['Total']); 
                       ?>
                      
                        <div class="col-3">
                            <div class="px-3 py-0 d-flex align-items-center">
                                <div class="js-easy-pie-chart color-primary-500 d-inline-flex" data-percent="<?php echo $percent; ?>" data-piesize="50" data-linewidth="5" data-scalelength="2"><canvas height="75" width="75" style="height: 50px; width: 50px;"></canvas></div>
                                <span class="d-inline-block ml-2 text-muted"><?php echo $Status; ?></span>
                                <div class="ml-auto d-inline-flex align-items-center">
                                    <div class="d-inline-flex flex-column small ml-2">
                                        <span class="d-inline-block badge badge-success text-center p-1 width-6 font-size-1-2em"  style="background:<?=$background_color;?>;"><?= $count; ?></span>
                                        <span class="d-inline-block badge bg-fusion-300 text-center p-1 width-6 mt-1 font-size-1-2em" style="background:<?=$background_color;?>;">
                                            <?php 
                                            
                                            echo $percent."%";
                                            ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
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
                    $background_color = $corporate_status['Color'];
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
                    }
                    $percent = $core->calculatePercentage($count,$status_array['Total']);
                   ?>
                     <div class="col-3">
                            <div class="px-3 py-0 d-flex align-items-center">
                                <div class="js-easy-pie-chart color-primary-500 d-inline-flex" data-percent="<?php echo $percent; ?>" data-piesize="50" data-linewidth="5" data-scalelength="2"><canvas height="75" width="75" style="height: 50px; width: 50px;"></canvas></div>
                                <span class="d-inline-block ml-2 text-muted"><?php echo $Status; ?></span>
                                <div class="ml-auto d-inline-flex align-items-center">
                                    <div class="d-inline-flex flex-column small ml-2">
                                        <span class="d-inline-block badge badge-success text-center p-1 width-6 font-size-1-2em"  style="background:<?=$background_color;?>;"><?= $count; ?></span>
                                        <span class="d-inline-block badge bg-fusion-300 text-center p-1 width-6 mt-1 font-size-1-2em" style="background:<?=$background_color;?>;">
                                            <?php 
                                            echo $percent."%";
                                            ?>
                                        </span>
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
                    $background_color = $corporate_status['Color'];
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
                    }
                    $percent = $core->calculatePercentage($count,$status_array['Total']);
                   ?>
                        <div class="col-3">
                            <div class="px-3 py-0 d-flex align-items-center">
                                <div class="js-easy-pie-chart color-primary-500 d-inline-flex" data-percent="<?php echo $percent; ?>" data-piesize="50" data-linewidth="5" data-scalelength="2"><canvas height="75" width="75" style="height: 50px; width: 50px;"></canvas></div>
                                <span class="d-inline-block ml-2 text-muted"><?php echo $Status; ?></span>
                                <div class="ml-auto d-inline-flex align-items-center">
                                    <div class="d-inline-flex flex-column small ml-2">
                                        <span class="d-inline-block badge badge-success text-center p-1 width-6 font-size-1-2em"  style="background:<?=$background_color;?>;"><?= $count; ?></span>
                                        <span class="d-inline-block badge bg-fusion-300 text-center p-1 width-6 mt-1 font-size-1-2em" style="background:<?=$background_color;?>;">
                                            <?php 
                                            echo $percent."%";
                                            ?>
                                        </span>
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
                    $background_color = $corporate_status['Color'];
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
                    }
                    $percent = $core->calculatePercentage($count,$status_array['Total']);
                   ?>
                     <div class="col-3">
                            <div class="px-3 py-0 d-flex align-items-center">
                                <div class="js-easy-pie-chart color-primary-500 d-inline-flex" data-percent="<?php echo $percent; ?>" data-piesize="50" data-linewidth="5" data-scalelength="2"><canvas height="75" width="75" style="height: 50px; width: 50px;"></canvas></div>
                                <span class="d-inline-block ml-2 text-muted"><?php echo $Status; ?></span>
                                <div class="ml-auto d-inline-flex align-items-center">
                                    <div class="d-inline-flex flex-column small ml-2">
                                        <span class="d-inline-block badge badge-success text-center p-1 width-6 font-size-1-2em"  style="background:<?=$background_color;?>;"><?= $count; ?></span>
                                        <span class="d-inline-block badge bg-fusion-300 text-center p-1 width-6 mt-1 font-size-1-2em" style="background:<?=$background_color;?>;">
                                            <?php 
                                            echo $percent."%";
                                            ?>
                                        </span>
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
                    $background_color = $corporate_status['Color'];
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
                    }
                    $percent = $core->calculatePercentage($count,$status_array['Total']);
                   ?>
                    <div class="col-3">
                            <div class="px-3 py-0 d-flex align-items-center">
                                <div class="js-easy-pie-chart color-primary-500 d-inline-flex" data-percent="<?php echo $percent; ?>" data-piesize="50" data-linewidth="5" data-scalelength="2"><canvas height="75" width="75" style="height: 50px; width: 50px;"></canvas></div>
                                <span class="d-inline-block ml-2 text-muted"><?php echo $Status; ?></span>
                                <div class="ml-auto d-inline-flex align-items-center">
                                    <div class="d-inline-flex flex-column small ml-2">
                                        <span class="d-inline-block badge badge-success text-center p-1 width-6 font-size-1-2em"  style="background:<?=$background_color;?>;"><?= $count; ?></span>
                                        <span class="d-inline-block badge bg-fusion-300 text-center p-1 width-6 mt-1 font-size-1-2em" style="background:<?=$background_color;?>;">
                                            <?php 
                                            echo $percent."%";
                                            ?>
                                        </span>
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