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

    $sql = "SELECT * FROM services Where status=1 ORDER BY DisplayPriority ASC";
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

/**
 * Search services and subservices by keyword
 * Searches across multiple columns in both tables
 */
function SearchServicesAndSubservices($conn, $keyword)
{
    $response = [];
    
    // Sanitize keyword
    $keyword = mysqli_real_escape_string($conn, $keyword);
    $searchTerm = "%{$keyword}%";
    
    // Search in services table
    $sqlServices = "SELECT 
        s.ID, 
        s.Name, 
        s.service_img, 
        s.mainheading,
        s.MetaDescription,
        s.MetaTitle,
        s.MetaKeyword,
        s.brand_heading,
        s.subservice_heading,
        'service' as result_type
    FROM services s
    WHERE s.status = 1 
    AND (
        s.Name LIKE ? 
        OR s.mainheading LIKE ? 
        OR s.MetaDescription LIKE ? 
        OR s.MetaTitle LIKE ? 
        OR s.MetaKeyword LIKE ? 
        OR s.brand_heading LIKE ? 
        OR s.subservice_heading LIKE ?
    )
    ORDER BY s.DisplayPriority ASC";
    
    $stmtServices = $conn->prepare($sqlServices);
    if ($stmtServices) {
        $stmtServices->bind_param("sssssss", $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm);
        $stmtServices->execute();
        $resultServices = $stmtServices->get_result();
        
        while ($row = $resultServices->fetch_assoc()) {
            $response[] = [
                'id' => $row['ID'],
                'title' => $row['Name'],
                'image' => $row['service_img'],
                'price' => null,
                'mainheading' => $row['mainheading'],
                'type' => 'service',
                'service_id' => $row['ID']
            ];
        }
        $stmtServices->close();
    }
    
    // Search in subservice table
    $sqlSubservices = "SELECT 
        ss.ID,
        ss.service_id,
        ss.title,
        ss.sub_service_image,
        ss.sub_service_price,
        ss.hourlyservice,
        ss.Customization,
        s.Name as service_name
    FROM subservice ss
    INNER JOIN services s ON ss.service_id = s.ID
    WHERE s.status = 1
    AND (
        ss.title LIKE ? 
        OR s.Name LIKE ?
    )
    ORDER BY ss.ID ASC";
    
    $stmtSubservices = $conn->prepare($sqlSubservices);
    if ($stmtSubservices) {
        $stmtSubservices->bind_param("ss", $searchTerm, $searchTerm);
        $stmtSubservices->execute();
        $resultSubservices = $stmtSubservices->get_result();
        
        while ($row = $resultSubservices->fetch_assoc()) {
            $response[] = [
                'id' => $row['ID'],
                'title' => $row['title'],
                'image' => $row['sub_service_image'],
                'price' => $row['sub_service_price'],
                'mainheading' => $row['service_name'],
                'type' => 'subservice',
                'service_id' => $row['service_id']
            ];
        }
        $stmtSubservices->close();
    }
    
    return $response;
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