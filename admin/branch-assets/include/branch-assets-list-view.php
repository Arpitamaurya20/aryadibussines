<?php
$nav = isset($_GET['nav']) ? $_GET['nav'] : -1;
?>
<div class="panel-container show">
                                    <div class="panel-content">
                                        <!-- datatable start -->
                                        <table id="view-branch-assets"
                                            class="table table-bordered table-hover table-striped w-100">
                                            <thead>
                                                <tr>
                                                    <th>#</th>
                                                    <?php if (1): ?>
                                                        <th>Corporate / Corporate Branches</th> 
                                                     <?php endif; ?>
                                                     <th>Equipment Number</th>
                                                    <th>Equipment Name<br>Category<br>Sub Category <br>Number</th>
                                                    <th>Make <br> Model</th>
                                                    <th>SNo</th>
                                                    <th>Capacity</th>
                                                    <th>ManufacturingYear</th>
                                                    <th>Service Type</th>
                                                    <th>FloorNumber <br> EquipmentLocation</th>
                                                    <th>PPM</th>
                                                    <th>AMC Ticket</th>
                                                    <th>Branch Assets QR Image</th>
                                                    <th>Update</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>

                                        </table>
                                        <!-- datatable end -->
                                    </div>
                                </div>