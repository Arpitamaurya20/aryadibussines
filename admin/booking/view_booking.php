<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <?php
		include('../controllers/common_controllers.php');
		include('controller/booking_controller.php');
        $UserType = SessionCheck();
        setNavigation($_SESSION['Roles']);
		$conn = _connectodb();

		//$total_projects = getTotatProjects($conn,$Enterpriseid);
		?>
    <meta charset="utf-8">
    <title>
        Bookings
    </title>
    <meta name="description" content="View Home Care Bookings">
    <?php
        include('../includes/common_head_content.php');
        ?>
    <link rel="stylesheet" media="screen, print" href="../css/datagrid/datatables/datatables.bundle.css">

</head>
<?php

	$UserType = SessionCheck();
	$bookingdata = getAllBookings($conn,"admin")['data'];

    $filter_param = "?UserType=".$UserType;

	?>

<body class="mod-bg-1 desktop chrome webkit pace-done nav-function-fixed blur">
    <?php include('../js/theme_settings.js'); ?>
    <!-- BEGIN Page Wrapper -->

    <div class="page-wrapper">
        <div class="page-inner">
            <?php include('../navigation/admin_navigation.php');   ?>
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
                        <li class="breadcrumb-item active">Bookings</li>

                    </ol>


                    <!-- Page Container -->
                    <div class="row">
                        <div class="col-xl-12">
                            <div id="panel-1" class="panel">
                                <div class="panel-hdr">
                                    <h2>
                                        View Bookings</span>
                                    </h2>
                                    <?php if($UserType == "Admin" ||$UserType == "Sub Admin"){ ?>
                                    <a href="#" onclick="ExportBookingData()" class="btn btn-info"
                                        style="margin-right:20px;">Export Data</a>
                                     <?php } ?>   
                                </div>
                               <?php
                                include('./ajax/booking-list-view.php')
                                 ?>

                            </div><!-- panel-1 -->
                        </div><!-- col-xl-12 -->
                    </div> <!-- row -->
                    <!-- Datatable Container -->

                </main>
                <script>
                function resume(resume) {
                    var url = "../media/resume/" + resume;
                    window.open(url, 'Documents', ['menubar=yes,scrollbars=yes,controlbox=yes',
                        'top=10,left=150,width=1050,height=650'
                    ]);
                    return;
                }
                </script>
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
            var i = 1;
            $('#view-booking').dataTable({
                responsive: true,
                'processing': true,
                'serverSide': true,
                'ordering': false,
                'serverMethod': 'post',
                'ajax': {
                    'url': 'ajax/booking-list-post.php<?php echo $filter_param; ?>'
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
                        data: 'BookingID'
                    },
                    {
                        data: 'Name'
                    },
                    {
                        data: 'Phone_Email'
                    },
                    {
                        data: 'Service_name'
                    },
                    {
                        data: 'Customer_address'
                    },
                    {
                        data: 'View_Booking'
                    },
                    {
                        data: 'BookingDate'
                    },
                    {
                        data: 'BookingTime'
                    },
                    {
                        data: 'Delete'
                    }
                ]


            });
        });

    $(document).ready(function() {
        $("#nav_booking").addClass("active");
        $("#js-nav-menu").addClass("active");
        $("#js-nav-menu").addClass("open");
        $("#nav_aryadibusiness_bookings").addClass("active");
    });
    </script>
    <script>
    $(document).ready(function() {

        // $('#view-booking').dataTable({
        //     responsive: true
        // });

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
    function Deletebooking(deleteid) {

        //alert(deleteid);
        alertify.confirm('Aryadibusiness ', 'Do you really want to delete booking', function() {
                $.post("action/delete_booking.php", {
                        deleteid: deleteid
                    },
                    function(data, status) {
                        // alert(data);
                        // alert(status);
                        status = status.trim()
                        if (status == 'success') {
                            alertify.alert('Aryadibusiness ', "Booking has been Deleted");
                            setTimeout(function() {
                                location.href = "view_booking";
                            }, 2000);
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


    <script>
    function ViewBookingDetails(BookingID) {
        $.post(
            "../controllers/setSession.php", {
                BookingID: BookingID,
            },
            function(data, status) {
                BasicURLRouter("view-booking-details");
            }
        );
    }
    function ExportBookingData() {
        $.ajax({
          url: "action/export_booking.php",
          type: "POST",
          data: $("#import_form").serialize(),
          success: function (data) {
              window.location.href = "report.xls";
          },
        });
      return false;
    }
    </script>

</body>


</html>