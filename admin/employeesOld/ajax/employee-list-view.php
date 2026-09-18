<div class="panel-container show">
    <div class="panel-content">
        <?php
        if(1)
        {
        ?>
        <!-- datatable start -->
        <table id="view-employees"
            class="table table-bordered table-hover table-striped w-100">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Name </th>
                    <th>ID</th>
                    <th>Department</th>
                    <th>Contact Details</th>
                    <th>Identidy</th>
                    <th>Vendor</th>
                    <th>View</th>
                    <th>Created By</th>
                    <th>Status</th>
                    <!-- <th>Delete</th> -->
                </tr>
            </thead>
          
        </table>
        <!-- datatable end -->
        <?php
        }
        else
        {
            echo "<div class='text-center'>Currently No Employes are added to system</div>";
        }
        ?>
    </div>
</div>