<?php
if (!function_exists('_getTableDetails')) {
    require_once('../../controllers/common_controllers.php');
}

function ticket_billing_parse_date($value, $default)
{
    $value = trim((string)$value);
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
        return $value;
    }
    return $default;
}

function ticket_billing_geo_join_sql()
{
    return " LEFT JOIN state tb_state ON tb_state.StateName = b.BranchState AND tb_state.IsActive = 1
             LEFT JOIN region tb_region ON tb_region.ID = tb_state.RegionID";
}

function ticket_billing_resolve_queue_stage_expr()
{
    return "COALESCE(NULLIF(TRIM(bt.BillingQueueStage), ''), 'Eligible')";
}

function ticket_billing_valid_queue_stages()
{
    return array('Eligible', 'ReadyForBilling', 'BillingVerification', 'PaymentStatus', 'PartialBilling');
}

function ticket_billing_sm_verified_exists_sql()
{
    return "EXISTS (
                SELECT 1 FROM corporate_ticket_sm_verification smv
                WHERE smv.TicketPK = ct.ID
                AND smv.IsActive = 1
                AND smv.IsVerified = 1
            )";
}

function ticket_billing_is_ticket_sm_verified($conn, $ticketPK)
{
    $ticketPK = (int)$ticketPK;
    if ($ticketPK <= 0) {
        return false;
    }
    $sql = "SELECT 1 FROM corporate_ticket_sm_verification
            WHERE TicketPK = $ticketPK AND IsActive = 1 AND IsVerified = 1
            LIMIT 1";
    $res = mysqli_query($conn, $sql);
    return ($res && mysqli_num_rows($res) > 0);
}

function ticket_billing_append_queue_stage_filter($conn, $filters, &$where)
{
    $queueStage = isset($filters['QueueStage']) ? trim($filters['QueueStage']) : '';
    if ($queueStage === '' || strtoupper($queueStage) === 'ALL') {
        return;
    }

    $stageExpr = ticket_billing_resolve_queue_stage_expr();
    $safeStage = mysqli_real_escape_string($conn, $queueStage);

    if ($queueStage === 'Eligible') {
        $where .= " AND $stageExpr = 'Eligible'";
        $where .= " AND COALESCE(bt.BillingStatus, 'Unbilled') = 'Unbilled'";
        $where .= ' AND ' . ticket_billing_sm_verified_exists_sql();
        return;
    }

    if ($queueStage === 'PartialBilling') {
        $where .= " AND bt.ID IS NOT NULL";
        $where .= " AND (
            COALESCE(bt.BillingStatus, 'Unbilled') = 'Partially Billed'
            OR COALESCE(bt.PaymentStatus, 'Pending') = 'Partially Paid'
        )";
        return;
    }

    if ($queueStage === 'PaymentStatus') {
        $where .= " AND $stageExpr = 'PaymentStatus'";
        $where .= " AND COALESCE(bt.BillingStatus, 'Unbilled') = 'Billed'";
        return;
    }

    $where .= " AND $stageExpr = '$safeStage'";
}

function ticket_billing_append_common_filters($conn, $filters, &$where)
{
    $CorporateID = isset($filters['CorporateID']) ? (int)$filters['CorporateID'] : -1;
    $BranchID = isset($filters['BranchID']) ? (int)$filters['BranchID'] : -1;
    $RegionID = isset($filters['RegionID']) ? (int)$filters['RegionID'] : -1;
    $StateID = isset($filters['StateID']) ? (int)$filters['StateID'] : -1;
    $TicketStatus = isset($filters['TicketStatus']) ? trim($filters['TicketStatus']) : '';
    $TicketType = isset($filters['TicketType']) ? trim($filters['TicketType']) : '';

    if ($CorporateID != -1) {
        $where .= " AND ct.CorporateID = $CorporateID";
    }
    if ($BranchID != -1) {
        $where .= " AND ct.BranchID = $BranchID";
    }
    if ($RegionID != -1) {
        $where .= " AND tb_region.ID = $RegionID";
    }
    if ($StateID != -1) {
        $where .= " AND tb_state.ID = $StateID";
    }
    if ($TicketStatus !== '' && strtoupper($TicketStatus) !== 'ALL') {
        $safeStatus = mysqli_real_escape_string($conn, $TicketStatus);
        $where .= " AND ct.Status = '$safeStatus'";
    }
    if ($TicketType !== '' && strtoupper($TicketType) !== 'ALL') {
        $safeType = mysqli_real_escape_string($conn, $TicketType);
        $where .= " AND ct.Type = '$safeType'";
    }
}

function ticket_billing_get_latest_quotation_for_ticket($conn, $ticketPK)
{
    $ticketPK = (int)$ticketPK;
    $sql = "SELECT q1.ID, q1.QuotationStatus, q1.QuotationDate
            FROM corporate_ticket_quotation q1
            INNER JOIN (
                SELECT TicketID, MAX(ID) AS MaxID
                FROM corporate_ticket_quotation
                WHERE IsActive = 1
                AND TicketID = $ticketPK
                GROUP BY TicketID
            ) q2 ON q1.TicketID = q2.TicketID AND q1.ID = q2.MaxID";
    $result = mysqli_query($conn, $sql);
    if ($result && ($row = mysqli_fetch_assoc($result))) {
        return $row;
    }
    return null;
}

function ticket_billing_ticket_has_remaining_qty($conn, $ticketPK)
{
    $ticketPK = (int)$ticketPK;
    $latestQuotation = ticket_billing_get_latest_quotation_for_ticket($conn, $ticketPK);
    if (!$latestQuotation) {
        return false;
    }
    $quotationID = (int)$latestQuotation['ID'];
    $sql = "SELECT
                qi.ID,
                COALESCE(qi.Qty, 0) AS OriginalQty,
                COALESCE(bi.BilledQty, 0) AS AlreadyBilledQty
            FROM corporate_ticket_quotation_items qi
            LEFT JOIN (
                SELECT QuotationItemID, SUM(COALESCE(BilledQty, 0)) AS BilledQty
                FROM ticket_billing_line_items
                WHERE IsActive = 1 AND TicketPK = $ticketPK
                GROUP BY QuotationItemID
            ) bi ON bi.QuotationItemID = qi.ID
            WHERE qi.IsActive = 1 AND qi.QuotationID = $quotationID";
    $res = mysqli_query($conn, $sql);
    if (!$res) {
        return false;
    }
    while ($row = mysqli_fetch_assoc($res)) {
        $remaining = round((float)$row['OriginalQty'] - (float)$row['AlreadyBilledQty'], 2);
        if ($remaining > 0) {
            return true;
        }
    }
    return false;
}

