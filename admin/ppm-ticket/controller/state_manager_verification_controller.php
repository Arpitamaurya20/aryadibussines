<?php

/**
 * State Manager PPM ticket verification — isolated helpers for ppm-tickets list.
 */

function psmv_canUserVerifyTicket($session)
{
    if (function_exists('userHasStateCorporateLeadAccess') && userHasStateCorporateLeadAccess($session)) {
        return true;
    }

    if (CheckRole($session, 'State Corporate Lead') === true) {
        return true;
    }

    return false;
}

function psmv_getRequiredChecklistKeys()
{
    return array(
        'QualityOfTicket',
        'ServiceReportAvailable',
        'ChecklistFieldsComplete',
    );
}

function psmv_getChecklistLabels()
{
    return array(
        'QualityOfTicket' => 'Quality of ticket is satisfactory',
        'ServiceReportAvailable' => 'Service report is available',
        'ChecklistFieldsComplete' => 'All checklist fields are filled',
    );
}

function psmv_getVerificationRowByTicketPK($conn, $ticketPK)
{
    $ticketPK = (int) $ticketPK;
    if ($ticketPK <= 0) {
        return null;
    }

    $sql = "SELECT * FROM ppm_ticket_sm_verification
            WHERE TicketPK = $ticketPK AND IsActive = 1
            LIMIT 1";
    $result = mysqli_query($conn, $sql);
    if ($result && $result->num_rows > 0) {
        return $result->fetch_assoc();
    }

    return null;
}

function psmv_isTicketVerifiedByStateManager($conn, $ticketPK)
{
    $row = psmv_getVerificationRowByTicketPK($conn, $ticketPK);
    return is_array($row) && (int) $row['IsVerified'] === 1;
}

function psmv_getVerificationStatusForTicket($conn, $ticketPK)
{
    $row = psmv_getVerificationRowByTicketPK($conn, $ticketPK);
    $requiredKeys = psmv_getRequiredChecklistKeys();

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
        'labels' => psmv_getChecklistLabels(),
    );
}

function psmv_allRequiredChecksPassed($checks)
{
    $requiredKeys = psmv_getRequiredChecklistKeys();

    foreach ($requiredKeys as $key) {
        if (empty($checks[$key]) || (int) $checks[$key] !== 1) {
            return false;
        }
    }

    return true;
}

function psmv_getTicketForVerification($conn, $ticketPK)
{
    $ticketPK = (int) $ticketPK;
    if ($ticketPK <= 0) {
        return null;
    }

    return _getTableDetails($conn, 'ppm_tickets', " WHERE ID = $ticketPK AND IsActive = 1");
}

