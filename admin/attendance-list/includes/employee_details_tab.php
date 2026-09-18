<div class="panel-container show">
    <div class="panel-content p-0">
        <!-- datatable start -->
        <table id="view-projects" class="table table-bordered table-hover table-striped w-100">
            <tbody>
                <tr>
                    <th>Name </th>
                    <td><?php echo $employee_data['Name']; ?>
                    </td>
                </tr>
                <tr>
                    <th>Designation</th>
                    <td><?php
                     if($employee_data['Designation'] == "")
                     echo "Not Set";
                 else
                     echo $employee_data['Designation'];
                     ?>
                    </td>
                </tr>
                <tr>
                    <th>Gender </th>
                    <td><?php
                     if($employee_data['Gender'] == "")
                     echo "Not Set";
                 else
                     echo $employee_data['Gender']; ?>
                    </td>
                </tr>
                <tr>
                    <th>ID </th>
                    <td><?php
                    if($employee_data['EmployeeNumber'] == "")
                    echo "Not Set";
                else
                     echo $employee_data['EmployeeNumber'];
                     ?>
                    </td>
                </tr>
                <tr>
                    <th>Department</th>
                    <td><?php
                    if($employee_data['Department'] == "")
                    echo "Not Set";
                else
                     echo $employee_data['Department'];
                     ?>
                    </td>
                </tr>

                <tr>
                    <th>Email</th>
                    <td><?php
                    if($employee_data['Email'] == "")
                    echo "Not Set";
                else
                     echo $employee_data['Email'];
                     ?>
                    </td>
                </tr>
                <tr>
                    <th>Contact Number</th>
                    <td><?php
                     if($employee_data['ContactNumber'] == "")
                     echo "Not Set";
                 else
                     echo $employee_data['ContactNumber'];
                     ?>
                    </td>
                </tr>

                <tr>
                    <th>PAN</th>
                    <td><?php
                     if($employee_data['PAN'] == "")
                     echo "Not Set";
                 else
                     echo $employee_data['PAN'];
                     ?>
                    </td>
                </tr>
 
                <tr>
                    <th>Aadhar Card </th>
                    <td><?php
                    if($employee_data['Aadhar'] == "")
                     echo "Not Set";
                 else
                     echo $employee_data['Aadhar'];
                      ?>
                    </td>
                </tr>
            </tbody>
        </table>    
    </div>
</div>
