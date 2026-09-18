<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <?php
		include('../controllers/common_controllers.php');
		include('controller/sub_categories_controller.php');
        include('../manage-categories/controller/categories_controller.php');
        
        $UserType = SessionCheck();
        setNavigation($_SESSION['Roles']);
		$conn = _connectodb();
		//$total_projects = getTotatProjects($conn,$Enterpriseid);
		?>
    <meta charset="utf-8">
    <title>
        Manage Sub-Categories - Aryadibusiness
    </title>
    <meta name="description" content="View Schema">
    <?php
        include('../includes/common_head_content.php');
        ?>
    <link rel="stylesheet" media="screen, print" href="../css/datagrid/datatables/datatables.bundle.css">
    <style>
    .modal_header {
        background-color: #003f88;
        color: #fff;
    }

    .modal_header button {
        opacity: 1;
        color: #fff;
    }
    </style>
</head>
<?php

	$UserType = SessionCheck();

	$APPA = false;
	if($UserType == "")
	{
		$APPA = true;
	}
	$subcategoriesdata = getAllSubCategories($conn);
    $categoriesdata = getAllCategories($conn);
    $categories_array_key = generateArraywithKey($categoriesdata);
	?>

<body class="mod-bg-1 desktop chrome webkit pace-done nav-function-fixed blur">
    <!-- DOC: script to save and load page settings -->
    <?php include('../js/theme_settings.js'); ?>
    <!-- BEGIN Page Wrapper -->

    <div class="page-wrapper">
        <div class="page-inner">
            <?php
                include('../navigation/admin_navigation.php');
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
                        <li class="breadcrumb-item"><a href="../dashboard/admin_dashboard">Aryadibusiness</a></li>
                        <li class="breadcrumb-item active">Manage Sub-Categories</li>

                    </ol>


                    <!-- Page Container -->
                    <div class="row">
                        <div class="col-xl-12">
                            <div id="panel-1" class="panel">
                                <div class="panel-hdr">
                                    <h2>
                                        Manage Sub-Categories</span>
                                    </h2>
                                    <a href="#" onclick="addsubcategories()" class="btn btn-info"
                                        style="margin-right:20px;">Add</a>

                                </div>
                                <div class="panel-container show">
                                    <div class="panel-content">
                                        <!-- datatable start -->
                                        <table id="view-subcategories"
                                            class="table table-bordered table-hover table-striped w-100">
                                            <thead>
                                                <tr>
                                                    <th>#</th>
                                                    <th>Categories</th>
                                                    <th>Sub-Categories Name</th>

                                                    <th>Delete</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                	$i=1;
                                                	foreach($subcategoriesdata as $subcategoriesvalue)
                                                	{

                                                	$id  = $subcategoriesvalue['ID'];
                                                	?>
                                                <tr>
                                                    <td><?php echo $i; ?></td>
                                                    <td><?php
                                                     if($subcategoriesvalue['Categories'] == "")
                                                     echo "Not Set" ;
                                                 else
                                                 {
                                                     $categories_details = $categories_array_key[$subcategoriesvalue['Categories']];
                                                     echo $categories_details['CategoriesName'];
                                                 } 
                                                     ?></td>
                                                    <td><?php echo $subcategoriesvalue['SubCategoriesName']; ?></td>


                                                    <td><a onclick="DeleteSubCategories('<?php echo $id;?>')"><i
                                                                class="fal fa-trash" aria-hidden="true"></i></td>
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


                    <!-- Modal -->
                    <div class="modal fade" id="addsubcategories" tabindex="-1" role="dialog"
                        aria-labelledby="exampleModalLabel" aria-hidden="true">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">
                                <div class="modal-header modal_header">
                                    <h5 class="modal-title" id="exampleModalLabel">Add Sub-Categories</h5>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <div class="modal-body">
                                    <form METHOD="POST" id="add_subcategories">
                                        <div class="form-group">
                                            <div class="row">
                                                <div class="col-12 mb-3">
                                                    <label>Categories <span class="text-danger">*</span></label>
                                                    <select class="select2 form-control w-100" id="categories"
                                                        name="categories">
                                                        <option value="-1">Search & Select</option>
                                                        <?php

                                                        foreach($categoriesdata as $categoriesvalue)
                                                        {
                                                        ?>
                                                        <option value="<?php echo $categoriesvalue['ID'];?>">
                                                            <?php echo $categoriesvalue['CategoriesName'];?>
                                                        </option>
                                                        <?php
                                                        }
                                                        ?>
                                                    </select>
                                                </div>
                                                <div class="col-12 mb-3">
                                                    <label for="exampleInputEmail1">Sub-Categories Name</label>
                                                    <input type="text" class="form-control" name="subcategories_name" id="subcategories_name"
                                                        placeholder="Enter Sub-Categories Name">
                                                </div>
                                            </div>



                                        </div>

                                        <button type="submit" id="submit" class="btn btn-primary"
                                            onclick="return add_subcategories()">Submit</button>
                                    </form>
                                </div>

                            </div>
                        </div>
                    </div>

                </main>

                <!-- this overlay is activated only when mobile menu is triggered -->
                <div class="page-content-overlay" data-action="toggle" data-class="mobile-nav-on"></div>
                <!-- END Page Content -->
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
    <script src="../js/modules/conf-sub-categories.js"></script>

</body>


</html>