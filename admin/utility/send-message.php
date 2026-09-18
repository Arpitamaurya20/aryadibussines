<?php

include('../controllers/common_controllers.php');
$conn = _connectodb();
setTimeZone();
$CreatedDate = date('Y-m-d');
$CreatedTime = date('H:i:s');

$dir = fopen("marketing_users.csv", "r");
$k=0;
while (($data = fgetcsv($dir, 1000, ",")) !== FALSE) 
{
  if($k==0)
  {
    $k++;
    continue;
  }
  $to_be_inserted = true;
  $Name = cleantext($data[1]);
  $Address = cleantext($data[2]);
  $PhoneNumber = cleantext($data[3]);
  
  $not_duplicate = true;



  $sql = "INSERT INTO marketing_users (Name,Address,PhoneNumber) VALUES('$Name','$Address','$PhoneNumber')";
    $response = _InsertTableRecords($conn, $sql);
  // echo $sql."<br>";


    $message = "Dear $Name,

As the summer season sets in, it's essential to keep your air conditioning unit functioning correctly to keep you comfortable. 

We are offering AC servicing for all our customers to help you beat the heat this summer. Our team of professionals will provide a thorough cleaning and maintenance of your AC unit, ensuring it runs efficiently and effectively starting from Rs. 299 only 🤩

Checkout our AC service here : http://surl.li/gxdme

To schedule your AC servicing appointment,

📲 Or Toll free no- 18001201343

🌐 Our Website - www.techxpertindia.in

👉 Techxpert App : https://play.google.com/store/apps/details?id=io.ionic.techXpert

Don't wait until the heat becomes unbearable, book your appointment today and stay cool all summer long!

Regards,
TechXpert Team.";
    $phonenumber = "+91".$PhoneNumber;

    sendWhatsAppMessage($phonenumber,$message);


  }

// }

fclose($dir);
//mysqli_close($connection);


?>

