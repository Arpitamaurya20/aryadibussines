<?php
// Include necessary files and initialize database connection
require_once('../../includes/autoloader.inc.php');
$dbh = new Dbh();
$conn = $dbh->_connectodb();

// Check if search text is provided
if (isset($_POST['searchText'])) {
    // Sanitize the search input
    $searchText = '%' . $_POST['searchText'] . '%';

    // Prepare SQL query to fetch matching line items
    $stmt = $conn->prepare("SELECT ID, Type, Category, SubCategory, LineItemName, UoM, Price 
                            FROM corporate_rate_card 
                            WHERE LineItemName LIKE ?");

    if ($stmt === false) {
        die("Error preparing statement: " . $conn->error);
    }

    // Bind parameters and execute query
    $stmt->bind_param("s", $searchText);
    $stmt->execute();
    $result = $stmt->get_result();

    // Fetch results into an array
    $lineItems = [];
    while ($row = $result->fetch_assoc()) {
        $lineItems[] = $row;
    }

    // Close statement and database connection
    $stmt->close();
    $conn->close();

    // Return JSON response
    echo json_encode($lineItems);
} else {
    // Return empty response or handle error
    echo json_encode([]);
}
?>