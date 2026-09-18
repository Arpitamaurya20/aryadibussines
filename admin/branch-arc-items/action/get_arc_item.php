<?php
include("../../controllers/common_controllers.php");
include("../controller/branch_arc_controller.php");
if(isset($_POST['CategoryID']))
{

  $conn = _connectodb();
  $arc_items = getGlobalARCItems($conn,$_POST['CategoryID']);
  foreach($arc_items as $item ){
    ?>
                            <tr>
                                <td>
                                    <div class='custom-control custom-checkbox'>
                                        <input type='checkbox' id="arc_item_<?php echo
                                             $item['ID']?>" name="item_<?php echo $item['ID'];?>[]" class='custom-control-input'
                                            onclick="oncheckchange(<?php echo $item['ID'];?>)"><label
                                            class='custom-control-label' for="arc_item_<?php echo
                                            $item['ID']?>"><?php echo $item['ItemName']?></label>
                                         <input type="hidden" name="ArcID[]" value="<?php echo $item['ID']?>">
                                         <input type="hidden" name="CategoriesID[]" value="<?php echo $item['ItemCategories'];?>">  
                                    </div>
                                </td>
                                <td><?php echo $item['ItemCode']?></td>
                                <td><?php echo $item['ItemDescription']?></td>
                                <td>
                                    <div class='form-group mb-0'><input type='text' value="<?php echo
                                        $item['ItemPrice']?>" name="arc_price[]" id="price_<?php echo
                                        $item['ID']?>" class='form-control' readonly></div>
                                </td>
                            </tr>
                   
<?php
  }
}
?>