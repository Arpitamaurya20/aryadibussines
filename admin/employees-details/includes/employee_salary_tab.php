<div class="panel-container show">
    <div class="panel-content p-0">
        <!-- datatable start -->
        <table id="view-projects" class="table table-bordered table-hover table-striped w-100">
            <tbody>
                <tr>
                    <th>Basic</th>
                    <td><?php
                     if($employee_data['Basic'] == "")
                     echo "Not Set";
                     else
                     echo $employee_data['Basic'];
                     ?>
                    </td>
                </tr>
                <tr>
                    <th>DA</th>
                    <td><?php
                     if($employee_data['DA'] == "")
                     echo "Not Set";
                     else
                     echo $employee_data['DA'];
                     ?>
                    </td>
                </tr>
               <tr>
                    <th>HRA</th>
                    <td><?php
                     if($employee_data['HRA'] == "")
                     echo "Not Set";
                     else
                     echo $employee_data['HRA'];
                     ?>
                    </td>
                </tr>
                <tr>
                    <th>Bonus</th>
                    <td><?php
                     if($employee_data['Bonus'] == "")
                     echo "Not Set";
                     else
                     echo $employee_data['Bonus'];
                     ?>
                    </td>
                </tr>
                <tr>
                    <th>Health Insurance</th>
                    <td><?php
                     if($employee_data['HealthInsurance'] == "")
                     echo "Not Set";
                     else
                     echo $employee_data['HealthInsurance'];
                     ?>
                    </td>
                </tr>
               <tr>
                    <th>EPF Number </th>
                    <td>
                        <?php
                        if($employee_data['Epf_number'] == "")
                            echo "Not Set";
                        else
                            echo $employee_data['Epf_number'];
                        ?>
                    </td>
                </tr>
                <tr>
                    <th>ESIC Number </th>
                    <td>
                        <?php
                        if($employee_data['Esic_number'] == "")
                            echo "Not Set";
                        else
                            echo $employee_data['Esic_number'];
                        ?>
                    </td>
                </tr>
                <tr>
                    <th>Others</th>
                    <td><?php
                     if($employee_data['Others'] == "")
                     echo "Not Set";
                     else
                     echo $employee_data['Others'];
                     ?>
                    </td>
                </tr>               
                
            </tbody>
        </table>
    </div>
</div>