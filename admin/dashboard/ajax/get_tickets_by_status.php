<?php
@session_start();
include('../../includes/autoloader.inc.php');
include('../controller/dashboard_controller.php');

header('Content-Type: application/json');

$dbh = new Dbh();
$conn = $dbh->_connectodb();
$core = new Core();
$core->setTimeZone();

$CorporateID = isset($_POST['CorporateID']) ? intval($_POST['CorporateID']) : -1;
$ticket_scope = isset($_POST['ticket_scope']) ? $_POST['ticket_scope'] : '';
$ticket_type = isset($_POST['ticket_type']) ? $_POST['ticket_type'] : '';
$ticket_status = isset($_POST['ticket_status']) ? $_POST['ticket_status'] : '';

$state_filter = isset($_POST['state_filter']) ? $_POST['state_filter'] : '';
$region_filter = isset($_POST['region_filter']) ? $_POST['region_filter'] : '';
$filter_date = isset($_POST['filter_date']) ? $_POST['filter_date'] : '';

$sql_in_state_string = isset($_POST['sql_in_state_string']) ? $_POST['sql_in_state_string'] : '';
$sql_in_branch_account_string = isset($_POST['sql_in_branch_account_string']) ? $_POST['sql_in_branch_account_string'] : '';

$analytics_filter = array();
$analyticsBranchID = applyAnalyticsBranchFilter($analytics_filter);

$limit = isset($_POST['limit']) ? intval($_POST['limit']) : 200;
if ($limit <= 0) {
  $limit = 200;
}
$offset = isset($_POST['offset']) ? intval($_POST['offset']) : 0;
if ($offset < 0) {
  $offset = 0;
}

// Small helper to parse "YYYY-MM-DD - YYYY-MM-DD"
function parseDateRange($filter_date) {
  $filter_date = trim((string)$filter_date);
  if ($filter_date === '') {
    return array('', '');
  }
  $parts = explode(' - ', $filter_date);
  if (count($parts) !== 2) {
    return array('', '');
  }
  return array(trim($parts[0]), trim($parts[1]));
}

$ticket_status_escaped = $conn->real_escape_string((string)$ticket_status);
$ticket_type_escaped = $conn->real_escape_string((string)$ticket_type);
$state_filter_escaped = $conn->real_escape_string((string)$state_filter);
$region_filter_escaped = $conn->real_escape_string((string)$region_filter);

if ($ticket_scope === 'ppm') {
  $where = " WHERE a.IsActive = 1 ";
  if ($CorporateID !== -1) {
    $where .= " AND a.CorporateID = " . intval($CorporateID) . " ";
  }

  if ($state_filter_escaped !== '') {
    $where .= " AND a.BranchID IN (Select ID from branch where BranchState = '" . $state_filter_escaped . "') ";
  } else if ($region_filter_escaped !== '') {
    // Resolve RegionName -> RegionID
    $region_where = " where RegionName = '" . $region_filter_escaped . "'";
    $region_details = $core->_getTableDetails($conn, 'region', $region_where);
    $region_id = isset($region_details['ID']) ? intval($region_details['ID']) : 0;
    if ($region_id > 0) {
      $where .= " AND a.BranchID IN (Select ID from branch where BranchState IN (Select StateName from state where RegionID = " . $region_id . ")) ";
    } else {
      echo json_encode(array('ok' => true, 'total_count' => 0, 'limit' => $limit, 'tickets' => array()));
      exit;
    }
  }

  // Optional ticket type filter (not used by current UI, but kept for safety)
  if ($ticket_type_escaped !== '' && $ticket_type_escaped !== 'PPM') {
    $where .= " AND a.Type = '" . $ticket_type_escaped . "' ";
  }

  if ($sql_in_state_string !== '') {
    $where .= " AND a.BranchID IN (Select ID from branch where BranchState IN (" . $sql_in_state_string . ")) ";
  }
  if ($sql_in_branch_account_string !== '') {
    $where .= " AND a.BranchID IN (" . $sql_in_branch_account_string . ") ";
  }
  if ($analyticsBranchID != -1) {
    $where .= " AND a.BranchID = " . intval($analyticsBranchID) . " ";
  }

  list($startDate, $endDate) = parseDateRange($filter_date);
  if ($startDate !== '' && $endDate !== '') {
    $where .= " AND (a.PPMDate >= '" . $conn->real_escape_string($startDate) . "' AND a.PPMDate <= '" . $conn->real_escape_string($endDate) . "') ";
  }

  // Overdue uses the same CASE expression as the dashboard count logic
  $caseExpr = "CASE WHEN a.Status IN ('Planned','Raised') AND a.PPMDate < DATE_SUB(CURDATE(), INTERVAL 7 DAY) THEN 'Overdue' ELSE a.Status END";
  $where .= " AND " . $caseExpr . " = '" . $ticket_status_escaped . "' ";

  $sqlCount = "SELECT COUNT(*) as cnt FROM ppm_tickets a " . $where;
  $resCount = mysqli_query($conn, $sqlCount);
  $total = 0;
  if ($resCount && $row = $resCount->fetch_assoc()) {
    $total = intval($row['cnt']);
  }

  // Join branch table so we can show BranchSite next to Ticket ID.
  $sql = "SELECT a.TicketID, b.BranchSite, b.BranchCode 
          FROM ppm_tickets a
          LEFT JOIN branch b ON a.BranchID = b.ID " . $where . "
          ORDER BY a.PPMDate DESC, a.ID DESC
          LIMIT " . $limit . " OFFSET " . $offset;
  $result = mysqli_query($conn, $sql);

  $tickets = array();
  if ($result) {
    while ($row = $result->fetch_assoc()) {
      $tickets[] = array(
        'ticket_id' => $row['TicketID'],
        'branch_site' => $row['BranchSite'],
        'branch_code' => $row['BranchCode']
      );
    }
  }

  echo json_encode(array(
    'ok' => true,
    'total_count' => $total,
    'limit' => $limit,
    'tickets' => $tickets
  ));
  exit;
}

