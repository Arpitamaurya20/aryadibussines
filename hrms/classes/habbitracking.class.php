<?php
/**
 * Habit tracking – date-wise check (Yes/No) per habit in a checklist.
 * Table: habbit_tracking (ID, CheckListID, HabbitID, CheckingDate, CheckStatus, ImagePath, IsActive, CreatedDate)
 * Uses Core _InsertTableRecords_prepare, _UpdateTableRecords_prepare.
 */
class Habbitracking extends Core
{
    private $conn;
    private $table = 'habbit_tracking';

    public function __construct($conn)
    {
        $this->conn = $conn;
        $this->setTimeZone();
    }

    /**
     * Insert one tracking record
     * @param array $data [CheckListID, HabbitID, CheckingDate, CheckStatus, ImagePath?, IsActive?, CreatedDate?]
     */
    public function insert($data)
    {
        if (empty($data['CreatedDate'])) {
            $data['CreatedDate'] = date('Y-m-d');
        }
        if (!isset($data['IsActive'])) {
            $data['IsActive'] = 1;
        }
        if (!isset($data['ImagePath'])) {
            $data['ImagePath'] = '';
        }
        return $this->_InsertTableRecords_prepare($this->conn, $this->table, $data);
    }

    /**
     * Update by ID
     */
    public function update($id, $data)
    {
        $id = (int) $id;
        if ($id <= 0) {
            return ['error' => true, 'message' => 'Invalid ID'];
        }
        $where = "ID = $id";
        return $this->_UpdateTableRecords_prepare($this->conn, $this->table, $data, $where);
    }

    /**
     * Find existing record by CheckListID, HabbitID, CheckingDate (for upsert)
     * @return array|null row or null
     */
    public function getExisting($checkListId, $habbitId, $checkingDate)
    {
        $checkListId = (int) $checkListId;
        $habbitId = (int) $habbitId;
        $checkingDate = $this->conn->real_escape_string($checkingDate);
        $where = "WHERE CheckListID = $checkListId AND HabbitID = $habbitId AND CheckingDate = '$checkingDate' AND IsActive = 1";
        return $this->_getTableDetails($this->conn, $this->table, $where);
    }

    /**
     * Upsert: insert or update one tracking row for (CheckListID, HabbitID, CheckingDate)
     * @param array $data [CheckListID, HabbitID, CheckingDate, CheckStatus, ImagePath?]
     * @return array [error, message, id?, inserted?]
     */
    public function upsertOne($data)
    {
        $checkListId = (int) ($data['CheckListID'] ?? 0);
        $habbitId = (int) ($data['HabbitID'] ?? 0);
        $checkingDate = $data['CheckingDate'] ?? date('Y-m-d');
        if ($checkListId <= 0 || $habbitId <= 0) {
            return ['error' => true, 'message' => 'CheckListID and HabbitID required'];
        }

        $existing = $this->getExisting($checkListId, $habbitId, $checkingDate);
        $updateData = [
            'CheckStatus' => $data['CheckStatus'] ?? 'No',
            'ImagePath'   => $data['ImagePath'] ?? '',
        ];

        if ($existing && !empty($existing['ID'])) {
            $res = $this->update($existing['ID'], $updateData);
            $res['id'] = $existing['ID'];
            $res['inserted'] = false;
            return $res;
        }

        $insertData = [
            'CheckListID'  => $checkListId,
            'HabbitID'     => $habbitId,
            'CheckingDate' => $checkingDate,
            'CheckStatus'  => $updateData['CheckStatus'],
            'ImagePath'    => $updateData['ImagePath'],
            'CreatedDate'  => date('Y-m-d'),
            'IsActive'     => 1,
        ];
        $res = $this->insert($insertData);
        if (!$res['error'] && isset($res['last_insert_id'])) {
            $res['id'] = $res['last_insert_id'];
            $res['inserted'] = true;
        }
        return $res;
    }

    /**
     * Get all tracking for a checklist on a given date (optionally with habit names)
     */
    public function getByCheckListAndDate($checkListId, $checkingDate, $activeOnly = true)
    {
        $checkListId = (int) $checkListId;
        $checkingDate = $this->conn->real_escape_string($checkingDate);
        if ($checkListId <= 0) {
            return [];
        }
        $activeClause = $activeOnly ? ' AND ht.IsActive = 1' : '';
        $sql = "SELECT ht.ID, ht.CheckListID, ht.HabbitID, ht.CheckingDate, ht.CheckStatus, ht.ImagePath, ht.IsActive, ht.CreatedDate, "
            . "mh.HabbitName FROM {$this->table} ht "
            . "LEFT JOIN master_habbit mh ON mh.ID = ht.HabbitID "
            . "WHERE ht.CheckListID = $checkListId AND ht.CheckingDate = '$checkingDate' $activeClause "
            . "ORDER BY ht.HabbitID ASC";
        $result = mysqli_query($this->conn, $sql);
        if (!$result) {
            return [];
        }
        $rows = [];
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
        return $rows;
    }

    /**
     * Get tracking for a checklist in a date range
     */
    public function getByCheckListAndDateRange($checkListId, $fromDate, $toDate, $activeOnly = true)
    {
        $checkListId = (int) $checkListId;
        $fromDate = $this->conn->real_escape_string($fromDate);
        $toDate = $this->conn->real_escape_string($toDate);
        if ($checkListId <= 0) {
            return [];
        }
        $activeClause = $activeOnly ? ' AND ht.IsActive = 1' : '';
        $sql = "SELECT ht.ID, ht.CheckListID, ht.HabbitID, ht.CheckingDate, ht.CheckStatus, ht.ImagePath, ht.IsActive, ht.CreatedDate, "
            . "mh.HabbitName FROM {$this->table} ht "
            . "LEFT JOIN master_habbit mh ON mh.ID = ht.HabbitID "
            . "WHERE ht.CheckListID = $checkListId AND ht.CheckingDate >= '$fromDate' AND ht.CheckingDate <= '$toDate' $activeClause "
            . "ORDER BY ht.CheckingDate DESC, ht.HabbitID ASC";
        $result = mysqli_query($this->conn, $sql);
        if (!$result) {
            return [];
        }
        $rows = [];
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
        return $rows;
    }

    /**
     * Get one by ID
     */
    public function getById($id)
    {
        $id = (int) $id;
        if ($id <= 0) {
            return null;
        }
        $where = "WHERE ID = $id";
        return $this->_getTableDetails($this->conn, $this->table, $where);
    }

    /**
     * Soft delete
     */
    public function delete($id)
    {
        $id = (int) $id;
        if ($id <= 0) {
            return ['error' => true, 'message' => 'Invalid ID'];
        }
        $where = "ID = $id";
        return $this->_UpdateTableRecords_prepare($this->conn, $this->table, ['IsActive' => 0], $where);
    }
}