function ticket_billing_get_remaining_amount($conn, $ticketPK, $calculatedAmount, $billedAmount)
{
    $calculatedAmount = (float)$calculatedAmount;
    $billedAmount = (float)$billedAmount;
    if ($calculatedAmount <= 0) {
        $latestQuotation = ticket_billing_get_latest_quotation_for_ticket($conn, $ticketPK);
        if ($latestQuotation) {
            $calculatedAmount = ticket_billing_get_quotation_total($conn, (int)$latestQuotation['ID']);
        }
    }
    $remaining = round(max($calculatedAmount - $billedAmount, 0), 2);
    return $remaining;
}

function ticket_billing_get_quotation_total($conn, $quotationID)
{
    $quotationID = (int)$quotationID;
    if ($quotationID <= 0) {
        return 0.0;
    }
    $sql = "SELECT QuotationID, SUM(COALESCE(TotalPrice,0)) AS TotalAmountNoGST
            FROM corporate_ticket_quotation_items
            WHERE IsActive = 1
            AND QuotationID = $quotationID
            GROUP BY QuotationID";
    $result = mysqli_query($conn, $sql);
    if ($result && ($row = mysqli_fetch_assoc($result))) {
        return (float)$row['TotalAmountNoGST'];
    }
    return 0.0;
}

function GetTicketsForBilling($conn, $filters)
{
    $response = array('error' => false, 'data' => array(), 'sql_debug' => '');

    $CorporateID = isset($filters['CorporateID']) ? (int)$filters['CorporateID'] : -1;
    $BranchID = isset($filters['BranchID']) ? (int)$filters['BranchID'] : -1;
    $TicketIDSearch = isset($filters['TicketID']) ? trim($filters['TicketID']) : '';
    $TicketStatus = isset($filters['TicketStatus']) ? trim($filters['TicketStatus']) : '';
    $TicketType = isset($filters['TicketType']) ? trim($filters['TicketType']) : '';
    $StartDate = ticket_billing_parse_date(isset($filters['StartDate']) ? $filters['StartDate'] : '', date('Y-m-01'));
    $EndDate = ticket_billing_parse_date(isset($filters['EndDate']) ? $filters['EndDate'] : '', date('Y-m-t'));

    $where = " WHERE ct.IsActive = 1 AND ct.CreatedDate >= '$StartDate' AND ct.CreatedDate <= '$EndDate'";
    if ($TicketIDSearch !== '') {
        $safeTicket = mysqli_real_escape_string($conn, $TicketIDSearch);
        $where .= " AND ct.TicketID = '$safeTicket'";
    }
    ticket_billing_append_common_filters($conn, $filters, $where);
    ticket_billing_append_queue_stage_filter($conn, $filters, $where);

    $sql = "SELECT
                ct.ID AS TicketPK,
                ct.TicketID AS TicketNumber,
                ct.CorporateID,
                ct.BranchID,
                ct.BranchAssetID,
                ct.Type AS TicketType,
                ct.Status AS TicketStatus,
                ct.QuotationStatus AS TicketQuotationStatus,
                ct.CreatedDate,
                ct.CloseDate,
                COALESCE(c.CompanyName, 'N/A') AS CompanyName,
                COALESCE(b.BranchSite, 'N/A') AS BranchSite,
                COALESCE(b.BranchCode, '') AS BranchCode,
                COALESCE(bt.ID, 0) AS BillingTrackingID,
                COALESCE(bt.BillingStatus, 'Unbilled') AS BillingStatus,
                " . ticket_billing_resolve_queue_stage_expr() . " AS BillingQueueStage,
                bt.QueuedDate,
                COALESCE(bt.QueuedBy, '') AS QueuedBy,
                bt.VerifiedDate,
                COALESCE(bt.VerifiedBy, '') AS VerifiedBy,
                COALESCE(bt.PaymentStatus, 'Pending') AS PaymentStatus,
                COALESCE(bt.CalculatedAmount, 0) AS StoredCalculatedAmount,
                COALESCE(bt.BilledAmount, 0) AS BilledAmount,
                COALESCE(bt.BillingNumber, '') AS BillingNumber,
                bt.BilledDate,
                COALESCE(bt.Remarks, '') AS Remarks,
                COALESCE(lq.QuotationID, 0) AS QuotationID,
                COALESCE(lq.QuotationStatus, 'Pending') AS QuotationStatus,
                lq.QuotationDate,
                ROUND(COALESCE(NULLIF(bt.CalculatedAmount, 0), qi.TotalAmountNoGST, 0), 2) AS CalculatedAmount,
                ROUND(GREATEST(
                    COALESCE(NULLIF(bt.CalculatedAmount, 0), qi.TotalAmountNoGST, 0) - COALESCE(bt.BilledAmount, 0),
                    0
                ), 2) AS RemainingAmount,
                COALESCE(smv.CustomerPoAvailable, 0) AS CustomerPoAvailable,
                COALESCE(smv.CustomerPoRemarks, '') AS CustomerPoRemarks
            FROM corporate_tickets ct
            LEFT JOIN company c ON ct.CorporateID = c.ID AND c.IsActive = 1
            LEFT JOIN branch b ON ct.BranchID = b.ID AND b.IsActive = 1
            " . ticket_billing_geo_join_sql() . "
            LEFT JOIN ticket_billing_tracking bt ON ct.ID = bt.TicketPK AND bt.IsActive = 1
            LEFT JOIN (
                SELECT q1.TicketID, q1.ID AS QuotationID, q1.QuotationStatus, q1.QuotationDate
                FROM corporate_ticket_quotation q1
                INNER JOIN (
                    SELECT TicketID, MAX(ID) AS MaxID
                    FROM corporate_ticket_quotation
                    WHERE IsActive = 1
                    GROUP BY TicketID
                ) q2 ON q1.TicketID = q2.TicketID AND q1.ID = q2.MaxID
            ) lq ON lq.TicketID = ct.ID
            LEFT JOIN (
                SELECT QuotationID, SUM(COALESCE(TotalPrice, 0)) AS TotalAmountNoGST
                FROM corporate_ticket_quotation_items
                WHERE IsActive = 1
                GROUP BY QuotationID
            ) qi ON qi.QuotationID = lq.QuotationID
            LEFT JOIN corporate_ticket_sm_verification smv ON ct.ID = smv.TicketPK AND smv.IsActive = 1 AND smv.IsVerified = 1
            $where
            ORDER BY ct.CreatedDate DESC, ct.ID DESC";

    $response['sql_debug'] = $sql;
    $result = mysqli_query($conn, $sql);
    if (!$result) {
        return array(
            'error' => true,
            'message' => 'SQL Error: ' . mysqli_error($conn),
            'sql_debug' => $sql
        );
    }

    while ($row = mysqli_fetch_assoc($result)) {
        if ((int)$row['BillingTrackingID'] === 0) {
            $row['BillingStatus'] = 'Unbilled';
            $row['BillingQueueStage'] = 'Eligible';
            $row['PaymentStatus'] = 'Pending';
            $row['BilledAmount'] = 0;
            $row['BillingNumber'] = '';
            $row['BilledDate'] = null;
            $row['QueuedDate'] = null;
            $row['QueuedBy'] = '';
            $row['VerifiedDate'] = null;
            $row['VerifiedBy'] = '';
            $row['RemainingAmount'] = (float)$row['CalculatedAmount'];
        }
        $row['HasRemainingQty'] = ticket_billing_ticket_has_remaining_qty($conn, (int)$row['TicketPK']) ? 1 : 0;
        if ((float)$row['RemainingAmount'] <= 0 && $row['HasRemainingQty']) {
            $row['RemainingAmount'] = ticket_billing_get_remaining_amount(
                $conn,
                (int)$row['TicketPK'],
                (float)$row['CalculatedAmount'],
                (float)$row['BilledAmount']
            );
        }
        $response['data'][] = $row;
    }

    return $response;
}

