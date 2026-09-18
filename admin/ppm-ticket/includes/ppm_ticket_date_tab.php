<div class="panel-container show">
    <div class="panel-content p-0">
        <!-- datatable start -->
        <table id="view-ppm-date" class="table table-bordered table-hover table-striped w-100">
            <tbody>
                <tr>
                    <th> PPM Date </th>
                   <td>

                        <?php 
                             
                         if($corporate_ticket_data['PPMDate'] == "")
                        {
                            echo "<b>Not Set</b>";
                        }
                        else
                        {
                            echo $corporate_ticket_data['PPMDate'];
                        }

                         ?>
                    </td>

                </tr>



            </tbody>

        </table>
        <div class="text-right">
            <a href="#" class="btn btn-info" style="margin-right:20px;" onclick="EditPPMDate()">Edit</a>
        </div>

    </div>
</div>


<!-- edit modal  -->

<div class="modal fade bd-example-modal-lg" id="edit_ppm_date" role="dialog" aria-labelledby="exampleModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header" style="background-color: #027dc1;
    color: #fff;">
                <div class="tab_modal_heading">
                    <h2>Edit PPM Ticket Date </h2>
                </div>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="opacity: 1;
    color: #fff;">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form method="post" id="ppm_ticket_date_form">
                    <div id="wizard">
                        <section>
                            <div class="row">

                                <div class="col-md-6 col-12">
                                    <div class="form_div">
                                        <label for="PPMDate">PPM Date</label>
                                        <input type="text" class="form-control" name="ppm_date"
                                                        id="ppm_date" placeholder="<?php
                                                        if($corporate_ticket_data['PPMDate'] == ""){
                                                            echo "Select PPM Date";
                                                        }else{
                                                            echo $corporate_ticket_data['PPMDate'];
                                                        }
                                                        ?>
                                                        ">
                                        
                                    </div>
                                </div>

                                <input type="hidden" name="TicketID" value="<?php echo $ID;?>" />


                            </div>
                            <section>
                                <div class="row justify-content-center mt-3">
                                    <a class="form_btn form_submit pl-4 pr-4 pt-2 pb-2 text-white cursor-pointer" id="ppm_date_change_btn"
                                        style="background-color: #2196f3;" onclick="ChangePPMDate()"
                                        value="Save">Save & Change</a>
                                </div>
                            </section>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- edit modal  -->