<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <?php
		include('../controllers/common_controllers.php');
		include('controller/holidays_controller.php');
        $UserType = SessionCheck();
        setNavigation($_SESSION['Roles']);
		$conn = _connectodb();
		//$total_projects = getTotatProjects($conn,$Enterpriseid);
		?>
    <meta charset="utf-8">
    <title>
        Manage Holidays - Aryadibusiness
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
    $HolidaysArray = getAllHolidays($conn);
    $regiondata = GetAllRegion($conn);
    $regiondata_array_key = generateArraywithKey($regiondata);

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
                        <li class="breadcrumb-item active">Holidays </li>
                    </ol>

                    <!-- Page Container -->
                    <div class="row">
                        <div class="col-xl-12">
                            <div id="panel-1" class="panel">
                                <div class="panel-hdr">
                                    <h2> View Holidays </h2>
                                    <a href="#" onclick="openholidays_modal()" class="btn btn-info"
                                        style="margin-right:20px;">Add Holiday</a>

                                </div>
                                <div class="panel-container show">
                                    <div class="panel-content">
                                        <!-- datatable start -->
                                        <table id="view-list-of-holidays"
                                            class="table table-bordered table-hover table-striped w-100">
                                            <thead>
                                                <tr>
                                                    <th>#</th>
                                                    <th>Region Name </th>
                                                    <th>Holidays Name </th>
                                                    <th>Holidays Date </th>
                                                    <th>Update</th>
                                                    <th>Delete</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                	$i=1;
                                                	foreach($HolidaysArray as $Holiday)
                                                	{
                                                	   $id  = $Holiday['ID'];
                                                	?>
                                                <tr>
                                                    <td><?php echo $i; ?></td>

                                                    <td><?php 
                                                        if($Holiday['RegionName'] == "")
                                                        echo "Not Set" ;
                                                        else
                                                        {
                                                        $regiondata_details = $regiondata_array_key[$Holiday['RegionName']];
                                                            echo $regiondata_details['RegionName'];
                                                        } 
                                                    ?></td>
                                               
                                                    <td><?php echo $Holiday['HolidaysName']; ?></td>
                                                    <td><?php echo $Holiday['HolidaysDate']; ?></td>
                                                    <td>
                                                        <a onclick="UpdateHolidays_modal('<?php echo $id;?>')"
                                                            class="cursor-pointer"><i class="fal fa-edit"
                                                                aria-hidden="true"></i>
                                                    </td>
                                                    <td>
                                                        <a onclick="DeleteHolidays('<?php echo $id;?>')"><i
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

                    <!-- Modal -->
                    <div class="modal fade" id="add_edit_holidays_modal"  role="dialog"
                        aria-hidden="true">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">
                                <div class="modal-header modal_header">
                                    <h5 class="modal-title">Add Holiday </h5>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <div class="modal-body">
                                    <form METHOD="POST" id="add_update_holidays_form">
                                        <div class="form-group">
                                            <div class="row mt-3">
                                                <div class="col-12">
                                                <label for="holidays_name">Region <span class="text-danger">*</span></label>
                                                <select class="select2 form-control w-100" id="region_name"
                                                    name="region_name">
                                                    <option value="-1">Search & Select</option>
                                                    <?php
                                                        foreach($regiondata as $regionvalue)
                                                        {
                                                            ?>
                                                            <option value="<?php echo $regionvalue['ID'];?>">
                                                                <?php echo $regionvalue['RegionName'];?>
                                                            </option>
                                                            <?php
                                                        }
                                                    ?>
                                                </select>
                                                </div>      
                                            </div>      
                                            <div class="row mt-3">
                                                <div class="col-12">
                                                    <label for="holidays_name">Holiday Name <span class="text-danger">*</span></label>
                                                    <input type="text" class="form-control" name="holidays_name"
                                                        id="holidays_name" placeholder="Enter Holidays ">
                                                </div>
                                            </div>

                                            <div class="row mt-3">
                                                <div class="col-12">
                                                    <label class="form-label" for="date">Holiday Date <span class="text-danger">*</span></label>
                                                    <input type="text" class="form-control col-xl-12 col-sm-12"
                                                        name="holidays_date" id="holidays_date" placeholder="Select Holiday Date" />
                                                </div>

                                            </div>
                                        </div>

                                        <input type="hidden" id="form_action" name="form_action" value="add" />
                                        <input type="hidden" id="form_id" name="form_id" value="-1" />
                                        <button type="submit" id="submit" class="btn btn-primary"
                                            onclick="return AddUpdateHolidays()">Submit</button>
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
    <script src="../js/formplugins/bootstrap-datepicker/bootstrap-datepicker.js"></script>
    <script src="../js/modules/conf-list-of-holidays.js"></script>

</body>


</html>