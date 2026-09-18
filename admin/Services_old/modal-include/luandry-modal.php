<?php
$Servicedata = getAllServices($conn);
$Servicedata = json_decode($Servicedata, true);

$SubServicedata = getAllSubServicesBySeiviceID($conn,$ID);
$SubServicedata = json_decode($SubServicedata, true);

$GetLuaundryData = getAllLuandryServices($conn,$ID);
?>


<!-- Modal -->
<div class="modal fade" id="luandryservice" tabindex="-1" aria-labelledby="luandryservicelLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header modal_header">
                <h5 class="modal-title" id="luandryservicelLabel">Add Type of Service and Price</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form onsubmit="return false;" id="luandryservice_form">
                    <div class="form-group">
                        <div class="row">
                            <div class="col-6 mt-3">
                                <label>Service <span class="text-danger">*</span> </label>
                                <select class="select2 form-control w-100" id="Service_id" name="Service_id">
                                    <?php

                                        foreach($Servicedata as $Service_value)
                                        {

                                            if($Service_value['ID'] == $ID){
                                                // continue;
                                            
                                            
                                        ?>
                                    <option value="<?php echo $Service_value['ID'];?>">
                                        <?php echo $Service_value['Name'];?>
                                    </option>
                                    <?php
                                    }
                                        }
                                        ?>
                                </select>
                            </div>

                            <div class="col-6 mt-3">
                                <label>Sub Service <span class="text-danger">*</span> </label>
                                <select class="select2 form-control w-100" id="Sub_Service_id" name="Sub_Service_id">
                                    <?php

                                        foreach($SubServicedata as $Sub_Service_value)
                                        {
                                            
                                        ?>
                                    <option value="<?php echo $Sub_Service_value['ID'];?>">
                                        <?php echo $Sub_Service_value['title'];?>
                                    </option>
                                    <?php
                                        }
                                        ?>
                                </select>
                            </div>

                            <div class="col-6 mt-3">
                                <label>Type Of Clothes <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="Type_of_clothes" id="Type_of_clothes"
                                    placeholder="Enter Clothes Name">
                            </div>

                            <div class="col-6 mt-3">
                                <label>Price ( as per unit ) <span class="text-danger">*</span></label>
                                <input onkeyup="validISNumber()" type="text" class="form-control" name="Clothe_Price" id="Clothe_Price"
                                    placeholder="Enter Price">
                            </div>

                            <input type="hidden" name="form_action" value='Add'>

                            <div class="col-12 mt-3 text-center">
                                <button id="luandry_service_btn" class="btn btn-primary"
                                    onclick="return AddLaundryServiceConfig()">Submit</button>
                            </div>



                        </div>
                    </div>
                </form>
                
            </div>

        </div>
    </div>
</div>