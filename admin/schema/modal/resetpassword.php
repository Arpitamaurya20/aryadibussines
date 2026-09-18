 <!-- Modal Right Small -->

                                              <!-- Modal center Large no backdrop -->

                                            <div class="modal fade modal-backdrop-transparent" id="example-modal-backdrop-transparent" tabindex="-1" role="dialog" aria-hidden="true">

                                                <div class="modal-dialog modal-md modal-dialog-centered" role="document">

                                                    <div class="modal-content">

                                                        <div class="modal-header">

                                                            <h5 class="modal-title">Reset Password</h5>

                                                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">

                                                                <span aria-hidden="true"><i class="fal fa-times"></i></span>

                                                            </button>

                                                        </div>

                                                        <div class="modal-body">

                                                                <form id="enterprise_create_form">

                                                                <div class="row">



                                                                    <div class="col-lg-12">



                                                                        <div class="form-group">



                                                                            <label class="form-label" for="block_name">Password</label>



                                                                            <input type="password" id="password" name="password" class="form-control">



                                                                        </div>



                                                                    </div>

                                                                     <div class="col-lg-12">



                                                                        <div class="form-group">



                                                                            <label class="form-label" for="block_name">Confirm Password</label>



                                                                            <input type="password" id="confirmpassword" name="confirmpassword" class="form-control">



                                                                        </div>



                                                                    </div>



                                                                </div>

                                                                <input type="hidden" name="ResetId" value="<?php echo $NewUserId ?>">

                                                                <div class="row mt-4">



                                                                    <div class="col-lg-12">



                                                                         <div>



                                                                            <button type="button" class="btn btn-blue" style="float:right;" onclick="resetpassword()">Reset</button>



                                                                        </div>



                                                                    </div>



                                                                </div>

                                                        </form>

                                                        </div>

                                                       

                                                    </div>

                                                </div>

                                            </div>