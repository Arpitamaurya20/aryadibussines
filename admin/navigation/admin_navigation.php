<?php 
$logoImg = "aryadi.png";
$logoPath = "../img/";
$navigation_bg = "#003f88";
if(isset($product_configuration['logo']))
{
    $logoImg = $product_configuration['logo'];
    $navigation_bg = $product_configuration['primary_color'];
}
$host = $_SERVER['HTTP_HOST'] ?? '';
$hostParts = explode('.', $host);
$subdomain = (count($hostParts) > 2) ? $hostParts[0] : '';
$isPoonawalla = ($subdomain === "poonawalla" || strpos($host, 'poonawalla') !== false || strpos($host, 'ponawalla') !== false);
if ($isPoonawalla) {
    $logoImg = "pfl-logo.svg";
    $logoPath = "../../images/";
}
?>
<style type="text/css">
.nav-footer {
    height: unset !important;
}

.nav-menu li>ul {
    background-color: #002e63 !important;
    padding-top: 10px;
    padding-bottom: 10px;
}

.nav-footer {
    padding-top: 0px !important;
    margin-top: 0px !important;
    background: none;
}

.nav-footer {
    padding-top: 0px !important;
    margin-top: 0px !important;
    background: rgba(12, 12, 12, 1) !important;
}

.nav-menu li a b {
    color: #fff !important;
    font-weight: 900 !important;
    font-size: 19px !important;
}

/* --- Active Menu Item Highlight --- */
.nav-menu li.active > a,
.nav-menu li.open > a,
.nav-menu li > a:hover {
    background-color: rgba(0, 0, 0, 0.25) !important;
    color: #ffffff !important;
    box-shadow: inset 4px 0 0 0 #5BB6E9 !important;
}
.nav-menu li.active > a > i,
.nav-menu li.open > a > i,
.nav-menu li > a:hover > i {
    color: #5BB6E9 !important;
}
/* --------------------------------- */

.page-logo {
    height: 120px !important;
    padding: 0 10px !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    border-bottom: 1px solid rgba(0, 0, 0, 0.08) !important;
    background-color: #ffffff !important;
}
.page-logo-link {
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    width: 100% !important;
    height: 100% !important;
}
.page-logo-link img {
    max-height: 100px !important;
    max-width: 100% !important;
    width: auto !important;
    height: auto !important;
    object-fit: contain !important;
    display: block !important;
    margin: 0 auto !important;
}
.info-card {
    height: 100px !important;
    padding: 10px 15px !important;
    display: flex !important;
    align-items: center !important;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1) !important;
}
.info-card img.cover {
    height: 100% !important;
    width: 100% !important;
    object-fit: cover !important;
    display: block !important;
}
.info-card .profile-image {
    width: 45px !important;
    height: 45px !important;
    z-index: 2 !important;
}
.info-card-text {
    margin-left: 12px !important;
    z-index: 2 !important;
}
.header-function-fixed:not(.nav-function-top):not(.nav-function-fixed) .page-sidebar .primary-nav {
    margin-top: 120px !important;
}


