<div class="panel-container show">
    <div class="panel-content p-0">
        <table id="view-projects" class="table table-bordered table-hover table-striped w-100">
            <thead>
                <tr>
                </tr>
            </thead>
            <tbody>
                
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

            </tbody>

        </table>
        <div class="text-right">
            <!--a href="#" class="btn btn-danger" style="margin-right:20px;" data-toggle="modal"
                data-target="#editaccess">Delete</a-->
        </div>
    </div>
</div>

<!-- <div class="comming_soon w-100">
    <img class="w-100" src="../img/coming-soon.png" alt="">
</div> -->