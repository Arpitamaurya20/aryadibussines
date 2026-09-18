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



           



            <span class="page-logo-text mr-1"><img src="../img/Chola-Logo.png"></span>



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



                     <ul class="nav-menu">

                             <li id="nav_dashboard">



                                <a href="../dashboard/cfl_dashboard" title="Info" data-filter-tags="package info">



                                    <i class="fal fa-tachometer"></i>



                                    <span class="nav-link-text" data-i18n="nav.package_info">Profile</span>



                                </a>



                             



                            </li>
                        <li id="nav_profile">



                                <a href="../cfl/update-profile" title="Info" data-filter-tags="package info">



                                    <i class="fal fa-user"></i>



                                    <span class="nav-link-text" data-i18n="nav.package_info">Update Profile</span>



                                </a>



                             



                            </li>


                              <li id="nav_Villages">

                                <a href="../village/view-village" title="Info" data-filter-tags="Village Info">

                                    <i class="fal fa-list-alt "></i>

                                    <span class="nav-link-text" data-i18n="nav.package_info">Villages</span>

                                </a>

                                <ul>

                                    <li id="nav_view_village">

                                        <a href="../village/view-village" title="View village" data-filter-tags="package info documentation">

                                            <span class="nav-link-text" data-i18n="nav.package_info_documentation">View Villages</span>

                                        </a>

                                    </li>

                                 

                                                      

                                     <li id="nav_create_village">

                                        <a href="../village/add-village" title="Add village" data-filter-tags="package info documentation">

                                            <span class="nav-link-text" data-i18n="nav.package_info_documentation">Add Village</span>

                                        </a>

                                    </li>

                              

                                   



                                </ul>

                            </li>

                                 <li id="nav_training">

                                <a href="../training/view-training" title="Info" data-filter-tags="Data info">

                                    <i class="fal fa-database"></i>

                                    <span class="nav-link-text" data-i18n="nav.package_info">Data</span>

                                </a>

                                <ul>

                                    <li id="nav_training_data">

                                        <a href="../training/view-training" title="View Data" data-filter-tags="CFL Info">

                                            <span class="nav-link-text" data-i18n="nav.package_info_documentation">View Data</span>

                                        </a>

                                    </li>
        

                                     <li id="nav_training_data_Create">

                                        <a href="../training/add-training" title="Add Data" data-filter-tags="package info documentation">

                                            <span class="nav-link-text" data-i18n="nav.package_info_documentation">Add Data</span>

                                        </a>

                                    </li>

                              
                                        <li id="nav_dashboard">



                                <a href="../dashboard/cfl_dashboard" title="Info" data-filter-tags="package info">

                                    <i class="fal fa-home"></i>

                                    <span class="nav-link-text" data-i18n="nav.package_info">Performance and Impact</span>

                                </a>

                            </li>

                                   



                                </ul>

                            </li>
                      </ul>



        <div class="filter-message js-filter-message bg-success-600"></div>



    </nav><div class="slimScrollBar" style="background: rgb(255, 255, 255); width: 4px; position: absolute; top: 0px; opacity: 0.4; display: block; border-radius: 7px; z-index: 99; right: 4px; height: 90.0543px;"></div><div class="slimScrollRail" style="width: 4px; height: 100%; position: absolute; top: 0px; display: none; border-radius: 7px; background: rgb(51, 51, 51); opacity: 0.4; z-index: 90; right: 4px;"></div></div>



   



    <!-- NAV FOOTER -->



    <div class="nav-footer shadow-top" style="padding-top:10px;margin-top:5%;background: none;height: unset!important;">



        



    </div> <!-- END NAV FOOTER -->



    <div class="align-items-center text-muted" style="float:left;text-align:center;margin-top:5%;">



            <span class="hidden-md-down fw-700" style="margin:0 auto;color:#ccc;">2020 © Cholamandalam &nbsp;</span>



        </div>



</aside>