 <div class="comming_soon w-100">
    <img class="w-100" src="../img/coming-soon.png" alt="">
</div>

<!-- <head>
<style>
#salaryYear ,#salaryMonth
{
    height:35px;
    width:85px;
    font-weight: bold;
}
#generate_button , #edit_button
{
    height:40px;
    width:140px;
    margin-left: 800px;
    font-weight: bold;
}

</style>
</head> -->
<!-- <div class="panel-container show">
    <div class="panel-content p-0">
   
    <select class="form-select-sm" name="year" id="salaryYear" onchange="getEmployeeSalary();">
        <?php
            for ($i = 0; $i < 12; $i++) {
                $year_name = date('Y', strtotime("-$i year"));
                echo "<option value='$year_name'>$year_name</option>";
            }
         ?>
    </select>

    <select class="form-select-sm" name="month" id="salaryMonth"  onchange="getEmployeeSalary();">
        <?php
            for ($i = 0; $i < 12; $i++) {
                $month_name = date('F', strtotime("-$i month"));
                echo "<option value='$month_name'>$month_name</option>";
            }
        ?>
    </select>

    <input type="hidden" id="monthly_employee_id" name="EmployeeID" value="<?php echo $ID ?>">

<?php
$current_year = date("Y");
$current_month = date("F");
$generate_button_display = true;


// if monthly salary already exists
$where  = " where EmployeeID = $ID and Year = '$current_year' and Month = '$current_month'";
$employee_monthly_salary_details = _getTableDetails($conn,'employee_monthly_salary', $where);
if(isset($employee_monthly_salary_details['ID']))
{
    $generate_button_display = false;
}
if($generate_button_display)
{
?>
    <a href="#" id="generate_button" class="btn btn-info" onclick="AddEmployeeMonthlySalary()" style="margin-right:20px">Generate Salary</a>
<?php
}
?>

        <table id="view-salary" style="display:none" class="table table-bordered table-hover table-striped w-100">

            <tbody id='view_month_salary'>
           
            </tbody>

        </table>
         <div class="text-center" id="salary_text" style="display:none" >
            <span>Employee salary not Generated </span>
        </div>
        <div class="text-right">
            <a href="#" class="btn btn-info" id="edit_button" style="margin-right:20px;display:none" onclick="openMonthlysalarymodal()">Edit</a>

        </div>
    </div>
</div> -->