function CreateUpdateTicketBillingTracking($conn, $data)
{
    $TicketPK = isset($data['TicketPK']) ? (int)$data['TicketPK'] : 0;
    if ($TicketPK <= 0) {
        return array('error' => true, 'message' => 'Ticket primary key is required');
    }

    $BillingStatus = isset($data['BillingStatus']) ? trim($data['BillingStatus']) : 'Unbilled';
    $BillingQueueStage = isset($data['BillingQueueStage']) ? trim($data['BillingQueueStage']) : '';
    $PaymentStatus = isset($data['PaymentStatus']) ? trim($data['PaymentStatus']) : 'Pending';
    $QueuedDate = isset($data['QueuedDate']) ? $data['QueuedDate'] : null;
    $QueuedBy = isset($data['QueuedBy']) ? mysqli_real_escape_string($conn, $data['QueuedBy']) : '';
    $VerifiedDate = isset($data['VerifiedDate']) ? $data['VerifiedDate'] : null;
    $VerifiedBy = isset($data['VerifiedBy']) ? mysqli_real_escape_string($conn, $data['VerifiedBy']) : '';
    $BillingNumber = isset($data['BillingNumber']) ? trim($data['BillingNumber']) : '';
    $BilledDate = ticket_billing_parse_date(isset($data['BilledDate']) ? $data['BilledDate'] : '', date('Y-m-d'));
    $Remarks = isset($data['Remarks']) ? mysqli_real_escape_string($conn, $data['Remarks']) : '';
    $CreatedBy = isset($data['CreatedBy']) ? mysqli_real_escape_string($conn, $data['CreatedBy']) : 'System';

    $ticket = _getTableDetails($conn, 'corporate_tickets', ' where ID = ' . $TicketPK . ' AND IsActive = 1');
    if (!$ticket) {
        return array('error' => true, 'message' => 'Ticket not found');
    }

    $latestQuotation = ticket_billing_get_latest_quotation_for_ticket($conn, $TicketPK);
    $quotationID = 0;
    $calculatedAmount = 0.0;
    if ($latestQuotation) {
        $quotationID = (int)$latestQuotation['ID'];
        $calculatedAmount = ticket_billing_get_quotation_total($conn, $quotationID);
    }

    $BilledAmount = isset($data['BilledAmount']) ? (float)$data['BilledAmount'] : 0.0;
    if ($BillingStatus === 'Billed' && $BilledAmount <= 0) {
        $BilledAmount = $calculatedAmount;
    }

    $existing = _getTableDetails($conn, 'ticket_billing_tracking', ' where TicketPK = ' . $TicketPK . ' AND IsActive = 1');
    $today = date('Y-m-d');
    $now = date('H:i:s');

    if ($BillingQueueStage === '' && $existing) {
        $BillingQueueStage = isset($existing['BillingQueueStage']) ? trim($existing['BillingQueueStage']) : 'Eligible';
    }
    if ($BillingQueueStage === '') {
        $BillingQueueStage = 'Eligible';
    }

    if ($existing) {
        $ID = (int)$existing['ID'];
        $queueStageSql = " BillingQueueStage = '" . mysqli_real_escape_string($conn, $BillingQueueStage) . "',";
        if (array_key_exists('QueuedDate', $data)) {
            if ($QueuedDate === null || $QueuedDate === '') {
                $queueStageSql .= " QueuedDate = NULL, QueuedBy = NULL,";
            } else {
                $queueStageSql .= " QueuedDate = '" . ticket_billing_parse_date($QueuedDate, date('Y-m-d')) . "', QueuedBy = '$QueuedBy',";
            }
        } elseif ($QueuedDate !== null && $QueuedDate !== '') {
            $queueStageSql .= " QueuedDate = '" . ticket_billing_parse_date($QueuedDate, date('Y-m-d')) . "', QueuedBy = '$QueuedBy',";
        }
        if (array_key_exists('VerifiedDate', $data)) {
            if ($VerifiedDate === null || $VerifiedDate === '') {
                $queueStageSql .= " VerifiedDate = NULL, VerifiedBy = NULL,";
            } else {
                $queueStageSql .= " VerifiedDate = '" . ticket_billing_parse_date($VerifiedDate, date('Y-m-d')) . "', VerifiedBy = '$VerifiedBy',";
            }
        } elseif ($VerifiedDate !== null && $VerifiedDate !== '') {
            $queueStageSql .= " VerifiedDate = '" . ticket_billing_parse_date($VerifiedDate, date('Y-m-d')) . "', VerifiedBy = '$VerifiedBy',";
        }
        $update = " BillingStatus = '$BillingStatus',
                    $queueStageSql
                    PaymentStatus = '$PaymentStatus',
                    QuotationID = $quotationID,
                    CalculatedAmount = $calculatedAmount,
                    BilledAmount = $BilledAmount,
                    BillingNumber = " . ($BillingNumber !== '' ? "'$BillingNumber'" : "NULL") . ",
                    BilledDate = '$BilledDate',
                    Remarks = '$Remarks',
                    UpdatedBy = '$CreatedBy',
                    UpdatedDate = '$today',
                    UpdatedTime = '$now'
                    where ID = $ID";
        $result = _UpdateTableRecords($conn, 'ticket_billing_tracking', $update);
    } else {
        $queuedDateSql = ($QueuedDate !== null && $QueuedDate !== '') ? "'" . ticket_billing_parse_date($QueuedDate, $today) . "'" : 'NULL';
        $verifiedDateSql = ($VerifiedDate !== null && $VerifiedDate !== '') ? "'" . ticket_billing_parse_date($VerifiedDate, $today) . "'" : 'NULL';
        $sql = "INSERT INTO ticket_billing_tracking
                (TicketPK, TicketID, CorporateID, BranchID, BranchAssetID, TicketStatus, QuotationID, BillingStatus, BillingQueueStage, QueuedDate, QueuedBy, VerifiedDate, VerifiedBy, PaymentStatus, CalculatedAmount, BilledAmount, BillingNumber, BilledDate, Remarks, CreatedBy, CreatedDate, CreatedTime, IsActive)
                VALUES
                ($TicketPK, '" . mysqli_real_escape_string($conn, $ticket['TicketID']) . "', " . (int)$ticket['CorporateID'] . ", " . (int)$ticket['BranchID'] . ", " . (int)$ticket['BranchAssetID'] . ",
                '" . mysqli_real_escape_string($conn, $ticket['Status']) . "', $quotationID, '$BillingStatus', '" . mysqli_real_escape_string($conn, $BillingQueueStage) . "',
                $queuedDateSql, " . ($QueuedBy !== '' ? "'$QueuedBy'" : "NULL") . ", $verifiedDateSql, " . ($VerifiedBy !== '' ? "'$VerifiedBy'" : "NULL") . ",
                '$PaymentStatus', $calculatedAmount, $BilledAmount, " . ($BillingNumber !== '' ? "'$BillingNumber'" : "NULL") . ", '$BilledDate', '$Remarks',
                '$CreatedBy', '$today', '$now', 1)";
        $result = _InsertTableRecords($conn, $sql);
    }

    if (isset($result['error']) && $result['error'] == false) {
        return array(
            'error' => false,
            'message' => 'Billing updated successfully',
            'CalculatedAmount' => $calculatedAmount,
            'BilledAmount' => $BilledAmount
        );
    }

    return array('error' => true, 'message' => isset($result['message']) ? $result['message'] : 'Unable to update billing');
}

