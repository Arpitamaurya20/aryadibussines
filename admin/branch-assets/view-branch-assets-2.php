<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <?php
		include('../controllers/common_controllers.php');
		include('controller/branch_assets_controller.php');
        include('../branch/controller/branch_controller.php');
        include('../manage-uom/controller/uom_controller.php');
        include('../manage-sub-categories/controller/sub_categories_controller.php');
        include('../manage-categories/controller/categories_controller.php');
		$conn = _connectodb();
        setNavigation($_SESSION['Roles']);
		//$total_projects = getTotatProjects($conn,$Enterpriseid);
		?>
    <meta charset="utf-8">
    <title>
        Manage Branch Assets - TechXpert
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
<script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-6496901533964255"
     crossorigin="anonymous"></script>
<?php
    $UserType = SessionCheck();

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

	$BranchAssetArray = getAllBranchAssetsList($conn,$BranchID);

    $branch_array = getAllBranchesWithName($conn,"branch");
    $branch_array_key = generateArraywithKey($branch_array);
    $uofdata = getAllUOM($conn);
    $CategorieData = getAllCategories($conn);
    $SubCategorieData = getAllSubCategories($conn);
    $CreatedBy = $_SESSION['pb_username'];

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
                            <li class="breadcrumb-item"><a href="javascript:void(0);">TechXpert</a></li>
                            <li class="breadcrumb-item"><a href="../company/view-company">Manage Corporate</a></li>
                            <li class="breadcrumb-item"><a href="../branch/view-branch">Manage Branch</a></li>
                            <li class="breadcrumb-item active">Manage Branch Assets</li>
                        </ol>
                        <?php
                                    if($UserType == "Admin"){
                                    ?>

                        <a href="#" onclick="DownloadAssetsFileFormat()" class="btn btn-success"
                            style="margin-right:20px;">Download Template for Bulk Upload</a>
                        <?php } ?>
                    </div>


                    <!-- Page Container -->
                    <div class="row">
                        <div class="col-xl-12">
                            <div id="panel-1" class="panel">
                                <div class="panel-hdr">
                                    <h2>
                                        View Branch Assets
                                    </h2>
                                    <?php
                                    if($UserType == "Admin"){
                                    ?>
                                    <a href="#" onclick="ExportBranchAssetsData()" class="btn btn-info" style="margin-right:20px;">Export Data</a>
                                    <a href="#" onclick="openBranch_modal()" class="btn btn-info"
                                        style="margin-right:20px;">Add Branch Assets</a>
                                    <?php } ?>
                                </div>
                                <?php
                                 include('./include/branch-assets-list-view.php')
                                 ; ?>

                            </div><!-- panel-1 -->
                        </div><!-- col-xl-12 -->
                    </div> <!-- row -->
                    <!-- Datatable Container -->


                    <!-- Modal -->
                    <div class="modal fade" id="add_edit_branch_assets_modal" role="dialog" aria-hidden="true">
                        <div class="modal-dialog modal-lg" role="document">
                            <div class="modal-content">
                                <div class="modal-header modal_header">
                                    <h5 class="modal-title" id="branch_modal_title"> </h5>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <div class="modal-body">
                                    <form METHOD="POST" id="add_update_branch_assets_form">
                                        <div class="form-group">
                                            <div class="row">
                                                <div class="col-4 mt-3">
                                                    <label>Branch <span class="text-danger">*</span></label>
                                                    <select class="select2 form-control w-100" id="branch_id"
                                                        name="branch_id">
                                                        <option value="-1">Search & Select</option>
                                                        <?php

                                                        foreach($branch_array as $branch)
                                                        {
                                                            $selected = "";
                                                            if($BranchID == $branch['ID'])
                                                                $selected = "selected";
                                                            else
                                                                continue;
                                                        ?>
                                                        <option value="<?php echo $branch['ID'];?>"
                                                            <?php echo $selected; ?>>
                                                            <?php echo $branch['BranchSite'];?>
                                                        </option>
                                                        <?php
                                                        }
                                                        ?>
                                                    </select>
                                                </div>
                                                <div class="col-4 mt-3">
                                                    <label>Name of Equipment<span class="text-danger">*</span> </label>
                                                    <input type="text" class="form-control" name="equipment_name"
                                                        id="equipment_name" placeholder="Enter Equipment Name">
                                                </div>

                                                <div class="col-4 mt-3">
                                                    <label>Make </label>
                                                    <input type="text" class="form-control" name="make" id="make"
                                                        placeholder="Enter Make">
                                                </div>

                                                <div class="col-4 mt-3">
                                                    <label>Model </label>
                                                    <input type="text" class="form-control" name="model" id="model"
                                                        placeholder="Enter Model">
                                                </div>

                                                <div class="col-4 mt-3">
                                                    <label> SNo </label>
                                                    <input type="text" class="form-control" name="serial_no"
                                                        id="serial_no" placeholder="Enter SNo">
                                                </div>
                                                <div class="col-4 mt-3">
                                                    <label>Capacity <span class="text-danger">*</span></label>
                                                    <input type="number" class="form-control" name="capacity"
                                                        id="capacity" placeholder="Enter Capacity">
                                                </div>


                                                <div class="col-4 mt-3">
                                                    <label> Qty <span class="text-danger">*</span></label>
                                                    <input type="number" onkeyup="MultiplyQty_Unit()"
                                                        class="form-control" name="quantity" id="quantity"
                                                        placeholder="Enter Qty">
                                                </div>

                                                <div class="col-4 mt-3">
                                                    <label> UoM </label>
                                                    <select class="select2 form-control w-100" id="uom" name="uom">
                                                        <option value="-1">Search & Select</option>
                                                        <?php

                                                        foreach($uofdata as $uofdata)
                                                        {
                                                        ?>
                                                        <option value="<?php echo $uofdata['ID'];?>">
                                                            <?php echo $uofdata['UOMName'];?>
                                                        </option>
                                                        <?php
                                                        }
                                                        ?>
                                                    </select>
                                                </div>
                                                <div class="col-4 mt-3">
                                                    <label> Unit Rate</label>
                                                    <input type="text" class="form-control" name="unit_rate"
                                                        id="unit_rate" onkeyup="MultiplyQty_Unit()"
                                                        placeholder="Enter Unit Rate">
                                                </div>

                                                <div class="col-4 mt-3">
                                                    <label>Amount</label>
                                                    <input type="text" class="form-control" name="amount" id="amount"
                                                        placeholder="Enter Amount">
                                                </div>

                                                <div class="col-4 mt-3">
                                                    <label>Manufacturing Year</label>
                                                    <br>
                                                    <input type="text" class="form-control" name="manufacturing_year"
                                                        id="manufacturing_year" placeholder="Enter Manufacturing Year">
                                                </div>



                                                <div class="col-4 mt-3">
                                                    <label> Age of Equipment ( years )<span
                                                            class="text-danger">*</span></label>
                                                    <input type="text" class="form-control" name="equipment_age"
                                                        id="equipment_age" placeholder="Enter Equipment Age">
                                                </div>

                                                <div class="col-4 mt-3">
                                                    <label> Type of Services</label>
                                                    <select class="select2 form-control w-100" id="service_type"
                                                        name="service_type">
                                                        <option value="-1">Search & Select</option>
                                                        <option value="TR">CAMC</option>
                                                        <option value="NCAMC">NCAMC</option>
                                                        <option value="NCMC">NCMC</option>
                                                    </select>
                                                </div>

                                                <div class="col-4 mt-3">
                                                    <label> Category</label>
                                                    <select onchange="SelectBranchAssetsCategories()" class="select2 form-control w-100" id="categories"
                                                        name="categories">
                                                        <option value="-1">Search & Select</option>
                                                        <?php

                                                        foreach($CategorieData as $CategorieValue)
                                                        {
                                                        ?>
                                                        <option value="<?php echo $CategorieValue['ID'];?>">
                                                            <?php echo $CategorieValue['CategoriesName'];?>
                                                        </option>
                                                        <?php
                                                        }
                                                        ?>
                                                    </select>
                                                </div>

                                                <div class="col-4 mt-3" id="sub_categories_div" style="display:none;">
                                                    <label>Sub Category </label>
                                                    <select class="select2 form-control w-100" id="sub_categories"
                                                        name="sub_categories">
                                                        <option value="-1">Search & Select</option>
                                                        <?php

                                                        foreach($SubCategorieData as $SubCategorieValue)
                                                        {
                                                        ?>
                                                        <option value="<?php echo $SubCategorieValue['ID'];?>">
                                                            <?php echo $SubCategorieValue['SubCategoriesName'];?>
                                                        </option>
                                                        <?php
                                                        }
                                                        ?>
                                                    </select>
                                                </div>

                                                <div class="col-4 mt-3">
                                                    <label> TAT ( In Days )</label>
                                                    <input type="text" class="form-control" id="tat" name="tat"
                                                        placeholder="Enter TAT" />

                                                </div>
                                                <div class="col-4 mt-3">
                                                    <label> AMC Start Date </label>
                                                    <input type="text" class="form-control" name="amc_start_date"
                                                        id="amc_start_date" placeholder="Pick AMC Start Date">
                                                </div>
                                                <div class="col-4 mt-3">
                                                    <label> AMC End Date </label>
                                                    <input type="text" class="form-control" name="amc_end_date"
                                                        id="amc_end_date" placeholder="Pick AMC End Date">
                                                </div>


                                                <div class="col-4 mt-3">
                                                    <label> SOW </label>
                                                    <div class="custom-file">
                                                        <input type="file" id="sow_img" name="sow_img"
                                                            class="form-control">
                                                    </div>

                                                </div>

                                                <div class="col-4 mt-3">
                                                    <label> Floor Number</label>
                                                    <input type="text" class="form-control" name="floor_number"
                                                        id="floor_number" placeholder="Enter Floor Number">
                                                </div>

                                                <div class="col-4 mt-3">
                                                    <label> Equipment Location</label>
                                                    <input type="text" class="form-control" id="equipment_location"
                                                        name="equipment_location"
                                                        placeholder="Enter Equipment Location" />

                                                </div>


                                                <div class="col-8 mt-3">
                                                    <label> Description</label>
                                                    <textarea class="form-control" name="description" id="description"
                                                        placeholder="Enter Description"></textarea>
                                                </div>


                                            </div>

                                        </div>
                                        <input type="hidden" id="form_action" name="form_action" value="add" />
                                        <input type="hidden" id="form_id" name="form_id" value="-1" />
                                        <button type="submit" class="btn btn-primary" id="branch_assets_btn"
                                            onclick="return AddUpdateBranchAssets()">Submit</button>
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


    <!-- Modal -->
    <div class="modal fade" id="raiseamcticket" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header modal_header">
                    <h5 class="modal-title">Raise AMC Ticket</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form METHOD="POST" id="raise_amc_tickets">
                        <div class="form-group">
                            <div class="row">
                                <div class="col-12">
                                    <label for="message">Type your Message <span class="text-danger">*</span> </label>
                                    <br>
                                    <textarea name="Message" id="message" class="form-control w-100" cols="30" rows="5"
                                        placeholder="Type your message"></textarea>
                                </div>

                            </div>


                        </div>
                        <!--input type="hidden" name="CorporateID" id="corporate_modal_id" value=""-->
                        <input type="hidden" name="BranchID" id="branch_modal_id" value="" >
                        <input type="hidden" name="BranchAssetID" id="branch_asset_modal_id" value="">
                        <input type="hidden" name="Type" value="AMC">
                        <input type="hidden" name="CreatedBy" id="created_by_modal" value="">
                        <button type="submit" class="btn btn-primary" onclick="return RaiseAMCTickets()">Submit</button>
                    </form>
                </div>

            </div>
        </div>
    </div>

    <?php
        include('../includes/common_modules.php');
        include('../includes/common_scripts.php');
       ?>
    <script src="../js/datagrid/datatables/datatables.bundle.js"></script>
    <script src="../js/modules/branch-assets.js"></script>
    <script src="../js/formplugins/bootstrap-datepicker/bootstrap-datepicker.js"></script>
     <script>
        $(document).ready(function() {
            var i = 1;
            $('#view-branch-assets').dataTable({
                responsive: true,
                'processing': true,
                'serverSide': true,
                'ordering': false,
                'serverMethod': 'post',
                'ajax': {
                    'url': 'include/branch-assets-list-post.php'
                },
                'columnDefs': [{
                    "targets": [0],
                    "className": "text-center"
                }],
                "order": [
                    [1, 'asc']
                ],
                'columns': [{
                        "data": "id",
                        render: function(data, type, row, meta) {
                            return meta.row + meta.settings._iDisplayStart + 1;
                        }
                    },
                    {
                        data: 'Equipment Name'
                    },
                    {
                        data: 'Make / Model'
                    },
                    {
                        data: 'ManufacturingYear'
                    },
                    {
                        data: 'Service Type'
                    },
                    {
                        data: 'FloorNumber / EquipmentLocation'
                    },
                    {
                        data: 'PPM'
                    },
                    {
                        data: 'AMC Ticket'
                    },
                    {
                        data: 'Update'
                    },
                    {
                        data: 'Action'
                    }


                ]


            });
        });

    </script>

</body>


</html>