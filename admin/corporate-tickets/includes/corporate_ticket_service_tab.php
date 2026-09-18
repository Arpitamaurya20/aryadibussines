<div class="panel-container show">
    <div class="panel-content p-0">
        <!-- datatable start -->
        <table id="view-projects" class="table table-bordered table-hover table-striped w-100">
            <tbody>
                <tr>
                    <th> Services </th>
                    <td>
                    </td>

                </tr>



            </tbody>

        </table>
        <div class="text-right">
            <a href="#" class="btn btn-info" style="margin-right:20px;" onclick="openBookingAssignmodal()">Edit</a>
        </div>

    </div>
</div>


<!-- edit modal  -->

<div class="modal fade bd-example-modal-lg" id="" role="dialog" aria-labelledby="exampleModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header" style="background-color: #027dc1;
    color: #fff;">
                <div class="tab_modal_heading">
                    <h2>Edit Ticket Status </h2>
                </div>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="opacity: 1;
    color: #fff;">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="ticket_assignment_form">
                    <div id="wizard">
                        <section>
                            <div class="row">

                                <div class="col-md-6 col-12">
                                    <div class="form_div">
                                        <label for="AssignEmployee">Assign Employee</label>

                                        <select class="select2 form-control w-100" id="assignemployee_dropdown"
                                            name="AssignedTo">

                                        </select>

                                    </div>
                                </div>

                                <div class="col-md-6 col-12">
                                    <div class="form_div"> <label for="ChangeStatus">Change Status
                                        </label>
                                        <select name="TicketStatus" id="ticket_status" class="form-control select2">

                                        </select>

                                    </div>
                                </div>
                                <input type="hidden" name="TicketID" value="" />


                            </div>
                            <section>
                                <div class="row justify-content-center mt-3">
                                    <a class="form_btn form_submit pl-4 pr-4 pt-2 pb-2 text-white cursor-pointer"
                                        style="background-color: #2196f3;" onclick="ChangeTicketStatus_Assignment()"
                                        value="Save">Save</a>
                                </div>
                            </section>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- edit modal  -->