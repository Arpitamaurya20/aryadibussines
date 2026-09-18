<?php
/**
 * Master habit list.
 * Table: master_habbit (ID, HabbitName, CreateData, IsActive)
 */
class Masterhabbit extends Core
{
    private $conn;
    private $table = 'master_habbit';

    public function __construct($conn)
    {
        $this->conn = $conn;
        $this->setTimeZone();
    }

    /**
     * Get all master habits (optional: active only)
     */
    public function getAll($activeOnly = true)
    {
        $where = $activeOnly ? "WHERE IsActive = 1" : "";
        $where .= ($where ? " ORDER BY HabbitName ASC" : "ORDER BY HabbitName ASC");
        return $this->_getTableRecords($this->conn, $this->table, $where);
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
     * Check if habit ID exists
     */
    public function exists($id)
    {
        $id = (int) $id;
        if ($id <= 0) {
            return false;
        }
        $where = "WHERE ID = $id";
        return $this->_getTotalRows($this->conn, $this->table, $where) > 0;
    }

    /**
     * Insert new master habit (Core prepared)
     * @param array $data [HabbitName, CreateData?, IsActive?]
     */
    public function insert($data)
    {
        if (!isset($data['CreateData']) || $data['CreateData'] === '') {
            $data['CreateData'] = date('Y-m-d');
        }
        if (!isset($data['IsActive'])) {
            $data['IsActive'] = 1;
        }
        return $this->_InsertTableRecords_prepare($this->conn, $this->table, $data);
    }

    /**
     * Soft delete: set IsActive = 0 (or hard delete – only for System Admin)
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
