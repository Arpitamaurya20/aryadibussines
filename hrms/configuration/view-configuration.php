<?php
 @session_start();
 require_once('../include/autoloader.inc.php');
 $conf = new Conf();
 $_ProductName = $conf->_ProductName;
$_ProductLogo = $conf->_ProductLogo;
  ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta name="description" content="">
    <meta name="author" content="">
    <meta name="keywords" content="">
    <!-- TITLE -->
    <title>View Configuration - DWD</title>
    <?php
        include("../include/common-head.php");
        $dbh = new Dbh();
        $conn = $dbh->_connectodb();
        $IMSSetting = new IMSSetting($conn);
        $core = new Core();
        $all_lead_status = $IMSSetting->GetAllLeadStatus();
        $All_Lead_Sources = $IMSSetting->GetAllLeadSources();
        $all_telecaller_lead_status = $IMSSetting->GetAllTelecallerLeadStatus();
        $authentication = new Authentication($conn);
        $UserType = $authentication->SessionCheck();
        $All_Offers = $IMSSetting->GetAllOffers();
    ?>
    <style type="text/css">
    .settings-display {
        display: flex;
        justify-content: center;
        align-items: center;
    }

    .settings-display .card {
        width: 50%;
    }
    </style>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/mdbassit/Coloris@latest/dist/coloris.min.css" />
    <script src="https://cdn.jsdelivr.net/gh/mdbassit/Coloris@latest/dist/coloris.min.js"></script>

</head>
<script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-6496901533964255"
     crossorigin="anonymous"></script>



