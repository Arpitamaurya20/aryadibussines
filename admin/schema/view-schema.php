<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <?php
		include('../controllers/common_controllers.php');
		include('controller/schema_controller.php');
        include('../blocks/controller/block_controller.php');
		$conn = _connectodb();
        
		//$total_projects = getTotatProjects($conn,$EnterpriseID);
		?>
        <meta charset="utf-8">
        <title>
             Schemes  
        </title>
        <meta name="description" content="View Schema">
        <?php
        include('../includes/common_head_content.php'); 
        ?>
		<link rel="stylesheet" media="screen, print" href="../css/datagrid/datatables/datatables.bundle.css">
		
    </head>
	<?php
	
	$UserType = SessionCheck();

	$APPA = false;
	if($UserType == "")
	{
		$APPA = true;
	}
	$Schemadata = getAllSchema($conn);
    $Schemadata = json_decode($Schemadata,true);
	
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
                            <li class="breadcrumb-item active">Schemes</li>
                           
                        </ol>
                     	
						
						<!-- Page Container -->
						 <div class="row">
                            <div class="col-xl-12">
                                <div id="panel-1" class="panel">
                                    <div class="panel-hdr">
                                        <h2>
                                            View Schemes</span>
                                        </h2>
                                        <a href="add-schema" class="btn btn-info" style="margin-right:20px;">Add</a>
                                    </div>
									<div class="panel-container show">
                                        <div class="panel-content">
                                            <!-- datatable start -->
                                            <table id="view-projects" class="table table-bordered table-hover table-striped w-100">
                                                <thead>
                                                    <tr>
                                                        <th>#</th>
                                                        <th>Title</th>
                                                        
                                                         <th>Short Description</th>
                                                                          
                                                         <?php if(($UserType == "Manager") || ($UserType == "Chola Representative")){  } else { ?>
                                                        <th>Edit</th>

                                                        <th>Delete</th>
                                                         <?php } ?>
														
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                	<?php
                                                	$i=1;
                                                	foreach($Schemadata as $Schemavalue)
                                                	{
                                                	
                                                	$ID  = $Schemavalue['SchemaId'];
                                                	?>
                                                	<tr>
                                                		<td><?php echo $i; ?></td>
                                                		<td><?php echo $Schemavalue['title']; ?></td>
                                                        
                                                        <td><?php echo $Schemavalue['shortdescription']; ?></td>
                                                		  <?php if(($UserType == "Manager") || ($UserType == "Chola Representative")){  } else { ?>
                                                		<td><span style='cursor:pointer;'><a href="update-schema?Id=<?php echo $ID ;?>"><i class='fal fa-edit'></i></a></span></td>
                                                         <td><a onclick="DeleteSchema('<?php echo $ID; ?>')"><i class="fal fa-trash" aria-hidden="true"></i></td>
                                                          <?php } ?>
                                                	</tr>
                                                	<?php
                                                	$i++;
                                                	}
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
          $(document).ready(function()
          {
            $("#nav_schema").addClass("active");
            $("#nav_schema").addClass("open");
            $("#nav_view_schema").addClass("active");
          });
         </script>
	<script>
		  $(document).ready(function()
            {
				
               $('#view-projects').dataTable(
                {
                    responsive: true
                });

                $('.js-thead-colors a').on('click', function()
                {
                    var theadColor = $(this).attr("data-bg");
                    console.log(theadColor);
                    $('#dt-basic-example thead').removeClassPrefix('bg-').addClass(theadColor);
                });

                $('.js-tbody-colors a').on('click', function()
                {
                    var theadColor = $(this).attr("data-bg");
                    console.log(theadColor);
                    $('#dt-basic-example').removeClassPrefix('bg-').addClass(theadColor);
                });

            });
			
		

	</script>
     <script type="text/javascript">
        

        function DeleteSchema(deleteID)
        {
            
            //alert(areaID);
            alertify.confirm('Cholamandalam ','Do you really want to delete Scheme', function()
            { 
                $.post("action/delete_schema.php",
                {
                    deleteID: deleteID
                },
                function(data, status){
                   // alert(data);
                   // alert(status);
                    status=status.trim()
                    if(status == 'success')
                    {
                        alertify.alert('Cholamandalam ',"Scheme has been Deleted");
                        setTimeout(function(){ location.href = "view-schema"; }, 2000);
                        /*window.location.assign("user_dashboard.php");*/
                    }
                    else
                    {
                        alertify.alert(data);
                    }
                }); 

            }, 
            function(){ alertify.error('Deletion Cancelled')});
        }
    </script>

    </body>
	
	
</html>
