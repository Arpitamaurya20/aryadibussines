<?php
require_once('../../api/common_api_header.php');
require_once('../controllers/common_controllers.php');
$data_raw = file_get_contents('php://input');
$data = json_decode($data_raw,true);

require 'PHPMailer-master/src/Exception.php';
require 'PHPMailer-master/src/PHPMailer.php';
require 'PHPMailer-master/src/SMTP.php';
$data_raw = file_get_contents('php://input');

$filename = 'api.logs';

// Open the file in append mode
$file = fopen($filename, 'a');

// Write the content to the file
fwrite($file, $data_raw);

// Close the file
fclose($file);


$data = json_decode($data_raw,true);

require ('include/mail-config.php');
require ('include/common-mail-design.php');

if($data['action'] == "New Corporate Account Register")
{
	$admin_usename = $data['admin_username'];
	$admin_password = $data['password'];
	$CompanyName = $data['CompanyName'];
	$CompanyEmail = $data['CompanyEmail'];

	$mail->Subject  = 'Welcome to Aryadibusiness - Save your credentials';
	$body_middle = "<tr>
					  <td style='color:#20252e;font-size:26px;line-height:26px;font-family:'panton','open sans','arial','verdana',sans-serif;letter-spacing:0.5px;font-weight:700'>Hi,</td>
					</tr>
					<tr>
					  <td height='20' style='line-height:1px;font-size:1px'>&nbsp;</td>
					</tr>
					<tr>
					  <td style='vertical-align:bottom'><span style='font-family:'open sans','arial','verdana',sans-serif;font-size:16px;color:#444e61'>A new company account is registered. Here are the details to login into app and portal:
						<br>
						<br>
						Company Account Name: ".$CompanyName." <br>
						Username: ".$admin_usename." <br>
						Password: ".$admin_password." <br>
						</td>
					</tr>
					<tr>
					  <td height='24' style='line-height:1px;font-size:1px'>&nbsp;</td>
					</tr>
					
					<tr>
					  <td><span style='font-family:'open sans','arial','verdana',sans-serif;font-size:16px;color:#444e61'>In case of any issues please let us know</td>
					</tr>
					<tr>
					  <td><span style='font-family:'open sans','arial','verdana',sans-serif;font-size:16px;color:#444e61'>App link - https://play.google.com/store/apps/details?id=io.ionic.techXpert</td>
					</tr>
					<tr>
					  <td><span style='font-family:'open sans','arial','verdana',sans-serif;font-size:16px;color:#444e61'>Website Link - https://techxpertindia.in/admin/authentication/login</td>
					</tr>
					<br>
					";

	$mail->Body = $mail_header.$body_middle.$mail_footer;
	$mail->addAddress($CompanyEmail);
	//$mail->addBCC("pgfiry@gmail.com");
}

if($data['action'] == "New Branch Account Register")
{
	$barnch_username = $data['branch_username'];
	$barnch_password = $data['password'];
	$BranchName = $data['BranchSite'];
	$BranchEmail = $data['BranchEmail'];

	$mail->Subject  = 'Welcome to Aryadibusiness - Save your credentials';
	$body_middle = "<tr>
					  <td style='color:#20252e;font-size:26px;line-height:26px;font-family:'panton','open sans','arial','verdana',sans-serif;letter-spacing:0.5px;font-weight:700'>Hi,</td>
					</tr>
					<tr>
					  <td height='20' style='line-height:1px;font-size:1px'>&nbsp;</td>
					</tr>
					<tr>
					  <td style='vertical-align:bottom'><span style='font-family:'open sans','arial','verdana',sans-serif;font-size:16px;color:#444e61'>A new branch account is registered. Here are the details to login into app and portal:
						<br>
						<br>
						Branch Account Name: ".$BranchName." <br>
						Username: ".$barnch_username." <br>
						Password: ".$barnch_password." <br>
						</td>
					</tr>
					<tr>
					  <td height='24' style='line-height:1px;font-size:1px'>&nbsp;</td>
					</tr>
					
					<tr>
					  <td><span style='font-family:'open sans','arial','verdana',sans-serif;font-size:16px;color:#444e61'>In case of any issues please let us know</td>
					</tr>
					<tr>
					  <td><span style='font-family:'open sans','arial','verdana',sans-serif;font-size:16px;color:#444e61'>App link - https://play.google.com/store/apps/details?id=io.ionic.techXpert</td>
					</tr>
					<tr>
					  <td><span style='font-family:'open sans','arial','verdana',sans-serif;font-size:16px;color:#444e61'>Website Link - https://techxpertindia.in/admin/authentication/login</td>
					</tr>
					<br>
					";

	$mail->Body = $mail_header.$body_middle.$mail_footer;
	$mail->addAddress($BranchEmail);
	//$mail->addBCC("pgfiry@gmail.com");
}


