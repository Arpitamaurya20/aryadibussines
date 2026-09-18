<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <?php
		include('../controllers/common_controllers.php');
		include('controller/site_controller.php');

        $UserType = SessionCheck();
        setNavigation($_SESSION['Roles']);
		$conn = _connectodb();
		//$total_projects = getTotatProjects($conn,$Enterpriseid);
		?>
    <meta charset="utf-8">
    <title>
        Manage Site
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

	$APPA = false;
	if($UserType == "")
	{
		$APPA = true;
	}
	$sitedata = getAllsite($conn);
    $sitedata = json_decode($sitedata,true);

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
                        <li class="breadcrumb-item"><a href="../dashboard/admin_dashboard">TechXpert</a></li>
                        <li class="breadcrumb-item active">Manage Site</li>

                    </ol>


                    <!-- Page Container -->
                    <div class="row">
                        <div class="col-xl-12">
                            <div id="panel-1" class="panel">
                                <div class="panel-hdr">
                                    <h2>
                                    Manage Site</span>
                                    </h2>
                                    <a href="#" onclick="addsite()" class="btn btn-info"
                                        style="margin-right:20px;">Add</a>

                                </div>
                                <div class="panel-container show">
                                    <div class="panel-content">
                                        <!-- datatable start -->
                                        <table id="view-projects"
                                            class="table table-bordered table-hover table-striped w-100">
                                            <thead>
                                                <tr>
                                                    <th>#</th>
                                                    <th>Site Name</th>

                                                    <th>Delete</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                	$i=1;
                                                	foreach($sitedata as $sitevalue)
                                                	{

                                                	$id  = $sitevalue['ID'];
                                                	?>
                                                <tr>
                                                    <td><?php echo $i; ?></td>
                                                    <td><?php echo $sitevalue['site_name']; ?></td>


                                                    <td><a onclick="Deletesite('<?php echo $id;?>')"><i
                                                                class="fal fa-trash" aria-hidden="true"></i></td>
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
                    <div class="modal fade" id="addsite" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
                        aria-hidden="true">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">
                                <div class="modal-header modal_header">
                                    <h5 class="modal-title" id="exampleModalLabel">Add Site </h5>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <div class="modal-body">
                                    <form METHOD="POST" id="add_site">
                                        <div class="form-group">
                                            <label for="exampleInputEmail1">Site Name</label>
                                            <input type="text" class="form-control" name="site_name"
                                                placeholder="Enter Site Name">

                                        </div>

                                        <button type="submit" id="submit" class="btn btn-primary"
                                            onclick="return add_site()">Submit</button>
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
    <script>
    $(document).ready(function() {
        $("#nav_configuration").addClass("active");
        $("#nav_configuration").addClass("open");
        $("#nav_manage_site").addClass("active");
    });

    function addsite() {
        $("#addsite").modal();
    }
    $(document).ready(function() {

        $('#view-projects').dataTable({
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
    function Deletesite(deleteid) {

        //alert(deleteid);
        alertify.confirm('TechXpert ', 'Do you really want to delete Site', function() {
                $.post("action/delete_site.php", {
                        deleteid: deleteid
                    },
                    function(data, status) {
                        // alert(data);
                        // alert(status);
                        status = status.trim()
                        if (status == 'success') {
                            alertify.alert('TechXpert ', "Site has been Deleted");
                            setTimeout(function() {
                                location.href = "view_site.php";
                            }, 2000);
                            /*window.location.assign("user_dashboard.php");*/
                        } else {
                            alertify.alert(data);
                        }
                    });

            },
            function() {
                alertify.error('Deletion Cancelled')
            });
    }

    function add_site() {
  document.getElementById("submit").innerHTML ="Submiting....";
        $.ajax({
            url: "./action/add_site.php",
            type: "POST",
            data: $("#add_site").serialize(),
            success: function(data) {
                var response = JSON.parse(data);
                TechXAlert(response.message);
                jQuery("#add_site")[0].reset();
                if (response.error == false) {
                    setInterval(function() {
                        location.reload();
                    }, 1000);
                }
                return false;
            },
        });
        return false;
    }
    </script>

</body>


</html>