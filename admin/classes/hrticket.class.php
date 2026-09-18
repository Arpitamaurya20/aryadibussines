<?php

class Hrticket extends Core
{
	private $conn;

	public function __construct($conn)
	{
		$this->conn = $conn;
		$this->setTimeZone();
	}

	public static function statusOptions()
	{
		return ['open', 'in_progress', 'pending_employee_response', 'on_hold', 'resolved', 'closed'];
	}

	public static function categoryOptions()
	{
		return ['general', 'payment_related', 'benefits'];
	}

	public function ticketPublicId($id)
	{
		return 'TX-HR-' . str_pad((string) (int) $id, 6, '0', STR_PAD_LEFT);
	}

	public function statusLabel($status)
	{
		$map = [
			'open' => 'Open',
			'in_progress' => 'In Progress',
			'pending_employee_response' => 'Pending Employee Response',
			'on_hold' => 'On Hold',
			'resolved' => 'Resolved',
			'closed' => 'Closed',
		];
		$key = strtolower(trim((string) $status));
		return $map[$key] ?? ucwords(str_replace('_', ' ', $key));
	}

	public function categoryLabel($category)
	{
		$map = [
			'general' => 'General',
			'payment_related' => 'Payment Related',
			'benefits' => 'Benefits',
		];
		$key = strtolower(trim((string) $category));
		return $map[$key] ?? ucwords(str_replace('_', ' ', $key));
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

	/**
	 * @param array<string,mixed> $row
	 */
	private function addHistory($ticketId, $actionType, array $row, $createdBy)
	{
		$ticketId = (int) $ticketId;
		$actionType = substr((string) $actionType, 0, 32);
		$status = isset($row['status']) ? substr((string) $row['status'], 0, 48) : null;
		$assignedFrom = isset($row['assigned_from']) && (int) $row['assigned_from'] > 0
			? (int) $row['assigned_from'] : null;
		$assignedTo = isset($row['assigned_to']) && (int) $row['assigned_to'] > 0
			? (int) $row['assigned_to'] : null;
		$summary = substr(trim((string) ($row['summary'] ?? '')), 0, 500);
		$createdBy = substr((string) $createdBy, 0, 128);

		if ($summary === '') {
			return;
		}

		$sql = 'INSERT INTO hr_ticket_history (
			ticket_id, action_type, status, assigned_from, assigned_to, summary, created_by
		) VALUES (?, ?, ?, ?, ?, ?, ?)';

		$stmt = $this->conn->prepare($sql);
		if (!$stmt) {
			return;
		}
		$afSql = $assignedFrom === null ? null : (string) $assignedFrom;
		$atSql = $assignedTo === null ? null : (string) $assignedTo;
		$stmt->bind_param(
			'issssss',
			$ticketId,
			$actionType,
			$status,
			$afSql,
			$atSql,
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
			FROM hr_ticket_history h
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

	public function getTicketById($id)
	{
		$id = (int) $id;
		if ($id < 1) {
			return null;
		}
		$stmt = $this->conn->prepare(
			'SELECT t.*,
				e.Name AS employee_name, e.ContactNumber AS employee_phone, e.Email AS employee_email,
				a.Name AS assigned_name
			FROM hr_ticket t
			INNER JOIN employees e ON e.ID = t.employee_id
			LEFT JOIN employees a ON a.ID = t.assigned_to AND a.IsActive = 1
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

	public function listTicketsForEmployee($employeeId)
	{
		$employeeId = (int) $employeeId;
		$rows = [];
		if ($employeeId < 1) {
			return $rows;
		}
		$stmt = $this->conn->prepare(
			'SELECT t.*, a.Name AS assigned_name
			FROM hr_ticket t
			LEFT JOIN employees a ON a.ID = t.assigned_to
			WHERE t.employee_id = ?
			ORDER BY t.id DESC'
		);
		if (!$stmt) {
			return $rows;
		}
		$stmt->bind_param('i', $employeeId);
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

	public function listAllTickets($filters = [])
	{
		$rows = [];
		$where = ['1=1'];
		$params = [];
		$types = '';

		if (!empty($filters['status'])) {
			$where[] = 't.status = ?';
			$params[] = (string) $filters['status'];
			$types .= 's';
		}
		if (!empty($filters['category'])) {
			$where[] = 't.category = ?';
			$params[] = (string) $filters['category'];
			$types .= 's';
		}
		if (!empty($filters['assigned_to'])) {
			$where[] = 't.assigned_to = ?';
			$params[] = (int) $filters['assigned_to'];
			$types .= 'i';
		}

		$sql = 'SELECT t.*, e.Name AS employee_name, a.Name AS assigned_name
			FROM hr_ticket t
			INNER JOIN employees e ON e.ID = t.employee_id
			LEFT JOIN employees a ON a.ID = t.assigned_to
			WHERE ' . implode(' AND ', $where) . '
			ORDER BY t.id DESC';

		if ($types === '') {
			if ($res = mysqli_query($this->conn, $sql)) {
				while ($r = mysqli_fetch_assoc($res)) {
					$rows[] = $r;
				}
			}
			return $rows;
		}

		$stmt = $this->conn->prepare($sql);
		if (!$stmt) {
			return $rows;
		}
		$stmt->bind_param($types, ...$params);
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

	public function getComments($ticketId, $includeInternal = false)
	{
		$ticketId = (int) $ticketId;
		$rows = [];
		$sql = 'SELECT c.*, e.Name AS author_name
			FROM hr_ticket_comment c
			LEFT JOIN employees e ON e.ID = c.author_employee_id
			WHERE c.ticket_id = ?';
		if (!$includeInternal) {
			$sql .= ' AND c.is_internal = 0';
		}
		$sql .= ' ORDER BY c.created_at ASC, c.id ASC';

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

	public function getAttachments($ticketId)
	{
		$ticketId = (int) $ticketId;
		$rows = [];
		$stmt = $this->conn->prepare(
			'SELECT * FROM hr_ticket_attachment WHERE ticket_id = ? ORDER BY created_at ASC, id ASC'
		);
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

	/**
	 * @return array{error:bool,message?:string,id?:int,ticket_code?:string}
	 */
	public function createTicket(array $data, $employeeId, $createdBy)
	{
		$employeeId = (int) $employeeId;
		if ($employeeId < 1) {
			return ['error' => true, 'message' => 'Employee profile is required to raise a ticket.'];
		}

		$subject = trim($data['subject'] ?? '');
		$description = trim($data['description'] ?? '');
		$category = strtolower(trim($data['category'] ?? 'general'));
		$paymentRef = trim($data['payment_reference'] ?? '');

		if ($subject === '') {
			return ['error' => true, 'message' => 'Subject is required.'];
		}
		if ($description === '') {
			return ['error' => true, 'message' => 'Description is required.'];
		}
		if (!in_array($category, self::categoryOptions(), true)) {
			return ['error' => true, 'message' => 'Please select a valid category.'];
		}
		if ($category === 'payment_related' && $paymentRef === '') {
			return ['error' => true, 'message' => 'Payment reference or month/period is required for payment-related tickets.'];
		}

		$priority = strtolower(trim($data['priority'] ?? 'normal'));
		if (!in_array($priority, ['low', 'normal', 'high'], true)) {
			$priority = 'normal';
		}
		if ($category === 'payment_related' && $priority === 'normal') {
			$priority = 'high';
		}

		$status = 'open';
		$createdBy = substr((string) $createdBy, 0, 128);
		$placeholderCode = 'TX-HR-PENDING';
		$paymentRefVal = $paymentRef === '' ? null : substr($paymentRef, 0, 128);

		$sql = 'INSERT INTO hr_ticket (
			ticket_code, employee_id, category, subject, description, priority,
			status, payment_reference, created_by
		) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)';

		$stmt = $this->conn->prepare($sql);
		if (!$stmt) {
			return ['error' => true, 'message' => mysqli_error($this->conn)];
		}

		$stmt->bind_param(
			'sisssssss',
			$placeholderCode,
			$employeeId,
			$category,
			$subject,
			$description,
			$priority,
			$status,
			$paymentRefVal,
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
		$upd = $this->conn->prepare('UPDATE hr_ticket SET ticket_code = ? WHERE id = ?');
		if ($upd) {
			$upd->bind_param('si', $ticketCode, $newId);
			$upd->execute();
			$upd->close();
		}

		$this->addHistory($newId, 'raised', [
			'status' => $status,
			'summary' => 'Ticket raised (' . $ticketCode . ') — ' . $this->categoryLabel($category) . '.',
		], $createdBy);

		return [
			'error' => false,
			'id' => $newId,
			'ticket_code' => $ticketCode,
			'message' => 'HR ticket raised successfully.',
		];
	}

	/**
	 * @return array{error:bool,message?:string}
	 */
	public function updateAssignmentAndStatus($ticketId, array $data, $updatedBy = '')
	{
		$ticketId = (int) $ticketId;
		if ($ticketId < 1) {
			return ['error' => true, 'message' => 'Invalid ticket.'];
		}

		$ticket = $this->getTicketById($ticketId);
		if (!$ticket) {
			return ['error' => true, 'message' => 'Ticket not found.'];
		}

		$oldStatus = (string) ($ticket['status'] ?? 'open');
		if ($oldStatus === 'closed') {
			return ['error' => true, 'message' => 'Closed tickets cannot be updated.'];
		}

		$assignedTo = isset($data['assigned_to']) ? (int) $data['assigned_to'] : (int) ($ticket['assigned_to'] ?? 0);
		$status = strtolower(trim($data['status'] ?? $oldStatus));
		if (!in_array($status, self::statusOptions(), true)) {
			return ['error' => true, 'message' => 'Invalid status.'];
		}

		$oldAssigned = (int) ($ticket['assigned_to'] ?? 0);

		if ($assignedTo > 0) {
			$empCheck = $this->conn->prepare('SELECT ID FROM employees WHERE ID = ? AND IsActive = 1 LIMIT 1');
			if ($empCheck) {
				$empCheck->bind_param('i', $assignedTo);
				$empCheck->execute();
				$empRes = $empCheck->get_result();
				$empRow = $empRes ? $empRes->fetch_assoc() : null;
				$empCheck->close();
				if (!$empRow) {
					return ['error' => true, 'message' => 'Selected HR representative is not active.'];
				}
			}
		}

		if ($oldStatus === 'open' && $assignedTo > 0 && $status === 'open') {
			$status = 'in_progress';
		}

		$resolvedAt = $ticket['resolved_at'] ?? null;
		$closedAt = $ticket['closed_at'] ?? null;
		$now = date('Y-m-d H:i:s');

		if ($status === 'resolved' && $oldStatus !== 'resolved') {
			$resolvedAt = $now;
		}
		if ($status === 'closed') {
			$closedAt = $now;
		}

		$updatedBy = substr((string) $updatedBy, 0, 128);

		if ($assignedTo > 0) {
			$sql = 'UPDATE hr_ticket SET assigned_to = ?, status = ?, resolved_at = ?, closed_at = ? WHERE id = ?';
			$stmt = $this->conn->prepare($sql);
			if (!$stmt) {
				return ['error' => true, 'message' => mysqli_error($this->conn)];
			}
			$stmt->bind_param('isssi', $assignedTo, $status, $resolvedAt, $closedAt, $ticketId);
		} else {
			$sql = 'UPDATE hr_ticket SET assigned_to = NULL, status = ?, resolved_at = ?, closed_at = ? WHERE id = ?';
			$stmt = $this->conn->prepare($sql);
			if (!$stmt) {
				return ['error' => true, 'message' => mysqli_error($this->conn)];
			}
			$stmt->bind_param('sssi', $status, $resolvedAt, $closedAt, $ticketId);
		}
		if (!$stmt->execute()) {
			$err = $stmt->error;
			$stmt->close();
			return ['error' => true, 'message' => $err];
		}
		$stmt->close();

		$toName = $this->employeeName($assignedTo);
		$fromName = $this->employeeName($oldAssigned);

		if ($oldAssigned < 1 && $assignedTo > 0) {
			$this->addHistory($ticketId, 'assigned', [
				'status' => $status,
				'assigned_to' => $assignedTo,
				'summary' => 'Assigned to ' . ($toName !== '' ? $toName : '#' . $assignedTo)
					. '. Status: ' . $this->statusLabel($status) . '.',
			], $updatedBy);
		} elseif ($oldAssigned > 0 && $assignedTo > 0 && $oldAssigned !== $assignedTo) {
			$this->addHistory($ticketId, 'reassigned', [
				'status' => $status,
				'assigned_from' => $oldAssigned,
				'assigned_to' => $assignedTo,
				'summary' => 'Reassigned from ' . ($fromName !== '' ? $fromName : '#' . $oldAssigned)
					. ' to ' . ($toName !== '' ? $toName : '#' . $assignedTo) . '.',
			], $updatedBy);
		}

		if ($oldStatus !== $status) {
			$this->addHistory($ticketId, 'status_changed', [
				'status' => $status,
				'assigned_to' => $assignedTo > 0 ? $assignedTo : null,
				'summary' => 'Status changed from ' . $this->statusLabel($oldStatus)
					. ' to ' . $this->statusLabel($status) . '.',
			], $updatedBy);
		}

		return [
			'error' => false,
			'message' => 'Ticket updated successfully.',
			'old_status' => $oldStatus,
			'new_status' => $status,
			'old_assigned' => $oldAssigned,
			'new_assigned' => $assignedTo,
			'ticket' => $this->getTicketById($ticketId),
		];
	}

	/**
	 * @return array{error:bool,message?:string,comment_id?:int}
	 */
	public function addComment($ticketId, array $data, $authorEmployeeId, $authorUsername, $authorRole)
	{
		$ticketId = (int) $ticketId;
		$commentText = trim($data['comment_text'] ?? '');
		$isInternal = !empty($data['is_internal']) && (int) $data['is_internal'] === 1;

		if ($ticketId < 1) {
			return ['error' => true, 'message' => 'Invalid ticket.'];
		}
		if ($commentText === '') {
			return ['error' => true, 'message' => 'Comment cannot be empty.'];
		}

		$ticket = $this->getTicketById($ticketId);
		if (!$ticket) {
			return ['error' => true, 'message' => 'Ticket not found.'];
		}
		if ((string) ($ticket['status'] ?? '') === 'closed') {
			return ['error' => true, 'message' => 'Cannot comment on a closed ticket.'];
		}

		$role = in_array($authorRole, ['employee', 'hr', 'system'], true) ? $authorRole : 'employee';
		if ($role === 'employee') {
			$isInternal = false;
		}

		$authorEmployeeId = (int) $authorEmployeeId;
		$authorEmployeeParam = $authorEmployeeId > 0 ? $authorEmployeeId : null;
		$authorUsername = substr((string) $authorUsername, 0, 128);

		$sql = 'INSERT INTO hr_ticket_comment (
			ticket_id, author_employee_id, author_username, author_role, comment_text, is_internal
		) VALUES (?, ?, ?, ?, ?, ?)';

		$stmt = $this->conn->prepare($sql);
		if (!$stmt) {
			return ['error' => true, 'message' => mysqli_error($this->conn)];
		}
		$stmt->bind_param(
			'iisssi',
			$ticketId,
			$authorEmployeeParam,
			$authorUsername,
			$role,
			$commentText,
			$isInternal
		);
		if (!$stmt->execute()) {
			$err = $stmt->error;
			$stmt->close();
			return ['error' => true, 'message' => $err];
		}
		$commentId = (int) $stmt->insert_id;
		$stmt->close();

		$preview = substr(preg_replace('/\s+/', ' ', $commentText), 0, 120);
		$this->addHistory($ticketId, 'comment_added', [
			'status' => $ticket['status'] ?? null,
			'summary' => ($role === 'hr' ? 'HR' : 'Employee') . ' added a comment: ' . $preview,
		], $authorUsername);

		if ($role === 'employee' && (string) ($ticket['status'] ?? '') === 'pending_employee_response') {
			$this->updateAssignmentAndStatus($ticketId, [
				'assigned_to' => (int) ($ticket['assigned_to'] ?? 0),
				'status' => 'in_progress',
			], $authorUsername);
		}

		return [
			'error' => false,
			'message' => 'Comment added.',
			'comment_id' => $commentId,
			'author_role' => $role,
			'is_internal' => $isInternal,
			'ticket' => $this->getTicketById($ticketId),
		];
	}

	/**
	 * @return array{error:bool,message?:string,attachment_id?:int}
	 */
	public function uploadAttachment($ticketId, array $fileData, $uploadedBy, $commentId = 0)
	{
		$ticketId = (int) $ticketId;
		$commentId = (int) $commentId;

		if ($ticketId < 1) {
			return ['error' => true, 'message' => 'Invalid ticket.'];
		}
		if (!isset($fileData['attachment']) || $fileData['attachment']['error'] !== UPLOAD_ERR_OK) {
			$err = isset($fileData['attachment']['error']) ? $fileData['attachment']['error'] : -1;
			if ($err === UPLOAD_ERR_INI_SIZE || $err === UPLOAD_ERR_FORM_SIZE) {
				return ['error' => true, 'message' => 'File is too large. Maximum 10 MB.'];
			}
			return ['error' => true, 'message' => 'Please choose a file to upload.'];
		}

		$ticket = $this->getTicketById($ticketId);
		if (!$ticket) {
			return ['error' => true, 'message' => 'Ticket not found.'];
		}
		if ((string) ($ticket['status'] ?? '') === 'closed') {
			return ['error' => true, 'message' => 'Cannot upload to a closed ticket.'];
		}

		$allowedExt = ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'webp', 'doc', 'docx', 'xls', 'xlsx', 'txt'];
		$maxSize = 10 * 1024 * 1024;

		$originalName = (string) $fileData['attachment']['name'];
		$tmpName = $fileData['attachment']['tmp_name'];
		$fileSize = (int) $fileData['attachment']['size'];
		$ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

		if ($fileSize <= 0 || $fileSize > $maxSize) {
			return ['error' => true, 'message' => 'File must be between 1 byte and 10 MB.'];
		}
		if (!in_array($ext, $allowedExt, true)) {
			return ['error' => true, 'message' => 'File type not allowed.'];
		}

		$uploadDir = dirname(__DIR__) . '/hr-tickets/uploads/';
		if (!is_dir($uploadDir)) {
			@mkdir($uploadDir, 0775, true);
		}

		$storedName = $ticketId . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
		$destPath = $uploadDir . $storedName;

		if (!move_uploaded_file($tmpName, $destPath) && !@rename($tmpName, $destPath)) {
			return ['error' => true, 'message' => 'Could not save uploaded file.'];
		}

		$mime = '';
		if (function_exists('finfo_open')) {
			$finfo = finfo_open(FILEINFO_MIME_TYPE);
			if ($finfo) {
				$mime = (string) finfo_file($finfo, $destPath);
				finfo_close($finfo);
			}
		}

		$uploadedBy = substr((string) $uploadedBy, 0, 128);
		$commentParam = $commentId > 0 ? $commentId : null;

		$sql = 'INSERT INTO hr_ticket_attachment (
			ticket_id, comment_id, file_name, original_name, mime_type, file_size, uploaded_by
		) VALUES (?, ?, ?, ?, ?, ?, ?)';

		$stmt = $this->conn->prepare($sql);
		if (!$stmt) {
			@unlink($destPath);
			return ['error' => true, 'message' => mysqli_error($this->conn)];
		}
		$stmt->bind_param(
			'iisssis',
			$ticketId,
			$commentParam,
			$storedName,
			$originalName,
			$mime,
			$fileSize,
			$uploadedBy
		);
		if (!$stmt->execute()) {
			$err = $stmt->error;
			$stmt->close();
			@unlink($destPath);
			return ['error' => true, 'message' => $err];
		}
		$attachId = (int) $stmt->insert_id;
		$stmt->close();

		$this->addHistory($ticketId, 'attachment_added', [
			'status' => $ticket['status'] ?? null,
			'summary' => 'Attachment uploaded: ' . substr($originalName, 0, 200) . '.',
		], $uploadedBy);

		return [
			'error' => false,
			'message' => 'Attachment uploaded.',
			'attachment_id' => $attachId,
		];
	}

	/**
	 * Mobile API — base64 file upload.
	 * @return array{error:bool,message?:string,attachment_id?:int,file_name?:string}
	 */
	public function uploadBase64Attachment($ticketId, $base64Raw, $originalName, $uploadedBy, $commentId = 0)
	{
		$ticketId = (int) $ticketId;
		$raw = trim((string) $base64Raw);
		if ($raw === '') {
			return ['error' => true, 'message' => 'Attachment data is required.'];
		}
		if (strpos($raw, 'base64,') !== false) {
			$parts = explode('base64,', $raw, 2);
			$raw = $parts[1] ?? '';
		}
		$bin = base64_decode($raw, true);
		if ($bin === false || $bin === '') {
			return ['error' => true, 'message' => 'Invalid attachment data.'];
		}

		$tmp = tempnam(sys_get_temp_dir(), 'hratt_');
		if ($tmp === false) {
			return ['error' => true, 'message' => 'Could not process attachment.'];
		}
		file_put_contents($tmp, $bin);

		$ext = strtolower(pathinfo((string) $originalName, PATHINFO_EXTENSION));
		if ($ext === '') {
			$ext = 'jpg';
		}
		$syntheticName = ($originalName !== '' ? $originalName : 'attachment.' . $ext);

		$result = $this->uploadAttachment($ticketId, [
			'attachment' => [
				'name' => $syntheticName,
				'tmp_name' => $tmp,
				'error' => UPLOAD_ERR_OK,
				'size' => strlen($bin),
			],
		], $uploadedBy, $commentId);

		@unlink($tmp);
		return $result;
	}

	/**
	 * Employee confirms resolution and closes ticket.
	 * @return array{error:bool,message?:string}
	 */
	public function closeByEmployee($ticketId, $employeeId, $username)
	{
		$ticketId = (int) $ticketId;
		$employeeId = (int) $employeeId;

		$ticket = $this->getTicketById($ticketId);
		if (!$ticket) {
			return ['error' => true, 'message' => 'Ticket not found.'];
		}
		if ((int) ($ticket['employee_id'] ?? 0) !== $employeeId) {
			return ['error' => true, 'message' => 'Access denied.'];
		}
		if ((string) ($ticket['status'] ?? '') !== 'resolved') {
			return ['error' => true, 'message' => 'Only resolved tickets can be closed by the employee.'];
		}

		return $this->updateAssignmentAndStatus($ticketId, [
			'assigned_to' => (int) ($ticket['assigned_to'] ?? 0),
			'status' => 'closed',
		], $username);
	}
}