if($data['action'] == "Marketing Message")
{
	$Name = $data['Name'];
	$Email = $data['Email'];

	$mail->Subject  = 'Welcome to Aryadibusiness - Marketing Message';
	$body_middle = "<tr>
					  <td style='color:#20252e;font-size:26px;line-height:26px;font-family:'panton','open sans','arial','verdana',sans-serif;letter-spacing:0.5px;font-weight:700'>Hi $Name,</td>
					</tr>
					<tr>
					  <td height='20' style='line-height:1px;font-size:1px'>&nbsp;</td>
					</tr>
					<tr>
					  <td style='vertical-align:bottom'><span style='font-family:'open sans','arial','verdana',sans-serif;font-size:16px;color:#444e61'>At TechXpert, we understand that your home or business is your sanctuary, and you need reliable and efficient services to keep it running smoothly. That's why we offer a wide range of top-quality services to help you take care of all your home and corporate needs.
						<br>
						<br>
						Our team of experts is dedicated to providing you with the best customer experience possible, from prompt response times to personalized solutions tailored to your specific needs. Whether you need help with home care, AC repairing, CCTV installation, electrician services, pest control, disinfection, app and software development, or laptop repair, we've got you covered.
						<br>
						<br>
						At Aryadibusiness, we believe that our success is measured by our customers' satisfaction, and we are committed to exceeding your expectations every time. Our team is equipped with the latest tools and technologies to provide you with fast, reliable, and affordable services, so you can focus on the things that matter most to you.
						<br>
						<br>
						So why choose Aryadibusiness? We are committed to providing you with:
						</td>
					</tr>
					<tr>
					  <td height='6' style='line-height:1px;font-size:1px'>&nbsp;</td>
					</tr>
					
					<tr>
					  <td>
						  <ul style='margin-bottom:0px;'>
	                         <li>Prompt and reliable services</li>
	                         <li>Skilled and experienced professionals</li>
	                         <li>Competitive pricing</li>
	                         <li>Flexible scheduling options</li>
	                         <li>Personalized solutions tailored to your needs</li>
	                      </ul>
                      </td>
					</tr>

					<tr>
					  <td>
					  <br>
						  Contact us today to schedule a service appointment or learn more about how we can help you take care of all your home and corporate needs. We look forward to serving you soon.
                      </td>
					</tr>
					";

	$mail->Body = $mail_header.$body_middle.$mail_footer;
	$mail->addAddress($Email);
	// $mail->addBCC("pgfiry@gmail.com");
	//$mail->addBCC("manishsharma.gary@gmail.com");
}

if($data['action'] == "New Home Care Booking")
{
	$Name = $data['Name'];
	$Email = $data['Email'];
	$PhoneNumber = $data['Phone'];
	$Services = $data['Service_name'];

	$mail->Subject  = 'Welcome to Aryadibusiness - HomeCare Booking';
	$body_middle = "<tr>
					  <td style='color:#20252e;font-size:26px;line-height:26px;font-family:'panton','open sans','arial','verdana',sans-serif;letter-spacing:0.5px;font-weight:700'>Hi,</td>
					</tr>
					<tr>
					  <td height='20' style='line-height:1px;font-size:1px'>&nbsp;</td>
					</tr>
					<tr>
					  <td style='vertical-align:bottom'><span style='font-family:'open sans','arial','verdana',sans-serif;font-size:16px;color:#444e61'>
					      New Home Care Booking:
						<br>
						<br>
						  Name: $Name
						<br>
						<br>
						  Phone Number: $PhoneNumber
						<br>
						<br>
						  Service : $Services
						<br>
						
						</td>
					</tr>
					<tr>
					  <td height='6' style='line-height:1px;font-size:1px'>&nbsp;</td>
					</tr>
					";

	$mail->Body = $mail_header.$body_middle.$mail_footer;
	// $mail->addAddress("manishsharma.gary@gmail.com");
	//$mail->addBCC("pgfiry@gmail.com");
	//$mail->addBCC("manishsharma.gary@gmail.com");
}

