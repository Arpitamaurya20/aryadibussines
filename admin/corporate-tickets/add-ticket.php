<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <?php
    require_once('../includes/autoloader.inc.php');
    include('../controllers/common_controllers.php');
    include('../company/controller/company_controller.php');
    include('../branch/controller/branch_controller.php');
    include('../Services/controller/service_controller.php');
    $UserType = SessionCheck();
    setNavigation($_SESSION['Roles']);
    $conn = _connectodb();
    ?>
    <meta charset="utf-8">
    <meta name="description" content="Add Corporate Ticket">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    
    <?php include('../includes/common_head_content.php'); ?>
    <link rel="stylesheet" media="screen, print" href="../css/formplugins/bootstrap-datepicker/bootstrap-datepicker.css">
    
    <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-6496901533964255" crossorigin="anonymous"></script>

    <?php
    $AllCompany = getAllCompanies($conn);
    $AllBranch = getAllBranchesWithName($conn);
    $categories_obj = new Categories($conn);
    $categories_array = $categories_obj->getAllCategories();
    $CorporateID = -1;
    $BranchID = -1;
    $TicketManager = false;

    if($UserType == "Corporate Admin") {
        $corporate_user = true;
        $CorporateID = (int)$_SESSION['Roles']['CorporateID'];
    }
    if($UserType == "Corporate Branch User") {
        $corporate_user = true;
        $CorporateID = (int)$_SESSION['Roles']['CorporateID'];
        $BranchID = (int)$_SESSION['Roles']['BranchID'];
    }
    if(isset($_SESSION['Roles']['EmployeeRoles'])) {
        $EmployeeRoles = $_SESSION['Roles']['EmployeeRoles'];
        foreach($EmployeeRoles as $E_Role) {
            if($E_Role == "Ticket Manager" || $UserType == "Admin" || $UserType == "Branch Account Manager" || $E_Role == "Branch Account Manager") {
                $TicketManager = true;
            }
        }
    }
    $ProductName = "Aryadibusiness";
    if ($CorporateID == 183) {
        $_product = "innov";
        $conf = new Config($conn);
        $product_configuration = $conf->GetConfigParametersfromURL($_product);
        $ProductName = $product_configuration['ProductName'];
    } 
    $logoImg = "tech-logo.jpg";
    if(isset($product_configuration['logo'])) {
        $logoImg = $product_configuration['logo'];
    }   
    ?>

    <title>Raise Ticket - <?=$ProductName;?></title>

    <?php 
    if(isset($product_configuration['favicon'])) {
        ?>
        <link rel="icon" type="image/png" sizes="32x32" href="../img/favicon/<?=$product_configuration['favicon'];?>">
        <?php
    }
    if($ProductName != "Aryadibusiness") {
        include("../css/client_generated_css.php");
    }
    ?>

    <style type="text/css">
        .select2-container {
            z-index: 1;
            width: 100% !important;
        }

        /* Modern Dashboard Card Overrides */
        .ticket-card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            background: #ffffff;
            margin-bottom: 1.5rem;
        }

        .ticket-card-header {
            background: #ffffff;
            border-bottom: 1px solid #f0f0f0;
            padding: 1.25rem 1.5rem;
            border-top-left-radius: 12px;
            border-top-right-radius: 12px;
        }

        .ticket-section-title {
            font-size: 0.95rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #4b5563;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
        }

        .ticket-section-title i {
            margin-right: 8px;
            color: #3b82f6;
        }

        .form-section-block {
            background-color: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 1.25rem;
            margin-bottom: 1.25rem;
        }

        .form-label {
            font-weight: 600;
            color: #374151;
            font-size: 0.875rem;
            margin-bottom: 0.35rem;
        }

        .form-control, .custom-file-label {
            border-radius: 6px;
            border: 1px solid #d1d5db;
            padding: 0.5rem 0.75rem;
            transition: all 0.2s ease-in-out;
        }

        .form-control:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
        }

        .btn-raise-ticket {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            border: none;
            border-radius: 6px;
            padding: 0.65rem 2rem;
            font-weight: 600;
            letter-spacing: 0.3px;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.25);
            transition: all 0.2s ease;
        }

        .btn-raise-ticket:hover {
            background: linear-gradient(135deg, #1d4ed8 0%, #1e40af 100%);
            box-shadow: 0 6px 16px rgba(37, 99, 235, 0.35);
            transform: translateY(-1px);
        }

        .required-asterisk {
            color: #ef4444;
            font-weight: bold;
        }
    </style>
</head>

<body class="mod-bg-1 desktop chrome webkit pace-done nav-function-fixed blur">
    <?php include('../js/theme_settings.js'); ?>

    <!-- BEGIN Page Wrapper -->
    <div class="page-wrapper">
        <div class="page-inner">
            <?php include('../navigation/admin_navigation.php'); ?>
            
            <div class="page-content-wrapper">
                <!-- BEGIN Page Header -->
                <?php include('../includes/common_header.php'); ?>
                <!-- END Page Header -->

                <!-- BEGIN Page Content -->
                <main id="js-page-content" role="main" class="page-content">
                    
                    <!-- Breadcrumbs -->
                    <ol class="breadcrumb page-breadcrumb">
                        <li class="breadcrumb-item"><a href="../dashboard/admin_dashboard"><i class="fal fa-home mr-1"></i> <?=$ProductName;?></a></li>
                        <li class="breadcrumb-item active">Raise Ticket</li>
                    </ol>

                    <!-- Main Container Card -->
                    <div class="card ticket-card">
                        <div class="ticket-card-header d-flex justify-content-between align-items-center">
                            <div>
                                <h3 class="font-weight-bold mb-0 text-dark">
                                    <i class="fal fa-ticket-alt text-primary mr-2"></i>Create New Support Ticket
                                </h3>
                                <small class="text-muted">Fill out the details below to submit a new service request.</small>
                            </div>
                        </div>

                        <div class="card-body p-4">
                            <form method="post" id="raise_ticket_form">
                                
                                <!-- SECTION 1: Organization & Site Details -->
                                <div class="form-section-block">
                                    <div class="ticket-section-title">
                                        <i class="fal fa-building"></i> Organization & Location
                                    </div>
                                    <div class="row">
                                        <div class="col-lg-6">
                                            <div class="form-group mb-lg-0">
                                                <label class="form-label" for="corporate_name">
                                                    Corporate <span class="required-asterisk">*</span>
                                                </label>
                                                <select onchange="SelectCorporate()" name="corporate_name" class="select2 form-control" id="corporate_name">
                                                    <option value="">Please Select Corporate</option>
                                                    <?php
                                                    foreach($AllCompany as $CompanyValue) {
                                                        $companyId = (int)$CompanyValue['ID'];
                                                        if(!$TicketManager && $companyId !== $CorporateID) {
                                                            continue;
                                                        }
                                                    ?>
                                                        <option value="<?php echo $companyId; ?>" <?php if(!$TicketManager && $companyId === $CorporateID){ echo "selected"; } ?>>
                                                            <?php echo $CompanyValue['CompanyName']; ?>
                                                        </option>
                                                    <?php } ?>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="col-lg-6" id="branch_div" style="display:none;">
                                            <div class="form-group mb-0">
                                                <label class="form-label" for="branch_name">
                                                    Branch <span class="required-asterisk">*</span>
                                                </label>
                                                <select name="branch_name" class="select2 form-control" id="branch_name">
                                                    <option value="">Please Select Branch</option>
                                                    <?php
                                                    foreach($AllBranch as $BranchValue) {
                                                        $branchCompanyId = (int)$BranchValue['CompanyID'];
                                                        $branchValueId = (int)$BranchValue['ID'];
                                                        if(!$TicketManager && $BranchID == -1) {
                                                            if($branchCompanyId !== $CorporateID) {
                                                                continue;
                                                            }
                                                        } else {
                                                            if(!$TicketManager && $BranchID !== $branchValueId) {
                                                                continue;
                                                            }
                                                        }
                                                    ?>
                                                        <option value="<?php echo $branchValueId; ?>">
                                                            <?php echo $BranchValue['BranchSite']; ?>
                                                        </option>
                                                    <?php } ?>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- SECTION 2: Classification -->
                                <div class="form-section-block">
                                    <div class="ticket-section-title">
                                        <i class="fal fa-layer-group"></i> Service Classification
                                    </div>
                                    <div class="row">
                                        <div class="col-lg-4">
                                            <div class="form-group">
                                                <label class="form-label" for="service_type">
                                                    Service Type <span class="required-asterisk">*</span>
                                                </label>
                                                <select class="form-control w-100" name="service_type" id="service_type">
                                                    <option value="">Please Select Service Type</option>
                                                    <option value="R&M">R&M</option>
                                                    <option value="Projects">Projects</option>
                                                    <option value="Supply">Supply</option>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="col-lg-4">
                                            <div class="form-group">
                                                <label class="form-label" for="service_name">
                                                    Service <span class="required-asterisk">*</span>
                                                </label>
                                                <select onchange="GetSubCategories()" class="form-control select2 w-100" name="service_name" id="service_name">
                                                    <option value="">Please Select Service</option>
                                                    <?php foreach($categories_array as $category) { ?>
                                                        <option value="<?php echo $category['CategoriesName']; ?>" data-id="<?php echo $category['ID']; ?>">
                                                            <?php echo $category['CategoriesName']; ?>
                                                        </option>
                                                    <?php } ?>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="col-lg-4" id="sub_services_div" style="display: none;">
                                            <div class="form-group">
                                                <label class="form-label" for="sub_service_name">
                                                    Sub Service <span class="required-asterisk">*</span>
                                                </label>
                                                <select class="form-control select2 w-100" name="sub_service_name" id="sub_service_name" onchange="displayOthersSubserviceTextBox(this.value)">
                                                    <option value="">Please Select Sub Service</option>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="col-lg-12" id="sub_services_others_div" style="display: none;">
                                            <div class="form-group mb-0">
                                                <label class="form-label" for="sub_service_others">
                                                    Sub Service (Others) <span class="required-asterisk">*</span>
                                                </label>
                                                <input type="text" class="form-control" name="sub_services_others" value="" id="sub_service_others" placeholder="Specify other sub-service..." />
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- SECTION 3: Ticket Details & Attachments -->
                                <div class="form-section-block">
                                    <div class="ticket-section-title">
                                        <i class="fal fa-file-alt"></i> Ticket Details
                                    </div>
                                    <div class="row">
                                        <div class="col-lg-4">
                                            <div class="form-group">
                                                <label class="form-label" for="priority">
                                                    Priority <span class="required-asterisk">*</span>
                                                </label>
                                                <select class="form-control w-100" name="priority" id="priority">
                                                    <option value="-1">Please Select Priority</option>
                                                    <option value="Low">Low</option>
                                                    <option value="Medium">Medium</option>
                                                    <option value="High">High</option>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="col-lg-4">
                                            <div class="form-group">
                                                <label class="form-label" for="ClientTicketID">Client Ticket ID</label> 
                                                <input type="text" class="form-control" name="ClientTicketID" id="ClientTicketID" placeholder="Enter Client Ticket ID">
                                            </div>
                                        </div>

                                        <div class="col-lg-4">
                                            <div class="form-group">
                                                <label class="form-label" for="ticket_attachment">Ticket Attachment</label>
                                                <div class="custom-file">
                                                    <input type="file" id="ticket_attachment" name="ticket_attachment" class="form-control">
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-lg-12">
                                            <div class="form-group mb-0">
                                                <label class="form-label" for="message">Message / Description</label>
                                                <textarea class="form-control w-100" name="message" id="message" rows="4" placeholder="Type detailed description or notes..."></textarea>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- SECTION 4: Dynamic Configurable Fields Container -->
                                <div id="configurable-fields" class="row"></div>

                                <!-- SECTION 5: Submit Action -->
                                <div class="row mt-4">
                                    <div class="col-lg-12 text-right">
                                        <a onclick="return RaiseTicket()" id="raise_ticket_btn" class="btn btn-raise-ticket text-white">
                                            <i class="fal fa-paper-plane mr-2"></i> Submit Ticket
                                        </a>
                                    </div>
                                </div>

                            </form>
                        </div>
                    </div>
                </main>

                <!-- Overlay for Mobile Navigation -->
                <div class="page-content-overlay" data-action="toggle" data-class="mobile-nav-on"></div>

                <!-- BEGIN Page Footer -->
                <?php include('../includes/common_footer.php'); ?>
                <!-- END Page Footer -->

            </div>
        </div>
    </div>
    <!-- END Page Wrapper -->

    <?php
    include('../includes/common_modules.php');
    include('../includes/common_scripts.php');
    ?>

    <script src="../js/formplugins/bootstrap-datepicker/bootstrap-datepicker.js"></script>
    <script src="../js/modules/raise-ticket.js"></script>
    <script>
    $(document).ready(function() {
        var corporateValue = $("#corporate_name").val();
        if (corporateValue) {
            SelectCorporate();
        }
    });
    </script>
</body>
</html>