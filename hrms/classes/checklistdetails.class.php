<?php
/**
 * Checklist details – uses Core _InsertTableRecords_prepare, _UpdateTableRecords_prepare.
 * Table: checklistdetails (ID, CheckListName, StartDate, TargetDays, CreatedDate, CreatedBy, IsActive)
 */
class Checklistdetails extends Core
{
    private $conn;
    private $table = 'checklistdetails';

    public function __construct($conn)
    {
        $this->conn = $conn;
        $this->setTimeZone();
    }

    /**
     * Insert using Core prepared insert
     * @param array $data [CheckListName, StartDate?, TargetDays?, CreatedDate?, CreatedBy?, IsActive?]
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
        if (!isset($data['CreatedBy'])) {
            $data['CreatedBy'] = 0;
        }
        return $this->_InsertTableRecords_prepare($this->conn, $this->table, $data);
    }

    /**
     * Update using Core prepared update
     * @param int $id
     * @param array $data [CheckListName?, StartDate?, TargetDays?, IsActive?]
     * @return array [error, message]
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
     * Soft delete: set IsActive = 0 using Core prepared update
     * @param int $id
     * @return array [error, message]
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
     * Hard delete: remove row (uses delete_identity_filter)
     * @param int $id
     * @return bool
     */
    public function hardDelete($id)
    {
        $id = (int) $id;
        if ($id <= 0) {
            return false;
        }
        return $this->delete_identity_filter($this->conn, $this->table, "WHERE ID = $id");
    }

    /**
     * Get one row by ID
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
     * Get all records (optional: only active)
     */
    public function getAll($activeOnly = true)
    {
        $where = $activeOnly ? "WHERE IsActive = 1" : "";
        $where .= ($where ? " ORDER BY CreatedDate DESC, ID DESC" : "ORDER BY CreatedDate DESC, ID DESC");
        return $this->_getTableRecords($this->conn, $this->table, $where);
    }

    /**
     * Get all records by CreatedBy (user's own checklists)
     */
    public function getByCreatedBy($createdBy, $activeOnly = true)
    {
        $createdBy = (int) $createdBy;
        $where = "WHERE CreatedBy = $createdBy";
        if ($activeOnly) {
            $where .= " AND IsActive = 1";
        }
        $where .= " ORDER BY CreatedDate DESC, ID DESC";
        return $this->_getTableRecords($this->conn, $this->table, $where);
    }

    /**
     * Get one row by ID only if it belongs to the user (CreatedBy)
     */
    public function getByIdAndCreatedBy($id, $createdBy)
    {
        $id = (int) $id;
        $createdBy = (int) $createdBy;
        if ($id <= 0) {
            return null;
        }
        $where = "WHERE ID = $id AND CreatedBy = $createdBy";
        return $this->_getTableDetails($this->conn, $this->table, $where);
    }
}