function ToggleTicketBillingStatus($conn, $data)
{
    $TicketPK = isset($data['TicketPK']) ? (int)$data['TicketPK'] : 0;
    $current = isset($data['CurrentBillingStatus']) ? trim($data['CurrentBillingStatus']) : 'Unbilled';
    $newStatus = ($current === 'Billed') ? 'Unbilled' : 'Billed';

    $payload = array(
        'TicketPK' => $TicketPK,
        'BillingStatus' => $newStatus,
        'PaymentStatus' => ($newStatus === 'Billed') ? 'Billed' : 'Pending',
        'BilledDate' => date('Y-m-d'),
        'CreatedBy' => isset($data['CreatedBy']) ? $data['CreatedBy'] : 'System'
    );
    $res = CreateUpdateTicketBillingTracking($conn, $payload);
    if ($res['error'] == false) {
        $res['new_status'] = $newStatus;
    }
    return $res;
}

function GetTicketBillingStatistics($conn, $filters)
{
    $CorporateID = isset($filters['CorporateID']) ? (int)$filters['CorporateID'] : -1;
    $BranchID = isset($filters['BranchID']) ? (int)$filters['BranchID'] : -1;
    $TicketStatus = isset($filters['TicketStatus']) ? trim($filters['TicketStatus']) : '';
    $TicketType = isset($filters['TicketType']) ? trim($filters['TicketType']) : '';
    $StartDate = ticket_billing_parse_date(isset($filters['StartDate']) ? $filters['StartDate'] : '', date('Y-m-01'));
    $EndDate = ticket_billing_parse_date(isset($filters['EndDate']) ? $filters['EndDate'] : '', date('Y-m-t'));

    $where = " WHERE ct.IsActive = 1 AND ct.CreatedDate >= '$StartDate' AND ct.CreatedDate <= '$EndDate'";
    ticket_billing_append_common_filters($conn, $filters, $where);

    $sql = "SELECT
                COUNT(*) AS total_tickets,
                SUM(COALESCE(NULLIF(bt.CalculatedAmount, 0), qi.TotalAmountNoGST, 0)) AS total_amount,
                SUM(CASE WHEN COALESCE(lq.QuotationStatus, '') = 'Quote Approved' THEN 1 ELSE 0 END) AS approved_quote_count,
                SUM(CASE
                    WHEN COALESCE(lq.QuotationStatus, '') = 'Quote Approved'
                        THEN COALESCE(NULLIF(bt.CalculatedAmount, 0), qi.TotalAmountNoGST, 0)
                    ELSE 0
                END) AS approved_quote_amount,
                SUM(CASE WHEN COALESCE(lq.QuotationStatus, '') <> 'Quote Approved' THEN 1 ELSE 0 END) AS not_approved_quote_count,
                SUM(CASE
                    WHEN COALESCE(lq.QuotationStatus, '') <> 'Quote Approved'
                        THEN COALESCE(NULLIF(bt.CalculatedAmount, 0), qi.TotalAmountNoGST, 0)
                    ELSE 0
                END) AS not_approved_quote_amount,
                SUM(CASE WHEN COALESCE(bt.BillingStatus, 'Unbilled') = 'Billed' THEN 1 ELSE 0 END) AS billed_count,
                SUM(CASE WHEN COALESCE(bt.BillingStatus, 'Unbilled') = 'Partially Billed' THEN 1 ELSE 0 END) AS partial_count,
                SUM(CASE WHEN COALESCE(bt.BillingStatus, 'Unbilled') = 'Unbilled' THEN 1 ELSE 0 END) AS unbilled_count,
                SUM(CASE
                    WHEN COALESCE(bt.BillingStatus, 'Unbilled') = 'Billed'
                        THEN COALESCE(NULLIF(bt.BilledAmount, 0), NULLIF(bt.CalculatedAmount, 0), qi.TotalAmountNoGST, 0)
                    ELSE 0
                END) AS billed_amount,
                SUM(CASE
                    WHEN COALESCE(bt.BillingStatus, 'Unbilled') = 'Partially Billed'
                        THEN COALESCE(NULLIF(bt.BilledAmount, 0), 0)
                    ELSE 0
                END) AS partial_amount,
                SUM(CASE
                    WHEN COALESCE(bt.BillingStatus, 'Unbilled') = 'Unbilled'
                        THEN COALESCE(NULLIF(bt.CalculatedAmount, 0), qi.TotalAmountNoGST, 0)
                    ELSE 0
                END) AS unbilled_amount,
                SUM(CASE WHEN COALESCE(bt.PaymentStatus, 'Pending') = 'Partially Paid' THEN 1 ELSE 0 END) AS partially_paid_count,
                SUM(CASE
                    WHEN COALESCE(bt.PaymentStatus, 'Pending') = 'Partially Paid'
                        THEN COALESCE(NULLIF(bt.BilledAmount, 0), NULLIF(bt.CalculatedAmount, 0), qi.TotalAmountNoGST, 0)
                    ELSE 0
                END) AS partially_paid_amount
            FROM corporate_tickets ct
            LEFT JOIN branch b ON ct.BranchID = b.ID AND b.IsActive = 1
            " . ticket_billing_geo_join_sql() . "
            LEFT JOIN ticket_billing_tracking bt ON ct.ID = bt.TicketPK AND bt.IsActive = 1
            LEFT JOIN (
                SELECT q1.TicketID, q1.ID AS QuotationID, q1.QuotationStatus
                FROM corporate_ticket_quotation q1
                INNER JOIN (
                    SELECT TicketID, MAX(ID) AS MaxID
                    FROM corporate_ticket_quotation
                    WHERE IsActive = 1
                    GROUP BY TicketID
                ) q2 ON q1.TicketID = q2.TicketID AND q1.ID = q2.MaxID
            ) lq ON lq.TicketID = ct.ID
            LEFT JOIN (
                SELECT QuotationID, SUM(COALESCE(TotalPrice, 0)) AS TotalAmountNoGST
                FROM corporate_ticket_quotation_items
                WHERE IsActive = 1
                GROUP BY QuotationID
            ) qi ON qi.QuotationID = lq.QuotationID
            $where";

    $res = mysqli_query($conn, $sql);
    if (!$res) {
        return array('error' => true, 'message' => 'SQL Error: ' . mysqli_error($conn));
    }
    $row = mysqli_fetch_assoc($res);

    return array(
        'error' => false,
        'data' => array(
            'total_tickets' => (int)$row['total_tickets'],
            'total_amount' => (float)$row['total_amount'],
            'quotation_status' => array(
                'Approved' => (int)$row['approved_quote_count'],
                'NotApproved' => (int)$row['not_approved_quote_count']
            ),
            'quotation_amounts' => array(
                'Approved' => (float)$row['approved_quote_amount'],
                'NotApproved' => (float)$row['not_approved_quote_amount']
            ),
            'billing_status' => array(
                'Billed' => (int)$row['billed_count'],
                'PartiallyBilled' => (int)$row['partial_count'],
                'Unbilled' => (int)$row['unbilled_count']
            ),
            'billing_amounts' => array(
                'Billed' => (float)$row['billed_amount'],
                'PartiallyBilled' => (float)$row['partial_amount'],
                'Unbilled' => (float)$row['unbilled_amount']
            ),
            'payment_status' => array('Pending' => 0, 'Closed' => 0, 'Billed' => 0),
            'payment_amounts' => array('Pending' => 0.0, 'PartiallyPaid' => (float)$row['partially_paid_amount'], 'Closed' => 0.0, 'Billed' => 0.0),
            'partially_paid' => array(
                'count' => (int)$row['partially_paid_count'],
                'amount' => (float)$row['partially_paid_amount']
            )
        )
    );
}

