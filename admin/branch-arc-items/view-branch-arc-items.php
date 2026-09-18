<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <?php
    include('../controllers/common_controllers.php');
    include('controller/branch_arc_controller.php');
    include('../manage-categories/controller/categories_controller.php');
    include('../branch/controller/branch_controller.php');
    include('../arc/controller/arc_controller.php');
    $UserType = SessionCheck();
    setNavigation($_SESSION['Roles']);
    $conn = _connectodb();

    //$total_projects = getTotatProjects($conn,$Enterpriseid);
    ?>
    <meta charset="utf-8">
    <title>
       All Branch ARC Items
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

  $username = $_SESSION['pb_username'];

    $BranchID = -1;
    if(isset($_SESSION['BranchID']))
        {
            $BranchID = $_SESSION['BranchID'];
        }


    // if(isset($_GET['nav']))
    // {
    //     $BranchID = -1;
    // }
    // else
    // {
    //     if(isset($_SESSION['BranchID']))
    //     {
    //         $BranchID = $_SESSION['BranchID'];
    //     }
    // }

    // if($UserType == "Corporate Branch User" || $UserType == "Corporate Admin")
    // {
    //     $corporate_user = true;
    //     $CorporateID = $_SESSION['Roles']['CorporateID'];
    // }
    

    $BranchARC = getAllARCItemsByBranchID($conn,$BranchID);

    $Categories = getAllCategories($conn);
    $Categories_array_key = generateArraywithKey($Categories);

    $branch_array = getAllBranchesWithName($conn,"branch");
    $branch_array_key = generateArraywithKey($branch_array);

    $arc_array = getAllARC($conn);
    $arc_array_key = generateArraywithKey($arc_array);

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
                    <div class="d-flex justify-content-between mb-3 align-items-center">
                    <ol class="breadcrumb page-breadcrumb">
                        <li class="breadcrumb-item"><a href="../dashboard/admin_dashboard">Aryadibusiness</a></li>
                        <li class="breadcrumb-item active"> View Branch ARC Items</li>

                    </ol>
                    <?php
                    if($UserType == "Admin"||$UserType == "Sub Admin"){
                    ?>
                    <a href="#" onclick="Downloadbranch_arcFileFormat(); return false;" class="btn btn-success" 
                            style="margin-right:20px;">Download Template for Bulk Upload</a><?php } ?>
                        </div>



                    <!-- Page Container -->
                    <div class="row">
                        <div class="col-xl-12">
                            <div id="panel-1" class="panel">
                                <div class="panel-hdr">
                                    <h2>
                                        View Branch ARC Items</span>
                                    </h2>
                                    <a href="#" onclick="Exportbranch_arcData()" class="btn btn-info"
                                        style="margin-right:20px;">Export Data</a>
                                    <?php 
                                    if($UserType == "Admin"||$UserType == "Sub Admin"){ 
                                    ?>
                                    <a href="add-branch-arc-item" class="btn btn-info"
                                        style="margin-right:20px;">Add Branch ARC Item</a>
                                    <span onclick="OpenCSVmodal()" class="btn btn-info"
                                        style="margin-right:20px;">Upload CSV File</span>  

                                     <?php } ?> 

                                     
                                
                                </div>
                                <div class="panel-container show">
                                    <div class="panel-content">
                                        <!-- datatable start -->
                                        <table id="view-branch-arc"
                                            class="table table-bordered table-hover table-striped w-100">
                                            <thead>
                                                <tr>
                                                    <th>#</th>
                                                    <th>Branch</th>
                                                    <th>ARC Product Name</th>
                                                    <th>Categories</th>
                                                    <th>Price</th>
                                                    <?php if($UserType == "Admin"){ ?>
                                                    <th>Edit</th>
                                                    <th>Delete</th>
                                                     <?php } ?>
                                                    <th>Order</th>

                                                </tr>
                                            </thead>
                                            <tbody>

                                                <?php

                                                    $i=1;
                                                    foreach($BranchARC as $BranchARCvalue)
                                                    {

                                                    $id  = $BranchARCvalue['ID'];
                                                    ?>
                                                <tr>
                                                    <td><?php echo $i; ?></td>
                                                    <td><?php
                                                    if($BranchARCvalue['BranchID'] == "")
                                                        {
                                                            echo "NA";
                                                        }
                                                        else
                                                        {
                                                            $BranchID_temp = $BranchARCvalue['BranchID'];
                                                            $BranchName = $branch_array_key[$BranchID_temp]['BranchSite'];
                                                            echo $BranchName;
                                                        }
                                                    ?></td>

                                                    <td><?php
                                                    if($BranchARCvalue['ARCItemID'] == "")
                                                        {
                                                            echo "NA";
                                                        }
                                                        else
                                                        {
                                                            $ARCItemID_temp = $BranchARCvalue['ARCItemID'];
                                                            $ARCName = $arc_array_key[$ARCItemID_temp]['ItemName'];
                                                            echo $ARCName;
                                                        }
                                                      ?></td>

                                                    <td><?php
                                                    if($BranchARCvalue['CategoriesID'] == "")
                                                        {
                                                            echo "NA";
                                                        }
                                                        else
                                                        {
                                                            $CategoriesID_temp = $BranchARCvalue['CategoriesID'];
                                                            $CategoriesName = $Categories_array_key[$CategoriesID_temp]['CategoriesName'];
                                                            echo $CategoriesName;
                                                        }
                                                     // echo $BranchARCvalue['CategoriesID']; 
                                                 ?></td>
                                                    <td><?php echo $BranchARCvalue['Price']; ?></td>

                                                    <?php if($UserType == "Admin"){ ?>     
                                                    <td>
                                                        <a class="cursor-pointer" onclick="UpdateBranchARC(<?php echo $id; ?>)"><i class="fal fa-edit"
                                                                aria-hidden="true"></i>
                                                            </a>

                                                    <td><a onclick="DeleteARCItem(<?php echo $id; ?>)"><i class="fal fa-trash" aria-hidden="true"></i></td>

                                                     <?php } ?>

                                                    <td><span onclick="AddtoCart_modal(<?php echo $BranchARCvalue['ARCItemID']; ?>,<?php echo $BranchARCvalue['CategoriesID']; ?>,<?php echo $BranchARCvalue['Price'];?>,<?php echo $BranchARCvalue['BranchID']; ?>)" class="badge cursor-pointer badge-primary">Add to Cart</span></td>

                                                </tr>
                                                <?php
                                                    $i++;
                                                    }
                                                    ?>

                                                <!-- <tr>
                                                    <td></td>
                                                    <td></td>
                                                    <td></td>

                                                    <td><img src="../media/testimonials/<?php echo $testimonialvalue['image']; ?>"
                                                            width="60px"></td>

                                                    <td><span style='cursor:pointer;'><a
                                                                href="update-testimonials"><i
                                                                    class='fal fa-edit'></i></a></span></td>
                                                    <td><a onclick="Deletetestimonial()"><i class="fal fa-trash"
                                                                aria-hidden="true"></i></td>

                                                </tr> -->
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