<body class="app sidebar-mini ltr light-mode">
    <div class="page">
        <div class="page-main">
            <?php
            include("../navigation/top-header.php");
            ?>

            <?php
            include("../navigation/side-navigation.php");
            ?>

            <!--app-content open-->
            <div class="main-content app-content mt-0">
                <div class="side-app">
                    <!-- CONTAINER -->
                    <div class="main-container container-fluid">
                        <!-- PAGE-HEADER -->
                        <div class="page-header row align-items-center justify-content-between">
                            <!-- <h1 class="page-title">DigitalWorkDesk Dashboard</h1> -->
                            <div class="col-md-6">
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item"><a href="javascript:void(0)">Home</a></li>
                                    <li class="breadcrumb-item active" aria-current="page">Configuration</li>
                                </ol>
                            </div>
                        </div>
                        <div class="row">
                            <!-- Lead Status -->
                            <div class="col-md-6">
                                <div class="card ">
                                    <div class="card-header d-flex justify-content-between align-items-center">
                                        <h3 class="card-title">Lead Status</h3>
                                        <a class="btn btn-info" onclick="AddLeadStatus()">Add</a>
                                    </div>
                                    <div class="card-body p-0">
                                        <div class="table-responsive">
                                            <table class="table table-bordered text-nowrap border-bottom"
                                                id="all_lead_status">
                                                <tbody>
                                                    <?php

                                                        foreach($all_lead_status as $Status)
                                                        {
                                                            $final_status_div = "";
                                                            if($Status['FinalStatus'] == 1)
                                                            {
                                                                $final_status_div = "<span class='text-muted'>&nbsp;&nbsp;(Final Status)</span>";
                                                            }
                                                            if($Status['DefaultStatus'] == 1)
                                                            {
                                                                $final_status_div = "<span class='text-muted'>&nbsp;&nbsp;(Default Status)</span>";
                                                            }
                                                            if($Status['NotInterestedStatus'] == 1)
                                                            {
                                                                $final_status_div = "<span class='text-muted'>&nbsp;&nbsp;(Not Interested Status)</span>";
                                                            }
                                                        ?>
                                                    <tr>
                                                        <td class="wd-15p border-bottom-0">
                                                            <?= $Status['Status'].$final_status_div; ?></td>
                                                        <td class="wd-15p border-bottom-0"><span class="badge" style='background-color:<?= $Status['LeadColor'] ?>'>&nbsp;&nbsp;</span>
                                                            </td>
                                                        <td class="wd-15p border-bottom-0">
                                                            <a class="btn text-danger btn-sm"
                                                                onclick="EditLeadStatus(<?=$Status['ID'];?>,'<?=$Status['Status'];?>',<?php echo $Status['FinalStatus']; ?>,<?php echo $Status['DefaultStatus']; ?>,<?php echo $Status['NotInterestedStatus']; ?>,'<?php echo $Status['LeadColor']; ?>')">
                                                                <span class="fa fa-pencil-square-o fs-14"></span>
                                                            </a>
                                                        </td>
                                                    </tr>
                                                    <?php
                                                    }
                                                    ?>

                                                </tbody>

                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Lead Source -->
                            <div class="col-md-6">
                                <div class="card ">
                                    <div class="card-header d-flex justify-content-between align-items-center">
                                        <h3 class="card-title">Lead Source</h3>
                                        <a class="btn btn-info" onclick="AddLeadSource()">Add</a>
                                    </div>
                                    <div class="card-body p-0">
                                        <div class="table-responsive">
                                            <table class="table table-bordered text-nowrap border-bottom"
                                                id="all_lead_source">
                                                <tbody>
                                                    <?php
                                                        foreach($All_Lead_Sources as $Lead_Source_value)
                                                        {
                                                        ?>
                                                    <tr>
                                                        <td class="wd-15p border-bottom-0">
                                                            <?= $Lead_Source_value['SourceName']; ?></td>
                                                        <td class="wd-15p border-bottom-0">
                                                            <a class="btn text-danger btn-sm"
                                                                onclick="EditLeadSource(<?=$Lead_Source_value['ID'];?>,'<?=$Lead_Source_value['SourceName'];?>')">
                                                                <span class="fa fa-pencil-square-o fs-14"></span>
                                                            </a>
                                                        </td>
                                                    </tr>
                                                    <?php
                                                        }
                                                        ?>

                                                </tbody>

                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Telecaller Lead Status -->
                            <div class="col-md-6">
                                <div class="card ">
                                    <div class="card-header d-flex justify-content-between align-items-center">
                                        <h3 class="card-title">Telecaller Lead Status</h3>
                                        <a class="btn btn-info" onclick="AddTelecallerLeadStatus()">Add</a>
                                    </div>
                                    <div class="card-body p-0">
                                        <div class="table-responsive">
                                            <table class="table table-bordered text-nowrap border-bottom"
                                                id="all_telecaller_lead_status">
                                                <tbody>
                                                    <?php

                                                        foreach($all_telecaller_lead_status as $Status)
                                                        {
                                                            $final_status_div = "";
                                                            if($Status['FinalStatus'] == 1)
                                                            {
                                                                $final_status_div = "<span class='text-muted'>&nbsp;&nbsp;(Final Status)</span>";
                                                            }
                                                            if($Status['DefaultStatus'] == 1)
                                                            {
                                                                $final_status_div = "<span class='text-muted'>&nbsp;&nbsp;(Default Status)</span>";
                                                            }
                                                            if($Status['NotInterestedStatus'] == 1)
                                                            {
                                                                $final_status_div = "<span class='text-muted'>&nbsp;&nbsp;(Not Interested Status)</span>";
                                                            }
                                                        ?>
                                                    <tr>
                                                        <td class="wd-15p border-bottom-0">
                                                            <?= $Status['Status'].$final_status_div; ?></td>
                                                        <td class="wd-15p border-bottom-0"><span class="badge" style='background-color:<?= $Status['LeadColor'] ?>'>&nbsp;&nbsp;</span>
                                                            </td>
                                                        <td class="wd-15p border-bottom-0">
                                                            <a class="btn text-danger btn-sm"
                                                                onclick="EditTelecallerLeadStatus(<?=$Status['ID'];?>,'<?=$Status['Status'];?>',<?php echo $Status['FinalStatus']; ?>,<?php echo $Status['DefaultStatus']; ?>,<?php echo $Status['NotInterestedStatus']; ?>,'<?php echo $Status['LeadColor']; ?>')">
                                                                <span class="fa fa-pencil-square-o fs-14"></span>
                                                            </a>
                                                        </td>
                                                    </tr>
                                                    <?php
                                                    }
                                                    ?>

                                                </tbody>

                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                             <!-- ----------------offers------------------- -->

                             <!-- Lead Source -->
                            <div class="col-md-6">
                                <div class="card ">
                                    <div class="card-header d-flex justify-content-between align-items-center">
                                        <h3 class="card-title">L2a Offers</h3>
                                        <a class="btn btn-info" onclick="AddOffers()">Add</a>
                                    </div>
                                    <div class="card-body p-0">
                                        <div class="table-responsive">
                                            <table class="table table-bordered text-nowrap border-bottom"
                                                id="all_lead_source">
                                                <tbody>
                                                    <?php

                                                        foreach($All_Offers as $offer)
                                                        {
                                                        ?>
                                                    <tr>
                                                        <td class="wd-15p border-bottom-0">
                                                            <?= $offer['OfferName']; ?></td>
                                                            <td class="wd-15p border-bottom-0">
                                                            <?= $offer['Discountvalue']; ?></td>
                                                        <td class="wd-15p border-bottom-0">
                                                            <a class="btn text-danger btn-sm"
                                                                onclick="EditOffer(<?=$offer['ID'];?>,'<?=$offer['OfferName'];?>','<?=$offer['Discountvalue'];?>')">
                                                                <span class="fa fa-pencil-square-o fs-14"></span>
                                                            </a>
                                                        </td>
                                                    </tr>
                                                    <?php
                                                        }
                                                        ?>

                                                </tbody>

                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>



                    </div>
                    <!-- CONTAINER END -->
                </div>
            </div>
            <!--app-content close-->


            <?php
            include("../navigation/right-side-navigation.php");
            ?>
        </div>
        <?php
        include("../include/common-script.php");
        ?>
        <script>
        $("#nav_configuration").addClass("active");
        </script>
        <script src="../project-assets/js/configuration.js"></script>
