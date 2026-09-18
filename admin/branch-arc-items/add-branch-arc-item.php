<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">

<head>

    <?php
	include('../controllers/common_controllers.php');
	include('controller/branch_arc_controller.php');
    include('../manage-categories/controller/categories_controller.php');
    $UserType = SessionCheck();
    setNavigation($_SESSION['Roles']);
	$conn = _connectodb();
	$EnterpriseID = "";
	$UserType = SessionCheck();

	?>
    <meta charset="utf-8">
    <title>
        Add Branch ARC Item
    </title>
    <meta name="description" content="Branch ARC Item">
    <?php
	include('../includes/common_head_content.php');
	?>
    <link rel="stylesheet" media="screen, print"
        href="../css/formplugins/bootstrap-datepicker/bootstrap-datepicker.css">
    <link rel="stylesheet" media="screen, print" href="../css/formplugins/dropzone/dropzone.css">
    <style>
    #categorie_table {
        display: none;
    }
    </style>
</head>
<?php
    $UserType = SessionCheck();
    $conn = _connectodb();
    $username = $_SESSION['pb_username'];

    $BranchID = -1;
    if(isset($_GET['nav']))
    {
        $BranchID = -1;
    }
    else
    {
        if(isset($_SESSION['BranchID']))
        {
            $BranchID = $_SESSION['BranchID'];
        }
    }

    if($UserType == "Corporate Branch User" || $UserType == "Corporate Admin")
    {
        $corporate_user = true;
        $CorporateID = $_SESSION['Roles']['CorporateID'];
    }


$Categories = getAllCategories($conn);

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
                        <li class="breadcrumb-item"><a href="view-branch-arc-items">View Branch ARC Item</a></li>
                        <li class="breadcrumb-item active">Add Branch ARC Item</li>

                    </ol>

                    <!-- Main Creation Form -->
                    <div id="panel-5" class="panel">
                        <div class="panel-hdr">
                            <h2>
                                Add <span class="fw-300"><i>Branch ARC Item</i></span>
                            </h2>
                            <button type="submit" id="save_arc" name="Submit" onclick="AddARCItem()" class="btn btn-primary mr-3">Save</button>

                        </div>
                        <div class="panel-container show">
                            <div class="panel-content">

                                <form id="enterprise_create_form" method="post"
                                    action="action/add_branch_arc_action" enctype="multipart/form-data">


                                    <div class="row mb-3">
                                        <div class="col-12 col-sm-6">
                                            <label for="categoryName">ARC Categories </label>
                                            <select onchange="SelectCategories()" class="select2 form-control w-100"
                                                id="categoryName" name="categoryName">
                                                <option value="-1">Search & Select</option>
                                                <?php

                                                        foreach($Categories as $Categoriesdata)
                                                        {
                                                             $CategoriesID =  $Categoriesdata['ID'];
                                                        ?>
                                                <option value="<?php echo $Categoriesdata['ID'];?>">
                                                    <?php echo $Categoriesdata['CategoriesName'];?>
                                                </option>
                                                <?php
                                                        }
                                                        ?>
                                            </select>
                                        </div>

                                    </div>
                                </form>
                            </div>
                        </div>


                        <div class="row" id="categorie_table">
                            <div class="col-xl-12">
                                <div id="panel-1" class="panel">
                                    <div class="panel-container show">
                                        <div class="panel-content">
                                            <form id="branch_arc_item" method="post"> 


                                            <!-- datatable start -->
                                            <table id="view-branch-arc-items"
                                                class="table table-bordered table-hover table-striped w-100">
                                                <thead>
                                                    <tr>
                                                        
                                                        <th>Item</th>
                                                        <th>Item Code</th>
                                                        <th>Item Description</th>
                                                        <th>Price</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="arc_data">

                                                </tbody>

                                            </table>
                                            <!-- datatable end -->
                                            <input type="hidden" name="BranchID" value="<?php echo $BranchID ?>">
                                            
                                            </form>
                                        </div>
                                    </div>

                                </div><!-- panel-1 -->
                            </div><!-- col-xl-12 -->
                        </div>


                    </div>
                    <input type="hidden" id="active_search" value="" />


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
    <script src="../js/formplugins/bootstrap-datepicker/bootstrap-datepicker.js"></script>
    <script src="../js/formplugins/dropzone/dropzone.js"></script>
    <script>
    $(document).ready(function() {
        $("#nav_corporate").addClass("open");
        $("#nav_corporate").addClass("active");
        $("#nav_branch").addClass("active");
        $("#categoryName").select2();

        $('#view-branch-arc-items').dataTable({
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




    function SelectCategories() {
        $.post("action/get_arc_item.php", {
                CategoryID: $("#categoryName").val()
            },
            function(data, status) {
                document.getElementById("categorie_table").style.display = "block";
                document.getElementById("arc_data").innerHTML = data;
            });
    }

    function oncheckchange(arc_id) {
        var arc_item_id = "arc_item_" + arc_id;
        var arc_tem = document.getElementById(arc_item_id);

        var arc_price_id = "price_" + arc_id;
        var arc_price = document.getElementById(arc_price_id);

        if (arc_tem.checked == true) {
            $(arc_price).removeAttr('readonly');
        } else {
            $(arc_price).prop("readonly", true);
        }

    }

    function AddARCItem() {

      document.getElementById("save_arc").innerHTML ="Saving....";

      $.ajax({
        url: "action/add_arc_item.php",
        type: "POST",
        data: $("#branch_arc_item").serialize(),
        success: function (data) {
          var response = JSON.parse(data);
          TechXAlert(response.message);
          if (response.error == false) 
          {
            setInterval(function () {
              window.location.href = "./view-branch-arc-items";
            }, 2000);
          }
        },
      });
      return false;
    }


    </script>

    </script>
</body>


</html>