<!DOCTYPE html>
<html lang="en">
    <head>
    	
		<?php
		include('../controllers/common_controllers.php');
		include('controller/schema_controller.php');
		include('../blocks/controller/block_controller.php');
		

		$conn = _connectodb();
		$UserType = SessionCheck();
		
		$conn = _connectodb();
		
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
        <meta charset="utf-8">
        <title>
            <?php echo $getSchemavalue['title'];?>
        </title>
        <meta name="description" content="Update Schema">
        
		<link rel="stylesheet" media="screen, print" href="../css/formplugins/bootstrap-datepicker/bootstrap-datepicker.css">
		<style>
			.schemeheading {
				margin-top: 10px;
			}
		</style>
    </head>
	
    <body>
       
        <div class="container-fluid">
            	<div class="row">
            		<div class="col-md-12">
            			<h1 class="schemeheading"><?php echo $getSchemavalue['title'] ?></h1>
            		</div>
            		<div class="col-md-12">
            			<p><?php echo $getSchemavalue['shortdescription'];?></p>
            		</div>
            	</div>
                  
                </div>
        </div>
        <!-- END Page Wrapper -->
        
       
       
	   <script src="../js/formplugins/bootstrap-datepicker/bootstrap-datepicker.js"></script>

		 <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote.min.css" rel="stylesheet">
         <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote.min.js"></script>
		
    </body>
	
	
</html>