</body>

</html>


<div class="modal fade" tabindex="-1" id="modal_lead_status">
    <div class="modal-dialog modal-sm" role="document">
        <div class="modal-content modal-content-demo">
            <div class="modal-header">
                <h3 class="modal-title">Lead Status</h3>
                <button class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span>
                </button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="lead_status_form_action" id="lead_status_form_action" value="add" />
                <input type="hidden" name="lead_status_form_id" id="lead_status_form_id" value="-1" />
                <div class="row">
                    <div class="form-group col-md-12">
                        <label for="recipient-name" class="col-form-label">Status </label>

                        <input type="text" class="form-control" name="LeadStatus" id="LeadStatus" value="">
                    </div>
                    <div class="form-group col-md-12">
                        <label for="recipient-name" class="col-form-label">Color : &nbsp; &nbsp;</label>
                        <input type="text" class="form-control" data-coloris name="LeadColor" id="LeadColor" value="">
                    </div>
                    <div class="form-group col-md-12">
                        <label class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" name="final_status" id="final_status"
                                value="1">
                            <span class="custom-control-label">Final Status</span>
                        </label>
                    </div>
                    <div class="form-group col-md-12">
                        <label class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" name="default_status" id="default_status"
                                value="1">
                            <span class="custom-control-label">Default Status</span>
                        </label>
                    </div>
                    <div class="form-group col-md-12">
                        <label class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" name="not_interested_status" id="not_interested_status"
                                value="1">
                            <span class="custom-control-label">Not Interested Status</span>
                        </label>
                    </div>
                </div>

                <div class="mt-5 text-center">
                    <button class="btn btn-success text-white" id="" onclick="UpdateLeadStatus()">Save</button>
                </div>


                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" tabindex="-1" id="modal_lead_source">
    <div class="modal-dialog modal-sm" role="document">
        <div class="modal-content modal-content-demo">
            <div class="modal-header">
                <h3 class="modal-title">Lead Source</h3>
                <button class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span>
                </button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="lead_source_form_action" id="lead_source_form_action" value="add" />
                <input type="hidden" name="lead_source_form_id" id="lead_source_form_id" value="-1" />
                <div class="row">
                    <div class="form-group col-md-12">
                        <label for="recipient-name" class="col-form-label">Source Name </label>
                        <input type="text" class="form-control" name="SourceName" id="SourceName" value="">
                    </div>
                </div>

                <div class="mt-5 text-center">
                    <button class="btn btn-success text-white" id="" onclick="UpdateLeadSource()">Save</button>
                </div>


                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" tabindex="-1" id="modal_telecaller_lead_status">
    <div class="modal-dialog modal-sm" role="document">
        <div class="modal-content modal-content-demo">
            <div class="modal-header">
                <h3 class="modal-title">Telecaller Lead Status</h3>
                <button class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span>
                </button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="telecaller_lead_status_form_action" id="telecaller_lead_status_form_action" value="add" />
                <input type="hidden" name="telecaller_lead_status_form_id" id="telecaller_lead_status_form_id" value="-1" />
                <div class="row">
                    <div class="form-group col-md-12">
                        <label for="recipient-name" class="col-form-label">Status </label>

                        <input type="text" class="form-control" name="TelecallerLeadStatus" id="TelecallerLeadStatus" value="">
                    </div>
                    <div class="form-group col-md-12">
                        <label for="recipient-name" class="col-form-label">Color : &nbsp; &nbsp;</label>
                        <input type="text" class="form-control" data-coloris name="TelecallerLeadColor" id="TelecallerLeadColor" value="">
                    </div>
                    <div class="form-group col-md-12">
                        <label class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" name="telecaller_final_status" id="telecaller_final_status"
                                value="1">
                            <span class="custom-control-label">Final Status</span>
                        </label>
                    </div>
                    <div class="form-group col-md-12">
                        <label class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" name="telecaller_default_status" id="telecaller_default_status"
                                value="1">
                            <span class="custom-control-label">Default Status</span>
                        </label>
                    </div>
                    <div class="form-group col-md-12">
                        <label class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" name="telecaller_not_interested_status" id="telecaller_not_interested_status"
                                value="1">
                            <span class="custom-control-label">Not Interested Status</span>
                        </label>
                    </div>
                </div>

                <div class="mt-5 text-center">
                    <button class="btn btn-success text-white" id="" onclick="UpdateTelecallerLeadStatus()">Save</button>
                </div>
            </div>
        </div>
    </div>
</div>


<!-- -------------------offer model------------- -->

<div class="modal fade" tabindex="-1" id="modal_offer">
    <div class="modal-dialog modal-sm" role="document">
        <div class="modal-content modal-content-demo">
            <div class="modal-header">
                <h3 class="modal-title">L2a Offer</h3>
                <button class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="offer_form" onsubmit="return false;" method="POST">
                <input type="hidden" name="offer_form_action" id="offer_form_action" value="add" />
                <input type="hidden" name="offer_form_id" id="offer_form_id" value="-1" />
                <div class="row">
                    <div class="form-group col-md-12">
                        <label for="recipient-name" class="col-form-label">Offer Name </label>
                        <input type="text" class="form-control" name="offerName" id="offerName" value="">
                    </div>
                    <div class="form-group col-md-12">
                        <label for="recipient-name" class="col-form-label">Offer value(%)</label>
                        <input type="text" class="form-control" name="offerValue" id="offerValue" value="">
                    </div>
                </div>

                <div class="mt-5 text-center">
                    <button class="btn btn-success text-white" id="" onclick="UpdateOffer()">Save</button>
                </div>


                </form>
            </div>
        </div>
    </div>
</div>