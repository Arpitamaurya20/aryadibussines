<?php
require_once('../../includes/autoloader.inc.php');
$dbh = new Dbh();
$conn = $dbh->_connectodb();
$core = new Core();
$BranchID = $_POST['BranchID'];
$filter = " where CompanyID = (Select CompanyID from branch where ID = $BranchID)";
$branch_array = $core->_getTableRecords($conn,'branch',$filter);
?>
<label class="form-label">Branch</label>
<select class="select2 form-control w-100" id="to_be_merged_branch"
    name="to_be_merged_branch">
    <option value="-1">Search & Select</option>
    <?php
    foreach($branch_array as $branch)
    {
    ?>
    <option value="<?php echo $branch['ID'];?>"><?php echo $branch['BranchSite'];?></option>
    <?php
    }
    ?>
</select>