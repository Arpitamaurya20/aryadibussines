<?php
function getAllServices($conn)
{
	$response = array();
	$sql = "Select * from  services ORDER BY DisplayPriority ASC";
	$result=mysqli_query($conn,$sql);
	if($result->num_rows>0)	
	{
		while($row = $result->fetch_assoc())
		{
			extract($row);
			array_push($response,$row);
		}
	}
	return json_encode($response);
}

function getSingleService($conn,$ID)
{
	$response = array();
	$sql = "Select * from  services WHERE ID='$ID'";
	$result=mysqli_query($conn,$sql);
	if($result->num_rows>0)	
	{
		while($row = $result->fetch_assoc())
		{
			extract($row);
			array_push($response,$row);
		}
	}
	return json_encode($response);
}


function DeleteServiceData($conn,$ID,$serviceImg)
{
	$sql = "Delete from services where ID = ".$ID;
	$result_delete_data=mysqli_query($conn,$sql);
	//echo $sql;
	if(!$result_delete_data)
	{
		mysqli_error($conn,$sql);
		//echo $sql;
	}
	else
	{

		$path="../../media/Services/".$serviceImg;
    unlink($path);

		return false;
	}
}

function getAllSubServicesBySeiviceID($conn,$ID)
{
	$response = array();
	$sql = "Select * from  subservice where service_id = ".$ID;
	$result=mysqli_query($conn,$sql);
	if($result->num_rows>0)	
	{
		while($row = $result->fetch_assoc())
		{
			extract($row);
			array_push($response,$row);
		}
	}
	return json_encode($response);
}

function InsertLaundryServicesConf($conn,$data)
{
	$Service_id = cleantext($data["Service_id"]);
	$Sub_Service_id = $data["Sub_Service_id"];
    $Type_of_clothes = cleantext($data["Type_of_clothes"]);
    $Clothe_Price = cleantext($data["Clothe_Price"]);
    $CreatedBy = $data['CreatedBy'];
    $CreatedDate = date('Y-m-d');
    $CreatedTime = date('H:i:s');
    $insert_query = "INSERT INTO laundry_sub_service (ServiceID,SubServiceID,TypeOfClothes,Price,CreatedBy,CreatedDate,CreatedTime ) VALUES('$Service_id','$Sub_Service_id','$Type_of_clothes','$Clothe_Price','$CreatedBy','$CreatedDate','$CreatedTime')";
    $response = _InsertTableRecords($conn, $insert_query);
    $response['message'] = "Luandry Service Added to the System";
	$response['error'] = false;
    
    return $response;
}

function UpdateLaundryServicesConf($conn,$data)
{	
	$Type_of_clothes = $data["Type_of_clothes"];
	$Clothe_Price = $data["Clothe_Price"];
    $laundry_form_id = $data['laundry_form_id'];
    $CreatedDate = date('Y-m-d');
    $CreatedTime = date('H:i:s');
    $old_laundry_details = GetLaundryServiceConfDetailsbyID($conn,$laundry_form_id);
    if($Type_of_clothes == $old_laundry_details['TypeOfClothes']  && $Clothe_Price == $old_laundry_details['Price'])
    {
    	$response['message'] = "No changes to update";
    	$response['error'] = true;
    }
    else
    {
    	$update_param = " TypeOfClothes = '$Type_of_clothes', Price = '$Clothe_Price' where ID=$laundry_form_id";
    	$response = _UpdateTableRecords($conn,'laundry_sub_service', $update_param);
		$response['message'] = "Laundry Configuration Data update";
    	
    }
    return $response;
}

function getAllLuandryServices($conn,$ID)
{
	$where = " where ServiceID = $ID AND IsActive = 1";
	$response = _getTableRecords($conn,'laundry_sub_service', $where);
	return $response;
}


function DeleteLaundryServiceConfiguration($conn,$data)
{
	// Get Corporate Details
	$ID = $data['ID'];
	$query = " IsActive = 0 where ID = $ID";
	$response = _UpdateTableRecords($conn,'laundry_sub_service', $query);
	return $response;
}

function GetLaundryServiceConfDetailsbyID($conn,$ID)
{
	$where = " where ID = $ID";
	$laundry_sub_service_details = _getTableDetails($conn,'laundry_sub_service', $where);
	return $laundry_sub_service_details;
}

function DeleteSubSeviceRateCard($conn,$data)
{
	// Get Corporate Details
	$ID = $data['ID'];
	$query = " sub_services_pdf = '' where ID = $ID";
	$response = _UpdateTableRecords($conn,'subservice', $query);
	return $response;
}


function getHomeServiceCategory($conn)
{
    $response = array();

    $sql = "SELECT * FROM services ORDER BY DisplayPriority ASC";
    $result = mysqli_query($conn, $sql);

    if ($result && $result->num_rows > 0)
    {
        while ($row = $result->fetch_assoc())
        {
            $response[] = $row;
        }
    }

    return $response; // ✅ RETURN ARRAY
}