if ($ticket_scope === 'corporate') {
  if ($ticket_type_escaped === '') {
    echo json_encode(array('ok' => false, 'msg' => 'Missing ticket type.'));
    exit;
  }

  $where = " WHERE ct.IsActive = 1 ";
  if ($CorporateID !== -1) {
    $where .= " AND ct.CorporateID = " . intval($CorporateID) . " ";
  }

  $where .= " AND ct.Status = '" . $ticket_status_escaped . "' ";
  $where .= " AND ct.Type = '" . $ticket_type_escaped . "' ";

  if ($state_filter_escaped !== '') {
    $where .= " AND ct.BranchID IN (Select ID from branch where BranchState = '" . $state_filter_escaped . "') ";
  } else if ($region_filter_escaped !== '') {
    // Resolve RegionName -> RegionID
    $region_where = " where RegionName = '" . $region_filter_escaped . "'";
    $region_details = $core->_getTableDetails($conn, 'region', $region_where);
    $region_id = isset($region_details['ID']) ? intval($region_details['ID']) : 0;
    if ($region_id > 0) {
      $where .= " AND ct.BranchID IN (Select ID from branch where BranchState IN (Select StateName from state where RegionID = " . $region_id . ")) ";
    } else {
      echo json_encode(array('ok' => true, 'total_count' => 0, 'limit' => $limit, 'tickets' => array()));
      exit;
    }
  }

  if ($sql_in_state_string !== '') {
    $where .= " AND ct.BranchID IN (Select ID from branch where BranchState IN (" . $sql_in_state_string . ")) ";
  }
  if ($sql_in_branch_account_string !== '') {
    $where .= " AND ct.BranchID IN (" . $sql_in_branch_account_string . ") ";
  }
  if ($analyticsBranchID != -1) {
    $where .= " AND ct.BranchID = " . intval($analyticsBranchID) . " ";
  }

  list($startDate, $endDate) = parseDateRange($filter_date);
  if ($startDate !== '' && $endDate !== '') {
    $where .= " AND (ct.CreatedDate >= '" . $conn->real_escape_string($startDate) . "' AND ct.CreatedDate <= '" . $conn->real_escape_string($endDate) . "') ";
  }

  $sqlCount = "SELECT COUNT(*) as cnt FROM corporate_tickets ct " . $where;
  $resCount = mysqli_query($conn, $sqlCount);
  $total = 0;
  if ($resCount && $row = $resCount->fetch_assoc()) {
    $total = intval($row['cnt']);
  }

  // Join branch table so we can show BranchSite next to Ticket ID.
  $sql = "SELECT ct.TicketID, b.BranchSite, b.BranchCode
          FROM corporate_tickets ct
          LEFT JOIN branch b ON ct.BranchID = b.ID " . $where . "
          ORDER BY ct.CreatedDate DESC, ct.ID DESC
          LIMIT " . $limit . " OFFSET " . $offset;
  $result = mysqli_query($conn, $sql);

  $tickets = array();
  if ($result) {
    while ($row = $result->fetch_assoc()) {
      $tickets[] = array(
        'ticket_id' => $row['TicketID'],
        'branch_site' => $row['BranchSite'],
        'branch_code' => $row['BranchCode']
      );
    }
  }

  echo json_encode(array(
    'ok' => true,
    'total_count' => $total,
    'limit' => $limit,
    'tickets' => $tickets
  ));
  exit;
}

echo json_encode(array(
  'ok' => false,
  'msg' => 'Invalid ticket scope.'
));
exit;

?>

