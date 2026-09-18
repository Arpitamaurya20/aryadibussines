<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <?php
		include('../controllers/common_controllers.php');
		include('controller/state_controller.php');
        include('../employees/controller/employee_controller.php');
        include('../region/controller/region_controller.php');
        
        setNavigation($_SESSION['Roles']);
        $UserType = SessionCheck();
		$conn = _connectodb();
		//$total_projects = getTotatProjects($conn,$Enterpriseid);
		?>
    <meta charset="utf-8">
    <title>
        Manage States - Aryadibusiness
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
	$StateArray = getAllStates($conn);
    $RegionsArray = getAllRegions($conn);

    $employee_array = getFilteredEmployeeArray($conn,"Employee");
    $emp_array_key = generateArraywithKey($employee_array);

    $region_array_key = generateArraywithKey($RegionsArray);

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
                        <li class="breadcrumb-item active">States</li>

                    </ol>


                    <!-- Page Container -->
                    <div class="row">
                        <div class="col-xl-12">
                            <div id="panel-1" class="panel">
                                <div class="panel-hdr">
                                    <h2>
                                       View All States</span>
                                    </h2>
                                    <a href="#" onclick="open_state_modal()" class="btn btn-info"
                                        style="margin-right:20px;">Add</a>

                                </div>

                                <div class="panel-container show">
                                    <div class="panel-content">

                                        <table id="view-states" class="table table-bordered table-hover table-striped w-100">
                                            <thead>
                                                <tr>
                                                    <th>#</th>
                                                    <th> State Name </th>
                                                    <th> Region </th>
                                                    <th> State Head </th>
                                                    <th> State Corporate Head </th>
                                                    <th>Update</th>
                                                    <th>Delete</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                    $i=1;
                                                    foreach($StateArray as $state)
                                                    {

                                                    $id  = $state['ID'];
                                                    ?>
                                                <tr>
                                                    <td><?php echo $i; ?></td>
                                                    <td><?php echo $state['StateName']; ?></td>
                                                    <td>
                                                        <?php
                                                            if($state['RegionID'] == -1)
                                                                echo "Not Set" ;
                                                            else
                                                            {
                                                                $region_details = $region_array_key[$state['RegionID']];
                                                                echo $region_details['RegionName'];
                                                            }
                                                        ?>
                                                    </td>
                                                    <td>
                                                        <?php
                                                            if($state['StateHead'] == -1)
                                                                echo "Not Set" ;
                                                            else
                                                            {
                                                                $emp_details = $emp_array_key[$state['StateHead']];
                                                                echo $emp_details['Name'];
                                                            }
                                                        ?>
                                                        </td>

                                                        <td>
                                                        <?php
                                                            if($state['StateCorporateHead'] == -1)
                                                                echo "Not Set" ;
                                                            else
                                                            {
                                                                $emp_details = $emp_array_key[$state['StateCorporateHead']];
                                                                echo $emp_details['Name'];
                                                            }
                                                        ?>
                                                        </td>
                                                    <td>
                                                        <a onclick="UpdateState_modal('<?php echo $id;?>')" class="cursor-pointer"><i class="fal fa-edit" aria-hidden="true"></i></a>
                                                        </td>
                                                    <td><a onclick="DeleteState('<?php echo $id;?>')"><i
                                                                class="fal fa-trash" aria-hidden="true"></i></a>
                                                            </td>
                                                </tr>
                                                <?php
                                                    $i++;
                                                    }
                                                    ?>
                                            </tbody>

                                        </table>


                                    </div>
                                </div>

                             

                            </div><!-- panel-1 -->
                        </div><!-- col-xl-12 -->
                    </div> <!-- row -->
                    <!-- Datatable Container -->


                    <!-- Modal -->
                    <div class="modal fade" id="add_edit_state_modal" role="dialog" aria-hidden="true">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">
                                <div class="modal-header modal_header">
                                    <h5 class="modal-title" id="state_modal_title">Add State </h5>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <div class="modal-body">
                                    <form METHOD="POST" id="add_update_state_form">
                                        <div class="form-group">
                                            <div class="row">
                                                <div class="col-12">
                                                    <label> State Name</label>
                                                    <input type="text" class="form-control" name="state_name"
                                                        placeholder="Enter State Name" id="state_name">
                                                </div>
                                            </div>
                                            <div class="row mt-3">
                                                <div class="col-12">
                                                    <label> State Head</label>
                                                    <select class="select2 form-control w-100" id="state_head"
                                                        name="state_head">
                                                        <option value="-1">Search & Select</option>
                                                        <?php

                                                        foreach($employee_array as $employee)
                                                        {
                                                            ?>
                                                        <option value="<?php echo $employee['ID'];?>">
                                                            <?php echo $employee['Name'];?>
                                                        </option>
                                                        <?php
                                                        }
                                                        ?>
                                                    </select>
                                                </div>
                                            </div>

                                            <div class="row mt-3">
                                                <div class="col-12">
                                                    <label> State Corporate Head</label>
                                                    <select class="select2 form-control w-100" id="state_corporate"
                                                        name="state_corporate_head">
                                                        <option value="-1">Search & Select</option>
                                                        <?php

                                                        foreach($employee_array as $employee)
                                                        {
                                                            ?>
                                                        <option value="<?php echo $employee['ID'];?>">
                                                            <?php echo $employee['Name'];?>
                                                        </option>
                                                        <?php
                                                        }
                                                        ?>
                                                    </select>
                                                </div>
                                            </div>

                                            <div class="row mt-3">
                                                <div class="col-12">
                                                    <label> Region</label>
                                                    <select class="select2 form-control w-100" id="region"
                                                        name="region">
                                                        <option value="-1">Search & Select</option>
                                                        <?php

                                                        foreach($RegionsArray as $region)
                                                        {
                                                            ?>
                                                        <option value="<?php echo $region['ID'];?>">
                                                            <?php echo $region['RegionName'];?>
                                                        </option>
                                                        <?php
                                                        }
                                                        ?>
                                                    </select>
                                                </div>
                                            </div>


                                        </div>

                                        <button type="submit" class="btn btn-primary"
                                            onclick="return AddUpdateState()">Submit</button>
                                        <input type="hidden" id="form_action" name="form_action" value="add" />
                                        <input type="hidden" id="form_id" name="form_id" value="-1" />
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
    <script src="../js/modules/conf-state.js"></script>
    

</body>


</html>