function ticket_billing_get_issuer_details($conn)
{
    $sql = "SELECT CompanyName, CompanyAddress, GstNumber, PanNumber, Email, Phone, HeaderImage, StampImage
            FROM quote_company_details
            WHERE IsActive = 1
            ORDER BY ID ASC
            LIMIT 1";
    $res = mysqli_query($conn, $sql);
    if ($res && ($row = mysqli_fetch_assoc($res))) {
        if (!empty($row['HeaderImage'])) {
            $row['HeaderImageUrl'] = '../media/pdf-assets/' . basename($row['HeaderImage']);
        } else {
            $row['HeaderImageUrl'] = '';
        }
        return $row;
    }
    return array(
        'CompanyName' => 'TechXpert',
        'CompanyAddress' => '',
        'GstNumber' => '',
        'PanNumber' => '',
        'Email' => '',
        'Phone' => '',
        'HeaderImage' => '',
        'HeaderImageUrl' => '',
        'StampImage' => ''
    );
}

function GetBillingInvoicesList($conn, $filters)
{
    $CorporateID = isset($filters['CorporateID']) ? (int)$filters['CorporateID'] : -1;
    $BranchID = isset($filters['BranchID']) ? (int)$filters['BranchID'] : -1;
    $BillingNumberSearch = isset($filters['BillingNumber']) ? trim($filters['BillingNumber']) : '';
    $StartDate = ticket_billing_parse_date(isset($filters['StartDate']) ? $filters['StartDate'] : '', date('Y-m-01', strtotime('-11 months')));
    $EndDate = ticket_billing_parse_date(isset($filters['EndDate']) ? $filters['EndDate'] : '', date('Y-m-t'));

    $where = " WHERE li.IsActive = 1 AND li.BillingNumber IS NOT NULL AND TRIM(li.BillingNumber) <> ''";
    $where .= " AND li.BilledDate >= '$StartDate' AND li.BilledDate <= '$EndDate'";
    if ($BillingNumberSearch !== '') {
        $safeBilling = mysqli_real_escape_string($conn, $BillingNumberSearch);
        $where .= " AND li.BillingNumber LIKE '%$safeBilling%'";
    }
    $invoiceFilters = array(
        'CorporateID' => $CorporateID,
        'BranchID' => $BranchID,
        'RegionID' => isset($filters['RegionID']) ? (int)$filters['RegionID'] : -1,
        'StateID' => isset($filters['StateID']) ? (int)$filters['StateID'] : -1,
        'TicketStatus' => '',
        'TicketType' => ''
    );
    ticket_billing_append_common_filters($conn, $invoiceFilters, $where);

    $sql = "SELECT
                li.BillingNumber,
                MIN(li.BilledDate) AS BilledDate,
                MAX(li.CreatedDate) AS LastEntryDate,
                COUNT(DISTINCT li.TicketPK) AS TicketCount,
                ROUND(SUM(COALESCE(li.BilledLineAmount, 0)), 2) AS LineItemsTotal,
                MAX(li.PaymentStatus) AS PaymentStatus,
                GROUP_CONCAT(DISTINCT COALESCE(c.CompanyName, 'N/A') ORDER BY c.CompanyName SEPARATOR ', ') AS CompanyNames,
                COALESCE(tba.TotalAmount, 0) AS StoredTotalAmount,
                tba.UpdatedAt
            FROM ticket_billing_line_items li
            INNER JOIN corporate_tickets ct ON ct.ID = li.TicketPK AND ct.IsActive = 1
            LEFT JOIN branch b ON ct.BranchID = b.ID AND b.IsActive = 1
            " . ticket_billing_geo_join_sql() . "
            LEFT JOIN company c ON ct.CorporateID = c.ID AND c.IsActive = 1
            LEFT JOIN ticket_billing_tracking_amount tba ON tba.BillingNumber = li.BillingNumber
            $where
            GROUP BY li.BillingNumber, tba.TotalAmount, tba.UpdatedAt
            ORDER BY MIN(li.BilledDate) DESC, li.BillingNumber DESC";

    $res = mysqli_query($conn, $sql);
    if (!$res) {
        return array('error' => true, 'message' => 'SQL Error: ' . mysqli_error($conn));
    }

    $data = array();
    while ($row = mysqli_fetch_assoc($res)) {
        $lineTotal = (float)$row['LineItemsTotal'];
        $storedTotal = (float)$row['StoredTotalAmount'];
        // Source of truth for invoice total should be billed line items.
        $row['TotalAmount'] = $lineTotal > 0 ? $lineTotal : $storedTotal;
        $data[] = $row;
    }

    return array('error' => false, 'data' => $data);
}