if($data['action'] == "Employee Access")
{
	$admin_usename = $data['username'];
	$admin_password = $data['password'];
	$Employee_Name = $data['Name'];
	$Employee_Email = $data['Email'];
	$mail->Subject  = 'Welcome to Aryadibusiness';
	$body_middle = "<tr>
					  <td style='color:#20252e;font-size:26px;line-height:26px;font-family:'panton','open sans','arial','verdana',sans-serif;letter-spacing:0.5px;font-weight:700'>Hi,</td>
					</tr>
					<tr>
					  <td height='20' style='line-height:1px;font-size:1px'>&nbsp;</td>
					</tr>
					<tr>
					  <td style='vertical-align:bottom'><span style='font-family:'open sans','arial','verdana',sans-serif;font-size:16px;color:#444e61'>A new  Employee is registered. Here are the details to login into app and portal:
						<br>
						<br>
						Employee Name: ".$Employee_Name." <br>
						Username: ".$admin_usename." <br>
						Password: ".$admin_password." <br>
						</td>
					</tr>
					<tr>
					  <td height='6' style='line-height:1px;font-size:1px'>&nbsp;</td>
					</tr>
					";

	$mail->Body = $mail_header.$body_middle.$mail_footer;
	$mail->addAddress($Employee_Email);
	//$mail->addBCC("pgfiry@gmail.com");
	//$mail->addBCC("manishsharma.gary@gmail.com");
	//$mail->addBCC("akashgupta.gary@gmail.com");
}

if($data['action'] == "Employee Leave")
{
	$EmployeeName = $data['Name'];
	$SupervisorEmail = $data['Email'];
	$EmployeeNumber = $data['number'];
	$FromDate = $data['FromDate'];
	$ToDate = $data['ToDate'];
	$ApproveLink = "https://techxpertindia.in/admin/authentication/login";
	$mail->Subject  = 'Application for Casual Leave';
	$body_middle = "<tr>
					  <td style='color:#20252e;font-size:26px;line-height:26px;font-family:'panton','open sans','arial','verdana',sans-serif;letter-spacing:0.5px;font-weight:700'>Hi Sir,</td>
					</tr>
					<tr>
					  <td height='20' style='line-height:1px;font-size:1px'>&nbsp;</td>
					</tr>
					<tr>
					  <td style='vertical-align:bottom'><span style='font-family:'open sans','arial','verdana',sans-serif;font-size:16px;color:#444e61'>I am writing to you to let you know that I have an important personal matter to attend at my hometown due to which I will not be able to come to the office from $FromDate to $ToDate.
						<br>
						I shall be reachable on my mobile number $EmployeeNumber during the period.
						<br>
						<br>
						I will be thankful to you for considering my application.
						</td>
					</tr>
					<tr>
					  <td height='6' style='line-height:1px;font-size:1px'>&nbsp;</td>
					</tr>
					
					<tr>
					  <td>
						 Yours Sincerely,
                      </td>
                      <td>
						 $EmployeeName
                      </td>
                      <td>
						 Warm regards,
                      </td>
                      <td>
						 Aryadibusiness Team
                      </td>
					</tr>
					<tr>
					  <td>
					  <br>
						 Website Link - $ApproveLink;
                      </td>
					</tr>
					";

	$mail->Body = $mail_header.$body_middle.$mail_footer;
	$mail->addAddress($SupervisorEmail);
	// $mail->addBCC("pgfiry@gmail.com");
	//$mail->addBCC("akashgupta.gary@gmail.com");
}


