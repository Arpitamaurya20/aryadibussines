<?php
/**
 * Checklist setting – links a habit (from master_habbit) to a checklist.
 * Table: checklistsetting (ID, CheckListID, HabbitID, CreatedDate, IsActive)
 * Uses Core _InsertTableRecords_prepare, _UpdateTableRecords_prepare.
 */
class Checklistsetting extends Core
{
    private $conn;
    private $table = 'checklistsetting';

    public function __construct($conn)
    {
        $this->conn = $conn;
        $this->setTimeZone();
    }

    /**
     * Add a habit to a checklist (insert into checklistsetting)
     * @param array $data [CheckListID, HabbitID, CreatedDate?, IsActive?]
     * @return array [error, message, last_insert_id?]
     */
    public function insert($data)
    {
        if (empty($data['CreatedDate'])) {
            $data['CreatedDate'] = date('Y-m-d');
        }
        if (!isset($data['IsActive'])) {
            $data['IsActive'] = 1;
        }
        return $this->_InsertTableRecords_prepare($this->conn, $this->table, $data);
    }

    /**
     * Soft delete: set IsActive = 0
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

    /**
     * Remove one habit from a checklist by CheckListID and HabbitID
     */
    public function removeHabitFromChecklist($checkListId, $habbitId)
    {
        $checkListId = (int) $checkListId;
        $habbitId = (int) $habbitId;
        if ($checkListId <= 0 || $habbitId <= 0) {
            return ['error' => true, 'message' => 'Invalid CheckListID or HabbitID'];
        }
        $where = "CheckListID = $checkListId AND HabbitID = $habbitId";
        return $this->_UpdateTableRecords_prepare($this->conn, $this->table, ['IsActive' => 0], $where);
    }

    /**
     * Get all habits assigned to a checklist (active only)
     * Returns rows from checklistsetting joined with master_habbit
     */
    public function getHabitsByCheckListID($checkListId, $activeOnly = true)
    {
        $checkListId = (int) $checkListId;
        if ($checkListId <= 0) {
            return [];
        }
        $activeClause = $activeOnly ? ' AND cs.IsActive = 1 AND mh.IsActive = 1' : '';
        $sql = "SELECT cs.ID, cs.CheckListID, cs.HabbitID, cs.CreatedDate, cs.IsActive, "
            . "mh.HabbitName, mh.CreateData AS HabbitCreateData "
            . "FROM {$this->table} cs "
            . "INNER JOIN master_habbit mh ON mh.ID = cs.HabbitID "
            . "WHERE cs.CheckListID = $checkListId $activeClause "
            . "ORDER BY cs.CreatedDate DESC, cs.ID DESC";
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
     * Get single row by ID
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
     * Check if this habit is already added to this checklist (active)
     */
    public function isHabitInChecklist($checkListId, $habbitId)
    {
        $checkListId = (int) $checkListId;
        $habbitId = (int) $habbitId;
        if ($checkListId <= 0 || $habbitId <= 0) {
            return false;
        }
        $where = "WHERE CheckListID = $checkListId AND HabbitID = $habbitId AND IsActive = 1";
        $n = $this->_getTotalRows($this->conn, $this->table, $where);
        return $n > 0;
    }
}
