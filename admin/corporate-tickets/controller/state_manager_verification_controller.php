<?php

/**
 * State Manager ticket verification — isolated helpers for corporate tickets list.
 * Do not modify existing Corporateticket / corporate_tickets_controller functions.
 */

function smv_isProjectTicketType($ticketType)
{
    $normalized = strtolower(trim((string) $ticketType));
    return ($normalized === 'project' || $normalized === 'projects');
}

function smv_canUserVerifyTicket($session)
{
    if (isset($session['UserType']) && $session['UserType'] === 'Admin') {
        return true;
    }

    if (CheckRole($session, 'State Corporate Lead') === true) {
        return true;
    }

    if (CheckRole($session, 'Branch Account Manager') === true) {
        return true;
    }

    return false;
}

function smv_getRequiredChecklistKeys($ticketType)
{
    $keys = array(
        'QualityOfTicket',
        'DigitalReportComplete',
        'WccChecked',
        'VendorPaymentChecked',
        'QuotationQuantityChecked',
    );

    if (smv_isProjectTicketType($ticketType)) {
        $keys[] = 'DprChecked';
    }

    return $keys;
}

function smv_getChecklistLabels($ticketType)
{
    $labels = array(
        'QualityOfTicket' => 'Quality of ticket is satisfactory',
        'DigitalReportComplete' => 'Digital report is complete',
        'WccChecked' => 'WCC checked',
        'VendorPaymentChecked' => 'Vendor payment checked',
        'QuotationQuantityChecked' => 'Quantity in quotation checked',
    );

    if (smv_isProjectTicketType($ticketType)) {
        $labels['DprChecked'] = 'DPR checked';
    }

    return $labels;
}

function smv_getVerificationRowByTicketPK($conn, $ticketPK)
{
    $ticketPK = (int) $ticketPK;
    if ($ticketPK <= 0) {
        return null;
    }

    $sql = "SELECT * FROM corporate_ticket_sm_verification
            WHERE TicketPK = $ticketPK AND IsActive = 1
            LIMIT 1";
    $result = mysqli_query($conn, $sql);
    if ($result && $result->num_rows > 0) {
        return $result->fetch_assoc();
    }

    return null;
}

function smv_isTicketVerifiedByStateManager($conn, $ticketPK)
{
    $row = smv_getVerificationRowByTicketPK($conn, $ticketPK);
    return is_array($row) && (int) $row['IsVerified'] === 1;
}

function smv_getVerificationStatusForTicket($conn, $ticketPK, $ticketType)
{
    $row = smv_getVerificationRowByTicketPK($conn, $ticketPK);
    $requiredKeys = smv_getRequiredChecklistKeys($ticketType);

    $checks = array();
    foreach ($requiredKeys as $key) {
        $checks[$key] = is_array($row) ? ((int) $row[$key] === 1) : false;
    }

    return array(
        'is_verified' => is_array($row) && (int) $row['IsVerified'] === 1,
        'checks' => $checks,
        'customer_po_available' => is_array($row) && isset($row['CustomerPoAvailable']) && (int) $row['CustomerPoAvailable'] === 1,
        'customer_po_remarks' => is_array($row) && isset($row['CustomerPoRemarks']) ? $row['CustomerPoRemarks'] : '',
        'verified_by' => is_array($row) ? $row['VerifiedBy'] : '',
        'verified_date' => is_array($row) ? $row['VerifiedDate'] : '',
        'verified_time' => is_array($row) ? $row['VerifiedTime'] : '',
        'required_keys' => $requiredKeys,
        'labels' => smv_getChecklistLabels($ticketType),
        'is_project' => smv_isProjectTicketType($ticketType),
    );
}

function smv_allRequiredChecksPassed($ticketType, $checks)
{
    $requiredKeys = smv_getRequiredChecklistKeys($ticketType);

    foreach ($requiredKeys as $key) {
        if (empty($checks[$key]) || (int) $checks[$key] !== 1) {
            return false;
        }
    }

    return true;
}

function smv_getTicketForVerification($conn, $ticketPK)
{
    $ticketPK = (int) $ticketPK;
    if ($ticketPK <= 0) {
        return null;
    }

    return _getTableDetails($conn, 'corporate_tickets', " WHERE ID = $ticketPK AND IsActive = 1");
}