if($data['action'] == "Branch ARC Order")
{
	$Order_Code = $data['Order_Code'];
	$BranchSiteIncharge = $data['Branch_Site_Incharge'];
	$BranchEmail = $data['Branch_Email'];

	$mail->Subject  = 'Welcome to Aryadibusiness- ARC Order';
	$body_middle = "<tr>
					  <td style='color:#20252e;font-size:26px;line-height:26px;font-family:'panton','open sans','arial','verdana',sans-serif;letter-spacing:0.5px;font-weight:700'>Hi $BranchSiteIncharge,</td>
					</tr>
					<tr>
					  <td height='20' style='line-height:1px;font-size:1px'>&nbsp;</td>
					</tr>
					<tr>
					  <td style='vertical-align:bottom'><span style='font-family:'open sans','arial','verdana',sans-serif;font-size:16px;color:#444e61'>This is to inform you that your items has been successfully Ordered.. Your Order ID is $Order_Code.
						</td>
					</tr>
					<tr>
					  <td height='24' style='line-height:1px;font-size:1px'>&nbsp;</td>
					</tr>
					
					<tr>
					  <td><span style='font-family:'open sans','arial','verdana',sans-serif;font-size:16px;color:#444e61'>In case of any issues please let us know</td>
					</tr>
					<tr>
					  <td><span style='font-family:'open sans','arial','verdana',sans-serif;font-size:16px;color:#444e61'>App link - https://play.google.com/store/apps/details?id=io.ionic.techXpert</td>
					</tr>
					<tr>
					  <td><span style='font-family:'open sans','arial','verdana',sans-serif;font-size:16px;color:#444e61'>Website Link - https://techxpertindia.in/admin/authentication/login</td>
					</tr>
					<br>
					";

	$mail->Body = $mail_header.$body_middle.$mail_footer;
	$mail->addAddress($BranchEmail);
}


if($data['action'] == "Aryadibusiness ARC Order")
{
	$Order_Code = $data['Order_Code'];
	$BranchSiteIncharge = $data['Branch_Site_Incharge'];
	$BranchSite = $data['Branch_Site'];
	$BranchCode = $data['Branch_Code'];
	$BranchPhone = $data['Branch_Phone'];
	$CityName = $data['City_Name'];
	$BranchState = $data['Branch_State'];

	$mail->Subject  = 'Welcome to Aryadibusiness - ARC Order';
	$body_middle = "<tr>
					  <td style='color:#20252e;font-size:26px;line-height:26px;font-family:'panton','open sans','arial','verdana',sans-serif;letter-spacing:0.5px;font-weight:700'>Hi,</td>
					</tr>
					<tr>
					  <td height='20' style='line-height:1px;font-size:1px'>&nbsp;</td>
					</tr>
					<tr>
					  <td style='vertical-align:bottom'><span style='font-family:'open sans','arial','verdana',sans-serif;font-size:16px;color:#444e61'>This is to inform you that we have been recieved order from $BranchSiteIncharge ( $BranchSite ). The details are bellow:
						<br>
						<br>
						Branch Name / Code: ".$BranchSite." / ".$BranchCode." <br>
						Branch City / State: ".$CityName." / ".$BranchState." <br>
						OrderID: ".$Order_Code." <br>
						Branch Site Incharge: ".$BranchSiteIncharge." <br>
						Branch Phone: ".$BranchPhone." <br>
						</td>
					</tr>
					<tr>
					  <td height='24' style='line-height:1px;font-size:1px'>&nbsp;</td>
					</tr>
					
					<tr>
					  <td><span style='font-family:'open sans','arial','verdana',sans-serif;font-size:16px;color:#444e61'>In case of any issues please let us know</td>
					</tr>
					<tr>
					  <td><span style='font-family:'open sans','arial','verdana',sans-serif;font-size:16px;color:#444e61'>App link - https://play.google.com/store/apps/details?id=io.ionic.techXpert</td>
					</tr>
					<tr>
					  <td><span style='font-family:'open sans','arial','verdana',sans-serif;font-size:16px;color:#444e61'>Website Link - https://techxpertindia.in/admin/authentication/login</td>
					</tr>
					<br>
					";

	$mail->Body = $mail_header.$body_middle.$mail_footer;
	// $mail->addAddress($BranchEmail);
	// $mail->addBCC("sales@techxpertindia.in");
	// $mail->addBCC("Procurement@techxpertindia.in");
	$mail->addBCC("manishsharma.gary@gmail.com");
}

