<?php


require_once('common_api_header.php');


require_once('../admin/controllers/common_controllers.php');


require_once('../admin/customer/controller/customer_controller.php');


$data_raw = file_get_contents('php://input');


$data = json_decode($data_raw,true);


$response = array();


if(isset($data['subservice_id']))


{


	$conn = _connectodb();


	$subservice_id = $data['subservice_id'];


	$where = " where ID = $subservice_id";


	$path = "https://techxpertindia.in/admin/media/Services/";
	$pdfpath = "https://techxpertindia.in/admin/media/Services/Rate-PDF/";


	$SubService_detail = _getTableDetails($conn,'subservice', $where);
	$serviceID=$SubService_detail['service_id'];
    $sql="Select * from faq where service_id= $serviceID";
	$getallfaq=_getSQLRecords($conn,$sql);

    
    $SubService_detail["faqs"]=$getallfaq;

	$SubService_detail["sub_service_image"] =$path.$SubService_detail["sub_service_image"];
	$SubService_detail["sub_services_pdf_url"] = $pdfpath.$SubService_detail["sub_services_pdf"];


	$response['data'] = $SubService_detail;


	$response["error"] = false;


	$response["message"] = "Sub service details fetched";


}


else 


{


	$response["error"] = true;


	$response["message"] = "Missing User Fields";


}


echo json_encode($response);


?>