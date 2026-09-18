<?php
$laundry_booking_array = getLaundryBookingByBookingID($conn,$ID);
// print_r($laundry_booking_array);
// echo $ID;
// die();

$where = " where 1";
$laundry_sub_service_array_temp = _getTableRecords($conn,'laundry_sub_service',$where);
$laundry_sub_service_array = array();
foreach($laundry_sub_service_array_temp as $Laundry_service)
{
    $Laundry_service_ID = $Laundry_service['ID'];
    $laundry_sub_service_array[$Laundry_service_ID] = $Laundry_service['TypeOfClothes'];
}

$where = " where 1";
$subservice_array_temp = _getTableRecords($conn,'subservice',$where);
$subservice_array = array();
foreach($subservice_array_temp as $Sub_service)
{
    $Sub_service_ID = $Sub_service['ID'];
    $subservice_array[$Sub_service_ID] = $Sub_service['title'];
}
if($bookingdata['Service_name'] == "Laundry & Dry Cleaning"){
    if (!empty($laundry_booking_array)) {
?>

<table class="table table-bordered">
    <thead>
        <tr>
            <th scope="col">#</th>
            <th scope="col">Sub Service</th>
            <th scope="col">Type Of Clothes</th>
            <th scope="col">Quantity</th>
            <th scope="col">Price</th>
        </tr>
    </thead>


    <tbody>
        <?php
            $i = 1;
            foreach($laundry_booking_array as $laundry_booking)
            {      
        ?>
        <tr>
            <td><?php echo $i; ?></td>
            <td><?php echo $subservice_array[$laundry_booking['SubServiceID']]; ?></td>
            <td><?php echo $laundry_sub_service_array[$laundry_booking['TypeofClothesID']]; ?></td>
            <td><?php echo $laundry_booking['Quantity']; ?></td>
            <td><?php echo $laundry_booking['Price']; ?></td>

        </tr>
        <?php
            $i++;
            }
        ?>
    </tbody>
</table>

<?php 
    }
}else{
?>


<table class="table table-bordered table-hover table-striped w-100">
    
    <tbody>
        <tr>
            <th>Service Name </th>
            <td>
                <?php 
                            if($bookingdata['Service_name'] == "")
                                echo "Not Set";
                            else
                                echo $bookingdata['Service_name'];
                        ?>
            </td>
        </tr>

        <tr>
            <th>Sub Service Name </th>
            <td>
                <?php 
                            if($bookingdata['SubService'] == "")
                                echo "Not Set";
                            else
                                echo $bookingdata['SubService'];
                        ?>


            </td>

        </tr>

        <tr>
            <th>Total Amount </th>
            <td>
                <?php 
                            if($bookingdata['Price'] == "")
                                echo "N/A";
                            else
                                echo $bookingdata['Price'];
                        ?>


            </td>

        </tr>

        <tr>
            <th>Status</th>
            <td>
                <?php 
                            if($bookingdata['Status'] == "")
                                echo "N/A";
                            else
                                echo $bookingdata['Status'];
                        ?>


            </td>

        </tr>


    </tbody>

</table>


<?php } ?>