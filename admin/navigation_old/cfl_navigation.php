<style type="text/css">

    .nav-footer {

    height: unset!important;

}
.nav-menu li > ul {
    background-color: #003f88!important;
    padding-top: 10px;
    padding-bottom: 10px;
}
.nav-footer{
    padding-top: 0px!important; 
    margin-top: 0px!important; 
    background: none;
}
.nav-footer {
    padding-top: 0px!important;
    margin-top: 0px!important;
    background:#c00!important;
}

</style>
<aside class="page-sidebar" style=" background-color: #003f88!important; ">
    <div class="page-logo">
        <a href="#" class="page-logo-link press-scale-down d-flex align-items-center position-relative" data-toggle="modal" data-target="#modal-shortcut">
            <img src="../img/Chola-Logo.png" alt="SmartAdmin WebApp" aria-roledescription="logo">
            <span class="page-logo-text mr-1">CholaMandalam</span>
            <span class="position-absolute text-white opacity-50 small pos-top pos-right mr-2 mt-n2"></span>
            <i class="fal fa-angle-down d-inline-block ml-1 fs-lg color-primary-300"></i>
        </a>
    </div>
    <!-- BEGIN PRIMARY NAVIGATION -->
    <nav id="js-primary-nav" class="primary-nav" role="navigation">
        <div class="nav-filter">
            <div class="position-relative">
                <input type="text" id="nav_filter_input" placeholder="Filter menu" class="form-control" tabindex="0">
                <a href="#" onclick="return false;" class="btn-primary btn-search-close js-waves-off" data-action="toggle" data-class="list-filter-active" data-target=".page-sidebar">
                    <i class="fal fa-chevron-up"></i>
                </a>
            </div>
        </div>
        <div class="info-card">
            <img src="../img/demo/avatars/demo-avatar.png" class="profile-image rounded-circle" alt="Cholamandalam Admin">
            <div class="info-card-text">
                <a href="#" class="d-flex align-items-center text-white">
                    <span class="text-truncate text-truncate-sm d-inline-block">
                        <?php echo $_SESSION['pb_username']; ?>  
                    </span>
                </a>
                <span class="d-inline-block text-truncate text-truncate-sm"><?php echo $_SESSION['UserType']; ?></span>
            </div>
            <img src="../img/card-backgrounds/ngo.jpg" class="cover" alt="cover">
            <a href="#" onclick="return false;" class="pull-trigger-btn" data-action="toggle" data-class="list-filter-active" data-target=".page-sidebar" data-focus="nav_filter_input">
                <i class="fal fa-angle-down"></i>
            </a>
        </div>
        <ul id="js-nav-menu" class="nav-menu">
            <li>
                <a href="../dashboard/admin_dashboard" title="info" data-filter-tags="info">
                    <i class="fal fa-info-circle"></i>
                    <span class="nav-link-text" data-i18n="nav.application_intel">Dashboard</span>
                </a>
                
            </li>
           
            <li>
                <a href="../blocks/view-blocks" title="Datatables" data-filter-tags="datatables datagrid">
                    <i class="fal fa-table"></i>
                    <span class="nav-link-text" data-i18n="nav.datatables">Block</span>
                    
                </a>
                
            </li>
            <li>
                <a href="../village/view-village" title="Datatables" data-filter-tags="datatables datagrid">
                    <i class="fal fa-list-alt"></i>
                    <span class="nav-link-text" data-i18n="nav.datatables">Villages</span>
                    
                </a>
               
            </li>
            <li>
                <a href="../cfl/view-cfl" title="Datatables" data-filter-tags="datatables datagrid">
                    <i class="fal fa-list-alt"></i>
                    <span class="nav-link-text" data-i18n="nav.datatables">CFL</span>
                    
                </a>
                
            </li>
            <li>
                <a href="../truck/view-truck" title="Datatables" data-filter-tags="datatables datagrid">
                    <i class="fal fa-truck"></i>
                    <span class="nav-link-text" data-i18n="nav.datatables">Truck Design</span>
                    
                </a>
                
            </li>
            <li>
                <a href="../stories/view-stories" title="Datatables" data-filter-tags="datatables datagrid">
                    <i class="fal fa-book"></i>
                    <span class="nav-link-text" data-i18n="nav.datatables">User Stories</span>
                    
                </a>
                
            </li>
            <li>
                <a href="../banking_and_insurance_information/view-banking_insurance_info" title="Datatables" data-filter-tags="datatables datagrid">
                    <i class="fal fa-university"></i>
                    <span class="nav-link-text" data-i18n="nav.datatables">Banking  Insurance Information</span>
                    
                </a>
                
            </li>
            <li>
                <a href="../poem/view-poem" title="Datatables" data-filter-tags="datatables datagrid">
                    <i class="fal fa-book"></i>
                    <span class="nav-link-text" data-i18n="nav.datatables">Poem</span>
                    
                </a>
                
            </li>
            <li>
                <a href="../womentemployment_ideas/view-employment_ideas" title="Datatables" data-filter-tags="datatables datagrid">
                    <i class="fal fa-user"></i>
                    <span class="nav-link-text" data-i18n="nav.datatables">Women Employment Ideas</span>
                    
                </a>
                
            </li>
            <li>
                <a href="../schema/view-schema" title="Datatables" data-filter-tags="datatables datagrid">
                    <i class="fal fa-list"></i>
                    <span class="nav-link-text" data-i18n="nav.datatables">Schemes</span>
                    
                </a>
                
            </li>
           <li>
                <a href="../UserRoles/view-roles" title="Datatables" data-filter-tags="datatables datagrid">
                    <i class="fal fa-user"></i>
                    <span class="nav-link-text" data-i18n="nav.datatables">User Role</span>
                    
                </a>
                
            </li>
            <li>
                <a href="../training/view-training" title="Datatables" data-filter-tags="datatables datagrid">
                    <i class="fal fa-database"></i>
                    <span class="nav-link-text" data-i18n="nav.datatables">Data</span>
                    
                </a>
                
            </li>
            <li>
                <a href="../dashboard/impact_dashboard" title="Datatables" data-filter-tags="datatables datagrid">
                    <i class="fal fa-database"></i>
                    <span class="nav-link-text" data-i18n="nav.datatables">Performance and Impact</span>
                    
                </a>
                
            </li>
        </ul>
        <div class="filter-message js-filter-message bg-success-600"></div>
    </nav>
    <!-- END PRIMARY NAVIGATION -->
    <!-- NAV FOOTER -->
</aside>