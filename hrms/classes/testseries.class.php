<?php
class TestSeries extends Core
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

    function InsertTestSeries($data)
    {
        $course_center_id = $data['Test_Series_center_id'];
        $course_name = $data['title'];
        $start_date = $data['sdate'];
        $end_date = $data['edate'];
        $price = $data['mrp'];
        $price = $data['price'];
        $test_series_type = $data['test_series_type'];
        $course_test = $data['course_test'];
        $image = $data['test_image'];
        $CreatedDate = date('Y-m-d');
        $CreatedTime = date('H:i:s');
        $CreatedBy = $data['CreatedBy'];
        $gst = isset($data['gst']) && is_numeric($data['gst']) ? $data['gst'] : 18; // Default GST value
        $type = isset($data['type']) ? $data['type'] : 'test_series'; // Default type value

        $sql = "INSERT INTO test_series (CenterID, title, test_type, course_test, test_mrp, price, gst, start_date, end_date, test_image, type, CreatedTime, CreatedDate, CreatedBy) 
            VALUES ('$course_center_id', '$course_name', '$test_series_type','$course_test', '$price', '$price', '$gst', '$start_date', '$end_date', '$image', '$type', '$CreatedTime', '$CreatedDate', '$CreatedBy')";

        $response_insert_course_details = $this->_InsertTableRecords($this->conn, $sql);

        $response_insert_course_details['message'] = "Test Series Successfully Added!";

        return $response_insert_course_details;

    }


    function UpdateTestSeries($data)
    {
        $test_series_id = $data['form_id'];
        $course_center_id = $data['Test_Series_center_id'];
        $course_name = $data['title'];
        $start_date = $data['sdate'];
        $end_date = $data['edate'];
        $mrp = $data['mrp'];
        $price = $data['price'];
        $test_series_type = $data['test_series_type'];
        $course_test = $data['course_test'];
        $image = $data['test_image'];

        // Fetch existing details to check for changes
        $old_details = $this->GetTestSeriesDetailsbyID($test_series_id);

        if (
            $course_name == $old_details['title'] &&
            $test_series_type == $old_details['test_type'] &&
            $course_test == $old_details['course_test'] &&
            $start_date == $old_details['start_date'] &&
            $end_date == $old_details['end_date'] &&
            $mrp == $old_details['test_mrp'] &&
            $price == $old_details['price'] &&
            $image == $old_details['test_image']

        ) {
            return ['message' => "No changes to update", 'error' => true];
        } else {
            $update_query = " 
                title = '$course_name', 
                start_date = '$start_date', 
                end_date = '$end_date', 
                test_mrp = '$mrp', 
                price = '$price', 
                course_test = '$course_test', 
                test_type = '$test_series_type', 
                test_image = '$image', 
                CenterID = '$course_center_id' 
                WHERE ID = $test_series_id";

            return $this->_UpdateTableRecords($this->conn, 'test_series', $update_query) + ['message' => "Test Series Successfully Updated!"];
        }
    }

    function DeleteTestSeries($data)
    {
        $TestSeriesID = $data['ID'];
        // Delete test series from the database
        $where = " where ID = $TestSeriesID";
        $response = $this->delete_identity_filter($this->conn, "test_series", $where);

        if ($response) {
            return ['message' => "Test Series Successfully Deleted!", 'error' => false];
        } else {
            return ['message' => "Failed to delete Test Series", 'error' => true];
        }
    }

    function GetTestSeriesDetailsbyID($ID)
    {
        $where = " where ID = $ID";
        $test_series_details = $this->_getTableDetails($this->conn, 'test_series', $where);
        return $test_series_details;
    }





}