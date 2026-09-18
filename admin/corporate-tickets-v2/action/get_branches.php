<?php
include("../../controllers/common_controllers.php");
include("../controller/corporate_tickets_controller.php");
if(isset($_POST['CorporateID']))
{

  $conn = _connectodb();
  $Branch_list = getBranchByCorporateID($conn,$_POST['CorporateID']);
  foreach($Branch_list as $Branch ){
    ?>                         
                             <option value="<?php echo $Branch['ID'];?>">
                                                            <?php echo $Branch['BranchSite'];?>
                                                        </option>
                                           
<?php
  }
}
?>