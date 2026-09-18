<?php
include("../../controllers/common_controllers.php");
include("../controller/corporate_tickets_controller.php");
if(isset($_POST['ServiveID']))
{

  $conn = _connectodb();
  $service_items = getSubServices($conn,$_POST['ServiveID']);
  foreach($service_items as $service ){
    ?>                         
                             <option value="<?php echo $service['title'];?>">
                                                            <?php echo $service['title'];?>
                                                        </option>
                                           
<?php
  }
}
?>