function GetBillingInvoiceDetail($conn, $billingNumber)
{
    $billingNumber = trim((string)$billingNumber);
    if ($billingNumber === '') {
        return array('error' => true, 'message' => 'Billing number is required');
    }
    $safeBilling = mysqli_real_escape_string($conn, $billingNumber);

    $headerSql = "SELECT
                    li.BillingNumber,
                    MIN(li.BilledDate) AS BilledDate,
                    MAX(li.PaymentStatus) AS PaymentStatus,
                    MAX(COALESCE(li.Remarks, '')) AS Remarks,
                    ROUND(SUM(COALESCE(li.BilledLineAmount, 0)), 2) AS LineItemsTotal,
                    COUNT(DISTINCT li.TicketPK) AS TicketCount,
                    COALESCE(tba.TotalAmount, 0) AS StoredTotalAmount
                  FROM ticket_billing_line_items li
                  LEFT JOIN ticket_billing_tracking_amount tba ON tba.BillingNumber = li.BillingNumber
                  WHERE li.IsActive = 1 AND li.BillingNumber = '$safeBilling'
                  GROUP BY li.BillingNumber, tba.TotalAmount
                  LIMIT 1";
    $headerRes = mysqli_query($conn, $headerSql);
    if (!$headerRes || mysqli_num_rows($headerRes) === 0) {
        return array('error' => true, 'message' => 'Billing invoice not found');
    }
    $header = mysqli_fetch_assoc($headerRes);
    $lineTotal = (float)$header['LineItemsTotal'];
    $storedTotal = (float)$header['StoredTotalAmount'];
    // Keep invoice printout consistent with visible line items.
    $header['TotalAmount'] = $lineTotal > 0 ? $lineTotal : $storedTotal;

    $ticketsSql = "SELECT
                        li.TicketPK,
                        li.TicketID,
                        ct.Type AS TicketType,
                        ct.Status AS TicketStatus,
                        ct.CreatedDate AS TicketCreatedDate,
                        COALESCE(c.CompanyName, 'N/A') AS CompanyName,
                        COALESCE(b.BranchSite, 'N/A') AS BranchSite,
                        COALESCE(b.BranchCode, '') AS BranchCode,
                        ROUND(SUM(COALESCE(li.BilledLineAmount, 0)), 2) AS TicketBilledAmount
                    FROM ticket_billing_line_items li
                    INNER JOIN corporate_tickets ct ON ct.ID = li.TicketPK AND ct.IsActive = 1
                    LEFT JOIN company c ON ct.CorporateID = c.ID AND c.IsActive = 1
                    LEFT JOIN branch b ON ct.BranchID = b.ID AND b.IsActive = 1
                    WHERE li.IsActive = 1 AND li.BillingNumber = '$safeBilling'
                    GROUP BY li.TicketPK, li.TicketID, ct.Type, ct.Status, ct.CreatedDate, c.CompanyName, b.BranchSite, b.BranchCode
                    ORDER BY li.TicketID ASC";
    $ticketsRes = mysqli_query($conn, $ticketsSql);
    if (!$ticketsRes) {
        return array('error' => true, 'message' => 'SQL Error: ' . mysqli_error($conn));
    }

    $tickets = array();
    while ($ticket = mysqli_fetch_assoc($ticketsRes)) {
        $ticketPK = (int)$ticket['TicketPK'];
        $linesSql = "SELECT
                        LineItemName,
                        OriginalQty,
                        BilledQty,
                        PerItemPrice,
                        BilledLineAmount
                     FROM ticket_billing_line_items
                     WHERE IsActive = 1
                     AND BillingNumber = '$safeBilling'
                     AND TicketPK = $ticketPK
                     ORDER BY ID ASC";
        $linesRes = mysqli_query($conn, $linesSql);
        $lines = array();
        if ($linesRes) {
            while ($line = mysqli_fetch_assoc($linesRes)) {
                $lines[] = $line;
            }
        }
        $ticket['line_items'] = $lines;
        $tickets[] = $ticket;
    }

    $companies = array();
    foreach ($tickets as $ticket) {
        $name = isset($ticket['CompanyName']) ? trim($ticket['CompanyName']) : '';
        if ($name !== '' && $name !== 'N/A' && !in_array($name, $companies, true)) {
            $companies[] = $name;
        }
    }

    return array(
        'error' => false,
        'data' => array(
            'header' => $header,
            'issuer' => ticket_billing_get_issuer_details($conn),
            'tickets' => $tickets,
            'bill_to_companies' => $companies
        )
    );
}

