<?php
session_start();
include('../../controllers/common_controllers.php');
require_once('../../includes/autoloader.inc.php');

$conn = _connectodb();
SessionCheck();
header('Content-Type: application/json');

function normalize_qty($value, $precision = 2)
{
    $value = (float)$value;
    $rounded = round($value, $precision);
    $epsilon = 1 / pow(10, $precision + 1); // e.g. 0.001 for 2 decimals
    if (abs($rounded) < $epsilon) {
        $rounded = 0.0;
    }
    return $rounded;
}

$ticketPK = isset($_GET['TicketPK']) ? intval($_GET['TicketPK']) : 0;
if ($ticketPK <= 0) {
    echo json_encode(array('error' => true, 'message' => 'Ticket ID is required'));
    exit;
}

$ticketSql = "SELECT ID, TicketID FROM corporate_tickets WHERE ID = $ticketPK AND IsActive = 1 LIMIT 1";
$ticketRes = mysqli_query($conn, $ticketSql);
if (!$ticketRes || mysqli_num_rows($ticketRes) === 0) {
    echo json_encode(array('error' => true, 'message' => 'Ticket not found'));
    exit;
}
$ticket = mysqli_fetch_assoc($ticketRes);

$quoteSql = "SELECT q1.ID
             FROM corporate_ticket_quotation q1
             INNER JOIN (
                SELECT TicketID, MAX(ID) AS MaxID
                FROM corporate_ticket_quotation
                WHERE IsActive = 1
                GROUP BY TicketID
             ) q2 ON q1.TicketID = q2.TicketID AND q1.ID = q2.MaxID
             WHERE q1.TicketID = $ticketPK
             LIMIT 1";
$quoteRes = mysqli_query($conn, $quoteSql);
if (!$quoteRes || mysqli_num_rows($quoteRes) === 0) {
    echo json_encode(array('error' => false, 'data' => array(), 'message' => 'No quotation found for this ticket'));
    exit;
}
$quote = mysqli_fetch_assoc($quoteRes);
$quotationID = intval($quote['ID']);

$sql = "SELECT
            qi.ID AS QuotationItemID,
            qi.LineItemID AS RateCardID,
            COALESCE(rc.LineItemName, CONCAT('Item #', qi.LineItemID)) AS LineItemName,
            COALESCE(qi.Qty, 0) AS OriginalQty,
            COALESCE(qi.PerItemPrice, 0) AS PerItemPrice,
            COALESCE(qi.TotalPrice, 0) AS QuotationLineAmount,
            COALESCE(bi.BilledQty, 0) AS AlreadyBilledQty
        FROM corporate_ticket_quotation_items qi
        LEFT JOIN corporate_rate_card rc ON qi.LineItemID = rc.ID AND rc.IsActive = 1
        LEFT JOIN (
            SELECT QuotationItemID, SUM(COALESCE(BilledQty, 0)) AS BilledQty
            FROM ticket_billing_line_items
            WHERE IsActive = 1 AND TicketPK = $ticketPK
            GROUP BY QuotationItemID
        ) bi ON bi.QuotationItemID = qi.ID
        WHERE qi.IsActive = 1
        AND qi.QuotationID = $quotationID
        ORDER BY qi.ID ASC";

$res = mysqli_query($conn, $sql);
if (!$res) {
    echo json_encode(array('error' => true, 'message' => 'SQL Error: ' . mysqli_error($conn)));
    exit;
}

$items = array();
while ($row = mysqli_fetch_assoc($res)) {
    $originalQty = normalize_qty($row['OriginalQty']);
    $alreadyBilledQty = normalize_qty($row['AlreadyBilledQty']);
    $remainingQty = normalize_qty($originalQty - $alreadyBilledQty);
    if ($remainingQty < 0) {
        $remainingQty = 0;
    }
    $row['OriginalQty'] = $originalQty;
    $row['AlreadyBilledQty'] = $alreadyBilledQty;
    $row['RemainingQty'] = $remainingQty;
    $row['LineAmountForRemainingQty'] = round($remainingQty * (float)$row['PerItemPrice'], 2);
    $items[] = $row;
}

echo json_encode(array(
    'error' => false,
    'ticket' => $ticket,
    'quotation_id' => $quotationID,
    'data' => $items
));
?>