</style>
<aside class="page-sidebar" style=" background-color: <?=$navigation_bg;?> !important; ">
    <div class="page-logo">
        <a href="#" class="page-logo-link press-scale-down d-flex align-items-center position-relative"
            data-toggle="modal" data-target="#modal-shortcut">
            <?php 
            if($logoImg == "aryadi.png")
            {
            ?>
                <img style="max-height: 110px !important; width: 85% !important; max-width: 100% !important; height: auto !important; object-fit: contain;" src="<?=$logoPath;?><?=$logoImg;?>"  aria-roledescription="logo">
            <?php
            }
            elseif($logoImg == "tech-logo.jpg" || $logoImg == "pfl-logo.svg" || $logoImg == "logo.png")
            {
            ?>
                <img width="112px" style="height:35px; object-fit: contain;" src="<?=$logoPath;?><?=$logoImg;?>"  aria-roledescription="logo">
            <?php
            }
            else
            {
                ?>
                <img width="112px" style="height:66px; object-fit: contain;" src="<?=$logoPath;?><?=$logoImg;?>"  aria-roledescription="logo">
                <?php
            }
            ?>
            <span class="position-absolute text-white opacity-50 small pos-top pos-right mr-2 mt-n2"></span>
            <!-- <i class="fal fa-angle-down d-inline-block ml-1 fs-lg color-primary-300"></i> -->
        </a>
    </div>
    <!-- BEGIN PRIMARY NAVIGATION -->
    <nav id="js-primary-nav" class="primary-nav" role="navigation">
        <!--<div class="nav-filter">
            <div class="position-relative">
                <input type="text" id="nav_filter_input" placeholder="Filter menu" class="form-control" tabindex="0">
                <a href="#" onclick="return false;" class="btn-primary btn-search-close js-waves-off" data-action="toggle" data-class="list-filter-active" data-target=".page-sidebar">
                    <i class="fal fa-chevron-up"></i>
                </a>
            </div>
        </div>-->
        <div class="info-card">
            <img src="../img/demo/avatars/demo-avatar.png" class="profile-image rounded-circle" alt="Admin">
            <div class="info-card-text">
                <a href="#" class="d-flex align-items-center text-white">
                    <span class="text-truncate text-truncate-sm d-inline-block">
                        <?php echo $_SESSION['pb_username']; ?>
                    </span>
                </a>
                <span class="d-inline-block text-truncate text-truncate-sm"><?php echo $_SESSION['UserType']; ?></span>
            </div>
            <img src="../img/card-backgrounds/admin-img1.png" class="cover" alt="cover">
            <!--<a href="#" onclick="return false;" class="pull-trigger-btn" data-action="toggle" data-class="list-filter-active" data-target=".page-sidebar" data-focus="nav_filter_input">
                <i class="fal fa-angle-down"></i>
            </a>-->
        </div>
        <ul id="js-nav-menu" class="nav-menu">
            <?php
            if($_Nav_Dashboard)
            {
            ?>
            <li id="nav_dashboard">
                <a href="../dashboard/admin_dashboard" data-filter-tags="Dashboard">
                    <i class="fa-solid fa-palette"></i>
                    <span class="nav-link-text" data-i18n="nav.Dashboard">Dashboard</span>
                </a>
            </li>
            <?php
            }
            if($_Nav_My_State_Dashboard)
            {
            ?>
            <li id="nav_my_state_dashboard">
                <a href="../dashboard/manager_dashboard" data-filter-tags="My State Dashboard">
                    <i class="fa-solid fa-map-location-dot"></i>
                    <span class="nav-link-text" data-i18n="nav.My_State_Dashboard">My State Dashboard</span>
                </a>
            </li>
            <?php
            }
            if($_Nav_My_Branch_Dashboard)
            {
            ?>
            <li id="nav_my_branch_dashboard">
                <a href="../dashboard/manager_dashboard" data-filter-tags="My Branch Dashboard">
                    <i class="fa-solid fa-building"></i>
                    <span class="nav-link-text" data-i18n="nav.My_Branch_Dashboard">My Branch Dashboard</span>
                </a>
            </li>
            <?php
            }
            if($_Nav_MIS_Report)
            {
            ?>
           
            <?php
            }
            if($_Nav_Analytics_Dashboard)
            {
            ?>
            <li id="nav_analytics_dashboard">
                <a href="../dashboard/analytics_dashboard" data-filter-tags="Dashboard">
                    <i class="fal fa-chart-pie"></i>
                    <span class="nav-link-text" data-i18n="nav.Dashboard">Analytics Dashboard</span>
                </a>
            </li>
            
            <li id="nav_account_dashboard">
                <a href="../dashboard/accounts_dashboard" data-filter-tags="Dashboard">
                    <i class="fa-solid fa-list"></i>
                    <span class="nav-link-text" data-i18n="nav.Dashboard">Account Dashboard</span>
                </a>
            </li>
            <?php
            }
            if($_Nav_Analytics_Daily_Tracker)
            {
            ?>
            <li id="nav_daily_tracker_dashboard">
                <a href="../dashboard/daily_tracker_dashboard" data-filter-tags="Daily Tracker Dashboard">
                    <i class="fa-solid fa-list"></i>
                    <span class="nav-link-text" data-i18n="nav.Dashboard">Daily Tracker</span>
                </a>
            </li>
            <?php
            }
            $corporateTicketsApprovalCount = 0;
            if($_Nav_Corporate_approval || $_Nav_Corporate_quotation_approval)
            {
                $navCountConn = null;
                if(isset($conn) && $conn instanceof mysqli)
                {
                    $navCountConn = $conn;
                }
                else if(function_exists('_connectodb'))
                {
                    $navCountConn = _connectodb();
                }

                if($navCountConn instanceof mysqli)
                {
                    $corporateApprovalFilter = "";
                    $navCorporateID = $_SESSION['Roles']['CorporateID'] ?? ($_SESSION['CorporateID'] ?? null);
                    if(
                        isset($_SESSION['UserType'])
                        && $_SESSION['UserType'] == "Corporate Admin"
                        && $navCorporateID !== null
                    )
                    {
                        $navCorporateID = (int)$navCorporateID;
                        $corporateApprovalFilter = " AND CorporateID = $navCorporateID";
                    }

                    $ticketApprovalCountSql = "SELECT COUNT(*) as total FROM corporate_tickets WHERE Status = 'Need Approval By Company Admin'".$corporateApprovalFilter;
                    $ticketApprovalCountResult = mysqli_query($navCountConn,$ticketApprovalCountSql);
                    if($ticketApprovalCountResult)
                    {
                        $ticketApprovalCountRow = mysqli_fetch_assoc($ticketApprovalCountResult);
                        $corporateTicketsApprovalCount += (int)($ticketApprovalCountRow['total'] ?? 0);
                    }

                    $quoteCorporateApprovalFilter = "";
                    $navCorporateID = $_SESSION['Roles']['CorporateID'] ?? ($_SESSION['CorporateID'] ?? null);
                    if(
                        isset($_SESSION['UserType'])
                        && $_SESSION['UserType'] == "Corporate Admin"
                        && $navCorporateID !== null
                    )
                    {
                        $navCorporateID = (int)$navCorporateID;
                        $quoteCorporateApprovalFilter = " AND b.CorporateID = $navCorporateID";
                    }

                    $quoteApprovalCountSql = "SELECT COUNT(*) as total
                        FROM corporate_ticket_quotation a
                        INNER JOIN corporate_tickets b ON a.TicketID = b.ID
                        WHERE a.QuotationStatus = 'Quote Pending Company Admin Approval'
                            AND a.IsActive = 1".$quoteCorporateApprovalFilter;
                    $quoteApprovalCountResult = mysqli_query($navCountConn,$quoteApprovalCountSql);
                    if($quoteApprovalCountResult)
                    {
                        $quoteApprovalCountRow = mysqli_fetch_assoc($quoteApprovalCountResult);
                        $corporateTicketsApprovalCount += (int)($quoteApprovalCountRow['total'] ?? 0);
                    }
                }
            }
            if($_Nav_Corporate_Tickets || $_Nav_Corporate || $_Nav_Corporate_approval || $_Nav_Corporate_quotation_approval)
            {
            ?>
            <li id="nav_corporate_tickets">
                <a href="../corporate-tickets/view-corporate-tickets"
                    data-filter-tags="Company">
                    <i class="fa-solid fa-users"></i>
                    <span class="nav-link-text" data-i18n="nav.Corporate Tickets">Corporate Tickets</span>
                    <?php if($corporateTicketsApprovalCount > 0) { ?>
                    <span class="badge badge-danger ml-auto"><?php echo $corporateTicketsApprovalCount; ?></span>
                    <?php } ?>
                </a>
            </li>
            <?php
            }
            if($_Nav_Tender_RFQ)
            {
            ?>
            <li id="nav_tender_rfq">
                <a href="../tender-rfq/view-tender-rfq-tickets" data-filter-tags="Tender RFQ">
                    <i class="fa-solid fa-file-signature"></i>
                    <span class="nav-link-text" data-i18n="nav.Tender_RFQ">Tender RFQ</span>
                </a>
            </li>
            <?php
            }
            if($_Nav_PPM_Tickets)
            {
            ?>
            <li id="nav_ppm_tickets">
                <a href="../ppm-ticket/view-all-ppm-tickets" data-filter-tags="Company">
                    <i class="fal fa-warehouse"></i>
                    <span class="nav-link-text" data-i18n="nav.PPMTickets">PPM Tickets</span>
                </a>
            </li>
            <li id="nav_crm">
                <a href="#" data-filter-tags="CRM">
                    <i class="fas fa-handshake"></i>
                    <span class="nav-link-text" data-i18n="nav.CRM">CRM Modules</span>
                </a>
                <ul>
                    <li><a href="../crm/view-leads.php"><span class="nav-link-text">Manage Leads</span></a></li>
                    <li><a href="../crm/view-customers.php"><span class="nav-link-text">Manage Customers</span></a></li>
                    <li><a href="../crm/view-products.php"><span class="nav-link-text">Manage Products</span></a></li>
                    <li><a href="../crm/view-quotations.php"><span class="nav-link-text">Manage Quotations</span></a></li>
                </ul>
            </li>
            <?php
            }
            if($_Nav_PPM_SC)
            {
            ?>
            <li id="nav_ppm_billing">
                <a href="../PPMBilling/view-ppm-billing" data-filter-tags="Billing PPM Invoice">
                    <i class="fas fa-file-invoice"></i>
                    <span class="nav-link-text" data-i18n="nav.PPMBilling">PPM Billing</span>
                </a>
            </li>

             <?php
            }
            if($_Nav_Mail_SC)
            {
            ?>
           <li id="nav_mail_scheduler">
                <a href="../mail-scheduler/view-mail-scheduler" data-filter-tags="Mail Scheduler">
                    <i class="fal fa-clock"></i>
                    <span class="nav-link-text" data-i18n="nav.MailScheduler">Mail Scheduler</span>
                </a>
            </li>
            <?php
            }
            if($_Nav_Corporate_Finance_Tickets)
            {
            ?>
            <li id="nav_finance">
                <a href="#" data-filter-tags="Fianance">
                    <i class="fa-solid fa-users"></i>
                    <span class="nav-link-text" data-i18n="nav.Fianance">Finance</span>
                </a>
                <ul class="pl-3">
                    <li id="nav_corporate_tickets_finance">
                        <a href="../corporate-tickets/view-corporate-tickets-finance" data-filter-tags="Tickets Finance">
                            <i class="fa-solid fa-calendar-check"></i>
                            <span class="nav-link-text" data-i18n="nav.Bookings">Tickets Finance</span>
                        </a>
                    </li>
                    <li id="nav_ticket_quotations">
                        <a href="../corporate-tickets/view-all-ticket-quotations" data-filter-tags="Quotations">
                            <i class="fa-solid fa-calendar-check"></i>
                            <span class="nav-link-text" data-i18n="nav.Quotations">Quotations</span>
                        </a>
                    </li>
                </ul>
            </li>
            <?php
            }
            if($_Nav_Site_Visits)
            {
            ?>
            <li id="nav_site_visits">
                <a href="../site_visits/view-all-site-visits" data-filter-tags="Company">
                    <i class="fa-solid fa-users"></i>
                    <span class="nav-link-text" data-i18n="nav.Site Visits">Site Visits</span>
                </a>
            </li>
            <?php
            }
            if($_Nav_Account_Tickets)
            {
            ?>
            <li id="nav_corporate_tickets">
                <a href="../corporate-tickets/view-account-tickets" data-filter-tags="Company">
                    <i class="fa-solid fa-users"></i>
                    <span class="nav-link-text" data-i18n="nav.Account Tickets">Account Tickets</span>
                </a>
            </li>
            <?php
            }
            if($_Nav_Account_Branch_Tickets)
            {
            ?>
            <li id="nav_corporate_branch_tickets">
                <a href="../corporate-tickets/view-account-branch-tickets"
                    data-filter-tags="Branch">
                    <i class="fas fa-tasks"></i>
                    <span class="nav-link-text" data-i18n="nav.Account Tickets">Account Branch Tickets</span>
                </a>
            </li>
            <?php
            }
            if($_Nav_Bookings)
            {
            ?>


            <li id="nav_booking">
                <a href="#" data-filter-tags="Services">
                    <i class="fa-solid fa-calendar-check"></i>
                    <span class="nav-link-text" data-i18n="nav.Services">Bookings</span>
                </a>
                <ul class="pl-3">
                    <li id="nav_techxpert_bookings">
                        <a href="../booking/view_booking" data-filter-tags="Bookings">
                            <i class="fa-solid fa-calendar-check"></i>
                            <span class="nav-link-text" data-i18n="nav.Bookings">Aryadibusiness Bookings</span>
                        </a>
                    </li>
                    <li id="nav_goodlife_bookings">
                        <a href="../booking/view-goodlife-booking" data-filter-tags="Bookings">
                            <i class="fa-solid fa-calendar-check"></i>
                            <span class="nav-link-text" data-i18n="nav.Bookings">Goodlife Bookings</span>
                        </a>
                    </li>
                </ul>
            </li>

            <?php
            }
            if($_Nav_Projects)
            {
            ?>
            <li id="nav_projects">
                <a href="../project-management/view-projects" data-filter-tags="Projects">
                    <i class="fa-solid fa-calendar-check"></i>
                    <span class="nav-link-text" data-i18n="nav.Projects">Projects</span>
                </a>
            </li>
            <?php
            }
            if($_Nav_Employees)
            {
            ?>
            <li id="nav_employees">
                <a href="../employees/view-employees" data-filter-tags="Employees">
                    <i class="fa-solid fa-users"></i>
                    <span class="nav-link-text" data-i18n="nav.Employees">Employees</span>
                </a>
            </li>
            <?php
            }
            if($_Nav_Employee_Asset_Acknowledgement)
            {
            ?>
            <li id="_Nav_Employee_Asset_Acknowledgement">
                <a href="../employee-asset-acknowledgement/view-employee-asset-acknowledgement.php"
                    data-filter-tags="employee asset acknowledgement">
                    <i class="fa-solid fa-laptop"></i>
                    <span class="nav-link-text" data-i18n="nav.employee_asset_acknowledgement">Asset Acknowledgement</span>
                </a>
            </li>
            <?php
            }
            if($_Nav_Employees_details)
            {
            ?>
            <li id="nav_employees_details">
                <a href="../employees-details/view-employees-detail"
                    data-filter-tags="EmployeesDetais">
                    <i class="fa-solid fa-users"></i>
                    <span class="nav-link-text" data-i18n="nav.EmployeesDetails">Employee Detail</span>
                </a>
            </li>
            <?php
            }
            ?>
            <?php
            if($_Nav_Attendance_List)
            {
            ?>
            <li id="nav_attendance">
                <a href="../attendance-list/view-attendance-list.php"
                    data-filter-tags="attendance">
                    <i class="fa-solid fa-users"></i>
                    <span class="nav-link-text" data-i18n="nav.attendance">Employees Attendance</span>
                </a>
            </li>
            <?php
            }
            if($_Nav_Attendance_Approval)
            {
            ?>
            <li id="nav_attendance_approval">
                <a href="../attendance-approval/view-attendance-approval.php"
                    data-filter-tags="attendance approval">
                    <i class="fa-solid fa-user-check"></i>
                    <span class="nav-link-text" data-i18n="nav.attendance_approval">Team Attendance</span>
                </a>
            </li>

            <?php
            }
            if($_Nav_Attendance_HR_Approval)
            {
            ?>
            <li id="nav_attendance_hr_approval">
                <a href="../attendance-hr-approval/view-attendance-hr-approval.php"
                    data-filter-tags="attendance hr approval">
                    <i class="fa-solid fa-clipboard-check"></i>
                    <span class="nav-link-text" data-i18n="nav.attendance_hr_approval">HR Attendance Approval</span>
                </a>
            </li>
            <?php
            }
            if($_Nav_Leave_HR_Approval)
            {
            ?>
            <li id="nav_leave_hr_approval">
                <a href="../leave-hr-approval/view-leave-hr-approval.php"
                    data-filter-tags="leave hr approval">
                    <i class="fa-solid fa-calendar-check"></i>
                    <span class="nav-link-text" data-i18n="nav.leave_hr_approval">HR Leave Approval</span>
                </a>
            </li>
            <?php
            }
            if($_Nav_Leave_Mgmt_HR)
            {
            ?>
            <li id="nav_leave_policy">
                <a href="../employee-leave-mgmt/view-leave-policy"
                    data-filter-tags="leave policy balance">
                    <i class="fa-solid fa-sliders"></i>
                    <span class="nav-link-text">Leave Policy</span>
                </a>
            </li>
            <li id="nav_comp_off_approval">
                <a href="../employee-leave-mgmt/view-comp-off-approval"
                    data-filter-tags="comp off leave">
                    <i class="fa-solid fa-business-time"></i>
                    <span class="nav-link-text">Comp-off Approval</span>
                </a>
            </li>
            <?php
            }
            if($_Nav_HR_Tickets)
            {
            ?>
            <li id="nav_hr_tickets_manage">
                <a href="../hr-tickets/view-hr-tickets"
                    data-filter-tags="hr tickets employee concerns">
                    <i class="fa-solid fa-headset"></i>
                    <span class="nav-link-text">HR Tickets</span>
                </a>
            </li>
            <?php
            }
            if($_Nav_Convenience_Approval)
            {
            ?>
            <li id="_Nav_Convenience_Approval">
                <a href="../convenience-approval/view-convenience-approval.php"
                    data-filter-tags="convenience approval supervisor">
                    <i class="fa-solid fa-route"></i>
                    <span class="nav-link-text" data-i18n="nav.convenience_approval">Team Convenience</span>
                </a>
            </li>
            <?php
            }
            if($_Nav_Convenience_HR_Approval)
            {
            ?>
            <li id="_Nav_Convenience_HR_Approval">
                <a href="../convenience-hr-approval/view-convenience-hr-approval.php"
                    data-filter-tags="convenience hr approval">
                    <i class="fa-solid fa-hand-holding-dollar"></i>
                    <span class="nav-link-text" data-i18n="nav.convenience_hr_approval">HR Convenience Approval</span>
                </a>
            </li>
            <?php
            }
            if($_Nav_Convenience_Finance_Payment)
            {
            ?>
            <li id="_Nav_Convenience_Finance_Payment">
                <a href="../convenience-finance-payment/view-convenience-finance-payment.php"
                    data-filter-tags="convenience finance payment">
                    <i class="fa-solid fa-money-check-dollar"></i>
                    <span class="nav-link-text" data-i18n="nav.convenience_finance_payment">Convenience Payment</span>
                </a>
            </li>
            <?php
            }
            if($_Nav_Portal_Notifications)
            {
            ?>
            <li id="nav_portal_notifications">
                <a href="../notifications/view-notifications.php"
                    data-filter-tags="notifications push">
                    <i class="fa-solid fa-bell"></i>
                    <span class="nav-link-text" data-i18n="nav.portal_notifications">Send Notifications</span>
                </a>
            </li>
            <?php
            }
            
            if($_Nav_Services)
            {
            ?>
            <li id="nav_services">
                <a href="#" data-filter-tags="Services">
                    <i class="fa-solid fa-gears"></i>
                    <span class="nav-link-text" data-i18n="nav.Services">Services</span>
                </a>
                <ul class="pl-3">
                    <li id="main_services">
                        <a href="../Services/view-services" data-filter-tags="Services">
                            <i class="fa-solid fa-screwdriver-wrench"></i>
                            <span class="nav-link-text" data-i18n="nav.Services">Main Services</span>
                        </a>
                    </li>
                    <!-- <li id="nav_location_services">
                        <a href="../location-service/view_location_service"
                            data-filter-tags="Location Services">
                            <i class="fa-sharp fa-solid fa-street-view"></i>
                            <span class="nav-link-text" data-i18n="nav.Location_Services">Location Services</span>
                        </a>
                    </li> -->
                </ul>
            </li>
            <?php
            }
            ?>
            <?php
            if($_Nav_Site_Setting)
            {
            ?>
            <li id="nav_site_setting">
                <a href="#" data-filter-tags="SiteSetting">
                    <i class="fa-solid fa-globe"></i>
                    <span class="nav-link-text" data-i18n="nav.SiteSetting">Site Setting</span>
                </a>
                <ul class="pl-3">
                    <li id="nav_testimonal">
                        <a href="../testimonials/view-testimonials"
                            data-filter-tags="Testimonials">
                            <i class="fa-solid fa-hand-holding-heart"></i>
                            <span class="nav-link-text" data-i18n="nav.Testimonials">Testimonials</span>
                        </a>
                    </li>
                    <!-- <li id="nav_review">
                        <a href="../reviews/view-reviews" data-filter-tags="Reviews">
                            <i class="fa-solid fa-square-pen"></i>
                            <span class="nav-link-text" data-i18n="nav.Reviews">Reviews</span>
                        </a>
                    </li>
                    <li id="nav_banners">
                        <a href="../banners/view-banners" data-filter-tags="Banners">
                            <i class="fa-solid fa-desktop"></i>
                            <span class="nav-link-text" data-i18n="nav.Banners">Banners</span>
                        </a>
                    </li> -->
                </ul>
            </li>
            <?php
            }
            ?>
            <?php
            if($_Nav_Contact)
            {
            ?>
            <li id="nav_contact">
                <a href="#" data-filter-tags="Contact">
                    <i class="fa-solid fa-address-book"></i>
                    <span class="nav-link-text" data-i18n="nav.Contact">Contact</span>
                </a>
                <ul class="pl-3">
                    <li id="nav_resume">
                        <a href="../resume/view_resume" data-filter-tags="Resumes">
                            <i class="fa-solid fa-file"></i>
                            <span class="nav-link-text" data-i18n="nav.Resumes">Resumes</span>
                        </a>
                    </li>
                    <li id="nav_enquiries">
                        <a href="../enquiry/view_enquiry" data-filter-tags="Enquiries">
                            <i class="fa-sharp fa-solid fa-book"></i>
                            <span class="nav-link-text" data-i18n="nav.Enquiries">Contact Enquiries</span>
                        </a>
                    </li>
                    <li id="nav_quote_enquiries">
                        <a href="../get-quote/view-get-quote-enquiry" data-filter-tags="get-quote">
                            <i class="fa-sharp fa-solid fa-book"></i>
                            <span class="nav-link-text" data-i18n="nav.get-quote">Quote Enquiries</span>
                        </a>
                    </li>
                    <li id="nav_join_us_enquiries">
                        <a href="../join-us/view-join-us-enquiry" data-filter-tags="get-join-us">
                            <i class="fa-sharp fa-solid fa-book"></i>
                            <span class="nav-link-text" data-i18n="nav.join-us">Join Us</span>
                        </a>
                    </li>
                </ul>
            </li>
            <?php
            }
            ?>


            <?php
            if($_Nav_Configuration)
            {
            ?>
            <li id="nav_configuration">
                <a href="#" data-filter-tags="Configuration">
                    <i class="fa-solid fa-pen-to-square"></i>
                    <span class="nav-link-text" data-i18n="nav.Configuration">Configuration</span>
                </a>
                <ul class="pl-3">

                    <li id="nav_manage_CuopunCode">
                    <a href="../couponcode/view-all-cuopuncode"
                       data-filter-tags="Configuration Manage Coupon Code">
                        <i class="fa-solid fa-tags"></i>
                        <span class="nav-link-text" data-i18n="nav.Configuration_Manage_CouponCode">
                            Manage Coupon Code
                        </span>
                    </a>
                </li>

                    <li id="nav_manage_site">
                        <a href="../manage-site/view_site"
                            data-filter-tags="Configuration Manage Sites"><i
                                class="fa-sharp fa-solid fa-list-check"></i>
                            <span class="nav-link-text" data-i18n="nav.Configuration_Manage_Sites">
                                Manage Sites</span>
                        </a>
                    </li>
                    <li id="nav_manage_uom">
                        <a href="../manage-uom/view-uom"
                            data-filter-tags="Configuration Manage UoM"><i class="fa-sharp fa-solid fa-list-check"></i>
                            <span class="nav-link-text" data-i18n="nav.Configuration_Manage_UoM">
                                Manage UoM</span>
                        </a>
                    </li>
                    <!-- <li id="nav_manage_equipment">
                        <a href="../manage-equipment/view-equipment"
                            data-filter-tags="Configuration Manage Equipment"><i
                                class="fa-sharp fa-solid fa-list-check"></i>
                            <span class="nav-link-text" data-i18n="nav.Configuration_Manage_equipment">
                                Manage Equipment </span>
                        </a>
                    </li> -->
                    <li id="nav_manage_categories">
                        <a href="../manage-categories/view-categories"
                            data-filter-tags="Configuration Manage Categories"><i
                                class="fa-sharp fa-solid fa-list-check"></i>
                            <span class="nav-link-text" data-i18n="nav.Configuration_Manage_categories">
                                Manage Categories </span>
                        </a>
                    </li>
                    <li id="nav_manage_sub_categories">
                        <a href="../manage-sub-categories/view-sub-categories"
                            data-filter-tags="Configuration Manage Sub-Categories"><i
                                class="fa-sharp fa-solid fa-list-check"></i>
                            <span class="nav-link-text" data-i18n="nav.Configuration_Manage_sub_categories">
                                Manage Sub-Categories </span>
                        </a>
                    </li>
                    <li id="nav_booking_status">
                        <a href="../booking-status/view-booking-status"
                            data-filter-tags="Configuration Booking Status"><i class="fa-solid fa-calendar-check"></i>
                            <span class="nav-link-text" data-i18n="nav.Booking_Status">
                                Booking Status</span>
                        </a>
                    </li>
                    <li id="nav_corporate_ticket_status">
                        <a href="../corporate-tickets-status/view-corporate-tickets-status"
                            data-filter-tags="Configuration Corporate Ticket Status"><i
                                class="fa-solid fa-calendar-check"></i>
                            <span class="nav-link-text" data-i18n="nav.corporate_ticket_status">
                                Corporate Ticket Status</span>
                        </a>
                    </li>
                    <li id="nav_ppm_ticket_status">
                        <a href="../ppm-tickets-status/view-ppm-tickets-status"
                            data-filter-tags="Configuration PPM Ticket Status"><i
                                class="fa-solid fa-calendar-check"></i>
                            <span class="nav-link-text" data-i18n="nav.ppm_ticket_status">
                                PPM Ticket Status</span>
                        </a>
                    </li>
                    <li id="nav_custoumer_rating">
                        <a href="../customer-rating/view-customer-rating"
                            data-filter-tags="Configuration Booking Status"><i class="fa-solid fa-star-half-stroke"></i>
                            <span class="nav-link-text" data-i18n="nav.custoumer_rating">
                                Customer Rating</span>
                        </a>
                    </li>
                    <li id="nav_leave_configuration">
                        <a href="../leave-configuration/view-leave-configuration"
                            data-filter-tags="Configuration Leave Configuration"><i class="fa-solid fa-user-tag"></i>
                            <span class="nav-link-text" data-i18n="nav.LeaveConfiguration">
                                Leave Configuration</span>
                        </a>
                    </li>
                    <li id="nav_list_of_holidays">
                        <a href="../list-of-holidays/view-holidays"
                            data-filter-tags="Configuration list of holiday "><i class="fa-solid fa-user-tag"></i>
                            <span class="nav-link-text" data-i18n="nav.ListOfHolidays">
                                List Of Holidays</span>
                        </a>
                    </li>
                    <li id="nav_department">
                        <a href="../department/view-department"
                            data-filter-tags="Configuration Department "><i class="fa-solid fa-user-tag"></i>
                            <span class="nav-link-text" data-i18n="nav.Department">
                                Department</span>
                        </a>
                    </li>
                    <li id="nav_arc">
                        <a href="../arc/view-arc" data-filter-tags="Company">
                            <i class="fa-solid fa-users"></i>
                            <span class="nav-link-text" data-i18n="nav.ARC Items">ARC Items</span>
                        </a>
                    </li>
                    <li id="nav_spare_part_list">
                        <a href="../spare-parts/view-spare-part"
                            data-filter-tags="Configuration list of holiday "><i class="fa-solid fa-user-tag"></i>
                            <span class="nav-link-text" data-i18n="nav.SparePartList">
                                Spare Part List</span>
                        </a>
                    </li>
                    <li id="nav_region">
                        <a href="../region/view-region" data-filter-tags="Configuration Region "><i
                                class="fa-solid fa-user-shield"></i>
                            <span class="nav-link-text" data-i18n="nav.Region">
                                Region </span>
                        </a>
                    </li>
                    <li id="nav_state">
                        <a href="../state/view-state" data-filter-tags="Configuration State "><i
                                class="fa-solid fa-user-shield"></i>
                            <span class="nav-l ink-text" data-i18n="nav.State">
                                State</span>
                        </a>
                    </li>
                    <li id="nav_city">
                        <a href="../city/view-city" data-filter-tags="City">
                            <i class="fa-solid fa-city"></i>
                            <span class="nav-link-text" data-i18n="nav.City">City</span>
                        </a>
                    </li>
                    <li id="nav_manage_tat_group">
                        <a href="../manage-tat-groups/view-tat-groups"
                            data-filter-tags="Configuration Manage TAT Groups"><i class="fa-sharp fa-solid fa-list-check"></i>
                            <span class="nav-link-text" data-i18n="nav.Configuration_Manage_UoM">
                                Manage TAT Groups</span>
                        </a>
                    </li>
                </ul>
            </li>
            <?php
            }
            ?>

             
            <?php
            if($_Nav_Corporate)
            {
            ?>
            <li id="nav_corporate">
                <a href="#" data-filter-tags="Corporate">
                    <i class="fa-solid fa-building"></i>
                    <span class="nav-link-text" data-i18n="nav.Corporate">Corporate</span>
                </a>
                <ul class="pl-3">
                    <li id="nav_main_corporate">
                        <a href="../corporate/view-corporate" data-filter-tags="Corporates">
                            <i class="fa-solid fa-users"></i>
                            <span class="nav-link-text" data-i18n="nav.Corporates">Companies</span>
                        </a>
                    </li>
                    <li id="nav_company">
                        <a href="../company/view-company?nav=1" data-filter-tags="Company">
                            <i class="fa-solid fa-users"></i>
                            <span class="nav-link-text" data-i18n="nav.Company">Company Accounts</span>
                        </a>
                    </li>
                    <li id="nav_branch">
                        <a href="../branch/view-branch?nav=1" data-filter-tags="Branch">
                            <i class="fa fa-building" aria-hidden="true"></i>
                            <span class="nav-link-text" data-i18n="nav.Branch">Company Account Branches</span>
                        </a>
                    </li>
                    <!--li id="nav_corporate_tickets">
                        <a href="../corporate-tickets/view-corporate-tickets"
                            data-filter-tags="Company">
                            <i class="fa-solid fa-users"></i>
                            <span class="nav-link-text" data-i18n="nav.Corporate Tickets">Corporate Tickets</span>
                        </a>
                    </li-->

                </ul>
            </li>
            <?php
            }
            if($_Nav_Corporate_dashboard)
            {
            ?>
            <li id="nav_dashboard">
                <a href="../dashboard/corporate_dashboard" data-filter-tags="Dashboard">
                    <i class="fa-solid fa-palette"></i>
                    <span class="nav-link-text" data-i18n="nav.Dashboard">Dashboard</span>
                </a>
            </li>
            <?php
            }
            if($_Nav_Corporate_Branches)
            {
                $nav_parameter = "?nav=1";
                if(isset($_SESSION['UserType']))
                {
                    if($_SESSION['UserType'] == "Corporate Admin" || $_SESSION['UserType'] == "Corporate Branch User" )
                    {
                        $nav_parameter = "";
                    }
                }
            ?>
            <li id="nav_branch">
                <a href="../branch/view-branch<?php echo $nav_parameter;?>" data-filter-tags="Branch">
                    <i class="fa fa-building" aria-hidden="true"></i>
                    <span class="nav-link-text" data-i18n="nav.Branch">Company Branches</span>
                </a>
            </li>

            <?php
            }
   
            if($_Nav_All_Assets)
            {
                
                if(!($_SESSION['UserType'] == "Corporate Branch User"))
                {
                    $nav_parameter = "?nav=1";
                }
                
            ?>
                <li id="nav_company_assets">
                    <a href="../branch-assets/view-branch-assets<?php echo $nav_parameter;?>" data-filter-tags="Branch">
                        <i class="fa fa-building" aria-hidden="true"></i>
                        <span class="nav-link-text" data-i18n="nav.Branch">Branch Assets</span>
                    </a>
                </li>
            <?php
            }
           
            if($_Nav_Corporate_Raise_Ticket || $_SESSION['pb_username'] == 'atul.sagar' || $_SESSION['pb_username'] == 'Kannan' || $_SESSION['pb_username'] == 'JLL-IDFC' || $_SESSION['pb_username'] == 'Vamshi' ||  $_SESSION['pb_username'] == 'V.Krishna')
            {
            ?>
            <li id="nav_raise_ticket">
                <a href="../corporate-tickets/add-ticket" data-filter-tags="Raise Ticket">
                    <i class="fa-solid fa-ticket"></i>
                    <span class="nav-link-text" data-i18n="nav.Raise Ticket">Raise Ticket</span>
                </a>
            </li>


            <?php
            }
            if($_Nav_Rate_Card)
            {
            ?>
            <li id="nav_ticket_rate_card">
                <a href="../company/view-rate-card.php?nav=1"
                    data-filter-tags="Approval">
                    <i class="fa-solid fa-ticket"></i>
                    <span class="nav-link-text" data-i18n="nav.Approval">Rate Card</span>
                </a>
            </li>
            <?php
            }
            if($_Nav_Corporate_quotation_approval)
            {
            ?>
            <li>
                <a href="../corporate-tickets/view-all-ticket-quotations"
                    data-filter-tags="Approval">
                    <i class="fa-solid fa-ticket"></i>
                    <span class="nav-link-text" data-i18n="nav.Approval">Quotations Received</span>
                </a>
            </li>
            <?php
            }
             if($_Nav_Corporate_Profile)
            {
            ?>
            <li id="nav_order">
                <a href="../order/view-order" data-filter-tags="Order">
                    <i class="fa fa-building" aria-hidden="true"></i>
                    <span class="nav-link-text" data-i18n="nav.Order">Orders</span>
                </a>
            </li>
            <?php
            }
            if($_Nav_Corporate_users)
            {
            ?>
            <li id="nav_corporate_user">
                <a href="../corporate-users/view-corporate-users"
                    data-filter-tags="Corporate Users">
                    <i class="fa fa-building" aria-hidden="true"></i>
                    <span class="nav-link-text" data-i18n="nav.Approval User">User Management</span>
                </a>
            </li>
            <?php
            }
            ?>
            <!-- <li id="nav_company">
                <a href="../company/view-company" data-filter-tags="Company">
                    <i class="fa-solid fa-users"></i>
                    <span class="nav-link-text" data-i18n="nav.Company">Company</span>
                </a>
            </li>
            <li id="nav_branch">
                <a href="../branch/view-branch" data-filter-tags="Branch">
                    <i class="fa-solid fa-users"></i>
                    <span class="nav-link-text" data-i18n="nav.Branch">Branch</span>
                </a>
            </li> -->
            <?php
            if($_Nav_Customer)
            {
            ?>
            <li id="nav_customer">
                <a href="../customer/view-customer-details" data-filter-tags="customer">
                    <i class="fa-solid fa-users"></i>
                    <span class="nav-link-text" data-i18n="nav.customer">Customer</span>
                </a>
            </li>
            <?php
            }
            ?>



            <?php
            if($_Nav_All_Order)
            {
            ?>
            <li id="nav_customer">
                <a href="../order/view-order" data-filter-tags="AllOrder">
                    <i class="fa-solid fa-ticket"></i>
                    <span class="nav-link-text" data-i18n="nav.customer">All ARC Orders</span>
                </a>
            </li>
            <?php
            }
            ?>
            

            <?php
            if($_Nav_Employee_Convenience)
            {
            ?>
            <li id="_Nav_Employee_Convenience">
                <a href="../employees-convenience/view-employees-convenience"
                    data-filter-tags="Employees Convenience">
                    <i class="fa-solid fa-users"></i>
                    <span class="nav-link-text" data-i18n="nav.EmployeesConvenience">Employees Convenience</span>
                </a>
            </li>
            <?php
            }
            ?>

            <?php
            if($_Nav_Corporate_Audit)
            {
            ?>
            <li id="nav_corporate_audit">
                <a href="#" data-filter-tags="Corporate Audit">
                    <i class="fa-solid fa-clipboard-check"></i>
                    <span class="nav-link-text">Corporate Audit</span>
                </a>
                <ul class="pl-3">
                    <li id="nav_corporate_audit_master">
                        <a href="../corporate-audit/view-master-audit">
                            <i class="fa-solid fa-list"></i>
                            <span class="nav-link-text">Master Audit</span>
                        </a>
                    </li>
                    <li id="nav_corporate_audit_sub">
                        <a href="../corporate-audit/view-master-sub-audit">
                            <i class="fa-solid fa-sitemap"></i>
                            <span class="nav-link-text">Master Sub Audit</span>
                        </a>
                    </li>
                    <li id="nav_corporate_audit_checklist">
                        <a href="../corporate-audit/view-master-checklist">
                            <i class="fa-solid fa-tasks"></i>
                            <span class="nav-link-text">Master Checklist</span>
                        </a>
                    </li>
                </ul>
            </li>
            <?php
            }
            ?>

            <?php
            if($_Nav_Corporate_Audit_Portal)
            {
            ?>
            <li id="nav_corporate_audit_portal">
                <a href="../corporate-audit/view-audit-portal"
                    data-filter-tags="Corporate Audit Portal">
                    <i class="fa-solid fa-magnifying-glass-chart"></i>
                    <span class="nav-link-text">Corporate Audit</span>
                </a>
            </li>
            <?php
            }
            ?>

            <?php
            if($_Nav_Audit_Tickets)
            {
            ?>
            <li id="nav_audit_tickets" class="nav-item">
                <a href="#" data-filter-tags="Audit Tickets">
                    <i class="fa-solid fa-ticket"></i>
                    <span class="nav-link-text">Audit Tickets</span>
                </a>
                <ul class="pl-3">
                    <li id="nav_audit_tickets_list">
                        <a href="../audit-ticket/view-audit-tickets">
                            <i class="fa-solid fa-list"></i>
                            <span class="nav-link-text">Ticket List</span>
                        </a>
                    </li>
                    <li id="nav_audit_tickets_raise">
                        <a href="../audit-ticket/view-raise-audit-ticket">
                            <i class="fa-solid fa-plus-circle"></i>
                            <span class="nav-link-text">Raise Ticket</span>
                        </a>
                    </li>
                </ul>
            </li>
            <?php
            }
            ?>

            <?php
            if($_Nav_Corporate_Configuration)
            {
            ?>
            <li id="_Nav_Corporate_Configuration">
                <a href="../configuration/view-corporate-configuration"
                    data-filter-tags="Corporate Configuration">
                    <i class="fa-solid fa-gear"></i>
                    <span class="nav-link-text" data-i18n="nav.CorporateConfiguration">Configuration</span>
                </a>
            </li>
            <?php
            }
            ?>
            <?php
            if($_Nav_My_Profile)
            {
            ?>
            <li id="nav_my_profile">
                <a href="../employees/view_profile_details"
                    data-filter-tags="Profile">
                    <i class="fa-solid fa-user"></i>
                    <span class="nav-link-text" data-i18n="nav.CorporateConfiguration">Profile</span>
                </a>
            </li>


             <li id="nav_emp_attendance">
                <a href="../attendance-list/emp-attendance"
                    data-filter-tags="Attendance">
                    <i class="fa-solid fa-calendar-check"></i>
                    <span class="nav-link-text" data-i18n="nav.EmployeeAttendance">Attendance</span>
                </a>
            </li>
            <?php if ($_Nav_My_Employee_Leave) { ?>
            <li id="nav_my_employee_leave">
                <a href="../employee-leave-mgmt/view-my-leaves"
                    data-filter-tags="leave apply balance">
                    <i class="fa-solid fa-calendar-minus"></i>
                    <span class="nav-link-text">My Leave</span>
                </a>
            </li>
            <?php } ?>
            <?php if ($_Nav_My_HR_Tickets) { ?>
            <li id="nav_my_hr_tickets">
                <a href="../hr-tickets/view-my-hr-tickets"
                    data-filter-tags="hr tickets my concerns">
                    <i class="fa-solid fa-headset"></i>
                    <span class="nav-link-text">My HR Tickets</span>
                </a>
            </li>
            <?php } ?>
            <?php
            }
            ?>

            <?php
            if($_Nav_Ticket_Billing)
            {
            ?>
            <li id="nav_ticket_billing">
                <a href="../tickets-billing/view-ticket-billing" data-filter-tags="Ticket Billing">
                    <i class="fa-solid fa-ticket"></i>
                    <span class="nav-link-text" data-i18n="nav.TicketBilling">Ticket Billing</span>
                </a>
            </li>
            <?php
            }
            ?>

        </ul>
        <div class="filter-message js-filter-message bg-success-600"></div>
    </nav>
</aside>
