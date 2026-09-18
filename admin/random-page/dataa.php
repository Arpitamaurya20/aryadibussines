<?php
include('../controllers/common_controllers.php');
include('controller/random-page-controller.php');
$conn = _connectodb();
$random_pagedata = getAllrandomPage($conn);
$random_pagedata = json_decode($random_pagedata, true);
?>





?>


<table id="view-projects" class="table table-bordered table-hover table-striped w-100" width="100%">
    <thead>
        <tr style="text-align: left;">
            <th>#</th>
            <th>Title</th>
            <th>URL</th>


        </tr>
    </thead>
    <tbody>
        <?php
        $i = 1;
        foreach ($random_pagedata as $RandomPageData) {
            $ID  = $RandomPageData['ID'];
            $status  = $RandomPageData['status'];
            $safety_img  = $RandomPageData['safety_img'];
        ?>
            <tr id="random_page_<?php echo $ID ?>">
                <td><?php echo $i; ?></td>
                <td><?php echo $RandomPageData['title']; ?></td>
                <td><?php echo $RandomPageData['url']; ?></td>



            </tr>
        <?php
            $i++;
        }
        ?>
    </tbody>

</table>

















</body>


</html>