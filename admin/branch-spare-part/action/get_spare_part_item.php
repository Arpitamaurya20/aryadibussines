<?php
include("../../controllers/common_controllers.php");
include("../controller/branch_spare_part_controller.php");
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
                                            $item['ID']?>"><?php echo $item['SparePart']?></label>
                                         <input type="hidden" name="SparePartID[]" value="<?php echo $item['ID']?>">
                                         <input type="hidden" name="CategoriesID[]" value="<?php echo $item['Categories'];?>">  
                                    </div>
                                </td>
                                <td><?php echo $item['SparePartCode']?></td>
                                <td>
                                    <div class='form-group mb-0'><input type='text' value="<?php echo
                                        $item['Price']?>" name="spare_part_price[]" id="price_<?php echo
                                        $item['ID']?>" class='form-control' readonly></div>
                                </td>
                            </tr>
                   
<?php
  }
}
?>