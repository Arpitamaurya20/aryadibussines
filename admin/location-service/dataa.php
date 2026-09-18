<?php
include('../controllers/common_controllers.php');
include('../authentication/auth_controller/authentication_controller.php');
include('controller/location_service_controller.php');
$conn = _connectodb();
?>

<?php


$location_servicedata = getAlllocation_service($conn);
$location_servicedata = json_decode($location_servicedata, true);

$cityarray = getcityarray($conn);
$servicearray = getservicearray($conn);


?>

<table id="view-projects" class="table table-bordered table-hover table-striped w-100" style="width: 100%;">
    <thead>
        <tr style="text-align: left;">
            <th>#</th>
            <th>Title</th>
            <th>Service</th>
            <th>URL</th>
           
        </tr>
    </thead>
    <tbody>
        <?php
        $i = 1;
        foreach ($location_servicedata as $location_servicedata) {


            $id  = $location_servicedata['id'];
        ?>
            <tr>
                <td><?php echo $i++; ?></td>
                <td><?php echo $location_servicedata['title']; ?></td>
                <td> <?php
                        $Name = $location_servicedata['service'];
                        echo  $servicearray[$Name] ?></td>
                <td>https://onepointservices.co.in/city-service/<?php echo $location_servicedata['url']; ?></td>



            </tr>
        <?php
        }
        $i++;

        ?>
    </tbody>

</table>