function GetBillingQueueCounts($conn, $filters)
{
    $StartDate = ticket_billing_parse_date(isset($filters['StartDate']) ? $filters['StartDate'] : '', date('Y-m-01'));
    $EndDate = ticket_billing_parse_date(isset($filters['EndDate']) ? $filters['EndDate'] : '', date('Y-m-t'));
    $stageExpr = ticket_billing_resolve_queue_stage_expr();

    $where = " WHERE ct.IsActive = 1 AND ct.CreatedDate >= '$StartDate' AND ct.CreatedDate <= '$EndDate'";
    ticket_billing_append_common_filters($conn, $filters, $where);

    $sql = "SELECT
                SUM(CASE
                    WHEN $stageExpr = 'Eligible'
                         AND COALESCE(bt.BillingStatus, 'Unbilled') = 'Unbilled'
                         AND " . ticket_billing_sm_verified_exists_sql() . "
                    THEN 1 ELSE 0 END) AS eligible_count,
                SUM(CASE WHEN $stageExpr = 'ReadyForBilling' THEN 1 ELSE 0 END) AS ready_count,
                SUM(CASE WHEN $stageExpr = 'BillingVerification' THEN 1 ELSE 0 END) AS verification_count,
                SUM(CASE
                    WHEN $stageExpr = 'PaymentStatus'
                         AND COALESCE(bt.BillingStatus, 'Unbilled') = 'Billed'
                    THEN 1 ELSE 0 END) AS payment_count,
                SUM(CASE
                    WHEN bt.ID IS NOT NULL AND (
                        COALESCE(bt.BillingStatus, 'Unbilled') = 'Partially Billed'
                        OR COALESCE(bt.PaymentStatus, 'Pending') = 'Partially Paid'
                    )
                    THEN 1 ELSE 0 END) AS partial_billing_count
            FROM corporate_tickets ct
            LEFT JOIN branch b ON ct.BranchID = b.ID AND b.IsActive = 1
            " . ticket_billing_geo_join_sql() . "
            LEFT JOIN ticket_billing_tracking bt ON ct.ID = bt.TicketPK AND bt.IsActive = 1
            LEFT JOIN (
                SELECT q1.TicketID, q1.QuotationStatus
                FROM corporate_ticket_quotation q1
                INNER JOIN (
                    SELECT TicketID, MAX(ID) AS MaxID
                    FROM corporate_ticket_quotation
                    WHERE IsActive = 1
                    GROUP BY TicketID
                ) q2 ON q1.TicketID = q2.TicketID AND q1.ID = q2.MaxID
            ) lq ON lq.TicketID = ct.ID
            $where";

    $res = mysqli_query($conn, $sql);
    if (!$res) {
        return array('error' => true, 'message' => 'SQL Error: ' . mysqli_error($conn));
    }
    $row = mysqli_fetch_assoc($res);

    $invoiceWhere = " WHERE li.IsActive = 1 AND li.BillingNumber IS NOT NULL AND TRIM(li.BillingNumber) <> ''";
    $invoiceWhere .= " AND li.BilledDate >= '$StartDate' AND li.BilledDate <= '$EndDate'";
    $invoiceFilters = array(
        'CorporateID' => isset($filters['CorporateID']) ? (int)$filters['CorporateID'] : -1,
        'BranchID' => isset($filters['BranchID']) ? (int)$filters['BranchID'] : -1,
        'RegionID' => isset($filters['RegionID']) ? (int)$filters['RegionID'] : -1,
        'StateID' => isset($filters['StateID']) ? (int)$filters['StateID'] : -1,
        'TicketStatus' => '',
        'TicketType' => ''
    );
    ticket_billing_append_common_filters($conn, $invoiceFilters, $invoiceWhere);
    $invoiceSql = "SELECT COUNT(DISTINCT li.BillingNumber) AS invoice_count
                   FROM ticket_billing_line_items li
                   INNER JOIN corporate_tickets ct ON ct.ID = li.TicketPK AND ct.IsActive = 1
                   LEFT JOIN branch b ON ct.BranchID = b.ID AND b.IsActive = 1
                   " . ticket_billing_geo_join_sql() . "
                   $invoiceWhere";
    $invoiceRes = mysqli_query($conn, $invoiceSql);
    $invoiceCount = 0;
    if ($invoiceRes && ($invRow = mysqli_fetch_assoc($invoiceRes))) {
        $invoiceCount = (int)$invRow['invoice_count'];
    }

    return array(
        'error' => false,
        'data' => array(
            'Eligible' => (int)$row['eligible_count'],
            'ReadyForBilling' => (int)$row['ready_count'],
            'BillingVerification' => (int)$row['verification_count'],
            'PaymentStatus' => (int)$row['payment_count'],
            'PartialBilling' => (int)$row['partial_billing_count'],
            'BillingPdf' => $invoiceCount
        )
    );
}

