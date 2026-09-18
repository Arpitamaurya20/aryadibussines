<?php
$CorporateID = $_POST['CompanyID'];
require_once('../../includes/autoloader.inc.php');
$dbh = new Dbh();
$conn = $dbh->_connectodb();
$branch_object = new Branch($conn);
$branch_array = $branch_object->setBranchArrayByCorporateID($CorporateID,'All');
?>

<select class="form-control" name="branch_name" id="branch_name">
    <option value="-1">Select Branch</option>
    <?php
    foreach ($branch_array as $ID=>$branch) 
    {

        $BranchSite = $branch['BranchName']
    ?>

        <option value="<?php echo $ID; ?>"> <?php echo $BranchSite; ?></option>

    <?php 
    }  
    ?>
</select>