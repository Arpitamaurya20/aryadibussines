
<div class="panel-container show">
    <div class="panel-content p-0">
        <table id="view-projects" class="table table-bordered table-hover table-striped w-100">
            <thead>
                <tr>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <th>Name </th>
                    <td>
                    <?php echo $bookingdata['Name']; ?>
                    </td>
                </tr>

                <tr>
                    <th>Phone Number</th>
                    <td> <?php echo $bookingdata['Phone']; ?></td>
                </tr>

                <tr>
                    <th>Email </th>
                    <td> <?php echo $bookingdata['Email']; ?></td>
                </tr>
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

                <tr>
                    <th>Transaction ID </th>
                    <td> 
                        <?php 
                            if($bookingdata['TransactionID'] == "")
                                echo "N/A";
                            else
                                echo $bookingdata['TransactionID'];
                        ?>

                          
                    </td>

                </tr>

                <tr>
                    <th>Payment Status </th>
                    <td> 
                        <?php 
                            if($bookingdata['PaymentStatus'] == "")
                                echo "N/A";
                            else
                                echo $bookingdata['PaymentStatus'];
                        ?>

                          
                    </td>

                </tr>

                <tr>
                    <th>Customer Address </th>
                    <td> 
                        <?php 
                            if($bookingdata['Customer_address'] == "")
                                echo "Not Set";
                            else
                                echo $bookingdata['Customer_address'];
                        ?>
                    </td>

                </tr>
                <tr>
                    <th>City Name </th>
                     <td> 
                        <?php 
                            if($bookingdata['City_name'] == "")
                                echo "Not Set";
                            else
                                echo $bookingdata['City_name']; 
                        ?>
                    </td>
                </tr>

                <tr>
                    <th>State Name </th>
                    <td> 
                        <?php 
                            if($bookingdata['State_name'] == "")
                                echo "Not Set";
                            else
                                echo $bookingdata['State_name']; 
                        ?>
                    </td>
                </tr>

                <tr>
                    <th>Location Landmark </th>
                    <td> 
                        <?php 
                            if($bookingdata['Location_landmark'] == "")
                                echo "Not Set";
                            else
                                echo $bookingdata['Location_landmark'];
                        ?>
                    </td>
                </tr>

                <tr>
                    <th>Postal Code </th>
                    <td> 
                        <?php 
                            if($bookingdata['PostalCode'] == "")
                                echo "Not Set";
                            else
                                echo $bookingdata['PostalCode'];
                        ?>
                    </td>
                </tr>
                <tr>
                    <th>Message </th>
                    <td> 
                        <?php 
                            if($bookingdata['Subject'] == "")
                                echo "Not Set";
                            else
                                echo $bookingdata['Subject'];
                        ?>
                    </td>
                </tr>

                <tr>
                    <th>Date </th>
                    <td> 
                        <?php 
                            if($bookingdata['BookingDate'] == "")
                                echo "Not Set";
                            else
                                echo $bookingdata['BookingDate'];
                        ?>
                    </td>

                </tr>

                <tr>
                    <th>Time </th>
                     <td> 
                        <?php 
                            if($bookingdata['BookingTime'] == "")
                                echo "Not Set";
                            else
                                echo $bookingdata['BookingTime'];
                        ?>
                    </td>
                </tr>

            </tbody>

        </table>
        <div class="text-right">
            <!--a href="#" class="btn btn-danger" style="margin-right:20px;" data-toggle="modal"
                data-target="#editaccess">Delete</a-->
        </div>
    </div>
</div>


<?php include("booking-includes/laundry-booking-details.php"); ?>

