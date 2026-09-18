<?php

ini_set('display_errors',1);
error_reporting(E_ALL);

require_once('common_api_header.php');
require_once('../admin/includes/autoloader.inc.php');

$data = json_decode(file_get_contents("php://input"), true);

$response = array();

if(isset($data['EmployeeID']))
{
    $dbh = new Dbh();
    $conn = $dbh->_connectodb();

    $employeeID = (int)$data['EmployeeID'];

    // Get Supervisor of selected Employee
    $stmt = $conn->prepare("SELECT Supervisor FROM employees WHERE ID=?");
    $stmt->bind_param("i",$employeeID);
    $stmt->execute();

    $result = $stmt->get_result();

    if($result->num_rows==0)
    {
        $response['status']=false;
        $response['message']="Employee not found.";

        echo json_encode($response);
        exit;
    }

    $row=$result->fetch_assoc();

    $supervisorID=$data['EmployeeID'];

    // Get all employees under this supervisor
    $stmt2=$conn->prepare("SELECT ID as EmployeeID, Name FROM employees WHERE Supervisor=? and IsActive=1");
    $stmt2->bind_param("i",$supervisorID);
    $stmt2->execute();

    $result2=$stmt2->get_result();

    $employeeIDs=array();

    while($row2=$result2->fetch_assoc())
    {
        $employeeIDs[]=$row2;
    }

    $response['status']=true;
    $response['SupervisorID']=$supervisorID;
    $response['Employees']=$employeeIDs;
    $response['EmployeeCount']=count($employeeIDs);
}
else
{
    $response['status']=false;
    $response['message']="EmployeeID is required.";
}

echo json_encode($response);

?>