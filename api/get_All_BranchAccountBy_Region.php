<?php
require_once('common_api_header.php');
require_once('../admin/controllers/common_controllers.php');

$data_raw = file_get_contents('php://input');
$data = json_decode($data_raw, true);

$response = array();

function _getTableDetailsRegion($conn, $where)
{
    $sql = "SELECT branch.* 
            FROM branch
            INNER JOIN company ON company.ID = branch.CompanyID
            INNER JOIN corporate ON corporate.ID = company.CorporateName $where";
    $result = mysqli_query($conn, $sql);
    $data = array();

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
    } else {
        $error = mysqli_error($conn);
        echo $sql;
        echo $error;
    }

    return $data;
}



function getRegionHedCorporateID($conn, $data) {
  $response = array();
  $RegionName = $data['RegionName'];
  $sql = "SELECT * FROM region WHERE RegionName = '$RegionName'";
  $result = mysqli_query($conn, $sql);
  if ($result) {
      if ($result->num_rows > 0) {
          while ($row = $result->fetch_assoc()) {
              $response = $row;
          }
      }
  } else {
      
  }
  return $response;
}




function getAllBranchAccountByRegion($conn, $data) {
  $filter_limit = "";
	if(isset($data['start_counter']))
	{
		$start_counter = $data['start_counter'];
		$no_of_records = $data['no_of_records'];
		$filter_limit = " LIMIT $start_counter,$no_of_records ";
	}

    $RegionName = "";
    $CorporateID = -1;
    if (isset($data['RegionName'])) {
       
        
        $regionDetails = getRegionHedCorporateID($conn, $data);
        $CorporateID = isset($regionDetails['RegionCorporateHead']) ? $regionDetails['RegionCorporateHead'] : -1;
    }
    
    if ($CorporateID != -1) {
             
                $where = "WHERE corporate.ID=$CorporateID ORDER BY ID DESC $filter_limit";
               $response['data'] = _getTableDetailsRegion($conn,$where);
                          $response['error'] = false;
                          $response['message'] = "Branch accounts fetched";
    } else {
        $response['error'] = true;
        $response['message'] = "CorporateID not found";
    }

    return $response;
}

if (isset($data['RegionName'])) {
    $conn = _connectodb();
    $response = getAllBranchAccountByRegion($conn, $data);
} else {
    $response["error"] = true;
    $response["message"] = "Missing User Fields";
}

echo json_encode($response);
?>