function GetAllElectricalSubserviceDetails($conn)
{
    $response = array();
    $sql = "SELECT * FROM subservice Where service_id=12 ORDER BY ID ASC";
    $result = mysqli_query($conn, $sql);

    if ($result && $result->num_rows > 0)
    {
        while ($row = $result->fetch_assoc())
        {
            $response[] = $row;
        }
    }

    return $response; // ✅ RETURN ARRAY
}


/**
 * Get all subservices for a given service name (category)
 */
function GetSubservicesByCategory($conn, $categoryName)
{
    $response = [];

    // Sanitize input
    $categoryName = mysqli_real_escape_string($conn, $categoryName);

    // Get the service_id from service table
    $sqlService = "SELECT ID FROM services WHERE Name='$categoryName' LIMIT 1";
    $resultService = mysqli_query($conn, $sqlService);

    if ($resultService && $resultService->num_rows > 0) {
        $serviceRow = $resultService->fetch_assoc();
        $serviceId = $serviceRow['ID'];

        // Fetch subservices
        $sqlSub = "SELECT `ID`, `service_id`, `title`, `sub_service_image`, `sub_service_price`, 
                          `hourlyservice`, `exclusive_service`, `inclusive_service`, 
                          `sub_services_pdf`, `Customization` 
                   FROM `subservice` 
                   WHERE `service_id` = $serviceId 
                   ORDER BY `ID` ASC";

        $resultSub = mysqli_query($conn, $sqlSub);
        if ($resultSub && $resultSub->num_rows > 0) {
            while ($row = $resultSub->fetch_assoc()) {
                $response[] = $row;
            }
        }
    }

    return $response; // Returns array of subservices
}


function GetAllAirConditionerAccessories($conn)
{
    $response = array();
    $sql = "SELECT * FROM subservice Where service_id=1 ORDER BY ID ASC";
    $result = mysqli_query($conn, $sql);

    if ($result && $result->num_rows > 0)
    {
        while ($row = $result->fetch_assoc())
        {
            $response[] = $row;
        }
    }

    return $response; // ✅ RETURN ARRAY
}

function GetAllServiceCategories($conn)
{
    $data = [];
    $sql = "SELECT ID, Name, service_img, mainheading 
            FROM services 
            WHERE status = '1' 
            ORDER BY DisplayPriority ASC";

    $result = mysqli_query($conn, $sql);
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $data[] = $row;
        }
    }
    return $data;
}


function GetSubservicesByServiceId($conn, $service_id, $limit = 8)
{
    $data = [];
    $sql = "SELECT service_id,  title, sub_service_image, sub_service_price 
            FROM subservice 
            WHERE service_id = '$service_id'
            ORDER BY ID DESC
            LIMIT $limit";

    $result = mysqli_query($conn, $sql);
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $data[] = $row;
        }
    }
    return $data;
}

function GetSubservicesByServiceIdComplete(mysqli $conn, int $service_id): array
{
    $data = [];

    $sql = "
        SELECT 
            ss.ID AS sub_id,
            ss.service_id,
            ss.title AS service_name,
            ss.sub_service_image,
            CAST(ss.sub_service_price AS DECIMAL(10,2)) AS price,
            ss.hourlyservice,
            ss.exclusive_service,
            ss.inclusive_service,
            s.Name AS main_service_name,
            s.mainheading,
            s.service_img AS service_img
        FROM subservice ss
        INNER JOIN services s ON ss.service_id = s.ID
        WHERE ss.service_id = ?
        ORDER BY ss.ID ASC
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $service_id);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {

        $data[] = [
            'id'            => (int)$row['sub_id'],
            'name'          => $row['service_name'],
            'price'         => (float)$row['price'], // ✅ FIX number_format error
            'original_price'=> null,
            'rating'        => 4.5,
            'reviews'       => rand(10, 200),
            'image'         => !empty($row['sub_service_image'])
                                ? "./admin/media/Services/".$row['sub_service_image']
                                : "assets/default-service.jpg",
            'includes'      => array_filter(explode(',', $row['inclusive_service'])),
            'excludes'      => array_filter(explode(',', $row['exclusive_service'])),

            // category-level info
            'main_service_name' => $row['main_service_name'],
            'mainheading'       => $row['mainheading'],
            'service_img'       => $row['service_img']
        ];
    }

    return $data;
}


function getValidCoupons($conn)
{
    $coupons = [];

    $sql = "
        SELECT CouponName, Discount, Type 
        FROM coupons_code 
        WHERE isActive = 1
    ";

    $result = mysqli_query($conn, $sql);

    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $coupons[$row['CouponName']] = [
                'discount' => (float)$row['Discount'],
                'type'     => strtolower($row['Type'])
            ];
        }
    }

    return $coupons;
}

