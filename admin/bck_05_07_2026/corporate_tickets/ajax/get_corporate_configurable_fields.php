<?php
require_once('../../includes/autoloader.inc.php');
$dbh = new Dbh();
$conn = $dbh->_connectodb();
if(isset($_POST['CorporateID']))
{
    $config_obj = new Config($conn);
    $CorporateID = $_POST['CorporateID'];
    $fields_data = $config_obj->getAllConfigurableFields($_POST);
    foreach($fields_data as $field)
    {
        $class = "";
        $mandatoryfield_html = "";
        if($field['Type'] == "Date")
        {
            $class = "add_date_condition";
        }
        if($field['Mandatory'] == "Yes")
        {
            $mandatoryfield_html = "<span class='text-danger'>*</span>";
        }
        ?>
        <div class="col-lg-6">
            <div class="form-group">
                <label class="form-label"><?php echo $field['Title']; ?> <?php echo $mandatoryfield_html;?></label> 
                <input type="text" class="form-control <?php echo $class;?>" name="<?php echo $field['Param'];?>" id="<?php echo $field['Param'];?>" Placeholder="Please Enter <?php echo $field['Title'];?>" mandatoryfield="<?php echo $field['Mandatory'];?>" field_type="<?php echo $field['Type'];?>">
            </div>
                        
        </div>
        <?php
    }
}
?>