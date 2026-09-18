<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <?php
		include('../controllers/common_controllers.php');
		include('controller/arc_controller.php');
        include('../manage-categories/controller/categories_controller.php');
        include('../manage-uom/controller/uom_controller.php');
		$conn = _connectodb();
        setNavigation($_SESSION['Roles']);
		//$total_projects = getTotatProjects($conn,$Enterpriseid);
		?>
    <meta charset="utf-8">
    <title>
        Manage ARC Items - TechXpert
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
    $arc_array = getAllARC($conn);

    

    $Categories = getAllCategories($conn);
    $arc_array_key = generateArraywithKey($Categories);

    $AllUOM = getAllUOM($conn);
    $uom_array_key = generateArraywithKey($AllUOM);

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
                        <li class="breadcrumb-item"><a href="javascript:void(0);">TechXpert</a></li>
                        <li class="breadcrumb-item"><a href="../company/view-company.php">Manage Corporate</a></li>
                        <li class="breadcrumb-item active">Manage ARC Items</li>

                    </ol>


                    <!-- Page Container -->
                    <div class="row">
                        <div class="col-xl-12">
                            <div id="panel-1" class="panel">
                                <div class="panel-hdr">
                                    <h2>
                                        View ARC Items
                                    </h2>
                                    <a href="#" onclick="openARC()" class="btn btn-info" style="margin-right:20px;">Add
                                        Items</a>

                                </div>
                                <div class="panel-container show">
                                    <div class="panel-content">
                                        <!-- datatable start -->
                                        <table id="view-arc"
                                            class="table table-bordered table-hover table-striped w-100">
                                            <thead>
                                                <tr>
                                                    <th>#</th>
                                                    <th>Code</th>
                                                    <th>ARC Product Name</th>
                                                    <th>Price</th>
                                                    <th>Categories</th>
                                                    <th>Update</th>
                                                    <th>Delete</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                	$i=1;
                                                	foreach($arc_array as $arc_value)
                                                	{

                                                	$ID  = $arc_value['ID'];
                                                	?>
                                                <tr>
                                                    <td><?php echo $i; ?></td>
                                                    <td><?php echo $arc_value['ItemCode']; ?></td>
                                                    <td><?php echo $arc_value['ItemName']; ?></td>
                                                    <td><?php echo $arc_value['ItemPrice']; ?></td>
                                                    <td><?php
                                                    if($arc_value['ItemCategories'] == "")
                                                        {
                                                            echo "NA";
                                                        }
                                                        else
                                                        {
                                                            $CategoriesID_temp = $arc_value['ItemCategories'];
                                                            $CategoriesName = $arc_array_key[$CategoriesID_temp]['CategoriesName'];
                                                            echo $CategoriesName;
                                                        }
                                                     // echo $arc_value['ItemCategories'];

                                                     ?></td>
                                                    <td>
                                                        <a onclick="UpdateARC_modal('<?php echo $ID;?>')"
                                                            class="cursor-pointer"><i class="fal fa-edit"
                                                                aria-hidden="true"></i>
                                                    </td>
                                                    <td>
                                                        <a onclick="DeleteARCAssets('<?php echo $ID;?>')"
                                                            class="cursor-pointer"><i class="fal fa-trash"
                                                                aria-hidden="true"></i>
                                                    </td>


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
                    <div class="modal fade" id="add_edit_arc_modal" role="dialog" aria-hidden="true">
                        <div class="modal-dialog modal-lg" role="document">
                            <div class="modal-content">
                                <div class="modal-header modal_header">
                                    <h5 class="modal-title" id="arc_modal_title"> </h5>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <div class="modal-body">
                                    <form METHOD="POST" id="add_update_arc_form">
                                        <div class="form-group">
                                            <div class="row">

                                                <div class="col-12 col-sm-6">
                                                    <div class="form-group">
                                                        <label for="ItemName">Item Name <span class="text-danger">*</span></label>
                                                        <input type="text" class="form-control" name="item_name"
                                                            id="item_name" placeholder="Enter Item Name">
                                                    </div>
                                                </div>

                                                <div class="col-12 col-sm-6">
                                                    <div class="form-group">
                                                        <label for="ItemDescription">Item Description</label>
                                                        <input type="text" class="form-control" name="item_decs"
                                                            id="item_decs" placeholder="Enter Item Description">
                                                    </div>
                                                </div>

                                                <div class="col-12 col-sm-6">
                                                    <div class="form-group">
                                                        <label for="ItemCode">Item Price <span class="text-danger">*</span></label>
                                                        <input type="text" onkeyup="validISNumber()" class="form-control" name="item_price"
                                                            id="item_price" placeholder="Enter Item Price">
                                                    </div>
                                                </div>

                                                
                                                <div class="col-12 col-sm-6">
                                                    <label for="itemCategories"> Item Category <span class="text-danger">*</span></label>
                                                    <select class="select2 form-control w-100" id="item_categories"
                                                        name="item_categories">
                                                        <option value="-1">Search & Select</option>
                                                        <?php

                                                        foreach($Categories as $Categoriesdata)
                                                        {
                                                        ?>
                                                        <option value="<?php echo $Categoriesdata['ID'];?>">
                                                            <?php echo $Categoriesdata['CategoriesName'];?>
                                                        </option>
                                                        <?php
                                                        }
                                                        ?>
                                                    </select>
                                                </div>

                                                <div class="col-12 col-sm-6">
                                                    <label for="itemCategories"> Item UoM <span class="text-danger">*</span></label>
                                                    <select class="select2 form-control w-100" id="item_uom"
                                                        name="item_uom">
                                                        <option value="-1">Search & Select</option>
                                                        <?php

                                                        foreach($AllUOM as $AllUOMdata)
                                                        {
                                                        ?>
                                                        <option value="<?php echo $AllUOMdata['ID'];?>">
                                                            <?php echo $AllUOMdata['UOMName'];?>
                                                        </option>
                                                        <?php
                                                        }
                                                        ?>
                                                    </select>
                                                </div>

                                                <div class="col-12 col-lg-6">
                                                    <label> Item Image  </label>
                                                    <div class="custom-file">
                                                        <input type="file" id="item_image" name="item_image"
                                                            class="form-control">
                                                    </div>

                                                </div>

                                            </div>

                                        </div>
                                        <input type="hidden" id="form_action" name="form_action" value="add" />
                                        <input type="hidden" id="form_id" name="form_id" value="-1" />
                                        <button class="btn btn-primary"
                                            onclick="return AddUpdateARC()">Submit</button>
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
    <script src="../js/modules/arc-item.js"></script>

</body>


</html>