function smv_saveTicketVerification($conn, $data)
{
    $response = array(
        'error' => true,
        'message' => 'Unable to save verification.',
    );

    $ticketPK = isset($data['TicketPK']) ? (int) $data['TicketPK'] : 0;
    if ($ticketPK <= 0) {
        $response['message'] = 'Invalid ticket.';
        return $response;
    }

    $ticket = smv_getTicketForVerification($conn, $ticketPK);
    if (!is_array($ticket) || empty($ticket['ID'])) {
        $response['message'] = 'Ticket not found.';
        return $response;
    }

    $ticketStatus = isset($ticket['Status']) ? $ticket['Status'] : '';
    if (!smv_isTicketStatusClosed($ticketStatus)) {
        $response['message'] = 'Ticket can be verified only when status is Closed.';
        return $response;
    }

    $ticketType = isset($ticket['Type']) ? $ticket['Type'] : '';
    $checks = array(
        'QualityOfTicket' => !empty($data['QualityOfTicket']) ? 1 : 0,
        'DigitalReportComplete' => !empty($data['DigitalReportComplete']) ? 1 : 0,
        'WccChecked' => !empty($data['WccChecked']) ? 1 : 0,
        'VendorPaymentChecked' => !empty($data['VendorPaymentChecked']) ? 1 : 0,
        'DprChecked' => !empty($data['DprChecked']) ? 1 : 0,
        'QuotationQuantityChecked' => !empty($data['QuotationQuantityChecked']) ? 1 : 0,
    );

    if (!smv_allRequiredChecksPassed($ticketType, $checks)) {
        $response['message'] = 'Please confirm all checklist items before verifying the ticket.';
        return $response;
    }

    $customerPoAvailable = !empty($data['CustomerPoAvailable']) ? 1 : 0;
    $customerPoRemarks = isset($data['CustomerPoRemarks']) ? trim($data['CustomerPoRemarks']) : '';
    if ($customerPoAvailable === 1 && $customerPoRemarks === '') {
        $response['message'] = 'Please enter the customer PO number / remarks when PO is available.';
        return $response;
    }
    $customerPoRemarksEsc = mysqli_real_escape_string($conn, $customerPoRemarks);
    $createdBy = isset($data['CreatedBy']) ? $data['CreatedBy'] : '';
    $createdDate = isset($data['CreatedDate']) ? $data['CreatedDate'] : date('Y-m-d');
    $createdTime = isset($data['CreatedTime']) ? $data['CreatedTime'] : date('H:i:s');
    $ticketID = mysqli_real_escape_string($conn, $ticket['TicketID']);
    $ticketTypeEsc = mysqli_real_escape_string($conn, $ticketType);
    $createdByEsc = mysqli_real_escape_string($conn, $createdBy);

    $existing = smv_getVerificationRowByTicketPK($conn, $ticketPK);
    if (is_array($existing) && (int) $existing['IsVerified'] === 1) {
        $response['message'] = 'This ticket is already verified.';
        return $response;
    }

    if (is_array($existing)) {
        $sql = "UPDATE corporate_ticket_sm_verification SET
            TicketID = '$ticketID',
            TicketType = '$ticketTypeEsc',
            IsVerified = 1,
            QualityOfTicket = 1,
            DigitalReportComplete = 1,
            WccChecked = 1,
            VendorPaymentChecked = 1,
            DprChecked = " . (smv_isProjectTicketType($ticketType) ? 1 : 0) . ",
            QuotationQuantityChecked = 1,
            CustomerPoAvailable = $customerPoAvailable,
            CustomerPoRemarks = " . ($customerPoRemarks !== '' ? "'$customerPoRemarksEsc'" : 'NULL') . ",
            VerifiedBy = '$createdByEsc',
            VerifiedDate = '$createdDate',
            VerifiedTime = '$createdTime',
            UpdatedBy = '$createdByEsc',
            UpdatedDate = '$createdDate',
            UpdatedTime = '$createdTime'
            WHERE TicketPK = $ticketPK AND IsActive = 1";
    } else {
        $dprValue = smv_isProjectTicketType($ticketType) ? 1 : 0;
        $sql = "INSERT INTO corporate_ticket_sm_verification
            (TicketPK, TicketID, TicketType, IsVerified,
             QualityOfTicket, DigitalReportComplete, WccChecked, VendorPaymentChecked,
             DprChecked, QuotationQuantityChecked, CustomerPoAvailable, CustomerPoRemarks,
             VerifiedBy, VerifiedDate, VerifiedTime,
             CreatedBy, CreatedDate, CreatedTime,
             UpdatedBy, UpdatedDate, UpdatedTime, IsActive)
            VALUES
            ($ticketPK, '$ticketID', '$ticketTypeEsc', 1,
             1, 1, 1, 1,
             $dprValue, 1, $customerPoAvailable, " . ($customerPoRemarks !== '' ? "'$customerPoRemarksEsc'" : 'NULL') . ",
             '$createdByEsc', '$createdDate', '$createdTime',
             '$createdByEsc', '$createdDate', '$createdTime',
             '$createdByEsc', '$createdDate', '$createdTime', 1)";
    }

    if (mysqli_query($conn, $sql)) {
        $response['error'] = false;
        $response['message'] = 'Ticket verified successfully.';
        $response['is_verified'] = true;
    } else {
        $response['message'] = 'Database error while saving verification.';
    }

    return $response;
}

function smv_isTicketStatusClosed($ticketStatus)
{
    return strtolower(trim((string) $ticketStatus)) === 'closed';
}

function smv_renderVerificationTickIcon($isVerified)
{
    if ($isVerified) {
        return "<i class='fas fa-check-circle text-success' title='Verified by State Manager'></i>";
    }

    return "<i class='fas fa-times-circle text-danger' title='Not verified by State Manager'></i>";
}

function smv_renderVerificationActionButton($ticketPK, $ticketID, $ticketType, $isVerified, $canVerify, $ticketStatus = '')
{
    if (!$canVerify) {
        return '-';
    }

    $ticketIDAttr = htmlspecialchars((string) $ticketID, ENT_QUOTES, 'UTF-8');
    $ticketTypeAttr = htmlspecialchars((string) $ticketType, ENT_QUOTES, 'UTF-8');

    if ($isVerified) {
        return "<span class='badge badge-success'>Verified</span>";
    }

    if (!smv_isTicketStatusClosed($ticketStatus)) {
        return "<button type='button' class='btn btn-sm btn-secondary' disabled title='Verify is enabled only when ticket status is Closed'>Verify</button>";
    }

    return "<button type='button' class='btn btn-sm btn-primary smv-open-verify-btn'
        data-ticket-pk='" . (int) $ticketPK . "'
        data-ticket-id='" . $ticketIDAttr . "'
        data-ticket-type='" . $ticketTypeAttr . "'>Verify</button>";
}
