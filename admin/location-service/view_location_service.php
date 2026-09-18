<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <?php
    include('../controllers/common_controllers.php');
    include('../authentication/auth_controller/authentication_controller.php');
    include('controller/location_service_controller.php');
    
    $UserType = SessionCheck();
    setNavigation($_SESSION['Roles']);
    $conn = _connectodb();
    ?>
    <meta charset="utf-8">
    <title>
        View Location Service
    </title>
    <meta name="description" content="View Villages">
    <?php
    include('../includes/common_head_content.php');
    ?>
    <link rel="stylesheet" media="screen, print" href="../css/datagrid/datatables/datatables.bundle.css">
</head>
<?php

$UserType = SessionCheck();
$APPA = false;
if ($UserType == "Admin") {
    $location_servicedata = getAlllocation_service($conn);
    $location_servicedata = json_decode($location_servicedata, true);

    $cityarray = getcityarray($conn);
    $servicearray = getservicearray($conn);
    // print_r($cityarray);
    // die();

}


?>

<body class="mod-bg-1 desktop chrome webkit pace-done nav-function-fixed blur">
    <!-- DOC: script to save and load page settings -->
    <script>
        /**
         *	This script should be placed right after the body tag for fast execution
         *	Note: the script is written in pure javascript and does not depend on thirdparty library
         **/
        'use strict';
        var classHolder = document.getElementsByTagName("BODY")[0],
            /**
             * Load from localstorage
             **/
            themeSettings = (localStorage.getItem('themeSettings')) ? JSON.parse(localStorage.getItem('themeSettings')) : {},
            themeURL = themeSettings.themeURL || '',
            themeOptions = themeSettings.themeOptions || '';
        /**
         * Load theme options
         **/
        if (themeSettings.themeOptions) {
            classHolder.className = themeSettings.themeOptions;
            console.log("%c✔ Theme settings loaded", "color: #148f32");
        } else {
            console.log("Heads up! Theme settings is empty or does not exist, loading default settings...");
        }
        if (themeSettings.themeURL && !document.getElementById('mytheme')) {
            var cssfile = document.createElement('link');
            cssfile.id = 'mytheme';
            cssfile.rel = 'stylesheet';
            cssfile.href = themeURL;
            document.getElementsByTagName('head')[0].appendChild(cssfile);
        }
        /**
         * Save to localstorage
         **/
        var saveSettings = function() {
            themeSettings.themeOptions = String(classHolder.className).split(/[^\w-]+/).filter(function(item) {
                return /^(nav|header|mod|display)-/i.test(item);
            }).join(' ');
            if (document.getElementById('mytheme')) {
                themeSettings.themeURL = document.getElementById('mytheme').getAttribute("href");
            };
            localStorage.setItem('themeSettings', JSON.stringify(themeSettings));
        }
        /**
         * Reset settings
         **/
        var resetSettings = function() {
            localStorage.setItem("themeSettings", "");
        }
    </script>
    <!-- BEGIN Page Wrapper -->

    <div class="page-wrapper">
        <div class="page-inner">
            <?php
            if ($UserType == "Admin") {
                include('../navigation/admin_navigation.php');
            } else  if ($UserType == "CFL") {
                include('../navigation/cfl_navigation.php');
            } else  if ($UserType == "Manager") {
                include('../navigation/manager_navigation.php');
            } else  if ($UserType == "Chola Representative") {
                include('../navigation/representative_navigation.php');
            } else {
            }
            ?>
            <div class="page-content-wrapper">
                <!-- BEGIN Page Header -->
                <?php
                include('../includes/common_header.php');
                ?>
                <!-- END Page Header -->
                <!-- BEGIN Page Content -->
                <!-- the #js-page-content id is needed for some plugins to initialize -->
                <main id="js-page-content" role="main" class="page-content">
                    <ol class="breadcrumb page-breadcrumb">
                        <li class="breadcrumb-item"><a href="javascript:void(0);">TechXpert</a></li>
                        <li class="breadcrumb-item active">View Location Services </li>

                    </ol>


                    <!-- Page Container -->
                    <div class="row">
                        <div class="col-xl-12">
                            <div id="panel-1" class="panel">
                                <div class="panel-hdr">
                                    <h2>
                                        View Location Services</span>
                                    </h2>
                                    <a href="add_location_service" class="btn btn-info" style="margin-right:20px;">Add</a>
                                </div>
                                <div class="panel-container show">
                                    <div class="panel-content">
                                        <!-- datatable start -->
                                        <table id="view-projects" class="table table-bordered table-hover table-striped w-100">
                                            <thead>
                                                <tr>
                                                    <th>#</th>
                                                    <th>Title</th>
                                                    <th>City</th>
                                                    <th>Service</th>
                                                    <th>Featured</th>
                                                    <th>URL</th>
                                                    <th>Service Position</th>
                                                    <th>Edit</th>
                                                    <th>Delete</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                $i = 1;
                                                foreach ($location_servicedata as $location_servicedata) {


                                                    $id  = $location_servicedata['id'];
                                                ?>
                                                    <tr>
                                                        <td><?php echo $i++; ?></td>
                                                        <td><?php echo $location_servicedata['title']; ?></td>

                                                        <td><?php
                                                            $CityId = $location_servicedata['CityId'];
                                                            echo  $cityarray[$CityId] ?></td>
                                                        <td><?php
                                                            $Name = $location_servicedata['service'];
                                                            echo  $servicearray[$Name] ?></td>
                                                        <td> <?php if ($location_servicedata['featured'] == 1) { ?>
                                                                <i class="fa fa-check fa-2x" aria-hidden="true" style="color: blue;"></i>
                                                            <?php } ?>
                                                        </td>
                                                        <td><?php echo $location_servicedata['url']; ?></td>
                                                        <td><?php echo $location_servicedata['service_position']; ?></td>

                                                        <td><span style='cursor:pointer;'><a href="update_location_service.php?ID=<?php echo $id; ?>"><i class='fal fa-edit'></i></a></span></td>
                                                        <td><a onclick="Deletelocation_service('<?php echo $location_servicedata['id']; ?>')"><i class="fal fa-trash" aria-hidden="true"></i></td>

                                                    </tr>
                                                <?php
                                                }
                                                $i++;

                                                ?>
                                            </tbody>

                                        </table>
                                        <!-- datatable end -->
                                    </div>
                                </div>

                            </div><!-- panel-1 -->
                        </div><!-- col-xl-12 -->
                    </div> <!-- row -->
                    <!-- Datatable Container -->

                </main>
                <!-- this overlay is activated only when mobile menu is triggered -->
                <div class="page-content-overlay" data-action="toggle" data-class="mobile-nav-on"></div> <!-- END Page Content -->
                <!-- BEGIN Page Footer -->
                <?php
                include('../includes/common_footer.php')
                ?>
                <!-- END Page Footer -->

            </div>
        </div>
    </div>
    <!-- END Page Wrapper -->

    <?php
    include('../includes/common_modules.php');
    include('../includes/common_scripts.php');
    ?>
    <script src="../js/datagrid/datatables/datatables.bundle.js"></script>
    <script>
        $(document).ready(function() {
            $("#js-nav-menu").addClass("active");
            $("#js-nav-menu").addClass("open");
            $("#nav_location_services").addClass("active");
            $("#nav_services").addClass("active");
        });
    </script>
    <script>
        $(document).ready(function() {
            $("#nav_projects").addClass("active");
            $('#view-projects').dataTable({
                responsive: true
            });
            $('.js-thead-colors a').on('click', function() {
                var theadColor = $(this).attr("data-bg");
                console.log(theadColor);
                $('#dt-basic-example thead').removeClassPrefix('bg-').addClass(theadColor);
            });
            $('.js-tbody-colors a').on('click', function() {
                var theadColor = $(this).attr("data-bg");
                console.log(theadColor);
                $('#dt-basic-example').removeClassPrefix('bg-').addClass(theadColor);
            });
        });
    </script>
    <script type="text/javascript">
        function Deletelocation_service(deleteID) {

            //alert(areaID);
            alertify.confirm('TechXpert ', 'Do you really want to delete Location Service Details', function() {
                    $.post("action/delete_location_service.php", {
                            deleteID: deleteID
                        },
                        function(data, status) {
                            if (data == "success") {
                                alertify.alert('TechXpert ', "Location Service  has been Deleted");
                                setTimeout(function() {
                                    location.href = "view_location_service";
                                }, 2000);
                                /*window.location.assign("user_dashboard.php");*/
                            } else {
                                alertify.alert(data);
                            }
                        });
                },
                function() {
                    alertify.error('Deletion Cancelled')
                });
        }
    </script>
</body>


</html>