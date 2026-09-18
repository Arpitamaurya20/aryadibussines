<?php

class TenderRfq extends Core
{
	private $conn;

	public function __construct($conn)
	{
		$this->conn = $conn;
		$this->setTimeZone();
	}

	public function ticketPublicId($id)
	{
		return 'CS-RFQ-' . (int) $id;
	}

	public function defaultQuotationRef($id)
	{
		return 'TECHX-EST-' . str_pad((string) (int) $id, 5, '0', STR_PAD_LEFT);
	}

	private function corporateFilterSql($roles, $userType)
	{
		if ($userType === 'Corporate Admin' || $userType === 'Corporate Branch User') {
			$cid = isset($roles['CorporateID']) ? (int) $roles['CorporateID'] : 0;
			return " AND t.corporate_id = $cid ";
		}
		return '';
	}

	public function listTickets($roles, $userType)
	{
		$extra = $this->corporateFilterSql($roles, $userType);
		$sql = "SELECT t.* FROM tender_rfq_ticket t WHERE 1=1 $extra ORDER BY t.id DESC";
		$rows = [];
		if ($res = mysqli_query($this->conn, $sql)) {
			while ($r = mysqli_fetch_assoc($res)) {
				$rows[] = $r;
			}
		}
		return $rows;
	}

	public function getTicketById($id, $roles, $userType)
	{
		$id = (int) $id;
		if ($id < 1) {
			return null;
		}
		$extra = $this->corporateFilterSql($roles, $userType);
		$sql = "SELECT t.* FROM tender_rfq_ticket t WHERE t.id = $id $extra LIMIT 1";
		$res = mysqli_query($this->conn, $sql);
		if (!$res || !($row = mysqli_fetch_assoc($res))) {
			return null;
		}
		return $row;
	}

	public function createTicket($data, $createdBy)
	{
		$corporateId = isset($data['corporate_id']) ? (int) $data['corporate_id'] : null;
		$branchId = isset($data['branch_id']) ? (int) $data['branch_id'] : null;
		if ($corporateId === 0) {
			$corporateId = null;
		}
		if ($branchId === 0) {
			$branchId = null;
		}

		$quotationRef = $this->conn->real_escape_string(trim($data['quotation_ref'] ?? ''));
		$qdate = $this->conn->real_escape_string(trim($data['quotation_date'] ?? ''));
		$edate = $this->conn->real_escape_string(trim($data['expiry_date'] ?? ''));
		$clientRef = $this->conn->real_escape_string(trim($data['client_reference'] ?? ''));
		$place = $this->conn->real_escape_string(trim($data['place_of_supply'] ?? ''));
		$sales = $this->conn->real_escape_string(trim($data['sales_person'] ?? ''));
		$custName = $this->conn->real_escape_string(trim($data['customer_name'] ?? ''));
		$custContact = $this->conn->real_escape_string(trim($data['customer_contact'] ?? ''));
		$billEntity = $this->conn->real_escape_string(trim($data['bill_to_entity'] ?? ''));
		$billAddr = $this->conn->real_escape_string(trim($data['bill_to_address'] ?? ''));
		$billGst = $this->conn->real_escape_string(trim($data['bill_to_gstin'] ?? ''));
		$shipTo = $this->conn->real_escape_string(trim($data['ship_to'] ?? ''));
		$title = $this->conn->real_escape_string(trim($data['title'] ?? ''));
		$desc = $this->conn->real_escape_string(trim($data['description'] ?? ''));
		$createdBy = $this->conn->real_escape_string(substr($createdBy, 0, 128));

		$qdateSql = $qdate === '' ? 'NULL' : "'$qdate'";
		$edateSql = $edate === '' ? 'NULL' : "'$edate'";
		$corpSql = $corporateId === null ? 'NULL' : (string) $corporateId;
		$branchSql = $branchId === null ? 'NULL' : (string) $branchId;
		$qrefSql = $quotationRef === '' ? 'NULL' : "'$quotationRef'";

		$sql = "INSERT INTO tender_rfq_ticket (corporate_id, branch_id, quotation_ref, quotation_date, expiry_date,
			client_reference, place_of_supply, sales_person, customer_name, customer_contact,
			bill_to_entity, bill_to_address, bill_to_gstin, ship_to, title, description, created_by)
			VALUES ($corpSql, $branchSql, $qrefSql, $qdateSql, $edateSql,
			'$clientRef', '$place', '$sales', '$custName', '$custContact',
			'$billEntity', '$billAddr', '$billGst', '$shipTo', '$title', '$desc', '$createdBy')";

