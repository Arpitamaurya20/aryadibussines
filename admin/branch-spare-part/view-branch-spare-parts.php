<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <?php
    include('../controllers/common_controllers.php');
    include('controller/branch_spare_part_controller.php');
    include('../manage-categories/controller/categories_controller.php');
    include('../branch/controller/branch_controller.php');
    include('../spare-parts/controller/spare_part_controller.php');
    $UserType = SessionCheck();
    setNavigation($_SESSION['Roles']);
    $conn = _connectodb();

    //$total_projects = getTotatProjects($conn,$Enterpriseid);
    ?>
    <meta charset="utf-8">
    <title>
       All Branch Spare Parts
    </title>
    <meta name="description" content="View Spare Parts">
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
    

    $BranchSparePart = getAllSparePartsByBranchID($conn,$BranchID);

    $Categories = getAllCategories($conn);
    $Categories_array_key = generateArraywithKey($Categories);

    $branch_array = getAllBranchesWithName($conn,"branch");
    $branch_array_key = generateArraywithKey($branch_array);

    $spare_array = getAllSparePart($conn);
    $spare_array_key = generateArraywithKey($spare_array);

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
                        <li class="breadcrumb-item active"> View Branch Spare Parts</li>

                    </ol>


                    <!-- Page Container -->
                    <div class="row">
                        <div class="col-xl-12">
                            <div id="panel-1" class="panel">
                                <div class="panel-hdr">
                                    <h2>
                                        View Branch Spare Parts</span>
                                    </h2>
                                    <?php if($UserType == "Admin"){ ?>
                                    <a href="add-branch-spare-part" class="btn btn-info"
                                        style="margin-right:20px;">Add Branch Spare Parts</a>
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
                                                    <th>Spare Parts ID</th>
                                                    <th>Categories</th>
                                                    <th>Price</th>
                                                    <th>Order</th>
                                                    <?php if($UserType == "Admin"){ ?>
                                                    <th>Edit</th>
                                                    <th>Delete</th>
                                                    <?php } ?>

                                                </tr>
                                            </thead>
                                            <tbody>

                                                <?php

                                                    $i=1;
                                                    foreach($BranchSparePart as $BranchSparePartvalue)
                                                    {

                                                    $id  = $BranchSparePartvalue['ID'];
                                                    ?>
                                                <tr>
                                                    <td><?php echo $i; ?></td>
                                                    <td><?php
                                                    if($BranchSparePartvalue['BranchID'] == "")
                                                        {
                                                            echo "NA";
                                                        }
                                                        else
                                                        {
                                                            $BranchID_temp = $BranchSparePartvalue['BranchID'];
                                                            $BranchName = $branch_array_key[$BranchID_temp]['BranchSite'];
                                                            echo $BranchName;
                                                        }
                                                    ?></td>

                                                    <td><?php
                                                    if($BranchSparePartvalue['SparePartID'] == "")
                                                        {
                                                            echo "NA";
                                                        }
                                                        else
                                                        {
                                                            $ARCID_temp = $BranchSparePartvalue['SparePartID'];
                                                            $SparePartName = $spare_array_key[$ARCID_temp]['SparePart'];
                                                            echo $SparePartName;
                                                        }
                                                      ?></td>

                                                    <td><?php
                                                    if($BranchSparePartvalue['CategoriesID'] == "")
                                                        {
                                                            echo "NA";
                                                        }
                                                        else
                                                        {
                                                            $CategoriesID_temp = $BranchSparePartvalue['CategoriesID'];
                                                            $CategoriesName = $Categories_array_key[$CategoriesID_temp]['CategoriesName'];
                                                            echo $CategoriesName;
                                                        }
                                                     // echo $BranchSparePartvalue['CategoriesID']; 
                                                 ?></td>
                                                    <td><?php echo $BranchSparePartvalue['Price']; ?></td>
                                                    <td><span onclick="" class="badge cursor-pointer badge-primary">Add to Cart</span></td>

                                                    <?php if($UserType == "Admin"){ ?>

                                                    <td>
                                                        <a class="cursor-pointer" onclick="UpdateBranchSparePart(<?php echo $id; ?>)"><i class="fal fa-edit"
                                                                aria-hidden="true"></i>
                                                            </a>

                                                    <td><a onclick="DeleteSparePart(<?php echo $id; ?>)"><i class="fal fa-trash" aria-hidden="true"></i></td>

                                                     <?php } ?>    

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


    <!-- edit modal -->

     <div class="modal fade" id="edit_branch_spare" role="dialog"
                        aria-labelledby="exampleModalLabel" aria-hidden="true">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">
                                <div class="modal-header pb-0 edit_header">
                                    <div class="tab_modal_heading">
                                        <h2>Edit Branch Spare Part</h2>
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
                                                        <input type="text" name="spare_price"
                                                            id="spare_price" class="form-control"
                                                            placeholder="Enter Price">
                                                    </div>
                                                </div>
                                                <input type="hidden" id="branch_spare_id" name="branch_spare_id">
                                            </div>

                                            <div class="row justify-content-center mt-3">

                                                <a class="btn-info btn form_submit pl-4 pr-4 pt-2 pb-2 text-white" id="branch_spare_btn"
                                                    onclick="AddUpdateBranchSpare()">Save & Update</a>

                                            </div>

                                        </form>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>

     <!-- edit modal -->

    <?php
    include('../includes/common_modules.php');
    include('../includes/common_scripts.php');
    ?>
    <script src="../js/datagrid/datatables/datatables.bundle.js"></script>
    <script>
    $(document).ready(function() {
        $("#nav_corporate").addClass("open");
        $("#nav_corporate").addClass("active");
        $("#nav_branch").addClass("active");
    });
    </script>
    <script>
    $(document).ready(function() {

        $('#view-branch-arc').dataTable({
            responsive: true
        });

        $('.js-thead-colors a').on('click', function() {
            var theadColor = $(this).attr("data-bg");
            console.log(theadColor);
            $('#dt-basic-example thead').removeClassPrefix('bg-').addClass(theadColor);
        });

        $('.js-tbody-colors a').on('click', function() {
            var theadColor = $(this).attr("data-bg");
            console.log(theadColor);
            $('#dt-basic-example').removeClassPrefix('bg-').addClass(theadColor);
        });

    });
    </script>
    <script type="text/javascript">
    function DeleteSparePart(delete_id) {
            alertify.confirm('TechXpert ', 'Do you really want to delete Branch Spare Part.', function() {
                    $.post("action/delete_spare_parts.php", {
                            ID: delete_id
                        },
                        function(data, status) {
                            var response = JSON.parse(data);
                            TechXAlert(response.message);
                            if (response.error == false)
                            {
                              setInterval(function(){
                                location.reload();
                              }, 2000);
                            }
                        });

                },
                function() {
                    alertify.error('Deletion Cancelled')
                });
        }

     function UpdateBranchSparePart(ID) {
  
      $.post(
        "action/get_branch_spare_part_details.php",
        {
          ID: ID,
        },
        function (data, status) {
          var response = JSON.parse(data);
          if (response.error == false) {
            var spare_price = response.data.Price;
            var branch_spare_id = response.data.ID;
            $("#spare_price").val(spare_price);
            $("#branch_spare_id").val(branch_spare_id);
            
          }
        }
      );
      $("#edit_branch_spare").modal();
    }

    function AddUpdateBranchSpare() {
 
      document.getElementById("branch_spare_btn").innerHTML ="Please Wait....";

      $.ajax({
        url: "action/update_action.php",
        type: "POST",
        data: $("#branch_arc_update").serialize(),
        success: function (data) {
          var response = JSON.parse(data);
          TechXAlert(response.message);
          if (response.error == false) 
          {
            setInterval(function () {
              location.reload();
            }, 2000);
          }
        },
      });
      return false;
    }   

   
    </script>

</body>


</html>