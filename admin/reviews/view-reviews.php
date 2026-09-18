<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <?php
    include('../controllers/common_controllers.php');
    include('controller/reviews_controller.php');
    $UserType = SessionCheck();
    setNavigation($_SESSION['Roles']);
    $conn = _connectodb();

    //$total_projects = getTotatProjects($conn,$Enterpriseid);
    ?>
    <meta charset="utf-8">
    <title>
        Reviews
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
$reviewsdata = getAllreviews($conn);
$reviewsdata = json_decode($reviewsdata, true);

$servicearray = getservicename($conn);

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
                        <li class="breadcrumb-item active">Reviews</li>

                    </ol>


                    <!-- Page Container -->
                    <div class="row">
                        <div class="col-xl-12">
                            <div id="panel-1" class="panel">
                                <div class="panel-hdr">
                                    <h2>
                                        View Reviews</span>
                                    </h2>
                                    <a href="add-reviews" class="btn btn-info" style="margin-right:20px;">Add</a>
                                </div>
                                <div class="panel-container show">
                                    <div class="panel-content">
                                        <!-- datatable start -->
                                        <table id="view-projects" class="table table-bordered table-hover table-striped w-100">
                                            <thead>
                                                <tr>
                                                    <th>#</th>

                                                    <th>Client Name</th>
                                                    <th>Review</th>
                                                    <th>Image</th>
                                                    <th>Edit</th>
                                                    <th>Delete</th>

                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                $i = 1;
                                                foreach ($reviewsdata as $reviewsvalue) {

                                                    $id  = $reviewsvalue['ID'];
                                                ?>
                                                    <tr id="review_<?= $id ?>">
                                                        <td><?php echo $i; ?></td>


                                                        <td><?php echo $reviewsvalue['client_name']; ?></td>
                                                        <td><?php echo $reviewsvalue['review']; ?></td>

                                                        <td><img src="../media/reviews/<?php echo $reviewsvalue['client_image']; ?>" width="60px"></td>

                                                        <td><span style='cursor:pointer;'><a href="update-reviews?id=<?php echo $id; ?>"><i class='fal fa-edit'></i></a></span></td>

                                                        <td><a onclick="Deletereviews('<?php echo $id; ?>','<?php echo $reviewsvalue['client_image'] ?>')"><i class="fal fa-trash" aria-hidden="true"></i></td>

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

                </main>
                <!-- this overlay is activated only when mobile menu is triggered -->
                <div class="page-content-overlay" data-action="toggle" data-class="mobile-nav-on"></div> <!-- END Page Content -->
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
            $("#js-nav-menu").addClass("active");
            $("#js-nav-menu").addClass("open");
            $("#nav_review").addClass("active");
            $("#nav_site_setting").addClass("active");
        });
    </script>
    <script>
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
    </script>
    <script type="text/javascript">
        function Deletereviews(deleteid, clientImg) {

            //alert(deleteid);
            alertify.confirm('Aryadibusiness ', 'Do you really want to delete Review', function() {
                    $.post("action/delete_reviews.php", {
                            deleteid: deleteid,
                            clientImg: clientImg
                        },
                        function(data, status) {
                            // alert(data);
                            // alert(status);
                            status = status.trim()
                            if (status == 'success') {
                                alertify.alert('Aryadibusiness ', "Review has been Deleted");
                                $('#review_' + deleteid + '').fadeOut();
                            } else {
                                alertify.alert(data);
                            }
                        });

                },
                function() {
                    alertify.error('Deletion Cancelled')
                });
        }
    </script>

</body>


</html>