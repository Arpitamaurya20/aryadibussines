<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <?php
		include('../controllers/common_controllers.php');
		include('controller/spare_part_controller.php');
        include('../manage-categories/controller/categories_controller.php');
        include('../manage-uom/controller/uom_controller.php');
        $UserType = SessionCheck();
        setNavigation($_SESSION['Roles']);
		$conn = _connectodb();
		//$total_projects = getTotatProjects($conn,$Enterpriseid);
		?>
    <meta charset="utf-8">
    <title>
        Manage Spare Part List- Aryadibusiness
    </title>
    <meta name="description" content="View Schema">
    <?php
        include('../includes/common_head_content.php');
        ?>
    <link rel="stylesheet" media="screen, print" href="../css/datagrid/datatables/datatables.bundle.css">
    <link rel="stylesheet" media="screen, print"
        href="../css/formplugins/bootstrap-datepicker/bootstrap-datepicker.css">
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
    $SparePartArray = getAllSparePart($conn);
    $CategoryData = getAllCategories($conn);
    $CategoryData_array_key = generateArraywithKey($CategoryData);

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
                        <li class="breadcrumb-item"><a href="../dashboard/admin_dashboard">Aryadibusiness</a></li>
                        <li class="breadcrumb-item active">Spare Part List</li>
                    </ol>

                    <!-- Page Container -->
                    <div class="row">
                        <div class="col-xl-12">
                            <div id="panel-1" class="panel">
                                <div class="panel-hdr">
                                    <h2>View Spare Parts </h2>
                                    <?php if($UserType=="Admin"||"Sub Admin") { ?>
                                    <a href="#" onclick="OpenCSVmodal()" class="btn btn-info"
                                        style="margin-right:20px;">Upload CSV File</a>
                                    <?php } ?>
                                    <?php if($UserType=="Admin"||"Sub Admin") { ?>
                                    <a href="#" onclick="ExportSparePartData()" class="btn btn-info"style="margin-right:20px;">Export Data</a>
                                    <?php } ?>
                                    <a href="#" onclick="OpenSparePart_modal()" class="btn btn-info"
                                        style="margin-right:20px;">Add Spare Part</a>

                                </div>
                                <div class="panel-container show">
                                    <div class="panel-content">
                                        <!-- datatable start -->
                                        <table id="view-spare-part"
                                            class="table table-bordered table-hover table-striped w-100">
                                            <thead>
                                                <tr>
                                                    <th>#</th>
                                                    <th>Spare Parts</th>
                                                    <th>Category </th>
                                                    <th>UOM </th>
                                                    <th>Price </th>
                                                    <th>Update</th>
                                                    <th>Delete</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                	$i=1;
                                                	foreach($SparePartArray as $SparePart)
                                                	{
                                                	   $id  = $SparePart['ID'];
                                                ?>
                                                <tr>
                                                    <td><?php echo $i; ?></td>

                                                    <td><?php echo $SparePart['SparePart'] ?></td>
                                               

                                                    <td><?php 
                                                        if($SparePart['Categories'] == "")
                                                        echo "Not Set" ;
                                                        else
                                                        {
                                                        $spare_part_details = $CategoryData_array_key[$SparePart['Categories']];
                                                            echo $spare_part_details['CategoriesName'];
                                                        } 
                                                    ?></td>
                                                    <td><?php 
                                                        if($SparePart['UOM'] == "")
                                                        echo "Not Set" ;
                                                        else
                                                        {
                                                        $spare_part_details = $uom_array_key[$SparePart['UOM']];
                                                            echo $spare_part_details['UOMName'];
                                                        } 
                                                    ?></td>




                                                    <td><?php echo $SparePart['Price']; ?></td>
                                                    <td>
                                                        <a onclick="UpdateSparePart_modal('<?php echo $id;?>')"
                                                            class="cursor-pointer"><i class="fal fa-edit"
                                                                aria-hidden="true"></i>
                                                    </td>
                                                    <td>
                                                        <a onclick="DeleteSparePart('<?php echo $id;?>')"><i
                                                                class="fal fa-trash" aria-hidden="true"></i>
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

                    <!-- upload csv modal             -->

          <div class="modal fade" id="upload_csv" role="dialog"
                        aria-labelledby="exampleModalLabel" aria-hidden="true">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">
                                <div class="modal-header pb-0 edit_header">
                                    <div class="tab_modal_heading">
                                        <h2>Upload Branch CSV </h2>
                                    </div>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <div class="modal-body">
                                    <div class="reset_password">
                                       <form id="uplaod_branch_csv">
                                            <input type="file" class="form-control" name="csvFile" accept=".csv">
                                            

                                            <div class="row justify-content-center mt-3">

                                                <a class="btn-info btn form_submit pl-4 pr-4 pt-2 pb-2 text-white" id="upload_csv_btn"
                                                    onclick="UploadSpareParts_CSV()">Upload</a>

                                            </div>
                                        </form>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>

                    <!-- Modal -->
                    <div class="modal fade" id="add_edit_spare_part_modal"  role="dialog"
                        aria-hidden="true">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">
                                <div class="modal-header modal_header">
                                    <h5 class="modal-title">Add Spare Part </h5>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <div class="modal-body">
                                    <form METHOD="POST" id="add_update_spare_part_form">
                                        <div class="form-group">
                                            <div class="row mt-2">
                                                <div class="col-12">
                                                    <label for="spare_part">Spare Part <span class="text-danger">*</span></label>
                                                     <input type="text" class="form-control" name="spare_part"
                                                            id="spare_part" placeholder="Enter Spare Part ">
                                                </div>      
                                            </div>      
                                            <div class="row mt-3">
                                                <div class="col-12">
                                                    <label for="categories">Category <span class="text-danger">*</span></label>
                                                    <select class="select2 form-control w-100" id="categories"
                                                    name="categories">
                                                    <option value="-1">Search & Select</option>
                                                    <?php
                                                        foreach($CategoryData as $CategoryValue)
                                                        {
                                                            ?>
                                                            <option value="<?php echo $CategoryValue['ID'];?>">
                                                                <?php echo $CategoryValue['CategoriesName'];?>
                                                            </option>
                                                            <?php
                                                        }
                                                    ?>
                                                    </select>
                                                </div>
                                            </div>

                                            <div class="row mt-3">
                                                <div class="col-12">
                                                    <label for="uom">UoM <span class="text-danger">*</span></label>
                                                    <select class="select2 form-control w-100" id="uom"
                                                    name="uom">
                                                    <option value="-1">Search & Select</option>
                                                    <?php
                                                        foreach($AllUOM as $AllUOMValue)
                                                        {
                                                            ?>
                                                            <option value="<?php echo $AllUOMValue['ID'];?>">
                                                                <?php echo $AllUOMValue['UOMName'];?>
                                                            </option>
                                                            <?php
                                                        }
                                                    ?>
                                                    </select>
                                                </div>
                                            </div>

                                            <div class="row mt-3">
                                                <div class="col-12">
                                                    <label class="form-label" for="price">Price </label>
                                                    <input type="text" class="form-control" onkeyup="validPriceNumber(price)" 
                                                        name="price" id="price" placeholder="Enter Price" />
                                                </div>

                                            </div>
                                        </div>

                                        <input type="hidden" id="form_action" name="form_action" value="add" />
                                        <input type="hidden" id="form_id" name="form_id" value="-1" />
                                        <button type="submit" id="submit" class="btn btn-primary"
                                            onclick="return AddUpdateSparePart()">Submit</button>
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
    <script src="../js/modules/conf-spare-part-list.js"></script>

</body>


</html>