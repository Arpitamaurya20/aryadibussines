<?php @session_start(); ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <?php
		include('../controllers/common_controllers.php');
		include('controller/order_controller.php');
        include('../arc/controller/arc_controller.php');
		$conn = _connectodb();
        setNavigation($_SESSION['Roles']);
		//$total_projects = getTotatProjects($conn,$Enterpriseid);
		?>
    <meta charset="utf-8">
    <title>
        Manage Order Items - Aryadibusiness
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
    $BranchID = -1;
    if(isset($_SESSION['BranchID']))
    {
        $BranchID = $_SESSION['BranchID'];
    }

    // if($BranchID == -1)
    // {
    //     // check for corporate admin 
    //     if(isset($_SESSION['Roles']['CorporateID']))
    //     {
    //         $CorporateID = $_SESSION['Roles']['CorporateID'];
    //     }
    // }
    // if($BranchID == -1 && $CorporateID != -1)
    // {
    //     $arc_array = getAllCorporateOrderItems($conn,$CorporateID);
    // }
    // else
    // {
        
    //     $arc_array = getAllOrderItemsDetails($conn,$BranchID);
        
    // }
    $OrderID = -1;
    if(isset($_SESSION['OrderID']))
    {
        $OrderID = $_SESSION['OrderID'];
    }
    // echo $OrderID;
    // echo $BranchID;
    // die();

    $arc_array = getAllOrderItemsDetails($conn,$OrderID);

    $ARC = getAllARC($conn);
    $arc_array_key = generateArraywithKey($ARC);


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
                        <li class="breadcrumb-item active">Manage Order Items</li>

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
                                        View Order Items
                                    </h2>

                                </div>

                                

                                <div class="panel-container show">
                                    <div class="panel-content">
                                        <!-- datatable start -->
                                        <table id="view-order"
                                            class="table table-bordered table-hover table-striped w-100">
                                            <thead>
                                                <tr>
                                                    <th>#</th>
                                                    <th>OrderID</th>
                                                    <th>Product Name</th>
                                                    <th>Price</th>
                                                    <th>QTY</th>
                                                    <th>Status</th>
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
                                                    <td>ORD-<?php echo $arc_value['OrderID']; ?></td>
                                                    <td><?php
                                                    if($arc_value['ProductID'] == "")
                                                        {
                                                            echo "NA";
                                                        }
                                                        else
                                                        {
                                                            $ProductID_temp = $arc_value['ProductID'];
                                                            if(isset($arc_array_key[$ProductID_temp])) {
                                                                $ProductName = $arc_array_key[$ProductID_temp]['ItemName'];
                                                                echo $ProductName;
                                                            } else {
                                                                echo "Product ID " . htmlspecialchars($ProductID_temp) . " Not Found";
                                                            }
                                                        }
                                                     // echo $arc_value['ItemCategories'];

                                                     ?></td>
                                                     

                                                     <td>
                                                        <span id="price_<?php echo $ID ?>">
                                                           <?php echo $arc_value['Price']; ?> 
                                                        </span>                                                        </td>
                                                     <td><?php echo $arc_value['OrderQTY']; ?></td>
                                                    
                                                    <td>
                                                         <a>
                                                            <span class="badge badge-primary cursor-pointer"><?php echo $arc_value['Status']; ?></span>
                                                        </a>
                                                    </td>


                                                </tr>
                                                <?php
                                                	$i++;
                                                	}
                                                	?>
                                            </tbody>

                                            <tfoot>
                                                
                                            </tfoot>

                                        </table>

                                       
                                        <!-- datatable end -->
                                    </div>
                                </div>

                            <?php }else{ ?>

                                <div class="panel-hdr">
                                    <h2>
                                        Order not found
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
    <script src="../js/modules/order.js"></script>

</body>


</html>