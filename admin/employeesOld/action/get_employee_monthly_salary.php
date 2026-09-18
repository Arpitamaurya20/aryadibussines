<?php
include("../../controllers/common_controllers.php");
include('../controller/employee_controller.php');


$conn = _connectodb();
$emp_monthly_data = getEmployeeMonthlySalary($conn,$_POST['ID'],$_POST['Year'],$_POST['Month']);
// print_r($emp_monthly_data);
$EmployeeAllData = getEmployeeMonthlySalaryData($conn,$_POST['ID']);

if(!empty($emp_monthly_data))
{
foreach($emp_monthly_data as $emp_monthly ) 
{
?>

  <tr>
      <th>Basic</th>
      <td><?php
        if($emp_monthly['Basic'] == "")
        echo "Not Set";
        else
        echo $emp_monthly['Basic'];
        ?>
      </td>
  </tr>
  <tr>
      <th>DA</th>
      <td><?php
        if($emp_monthly['DA'] == "")
        echo "Not Set";
        else
        echo $emp_monthly['DA'];
        ?>
      </td>
  </tr>           
  <tr>
      <th>HRA</th>
      <td><?php
        if($emp_monthly['HRA'] == "")
        echo "Not Set";
        else
        echo $emp_monthly['HRA'];
        ?>
      </td>
  </tr>
  <tr>
      <th>Bonus</th>
      <td><?php
        if($emp_monthly['Bonus'] == "")
        echo "Not Set";
        else
        echo $emp_monthly['Bonus'];
        ?>
      </td>
  </tr>        
  <tr>
      <th>Health Insurance</th>
      <td><?php
        if($emp_monthly['HealthInsurance'] == "")
        echo "Not Set";
        else
        echo $emp_monthly['HealthInsurance'];
        ?>
      </td>
  </tr>           
  <tr>
      <th>EPF Amonut</th>
      <td><?php
        if($emp_monthly['Epf_number'] == "")
        echo "Not Set";
        else
        echo $emp_monthly['Epf_number'];
        ?>
      </td>
  </tr>        
  <tr>
      <th>ESIC Amount</th>
      <td><?php
        if($emp_monthly['Esic_number'] == "")
        echo "Not Set";
        else
        echo $emp_monthly['HealthInsurance'];
        ?>
      </td>
  </tr> 
  <?php
}
}
else
{
  echo "false";
}
?>