<?php
$nav = isset($_GET['nav']) ? $_GET['nav'] : -1;
?>
<div class="panel-container show">
    <div class="panel-content">
        <ul class="nav nav-tabs mb-3" id="branchAssetStatusTabs" role="tablist">
            <li class="nav-item">
                <a class="nav-link active" id="active-assets-tab" data-toggle="tab" href="#active-assets-pane" role="tab" aria-controls="active-assets-pane" aria-selected="true">Active Assets</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="inactive-assets-tab" data-toggle="tab" href="#inactive-assets-pane" role="tab" aria-controls="inactive-assets-pane" aria-selected="false">Inactive Assets</a>
            </li>
        </ul>

        <div class="tab-content">
            <div class="tab-pane fade show active" id="active-assets-pane" role="tabpanel" aria-labelledby="active-assets-tab">
                <table id="view-branch-assets-active" class="table table-bordered table-hover table-striped w-100">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Corporate / Corporate Branches</th>
                            <th>Equipment Number</th>
                            <th>Equipment Name<br>Category<br>Sub Category </th>
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
            </div>

            <div class="tab-pane fade" id="inactive-assets-pane" role="tabpanel" aria-labelledby="inactive-assets-tab">
                <table id="view-branch-assets-inactive" class="table table-bordered table-hover table-striped w-100">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Corporate / Corporate Branches</th>
                            <th>Equipment Number</th>
                            <th>Equipment Name<br>Category<br>Sub Category </th>
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
            </div>
        </div>
    </div>
</div>