function UpdateTicketsBillingQueueStage($conn, $ticketPKs, $newStage, $actor, $options = array())
{
    $billRemaining = !empty($options['BillRemaining']);
    if ($billRemaining) {
        $newStage = 'ReadyForBilling';
    }

    $validStages = ticket_billing_valid_queue_stages();
    if (!in_array($newStage, $validStages, true)) {
        return array('error' => true, 'message' => 'Invalid billing queue stage');
    }

    if (!is_array($ticketPKs) || count($ticketPKs) === 0) {
        return array('error' => true, 'message' => 'No tickets selected');
    }

    $today = date('Y-m-d');
    $processed = 0;
    $errors = array();

    foreach ($ticketPKs as $ticketPK) {
        $ticketPK = (int)$ticketPK;
        if ($ticketPK <= 0) {
            continue;
        }

        $existing = _getTableDetails($conn, 'ticket_billing_tracking', ' where TicketPK = ' . $ticketPK . ' AND IsActive = 1');

        if ($billRemaining) {
            if (!$existing || trim($existing['BillingStatus']) !== 'Partially Billed') {
                $errors[] = 'Ticket #' . $ticketPK . ': only partially billed tickets can be sent to bill remaining';
                continue;
            }
            if (!ticket_billing_ticket_has_remaining_qty($conn, $ticketPK)) {
                $errors[] = 'Ticket #' . $ticketPK . ': no remaining quantity left to bill';
                continue;
            }
        }

        if ($newStage === 'ReadyForBilling' && !$billRemaining) {
            if (!ticket_billing_is_ticket_sm_verified($conn, $ticketPK)) {
                $errors[] = 'Ticket #' . $ticketPK . ': State Manager verification is required';
                continue;
            }
            $billingStatus = $existing ? trim($existing['BillingStatus']) : 'Unbilled';
            if ($billingStatus === 'Billed' || $billingStatus === 'Partially Billed') {
                $errors[] = 'Ticket #' . $ticketPK . ': ticket is already billed or partially billed';
                continue;
            }
        }

        $payload = array(
            'TicketPK' => $ticketPK,
            'BillingQueueStage' => $newStage,
            'CreatedBy' => $actor
        );

        $targetStage = $newStage;
        if (!empty($options['Verified']) && $newStage === 'PaymentStatus') {
            $billingStatusForRoute = $existing ? trim($existing['BillingStatus']) : 'Unbilled';
            if ($billingStatusForRoute === 'Partially Billed') {
                $targetStage = 'PartialBilling';
            }
        }
        if ($billRemaining) {
            $targetStage = 'ReadyForBilling';
        }
        $payload['BillingQueueStage'] = $targetStage;
        if ($existing) {
            $payload['BillingStatus'] = $existing['BillingStatus'];
            $payload['PaymentStatus'] = $existing['PaymentStatus'];
            $payload['BillingNumber'] = isset($existing['BillingNumber']) ? $existing['BillingNumber'] : '';
            $payload['BilledDate'] = isset($existing['BilledDate']) ? $existing['BilledDate'] : date('Y-m-d');
            $payload['BilledAmount'] = isset($existing['BilledAmount']) ? (float)$existing['BilledAmount'] : 0;
            $payload['Remarks'] = isset($existing['Remarks']) ? $existing['Remarks'] : '';
        }

        if ($newStage === 'ReadyForBilling') {
            $payload['QueuedDate'] = $today;
            $payload['QueuedBy'] = $actor;
            if (!$existing) {
                $payload['BillingStatus'] = 'Unbilled';
                $payload['PaymentStatus'] = 'Pending';
            }
        }

        if ($targetStage === 'BillingVerification' && !empty($options['Verified'])) {
            $payload['VerifiedDate'] = $today;
            $payload['VerifiedBy'] = $actor;
        }

        if (($targetStage === 'PaymentStatus' || $targetStage === 'PartialBilling') && !empty($options['Verified'])) {
            $payload['VerifiedDate'] = $today;
            $payload['VerifiedBy'] = $actor;
        }

        if ($newStage === 'Eligible') {
            $payload['BillingStatus'] = 'Unbilled';
            $payload['PaymentStatus'] = 'Pending';
            $payload['BillingNumber'] = '';
            $payload['QueuedDate'] = null;
            $payload['QueuedBy'] = '';
            $payload['VerifiedDate'] = null;
            $payload['VerifiedBy'] = '';
        }

        $res = CreateUpdateTicketBillingTracking($conn, $payload);
        if ($res['error']) {
            $errors[] = 'Ticket #' . $ticketPK . ': ' . $res['message'];
            continue;
        }
        $processed++;
    }

    if ($processed <= 0) {
        return array(
            'error' => true,
            'message' => count($errors) ? implode('; ', $errors) : 'No tickets updated'
        );
    }

    $message = $billRemaining
        ? $processed . ' ticket(s) sent back to Ready for Billing to bill remaining quantity. Previous invoices are kept.'
        : $processed . ' ticket(s) moved to ' . $newStage;

    return array(
        'error' => false,
        'message' => $message,
        'processed' => $processed,
        'errors' => $errors
    );
}
?>
