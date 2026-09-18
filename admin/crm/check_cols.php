<?php
$conn = mysqli_connect("localhost", "root", "", "aryadibussiness");
$result = mysqli_query($conn, "SHOW COLUMNS FROM crm_leads");
while($row = mysqli_fetch_assoc($result)) {
    echo $row['Field'] . "\n";
}
?>
