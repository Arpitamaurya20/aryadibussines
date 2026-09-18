<?php
session_start();
include('../../controllers/common_controllers.php');
require_once('../../includes/autoloader.inc.php');
require_once('../controller/ticket_billing_controller.php');

$conn = _connectodb();
SessionCheck();
header('Content-Type: application/json');

$ticketPKs = isset($_POST['TicketPKs']) ? $_POST['TicketPKs'] : array();
$billingNumber = isset($_POST['BillingNumber']) ? trim($_POST['BillingNumber']) : '';
$billedDate = isset($_POST['BilledDate']) ? $_POST['BilledDate'] : date('Y-m-d');
$paymentStatus = isset($_POST['PaymentStatus']) ? $_POST['PaymentStatus'] : 'Pending';
$remarks = isset($_POST['Remarks']) ? $_POST['Remarks'] : '';
$createdBy = isset($_SESSION['Username']) ? $_SESSION['Username'] : 'System';
$billingMode = isset($_POST['BillingMode']) ? trim($_POST['BillingMode']) : 'full';
$lineItemsJson = isset($_POST['LineItems']) ? $_POST['LineItems'] : '[]';
$lineItems = json_decode($lineItemsJson, true);
$paymentOnly = isset($_POST['PaymentOnly']) ? intval($_POST['PaymentOnly']) : 0;
$reopenBilling = isset($_POST['ReopenBilling']) ? intval($_POST['ReopenBilling']) : 0;

if (!is_array($ticketPKs) || count($ticketPKs) === 0) {
    echo json_encode(array('error' => true, 'message' => 'No tickets selected'));
    exit;
}

if (!is_array($lineItems)) {
    $lineItems = array();
}

function normalize_qty_value($value, $precision = 2)
{
    $rounded = round((float)$value, $precision);
    $epsilon = 1 / pow(10, $precision + 1);
    if (abs($rounded) < $epsilon) {
        $rounded = 0.0;
    }
    return $rounded;
}

