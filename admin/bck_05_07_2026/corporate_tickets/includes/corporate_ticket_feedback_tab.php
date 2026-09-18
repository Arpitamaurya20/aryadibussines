<style>
    .star {
        color: #FFBD19;
        -webkit-text-stroke: 1px #FFBD19;
        -webkit-text-fill-color: #FFBD19;
    }
</style>
<?php
if ($customer_rating) {
    $rating = $customer_rating['Rating'];
    $stars = str_repeat('<span class="star">&#9733;</span>', $rating);
    $feedback= $customer_rating['Message'];
}
else
{
    $stars = "Not Found";
    $feedback = "Not Found";
}
?>

<div class="panel-container show">
    <div class="panel-content p-0">
        <!-- datatable start -->
        <table id="view-projects" class="table table-bordered table-hover table-striped w-100">
            <tbody>
                <tr>
                    <th>Feedback</th>
                    <td>
                        <?php echo $feedback ; ?>
                    </td>

                </tr>
                <tr>
                    <th>Rating</th>
                    <td>
                        <?php echo $stars; ?>
                    </td>

                </tr>

            </tbody>

        </table>

    </div>
</div>


<!-- edit modal  -->

<!-- <div class="comming_soon w-100">
    <img class="w-100" src="../img/coming-soon.png" alt="">
</div> -->