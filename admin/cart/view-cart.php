<?php @session_start(); ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <?php
		include('../controllers/common_controllers.php');
		include('controller/cart_controller.php');
        include('../branch-arc-items/controller/branch_arc_controller.php');
        include('../arc/controller/arc_controller.php');
		$conn = _connectodb();
        setNavigation($_SESSION['Roles']);
		//$total_projects = getTotatProjects($conn,$Enterpriseid);
		?>
    <meta charset="utf-8">
    <title>
        Manage Cart Items - Aryadibusiness
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
    $username = $_SESSION['pb_username'];

    $CorporateID = -1;
    $BranchID = -1;
    if($UserType == "Corporate Admin")
    {
        $CorporateID = $_SESSION['Roles']['CorporateID'];
        $techx_admin = false;
    }
    if($UserType == "Corporate Branch User")
    {
        $CorporateID = $_SESSION['Roles']['CorporateID'];
        $BranchID = $_SESSION['Roles']['BranchID'];
        $techx_admin = false;
    }
    $arc_array = getAllCartItems($conn,$BranchID);
    $all_arc_array = getAllARC($conn);
    $arc_array_key = generateArraywithKey($all_arc_array);
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
                        <li class="breadcrumb-item"><a href="javascript:void(0);">Aryadibusiness</a></li>
                        <li class="breadcrumb-item active">Manage Cart Items</li>

                    </ol>


                    <!-- Page Container -->
                    <div class="row">
                        <div class="col-xl-12">
                            <div id="panel-1" class="panel">
                                <?php if (!empty($arc_array)) {
                                         // code...
                                     ?>
                                <div class="panel-hdr">
                                    <h2>
                                        View Cart Items
                                    </h2>

                                </div>
                                <div class="panel-container show">
                                    <div class="panel-content">

                                     
                                        <!-- datatable start -->
                                    <form id="add_order" method="post">
                                        <table id="view-cart"
                                            class="table table-bordered table-hover table-striped w-100">
                                            <thead>
                                                <tr>
                                                    <th>#</th>
                                                    <th>Product Name</th>
                                                    <th>Price</th>
                                                    <th>QTY</th>
                                                    <!--th>Delete</th-->
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                    $total = 0;
                                                    $Qty = 0;
                                                    $Product = 0;
                                                	$i=1;
                                                	foreach($arc_array as $arc_value)
                                                	{

                                                	$ID  = $arc_value['ID'];
                                                    $total = $total + $arc_value['OrderQTY']*$arc_value['Price'];
                                                    $Qty = $Qty + $arc_value['OrderQTY'];
                                                	?>
                                                <tr>
                                                    <td><?php echo $i; ?></td>
                                                    <td><?php
                                                    if($arc_value['ProductID'] == "")
                                                        {
                                                            echo "NA";
                                                        }
                                                        else
                                                        {
                                                            $ProductID_temp = $arc_value['ProductID'];
                                                            $ProductName = $arc_array_key[$ProductID_temp]['ItemName'];
                                                            echo $ProductName;
                                                        }
                                                     // echo $arc_value['ItemCategories'];

                                                     ?></td>
                                                     <td>
                                                        <span id="price_<?php echo $ID ?>">
                                                           <?php echo $arc_value['Price']; ?> 
                                                        </span>                                                        
                                                     </td>

                                                     <td><?php echo $arc_value['OrderQTY']; ?></td>
                                                    
                                                    <!--td>
                                                        <a onclick="addProduct()"
                                                            class="cursor-pointer"><i class="fal fa-trash"
                                                                aria-hidden="true"></i>
                                                    </td-->
                                                    <input type="hidden" name="BranchID[]" value="<?php echo $arc_value['BranchID']; ?>">
                                                    <input type="hidden" name="ProductID[]" value="<?php echo $arc_value['ProductID']; ?>">
                                                    <input type="hidden" name="CategoriesID[]" value="<?php echo $arc_value['ProductCategory']; ?>">
                                                    <input type="hidden" name="Price[]" value="<?php echo $arc_value['Price']; ?>">
                                                    <input type="hidden" name="OrderQTY[]" value="<?php echo $arc_value['OrderQTY']; ?>">
                                                    <input type="hidden" name="CartID[]" value="<?php echo $arc_value['CartID']; ?>">


                                                </tr>
                                                <?php
                                                	$i++;
                                                    $Product++;
                                                	}
                                                	?>
                                            </tbody>

                                            <tfoot>
                                                
                                            </tfoot>

                                        </table>

                                       

                                        </form>

                                              <div class="d-flex justify-content-end flex-column align-items-end">
                                                <h3 class="mr-3 mb-4"> <b>Order Details :</b> </h3>
                                                 <span class="mr-3"> <b>Product -</b> <?php echo $Product; ?></span>
                                                <br>
                                                <span class="mr-3"> <b>QTY -</b> <?php echo $Qty; ?></span>
                                                <br>
                                                <span class="mr-3"> <b>Total -</b> <?php echo $total; ?></span>
                                                <br>
                                                

                                                <a class="btn btn-primary text-light" id="order_btn" onclick="Order()" >Order</a>
                                              </div>

                                             
                                        <!-- datatable end -->
                                    </div>
                                </div>

                                <?php }else{?> 

                                 <div class="panel-hdr">
                                    <h2>
                                        Cart Is Empty!
                                    </h2>

                                </div>

                            <?php } ?>

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

    <?php
        include('../includes/common_modules.php');
        include('../includes/common_scripts.php');
       ?>
    <script src="../js/datagrid/datatables/datatables.bundle.js"></script>
    <script src="../js/modules/cart.js"></script>

</body>


</html>