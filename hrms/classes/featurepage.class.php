<?php
class FeaturePage extends Core
{
    private $conn;
    public function __construct($conn)
    {
        $this->conn = $conn;
        $this->setTimeZone();
    }
    public function setTimeZone($timezone = 'Asia/Kolkata')
    {
        date_default_timezone_set($timezone);
    }

    public function InsertFeaturePage($data)
    {
        try {
            // Set card_id based on type
            $card_id = $data['type'] === 'course' ? $data['course_id'] : $data['test_series_id'];

            // Initialize empty values for optional fields

            // Initialize empty values for optional fields
            $schedule_pdf_file = $data['schedule_pdf_file'] ?? '';
            $syllabus_pdf_file = $data['syllabus_pdf_file'] ?? '';
            $course_overview = $data['course_overview'] ?? '';
            $notes = $data['notes'] ?? '';
            $created_by = $data['CreatedBy'] ?? '';

            $row_data = [
                "slug" => $data['slug'],
                "type" => $data['type'],
                "card_id" => $card_id,
                "feature_image" => $data['feature_image'],
                "schedule_pdf_file" => $schedule_pdf_file,
                "syllabus_pdf_file" => $syllabus_pdf_file,
                "heading" => $data['heading'],  
                "course_overview" => $course_overview,
                "start_date" => $data['start_date'],
                "start_time" => $data['start_time'],
                "duration" => $data['duration'],
                "price" => $data['price'],
                "notes" => $notes,
                "MetaTitle" => $data['meta_title'],
                "MetaDescription" => $data['meta_description'],
                "created_by" => $created_by
            ];
            $response_insert_custom_inventory = $this->_InsertTableRecords_prepare($this->conn, "feature_page", $row_data);

           
            return ['error' => false, 'message' => 'Feature page created successfully'];
        } catch (Exception $e) {
          
            return ['error' => true, 'message' => $e->getMessage()];
        }
    }


    function UpdateFeaturePage($data)
    {
        try {
            $id = $data['ID'] ?? 0;
            // Fetch existing details
            $old_details = $this->GetFeaturePageDetailsbyID($id);
            if (!$old_details) {
                throw new Exception("Feature page not found");
            }

            // Validate course/test series selection
            if ($data['type'] === 'course' && empty($data['course_id'])) {
                throw new Exception("Please select a Course");
            }
            if ($data['type'] === 'test_series' && empty($data['test_series_id'])) {
                throw new Exception("Please select a Test Series");
            }

            // Set card_id based on type
            $card_id = $data['type'] === 'course' ? $data['course_id'] : $data['test_series_id'];

            // Keep existing image if no new image is uploaded
            $feature_image = !empty($data['feature_image']) ? $data['feature_image'] : $old_details['feature_image'];

           

            // Initialize empty values for optional fields
            $schedule_pdf_file = $data['schedule_pdf_file'] ?? '';
            $syllabus_pdf_file = $data['syllabus_pdf_file'] ?? '';
            $course_overview = $data['course_overview'] ?? '';
            $notes = $data['notes'] ?? '';
            $created_by = $data['CreatedBy'] ?? '';

            $data = [
                "slug" => $data['slug'],
                "type" => $data['type'],
                "card_id" => $card_id,
                "feature_image" => $feature_image,
                "schedule_pdf_file" => $schedule_pdf_file,
                "syllabus_pdf_file" => $syllabus_pdf_file,
                "heading" => $data['heading'],
                "course_overview" => $course_overview,
                "start_date" => $data['start_date'],
                "start_time" => $data['start_time'],
                "duration" => $data['duration'],
                "price" => $data['price'],
                "notes" => $notes,
                "MetaTitle" => $data['meta_title'],
                "MetaDescription" => $data['meta_description']
            ];
            $where = "ID = $id";
            $response = $this->_UpdateTableRecords_prepare($this->conn, 'feature_page', $data, $where);
            if($response['error'] == false)
            {
                return ['error' => false, 'message' => 'Feature page updated successfully'];
            }
            else
            {
                return ['error' => true, 'message' => 'Failed to update feature page'];
            }
        } catch (Exception $e) {
            return ['error' => true, 'message' => $e->getMessage()];
        }
    }

    function DeleteFeaturePage($data)
    {
        $id = $data['ID'] ?? 0;
        if (!$id) {
            return ['message' => "Invalid feature page ID", 'error' => true];
        }

        // First get the feature page details to delete associated files
        $feature_page = $this->GetFeaturePageDetailsbyID($id);
        if ($feature_page) {
            // Delete feature image
            if (!empty($feature_page['feature_image'])) {
                $image_path = "../../../project-assets/images/Feature_page/" . $feature_page['feature_image'];
                if (file_exists($image_path)) {
                    unlink($image_path);
                }
            }

            // Delete PDF files
            if (!empty($feature_page['schedule_pdf_file'])) {
                $schedule_pdf_path = "../../../project-assets/pdf/feature_page/" . $feature_page['schedule_pdf_file'];
                if (file_exists($schedule_pdf_path)) {
                    unlink($schedule_pdf_path);
                }
            }

            if (!empty($feature_page['syllabus_pdf_file'])) {
                $syllabus_pdf_path = "../../../project-assets/pdf/feature_page/" . $feature_page['syllabus_pdf_file'];
                if (file_exists($syllabus_pdf_path)) {
                    unlink($syllabus_pdf_path);
                }
            }
        }

        $where = " WHERE ID = $id";
        $response = $this->delete_identity_filter($this->conn, "feature_page", $where);

        if ($response) {
            return ['message' => "Feature Page Successfully Deleted!", 'error' => false];
        } else {
            return ['message' => "Failed to delete Feature Page", 'error' => true];
        }
    }

    function GetFeaturePageDetailsbyID($id)
    {
        if (!$id) {
            return null;
        }
        $where = " WHERE ID = $id";
        return $this->_getTableDetails($this->conn, 'feature_page', $where);
    }
}