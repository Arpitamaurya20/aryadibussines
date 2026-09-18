<?php

/**
 * Tender RFQ — thin helpers (logic lives in TenderRfq class).
 */

function tender_rfq_session_roles()
{
	return isset($_SESSION['Roles']) ? $_SESSION['Roles'] : [];
}

function tender_rfq_can_access($conn, $ticketId, $roles, $userType)
{
	$obj = new TenderRfq($conn);
	return $obj->getTicketById($ticketId, $roles, $userType) !== null;
}
