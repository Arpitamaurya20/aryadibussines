<div class="panel-container show">
    <div class="panel-content p-0">
        <?php
        if($Ticket_finance_data != null)
        {
        ?>
        
        <table id="view-costing" class="table table-bordered table-hover table-striped w-100">
            <tbody>
                <?php
                $T_VisitorNo = $Ticket_finance_data['T_VisitorNo'];
                $T_VisitCharge = $Ticket_finance_data['T_VisitCharge'];
                $T_MaterialCost = $Ticket_finance_data['T_MaterialCost'];
                $T_LabourCost = $Ticket_finance_data['T_LabourCost'];
                $T_TotalPrice = $Ticket_finance_data['T_TotalPrice'];
                $C_VisitorNo = $Ticket_finance_data['C_VisitorNo'];
                $C_VisitCharge = $Ticket_finance_data['C_VisitCharge'];
                $C_MaterialCost = $Ticket_finance_data['C_MaterialCost'];
                $C_LabourCost = $Ticket_finance_data['C_LabourCost'];
                $C_TotalPrice = $Ticket_finance_data['C_TotalPrice'];
                $TicketFinanceStatus = $Ticket_finance_data['Status'];
                ?>

            
                            
                <tr>
                    <th>No. Of Visits</th>
                    <td>
                        <?php 
                        if(isset($Ticket_finance_data['C_VisitorNo']))
                            echo $Ticket_finance_data['C_VisitorNo'];
                        else
                            echo "Not Set";
                        ?>
                    </td>
                </tr>

                <tr>
                    <th>Visit Charge </th>
                    <td>
                        <?php 
                        if(isset($Ticket_finance_data['C_VisitCharge']))
                            echo "&#8377;".$Ticket_finance_data['C_VisitCharge'];
                        else
                            echo "Not Set";
                        ?>
                    </td>
                </tr>

                <tr>
                    <th>Material Cost</th>
                    <td>
                        <?php 
                        if(isset($Ticket_finance_data['C_MaterialCost']))
                            echo "&#8377;".$Ticket_finance_data['C_MaterialCost'];
                        else
                            echo "Not Set";
                        ?>
                    </td>
                </tr>
                <tr>
                    <th>Labour Cost </th>
                    <td>
                        <?php 
                        if(isset($Ticket_finance_data['C_LabourCost']))
                            echo "&#8377;".$Ticket_finance_data['C_LabourCost'];
                        else
                            echo "Not Set";
                        ?>
                    </td>
                </tr>
                
                <tr>
                    <th>Total Price </th>
                    <td>
                        <?php 
                        if(isset($Ticket_finance_data['C_TotalPrice']))
                            echo "<b>&#8377;".$Ticket_finance_data['C_TotalPrice']."</b>";
                        else
                            echo "Not Set";
                        ?>
                    </td>
                </tr>
                

               
                <tr>
                    <th>Remarks</th>
                    <td>
                        <?php 
                        if(isset($Ticket_finance_data['Remarks']))
                            echo $Ticket_finance_data['Remarks'];
                        else
                            echo "Not Set";
                        ?>
                    </td>
                </tr>
            </tbody>
        </table>
        <?php
        }
        else
        {
            echo "<h5>No cost placed</h5>";
        }
        ?>
    </div>
</div>