<?php
include('../controllers/common_controllers.php');
include('controller/service_controller.php');
$conn = _connectodb();
$UserType = SessionCheck();

$APPA = false;
if ($UserType == "") {
    $APPA = true;
}
$Servicedata = getAllServices($conn);
$Servicedata = json_decode($Servicedata, true);

if (isset($_GET['action']) && $_GET['action'] == 'active' && isset($_GET['ID']) && !empty($_GET['ID'])) {
    $Cityquery = "UPDATE services SET status=0 WHERE ID=" . $_GET['ID'] . "";
    $result = mysqli_query($conn, $Cityquery);
    header("location:view-services");
}
if (isset($_GET['action']) && $_GET['action'] == 'deactive' && isset($_GET['ID']) && !empty($_GET['ID'])) {
    $Cityquery1 = "UPDATE services SET status=1 WHERE ID=" . $_GET['ID'] . "";
    $result = mysqli_query($conn, $Cityquery1);
    header("location:view-service");
}

?>

<table id="view-projects" class="table table-bordered table-hover table-striped w-100" width="100%">
    <thead>
        <tr style="text-align: left;">
            <th>#</th>
            <th>Service Name</th>
            <th>Url</th>



        </tr>
    </thead>
    <tbody>
        <?php
        $i = 1;
        foreach ($Servicedata as $Servicevalue) {

            $ID  = $Servicevalue['ID'];
            $status  = $Servicevalue['status'];

            $service_img = $Servicevalue['service_img'];
        ?>
            <tr>
                <td><?php echo $i; ?></td>
                <td><?php echo $Servicevalue['Name']; ?></td>

                <td>https://onepointservices.co.in/service/<?php echo $Servicevalue['ServiceUrl']; ?></td>

            </tr>
        <?php
            $i++;
        }
        ?>
    </tbody>

</table>
<!-- datatable end -->