<?php
include("../../controllers/common_controllers.php");
include('../controller/branch_controller.php');
if(isset($_POST['StateID']))
{

  $conn = _connectodb();
  $category_items = getStateByCity($conn,$_POST['StateID']);
  foreach($category_items as $category ){
    ?>
                             <option value="<?php echo $category['CityName'];?>">
                                                            <?php echo $category['CityName'];?>
                                                        </option>
                                           
<?php
  }
}
?>