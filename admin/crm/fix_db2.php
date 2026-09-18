<?php
// Connect to the DB the user actually uses
$conn = mysqli_connect("localhost", "root", "", "aryadibussiness");
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// 1. Audit Logs
$q_audit = "CREATE TABLE IF NOT EXISTS `crm_audit_logs` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `ActionType` varchar(50) NOT NULL,
  `ModuleName` varchar(100) NOT NULL,
  `RecordID` int(11) DEFAULT NULL,
  `OldValue` longtext,
  `NewValue` longtext,
  `IPAddress` varchar(45) DEFAULT NULL,
  `Browser` varchar(255) DEFAULT NULL,
  `ActionDate` date DEFAULT NULL,
  `ActionTime` time DEFAULT NULL,
  `EmployeeID` int(11) DEFAULT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
mysqli_query($conn, $q_audit);

$q_roles = "CREATE TABLE IF NOT EXISTS `crm_roles` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `RoleName` varchar(100) NOT NULL,
  `Description` varchar(255) DEFAULT NULL,
  `IsActive` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
mysqli_query($conn, $q_roles);

$q_perms = "CREATE TABLE IF NOT EXISTS `crm_permissions` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `RoleID` int(11) NOT NULL,
  `ModuleName` varchar(100) NOT NULL,
  `CanView` tinyint(1) DEFAULT '0',
  `CanAdd` tinyint(1) DEFAULT '0',
  `CanEdit` tinyint(1) DEFAULT '0',
  `CanDelete` tinyint(1) DEFAULT '0',
  `CanExport` tinyint(1) DEFAULT '0',
  `CanApprove` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
mysqli_query($conn, $q_perms);

// crm_leads
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
    echo "Error fetching columns from crm_leads: " . mysqli_error($conn) . "\n";
}

if (!empty($existing_columns)) {
    foreach ($columns_to_add as $colName => $colDef) {
        if (!in_array($colName, $existing_columns)) {
            $query = "ALTER TABLE `$table` ADD COLUMN `$colName` $colDef";
            if (mysqli_query($conn, $query)) {
                echo "Successfully added column `$colName` to $table.\n";
            } else {
                echo "Error adding column `$colName` to $table: " . mysqli_error($conn) . "\n";
            }
        }
    }
}

// crm_accounts
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

if (!empty($existing_columns2)) {
    foreach ($columns_to_add2 as $colName => $colDef) {
        if (!in_array($colName, $existing_columns2)) {
            $query = "ALTER TABLE `$table2` ADD COLUMN `$colName` $colDef";
            if (mysqli_query($conn, $query)) {
                echo "Successfully added column `$colName` to $table2.\n";
            } else {
                echo "Error adding column `$colName` to $table2: " . mysqli_error($conn) . "\n";
            }
        }
    }
}

echo "Done fixing aryadibussiness database!\n";
?>
