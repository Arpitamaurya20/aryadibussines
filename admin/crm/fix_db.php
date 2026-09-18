<?php
include('c:/wamp64/www/projects/aryadibussines/connection.php');

$table = 'crm_leads';
$columns_to_add = [
    "Notes" => "text",
    "Attachments" => "text",
    "IsDuplicate" => "tinyint(1) DEFAULT '0'",
    "UpdatedBy" => "int(11) DEFAULT NULL",
    "UpdatedDate" => "date DEFAULT NULL",
    "UpdatedTime" => "time DEFAULT NULL"
];

$existing_columns = [];
$result = mysqli_query($conn, "SHOW COLUMNS FROM `$table`");
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $existing_columns[] = $row['Field'];
    }
} else {
    echo "Error fetching columns: " . mysqli_error($conn) . "\n";
    exit;
}

foreach ($columns_to_add as $colName => $colDef) {
    if (!in_array($colName, $existing_columns)) {
        $query = "ALTER TABLE `$table` ADD COLUMN `$colName` $colDef";
        if (mysqli_query($conn, $query)) {
            echo "Successfully added column `$colName`.\n";
        } else {
            echo "Error adding column `$colName`: " . mysqli_error($conn) . "\n";
        }
    } else {
        echo "Column `$colName` already exists. Skipping.\n";
    }
}
echo "Done checking crm_leads.\n";

$table2 = 'crm_accounts';
$columns_to_add2 = [
    "ContactPerson" => "varchar(255) DEFAULT NULL",
    "Mobile" => "varchar(20) DEFAULT NULL",
    "Email" => "varchar(150) DEFAULT NULL",
    "GST" => "varchar(50) DEFAULT NULL",
    "PAN" => "varchar(50) DEFAULT NULL",
    "State" => "varchar(100) DEFAULT NULL",
    "City" => "varchar(100) DEFAULT NULL",
    "Country" => "varchar(100) DEFAULT NULL",
    "CustomerType" => "varchar(100) DEFAULT NULL",
    "Source" => "varchar(100) DEFAULT NULL",
    "AssignedEmployee" => "int(11) DEFAULT NULL",
    "UpdatedBy" => "int(11) DEFAULT NULL",
    "UpdatedDate" => "date DEFAULT NULL",
    "UpdatedTime" => "time DEFAULT NULL"
];

$existing_columns2 = [];
$result2 = mysqli_query($conn, "SHOW COLUMNS FROM `$table2`");
if ($result2) {
    while ($row = mysqli_fetch_assoc($result2)) {
        $existing_columns2[] = $row['Field'];
    }
}

foreach ($columns_to_add2 as $colName => $colDef) {
    if (!in_array($colName, $existing_columns2)) {
        $query = "ALTER TABLE `$table2` ADD COLUMN `$colName` $colDef";
        if (mysqli_query($conn, $query)) {
            echo "Successfully added column `$colName` to $table2.\n";
        } else {
            echo "Error adding column `$colName` to $table2: " . mysqli_error($conn) . "\n";
        }
    } else {
        echo "Column `$colName` already exists in $table2. Skipping.\n";
    }
}
echo "Done checking crm_accounts.\n";
?>
