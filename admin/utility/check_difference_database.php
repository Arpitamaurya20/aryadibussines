<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
// Database connection for live (techxpert) database
$live_conn = new mysqli("localhost", "root", "TechXpert@123", "techxpertindia");

// Database connection for old (techxpert_old) database
$old_conn = new mysqli("localhost", "root", "TechXpert@123", "techxpert_old");

// Check connection for live DB
if ($live_conn->connect_error) {
    die("Connection failed: " . $live_conn->connect_error);
}

// Check connection for old DB
if ($old_conn->connect_error) {
    die("Connection failed: " . $old_conn->connect_error);
}

// CSV file to store differences
$csv_file = fopen('branch_assets_differences.csv', 'w');
fputcsv($csv_file, ['ID', 'BranchID', 'EquipmentName', 'Make', 'Model', 'SNo', 'Capacity', 'Qty', 'UoM', 'UnitRate', 'Amount', 'ManufacturingYear', 'EquipmentAge', 'ServiceType', 'Category', 'SubCategory', 'Tat', 'AMCStartDate', 'AMCEndDate', 'SOW', 'FloorNumber', 'EquipmentLocation', 'Description', 'CreatedBy', 'CreatedDate', 'IsActive', 'Remarks']);

// Fetch all data from the old database
$old_data = $old_conn->query("SELECT * FROM branch_assets");
$flag_to_insert = false; // Set to true to allow data insertion

// Array to store SQL queries for output
$sql_queries = [];

// Loop through each row from the old data
while ($row_old = $old_data->fetch_assoc()) {
    $old_id = $row_old['ID'];
    $EquipmentName_old = $row_old['EquipmentName'];
     $BranchID_old = $row_old['BranchID'];
    //echo $old_id."<br>";

    // Check if the row exists in the live database based on ID
    $live_data = $live_conn->query("SELECT * FROM branch_assets WHERE (EquipmentName = '$EquipmentName_old' and BranchID = $BranchID_old)");
    
    if ($live_data->num_rows > 0) {
        // If record exists, fetch it and compare fields
        $row_live = $live_data->fetch_assoc();

        // Check for differences and log them for updates
        $update_fields = [];
        $remarks = []; // To track differing fields
        foreach ($row_old as $column => $value) {
            if ($value != $row_live[$column] && $column != 'ID') {  // Skip ID column
                $update_fields[] = "$column = '" . $value . "'";
                $remarks[] = $column;
            }
        }

        // If there are differences, update the live database and log in CSV
        if (count($update_fields) > 0) {
            if ($flag_to_insert) {
                $update_query = "UPDATE branch_assets SET " . implode(", ", $update_fields) . " WHERE ID = '$old_id'";
                $live_conn->query($update_query);
                $sql_queries[] = $update_query;  // Log SQL query
            }

            // Write the differences into the CSV file
            fputcsv($csv_file, array_merge([$row_old['ID']], $row_old, [implode(", ", $remarks)]));
        }

    } else {
        // If record doesn't exist, insert it into the live database
        if ($flag_to_insert) {
            $insert_query = "INSERT INTO branch_assets (BranchID, EquipmentName, Make, Model, SNo, Capacity, Qty, UoM, UnitRate, Amount, ManufacturingYear, EquipmentAge, ServiceType, Category, SubCategory, Tat, AMCStartDate, AMCEndDate, SOW, FloorNumber, EquipmentLocation, Description, CreatedBy, CreatedDate, IsActive)
                             VALUES ('" . $row_old['BranchID'] . "', '" . $row_old['EquipmentName'] . "', '" . $row_old['Make'] . "', '" . $row_old['Model'] . "', '" . $row_old['SNo'] . "', 
                                     '" . $row_old['Capacity'] . "', '" . $row_old['Qty'] . "', '" . $row_old['UoM'] . "', '" . $row_old['UnitRate'] . "', '" . $row_old['Amount'] . "', 
                                     '" . $row_old['ManufacturingYear'] . "', '" . $row_old['EquipmentAge'] . "', '" . $row_old['ServiceType'] . "', '" . $row_old['Category'] . "', 
                                     '" . $row_old['SubCategory'] . "', '" . $row_old['Tat'] . "', '" . $row_old['AMCStartDate'] . "', '" . $row_old['AMCEndDate'] . "', 
                                     '" . $row_old['SOW'] . "', '" . $row_old['FloorNumber'] . "', '" . $row_old['EquipmentLocation'] . "', '" . $row_old['Description'] . "', 
                                     '" . $row_old['CreatedBy'] . "', '" . $row_old['CreatedDate'] . "', '" . $row_old['IsActive'] . "')";
            $live_conn->query($insert_query);
            $sql_queries[] = $insert_query;  // Log SQL query
        }

        // Log insertion into CSV
        fputcsv($csv_file, array_merge([$row_old['ID']], $row_old, ['Inserted']));
    }
}

// Output the SQL queries executed
foreach ($sql_queries as $query) {
    echo $query . "<hr>";
}

// Close the CSV file and database connections
fclose($csv_file);
$live_conn->close();
$old_conn->close();

echo "Data sync for branch_assets table completed.";
?>