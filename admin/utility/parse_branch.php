<?php

include('../controllers/common_controllers.php');
$conn = _connectodb();
$where = "Where IsActive=1";
$Company_array = array();
$city_array = array();
$state_array = array();
setTimeZone();
$CreatedDate = date('Y-m-d');
$CreatedTime = date('H:i:s');

$Company_temp_array = _getTableRecords($conn, 'company', $where);
 foreach ($Company_temp_array as $Companydata) {
    $company_name = $Companydata['CompanyName'];
    $Company_array[$company_name]['ID'] = $Companydata['ID'];
 }


$dir = fopen("Urban-Branch-Admin.csv", "r");
$k=0;
while (($data = fgetcsv($dir, 1000, ",")) !== FALSE) 
{
  if($k==0)
  {
    $k++;
    continue;
  }
  $to_be_inserted = true;
  $CompanyName = cleantext($data[0]);
  $BranchName = cleantext($data[1]);
  $BranchCode = cleantext($data[2]);
  $ContactEmail = cleantext($data[3]);
  $MobileNumber = cleantext($data[4]);
  $LandlineNumber = cleantext($data[5]);
  $PostalCode = cleantext($data[6]);
  $CityName = $data[7];
  // echo $CityName;
  // die();
  $StateName = $data[8];
  $AddressLine1 = cleantext($data[9]);
  $AddressLine2 = cleantext($data[10]);
  $SiteIncharge = cleantext($data[11]);
  $Username = cleantext($data[12]);
  $Password = cleantext($data[13]);
  
  $CompanyID ='';

  if(isset($Company_array[$CompanyName]['ID']))
  {
    $CompanyID = $Company_array[$CompanyName]['ID'];
    // echo $CompanyID."<br>";
  }
  else
  {

    $to_be_inserted = false;
  }




  $not_duplicate = true;

  if($Username != ""){
  $where = " where UserName = '$Username'";
  $not_duplicate = check_unique_identity_filter($conn,'users',$where);
  }

  if($not_duplicate)
  {


  $sql = "INSERT INTO branch (CompanyID,BranchSite,BranchEmail,BranchMobile,BranchLandline,BranchAddress1,BranchAddress2,BranchCity,BranchState,BranchPostalCode,SiteIncharge,BranchCode,CreatedBy,CreatedDate,CreatedTime ) VALUES('$CompanyID','$BranchName','$ContactEmail','$MobileNumber','$LandlineNumber','$AddressLine1','$AddressLine2','$CityName','$StateName','$PostalCode','$SiteIncharge','$BranchCode','System','$CreatedDate','$CreatedTime')";
    $response = _InsertTableRecords($conn, $sql);
  // echo $sql."<br>";


    $BranchID = $response['last_insert_id'];
    if($Username != "")
    {
      $branch_password = md5($Password);
      $user_type = "Corporate Branch User";
      $emp_Id = "-1";

      $add_branch_user = "INSERT INTO users (UserName,Password,UserType,EmployeeID,CorporateID,BranchID,CreatedDate,CreatedTime) VALUES('$Username','$branch_password','$user_type','$emp_Id','$CompanyID','$BranchID','$CreatedDate','$CreatedTime')";
     $response_add_branch_user = _InsertTableRecords($conn, $add_branch_user);
       // echo $add_branch_user."<br>";
    }

    $message = "Dear $SiteIncharge,\n\nThis is to inform you that we have just created a new branch ( $BranchName ) with following details - \n\nYour Credential:\n\nUsername - $Username\n\nPassword - $Password\n\nWarm regards,\nTechXpert Team\n\n\nApplication Link - https://play.google.com/store/apps/details?id=io.ionic.techXpert\n\nWebsite Link - https://techxpertindia.in/admin/authentication/login.php\n\n";
    $phonenumber = "+91".$MobileNumber;
    $post_mail_data['action'] = "New Branch Account Register";
    $post_mail_data['branch_username'] = $Username;
    $post_mail_data['BranchEmail'] = $ContactEmail;
    $post_mail_data['BranchSite'] = $BranchName;
    $post_mail_data['password'] = $Password;
    sendMailRequest($post_mail_data);
    sendWhatsAppMessage($phonenumber,$message);


  }

}

fclose($dir);
//mysqli_close($connection);


?>

