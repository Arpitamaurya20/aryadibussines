<?php
/**
 * Helpers for adding quotation line items when status is Quote Sent Approval Pending.
 * Uses explicit next ID because corporate_ticket_quotation_items.ID may lack AUTO_INCREMENT.
 */

function getNextQuotationLineItemId($conn, $core)
{
    $nextRow = $core->_getSQLDetails(
        $conn,
        'SELECT COALESCE(MAX(`ID`), 0) + 1 AS next_id FROM corporate_ticket_quotation_items'
    );

    $nextId = (int) ($nextRow['next_id'] ?? 1);
    return $nextId > 0 ? $nextId : 1;
}

function addPendingQuotationLineItem($conn, $core, $data)
{
    $response = array(
        'error' => true,
        'message' => 'Unable to add line item to quotation.'
    );

    $quotationId = (int) ($data['QuotationID'] ?? 0);
    $lineItemId = (int) ($data['LineItemID'] ?? 0);
    $quantity = (int) ($data['quantity'] ?? 0);
    $createdBy = mysqli_real_escape_string($conn, (string) ($data['CreatedBy'] ?? ''));
    $createdDate = mysqli_real_escape_string($conn, (string) ($data['CreatedDate'] ?? ''));
    $createdTime = mysqli_real_escape_string($conn, (string) ($data['CreatedTime'] ?? ''));

    if ($quotationId <= 0 || $lineItemId <= 0 || $quantity <= 0) {
        $response['message'] = 'Invalid quotation line item data.';
        return $response;
    }

    $lineItemDetails = $core->_getTableDetails($conn, 'corporate_rate_card', " where ID = $lineItemId");
    if (empty($lineItemDetails) || !is_array($lineItemDetails)) {
        $response['message'] = 'Rate card item not found.';
        return $response;
    }

    $perItemPrice = (float) ($lineItemDetails['Price'] ?? 0);
    $filter = " where LineItemID = $lineItemId and QuotationID = $quotationId";
    $numRows = (int) $core->_getTotalRows($conn, 'corporate_ticket_quotation_items', $filter);

    if ($numRows > 0) {
        $existingItem = $core->_getTableDetails($conn, 'corporate_ticket_quotation_items', $filter);
        $itemId = (int) ($existingItem['ID'] ?? 0);
        $oldQty = (int) ($existingItem['Qty'] ?? 0);
        $newQty = $oldQty + $quantity;
        $totalPrice = $perItemPrice * $newQty;
        $updateSql = " Qty = $newQty, TotalPrice = '$totalPrice' where ID = $itemId";
        return $core->_UpdateTableRecords($conn, 'corporate_ticket_quotation_items', $updateSql);
    }

    $nextId = getNextQuotationLineItemId($conn, $core);
    $totalPrice = $perItemPrice * $quantity;
    $sql = "INSERT INTO corporate_ticket_quotation_items(`ID`, `QuotationID`, `LineItemID`, `Qty`, `PerItemPrice`, `TotalPrice`, `CreatedBy`, `CreatedDate`, `CreatedTime`)
            VALUES($nextId, $quotationId, $lineItemId, $quantity, '$perItemPrice', '$totalPrice', '$createdBy', '$createdDate', '$createdTime')";

    return $core->_InsertTableRecords($conn, $sql);
}

function quotationRequiresCompanyAdminApproval($conn, $core, $quotationDetail)
{
    if (empty($quotationDetail) || !isset($quotationDetail['TicketID'])) {
        return false;
    }

    $ticket = $core->_getTableDetails($conn, 'corporate_tickets', ' where ID = ' . (int)$quotationDetail['TicketID']);
    if (empty($ticket) || !isset($ticket['CorporateID'])) {
        return false;
    }

    $company = $core->_getTableDetails($conn, 'company', ' where ID = ' . (int)$ticket['CorporateID']);
    return !empty($company) && (int)($company['TicketsNeedApproval'] ?? 0) === 1;
}

function quotationCurrentUserCanApproveCompanyAdmin($conn, $core, $quotationDetail)
{
    if (isset($_SESSION['UserType']) && $_SESSION['UserType'] === 'Admin') {
        return true;
    }

    if (
        isset($_SESSION['UserType'], $_SESSION['Roles']['CorporateID'], $quotationDetail['TicketID'])
        && $_SESSION['UserType'] === 'Corporate Admin'
    ) {
        $ticket = $core->_getTableDetails($conn, 'corporate_tickets', ' where ID = ' . (int)$quotationDetail['TicketID']);
        return !empty($ticket)
            && isset($ticket['CorporateID'])
            && (int)$_SESSION['Roles']['CorporateID'] === (int)$ticket['CorporateID'];
    }

    return false;
}

function moveQuotationBackToCompanyAdminApproval($conn, $core, $corporateticket_obj, $quotationDetail, $createdBy, $createdDate, $createdTime, $remarks)
{
    $companyAdminApprovalStatus = 'Quote Pending Company Admin Approval';
    $quotationId = (int)($quotationDetail['ID'] ?? 0);
    $ticketId = (int)($quotationDetail['TicketID'] ?? 0);

    if ($quotationId <= 0 || $ticketId <= 0) {
        return;
    }

    $core->_UpdateTableRecords($conn, 'corporate_ticket_quotation', " QuotationStatus = '$companyAdminApprovalStatus' where ID = $quotationId");
    $core->_UpdateTableRecords($conn, 'corporate_tickets', " Status = '$companyAdminApprovalStatus' where ID = $ticketId");
    $corporateticket_obj->UpdateTicketQuotationHistory(array(
        'QuotationID' => $quotationId,
        'QuotationStatus' => $companyAdminApprovalStatus,
        'Remarks' => $remarks,
        'CreatedBy' => $createdBy,
        'CreatedDate' => $createdDate,
        'CreatedTime' => $createdTime
    ));
}
