<?php
require_once('../../includes/autoloader.inc.php');
if(isset($_POST['CategoryID']))
{
  $dbh = new Dbh();
  $conn = $dbh->_connectodb();
  $categories_obj = new Categories($conn);
  $sub_categories_array = $categories_obj->getAllSubCategoriesfromCategoryID($_POST['CategoryID']);

  foreach($sub_categories_array as $sub_category )
  {
  ?>                         
      <option value="<?php echo $sub_category['SubCategoriesName'];?>"><?php echo $sub_category['SubCategoriesName'];?></option>                                
<?php
  }
  ?>
  <option value="Others">Others</option>
  <?php
}
?>