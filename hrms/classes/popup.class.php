<?php
class Popup extends Core
{
    private $conn;
    public function __construct($conn)
    {
        $this->conn = $conn;
        $this->setTimeZone();
    }

    public function insertPopupImages($data)
    {
        return $this->_InsertTableRecords_prepare($this->conn, 'popup_image', $data);
    }

    public function insertPopupImageNew($data)
    {
        return $this->_InsertTableRecords_prepare($this->conn, '`popup-image`', $data);
    }

    public function insertPopupImageWithLink($data)
    {
        return $this->_InsertTableRecords_prepare($this->conn, '`popup-image`', $data);
    }

    public function updatePopupImageNew($data)
    {
        $imageNumber = $data['imageNumber'];
        $imagePath = $data['imagePath'];
        $createdDate = $data['CreatedDate'];
        $id = $data['ID'];
        
        $updateData = [
            "P{$imageNumber}" => $imagePath,
            'CreatedDate' => $createdDate
        ];
        
        return $this->_UpdateTableRecords_prepare($this->conn, '`popup-image`', $updateData, "ID = {$id}");
    }

    public function updatePopupImageWithLink($data)
    {
        $imageNumber = $data['imageNumber'];
        $imagePath = $data['imagePath'];
        $link = $data['link'];
        $createdDate = $data['CreatedDate'];
        $id = $data['ID'];
        
        // Build update data dynamically
        $updateData = [
            'CreatedDate' => $createdDate
        ];
        
        // Add image update if new image is provided
        if (!empty($imagePath)) {
            $updateData["P{$imageNumber}"] = $imagePath;
        }
        
        // Add link update if link is provided
        if (!empty($link)) {
            $updateData["L{$imageNumber}"] = $link;
        }
        
        return $this->_UpdateTableRecords_prepare($this->conn, '`popup-image`', $updateData, "ID = {$id}");
    }

    public function getCurrentPopupImages()
    {
        $sql = "SELECT ID, P1, L1, P2, L2, P3, L3, P4, L4, IsActive, CreatedDate 
                FROM `popup-image` 
                WHERE IsActive = 1 
                ORDER BY ID DESC 
                LIMIT 1";

        $stmt = $this->conn->prepare($sql);
        if ($stmt) {
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                return $result->fetch_assoc();
            }
        }
        
        return null;
    }

    public function checkPopupImageExists()
    {
        $sql = "SELECT ID FROM `popup-image` WHERE IsActive = 1 LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        if ($stmt) {
            $stmt->execute();
            $result = $stmt->get_result();
            return $result->num_rows > 0;
        }
        return false;
    }
}
