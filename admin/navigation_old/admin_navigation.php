<?php 
$logoImg = "tech-logo.jpg";
$navigation_bg = "#0581c1";
if(isset($product_configuration['logo']))
{
    $logoImg = $product_configuration['logo'];
    $navigation_bg = $product_configuration['primary_color'];
}
?>
<style type="text/css">
.nav-footer {
    height: unset !important;
}

.nav-menu li>ul {
    background-color: #003f88 !important;
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
    background: #c00 !important;
}

.nav-menu li a b {
    color: #fff !important;
    font-weight: 900 !important;
    font-size: 19px !important;
}
</style>
<aside class="page-sidebar" style=" background-color: <?=$navigation_bg;?> !important; ">
    <div class="page-logo">
        <a href="#" class="page-logo-link press-scale-down d-flex align-items-center position-relative"
            data-toggle="modal" data-target="#modal-shortcut">
            <?php 
            if($logoImg == "tech-logo.jpg")
            {
            ?>
                <img width="112px" style="height:35px;" src="../img/<?=$logoImg;?>"  aria-roledescription="logo">
            <?php
            }
            else
            {
                ?>
                <img width="112px" style="height:66px;" src="../img/<?=$logoImg;?>"  aria-roledescription="logo">
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
            <img src="../img/card-backgrounds/ngo-new.png" class="cover" alt="cover">
            <!--<a href="#" onclick="return false;" class="pull-trigger-btn" data-action="toggle" data-class="list-filter-active" data-target=".page-sidebar" data-focus="nav_filter_input">
                <i class="fal fa-angle-down"></i>-->
            </a>
        </div>
        <ul id="js-nav-menu" class="nav-menu">
            <?php
            if($_Nav_Dashboard)
            {
            ?>
            <li id="nav_dashboard">
                <a href="../dashboard/admin_dashboard" title="Dashboard" data-filter-tags="Dashboard">
                    <i class="fa-solid fa-palette"></i>
                    <span class="nav-link-text" data-i18n="nav.Dashboard">Dashboard</span>
                </a>
            </li>
            <?php
            }
            if($_Nav_Analytics_Dashboard)
            {
            ?>
            <li id="nav_analytics_dashboard">
                <a href="../dashboard/analytics_dashboard" title="Analytics Dashboard" data-filter-tags="Dashboard">
                    <i class="fal fa-chart-pie"></i>
                    <span class="nav-link-text" data-i18n="nav.Dashboard">Analytics Dashboard</span>
                </a>
            </li>
            <?php
            }

             if($_Nav_My_KPI)
                {
                ?>
                    <li id="nav_my_kpi_dashboard">
                        <a href="../dashboard/my_kpi_dashboard" title="My KPI" data-filter-tags="My KPI">
                            <i class="fa-solid fa-chart-line"></i>
                            <span class="nav-link-text" data-i18n="nav.My_KPI">My KPI</span>
                        </a>
                    </li>
                <?php
                }
                
            if($_Nav_Accounts_Dashboard)
            {
            ?>
            <li id="nav_account_dashboard">
                <a href="../dashboard/accounts_dashboard" title="Account Wise Dashboard" data-filter-tags="Dashboard">
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
                <a href="../dashboard/daily_tracker_dashboard" title="Daily Tracker Dashboard" data-filter-tags="Daily Tracker Dashboard">
                    <i class="fa-solid fa-list"></i>
                    <span class="nav-link-text" data-i18n="nav.Dashboard">Daily Tracker</span>
                </a>
            </li>
            <?php
            }
            if($_Nav_Corporate_Tickets || $_Nav_Corporate)
            {
            ?>
            <li id="nav_corporate_tickets">
                <a href="../corporate-tickets/view-corporate-tickets" title="Corporate Tickets"
                    data-filter-tags="Company">
                    <i class="fa-solid fa-users"></i>
                    <span class="nav-link-text" data-i18n="nav.Corporate Tickets">Corporate Tickets</span>
                </a>
            </li>
            <?php
            }
            if($_Nav_PPM_Tickets)
            {
            ?>
            <li id="nav_ppm_tickets">
                <a href="../ppm-ticket/view-all-ppm-tickets" title="Corporate PPM Tickets" data-filter-tags="Company">
                    <i class="fal fa-warehouse"></i>
                    <span class="nav-link-text" data-i18n="nav.PPMTickets">PPM Tickets</span>
                </a>
            </li>
            <?php
            }
            if($_Nav_PPM_SC)
            {
            ?>
            <li id="nav_ppm_billing">
                <a href="../PPMBilling/view-ppm-billing" title="PPM Billing" data-filter-tags="Billing PPM Invoice">
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
                <a href="../mail-scheduler/view-mail-scheduler" title="Mail Scheduler" data-filter-tags="Mail Scheduler">
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
                <a href="#" title="Services" data-filter-tags="Fianance">
                    <i class="fa-solid fa-users"></i>
                    <span class="nav-link-text" data-i18n="nav.Fianance">Finance</span>
                </a>
                <ul class="pl-3">
                    <li id="nav_corporate_tickets_finance">
                        <a href="../corporate-tickets/view-corporate-tickets-finance" title="Tickets Finance" data-filter-tags="Tickets Finance">
                            <i class="fa-solid fa-calendar-check"></i>
                            <span class="nav-link-text" data-i18n="nav.Bookings">Tickets Finance</span>
                        </a>
                    </li>
                    <li id="nav_ticket_quotations">
                        <a href="../corporate-tickets/view-all-ticket-quotations" title="Quotations" data-filter-tags="Quotations">
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
                <a href="../site_visits/view-all-site-visits" title="Site Visits" data-filter-tags="Company">
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
                <a href="../corporate-tickets/view-account-tickets" title="Account Tickets" data-filter-tags="Company">
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
                <a href="../corporate-tickets/view-account-branch-tickets" title="Branch Account Tickets"
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
                <a href="#" title="Services" data-filter-tags="Services">
                    <i class="fa-solid fa-calendar-check"></i>
                    <span class="nav-link-text" data-i18n="nav.Services">Bookings</span>
                </a>
                <ul class="pl-3">
                    <li id="nav_techxpert_bookings">
                        <a href="../booking/view_booking" title="Aryadibusiness Bookings" data-filter-tags="Bookings">
                            <i class="fa-solid fa-calendar-check"></i>
                            <span class="nav-link-text" data-i18n="nav.Bookings">Aryadibusiness Bookings</span>
                        </a>
                    </li>
                    <li id="nav_goodlife_bookings">
                        <a href="../booking/view-goodlife-booking" title="Goodlife Bookings" data-filter-tags="Bookings">
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
                <a href="../project-management/view-projects" title="Projects" data-filter-tags="Projects">
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
                <a href="../employees/view-employees" title="Employees" data-filter-tags="Employees">
                    <i class="fa-solid fa-users"></i>
                    <span class="nav-link-text" data-i18n="nav.Employees">Employees</span>
                </a>
            </li>
            <?php
            }
            if($_Nav_Employees_details)
            {
            ?>
            <li id="nav_employees_details">
                <a href="../employees-details/view-employees-detail" title="Employees Detail"
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
                <a href="../attendance-list/view-attendance-list.php" title="Attendance List"
                    data-filter-tags="attendance">
                    <i class="fa-solid fa-users"></i>
                    <span class="nav-link-text" data-i18n="nav.attendance">Employees Attendance</span>
                </a>
            </li>
            <?php
            }
            
            if($_Nav_Services)
            {
            ?>
            <li id="nav_services">
                <a href="#" title="Services" data-filter-tags="Services">
                    <i class="fa-solid fa-gears"></i>
                    <span class="nav-link-text" data-i18n="nav.Services">Services</span>
                </a>
                <ul class="pl-3">
                    <li id="main_services">
                        <a href="../Services/view-services" title="Services" data-filter-tags="Services">
                            <i class="fa-solid fa-screwdriver-wrench"></i>
                            <span class="nav-link-text" data-i18n="nav.Services">Main Services</span>
                        </a>
                    </li>
                    <!-- <li id="nav_location_services">
                        <a href="../location-service/view_location_service" title="Location Services"
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
                <a href="#" title="Site Setting" data-filter-tags="SiteSetting">
                    <i class="fa-solid fa-globe"></i>
                    <span class="nav-link-text" data-i18n="nav.SiteSetting">Site Setting</span>
                </a>
                <ul class="pl-3">
                    <li id="nav_testimonal">
                        <a href="../testimonials/view-testimonials" title="Testimonials"
                            data-filter-tags="Testimonials">
                            <i class="fa-solid fa-hand-holding-heart"></i>
                            <span class="nav-link-text" data-i18n="nav.Testimonials">Testimonials</span>
                        </a>
                    </li>
                    <!-- <li id="nav_review">
                        <a href="../reviews/view-reviews" title="Reviews" data-filter-tags="Reviews">
                            <i class="fa-solid fa-square-pen"></i>
                            <span class="nav-link-text" data-i18n="nav.Reviews">Reviews</span>
                        </a>
                    </li>
                    <li id="nav_banners">
                        <a href="../banners/view-banners" title="Banners" data-filter-tags="Banners">
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
                <a href="#" title="Contact" data-filter-tags="Contact">
                    <i class="fa-solid fa-address-book"></i>
                    <span class="nav-link-text" data-i18n="nav.Contact">Contact</span>
                </a>
                <ul class="pl-3">
                    <li id="nav_resume">
                        <a href="../resume/view_resume" title="Resumes" data-filter-tags="Resumes">
                            <i class="fa-solid fa-file"></i>
                            <span class="nav-link-text" data-i18n="nav.Resumes">Resumes</span>
                        </a>
                    </li>
                    <li id="nav_enquiries">
                        <a href="../enquiry/view_enquiry" title="Enquiries" data-filter-tags="Enquiries">
                            <i class="fa-sharp fa-solid fa-book"></i>
                            <span class="nav-link-text" data-i18n="nav.Enquiries">Contact Enquiries</span>
                        </a>
                    </li>
                    <li id="nav_quote_enquiries">
                        <a href="../get-quote/view-get-quote-enquiry" title="get-quote" data-filter-tags="get-quote">
                            <i class="fa-sharp fa-solid fa-book"></i>
                            <span class="nav-link-text" data-i18n="nav.get-quote">Quote Enquiries</span>
                        </a>
                    </li>
                    <li id="nav_join_us_enquiries">
                        <a href="../join-us/view-join-us-enquiry" title="get-quote" data-filter-tags="get-join-us">
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
                <a href="#" title="Configuration" data-filter-tags="Configuration">
                    <i class="fa-solid fa-pen-to-square"></i>
                    <span class="nav-link-text" data-i18n="nav.Configuration">Configuration</span>
                </a>
                <ul class="pl-3">
                    
                    <li id="nav_manage_CuopunCode">
                    <a href="../couponcode/view-all-cuopuncode" title="Manage Coupon Code"
                       data-filter-tags="Configuration Manage Coupon Code">
                        <i class="fa-solid fa-tags"></i>
                        <span class="nav-link-text" data-i18n="nav.Configuration_Manage_CouponCode">
                            Manage Coupon Code
                        </span>
                    </a>
                </li>

                    <li id="nav_manage_site">
                        <a href="../manage-site/view_site" title="Manage Sites"
                            data-filter-tags="Configuration Manage Sites"><i
                                class="fa-sharp fa-solid fa-list-check"></i>
                            <span class="nav-link-text" data-i18n="nav.Configuration_Manage_Sites">
                                Manage Sites</span>
                        </a>
                    </li>
                    <li id="nav_manage_uom">
                        <a href="../manage-uom/view-uom" title="Manage UoM"
                            data-filter-tags="Configuration Manage UoM"><i class="fa-sharp fa-solid fa-list-check"></i>
                            <span class="nav-link-text" data-i18n="nav.Configuration_Manage_UoM">
                                Manage UoM</span>
                        </a>
                    </li>
                    <!-- <li id="nav_manage_equipment">
                        <a href="../manage-equipment/view-equipment" title="Manage Equipment"
                            data-filter-tags="Configuration Manage Equipment"><i
                                class="fa-sharp fa-solid fa-list-check"></i>
                            <span class="nav-link-text" data-i18n="nav.Configuration_Manage_equipment">
                                Manage Equipment </span>
                        </a>
                    </li> -->
                    <li id="nav_manage_categories">
                        <a href="../manage-categories/view-categories" title="Manage Categories"
                            data-filter-tags="Configuration Manage Categories"><i
                                class="fa-sharp fa-solid fa-list-check"></i>
                            <span class="nav-link-text" data-i18n="nav.Configuration_Manage_categories">
                                Manage Categories </span>
                        </a>
                    </li>
                    <li id="nav_manage_sub_categories">
                        <a href="../manage-sub-categories/view-sub-categories" title="Manage Sub-Categories"
                            data-filter-tags="Configuration Manage Sub-Categories"><i
                                class="fa-sharp fa-solid fa-list-check"></i>
                            <span class="nav-link-text" data-i18n="nav.Configuration_Manage_sub_categories">
                                Manage Sub-Categories </span>
                        </a>
                    </li>
                    <li id="nav_booking_status">
                        <a href="../booking-status/view-booking-status" title="Booking Status"
                            data-filter-tags="Configuration Booking Status"><i class="fa-solid fa-calendar-check"></i>
                            <span class="nav-link-text" data-i18n="nav.Booking_Status">
                                Booking Status</span>
                        </a>
                    </li>
                    <li id="nav_corporate_ticket_status">
                        <a href="../corporate-tickets-status/view-corporate-tickets-status"
                            title="Corporate Ticket Status" data-filter-tags="Configuration Corporate Ticket Status"><i
                                class="fa-solid fa-calendar-check"></i>
                            <span class="nav-link-text" data-i18n="nav.corporate_ticket_status">
                                Corporate Ticket Status</span>
                        </a>
                    </li>
                    <li id="nav_ppm_ticket_status">
                        <a href="../ppm-tickets-status/view-ppm-tickets-status" title="PPM Ticket Status"
                            data-filter-tags="Configuration PPM Ticket Status"><i
                                class="fa-solid fa-calendar-check"></i>
                            <span class="nav-link-text" data-i18n="nav.ppm_ticket_status">
                                PPM Ticket Status</span>
                        </a>
                    </li>
                    <li id="nav_custoumer_rating">
                        <a href="../customer-rating/view-customer-rating" title="Customer Rating"
                            data-filter-tags="Configuration Booking Status"><i class="fa-solid fa-star-half-stroke"></i>
                            <span class="nav-link-text" data-i18n="nav.custoumer_rating">
                                Customer Rating</span>
                        </a>
                    </li>
                    <li id="nav_leave_configuration">
                        <a href="../leave-configuration/view-leave-configuration" title="Leave Configuration"
                            data-filter-tags="Configuration Leave Configuration"><i class="fa-solid fa-user-tag"></i>
                            <span class="nav-link-text" data-i18n="nav.LeaveConfiguration">
                                Leave Configuration</span>
                        </a>
                    </li>
                    <li id="nav_list_of_holidays">
                        <a href="../list-of-holidays/view-holidays" title="list of holidays"
                            data-filter-tags="Configuration list of holiday "><i class="fa-solid fa-user-tag"></i>
                            <span class="nav-link-text" data-i18n="nav.ListOfHolidays">
                                List Of Holidays</span>
                        </a>
                    </li>
                    <li id="nav_department">
                        <a href="../department/view-department" title="Department"
                            data-filter-tags="Configuration Department "><i class="fa-solid fa-user-tag"></i>
                            <span class="nav-link-text" data-i18n="nav.Department">
                                Department</span>
                        </a>
                    </li>
                    <li id="nav_arc">
                        <a href="../arc/view-arc" title="ARC Items" data-filter-tags="Company">
                            <i class="fa-solid fa-users"></i>
                            <span class="nav-link-text" data-i18n="nav.ARC Items">ARC Items</span>
                        </a>
                    </li>
                    <li id="nav_spare_part_list">
                        <a href="../spare-parts/view-spare-part" title="Spare Part List"
                            data-filter-tags="Configuration list of holiday "><i class="fa-solid fa-user-tag"></i>
                            <span class="nav-link-text" data-i18n="nav.SparePartList">
                                Spare Part List</span>
                        </a>
                    </li>
                    <li id="nav_region">
                        <a href="../region/view-region" title="Region" data-filter-tags="Configuration Region "><i
                                class="fa-solid fa-user-shield"></i>
                            <span class="nav-link-text" data-i18n="nav.Region">
                                Region </span>
                        </a>
                    </li>
                    <li id="nav_state">
                        <a href="../state/view-state" title="State " data-filter-tags="Configuration State "><i
                                class="fa-solid fa-user-shield"></i>
                            <span class="nav-l ink-text" data-i18n="nav.State">
                                State</span>
                        </a>
                    </li>
                    <li id="nav_city">
                        <a href="../city/view-city" title="City" data-filter-tags="City">
                            <i class="fa-solid fa-city"></i>
                            <span class="nav-link-text" data-i18n="nav.City">City</span>
                        </a>
                    </li>
                    <li id="nav_manage_tat_group">
                        <a href="../manage-tat-groups/view-tat-groups" title="Manage TAT Groups"
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
                <a href="#" title="Corporate" data-filter-tags="Corporate">
                    <i class="fa-solid fa-building"></i>
                    <span class="nav-link-text" data-i18n="nav.Corporate">Corporate</span>
                </a>
                <ul class="pl-3">
                    <li id="nav_main_corporate">
                        <a href="../corporate/view-corporate" title="Companies" data-filter-tags="Corporates">
                            <i class="fa-solid fa-users"></i>
                            <span class="nav-link-text" data-i18n="nav.Corporates">Companies</span>
                        </a>
                    </li>
                    <li id="nav_company">
                        <a href="../company/view-company?nav=1" title="Company" data-filter-tags="Company">
                            <i class="fa-solid fa-users"></i>
                            <span class="nav-link-text" data-i18n="nav.Company">Company Accounts</span>
                        </a>
                    </li>
                    <li id="nav_branch">
                        <a href="../branch/view-branch?nav=1" title="Branch" data-filter-tags="Branch">
                            <i class="fa fa-building" aria-hidden="true"></i>
                            <span class="nav-link-text" data-i18n="nav.Branch">Company Account Branches</span>
                        </a>
                    </li>
                    <!--li id="nav_corporate_tickets">
                        <a href="../corporate-tickets/view-corporate-tickets" title="Corporate Tickets"
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
                <a href="../dashboard/corporate_dashboard" title="Dashboard" data-filter-tags="Dashboard">
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
                <a href="../branch/view-branch<?php echo $nav_parameter;?>" title="Branch" data-filter-tags="Branch">
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
                    <a href="../branch-assets/view-branch-assets<?php echo $nav_parameter;?>" title="Branch" data-filter-tags="Branch">
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
                <a href="../corporate-tickets/add-ticket" title="Raise Ticket" data-filter-tags="Raise Ticket">
                    <i class="fa-solid fa-ticket"></i>
                    <span class="nav-link-text" data-i18n="nav.Raise Ticket">Raise Ticket</span>
                </a>
            </li>


            <?php
            }
            if($_Nav_Corporate_approval)
            {
            ?>
            <!--li id="nav_ticket_approval">
                <a href="../approval-pending/view-approval-pending-tickets" title="Approval"
                    data-filter-tags="Approval">
                    <i class="fa-solid fa-ticket"></i>
                    <span class="nav-link-text" data-i18n="nav.Approval">Ticket Approvals</span>
                </a>
            </li-->
            <?php
            }
            if($_Nav_Rate_Card)
            {
            ?>
            <li id="nav_ticket_rate_card">
                <a href="../company/view-rate-card.php?nav=1" title="Rate Card"
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
                <a href="../corporate-tickets/view-all-ticket-quotations" title="Ticket Quotations"
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
                <a href="../order/view-order" title="Order" data-filter-tags="Order">
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
                <a href="../corporate-users/view-corporate-users" title="Corporate Users"
                    data-filter-tags="Corporate Users">
                    <i class="fa fa-building" aria-hidden="true"></i>
                    <span class="nav-link-text" data-i18n="nav.Approval User">User Management</span>
                </a>
            </li>
            <?php
            }
            ?>
            <!-- <li id="nav_company">
                <a href="../company/view-company" title="Company" data-filter-tags="Company">
                    <i class="fa-solid fa-users"></i>
                    <span class="nav-link-text" data-i18n="nav.Company">Company</span>
                </a>
            </li>
            <li id="nav_branch">
                <a href="../branch/view-branch" title="Branch" data-filter-tags="Branch">
                    <i class="fa-solid fa-users"></i>
                    <span class="nav-link-text" data-i18n="nav.Branch">Branch</span>
                </a>
            </li> -->
            <?php
            if($_Nav_Customer)
            {
            ?>
            <li id="nav_customer">
                <a href="../customer/view-customer-details" title="customer" data-filter-tags="customer">
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
                <a href="../order/view-order" title="AllOrder" data-filter-tags="AllOrder">
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
                <a href="../employees-convenience/view-employees-convenience" title="Employees Convenience"
                    data-filter-tags="Employees Convenience">
                    <i class="fa-solid fa-users"></i>
                    <span class="nav-link-text" data-i18n="nav.EmployeesConvenience">Employees Convenience</span>
                </a>
            </li>
            <?php
            }
            ?>

            <?php
            if($_Nav_Corporate_Configuration)
            {
            ?>
            <li id="_Nav_Corporate_Configuration">
                <a href="../configuration/view-corporate-configuration" title="Corporate Configuration"
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
                <a href="../employees/view_profile_details" title="Profile"
                    data-filter-tags="Profile">
                    <i class="fa-solid fa-user"></i>
                    <span class="nav-link-text" data-i18n="nav.CorporateConfiguration">Profile</span>
                </a>
            </li>


             <li id="nav_emp_attendance">
                <a href="../attendance-list/emp-attendance" title="Attendance"
                    data-filter-tags="Attendance">
                    <i class="fa-solid fa-calendar-check"></i>
                    <span class="nav-link-text" data-i18n="nav.EmployeeAttendance">Attendance</span>
                </a>
            </li>
            <?php
            }
            ?>

            

        </ul>
        <div class="filter-message js-filter-message bg-success-600"></div>
    </nav>
</aside>