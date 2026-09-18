<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <title>
          Cholamandalam Graph
        </title>
        <meta name="description" content="Cholamandalam Dashboard">
        <?php
        include('../controllers/common_controllers.php');
        $UserType = SessionCheck();
        if($UserType == "")
        {
            ?>
            <script type="text/javascript">
            window.location.href = "../authentication/login.php";
            </script>
            <?php
        }
        include('../includes/common_head_content.php'); 
		$conn = _connectodb();
		include('../UserRoles/controller/role_controller.php');
        include('../blocks/controller/block_controller.php');
        include('../cfl/controller/cfl_controller.php');
        include('../village/controller/village_controller.php');
        $Blockdata=getAllBlock($conn);
        $Blockdata=json_decode($Blockdata,true);

        //User Roles
        $Roledata=getAllRole($conn);
        $Roledata=json_decode($Roledata,true);

         //CFL
        $cfldata=getAllClf($conn);
        $cfldata=json_decode($cfldata,true);

         //Villages
        $Villagedata=getAllVillage($conn);
        $Villagedata=json_decode($Villagedata,true);
        ?>
        <style type="text/css">
            .nav-footer {
            height: unset!important;
        }
        .slimScrollDiv{
            background: #003f88!important;
        }
        .l-h-n {
                line-height: normal;
            padding: 10px;
            text-align: center;
            font-size: 20px;
        }
        .bg-primary-300 {
            background-color: #03A9F4!important;
           
        }
        .bg-danger-200 {
            background-color: #d71771!important;
        }
        .bg-success-200 {
            background-color: #007266!important;
           
        }
        .bg-warning-200 {
            background-color: #edac20!important;
          
        }
        a.canvasjs-chart-credit {
            display: none;
        }
        </style>
    </head>
    <body class="mod-bg-1 header-function-fixed nav-function-fixed blur">
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
                themeSettings = (localStorage.getItem('themeSettings')) ? JSON.parse(localStorage.getItem('themeSettings')) :
                {},
                themeURL = themeSettings.themeURL || '',
                themeOptions = themeSettings.themeOptions || '';
            /** 
             * Load theme options
             **/
            if (themeSettings.themeOptions)
            {
                classHolder.className = themeSettings.themeOptions;
                console.log("%c✔ Theme settings loaded", "color: #148f32");
            }
            else
            {
                console.log("Heads up! Theme settings is empty or does not exist, loading default settings...");
            }
            if (themeSettings.themeURL && !document.getElementById('mytheme'))
            {
                var cssfile = document.createElement('link');
                cssfile.id = 'mytheme';
                cssfile.rel = 'stylesheet';
                cssfile.href = themeURL;
                document.getElementsByTagName('head')[0].appendChild(cssfile);
            }
            /** 
             * Save to localstorage 
             **/
            var saveSettings = function()
            {
                themeSettings.themeOptions = String(classHolder.className).split(/[^\w-]+/).filter(function(item)
                {
                    return /^(nav|header|mod|display)-/i.test(item);
                }).join(' ');
                if (document.getElementById('mytheme'))
                {
                    themeSettings.themeURL = document.getElementById('mytheme').getAttribute("href");
                };
                localStorage.setItem('themeSettings', JSON.stringify(themeSettings));
            }
            /** 
             * Reset settings
             **/
            var resetSettings = function()
            {
                localStorage.setItem("themeSettings", "");
            }

        </script>
        <!-- BEGIN Page Wrapper -->
        
        <div class="page-wrapper">
            <div class="page-inner">
                <?php 
                  if($UserType == "Admin"){
                    include('../navigation/admin_navigation.php'); 
                      }
                    else  if($UserType == "CFL"){
                    include('../navigation/cfl_navigation.php'); 
                     }
                    else  if($UserType == "Manager"){
                    include('../navigation/manager_navigation.php'); 
                     }
                     else  if($UserType == "Chola Representative"){
                    include('../navigation/representative_navigation.php'); 
                     }
                     else{

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
                            <li class="breadcrumb-item"><a href="javascript:void(0);">Cholamandalam</a></li>
                             <?php 
                              if($UserType == "Admin"){ ?>
                                <li class="breadcrumb-item"><a href="admin_dashboard.php">Dashboard</a></li><?php
                                  }
                                else  if($UserType == "CFL"){
                                ?>
                                <li class="breadcrumb-item"><a href="manager_dashboard.php">Dashboard</a></li><?php
                                 }
                                else  if($UserType == "Manager"){
                                 ?>
                                <li class="breadcrumb-item"><a href="manager_dashboard.php">Dashboard</a></li><?php
                                 }
                                 else  if($UserType == "Chola Representative"){
                                 ?>
                                <li class="breadcrumb-item"><a href="representative_dashboard.php">Dashboard</a></li><?php
                                 }
                                 else{
                                    
                                 }
                            ?>						
                            <li class="breadcrumb-item"><a href="javascript:void(0);">Graph</a></li>
                        </ol>
                        
						<div class="row">
                          
                        <?php
 
                        $dataPoints = array( 
                            array("y" => 530, "label" => "Drivers" ),
                            array("y" => 365, "label" => "Cleaners" ),
                            array("y" => 467, "label" => "Mechanics" ),
                            array("y" => 690, "label" => "Trucker's Women" ),
                            array("y" => 2052, "label" => "Total" )
                            
                        );
                        
                        ?>
                        
                        <script>
                        window.onload = function() {
                         
                        var chart = new CanvasJS.Chart("chartContainer", {
                            animationEnabled: true,
                            theme: "light2",
                            title:{
                                text: "Trainer Trucker's Community: Gujrat July 2021"
                            },
                            axisY: {
                                title: ""
                            },
                            data: [{
                                type: "column",
                                yValueFormatString: "",
                                dataPoints: <?php echo json_encode($dataPoints, JSON_NUMERIC_CHECK); ?>
                            }]
                        });
                        chart.render();
                         
                        }
                        </script>
                      
                        <div id="chartContainer" style="height: 370px; width: 100%;"></div>

                        <script src="https://canvasjs.com/assets/script/canvasjs.min.js"></script>
			
                      
                            
                        </div>
                         <h5 style="color:red;margin-top: 10px;text-align: center;font-style: oblique;"> ** Its Sample, data Integration and work in progress</h5>
                       
                        
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
       
       <?php
        include('../includes/common_modules.php');
        include('../includes/common_scripts.php');
       ?>
    	<script>
    		$(document).ready(function () 
            {
    		  $("#nav_dashboard").addClass("active");	
    		});
    	</script>
    </body>
</html>
