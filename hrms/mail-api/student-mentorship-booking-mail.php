<?php
include("include/mail-header.php");
// $myfile = fopen("newlog.txt", "a") or die("Unable to open file!");
// // $email_action = 0;
// function WriteLog($txt)
// {
// 	global $myfile;
// 	fwrite($myfile, "\n". $txt);
// }
// WriteLog($data_raw);
$Name= $data['name'];
$Number = $data['number'];
$Email= $data['email'];
$Session= $data['session'];
$Subject= 'N/A';
if(isset($data['subject'])){
    $Subject= $data['subject'];
}
$AttemptUPSC = $data['attemptUPSC'];
$Attemptyear= $data['attemptyear'];
$Previouscoaching= $data['previouscoaching'];
$Sessiondate= $data['sessiondate'];
$Sessiontime = $data['sessiontime'];

$Link = "$_WebsiteLink/admission/fee-reciept?AdmissionID=$Student_encode&FeeID=$Student_ID";



// $Email= 'manish@garyglobalsolutions.com';
// $Link = 'test';


	
$mail->Subject  = "Mentorship Booking - $_MailName!";

$mail->Body = "<div style='background-color:#fbfbfc;margin:0'>
        <font color='#888888'>

            </font><font color='#888888'>

        </font><font color='#888888'>

        </font><table style='font-family:'open sans','arial','verdana',sans-serif;font-size:14px;color:#444e61;width:98%;max-width:600px;float:none;margin:0 auto' border='0' cellpadding='0' cellspacing='0' valign='top' align='center'>

        <tbody>

            <tr>

            <td height='24' style='line-height:1px;font-size:1px'>&nbsp;</td>

            </tr>

            <tr align='middle'>

            <td>

            <a href='$_WebsiteLink'>

            <img src='https://praajasv.digitalworkdesk.com/project-assets/images/logo.png' height='100' class='CToWUd' data-bit='iit'>

            </a>

            </td>

            </tr>

            <tr>

            <td height='24' style='line-height:1px;font-size:1px'>&nbsp;</td>

            </tr>

            <tr bgcolor='#ffffff'>

            <td align='center'>

                <table bgcolor='#ffffff' style='width:100%;line-height:20px;border:1px solid #f4f5f6;border-radius:5px' cellpadding='0'>

                <tbody>

                    <tr>

                    <td>

                        <table style='width:92%;margin:0 auto' align='center'>

                        <tbody>

                            <tr>

                            <td height='32' style='line-height:1px;font-size:1px'>&nbsp;</td>

                            </tr>

                            <tr>

                            <td style='color:#20252e;font-size:26px;line-height:26px;font-family:'panton','open sans','arial','verdana',sans-serif;letter-spacing:0.5px;font-weight:700'>Hello,</td>

                            </tr>

                            <tr>

                            <td height='20' style='line-height:1px;font-size:1px'>&nbsp;</td>

                            </tr>

                            <tr>

                            <td style='vertical-align:bottom'><span style='font-family:'open sans','arial','verdana',sans-serif;font-size:16px;color:#444e61'><b>Thank You</b> <br> <br>Your Mentorship Session has been Recieved. If you have any query related the Booking details Please let us know -

                            </span></td>

                            </tr>

                            <br>

                            <tr>

                            <td style='vertical-align:bottom'><span style='font-family:'open sans','arial','verdana',sans-serif;font-size:16px;color:#444e61'>Name: $Name

                            </span></td>

                            </tr>
                            <tr>

                            <td style='vertical-align:bottom'><span style='font-family:'open sans','arial','verdana',sans-serif;font-size:16px;color:#444e61'>Email: $Email

                            </span></td>

                            </tr>
                            <tr>

                            <td style='vertical-align:bottom'><span style='font-family:'open sans','arial','verdana',sans-serif;font-size:16px;color:#444e61'>Session: $Session

                            </span></td>

                            </tr>

                            <tr>

                            <td style='vertical-align:bottom'><span style='font-family:'open sans','arial','verdana',sans-serif;font-size:16px;color:#444e61'>Subject: $Subject

                            </span></td>

                            </tr>

                            <tr>

                            <td style='vertical-align:bottom'><span style='font-family:'open sans','arial','verdana',sans-serif;font-size:16px;color:#444e61'>Attempt UPSC: $AttemptUPSC

                            </span></td>

                            </tr>

                            <tr>

                            <td style='vertical-align:bottom'><span style='font-family:'open sans','arial','verdana',sans-serif;font-size:16px;color:#444e61'>Attempt Year: $Attemptyear

                            </span></td>

                            </tr>

                            <tr>

                            <td style='vertical-align:bottom'><span style='font-family:'open sans','arial','verdana',sans-serif;font-size:16px;color:#444e61'>Previous Coaching: $Previouscoaching

                            </span></td>

                            </tr>

                            <tr>

                            <td style='vertical-align:bottom'><span style='font-family:'open sans','arial','verdana',sans-serif;font-size:16px;color:#444e61'>Session Date: $Sessiondate

                            </span></td>

                            </tr>

                            <tr>

                            <td style='vertical-align:bottom'><span style='font-family:'open sans','arial','verdana',sans-serif;font-size:16px;color:#444e61'>Session Time: $Sessiontime

                            </span></td>

                            </tr>

                            <tr>

                            <td height='18' style='line-height:1px;font-size:1px'>&nbsp;</td>

                            </tr>

                            

                            

                            <tr>

                            <td><span style='font-family:'open sans','arial','verdana',sans-serif;font-size:16px;color:#444e61'>Best regards,</span></td>

                            </tr>

                            <tr>

                            <td><span style='font-family:'open sans','arial','verdana',sans-serif;font-size:18px;color:#20252e;font-weight:bold'>$_MailSignature</span></td>

                            </tr>

                            

                            

                            <tr>

                            <td height='32' style='line-height:1px;font-size:1px'>&nbsp;</td>

                            </tr>

                        </tbody>

                        </table></td>

                    </tr>

                </tbody>

                </table></td>

            </tr>

            <tr>

            <td height='24' style='line-height:1px;font-size:1px'>&nbsp;</td>

            </tr>

            <tr>

            <td style='font-family:'open sans','arial',sans-serif;font-size:12px;line-height:130%;color:#9f9f9f;text-align:center' align='center'>© $currentYear $_MailName.&nbsp;All rights reserved. <br><a href='$_WebsiteLink' title='$_MailName' style='color:#9f9f9f;font-family:'open sans','arial','verdana',sans-serif' target='_blank' >$_DomainName</a></td>

            </tr>

            <tr>

            <td style='font-size:12px;color:#d0d3d6;text-align:center'>___</td>

            </tr>

            <tr>

            <td style='font-size:12px;padding:15px;color:#9f9f9f;text-align:center'>If you did not make this request, please contact us by replying to this email.</td></tr></tbody></table><font color='#888888'>

        </font></div>";
         $mail->addAddress($Email);
        // $mail->addBCC("manishsharma.gary@gmail.com");
     


if(!$mail->send())
{
	// echo "error";
}
else
{
	// echo "Mail Sent";
}
?>