		$res = mysqli_query($this->conn, $sql);
		if (!$res) {
			return ['error' => true, 'message' => mysqli_error($this->conn)];
		}
		$newId = (int) mysqli_insert_id($this->conn);
		if ($quotationRef === '') {
			$autoRef = $this->defaultQuotationRef($newId);
			$autoRefEsc = $this->conn->real_escape_string($autoRef);
			mysqli_query($this->conn, "UPDATE tender_rfq_ticket SET quotation_ref = '$autoRefEsc' WHERE id = $newId");
		}
		return ['error' => false, 'id' => $newId, 'ticket_public_id' => $this->ticketPublicId($newId)];
	}

	public function getImportRows($ticketId)
	{
		$ticketId = (int) $ticketId;
		$rows = [];
		$sql = "SELECT id, unique_key, dynamic_data, row_order FROM tender_rfq_import_row WHERE ticket_id = $ticketId ORDER BY row_order ASC, id ASC";
		if ($res = mysqli_query($this->conn, $sql)) {
			while ($r = mysqli_fetch_assoc($res)) {
				$rows[] = $r;
			}
		}
		return $rows;
	}

	public function decodeHeaders($ticketRow)
	{
		if (empty($ticketRow['csv_headers_json'])) {
			return [];
		}
		$h = json_decode($ticketRow['csv_headers_json'], true);
		return is_array($h) ? $h : [];
	}

	/**
	 * Replace all import rows and set headers from CSV.
	 * $parsed: [ 'headers' => string[], 'rows' => [ ['col'=>'val',...], ... ], 'unique_key_header' => string|null ]
	 */
	public function replaceCsvImport($ticketId, array $parsed)
	{
		$ticketId = (int) $ticketId;
		$headers = $parsed['headers'] ?? [];
		$dataRows = $parsed['rows'] ?? [];
		$uniqueKeyHeader = isset($parsed['unique_key_header']) ? trim((string) $parsed['unique_key_header']) : '';

		if (!is_array($headers) || !count($headers)) {
			return ['error' => true, 'message' => 'CSV has no header row.'];
		}

		mysqli_begin_transaction($this->conn);
		try {
			if (!mysqli_query($this->conn, "DELETE FROM tender_rfq_import_row WHERE ticket_id = $ticketId")) {
				throw new Exception(mysqli_error($this->conn));
			}

			$headersJson = json_encode(array_values($headers), JSON_UNESCAPED_UNICODE);
			if ($headersJson === false) {
				throw new Exception('Invalid header encoding.');
			}
			$headersEsc = mysqli_real_escape_string($this->conn, $headersJson);
			$cnt = count($dataRows);
			if (!mysqli_query($this->conn, "UPDATE tender_rfq_ticket SET csv_headers_json = '$headersEsc', import_row_count = 0 WHERE id = $ticketId")) {
				throw new Exception(mysqli_error($this->conn));
			}

			$order = 0;
			foreach ($dataRows as $assoc) {
				if (!is_array($assoc)) {
					continue;
				}
				$order++;
				$dynJson = json_encode($assoc, JSON_UNESCAPED_UNICODE);
				if ($dynJson === false) {
					$order--;
					continue;
				}
				$dynEsc = mysqli_real_escape_string($this->conn, $dynJson);
				$ukSql = 'NULL';
				if ($uniqueKeyHeader !== '' && isset($assoc[$uniqueKeyHeader])) {
					$uk = substr((string) $assoc[$uniqueKeyHeader], 0, 191);
					if ($uk !== '') {
						$ukEsc = mysqli_real_escape_string($this->conn, $uk);
						$ukSql = "'$ukEsc'";
					}
				}
				$ins = "INSERT INTO tender_rfq_import_row (ticket_id, unique_key, dynamic_data, row_order) VALUES ($ticketId, $ukSql, '$dynEsc', $order)";
				if (!mysqli_query($this->conn, $ins)) {
					throw new Exception(mysqli_error($this->conn));
				}
			}
			if (!mysqli_query($this->conn, "UPDATE tender_rfq_ticket SET import_row_count = $order WHERE id = $ticketId")) {
				throw new Exception(mysqli_error($this->conn));
			}
			mysqli_commit($this->conn);
		} catch (Exception $e) {
			mysqli_rollback($this->conn);
			return ['error' => true, 'message' => $e->getMessage()];
		}

		return ['error' => false, 'imported' => $order];
	}

	/**
	 * Parse uploaded CSV file path into header list and associative rows.
	 */
	public static function parseCsvFile($path, $uniqueKeyColumnName = '')
	{
		$fh = fopen($path, 'r');
		if (!$fh) {
			return ['error' => true, 'message' => 'Could not read CSV file.'];
		}
		$bom = fread($fh, 3);
		if ($bom !== "\xEF\xBB\xBF") {
			rewind($fh);
		}

		$headers = fgetcsv($fh);
		if ($headers === false || !count($headers)) {
			fclose($fh);
			return ['error' => true, 'message' => 'Missing header row.'];
		}
		$headers = array_map(function ($h) {
			return trim((string) $h);
		}, $headers);
		$headers = array_map(function ($h, $i) {
			return $h === '' ? 'Column_' . ($i + 1) : $h;
		}, $headers, array_keys($headers));

		$rows = [];
		while (($cells = fgetcsv($fh)) !== false) {
			if ($cells === null) {
				continue;
			}
			$allEmpty = true;
			foreach ($cells as $c) {
				if (trim((string) $c) !== '') {
					$allEmpty = false;
					break;
				}
			}
			if ($allEmpty) {
				continue;
			}
			$assoc = [];
			foreach ($headers as $i => $key) {
				$assoc[$key] = isset($cells[$i]) ? trim((string) $cells[$i]) : '';
			}
			$rows[] = $assoc;
		}
		fclose($fh);

		return [
			'error' => false,
			'headers' => $headers,
			'rows' => $rows,
			'unique_key_header' => $uniqueKeyColumnName,
		];
	}
}
