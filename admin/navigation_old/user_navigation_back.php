<aside class="page-sidebar">
    <div class="page-logo">
        <a href="#" class="page-logo-link press-scale-down d-flex align-items-center position-relative" data-toggle="modal" data-target="#modal-shortcut">
          <!--   <img src="../img/logo.png" alt="Novologic" aria-roledescription="logo"> -->
            <i class="fal fa-cube" style="color:white;font-size:21px;"></i>
            <span class="page-logo-text mr-1">Novologic</span>
            <span class="position-absolute text-white opacity-50 small pos-top pos-right mr-2 mt-n2"></span>
            <i class="fal fa-angle-down d-inline-block ml-1 fs-lg color-primary-300"></i>
        </a>
    </div>
    <!-- BEGIN PRIMARY NAVIGATION -->
    <div class="slimScrollDiv" style="position: relative; overflow: hidden; width: auto; height: 100%;"><nav id="js-primary-nav" class="primary-nav js-list-filter" role="navigation" style="overflow: hidden; width: auto; height: 100%;">
        <div class="nav-filter">
            <div class="position-relative">
                <input type="text" id="nav_filter_input" placeholder="Filter menu" class="form-control" tabindex="0">
                <a href="#" onclick="return false;" class="btn-primary btn-search-close js-waves-off" data-action="toggle" data-class="list-filter-active" data-target=".page-sidebar">
                    <i class="fal fa-chevron-up"></i>
                </a>
            </div>
        </div>
        <div class="info-card">
            <img src="<?php echo $_SESSION['ProfileImage'];?>" class="profile-image rounded-circle" alt="Novologic Admin">
            <div class="info-card-text">
                <a href="#" class="d-flex align-items-center text-white">
                    <span class="text-truncate text-truncate-sm d-inline-block">
                        <?php echo $_SESSION['pb_username']; ?>  
                    </span>
                </a>
                <span class="d-inline-block text-truncate text-truncate-sm"><?php echo $_SESSION['UserType']; ?></span>
            </div>
            <img src="../img/card-backgrounds/novologic.jpeg" class="cover" alt="cover">
            <a href="#" onclick="return false;" class="pull-trigger-btn" data-action="toggle" data-class="list-filter-active" data-target=".page-sidebar" data-focus="nav_filter_input">
                <i class="fal fa-angle-down"></i>
            </a>
        </div>
        <ul class="nav-menu">
            <!--li id="nav_dashboard">
                <a href="../dashboard/user_dashboard.php" title="User Home" data-filter-tags="application intel" class=" waves-effect waves-themed">
                    <i class="fal fa-chart-pie"></i>
                    <span class="nav-link-text" data-i18n="nav.application_intel">Home</span>
                </a>
            </li-->
            <li id="nav_enterprise">
                <a href="../enterprises/view_enterprises" title="Enterprises" data-filter-tags="theme settings" class=" waves-effect waves-themed">
                    <i class="fal fa-th-list"></i>
                    <span class="nav-link-text" data-i18n="nav.theme_settings">Enterprises</span>
                </a>
              
            </li>
            <li id="nav_projects">
                <a href="../projects/view_user_projects" title="Projects" data-filter-tags="theme settings" class=" waves-effect waves-themed">
                    <i class="fal fa-table"></i>
                    <span class="nav-link-text" data-i18n="nav.theme_settings">Projects</span>
                </a>
              
            </li>
            <!--li id="nav_users">
                <a href="../users/user_profile.php" title="User Profile" data-filter-tags="package info" class=" waves-effect waves-themed">
                    <i class="fal fa-user"></i>
                    <span class="nav-link-text" data-i18n="nav.package_info">Users</span>
                </a>
               
            </li>
            <li id="nav_settings">
                <a href="../settings/global_configurations.php" title="Novologic Settings" data-filter-tags="package info">
                    <i class="fal fa-cog"></i>
                    <span class="nav-link-text" data-i18n="nav.package_info">Settings</span>
                </a>
               
            </li-->
           <!--  <li id="nav_documents">
                <a href="../users/user_documents.php" title="Novologic Document Manager" data-filter-tags="package info" class=" waves-effect waves-themed">
                    <i class="fal fa-cog"></i>
                    <span class="nav-link-text" data-i18n="nav.package_info">Documents</span>
                </a>
               
            </li>
             <li id="nav_documents">
                <a href="../users/user_documents.php" title="Novologic Document Manager" data-filter-tags="package info" class=" waves-effect waves-themed">
                    <i class="fal fa-cog"></i>
                    <span class="nav-link-text" data-i18n="nav.package_info">Data</span>
                </a>
               
            </li> -->
        </ul>
        <div class="filter-message js-filter-message bg-success-600"></div>
    </nav><div class="slimScrollBar" style="background: rgb(255, 255, 255); width: 4px; position: absolute; top: 0px; opacity: 0.4; display: block; border-radius: 7px; z-index: 99; right: 4px; height: 90.0543px;"></div><div class="slimScrollRail" style="width: 4px; height: 100%; position: absolute; top: 0px; display: none; border-radius: 7px; background: rgb(51, 51, 51); opacity: 0.4; z-index: 90; right: 4px;"></div></div>
    <!-- END PRIMARY NAVIGATION -->
     <div class="nav-footer shadow-top" style="background: none;">
         <ul class="list-table m-auto nav-footer-buttons">
            <li>
                 <a href="../users/view_users"> <button type="button" class="btn btn-sm btn-primary waves-effect waves-themed" style="min-width:90px;max-width:100px;overflow-wrap:break-word;height:50px;">Users</button></a>
            </li>
            <li>
                <a href="../users/user_documents"><button type="button" class="btn btn-sm btn-primary waves-effect waves-themed" style="min-width:90px;max-width:100px;overflow-wrap:break-word;height:50px;">Data & Documents</button></a>
            </li>
    </div>
    <!-- NAV FOOTER -->
    <div class="nav-footer shadow-top" style="padding-top:10px;margin-top:5%;background: none;">
        <a href="#" onclick="return false;" data-action="toggle" data-class="nav-function-minify" class="hidden-md-down">
            <i class="ni ni-chevron-right"></i>
            <i class="ni ni-chevron-right"></i>
        </a>
        <ul class="list-table m-auto nav-footer-buttons">
            <li>
                <a href="javascript:void(0);" data-toggle="tooltip" data-placement="top" title="Mail">
                    <i class="fal fa-envelope-open"></i>
                </a>
            </li>
            <li>
                <a href="javascript:void(0);" data-toggle="tooltip" data-placement="top" title="" data-original-title="Chat logs">
                    <i class="fal fa-comments"></i>
                </a>
            </li>
            <li>
                <a href="javascript:void(0);" data-toggle="tooltip" data-placement="top" title="" data-original-title="Support Chat">
                    <i class="fal fa-life-ring"></i>
                </a>
            </li>
            <li>
                <a href="javascript:void(0);" data-toggle="tooltip" data-placement="top" title="" data-original-title="Make a call">
                    <i class="fal fa-phone"></i>
                </a>
            </li>
        </ul>

    </div> <!-- END NAV FOOTER -->
    <div class="align-items-center text-muted" style="float:left;text-align:center;margin-top:5%;">
            <span class="hidden-md-down fw-700" style="margin:0 auto;color:#ccc;">2020 © Novologic by&nbsp;<a href="#" class="text-primary fw-500" style="color:#ccc !important;" title="Stone Boy" target="_blank">Stoneboy</a></span>
        </div>
</aside>