<?php
require 'admin/controllers/common_controllers.php';
$conn = _connectodb();
$res = mysqli_query($conn, "DESCRIBE employees");
$employees = [];
while($row = mysqli_fetch_assoc($res)) {
    $employees[] = $row;
}
echo "Employees Table:\n";
print_r($employees);

$res2 = mysqli_query($conn, "DESCRIBE roles");
if($res2) {
    $roles = [];
    while($row = mysqli_fetch_assoc($res2)) {
        $roles[] = $row;
    }
    echo "\nRoles Table:\n";
    print_r($roles);
}
?>