function getTicketAndQuotation($conn, $ticketPK)
{
    $ticketPK = (int)$ticketPK;
    $ticketSql = "SELECT ID, TicketID, CorporateID, BranchID, BranchAssetID, Status
                  FROM corporate_tickets
                  WHERE ID = $ticketPK AND IsActive = 1
                  LIMIT 1";
    $ticketRes = mysqli_query($conn, $ticketSql);
    if (!$ticketRes || mysqli_num_rows($ticketRes) === 0) {
        return null;
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
    $quotationID = 0;
    if ($quoteRes && mysqli_num_rows($quoteRes) > 0) {
        $quote = mysqli_fetch_assoc($quoteRes);
        $quotationID = (int)$quote['ID'];
    }

    $ticket['QuotationID'] = $quotationID;
    return $ticket;
}

function getExistingTicketBillingTracking($conn, $ticketPK)
{
    $ticketPK = (int)$ticketPK;
    return _getTableDetails($conn, 'ticket_billing_tracking', ' where TicketPK = ' . $ticketPK . ' AND IsActive = 1');
}

function getLineItemBase($conn, $ticketPK, $quotationItemID)
{
    $ticketPK = (int)$ticketPK;
    $quotationItemID = (int)$quotationItemID;
    $sql = "SELECT
                qi.ID AS QuotationItemID,
                qi.QuotationID,
                qi.LineItemID AS RateCardID,
                COALESCE(qi.Qty, 0) AS OriginalQty,
                COALESCE(qi.PerItemPrice, 0) AS PerItemPrice,
                COALESCE(rc.LineItemName, CONCAT('Item #', qi.LineItemID)) AS LineItemName,
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
            AND qi.ID = $quotationItemID
            LIMIT 1";
    $res = mysqli_query($conn, $sql);
    if (!$res || mysqli_num_rows($res) === 0) {
        return null;
    }
    return mysqli_fetch_assoc($res);
}

function getTicketTotalsForStatus($conn, $ticketPK, $quotationID)
{
    $ticketPK = (int)$ticketPK;
    $quotationID = (int)$quotationID;
    $origSql = "SELECT SUM(COALESCE(Qty, 0)) AS TotalOriginalQty
                FROM corporate_ticket_quotation_items
                WHERE IsActive = 1 AND QuotationID = $quotationID";
    $origRes = mysqli_query($conn, $origSql);
    $origQty = 0.0;
    if ($origRes && ($row = mysqli_fetch_assoc($origRes))) {
        $origQty = (float)$row['TotalOriginalQty'];
    }

    $billSql = "SELECT SUM(COALESCE(BilledQty, 0)) AS TotalBilledQty, SUM(COALESCE(BilledLineAmount, 0)) AS TotalBilledAmount
                FROM ticket_billing_line_items
                WHERE IsActive = 1 AND TicketPK = $ticketPK";
    $billRes = mysqli_query($conn, $billSql);
    $billedQty = 0.0;
    $billedAmount = 0.0;
    if ($billRes && ($row = mysqli_fetch_assoc($billRes))) {
        $billedQty = (float)$row['TotalBilledQty'];
        $billedAmount = (float)$row['TotalBilledAmount'];
    }

    $status = 'Unbilled';
    if ($billedQty > 0 && $origQty > 0 && $billedQty < $origQty) {
        $status = 'Partially Billed';
    } elseif ($origQty > 0 && $billedQty >= $origQty) {
        $status = 'Billed';
    }
    return array(
        'BillingStatus' => $status,
        'TotalBilledAmount' => $billedAmount
    );
}

function autoResolvePaymentStatus($billingStatus, $requestedPaymentStatus)
{
    $billingStatus = trim((string)$billingStatus);
    if ($billingStatus === 'Partially Billed') {
        return 'Partially Paid';
    }
    if ($billingStatus === 'Unbilled') {
        return 'Pending';
    }
    return $requestedPaymentStatus;
}

function billRemainingLineItemsForTicket($conn, $ticketInfo, $billingNumber, $paymentStatus, $billedDate, $remarks, $createdBy)
{
    $ticketPK = (int)$ticketInfo['ID'];
    $quotationID = (int)$ticketInfo['QuotationID'];
    if ($quotationID <= 0) {
        return 0.0;
    }

    $sql = "SELECT
                qi.ID AS QuotationItemID,
                qi.QuotationID,
                qi.LineItemID AS RateCardID,
                COALESCE(qi.Qty, 0) AS OriginalQty,
                COALESCE(qi.PerItemPrice, 0) AS PerItemPrice,
                COALESCE(rc.LineItemName, CONCAT('Item #', qi.LineItemID)) AS LineItemName,
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
            AND qi.QuotationID = $quotationID";
    $res = mysqli_query($conn, $sql);
    if (!$res) {
        throw new Exception('Unable to read remaining line items for ticket ' . $ticketInfo['TicketID']);
    }

    $newlyBilledAmount = 0.0;
    while ($row = mysqli_fetch_assoc($res)) {
        $remainingQty = (float)$row['OriginalQty'] - (float)$row['AlreadyBilledQty'];
        $remainingQty = normalize_qty_value($remainingQty, 2);
        if ($remainingQty <= 0) {
            continue;
        }
        $lineAmount = round($remainingQty * (float)$row['PerItemPrice'], 2);
        $newlyBilledAmount += $lineAmount;

        $ins = "INSERT INTO ticket_billing_line_items
                (TicketPK, TicketID, QuotationID, QuotationItemID, RateCardID, LineItemName, OriginalQty, BilledQty, PerItemPrice, BilledLineAmount, BillingNumber, BillingStatus, PaymentStatus, BilledDate, Remarks, CreatedBy, CreatedDate, CreatedTime, IsActive)
                VALUES
                ($ticketPK, '" . mysqli_real_escape_string($conn, $ticketInfo['TicketID']) . "', " . (int)$row['QuotationID'] . ", " . (int)$row['QuotationItemID'] . ", " . (int)$row['RateCardID'] . ",
                '" . mysqli_real_escape_string($conn, $row['LineItemName']) . "', " . (float)$row['OriginalQty'] . ", $remainingQty, " . (float)$row['PerItemPrice'] . ",
                $lineAmount, " . ($billingNumber !== '' ? "'" . mysqli_real_escape_string($conn, $billingNumber) . "'" : "NULL") . ", 'Billed',
                '" . mysqli_real_escape_string($conn, $paymentStatus) . "', '$billedDate', '" . mysqli_real_escape_string($conn, $remarks) . "',
                '" . mysqli_real_escape_string($conn, $createdBy) . "', '" . date('Y-m-d') . "', '" . date('H:i:s') . "', 1)";
        $insRes = _InsertTableRecords($conn, $ins);
        if (isset($insRes['error']) && $insRes['error'] == true) {
            throw new Exception('Unable to save remaining line item billing for ticket ' . $ticketInfo['TicketID']);
        }
    }

    return $newlyBilledAmount;
}

mysqli_begin_transaction($conn);
try {
    $success = 0;
    $totalAmount = 0.0;

    if ($reopenBilling === 1) {
        foreach ($ticketPKs as $ticketPK) {
            $ticketPK = (int)$ticketPK;
            if ($ticketPK <= 0) {
                continue;
            }
            $updLine = " IsActive = 0 where TicketPK = $ticketPK AND IsActive = 1";
            _UpdateTableRecords($conn, 'ticket_billing_line_items', $updLine);

            $existing = getExistingTicketBillingTracking($conn, $ticketPK);
            if ($existing) {
                $updTrack = " BillingStatus = 'Unbilled',
                              BillingQueueStage = 'Eligible',
                              PaymentStatus = 'Pending',
                              BilledAmount = 0,
                              BillingNumber = NULL,
                              BilledDate = NULL,
                              QueuedDate = NULL,
                              QueuedBy = NULL,
                              VerifiedDate = NULL,
                              VerifiedBy = NULL,
                              UpdatedBy = '" . mysqli_real_escape_string($conn, $createdBy) . "',
                              UpdatedDate = '" . date('Y-m-d') . "',
                              UpdatedTime = '" . date('H:i:s') . "'
                              where ID = " . (int)$existing['ID'];
                _UpdateTableRecords($conn, 'ticket_billing_tracking', $updTrack);
            }
            $success++;
        }

        if ($success <= 0) {
            throw new Exception('No tickets processed for re-open billing.');
        }

        mysqli_commit($conn);
        echo json_encode(array(
            'error' => false,
            'message' => $success . ' ticket(s) re-opened for billing.',
            'TotalAmount' => 0
        ));
        exit;
    }

    if ($paymentOnly === 1) {
        foreach ($ticketPKs as $ticketPK) {
            $ticketPK = (int)$ticketPK;
            if ($ticketPK <= 0) {
                continue;
            }
            $existing = getExistingTicketBillingTracking($conn, $ticketPK);
            $currentBillingStatus = $existing && !empty($existing['BillingStatus']) ? $existing['BillingStatus'] : 'Unbilled';
            $currentBilledAmount = $existing ? (float)$existing['BilledAmount'] : 0.0;
            $payload = array(
                'TicketPK' => $ticketPK,
                'BillingStatus' => $currentBillingStatus,
                'PaymentStatus' => $paymentStatus,
                'BillingNumber' => $billingNumber,
                'BilledDate' => $billedDate,
                'BilledAmount' => $currentBilledAmount,
                'Remarks' => $remarks,
                'CreatedBy' => $createdBy
            );
            $res = CreateUpdateTicketBillingTracking($conn, $payload);
            if ($res['error']) {
                throw new Exception($res['message']);
            }
            $success++;
            $totalAmount += isset($res['BilledAmount']) ? (float)$res['BilledAmount'] : 0.0;
        }

        if ($success <= 0) {
            throw new Exception('No tickets processed for payment update.');
        }

        if ($billingNumber !== '') {
            $safeBillingNo = mysqli_real_escape_string($conn, $billingNumber);
            $checkSql = "SELECT ID FROM ticket_billing_tracking_amount WHERE BillingNumber = '$safeBillingNo' LIMIT 1";
            $checkRes = mysqli_query($conn, $checkSql);
            if ($checkRes && mysqli_num_rows($checkRes) > 0) {
                $update = " TotalAmount = $totalAmount, UpdatedAt = NOW() WHERE BillingNumber = '$safeBillingNo'";
                _UpdateTableRecords($conn, 'ticket_billing_tracking_amount', $update);
            } else {
                $ins = "INSERT INTO ticket_billing_tracking_amount (BillingNumber, TotalAmount, UpdatedAt)
                        VALUES ('$safeBillingNo', $totalAmount, NOW())";
                _InsertTableRecords($conn, $ins);
            }
        }

        mysqli_commit($conn);
        echo json_encode(array(
            'error' => false,
            'message' => $success . ' ticket(s) payment details updated.',
            'TotalAmount' => round($totalAmount, 2)
        ));
        exit;
    }

    if ($billingMode === 'itemized') {
        if (count($lineItems) === 0) {
            throw new Exception('Please select at least one line item for partial billing.');
        }

        $ticketPKMap = array();
        foreach ($ticketPKs as $ticketPK) {
            $ticketPKMap[(int)$ticketPK] = true;
        }

        $ticketInfos = array();
        $lineBilledAmount = 0.0;
        foreach ($lineItems as $item) {
            $ticketPK = isset($item['TicketPK']) ? (int)$item['TicketPK'] : 0;
            $quotationItemID = isset($item['QuotationItemID']) ? (int)$item['QuotationItemID'] : 0;
            $qtyToBill = isset($item['BilledQty']) ? (float)$item['BilledQty'] : 0.0;
            $qtyToBill = normalize_qty_value($qtyToBill, 2);
            if ($ticketPK <= 0 || $quotationItemID <= 0 || $qtyToBill <= 0) {
                continue;
            }
            if (!isset($ticketPKMap[$ticketPK])) {
                throw new Exception('Invalid ticket selection for itemized billing.');
            }

            if (!isset($ticketInfos[$ticketPK])) {
                $ticketInfo = getTicketAndQuotation($conn, $ticketPK);
                if (!$ticketInfo || (int)$ticketInfo['QuotationID'] <= 0) {
                    throw new Exception('Quotation not found for ticket ID ' . $ticketPK);
                }
                $ticketInfos[$ticketPK] = $ticketInfo;
            }
            $ticketInfo = $ticketInfos[$ticketPK];

            $base = getLineItemBase($conn, $ticketPK, $quotationItemID);
            if (!$base) {
                throw new Exception('Invalid quotation line item selected.');
            }

            $remainingQty = (float)$base['OriginalQty'] - (float)$base['AlreadyBilledQty'];
            $remainingQty = normalize_qty_value($remainingQty, 2);
            if ($qtyToBill > $remainingQty + 0.0001) {
                throw new Exception('Billed quantity is more than remaining quantity for line item ' . $base['LineItemName']);
            }

            $lineAmount = round($qtyToBill * (float)$base['PerItemPrice'], 2);
            $lineBilledAmount += $lineAmount;

            $ins = "INSERT INTO ticket_billing_line_items
                    (TicketPK, TicketID, QuotationID, QuotationItemID, RateCardID, LineItemName, OriginalQty, BilledQty, PerItemPrice, BilledLineAmount, BillingNumber, BillingStatus, PaymentStatus, BilledDate, Remarks, CreatedBy, CreatedDate, CreatedTime, IsActive)
                    VALUES
                    ($ticketPK, '" . mysqli_real_escape_string($conn, $ticketInfo['TicketID']) . "', " . (int)$base['QuotationID'] . ", $quotationItemID, " . (int)$base['RateCardID'] . ",
                    '" . mysqli_real_escape_string($conn, $base['LineItemName']) . "', " . (float)$base['OriginalQty'] . ", $qtyToBill, " . (float)$base['PerItemPrice'] . ",
                    $lineAmount, " . ($billingNumber !== '' ? "'" . mysqli_real_escape_string($conn, $billingNumber) . "'" : "NULL") . ", 'Billed',
                    '" . mysqli_real_escape_string($conn, $paymentStatus) . "', '$billedDate', '" . mysqli_real_escape_string($conn, $remarks) . "',
                    '" . mysqli_real_escape_string($conn, $createdBy) . "', '" . date('Y-m-d') . "', '" . date('H:i:s') . "', 1)";

            $insRes = _InsertTableRecords($conn, $ins);
            if (isset($insRes['error']) && $insRes['error'] == true) {
                throw new Exception('Unable to save line item billing entry.');
            }
        }

        if ($lineBilledAmount <= 0) {
            throw new Exception('Please enter quantity to bill for selected line items.');
        }

        foreach ($ticketInfos as $ticketPK => $ticketInfo) {
            $statusInfo = getTicketTotalsForStatus($conn, (int)$ticketPK, (int)$ticketInfo['QuotationID']);
            $finalPaymentStatus = autoResolvePaymentStatus($statusInfo['BillingStatus'], $paymentStatus);
            $payload = array(
                'TicketPK' => (int)$ticketPK,
                'BillingStatus' => $statusInfo['BillingStatus'],
                'PaymentStatus' => $finalPaymentStatus,
                'BillingNumber' => $billingNumber,
                'BilledDate' => $billedDate,
                'BilledAmount' => $statusInfo['TotalBilledAmount'],
                'Remarks' => $remarks,
                'CreatedBy' => $createdBy
            );
            $res = CreateUpdateTicketBillingTracking($conn, $payload);
            if ($res['error']) {
                throw new Exception($res['message']);
            }
            $success++;
        }
        $totalAmount = $lineBilledAmount;
    } else {
        foreach ($ticketPKs as $ticketPK) {
            $ticketInfo = getTicketAndQuotation($conn, (int)$ticketPK);
            if (!$ticketInfo) {
                continue;
            }

            // If ticket has itemized history, full billing now bills only remaining qty.
            billRemainingLineItemsForTicket($conn, $ticketInfo, $billingNumber, $paymentStatus, $billedDate, $remarks, $createdBy);

            if ((int)$ticketInfo['QuotationID'] > 0) {
                $statusInfo = getTicketTotalsForStatus($conn, (int)$ticketInfo['ID'], (int)$ticketInfo['QuotationID']);
                $finalPaymentStatus = autoResolvePaymentStatus($statusInfo['BillingStatus'], $paymentStatus);
                $payload = array(
                    'TicketPK' => (int)$ticketInfo['ID'],
                    'BillingStatus' => $statusInfo['BillingStatus'],
                    'PaymentStatus' => $finalPaymentStatus,
                    'BillingNumber' => $billingNumber,
                    'BilledDate' => $billedDate,
                    'BilledAmount' => $statusInfo['TotalBilledAmount'],
                    'Remarks' => $remarks,
                    'CreatedBy' => $createdBy
                );
                $res = CreateUpdateTicketBillingTracking($conn, $payload);
            } else {
                $payload = array(
                    'TicketPK' => (int)$ticketInfo['ID'],
                    'BillingStatus' => 'Billed',
                    'PaymentStatus' => $paymentStatus,
                    'BillingNumber' => $billingNumber,
                    'BilledDate' => $billedDate,
                    'Remarks' => $remarks,
                    'CreatedBy' => $createdBy
                );
                $res = CreateUpdateTicketBillingTracking($conn, $payload);
            }

            if ($res['error'] == false) {
                $success++;
                $totalAmount += isset($res['BilledAmount']) ? (float)$res['BilledAmount'] : 0.0;
            }
        }
    }

    if ($success <= 0) {
        throw new Exception('No tickets processed');
    }

    if ($billingNumber !== '') {
        $safeBillingNo = mysqli_real_escape_string($conn, $billingNumber);
        $checkSql = "SELECT ID FROM ticket_billing_tracking_amount WHERE BillingNumber = '$safeBillingNo' LIMIT 1";
        $checkRes = mysqli_query($conn, $checkSql);
        if ($checkRes && mysqli_num_rows($checkRes) > 0) {
            $update = " TotalAmount = $totalAmount, UpdatedAt = NOW() WHERE BillingNumber = '$safeBillingNo'";
            _UpdateTableRecords($conn, 'ticket_billing_tracking_amount', $update);
        } else {
            $ins = "INSERT INTO ticket_billing_tracking_amount (BillingNumber, TotalAmount, UpdatedAt)
                    VALUES ('$safeBillingNo', $totalAmount, NOW())";
            _InsertTableRecords($conn, $ins);
        }
    }

    foreach ($ticketPKs as $ticketPK) {
        $ticketPK = (int)$ticketPK;
        if ($ticketPK <= 0) {
            continue;
        }
        $stagePayload = array(
            'TicketPK' => $ticketPK,
            'BillingQueueStage' => 'BillingVerification',
            'CreatedBy' => $createdBy
        );
        $existingTrack = getExistingTicketBillingTracking($conn, $ticketPK);
        if ($existingTrack) {
            $stagePayload['BillingStatus'] = $existingTrack['BillingStatus'];
            $stagePayload['PaymentStatus'] = $existingTrack['PaymentStatus'];
            $stagePayload['BillingNumber'] = $billingNumber !== '' ? $billingNumber : (isset($existingTrack['BillingNumber']) ? $existingTrack['BillingNumber'] : '');
            $stagePayload['BilledDate'] = $billedDate;
            $stagePayload['BilledAmount'] = isset($existingTrack['BilledAmount']) ? (float)$existingTrack['BilledAmount'] : 0;
            $stagePayload['Remarks'] = $remarks;
        }
        CreateUpdateTicketBillingTracking($conn, $stagePayload);
    }

    mysqli_commit($conn);
    echo json_encode(array(
        'error' => false,
        'message' => ($billingMode === 'itemized')
            ? 'Partial line-item billing saved successfully. Moved to Billing Verification.'
            : $success . ' tickets billed successfully. Moved to Billing Verification.',
        'TotalAmount' => round($totalAmount, 2)
    ));
    exit;
} catch (Exception $e) {
    mysqli_rollback($conn);
    echo json_encode(array('error' => true, 'message' => $e->getMessage()));
}
?>
