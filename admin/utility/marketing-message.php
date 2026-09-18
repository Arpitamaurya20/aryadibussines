<?php

include('../controllers/common_controllers.php');
$conn = _connectodb();
setTimeZone();
$CreatedDate = date('Y-m-d');
$CreatedTime = date('H:i:s');

$dir = fopen("marketing-test.csv", "r");
$k=0;
while (($data = fgetcsv($dir, 1000, ",")) !== FALSE) 
{
  if($k==0)
  {
    $k++;
    continue;
  }
  $to_be_inserted = true;
  $Name = cleantext($data[0]);
  $Address = cleantext($data[1]);
  $PhoneNumber = cleantext($data[2]);
  $UniqueID = cleantext($data[3]);
  
  $not_duplicate = true;

  if($UniqueID != ""){
  $where = " where UniqueID = '$UniqueID'";
  $not_duplicate = check_unique_identity_filter($conn,'marketing_user',$where);
  }

  if($not_duplicate)
  {


  $sql = "INSERT INTO marketing_user (Name,Address,PhoneNumber,UniqueID,CreatedBy,CreatedDate,CreatedTime ) VALUES('$Name','$Address','$PhoneNumber','$UniqueID','System','$CreatedDate','$CreatedTime')";
    $response = _InsertTableRecords($conn, $sql);
  
  // echo $sql."<br>";

    $message = "Dear $Name,\n\nAs the summer season sets in, it's essential to keep your air conditioning unit functioning correctly to keep you comfortable.\n\nWe are offering AC servicing for all our customers to help you beat the heat this summer. Our team of professionals will provide a thorough cleaning and maintenance of your AC unit, ensuring it runs efficiently and effectively starting from Rs. 299 only 🤩
     \n\nBook our AC service here : http://surl.li/gxdme\n\nTo schedule your AC servicing appointment,\n\n📲 Or Toll free no- 18001201343\n\n🌐 Our Website - www.techxpertindia.in\n\n👉 Techxpert App : https://play.google.com/store/apps/details?id=io.ionic.techXpert\n\nDon't wait until the heat becomes unbearable, book your appointment today and stay cool all summer long!\n\nRegards,\nTechXpert Team";
    $phonenumber = "+91".$PhoneNumber;
    // $post_mail_data['action'] = "Marketing Message";
    // $post_mail_data['Name'] = $Name;
    // $post_mail_data['Email'] = $Email;
    // sendMailRequest($post_mail_data);
    sendWhatsAppMessage($phonenumber,$message);


  }

}

fclose($dir);
//mysqli_close($connection);


?>

