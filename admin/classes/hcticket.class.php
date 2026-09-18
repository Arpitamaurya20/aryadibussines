<?php

class Hcticket extends Core
{
	private $conn;

	public function __construct($conn)
	{
		$this->conn = $conn;
		$this->setTimeZone();
	}

	public function ticketPublicId($id)
	{
		return 'TX-HC-' . str_pad((string) (int) $id, 6, '0', STR_PAD_LEFT);
	}

	private function employeeName($employeeId)
	{
		$employeeId = (int) $employeeId;
		if ($employeeId < 1) {
			return '';
		}
		$stmt = $this->conn->prepare('SELECT Name FROM employees WHERE ID = ? LIMIT 1');
		if (!$stmt) {
			return '';
		}
		$stmt->bind_param('i', $employeeId);
		$stmt->execute();
		$res = $stmt->get_result();
		$row = $res ? $res->fetch_assoc() : null;
		$stmt->close();
		return $row ? trim($row['Name']) : '';
	}

	private function statusLabel($status)
	{
		return ucwords(str_replace('_', ' ', (string) $status));
	}

	/**
	 * @param array<string,mixed> $row
	 */
	private function addHistory($ticketId, $actionType, array $row, $createdBy)
	{
		$ticketId = (int) $ticketId;
		$actionType = substr((string) $actionType, 0, 32);
		$status = isset($row['status']) ? substr((string) $row['status'], 0, 32) : null;
		$assignedFrom = isset($row['assigned_from']) && (int) $row['assigned_from'] > 0
			? (int) $row['assigned_from'] : null;
		$assignedTo = isset($row['assigned_to']) && (int) $row['assigned_to'] > 0
			? (int) $row['assigned_to'] : null;
		$dueDate = !empty($row['due_date']) ? $row['due_date'] : null;
		$remarks = isset($row['remarks']) ? (string) $row['remarks'] : null;
		$summary = substr(trim((string) ($row['summary'] ?? '')), 0, 500);
		$createdBy = substr((string) $createdBy, 0, 128);

		if ($summary === '') {
			return;
		}

		$sql = 'INSERT INTO hc_ticket_history (
			ticket_id, action_type, status, assigned_from, assigned_to, due_date, remarks, summary, created_by
		) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)';

		$stmt = $this->conn->prepare($sql);
		if (!$stmt) {
			return;
		}
		$afSql = $assignedFrom === null ? null : (string) $assignedFrom;
		$atSql = $assignedTo === null ? null : (string) $assignedTo;
		$stmt->bind_param(
			'issssssss',
			$ticketId,
			$actionType,
			$status,
			$afSql,
			$atSql,
			$dueDate,
			$remarks,
			$summary,
			$createdBy
		);
		$stmt->execute();
		$stmt->close();
	}

	public function getTicketHistory($ticketId)
	{
		$ticketId = (int) $ticketId;
		$rows = [];
		$sql = 'SELECT h.*,
				ef.Name AS assigned_from_name,
				et.Name AS assigned_to_name
			FROM hc_ticket_history h
			LEFT JOIN employees ef ON ef.ID = h.assigned_from
			LEFT JOIN employees et ON et.ID = h.assigned_to
			WHERE h.ticket_id = ?
			ORDER BY h.created_at DESC, h.id DESC';
		$stmt = $this->conn->prepare($sql);
		if (!$stmt) {
			return $rows;
		}
		$stmt->bind_param('i', $ticketId);
		$stmt->execute();
		$res = $stmt->get_result();
		if ($res) {
			while ($r = $res->fetch_assoc()) {
				$rows[] = $r;
			}
		}
		$stmt->close();
		return $rows;
	}

	public function listTickets()
	{
		$rows = [];
		$sql = 'SELECT t.*, s.StateName AS state_name
			FROM hc_ticket t
			LEFT JOIN state s ON s.ID = t.state_id AND s.IsActive = 1
			ORDER BY t.id DESC';
		if ($res = mysqli_query($this->conn, $sql)) {
			while ($r = mysqli_fetch_assoc($res)) {
				$rows[] = $r;
			}
		}
		return $rows;
	}

	public function getTicketById($id)
	{
		$id = (int) $id;
		if ($id < 1) {
			return null;
		}
		$stmt = $this->conn->prepare(
			'SELECT t.*, s.StateName AS state_name,
				e.Name AS assigned_name, e.ContactNumber AS assigned_phone
			FROM hc_ticket t
			LEFT JOIN state s ON s.ID = t.state_id
			LEFT JOIN employees e ON e.ID = t.assigned_to AND e.IsActive = 1
			WHERE t.id = ? LIMIT 1'
		);
		if (!$stmt) {
			return null;
		}
		$stmt->bind_param('i', $id);
		$stmt->execute();
		$res = $stmt->get_result();
		$row = $res ? $res->fetch_assoc() : null;
		$stmt->close();
		return $row ?: null;
	}

	/**
	 * @return array{error:bool,message?:string,id?:int,ticket_code?:string}
	 */
	public function createTicket(array $data, $createdBy)
	{
		$name = trim($data['name'] ?? '');
		$description = trim($data['description'] ?? '');
		$purpose = strtolower(trim($data['purpose'] ?? ''));

		if ($name === '') {
			return ['error' => true, 'message' => 'Name is required.'];
		}
		if ($description === '') {
			return ['error' => true, 'message' => 'Description is required.'];
		}
		if (!in_array($purpose, ['rent', 'self'], true)) {
			return ['error' => true, 'message' => 'Please select purpose (Rent or Self).'];
		}

		$purposeName = trim($data['purpose_name'] ?? '');
		$purposePhone = trim($data['purpose_phone'] ?? '');
		$purposeAddress = trim($data['purpose_address'] ?? '');
		if ($purposeName === '' || $purposePhone === '' || $purposeAddress === '') {
			return ['error' => true, 'message' => 'Purpose contact name, phone and address are required.'];
		}

		$timelineType = strtolower(trim($data['timeline_type'] ?? 'immediate'));
		if (!in_array($timelineType, ['immediate', 'custom'], true)) {
			$timelineType = 'immediate';
		}

		$timelineCustomAt = null;
		if ($timelineType === 'custom') {
			$raw = trim($data['timeline_custom_at'] ?? '');
			if ($raw === '') {
				return ['error' => true, 'message' => 'Please set a custom timeline date and time.'];
			}
			$ts = strtotime($raw);
			if ($ts === false) {
				return ['error' => true, 'message' => 'Invalid custom timeline date/time.'];
			}
			$timelineCustomAt = date('Y-m-d H:i:s', $ts);
		}

		$budget = trim($data['budget'] ?? '');
		$budgetVal = $budget === '' ? null : number_format((float) $budget, 2, '.', '');

		$bookingDate = date('Y-m-d');
		$bookingTime = date('H:i:s');
		$status = 'new';
		$createdBy = substr((string) $createdBy, 0, 128);

		$placeholderCode = 'TX-HC-PENDING';

		$stateId = isset($data['state_id']) ? (int) $data['state_id'] : 0;
		$stateId = $stateId > 0 ? $stateId : null;

		$sql = 'INSERT INTO hc_ticket (
			ticket_code, name, address, phone, email, city, state_id, location, landmark, postal_code,
			timeline_type, timeline_custom_at, budget, description, purpose,
			purpose_name, purpose_phone, purpose_address,
			booking_date, booking_time, status, created_by
		) VALUES (
			?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
			?, ?, ?, ?, ?,
			?, ?, ?,
			?, ?, ?, ?
		)';

		$stmt = $this->conn->prepare($sql);
		if (!$stmt) {
			return ['error' => true, 'message' => mysqli_error($this->conn)];
		}

		$address = trim($data['address'] ?? '');
		$phone = trim($data['phone'] ?? '');
		$email = trim($data['email'] ?? '');
		$city = trim($data['city'] ?? '');
		$location = trim($data['location'] ?? '');
		$landmark = trim($data['landmark'] ?? '');
		$postalCode = trim($data['postal_code'] ?? '');

		$stmt->bind_param(
			'sssssssissssssssssssss',
			$placeholderCode,
			$name,
			$address,
			$phone,
			$email,
			$city,
			$stateId,
			$location,
			$landmark,
			$postalCode,
			$timelineType,
			$timelineCustomAt,
			$budgetVal,
			$description,
			$purpose,
			$purposeName,
			$purposePhone,
			$purposeAddress,
			$bookingDate,
			$bookingTime,
			$status,
			$createdBy
		);

		if (!$stmt->execute()) {
			$err = $stmt->error;
			$stmt->close();
			return ['error' => true, 'message' => $err];
		}

		$newId = (int) $stmt->insert_id;
		$stmt->close();

		$ticketCode = $this->ticketPublicId($newId);
		$upd = $this->conn->prepare('UPDATE hc_ticket SET ticket_code = ? WHERE id = ?');
		if ($upd) {
			$upd->bind_param('si', $ticketCode, $newId);
			$upd->execute();
			$upd->close();
		}

		$this->addHistory($newId, 'raised', [
			'status' => $status,
			'summary' => 'Ticket raised (' . $ticketCode . ').',
		], $createdBy);

		return [
			'error' => false,
			'id' => $newId,
			'ticket_code' => $ticketCode,
			'message' => 'Ticket raised successfully.',
		];
	}

	/**
	 * @return array{error:bool,message?:string}
	 */
	public function updateAssignment($ticketId, array $data, $updatedBy = '')
	{
		$ticketId = (int) $ticketId;
		if ($ticketId < 1) {
			return ['error' => true, 'message' => 'Invalid ticket.'];
		}

		$ticket = $this->getTicketById($ticketId);
		if (!$ticket) {
			return ['error' => true, 'message' => 'Ticket not found.'];
		}

		$assignedTo = isset($data['assigned_to']) ? (int) $data['assigned_to'] : 0;
		if ($assignedTo < 1) {
			return ['error' => true, 'message' => 'Please assign a technician.'];
		}

		$empCheck = $this->conn->prepare('SELECT ID FROM employees WHERE ID = ? AND IsActive = 1 LIMIT 1');
		if (!$empCheck) {
			return ['error' => true, 'message' => mysqli_error($this->conn)];
		}
		$empCheck->bind_param('i', $assignedTo);
		$empCheck->execute();
		$empRes = $empCheck->get_result();
		$empRow = $empRes ? $empRes->fetch_assoc() : null;
		$empCheck->close();
		if (!$empRow) {
			return ['error' => true, 'message' => 'Selected technician is not active or does not exist.'];
		}

		$allowed = ['new', 'raised', 'assigned', 'in_progress', 'on_hold', 'completed', 'closed'];
		$oldAssigned = (int) ($ticket['assigned_to'] ?? 0);
		$oldStatus = (string) ($ticket['status'] ?? 'new');
		$oldDueDate = $ticket['due_date'] ?? null;
		$oldRemarks = trim($ticket['remarks'] ?? '');

		$isReassign = !empty($data['is_reassign']) && (int) $data['is_reassign'] === 1;
		if ($isReassign) {
			if ($oldAssigned < 1) {
				return ['error' => true, 'message' => 'Ticket is not assigned yet. Use normal assignment instead of reassign.'];
			}
			if ($oldAssigned === $assignedTo) {
				return ['error' => true, 'message' => 'Ticket is already assigned to this technician.'];
			}
			$status = $oldStatus;
		} else {
			$status = strtolower(trim($data['status'] ?? ''));
			if (!in_array($status, $allowed, true)) {
				$status = in_array($oldStatus, $allowed, true) ? $oldStatus : 'assigned';
			}
			if ($oldAssigned < 1 && $assignedTo > 0 && in_array($status, ['new', 'raised'], true)) {
				$status = 'assigned';
			}
		}

		$dueDate = trim($data['due_date'] ?? '');
		$dueDateVal = null;
		if ($dueDate !== '') {
			$ts = strtotime($dueDate);
			if ($ts === false) {
				return ['error' => true, 'message' => 'Invalid due date.'];
			}
			$dueDateVal = date('Y-m-d', $ts);
		}

		$remarks = trim($data['remarks'] ?? '');
		$updatedBy = substr((string) $updatedBy, 0, 128);

		$sql = 'UPDATE hc_ticket SET assigned_to = ?, status = ?, due_date = ?, remarks = ? WHERE id = ?';
		$stmt = $this->conn->prepare($sql);
		if (!$stmt) {
			return ['error' => true, 'message' => mysqli_error($this->conn)];
		}
		$stmt->bind_param('isssi', $assignedTo, $status, $dueDateVal, $remarks, $ticketId);
		if (!$stmt->execute()) {
			$err = $stmt->error;
			$stmt->close();
			return ['error' => true, 'message' => $err];
		}
		$stmt->close();

		$fromName = $this->employeeName($oldAssigned);
		$toName = $this->employeeName($assignedTo);

		if ($isReassign) {
			$this->addHistory($ticketId, 'reassigned', [
				'status' => $status,
				'assigned_from' => $oldAssigned,
				'assigned_to' => $assignedTo,
				'due_date' => $dueDateVal,
				'remarks' => $remarks,
				'summary' => 'Reassigned from ' . ($fromName !== '' ? $fromName : '#' . $oldAssigned)
					. ' to ' . ($toName !== '' ? $toName : '#' . $assignedTo)
					. '. Status unchanged (' . $this->statusLabel($status) . ').',
			], $updatedBy);
			return ['error' => false, 'message' => 'Ticket reassigned successfully.'];
		}

		$loggedAssignment = false;
		if ($oldAssigned < 1 && $assignedTo > 0) {
			$this->addHistory($ticketId, 'assigned', [
				'status' => $status,
				'assigned_to' => $assignedTo,
				'due_date' => $dueDateVal,
				'remarks' => $remarks,
				'summary' => 'Assigned to ' . ($toName !== '' ? $toName : '#' . $assignedTo)
					. '. Status: ' . $this->statusLabel($status) . '.',
			], $updatedBy);
			$loggedAssignment = true;
		} elseif ($oldAssigned > 0 && $assignedTo > 0 && $oldAssigned !== $assignedTo) {
			$this->addHistory($ticketId, 'reassigned', [
				'status' => $status,
				'assigned_from' => $oldAssigned,
				'assigned_to' => $assignedTo,
				'due_date' => $dueDateVal,
				'remarks' => $remarks,
				'summary' => 'Reassigned from ' . ($fromName !== '' ? $fromName : '#' . $oldAssigned)
					. ' to ' . ($toName !== '' ? $toName : '#' . $assignedTo)
					. '. Status: ' . $this->statusLabel($status) . '.',
			], $updatedBy);
		}

		if (!$loggedAssignment && $oldStatus !== $status) {
			$this->addHistory($ticketId, 'status_changed', [
				'status' => $status,
				'assigned_to' => $assignedTo,
				'due_date' => $dueDateVal,
				'remarks' => $remarks,
				'summary' => 'Status changed from ' . $this->statusLabel($oldStatus)
					. ' to ' . $this->statusLabel($status) . '.',
			], $updatedBy);
		}

		$oldDueNorm = $oldDueDate ? date('Y-m-d', strtotime($oldDueDate)) : '';
		$newDueNorm = $dueDateVal ?? '';
		if ($oldDueNorm !== $newDueNorm) {
			$this->addHistory($ticketId, 'due_date_updated', [
				'status' => $status,
				'assigned_to' => $assignedTo,
				'due_date' => $dueDateVal,
				'remarks' => $remarks,
				'summary' => $newDueNorm === ''
					? 'Due date cleared.'
					: 'Due date set to ' . $newDueNorm . '.',
			], $updatedBy);
		}

		if ($oldRemarks !== $remarks) {
			$this->addHistory($ticketId, 'remarks_updated', [
				'status' => $status,
				'assigned_to' => $assignedTo,
				'due_date' => $dueDateVal,
				'remarks' => $remarks,
				'summary' => $remarks === '' ? 'Remarks cleared.' : 'Remarks updated.',
			], $updatedBy);
		}

		if ($oldAssigned > 0 && $oldAssigned === $assignedTo && $oldStatus === $status
			&& $oldDueNorm === $newDueNorm && $oldRemarks === $remarks) {
			return ['error' => true, 'message' => 'No changes detected.'];
		}

		return ['error' => false, 'message' => 'Assignment updated successfully.'];
	}
}
