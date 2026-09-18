<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <?php
    include('../controllers/common_controllers.php');
    include('../authentication/auth_controller/authentication_controller.php');
    include('controller/get_quote_enquiry_controller.php');

    $UserType = SessionCheck();
    setNavigation($_SESSION['Roles']);
    $conn = _connectodb();
    ?>
    <meta charset="utf-8">
    <title>
        Quote Enquiries
    </title>
    <meta name="description" content="Quote Enquiries">
    <?php
    include('../includes/common_head_content.php');
    ?>
    <link rel="stylesheet" media="screen, print" href="../css/datagrid/datatables/datatables.bundle.css">
</head>
<?php

$UserType = SessionCheck();
$APPA = false;
if ($UserType == "Admin") {
    $qoute_enquirydata = getAllQuoteenquiry($conn);
    //print_r($qoute_enquirydata);
    $qoute_enquirydata = json_decode($qoute_enquirydata, true);
}

    //$Villagedata = getSingleCFLVillage($conn,$getClfvalue['AssociatedBlock']);
    //$Villagedata = json_decode($Villagedata,true);

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
                        <li class="breadcrumb-item active">View Quote Enquiries </li>

                    </ol>


                    <!-- Page Container -->
                    <div class="row">
                        <div class="col-xl-12">
                            <div id="panel-1" class="panel">
                                <div class="panel-hdr">
                                    <h2>
                                        View Quote Enquiries</span>
                                    </h2>
                                    <?php if($UserType == "Admin" ||$UserType == "Sub Admin"){ ?>
                                    <a href="#" onclick="ExportQuoteEnquiryData()" class="btn btn-info"
                                        style="margin-right:20px;">Export Data</a><?php } ?>
                                </div>
                                <div class="panel-container show">
                                    <div class="panel-content">
                                        <!-- datatable start -->
                                        <table id="view-quote"
                                            class="table table-bordered table-hover table-striped w-100">
                                            <thead>
                                                <tr>
                                                    <th>#</th>
                                                    <th>Name</th>
                                                    <th>Email</th>
                                                    <th>Phone</th>
                                                    <th>Services</th>
                                                    <th>Message</th>

                                                    <th>Date</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                $i = 1;
                                                foreach ($qoute_enquirydata as $qoute_enquirydata) {

                                                    $id  = $qoute_enquirydata['id'];
                                                ?>
                                                <tr>
                                                    <td><?php echo $i; ?></td>

                                                    <td><?php echo $qoute_enquirydata['name']; ?></td>
                                                    <td><?php echo $qoute_enquirydata['email']; ?></td>
                                                    <td><?php echo $qoute_enquirydata['mobile_number']; ?></td>
                                                    <td><?php echo $qoute_enquirydata['service']; ?></td>
                                                    <td><?php echo $qoute_enquirydata['message']; ?></td>
                                                    <td><?php echo $qoute_enquirydata['ondate']; ?></td>
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
        $("#nav_contact").addClass("active");
        $("#nav_contact").addClass("open");
        $("#nav_quote_enquiries").addClass("active");
    });
    </script>
    <script>
    $(document).ready(function() {
        $("#nav_quote_enquiries").addClass("active");
        $('#view-quote').dataTable({
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
    function Deleteenquiry(deleteID) {

        //alert(areaID);
        alertify.confirm('Aryadibusiness ', 'Do you really want to delete Quote enquiry Details', function() {
                $.post("action/delete_enquiry.php", {
                        deleteID: deleteID
                    },
                    function(data, status) {
                        if (data == "success") {
                            alertify.alert('Aryadibusiness ', "enquiry Data has been Deleted");
                            setTimeout(function() {
                                location.href = "view-get-quote-enquiry";
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
    function ExportQuoteEnquiryData() {
        $.ajax({
           url: "action/export_quote_enquiry.php",
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