<!-- Branch ARC edit modal -->

     <div class="modal fade" id="edit_branch_arc" role="dialog"
                        aria-labelledby="exampleModalLabel" aria-hidden="true">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">
                                <div class="modal-header pb-0 edit_header">
                                    <div class="tab_modal_heading">
                                        <h2>Edit Branch ARC Item</h2>
                                    </div>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <div class="modal-body">
                                    <div class="reset_password">
                                        <form id="branch_arc_update">
                                            <div class="row">
                                                <div class="col-lg-12">
                                                    <div class="form-group">
                                                        <label class="form-label" for="Price">Price</label>
                                                        <input type="text" name="arc_price"
                                                            id="arc_price" class="form-control"
                                                            placeholder="Enter Price">
                                                    </div>
                                                </div>
                                                <input type="hidden" id="branch_arc_id" name="branch_arc_id">
                                            </div>

                                            <div class="row justify-content-center mt-3">

                                                <a class="btn-info btn form_submit pl-4 pr-4 pt-2 pb-2 text-white" id="branch_arc_btn"
                                                    onclick="AddUpdateBranch()">Save & Update</a>

                                            </div>

                                        </form>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>

     <!-- Add To cart modal -->

     <div class="modal fade" id="add_to_cart" role="dialog"
                        aria-labelledby="exampleModalLabel" aria-hidden="true">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">
                                <div class="modal-header pb-0 edit_header">
                                    <div class="tab_modal_heading">
                                        <h2>ADD ARC Item to Order</h2>
                                    </div>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <div class="modal-body">
                                    <div class="reset_password">
                                        <form id="add_to_cart_form">
                                            <div class="row">
                                                <div class="col-lg-12">
                                                    <div class="form-group">
                                                        <label class="form-label" for="Price">Product Qty</label>
                                                        <input type="number" name="product_qty"
                                                            id="product_qty" class="form-control"
                                                            placeholder="Enter QTY" min='1' value="1">
                                                    </div>
                                                </div>
                                                <input type="hidden" id="add_arc_id" name="add_arc_id">
                                                <input type="hidden" id="add_arc_category" name="add_arc_category">
                                                <input type="hidden" id="add_arc_price" name="add_arc_price">
                                                <input type="hidden" id="add_branch_id" name="add_branch_id">
                                            </div>

                                            <div class="row justify-content-center mt-3">

                                                <a class="btn-info btn form_submit pl-4 pr-4 pt-2 pb-2 text-white" id="add_to_cart_btn"
                                                    onclick="AddToCart()">Add To Cart</a>

                                            </div>

                                        </form>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div> 

           <!-- upload csv modal             -->

          <div class="modal fade" id="upload_csv" role="dialog"
                        aria-labelledby="exampleModalLabel" aria-hidden="true">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">
                                <div class="modal-header pb-0 edit_header">
                                    <div class="tab_modal_heading">
                                        <h2>Upload Branch ARC CSV </h2>
                                    </div>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <div class="modal-body">
                                    <div class="reset_password">
                                       <form id="uplaod_branch_arc_csv">
                                            <input type="file" class="form-control" name="csvFile" accept=".csv">
                                            

                                            <div class="row justify-content-center mt-3">

                                                <a class="btn-info btn form_submit pl-4 pr-4 pt-2 pb-2 text-white" id="upload_csv_btn"
                                                    onclick="UploadBranchARC_CSV()">Upload</a>

                                            </div>
                                        </form>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>                          

    <?php
    include('../includes/common_modules.php');
    include('../includes/common_scripts.php');
    ?>
    <script src="../js/datagrid/datatables/datatables.bundle.js"></script>
    <script src="../js/modules/branch-arc-item.js"></script>
</body>


</html>