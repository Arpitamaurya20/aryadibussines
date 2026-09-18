<?php
include("../../controllers/common_controllers.php");
include("../../manage-sub-categories/controller/sub_categories_controller.php");
if(isset($_POST['CategoryID']))
{

  $conn = _connectodb();
  $category_items = getSubCategories($conn,$_POST['CategoryID']);
  foreach($category_items as $category ){
    ?>
                             <option value="<?php echo $category['ID'];?>">
                                                            <?php echo $category['SubCategoriesName'];?>
                                                        </option>
                                           
<?php
  }
}
?>