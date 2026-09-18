<?php session_start(); ?>

<!DOCTYPE html>

<html lang="en">
     <head>
        <title>
                Leave Approval
        </title>
        <?php 
        include('../controllers/common_controllers.php');
        $conn = _connectodb();
        include('../includes/common_head_content.php'); 
        include('controller/employee_controller.php');
        ?>
    </head>

<?php

$EmployeeLeaveArray = getEmployeeAllLeave($conn);

 // if ($_GET['secret']) {
 //    $UserID = $_GET['secret'];
 //    $GetDataTempTable = getDataTempTable($conn,$UserID);
 //    if($GetDataTempTable){
 //        $CretaedDate = $GetDataTempTable['CreatedDate'];
 //        date_default_timezone_set("Asia/Calcutta");
 //        $current_date = date('Y-m-d H:i:s');
 //        $extra_minutes = 30;
 //        $extra_date = date('Y-m-d H:i:s', strtotime($CretaedDate . ' + ' . $extra_minutes . ' minutes'));
 //    }
 // }

?>
    <body>
<div class="page-wrapper">
            <div class="page-inner bg-brand-gradient">
                <div class="page-content-wrapper bg-transparent m-0">                    
                    <div class="height-10 w-100 shadow-lg px-4 bg-brand-gradient">
                        <div class="d-flex align-items-center container p-0">               
                            <div
                                class="d-flex width-mobile-auto m-0 align-items-center justify-content-center p-0 bg-transparent bg-img-none shadow-0 height-9">
                                <a href="javascript:void(0)"
                                    class="page-logo-link press-scale-down d-flex align-items-center">
                                    <span class="page-logo-text mr-1 mt-1" id="logoImg"><img width="px"
                                            src="../img/tech-logo.jpg"></span>
                                </a>
                            </div>
                        </div>
                    </div>
    <div class="row">
        <div class="col-xl-12">
            <table class="table table-bordered table-hover table-striped w-50">
                <thead>
                    <tr>
                        <th>Employee Total Leave</th>
                    <!-- <th><?php echo $employee_data['EmployeeLeave']; ?></th> -->
                    </tr>
                </thead>
            </table>
            <div id="panel-1" class="panel">
                <div class="panel-container show">
                    <!-- <th><?php echo $employee_data['EmployeeLeave']; ?></th> -->
                    <div class="panel-content">
                        <!-- datatable start -->
                        <table id="view-leave-configuration"
                            class="table table-bordered table-hover table-striped w-100">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Type Of Leave</th>
                                    <th>Reason Of Leave</th>
                                    <th>From Date</th>
                                    <th>To Date</th>
                                    <th>Update</th>
                                    <th>Approve</th>
                                    <th>Delete</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                    $i=1;
                                    foreach($EmployeeLeaveArray as $EmpLeave)
                                    {
                                        $EmployeeLeaveId  = $EmpLeave['ID'];
                                        $EmployeeID  = $EmpLeave['EmployeeID'];
                                        $ApproveID  = $EmpLeave['Approved'];
                                        $FromDate  = $EmpLeave['FromDate'];
                                        $ToDate  = $EmpLeave['ToDate'];
                                        // if ($EmployeeID == $ID)
                                        // {
                                ?>
                                <tr>
                                    <td><?php echo $i; ?></td>

                                    <td><?php echo $EmpLeave['TypeOfLeave']; ?></td>

                                    <td><?php echo $EmpLeave['ReasonOfLeave']; ?></td>

                                    <td><?php echo $EmpLeave['FromDate']; ?></td>

                                    <td><?php echo $EmpLeave['ToDate']; ?></td>

                                    <td>
                                        <a onclick="UpdateLeave_modal('<?php echo $EmployeeLeaveId;?>')"
                                            class="cursor-pointer"><i class="fal fa-edit"
                                                aria-hidden="true"></i></a>
                                    </td>

                                    <td>
                                        <?php 
                                            if ($ApproveID==0) {
                                        ?>
                                       <span class="badge badge-danger cursor-pointer">Approved</span>
                                        <?php
                                            } else {
                                        ?>
                                        <a onclick="ApproveLeave('<?php echo $EmployeeLeaveId;?>')">
                                            <span class="badge badge-primary cursor-pointer">Approve</span>
                                        </a>
                                        <?php
                                            }
                                        ?>
                                    </td>

                                    <td>
                                        <a onclick="DeleteLeave('<?php echo $EmployeeLeaveId;?>')"><i class="fal fa-trash" aria-hidden="true"></i></a>
                                    </td>
                                </tr>
                                <?php
                                   $i++;
                                    }
                                // }
                                ?>
                            </tbody>
                        </table>

                    </div>
                </div>

            </div><!-- panel-1 -->
        </div><!-- col-xl-12 -->
    </div> <!-- row -->
</div>
</div>
</div>
<?php
    include('../includes/common_modules.php');
    include('../includes/common_scripts.php');
    ?>
    <script src="../js/datagrid/datatables/datatables.bundle.js"></script>
    <script src="../js/modules/employee.js"></script>
 <!-- <script src="https://code.jquery.com/jquery-3.6.4.min.js" integrity="sha256-oP6HI9z1XaZNBrJURtCoUT5SUnxFr8s3BzRl+cbzUq8=" crossorigin="anonymous"></script> -->
    </body>
</html>