function psmv_saveTicketVerification($conn, $data)
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

    $ticket = psmv_getTicketForVerification($conn, $ticketPK);
    if (!is_array($ticket) || empty($ticket['ID'])) {
        $response['message'] = 'Ticket not found.';
        return $response;
    }

    $ticketStatus = isset($ticket['Status']) ? $ticket['Status'] : '';
    if (!psmv_isTicketStatusClosed($ticketStatus)) {
        $response['message'] = 'Ticket can be verified only when status is Closed.';
        return $response;
    }

    $checks = array(
        'QualityOfTicket' => !empty($data['QualityOfTicket']) ? 1 : 0,
        'ServiceReportAvailable' => !empty($data['ServiceReportAvailable']) ? 1 : 0,
        'ChecklistFieldsComplete' => !empty($data['ChecklistFieldsComplete']) ? 1 : 0,
    );

    if (!psmv_allRequiredChecksPassed($checks)) {
        $response['message'] = 'Please confirm all checklist items before verifying the ticket.';
        return $response;
    }

    $customerPoAvailable = !empty($data['CustomerPoAvailable']) ? 1 : 0;
    $customerPoRemarks = isset($data['CustomerPoRemarks']) ? trim($data['CustomerPoRemarks']) : '';
    if ($customerPoAvailable === 1 && $customerPoRemarks === '') {
        $response['message'] = 'Please enter the PO number / remarks when PO is available.';
        return $response;
    }
    $customerPoRemarksEsc = mysqli_real_escape_string($conn, $customerPoRemarks);
    $createdBy = isset($data['CreatedBy']) ? $data['CreatedBy'] : '';
    $createdDate = isset($data['CreatedDate']) ? $data['CreatedDate'] : date('Y-m-d');
    $createdTime = isset($data['CreatedTime']) ? $data['CreatedTime'] : date('H:i:s');
    $ticketID = mysqli_real_escape_string($conn, $ticket['TicketID']);
    $createdByEsc = mysqli_real_escape_string($conn, $createdBy);

    $existing = psmv_getVerificationRowByTicketPK($conn, $ticketPK);
    if (is_array($existing) && (int) $existing['IsVerified'] === 1) {
        $response['message'] = 'This ticket is already verified.';
        return $response;
    }

    if (is_array($existing)) {
        $sql = "UPDATE ppm_ticket_sm_verification SET
            TicketID = '$ticketID',
            IsVerified = 1,
            QualityOfTicket = 1,
            ServiceReportAvailable = 1,
            ChecklistFieldsComplete = 1,
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
        $sql = "INSERT INTO ppm_ticket_sm_verification
            (TicketPK, TicketID, IsVerified,
             QualityOfTicket, ServiceReportAvailable, ChecklistFieldsComplete,
             CustomerPoAvailable, CustomerPoRemarks,
             VerifiedBy, VerifiedDate, VerifiedTime,
             CreatedBy, CreatedDate, CreatedTime,
             UpdatedBy, UpdatedDate, UpdatedTime, IsActive)
            VALUES
            ($ticketPK, '$ticketID', 1,
             1, 1, 1,
             $customerPoAvailable, " . ($customerPoRemarks !== '' ? "'$customerPoRemarksEsc'" : 'NULL') . ",
             '$createdByEsc', '$createdDate', '$createdTime',
             '$createdByEsc', '$createdDate', '$createdTime',
             '$createdByEsc', '$createdDate', '$createdTime', 1)";
    }

    if (mysqli_query($conn, $sql)) {
        $response['error'] = false;
        $response['message'] = 'PPM ticket verified successfully.';
        $response['is_verified'] = true;
    } else {
        $response['message'] = 'Database error while saving verification.';
    }

    return $response;
}

function psmv_isTicketStatusClosed($ticketStatus)
{
    return strtolower(trim((string) $ticketStatus)) === 'closed';
}

function psmv_renderVerificationTickIcon($isVerified)
{
    if ($isVerified) {
        return "<i class='fas fa-check-circle text-success' title='Verified by State Manager'></i>";
    }

    return "<i class='fas fa-times-circle text-danger' title='Not verified by State Manager'></i>";
}

function psmv_renderVerificationActionButton($ticketPK, $ticketID, $isVerified, $canVerify, $ticketStatus = '')
{
    if (!$canVerify) {
        return '-';
    }

    $ticketIDAttr = htmlspecialchars((string) $ticketID, ENT_QUOTES, 'UTF-8');
    $isClosed = psmv_isTicketStatusClosed($ticketStatus);

    if ($isVerified) {
        return "<button type='button' class='btn btn-sm btn-success psmv-open-verify-btn'
            data-ticket-pk='" . (int) $ticketPK . "'
            data-ticket-id='" . $ticketIDAttr . "'>Verified</button>";
    }

    if (!$isClosed) {
        return "<button type='button' class='btn btn-sm btn-secondary' disabled title='Verify is enabled only when ticket status is Closed'>Verify</button>";
    }

    return "<button type='button' class='btn btn-sm btn-primary psmv-open-verify-btn'
        data-ticket-pk='" . (int) $ticketPK . "'
        data-ticket-id='" . $ticketIDAttr . "'>Verify</button>";
}
