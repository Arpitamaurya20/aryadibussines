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
    <?php
    include('../includes/common_head_content.php');
    ?>
    <link rel="stylesheet" media="screen, print" href="../css/formplugins/bootstrap-datepicker/bootstrap-datepicker.css">
    
    <!-- Custom UI Enhancements CSS -->
    <style type="text/css">
        .select2-container {
            z-index: 1;
        }

        /* Modern Modernized UI Styling */
        .ticket-page-header {
            background: #ffffff;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.03);
            border-left: 4px solid #3b82f6;
        }

        .ticket-card {
            background: #ffffff;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.03);
            margin-bottom: 1.5rem;
            overflow: hidden;
            transition: all 0.2s ease-in-out;
        }

        .ticket-card-header {
            background: #f8fafc;
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #e2e8f0;
            font-weight: 600;
            color: #1e293b;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
        }

        .ticket-card-header i {
            margin-right: 0.5rem;
            color: #3b82f6;
        }

        .ticket-card-body {
            padding: 1.5rem;
        }

        .form-label {
            font-weight: 600;
            color: #475569;
            font-size: 0.85rem;
            margin-bottom: 0.4rem;
        }

        .form-control, .select2-container--default .select2-selection--single {
            border-radius: 8px !important;
            border: 1px solid #cbd5e1 !important;
            padding: 0.45rem 0.75rem !important;
            height: auto !important;
            font-size: 0.9rem;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .form-control:focus {
            border-color: #3b82f6 !important;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15) !important;
        }

        .btn-raise-action {
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            color: #ffffff !important;
            font-weight: 600;
            padding: 0.65rem 2rem;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.25);
            transition: transform 0.2s, box-shadow 0.2s;
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
        }

        .btn-raise-action:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 18px rgba(37, 99, 235, 0.35);
            color: #ffffff;
        }

        .info-sidebar-card {
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: 12px;
            padding: 1.25rem;
            margin-bottom: 1.5rem;
        }

        .info-sidebar-card h5 {
            color: #1e40af;
            font-weight: 600;
            font-size: 0.95rem;
            margin-bottom: 0.75rem;
        }

        .info-sidebar-card ul {
            padding-left: 1.2rem;
            margin-bottom: 0;
            color: #1e3a8a;
            font-size: 0.85rem;
        }

        .info-sidebar-card ul li {
            margin-bottom: 0.4rem;
        }

        .sla-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .sla-high { background: #fee2e2; color: #991b1b; }
        .sla-med { background: #fef3c7; color: #92400e; }
        .sla-low { background: #dcfce7; color: #166534; }
    </style>

    <?php
    $AllCompany = getAllCompanies($conn);
    $AllBranch = getAllBranchesWithName($conn);
    $categories_obj = new Categories($conn);
    $categories_array = $categories_obj->getAllCategories();
    $CorporateID = -1;
    $BranchID = -1;
    $TicketManager = false;
    
    // User type validation logic
    if($UserType == "Corporate Admin")
    {
        $corporate_user = true;
        $CorporateID = $_SESSION['Roles']['CorporateID'];
    }
    if($UserType == "Corporate Branch User")
    {
        $corporate_user = true;
        $CorporateID = $_SESSION['Roles']['CorporateID'];
        $BranchID = $_SESSION['Roles']['BranchID'];
    }
    if(isset($_SESSION['Roles']['EmployeeRoles']))
    {
        $EmployeeRoles = $_SESSION['Roles']['EmployeeRoles'];
        foreach($EmployeeRoles as $E_Role)
        {
            if($E_Role == "Ticket Manager" || $UserType == "Admin" || $UserType == "Branch Account Manager" || $E_Role == "Branch Account Manager")
            {
                $TicketManager = true;
            }
        }
    }
    $ProductName = "Aryadibusiness";
    if ($CorporateID == 183) 
    {
        $_product = "innov";
        $conf = new Config($conn);
        $product_configuration = $conf->GetConfigParametersfromURL($_product);
        $ProductName = $product_configuration['ProductName'];
    } 
    $logoImg = "tech-logo.jpg";
    if(isset($product_configuration['logo']))
    {
        $logoImg = $product_configuration['logo'];
    }   
    ?>

    <title>
        Raise Ticket - <?=$ProductName;?>
    </title>

    <?php 
    if(isset($product_configuration['favicon']))
    {
        ?>
        <link rel="icon" type="image/png" sizes="32x32" href="../img/favicon/<?=$product_configuration['favicon'];?>">
        <?php
    }
    if($ProductName != "Aryadibusiness")
    {
        include("../css/client_generated_css.php");
    }
    ?>
</head>

<script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-6496901533964255" crossorigin="anonymous"></script>

<body class="mod-bg-1 desktop chrome webkit pace-done nav-function-fixed blur">
    <!-- Keep user type hidden container for script compatibility if required -->
    <span style="display:none;"><?php echo $UserType; ?></span>

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
                        <li class="breadcrumb-item"><a href="../dashboard/admin_dashboard"> <?=$ProductName;?></a></li>
                        <li class="breadcrumb-item active">Raise Ticket</li>
                    </ol>

                    <!-- Header Banner -->
                    <div class="ticket-page-header d-flex align-items-center justify-content-between">
                        <div>
                            <h3 class="font-weight-bold text-dark mb-1">
                                <i class="fal fa-ticket-alt text-primary mr-2"></i>Create New Support Ticket
                            </h3>
                            <p class="text-muted mb-0 small">Submit a request to our service team. Fill in the details below for faster processing.</p>
                        </div>
                    </div>

                    <!-- Main Content Grid -->
                    <form method="post" id="raise_ticket_form" enctype="multipart/form-data">
                        <div class="row">
                            
                            <!-- Left Column: Form Cards -->
                            <div class="col-lg-8">
                                
                                <!-- Card 1: Account & Location Details -->
                                <div class="ticket-card">
                                    <div class="ticket-card-header">
                                        <i class="fal fa-building"></i> 1. Account & Location Details
                                    </div>
                                    <div class="ticket-card-body">
                                        <div class="row">
                                            <div class="col-lg-6">
                                                <div class="form-group">
                                                    <label class="form-label" for="corporate_name">Corporate <span class="text-danger">*</span></label>
                                                    <select onchange="SelectCorporate()" name="corporate_name" class="select2 form-control" id="corporate_name">
                                                        <option value="">Please Select Corporate</option>
                                                        <?php
                                                        foreach($AllCompany as $CompanyValue)
                                                        {
                                                            if(!$TicketManager && $CompanyValue['ID'] !== $CorporateID)
                                                            {
                                                                continue;
                                                            }
                                                        ?>
                                                            <option value="<?php echo $CompanyValue['ID']; ?>"><?php echo $CompanyValue['CompanyName']; ?></option>
                                                        <?php
                                                        }
                                                        ?>
                                                    </select>
                                                </div>
                                            </div>

                                            <div class="col-lg-6" id="branch_div" style="display:none;">
                                                <div class="form-group">
                                                    <label class="form-label" for="branch_name">Branch <span class="text-danger">*</span></label>
                                                    <select name="branch_name" class="select2 form-control" id="branch_name">
                                                        <option value="">Please Select Branch</option>
                                                        <?php
                                                        foreach($AllBranch as $BranchValue)
                                                        {
                                                            if(!$TicketManager && $BranchID == -1)
                                                            {
                                                                if($BranchValue['CompanyID'] !== $CorporateID)
                                                                {
                                                                    continue;
                                                                }
                                                            }
                                                            else
                                                            {
                                                                if(!$TicketManager && $BranchID !== $BranchValue['ID'])
                                                                {
                                                                    continue;
                                                                }
                                                            }
                                                        ?>
                                                            <option value="<?php echo $BranchValue['ID']; ?>"><?php echo $BranchValue['BranchSite']; ?></option>
                                                        <?php
                                                        }
                                                        ?>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Card 2: Service Classification -->
                                <div class="ticket-card">
                                    <div class="ticket-card-header">
                                        <i class="fal fa-list-alt"></i> 2. Service Classification
                                    </div>
                                    <div class="ticket-card-body">
                                        <div class="row">
                                            <div class="col-lg-6">
                                                <div class="form-group">
                                                    <label class="form-label" for="service_type">Service Type <span class="text-danger">*</span></label>
                                                    <select class="form-control w-100" name="service_type" id="service_type">
                                                        <option value="">Please Select Service Type</option>
                                                        <option value="R&M">R&M</option>
                                                        <option value="Projects">Projects</option>
                                                        <option value="Supply">Supply</option>
                                                    </select>
                                                </div>
                                            </div>

                                            <div class="col-lg-6">
                                                <div class="form-group">
                                                    <label class="form-label" for="service_name">Service <span class="text-danger">*</span></label>
                                                    <select onchange="GetSubCategories()" class="form-control w-100 select2" name="service_name" id="service_name">
                                                        <option value="">Please Select Service</option>
                                                        <?php
                                                        foreach($categories_array as $category)
                                                        {
                                                        ?>
                                                            <option value="<?php echo $category['CategoriesName']; ?>" data-id="<?php echo $category['ID']; ?>"><?php echo $category['CategoriesName']; ?></option>
                                                        <?php
                                                        }
                                                        ?>
                                                    </select>
                                                </div>
                                            </div>

                                            <div class="col-lg-6 mt-3" id="sub_services_div" style="display: none;">
                                                <div class="form-group">
                                                    <label class="form-label" for="sub_service_name">Sub Service <span class="text-danger">*</span></label>
                                                    <select class="form-control w-100 select2" name="sub_service_name" id="sub_service_name" onchange="displayOthersSubserviceTextBox(this.value)">
                                                        <option value="">Please Select Sub Service</option>
                                                    </select>
                                                </div>
                                            </div>

                                            <div class="col-lg-6 mt-3" id="sub_services_others_div" style="display: none;">
                                                <div class="form-group">
                                                    <label class="form-label" for="sub_service_others">Sub Service (Others) <span class="text-danger">*</span></label>
                                                    <input type="text" class="form-control" name="sub_services_others" value="" id="sub_service_others" placeholder="Specify other sub-service" />
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Card 3: Identification & Priority -->
                                <div class="ticket-card">
                                    <div class="ticket-card-header">
                                        <i class="fal fa-tags"></i> 3. Identification & Priority
                                    </div>
                                    <div class="ticket-card-body">
                                        <div class="row">
                                            <div class="col-lg-6">
                                                <div class="form-group">
                                                    <label class="form-label" for="ClientTicketID">Client Ticket ID</label>
                                                    <input type="text" class="form-control" name="ClientTicketID" id="ClientTicketID" placeholder="Enter reference ticket ID (Optional)">
                                                </div>
                                            </div>

                                            <div class="col-lg-6">
                                                <div class="form-group">
                                                    <label class="form-label" for="priority">Priority <span class="text-danger">*</span></label>
                                                    <select class="form-control w-100" name="priority" id="priority">
                                                        <option value="-1">Please Select Priority</option>
                                                        <option value="Low">Low (General Inquiry / Minor issue)</option>
                                                        <option value="Medium">Medium (Operational Impact)</option>
                                                        <option value="High">High (Critical / System Outage)</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Card 4: Ticket Details & Attachments -->
                                <div class="ticket-card">
                                    <div class="ticket-card-header">
                                        <i class="fal fa-file-alt"></i> 4. Description & Attachments
                                    </div>
                                    <div class="ticket-card-body">
                                        <div class="row">
                                            <div class="col-lg-12">
                                                <div class="form-group">
                                                    <label class="form-label" for="message">Message / Description</label>
                                                    <textarea class="form-control w-100" name="message" id="message" rows="4" placeholder="Provide detailed context or steps to reproduce the issue..."></textarea>
                                                </div>
                                            </div>

                                            <div class="col-lg-12 mt-3">
                                                <div class="form-group mb-0">
                                                    <label class="form-label" for="ticket_attachment">Ticket Attachment</label>
                                                    <div class="custom-file">
                                                        <input type="file" id="ticket_attachment" name="ticket_attachment" class="custom-file-input">
                                                        <label class="custom-file-label" for="ticket_attachment">Choose file (Screenshots, PDFs, Logs)...</label>
                                                    </div>
                                                    <small class="text-muted mt-1 d-block">Supported formats: JPG, PNG, PDF, DOCX (Max size: 10MB)</small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Dynamic Configurable Fields Container -->
                                <div id="configurable-fields" class="row"></div>

                                <!-- Action Buttons -->
                                <div class="mb-4">
                                    <button type="button" onclick="return RaiseTicket()" id="raise_ticket_btn" class="btn-raise-action">
                                        <i class="fal fa-paper-plane"></i> Raise Ticket
                                    </button>
                                    <a href="../dashboard/admin_dashboard" class="btn btn-light ml-2 border" style="border-radius: 8px;">Cancel</a>
                                </div>

                            </div>

                            <!-- Right Column: Helper Sidebar -->
                            <div class="col-lg-4">
                                <!-- Quick Guidelines -->
                                <div class="info-sidebar-card">
                                    <h5><i class="fal fa-lightbulb text-warning mr-1"></i> Submission Tips</h5>
                                    <ul>
                                        <li>Select correct <strong>Corporate</strong> and <strong>Branch</strong> to ensure proper ticket assignment.</li>
                                        <li>Provide an accurate <strong>Service</strong> category to route this directly to the responsible team.</li>
                                        <li>Attach clear <strong>screenshots</strong> or logs to help resolve issues faster.</li>
                                    </ul>
                                </div>

                                <!-- Response Times -->
                                <div class="ticket-card">
                                    <div class="ticket-card-header">
                                        <i class="fal fa-clock"></i> SLA Targets
                                    </div>
                                    <div class="ticket-card-body p-3">
                                        <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
                                            <span class="small font-weight-bold"><span class="sla-badge sla-high">High</span> Critical</span>
                                            <span class="small text-muted">&lt; 2 Hours</span>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
                                            <span class="small font-weight-bold"><span class="sla-badge sla-med">Medium</span> Moderate</span>
                                            <span class="small text-muted">&lt; 8 Hours</span>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="small font-weight-bold"><span class="sla-badge sla-low">Low</span> General</span>
                                            <span class="small text-muted">&lt; 24 Hours</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Need Help Contact Box -->
                                <div class="ticket-card bg-light">
                                    <div class="ticket-card-body text-center p-4">
                                        <i class="fal fa-headset fa-2x text-primary mb-2"></i>
                                        <h6 class="font-weight-bold mb-1">Need Urgent Assistance?</h6>
                                        <p class="text-muted small mb-0">Contact our technical helpdesk directly if you are experiencing a site-wide outage.</p>
                                    </div>
                                </div>

                            </div>

                        </div>
                    </form>

                </main>

                <!-- Mobile Overlay -->
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
            // Update custom file input label when file chosen
            $('.custom-file-input').on('change', function() {
                var fileName = $(this).val().split('\\').pop();
                $(this).next('.custom-file-label').addClass("selected").html(fileName);
            });
        });
    </script>
</body>

</html>