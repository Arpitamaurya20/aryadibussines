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
?>

<div class="modal fade bd-example-modal-lg" id="laundry_booking_details" role="dialog"
    aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header" style="background-color: #027dc1;color: #fff;">
                <div class="tab_modal_heading">
                    <h2>Laundry Booking Details </h2>
                </div>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"
                    style="opacity: 1;color: #fff;">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="booking_assignment_form">
                    <div id="wizard">
                        <div class="row">
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

                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>