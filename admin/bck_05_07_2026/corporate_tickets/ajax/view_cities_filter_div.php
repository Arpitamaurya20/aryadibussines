<?php
$StateName = $_POST['StateName'];
$sql_in_string = "";
if(isset($_POST['City_In_SQL']))
{
    $sql_in_string = $_POST['City_In_SQL'];
}
require_once('../../includes/autoloader.inc.php');
$dbh = new Dbh();
$conn = $dbh->_connectodb();
$core = new Core();
$StateID = -1;
if($StateName != "")
{
    $where = " where StateName = '$StateName'";
    $StateID = $core->_getTableDetails($conn,'state', $where)['ID'];
}
$city_object = new City($conn);
if($sql_in_string == "")
{
    $city_array_raw = $city_object->getCitiesbyState($StateID);
}
else
{
    if($StateID != -1)
    {
        $where = " where CityName IN (".$sql_in_string.") AND StateID = $StateID ORDER BY CityName ASC";
    }
    else
    {
        $where = " where CityName IN (".$sql_in_string.") ORDER BY CityName ASC";
    }
    $city_array_raw = $core->_getTableRecords($conn,'citydata',$where);
}
?>

<select class="form-control" name="cityName" id="cityName">
    <option value="">Select City Name</option>
    <?php
    foreach ($city_array_raw as $city) 
    {
        $CityName = $city['CityName']
    ?>

        <option value="<?php echo $CityName ?>"> <?php echo $CityName ?></option>

    <?php 
    }  
    ?>
</select>