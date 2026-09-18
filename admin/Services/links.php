<?php
include('../controllers/common_controllers.php');
include('controller/service_controller.php');
$conn = _connectodb();


?>

<table id="view-projects" class="table table-bordered table-hover table-striped w-100" width="100%">
    <thead>
        <tr style="text-align: left;">
            <th>#</th>
           
            <th>Link</th>



        </tr>
    </thead>
    <tbody>
        <?php
        $i = 1;

        $d="select * from keywords";
        $d=mysqli_query($conn,$d);
        while($row=mysqli_fetch_array($d))
        {
        ?>
            <tr>
                <td><?php echo $i; ?></td>
                <td><?php echo $row['keywordlink']; ?></td>


            </tr>
        <?php
            $i++;
        }
        ?>
    </tbody>

</table>
<!-- datatable end -->