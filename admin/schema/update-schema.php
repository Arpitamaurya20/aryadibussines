<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
    <head>
    	
		<?php
		include('../controllers/common_controllers.php');
		include('controller/schema_controller.php');
		include('../blocks/controller/block_controller.php');
		

		$conn = _connectodb();
		
		$UserType = SessionCheck();
		//$total_projects = getTotatProjects($conn,$EnterpriseID);
		?>
        <meta charset="utf-8">
        <title>
            Update CFL 
        </title>
        <meta name="description" content="Update Schema">
        <?php
        include('../includes/common_head_content.php'); 
        ?>
		<link rel="stylesheet" media="screen, print" href="../css/formplugins/bootstrap-datepicker/bootstrap-datepicker.css">
    </head>
	<?php
	$UserType = SessionCheck();

	if(!($UserType == "Admin"))
	{
		header('Location:../authentication/login.php');
	}
	$conn = _connectodb();
	$username = $_SESSION['pb_username'];
	$SchemaId=$_GET['Id'];
	$getSchema=getSingleSchema($conn,$SchemaId);
	$getSchema = json_decode($getSchema,true);
	foreach ($getSchema as $key => $getSchemavalue) {
		extract($getSchemavalue);
		//print_r($getSchemavalue);
	}
	//GET BLOCK DATA
	$getBlockdata=getAllBlock($conn);
	$getBlockdata = json_decode($getBlockdata,true);

		
	
	?>
    <body class="mod-bg-1 desktop chrome webkit pace-done nav-function-fixed blur">
        <!-- DOC: script to save and load page settings -->
         <?php include('../js/theme_settings.js');?>
        <!-- BEGIN Page Wrapper -->
        
        <div class="page-wrapper">
            <div class="page-inner">
                 <?php 
                    if($UserType == "Admin") 
                        include('../navigation/admin_navigation.php'); 
                ?>
                <div class="page-content-wrapper">
                    <!-- BEGIN Page Header -->
                    <?php 
                        include('../includes/common_header.php');
                        //$UserId=getUserClfId($conn,$getSchemavalue['Username']);	
						//$UserId = json_decode($UserId,true);
						
						//foreach ($UserId  as $id => $Uservalue) {
						//	extract($Uservalue);
					//	}
					//	$NewUserId=$Uservalue['UserID'];	
                    ?>
                    <!-- END Page Header -->
                    <!-- BEGIN Page Content -->
                    <!-- the #js-page-content id is needed for some plugins to initialize -->
                    <main id="js-page-content" role="main" class="page-content">
                        <ol class="breadcrumb page-breadcrumb">
                            <li class="breadcrumb-item"><a href="javascript:void(0);">Cholamandalam</a></li>
                            <li class="breadcrumb-item"><a href="view-schema">Scheme</a></li>
                            <li class="breadcrumb-item active">Update Scheme</li>
                            
                        </ol>
						
						
						<!-- Main Creation Form -->
						<div id="panel-5" class="panel">
							<div class="panel-hdr">
								<h2>
									Update <span class="fw-300"><i>Scheme</i></span>
								</h2>
								 
							</div>
							<div class="panel-container show">
								<div class="panel-content">
									<div class="panel-tag">
										Update the information below to create the Scheme <br>
										
									</div>
									<form id="enterprise_create_form">
										
										<div class="row">
										    	<div class="col-lg-6">
												<div class="form-group">
                                                    <label class="form-label" for="username">Scheme Title</label>
                                                    <input type="text" id="title" name="title" class="form-control" value="<?php echo $getSchemavalue['title'] ?>">
                                                </div>
											</div>
											<div class="col-lg-6">
												<div class="form-group">
                                                    <label class="form-label" for="name">URL</label>
                                                    <input type="url" id="schemaurl" name="schemaurl" value="<?php echo $getSchemavalue['schemaurl'] ?>" class="form-control">
                                                </div>
											</div>
											<div class="col-lg-12">
											 <label class="form-label" for="phonenumber">Scheme Short Description</label>												
											  <textarea id="shortdescription" name="shortdescription"><?php echo $getSchemavalue['shortdescription'] ?></textarea>

										     </div>

											<div class="col-lg-12">
											 <label class="form-label" for="phonenumber">Scheme Description</label>												
											  <textarea id="description" name="description"><?php echo $getSchemavalue['description'] ?></textarea>

										     </div>
											
											</div>
										
										<input type="hidden" name="SchemaId" value="<?php echo $getSchemavalue['SchemaId'] ?>">
									
										
										<div class="row mt-4">
											<div class="col-lg-12">
												<div class="form-group">
													<button type="button" class="btn btn-success waves-effect waves-themed" style="float:right;" onclick="updateSchema()">Update Schema</button>
												</div>
											</div>
										</div>
									
								</form>
							</div>
						</div>
						
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
       
	   <script src="../js/formplugins/bootstrap-datepicker/bootstrap-datepicker.js"></script>
	    <script>
          $(document).ready(function()
          {
           $("#nav_schema").addClass("active");
            $("#nav_schema").addClass("open");
            $("#nav_view_schema").addClass("active");
          });
         </script>
	<script>
		
			function updateSchema()
			{
					var title = document.getElementById("title").value;
				if(title == "")
				{
					noboAlert("Please Enter Title");
					return false;
				}
				var description = document.getElementById("description").value;
				if(description == "")
				{
					noboAlert("Please Enter Description");
					return false;
				}

				var shortdescription = document.getElementById("shortdescription").value;
				if(shortdescription == "")
				{
					noboAlert("Please Enter Shortdescription");
					return false;
				}
				
				var formData = new FormData(document.querySelector('form'));

				$.ajax({
					url: "action/update_schema_action.php",
					type: 'POST',
					data: formData,
					async: false,
					success: function (data) {
						//alert(data);
						var response = JSON.parse(data);
						if(response.error == true)
						{
							alert(response.message);
						}
						else
						{
							alert(response.message)
							BasicURLRouter('../schema/view-schema');
						}
						
					},
					cache: false,
					contentType: false,
					processData: false
				});

				return false;
			}
		
	</script>
		 <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote.min.css" rel="stylesheet">
         <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote.min.js"></script>
		<script>
	     $(document).ready(function() {
	   $('#description').summernote(
	  {
		  "height":400
	  });
	   $('#shortdescription').summernote({
		  "height":200
	  });
	});
    </script>
    </body>
	
	
</html>