if($data['action'] == "Send Quotation")
{
	// $Order_Code = $data['Order_Code'];
	// $BranchSiteIncharge = $data['Branch_Site_Incharge'];
	// $BranchSite = $data['Branch_Site'];
	// $BranchCode = $data['Branch_Code'];
	// $BranchPhone = $data['Branch_Phone'];
	// $CityName = $data['City_Name'];
	// $BranchState = $data['Branch_State'];

	

	$mail->Subject  = 'Welcome to Aryadibusiness - Quotation';
	$body_middle = "<tr>
					  <td style='color:#20252e;font-size:26px;line-height:26px;font-family:'panton','open sans','arial','verdana',sans-serif;letter-spacing:0.5px;font-weight:700'>Hi,</td>
					</tr>
					<tr>
					  <td height='20' style='line-height:1px;font-size:1px'>&nbsp;</td>
					</tr>
					<tr>
					  <td style='vertical-align:bottom'><span style='font-family:'open sans','arial','verdana',sans-serif;font-size:16px;color:#444e61'>This is to inform you that we have been recieved order from $BranchSiteIncharge ( $BranchSite ). The details are bellow:
						<br>
						<br>
						Branch Name / Code:  <br>
						Branch City / State:  <br>
						OrderID:  <br>
						Branch Site Incharge: <br>
						Branch Phone: <br>
						</td>
					</tr>
					<table class='table mt-2' style='border-collapse: collapse;'>
                    <thead>
                        <tr>
                            <th style='border: 1px solid; border-color: #090909 !important; text-align: center; padding: 9px;' class='text-center border-top-0 table-scale-border-bottom fw-700'></th>
                            <th style='border: 1px solid; border-color: #090909 !important; text-align: center; padding: 9px;' class='border-top-0 table-scale-border-bottom fw-700'>Item</th>
                            <th style='border: 1px solid; border-color: #090909 !important; text-align: center; padding: 9px;' class='border-top-0 table-scale-border-bottom fw-700'>Category</th>
                            <th style='border: 1px solid; border-color: #090909 !important; text-align: center; padding: 9px;' class='border-top-0 table-scale-border-bottom fw-700'>Make</th>
                            <th style='border: 1px solid; border-color: #090909 !important; text-align: center; padding: 9px;' class='border-top-0 table-scale-border-bottom fw-700'>HSN</th>
                            <th style='border: 1px solid; border-color: #090909 !important; text-align: center; padding: 9px;' class='border-top-0 table-scale-border-bottom fw-700'>ARC Code</th>
                            <th style='border: 1px solid; border-color: #090909 !important; text-align: center; padding: 9px;' class='text-left border-top-0 table-scale-border-bottom fw-700'>Unit Cost</th>
                            <th style='border: 1px solid; border-color: #090909 !important; text-align: center; padding: 9px;' class='text-left border-top-0 table-scale-border-bottom fw-700'>Qty</th>
                            <th style='border: 1px solid; border-color: #090909 !important; text-align: center; padding: 9px;' class='text-left border-top-0 table-scale-border-bottom fw-700'>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                            <tr>
                                <td style='border: 1px solid; border-color: #090909 !important; text-align: center; padding: 9px;' class='text-center fw-700'>
                                                                        
                                    1                                </td>
                                <td style='border: 1px solid; border-color: #090909 !important; text-align: center; padding: 9px;' class='text-left strong'>MCB || Item 160-Mcb 2Pole,32A,'C' Curve || MAKE / BRAND - HAGER - 2P, C, 32A</td>
                                <td style='border: 1px solid; border-color: #090909 !important; text-align: center; padding: 9px;' class='text-left'>Electrical</td>
                                <td style='border: 1px solid; border-color: #090909 !important; text-align: center; padding: 9px;' class='text-left'>ABB</td>
                                <td style='border: 1px solid; border-color: #090909 !important; text-align: center; padding: 9px;' style='border: 1px solid; border-color: #090909 !important; text-align: center; padding: 9px;' class='text-left'>9987</td>
                                <td style='border: 1px solid; border-color: #090909 !important; text-align: center; padding: 9px;' class='text-left'>123</td>
                                <td style='border: 1px solid; border-color: #090909 !important; text-align: center; padding: 9px;' class='text-left'>692</td>
                                <td style='border: 1px solid; border-color: #090909 !important; text-align: center; padding: 9px;' class='text-left'>1</td>
                                <td style='border: 1px solid; border-color: #090909 !important; text-align: center; padding: 9px;' class='text-left'>₹692</td>
                            </tr>
                            <tr>
                                <td style='border: 1px solid; border-color: #090909 !important; text-align: center; padding: 9px;' class='text-center fw-700'>
                                                                        
                                    1                                </td>
                                <td style='border: 1px solid; border-color: #090909 !important; text-align: center; padding: 9px;' class='text-left strong'>SERVICE CHARGE - INCITY</td>
                                <td style='border: 1px solid; border-color: #090909 !important; text-align: center; padding: 9px;' class='text-left'>Others</td>
                                <td style='border: 1px solid; border-color: #090909 !important; text-align: center; padding: 9px;' class='text-left'>NA</td>
                                <td style='border: 1px solid; border-color: #090909 !important; text-align: center; padding: 9px;' class='text-left'>9986</td>
                                <td style='border: 1px solid; border-color: #090909 !important; text-align: center; padding: 9px;' class='text-left'>124</td>
                                <td style='border: 1px solid; border-color: #090909 !important; text-align: center; padding: 9px;' class='text-left'>600</td>
                                <td style='border: 1px solid; border-color: #090909 !important; text-align: center; padding: 9px;' class='text-left'>1</td>
                                <td style='border: 1px solid; border-color: #090909 !important; text-align: center; padding: 9px;' class='text-left'>₹600</td>
                            </tr>                
                    </tbody>
                </table>
					<table class='table table-clean' style='width:100%;'>
						<tbody style='text-align: end;'>
							<tr class='table-scale-border-top border-left-0 border-right-0 border-bottom-0'>
								<td class='text-right keep-print-font'>
									<h4 class='m-0 fw-700 h2 keep-print-font'>Total - ₹11792</h4>
								</td>
							</tr>
						</tbody>
					</table>
					<tr>
					  <td height='24' style='line-height:1px;font-size:1px'>&nbsp;</td>
					</tr>
					
					<tr>
					  <td><span style='font-family:'open sans','arial','verdana',sans-serif;font-size:16px;color:#444e61'>In case of any issues please let us know</td>
					</tr>
					<tr>
					  <td><span style='font-family:'open sans','arial','verdana',sans-serif;font-size:16px;color:#444e61'>App link - https://play.google.com/store/apps/details?id=io.ionic.techXpert</td>
					</tr>
					<tr>
					  <td><span style='font-family:'open sans','arial','verdana',sans-serif;font-size:16px;color:#444e61'>Website Link - https://techxpertindia.in/admin/authentication/login</td>
					</tr>
					<br>
					";

	$mail->Body = $mail_header.$body_middle.$mail_footer;
	// $mail->addAddress($BranchEmail);
	// $mail->addBCC("sales@techxpertindia.in");
	// $mail->addBCC("Procurement@techxpertindia.in");
	$mail->addBCC("manishsharma.gary@gmail.com");
}


if(!$mail->send())
{
	echo "Error";
	//$result_array["error"] = true;
	//$result_array["message"] = "Error occured";
	//echo "There is some technical error right now. Our technical team is working on it to get the services back. Thank you for your patience.";
}
else
{
	//echo "Mail Sent";
}
?>