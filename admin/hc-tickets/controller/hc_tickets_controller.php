<?php

require_once(__DIR__ . '/../../includes/autoloader.inc.php');

/**
 * URL-safe Base64 encode for ticket id (use in query string as t=...).
 */
function hc_ticket_encode_id($id)
{
	$id = (int) $id;
	if ($id < 1) {
		return '';
	}
	return rtrim(strtr(base64_encode((string) $id), '+/', '-_'), '=');
}

/**
 * Decode t= token from detail URL back to numeric ticket id.
 */
function hc_ticket_decode_id($token)
{
	$token = trim((string) $token);
	if ($token === '') {
		return 0;
	}
	$b64 = strtr($token, '-_', '+/');
	$pad = strlen($b64) % 4;
	if ($pad > 0) {
		$b64 .= str_repeat('=', 4 - $pad);
	}
	$decoded = base64_decode($b64, true);
	if ($decoded === false || !ctype_digit((string) $decoded)) {
		return 0;
	}
	return (int) $decoded;
}

function hc_ticket_list_url()
{
	return 'view-hc-tickets';
}

function hc_ticket_detail_url($ticketId)
{
	$token = hc_ticket_encode_id($ticketId);
	if ($token === '') {
		return hc_ticket_list_url();
	}
	return 'view-hc-ticket-detail?t=' . rawurlencode($token);
}

function hc_ticket_created_by()
{
	return $_SESSION['pb_username'] ?? '';
}

function hc_ticket_state_list($conn)
{
	return _getTableRecords($conn, 'state', ' WHERE IsActive = 1 ORDER BY StateName ASC');
}

function hc_ticket_technician_list($conn)
{
	return _getTableRecords($conn, 'employees', ' WHERE IsActive = 1 ORDER BY Name ASC');
}

function hc_ticket_status_options()
{
	return ['new', 'raised', 'assigned', 'in_progress', 'on_hold', 'completed', 'closed'];
}

function hc_status_badge($status)
{
	$status = strtolower(trim((string) $status));
	$label = ucwords(str_replace('_', ' ', $status));
	$slug = preg_replace('/[^a-z0-9_]/', '', str_replace('-', '_', $status));
	if ($slug === '') {
		$slug = 'new';
	}
	return '<span class="hc-badge hc-badge--' . htmlspecialchars($slug) . '">' . htmlspecialchars($label) . '</span>';
}

function hc_history_badge($actionType)
{
	$map = [
		'raised' => ['raised_hist', 'Ticket raised'],
		'assigned' => ['assigned_hist', 'Assigned'],
		'reassigned' => ['reassigned_hist', 'Reassigned'],
		'status_changed' => ['status_changed_hist', 'Status changed'],
		'due_date_updated' => ['due_date_updated_hist', 'Due date'],
		'remarks_updated' => ['remarks_updated_hist', 'Remarks'],
		'assignment_updated' => ['remarks_updated_hist', 'Updated'],
	];
	$actionType = strtolower(trim((string) $actionType));
	$pair = $map[$actionType] ?? ['remarks_updated_hist', ucwords(str_replace('_', ' ', $actionType))];
	return '<span class="hc-badge hc-badge--' . htmlspecialchars($pair[0]) . '">' . htmlspecialchars($pair[1]) . '</span>';
}
