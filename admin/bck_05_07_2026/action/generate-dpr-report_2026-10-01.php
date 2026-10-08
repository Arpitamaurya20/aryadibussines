<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once('../../includes/autoloader.inc.php');
require_once '../../vendor/autoload.php'; 
include '../../controllers/common_controllers.php';

@ini_set('pcre.backtrack_limit', '10000000');
@ini_set('pcre.recursion_limit', '10000000');

function dprWriteMpdfHtmlInChunks(\Mpdf\Mpdf $mpdf, $html, $chunkSize = 400000)
{
    $html = (string) $html;
    if ($html === '') {
        return;
    }

    $length = strlen($html);
    if ($length <= $chunkSize) {
        $mpdf->WriteHTML($html);
        return;
    }

    $offset = 0;
    while ($offset < $length) {
        $remaining = $length - $offset;
        if ($remaining <= $chunkSize) {
            $mpdf->WriteHTML(substr($html, $offset));
            break;
        }

        $chunk = substr($html, $offset, $chunkSize);
        $splitAt = 0;
        foreach (array('</table>', '</div>', '</tr>', '</p>', '<pagebreak>') as $marker) {
            $pos = strrpos($chunk, $marker);
            if ($pos !== false) {
                $end = $pos + strlen($marker);
                if ($end > $splitAt) {
                    $splitAt = $end;
                }
            }
        }

        if ($splitAt <= 0) {
            $splitAt = $chunkSize;
        }

        $mpdf->WriteHTML(substr($html, $offset, $splitAt));
        $offset += $splitAt;
    }
}

function dprWriteDprReportToMpdf(\Mpdf\Mpdf $mpdf, $htmlPrefix, $progressImagesHtml, $timelineHtml, $tasksHtml, $htmlSuffix)
{
    dprWriteMpdfHtmlInChunks($mpdf, $htmlPrefix);
    dprWriteMpdfHtmlInChunks($mpdf, $progressImagesHtml);
    dprWriteMpdfHtmlInChunks($mpdf, $timelineHtml);
    dprWriteMpdfHtmlInChunks($mpdf, $tasksHtml);
    dprWriteMpdfHtmlInChunks($mpdf, $htmlSuffix);
}

// Start MPDF
$html = "Test HTML";
$mpdf = new \Mpdf\Mpdf();
$conn = _connectodb();
$core = new Core();
$progress_data = [];
$api_key = "AIzaSyDU0suOSG-X34RvDzawjDGHbX1C5JrHHsw";

$Action = trim((string) ($_POST['Action'] ?? $_GET['Action'] ?? ''));
$TicketID = trim((string) ($_POST['TicketID'] ?? $_GET['TicketID'] ?? ''));

if ($TicketID === '' || !ctype_digit($TicketID)) {
    http_response_code(400);
    header('Content-Type: text/plain; charset=utf-8');
    exit('TicketID is required.');
}

$TicketID = (int) $TicketID;
$NumTicketID = $TicketID;
$BranchAccountManagerEmail = '';
$corporateticket_obj = new Corporateticket($conn);
$ticket_details = $corporateticket_obj->GetTicketDetails($TicketID);
    $branch_details = $ticket_details['branch_details'];
    $BranchSite = $branch_details['BranchSite'];
    $BranchMobile = $branch_details['BranchMobile'];
    $BranchAddress1 = $branch_details['BranchAddress1'];
    $BranchEmail = $branch_details['BranchEmail'];
    $BranchAccountManager = $branch_details['AccountBranchManager'];
    $CorporateID = $ticket_details['CorporateID'];
    

    $ticket_overview = $ticket_details['ticket_details'];
    $Type = $ticket_overview['Type'];
    $TicketID = $ticket_overview['TicketID'];
    $ClientTicketID = $ticket_overview['ClientTicketID'];
    $RegisteredDate = $ticket_overview['CreatedDate'];
    $CloseDate = $ticket_overview['CloseDate'];
    $CloseTime = $ticket_overview['CloseTime'];
    $Service = $ticket_overview['Service'];
    $Subservice = $ticket_overview['Subservice'];
    $RegisteredTime = $ticket_overview['CreatedTime'];
    $employee_obj = new Employee($conn);
    $employee_array = $employee_obj->setEmployeeArray('All');
    $AssignedTo = $ticket_overview['AssignedTo'];
    $TechnicianName = $employee_array[$AssignedTo]['Name']; 
    $TechnicianPhoneNumber = $employee_array[$AssignedTo]['ContactNumber'];
    if($BranchAccountManager != -1 && $BranchAccountManager != "")
    {
        $BranchAccountManagerEmail = $employee_array[$BranchAccountManager]['Email']; 
    } 

    $corporate_details = $ticket_details['corporate_details'];
    $CompanyEmail = "";
    if($corporate_details['CompanyEmail']!="")
    {
        $CompanyEmail = $corporate_details['CompanyEmail'];
    }

$media_asset = "dpr.png";
if($CorporateID == 183)
{
    $media_asset = "dpr.jpg";
}
            
$count = 1;

$tasks_html = "";

$task_query = "SELECT * FROM project_tasks WHERE TicketID = '$NumTicketID' AND IsActive = 1";
$task_result = mysqli_query($conn, $task_query);

$p_task_query = "SELECT * FROM projects WHERE TicketID = '$NumTicketID' AND IsActive = 1";
$p_task_result = mysqli_query($conn, $p_task_query);

if (mysqli_num_rows($p_task_result) > 0) {
    // Fetch the first row as an associative array
    $p_task_row = mysqli_fetch_assoc($p_task_result);

    $projectstartdate = $p_task_row['StartDate'];
    $projectenddate   = $p_task_row['EndDate'];
    $projectname   = $p_task_row['ProjectName'];
} else {
    $projectstartdate = null;
    $projectenddate   = null;
}

        
if(mysqli_num_rows($task_result) > 0) {
    $tasks_html .= '<div class="task_progress_section">';
    $tasks_html .= '<h3 style="background:#027dc1; color:#fff; padding:10px; border-radius:4px;">Project Task Progress Summary</h3>';

    while($task = mysqli_fetch_assoc($task_result)) {
        $TaskID = $task['ID'];
        $TaskName = htmlspecialchars($task['Description']);
        $StartDate = $task['StartDate'];
        $EndDate = $task['EndDate'];
        $ActualEndDate = $task['ActualEndDate'];
        $TaskStatus = $task['TaskStatus'];
        $Priority = $task['Prioirity'];
        $Weightage = $task['Weightage'];

        $tasks_html .= '
        <div class="task_box">
            <div class="task_header">
                <h4>'.$TaskName.'</h4>
                <div class="task_meta">
                    <span><b>Start:</b> '.$StartDate.'</span> | 
                    <span><b>End:</b> '.$EndDate.'</span> | 
                    <span><b>Status:</b> '.$TaskStatus.'</span> 
                </div>
            </div>';

        // ---- Fetch task progress ----
        $progress_query = "SELECT * FROM project_task_date_progress WHERE TaskID = '$TaskID' AND IsActive = 1 ORDER BY TaskDate ASC";
        $progress_result = mysqli_query($conn, $progress_query);

        if(mysqli_num_rows($progress_result) > 0) {
            $tasks_html .= '<table class="activity_table" style="margin-top:10px;">
                <thead>
                    <tr>
                        <th width="15%">Date</th>
                        <th width="20%">Status (In %)</th>
                        <th width="40%">Remarks</th>
                        <th width="25%">Images</th>
                    </tr>
                </thead>
                <tbody>';

            while($progress = mysqli_fetch_assoc($progress_result)) {
                $ProgressID = $progress['ID'];
                $TaskDate = $progress['TaskDate'];
                $Status = $progress['Status'];
                $Remarks = htmlspecialchars($progress['Remarks']);

                // ---- Fetch progress images ----
                $images_query = "SELECT * FROM project_task_evidence WHERE TaskProgressID = '$ProgressID' AND IsActive = 1";
                $images_result = mysqli_query($conn, $images_query);
                $img_html = "";

                if(mysqli_num_rows($images_result) > 0) {
                    while($img = mysqli_fetch_assoc($images_result)) {
                        $FileName = $img['FileName'];
                        $img_html .= '<img src="../../media/dpr/'.$FileName.'" alt="Evidence" class="evidence_img">';
                    }
                } else {
                    $img_html = '<span style="color:#999;">No Images</span>';
                }

                $tasks_html .= '
                <tr>
                    <td>'.$TaskDate.'</td>
                    <td>'.$Status.'</td>
                    <td>'.$Remarks.'</td>
                    <td>'.$img_html.'</td>
                </tr>';
            }

            $tasks_html .= '</tbody></table>';
        } else {
            $tasks_html .= '<p style="font-size:11px; color:#777;">No progress updates found for this task.</p>';
        }

        $tasks_html .= '</div>';
    }

    $tasks_html .= '</div>';
}





$project_query = "SELECT * FROM projects WHERE TicketID = '$NumTicketID' AND IsActive = 1";

$project_result = mysqli_query($conn, $project_query);
$project = mysqli_fetch_assoc($project_result);



$project_start = $project['StartDate'];
$project_end   = $project['EndDate'];


$dates = [];
$start = new DateTime($project_start);
$end   = new DateTime($project_end);
$end->modify('+1 day'); // include end date

$interval = new DateInterval('P1D');
$period = new DatePeriod($start, $interval, $end);

foreach ($period as $date) {
    $dates[] = $date->format('Y-m-d');
}


$task_query = "SELECT * FROM project_tasks WHERE TicketID = '$NumTicketID' AND IsActive = 1";

$task_result = mysqli_query($conn, $task_query);

// Split date columns into groups (10 per table)
$date_chunks = array_chunk($dates, 10);

$timeline_html = '<div class="project_timeline_section" style="margin-top:20px;">
    <h3 style="background:#027dc1; color:#fff; padding:10px; border-radius:4px;">Project Task Timeline</h3>';

foreach ($date_chunks as $chunk_index => $chunk_dates) {

    $timeline_html .= '<table class="timeline_table" style="margin-bottom:20px;">
        <thead>
            <tr>
                <th style="width:150px;">Task Name</th>';

    foreach ($chunk_dates as $d) {
        $timeline_html .= '<th style="min-width:60px;">'.date("d-M", strtotime($d)).'</th>';
    }
    $timeline_html .= '<th>Total</th></tr></thead><tbody>';

    mysqli_data_seek($task_result, 0); // reset pointer to loop tasks again

    while($task = mysqli_fetch_assoc($task_result)) {
        $TaskID = $task['ID'];
        $TaskName = htmlspecialchars($task['Description']);
        $timeline_html .= '<tr><td><b>'.$TaskName.'</b></td>';

        $progress_query = "SELECT TaskDate, Status FROM project_task_date_progress WHERE TaskID = '$TaskID' AND IsActive = 1";
        $progress_result = mysqli_query($conn, $progress_query);

        $progress_map = [];
        while($p = mysqli_fetch_assoc($progress_result)) {
            $progress_map[$p['TaskDate']] = $p['Status'];
        }

        foreach ($chunk_dates as $d) {
            if(isset($progress_map[$d])) {
                $status = $progress_map[$d];
                $color = "#fff";
                if(stripos($status, "Pending") !== false) $color = "#f0efecff";
                if(stripos($status, "Delayed") !== false) $color = "#eee6e5ff";
                $timeline_html .= '<td style="text-align:center; background:'.$color.'; color:black;">'.$status.' %</td>';
            } else {
                $timeline_html .= '<td style="text-align:center; color:#999;">–</td>';
            }
        }

// sort by date (just to be sure)
ksort($progress_map);

// Sum all numeric status values
$total_status = 0;
foreach ($progress_map as $status) {
    $status_num = (int)$status;  // convert to integer
    $total_status += $status_num;
}

// Clamp total to 100 max
if ($total_status > 100) {
    $total_status = 100;
}

// Display the sum with %
$last_status_display = $total_status > 0 ? $total_status . '%' : '-';

$timeline_html .= '<td style="font-weight:bold; text-align:center;">' . $last_status_display . '</td></tr>';




    }
    $timeline_html .= '</tbody></table>';
}
$timeline_html .= '</div>';

$chart_data = [];
$task_query = "SELECT * FROM project_tasks WHERE TicketID = '$NumTicketID' AND IsActive = 1";
$task_result = mysqli_query($conn, $task_query);

while ($task = mysqli_fetch_assoc($task_result)) {
    $TaskID = $task['ID'];
    $TaskName = htmlspecialchars($task['Description']);

    // Fetch cumulative progress
    $progress_query = "
        SELECT SUM(CAST(Status AS DECIMAL(10,2))) AS TotalProgress
        FROM project_task_date_progress
        WHERE TaskID = '$TaskID' AND IsActive = 1
    ";
    $progress_result = mysqli_query($conn, $progress_query);

    $progress_percent = 0;
    if ($row = mysqli_fetch_assoc($progress_result)) {
        $progress_percent = floatval($row['TotalProgress']);
        if ($progress_percent > 100) $progress_percent = 100;
    }

    $chart_data[] = [
        'task' => $TaskName,
        'progress' => $progress_percent
    ];
}


$max_tasks_per_image = 50; // how many tasks per chart image
$total_tasks = count($chart_data);



// if (!empty($chart_data)) {

//     /* ------------------ Layout Settings ------------------ */
//     $width = 900;
//     $bar_height = 25;
//     $padding = 60;
//     $gap = 30;

//     // Canvas height
//     $height = $padding * 2 + count($chart_data) * ($bar_height + $gap + 25);

//     // Bar X positions (smaller width)
//     $bar_start_x = 350;  // moved right to allow full text on left
//     $bar_end_x   = 650;  // narrower bar section

//     // image
//     $img = imagecreatetruecolor($width, $height);

//     /* ------------------ Colors ------------------ */
//     $white = imagecolorallocate($img, 255, 255, 255);
//     $black = imagecolorallocate($img, 0, 0, 0);
//     $green = imagecolorallocate($img, 0, 168, 0);   // green bar
//     $gray  = imagecolorallocate($img, 230, 230, 230);

//     imagefill($img, 0, 0, $white);

//     /* ------------------ Title ------------------ */
//     imagestring($img, 5, ($width/2) - 120, 20, "Task Progress Overview", $black);

//     $y = $padding;

//     foreach ($chart_data as $d) {
//         $task = $d['task'];
//         $progress = $d['progress'];

//         /* ------------------ FULL TASK NAME WRAPPING ------------------ */
//         $max_chars = 100; // large text width so it shows full string
//         $line_height = 14;

//         $lines = str_split($task, $max_chars);

//         /* ------------------ Draw Task Text ------------------ */
//         $text_x = 10;
//         $line_y = $y;

//         foreach ($lines as $line) {
//             imagestring($img, 3, $text_x, $line_y, trim($line), $black);
//             $line_y += $line_height;
//         }

//         // Move down for bar drawing
//         $y = $line_y + 5;

//         /* ------------------ Background Bar ------------------ */
//         imagefilledrectangle($img, $bar_start_x, $y, $bar_end_x, $y + $bar_height, $gray);

//         /* ------------------ Progress Bar (GREEN) ------------------ */
//         $progress_width = (($bar_end_x - $bar_start_x) * $progress) / 100;

//         imagefilledrectangle(
//             $img,
//             $bar_start_x,
//             $y,
//             $bar_start_x + $progress_width,
//             $y + $bar_height,
//             $green
//         );

//         /* ------------------ Percentage Text ------------------ */
//         imagestring($img, 4, $bar_end_x + 15, $y + 5, $progress . "%", $black);

//         /* ------------------ Move to Next Row ------------------ */
//         $y += $bar_height + $gap;
//     }

//     /* ------------------ Save Image ------------------ */
//     $chart_filename = '../reports/dpr/chart_'.$NumTicketID.'.png';
//     imagepng($img, $chart_filename);
//     imagedestroy($img);
// }


$chart_images = []; // array to store all generated chart file paths
$tasks_per_image = 10; // number of tasks per chart image

if (!empty($chart_data)) {

    $total_tasks = count($chart_data);
    $chunks = array_chunk($chart_data, $tasks_per_image); // split tasks into smaller chunks

    foreach ($chunks as $index => $tasks_chunk) {

        /* ------------------ Layout Settings ------------------ */
        $width = 900;
        $bar_height = 25;
        $padding = 60;
        $gap = 15; // smaller gap to reduce height

        $height = $padding * 2 + count($tasks_chunk) * ($bar_height + $gap + 25);

        $bar_start_x = 350;
        $bar_end_x = 650;

        // create image
        $img = imagecreatetruecolor($width, $height);

        /* ------------------ Colors ------------------ */
        $white = imagecolorallocate($img, 255, 255, 255);
        $black = imagecolorallocate($img, 0, 0, 0);
        $green = imagecolorallocate($img, 0, 168, 0);
        $gray  = imagecolorallocate($img, 230, 230, 230);

        imagefill($img, 0, 0, $white);

        /* ------------------ Title ------------------ */
        imagestring($img, 5, ($width/2) - 120, 20, "Task Progress Overview", $black);

        $y = $padding;

        foreach ($tasks_chunk as $d) {
            $task = $d['task'];
            $progress = $d['progress'];

            /* Wrap task name */
            $max_chars = 100;
            $line_height = 14;
            $lines = str_split($task, $max_chars);
            $text_x = 10;
            $line_y = $y;

            foreach ($lines as $line) {
                imagestring($img, 3, $text_x, $line_y, trim($line), $black);
                $line_y += $line_height;
            }

            $y = $line_y + 5;

            // background bar
            imagefilledrectangle($img, $bar_start_x, $y, $bar_end_x, $y + $bar_height, $gray);

            // progress bar
            $progress_width = (($bar_end_x - $bar_start_x) * $progress) / 100;
            imagefilledrectangle($img, $bar_start_x, $y, $bar_start_x + $progress_width, $y + $bar_height, $green);

            // percentage text
            imagestring($img, 4, $bar_end_x + 15, $y + 5, $progress . "%", $black);

            $y += $bar_height + $gap;
        }

        // save image
        $chart_filename = '../reports/dpr/chart_'.$NumTicketID.'_page_'.($index+1).'.png';
        imagepng($img, $chart_filename);
        imagedestroy($img);

        $chart_images[] = $chart_filename; // store file path
    }
}





// ---------------------- Circle Chart ----------------------

// Calculate overall project progress
$total_progress = 0;
$total_tasks = count($chart_data);

foreach ($chart_data as $item) {
    $total_progress += $item['progress'];
}

$overall_percent = ($total_tasks > 0)
    ? round($total_progress / $total_tasks)
    : 0;

$overall_remaining = 100 - $overall_percent;


// ---------- Overall Circular Chart (Donut with Green + Red) -----------
$circle_width  = 300;
$circle_height = 300;

$circle_img = imagecreatetruecolor($circle_width, $circle_height);

// Colors
$white      = imagecolorallocate($circle_img, 255, 255, 255);
$green      = imagecolorallocate($circle_img, 0, 168, 0);   // completed
$red        = imagecolorallocate($circle_img, 220, 0, 0);   // remaining
$black      = imagecolorallocate($circle_img, 0, 0, 0);

imagefill($circle_img, 0, 0, $white);

// Donut settings
$thickness = 40;
$cx = (int)$circle_width / 2;
$cy = (int)$circle_height / 2;
$radius = 120;

// Full red background circle (remaining)
imagefilledarc(
    $circle_img,
    $cx,
    $cy,
    (int)$radius * 2,
    (int)$radius * 2,
    0,
    360,
    $red,
    IMG_ARC_PIE
);

// Draw green progress arc on top
$start_angle = 270; // start at top
$end_angle   = $start_angle + (($overall_percent / 100) * 360);

imagefilledarc(
    $circle_img,
    $cx,
    $cy,
    (int)$radius * 2,
    (int)$radius * 2,
    $start_angle,
    $end_angle,
    $green,
    IMG_ARC_PIE
);

// Donut hole
$hole_radius = $radius - $thickness;
imagefilledellipse(
    $circle_img,
    $cx,
    $cy,
    (int)$hole_radius * 2,
   (int)$hole_radius * 2,
    $white
);

// Center % text
$text = $overall_percent . "%";
$font = 5;

$text_width  = imagefontwidth($font) * strlen($text);
$text_height = imagefontheight($font);

imagestring(
    $circle_img,
    $font,
    (int)($cx - ($text_width / 2)),
    (int)($cy - ($text_height / 2)),
    $text,
    $black
);

// Save chart
$circle_filename = "../reports/dpr/circle_$NumTicketID.png";
imagepng($circle_img, $circle_filename);
imagedestroy($circle_img);




// -----------------------new thing form here---------------------

$progress_images_html = '';

if (!empty($chart_images)) {
    foreach ($chart_images as $index => $chart_file) {
        if (file_exists($chart_file)) {
            $progress_images_html .= '
            <div class="chart_section" style="margin-top:20px;">
                <h3 style="background:#027dc1; color:#fff; padding:10px; border-radius:4px;">Task Progress Chart - Page '.($index+1).'</h3>
                <img src="'.$chart_file.'" style="width:100%; max-height:650px; object-fit:contain; border:1px solid #000; border-radius:6px; display:block; margin-bottom:10px;">
            </div>
            <pagebreak>'; // MPDF page break after each chart
        }
    }
} else {
    $progress_images_html .= '<p style="color:#999;">No chart images generated.</p>';
}



$html_prefix = '
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <style>
        .container{
            // border:1px solid #000;
        } 
        body{
             font-family: poppins, sans-serif;
            font-size:12pt;
        }  
        table {
            width: 100%;
        }
        .top_header{
           background:#027dc1;
           border:1px solid #000;
        }
        .top_header tr td{
            width:50%;
         }
         .top_header_inside tr td {
            text-align:center;
            color:#fff;
            font-weight:bold;
            // font-family: sans-serif;
            font-size:15px;
         }
         .top_right_border{
            border-right:1px solid #fff;
         } 

         
         .customer_details{
            border:1px solid #000;
         }
         .customer_details tr td{
            width:50%;
         }
         .customer_details_inside tr td{
             font-size:11px;
            //  font-family: sans-serif;
         }
         .customer_details_inside tr td{
            padding:3px 0px;
         }
         .costumer_detail_bold{
            font-weight:bold;
            width:100px;
            font-size:12px;
         }
         .custumer_right_border{
            border-right:1px solid #000;
         }
         .report_details_inside tr td{
            font-size:11px;
            // font-family: sans-serif;
        }
        .report_details_inside tr td{
           padding:3px 0px;
        }
        
        .customer_details tr {
            display: inline-flex !important;
        }
        .other_feedback{
            border-left: 1px solid #000;
            border-right: 1px solid #000;
            border-bottom: 1px solid #000;
            // padding-bottom:50px;
        }
        .other_feedback tr .width_30{
            font-weight:bold;
            font-size:12px;
            // font-family: sans-serif;
        }
        .other_feedback_inside td{
            font-size:11px;
            // font-family: sans-serif;
            margin-top:10px;
        }

        .attacment_div{
            border: 1px solid #000;
            page-break-inside: avoid;
        }   

        .action_taken_div{
            page-break-inside: avoid;
        }
        .remark_div{
            page-break-inside: avoid;
        }
        .observation_div{
            page-break-inside: avoid;
        }
        .problemreported_div{
            page-break-inside: avoid;
        }
        .quotation_div{
            page-break-inside: avoid;
        }

        .bottom_div{
            page-break-inside: avoid;
        }

        .attacment{
            background:#027dc1;
            padding:6px 0px 6px 6px;
            color:#fff;
            font-size:14px;
            font-weight:bold;
            // font-family: sans-serif;
            
        }
        .product_attachment_box{
            padding:20px;
        }
        

        .bottom_signature_table{
            border:1px solid #000;
            border-collapse: collapse;
        }

        .bottom_table_bold{
            font-weight:bold;
            font-size:13px;
            padding-left:10px !important;
        }

        .bottom_signature_table tbody tr td{
            // font-family: sans-serif;
            border:1px solid #000;
            font-size:12px;
            padding-top:15px;
            padding-bottom:15px;
            width:20%;
        }


        .natural_of_call_div{
            border-left: 1px solid #000;
            border-right: 1px solid #000;
            font-weight:bold;
            font-size:12px;
            padding:4px 7px;
            // font-family: sans-serif;
        }
        .natural_table_bold{
            font-weight:bold;
            font-size:11px;
            padding-left:10px !important;
        }
        .natural_call_table{
            border:1px solid #000;
            border-collapse: collapse;
        }
        .natural_call_table tbody tr td{
            // font-family: sans-serif;
            border:1px solid #000;
            padding:10px 5px;
        }
        .pdf_header{
            width:100%;
        }
        .pdf_header img {
            width:100%;
        }

        .client_signature{
            width:100px;

        }
        .client_signature img{
            width:100%;
        }

        .tech_value{
          font-weight:bold;
        }   

        .tech_bottom_table_bold{
            font-size:13px;
            padding-left:10px !important;
        }

        .page_1 {
            // border:1px solid #000;
            // padding:30px;
            // height:88.8%;
        }
        .page_2 {
            // border:1px solid #000;
            // padding:30px;
            // height:100%;
        }

        .activity_table {
    border-collapse: collapse;
    width: 100%;
    font-size: 11px;
    // font-family: sans-serif;
}

.activity_table th {
    background-color: #027dc1;
    color: #fff;
    padding: 8px;
    text-align: left;
    border: 1px solid #000;
}

.activity_table td {
    border: 1px solid #000;
    padding: 6px;
}

.activity_table tr:nth-child(even) {
    background-color: #f9f9f9;
}

.rating-badge {
   margin-top:10px;
  display: inline-block;
  padding: 6px 14px;
  font-size: 14px;
  font-weight: 600;
  color: #fff;
  border-radius: 25px;
  letter-spacing: 0.3px;
  transition: all 0.3s ease;
  text-align: right;
}

.rating-badge:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 10px rgba(0,0,0,0.2);
}

.task_progress_section {
  margin-top: 30px;
  page-break-inside: avoid;
}

.task_box {
  border: 1px solid #ccc;
  border-radius: 8px;
  margin-bottom: 20px;
  padding: 15px;
  background: #fdfdfd;
  page-break-inside: avoid;
  box-shadow: 0 2px 6px rgba(0,0,0,0.08);
}

.task_header {
  border-bottom: 1px solid #ddd;
  padding-bottom: 6px;
  margin-bottom: 8px;
}

.task_header h4 {
  color: #027dc1;
  margin: 0;
  font-size: 15px;
}

.task_meta {
  font-size: 11px;
  color: #444;
  margin-top: 4px;
}

.evidence_img {
  width: 70px;
  height: 70px;
  object-fit: cover;
  margin-right: 5px;
  border-radius: 6px;
  border: 1px solid #ccc;
}

.project_timeline_section {
    page-break-inside: avoid;
}
.project_timeline_section table {
    border-collapse: collapse;
    width: 100%;
    font-size: 10px;
}
.project_timeline_section th, .project_timeline_section td {
    border: 1px solid #000;
    padding: 4px;
}
.project_timeline_section th {
    background: #027dc1;
    color: #fff;
}

.chart_section img {
  margin-top:10px;
  width:100%;
  border:1px solid #ccc;
  border-radius:8px;
}


    </style>
</head>
<body>
    <div class="container">
           
        <div class="page_1">
            <div class="pdf_header">
                <img src="../../media/pdf-assets/'.$media_asset.'">
            </div>
            <div style="display:flex; margin-top:10px;">
                <table class="top_header">
                    <tr>
                        <td class="top_right_border">
                            <table class="top_header_inside">
                                <tr>
                                <td>Project Details</td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </div>
            <div>
                <table  class="customer_details">
                    <tr>
                        <td class="custumer_right_border">
                            <table class="customer_details_inside">
                                <tr>
                                    <td style="width:50%;" class="costumer_detail_bold">Branch Name</td>
                                    <td style="width:10%;">:</td>
                                    <td style="width:40%;">'.$BranchSite.'</td>
                                </tr>
                                <tr>
                                    <td style="width:50%;" class="costumer_detail_bold">Address</td>
                                    <td style="width:10%;">:</td>
                                    <td style="width:40%;">'.$BranchAddress1.'</td>
                                </tr>
                                
                                <tr>
                                    <td style="width:50%;" class="costumer_detail_bold">Mobile No.</td>
                                    <td style="width:10%;">:</td>
                                    <td style="width:40%;">'.$BranchMobile.'</td>
                                </tr>
                               
                                <tr>
                                    <td style="width:50%;" class="costumer_detail_bold"></td>
                                    <td style="width:10%;"></td>
                                    <td style="width:40%;"></td>
                                </tr>
                                <tr>
                                    <td style="width:50%;" class="costumer_detail_bold"></td>
                                    <td style="width:10%;"></td>
                                    <td style="width:40%;"></td>
                                </tr>
                                <tr>
                                    <td style="width:50%;" class="costumer_detail_bold"></td>
                                    <td style="width:10%;"></td>
                                    <td style="width:40%;"></td>
                                </tr>
                                <tr>
                                    <td style="width:50%;" class="costumer_detail_bold"></td>
                                    <td style="width:10%;"></td>
                                    <td style="width:40%;"></td>
                                </tr>
                                <tr>
                                    <td style="width:50%;" class="costumer_detail_bold"></td>
                                    <td style="width:10%;"></td>
                                    <td style="width:40%;"></td>
                                </tr>
                                <tr>
                                    <td style="width:50%;" class="costumer_detail_bold"></td>
                                    <td style="width:10%;"></td>
                                    <td style="width:40%;"></td>
                                </tr>
                                <tr>
                                    <td style="width:50%;" class="costumer_detail_bold"></td>
                                    <td style="width:10%;"></td>
                                    <td style="width:40%;"></td>
                                </tr>
                            </table>
                        </td>
                        <td>
                            <table class="report_details_inside">
                             <tr>
                                    <td style="width:50%;" class="costumer_detail_bold">Project Name</td>
                                    <td style="width:10%;">:</td>
                                    <td style="width:40%;">'.$projectname.'</td>
                                </tr>
                                <tr>
                                    <td style="width:50%;" class="costumer_detail_bold">Type</td>
                                    <td style="width:10%;">:</td>
                                    <td style="width:40%;">'.$Type.'</td>
                                </tr>
                                <tr>
                                    <td style="width:50%;" class="costumer_detail_bold">Ticket No</td>
                                    <td style="width:10%;">:</td>
                                    <td style="width:40%;">'.$TicketID.'</td>
                                </tr>

                                 <tr>
                                    <td style="width:50%;" class="costumer_detail_bold">Client Ticket Number</td>
                                    <td style="width:10%;">:</td>
                                    <td style="width:40%;">'.$ClientTicketID.'</td>
                                </tr>
                                
                                   <tr>
                                        <td style="width:50%;" class="costumer_detail_bold">Project Start Date</td>
                                        <td style="width:10%;">:</td>
                                        <td style="width:40%;">'.$projectstartdate.'</td>
                                    </tr>
                                    <tr>
                                        <td style="width:50%;" class="costumer_detail_bold">Project End Date</td>
                                        <td style="width:10%;">:</td>
                                        <td style="width:40%;">'.$projectenddate.'</td>
                                    </tr>

                                <tr>
                                    <td style="width:50%;" class="costumer_detail_bold"> </td>
                                    <td style="width:10%;"></td>
                                    <td style="width:40%;"></td>
                                </tr>
                                
                            </table>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
        <div class="">
 '.(file_exists($circle_filename) ? '
<div class="chart_section" style="page-break-inside: avoid; margin-top:20px; text-align:center;">
    <h3 style="background:#027dc1; color:#fff; padding:10px; border-radius:4px; text-align:center;">
        Overall Project Progress
    </h3>


            <!-- Legend with table for PDF compatibility -->
            <div style="display:flex; justify-content:center; align-items:center; width:100%; margin-top:1px; margin-left:150px;">
            <table style="margin: 20px auto 0; border-collapse: separate; border-spacing: 10px 5px; 
                    font-family: Arial, sans-serif; font-size: 14px; color: #333; ">
            <tr>
                <td style="width: 20px; height: 20px; background-color: #00A800; border-radius: 4px; vertical-align: middle;"></td>
                <td style="padding-left: 5px; vertical-align: middle;">Completed</td>

                <td style="width: 20px; height: 20px; background-color: #DC0000; border-radius: 4px; vertical-align: middle;"></td>
                <td style="padding-left: 5px; vertical-align: middle;">Pending</td>
            </tr>
        </table>
         </div>


    <div style="display:flex; justify-content:center; align-items:center; width:100%; margin-top:10px;">
        <img src="'.$circle_filename.'" style="width:40%; max-width:280px; margin:auto;">
    </div>

    
</div>' : '').'

';
$html_suffix = '
</div>
    </div>
</body>
</html>';






$default_logo = "https://techxpertindia.in/images/techx-14.png"; // default logo

if ($CorporateID == 183) {
    $default_logo = "https://techxpertindia.in/admin/img/innov_logo.jpg"; // new logo for ID 183
}
$defaultConfig = (new Mpdf\Config\ConfigVariables())->getDefaults();
$fontDirs = $defaultConfig['fontDir'];

$defaultFontConfig = (new Mpdf\Config\FontVariables())->getDefaults();
$fontData = $defaultFontConfig['fontdata'];

$mpdf = new \Mpdf\Mpdf([
    'fontDir' => array_merge($fontDirs, [__DIR__ . '/../../fonts']),
    'fontdata' => $fontData + [
        'poppins' => [
            'R' => 'Poppins-Regular.ttf',
            'B' => 'Poppins-Bold.ttf',
            'I' => 'Poppins-Italic.ttf'
        ]
    ],
    'default_font' => 'poppins'
]);
$mpdf->WriteHTML("");
$mpdf->SetWatermarkImage(
    $default_logo,
    0.08,         // opacity (keep subtle)
    [50, 20],    // size (width x height in mm) - adjust as needed
    'F',          // behind content (background)
    true,         // keep aspect ratio
    45            // rotation angle (45 = diagonal)
);
// --- Generate PDF ---
$mpdf->showWatermarkImage = true;
$mpdf->SetFooter('
<div style="font-size:9px; color:#555; border-top:1px solid #999; padding:3px 10px;">
    <span style="float:left; font-style:italic; color:#333;">
        System Generated Report | Private & Confidential |
    </span>
    <span style="float:right; font-weight:bold; color:#000;">
        Page {PAGENO} of {nbpg}
    </span>
</div>
');

$pdf_name = "service-report-dpr" . $TicketID . ".pdf";
$pdfFilePath = '../reports/dpr/' . $pdf_name;

// --- Generate PDF only if not already present ---
if (!file_exists($pdfFilePath)) {
    $mpdf->showWatermarkImage = true;
    $mpdf->SetFooter('
<div style="font-size:9px; color:#555; border-top:1px solid #999; padding:3px 10px;">
    <span style="float:left; font-style:italic; color:#333;">
        System Generated Report | Private & Confidential | Generated on: '.date("d-M-Y H:i").'
    </span>
    <span style="float:right; font-weight:bold; color:#000;">
        Page {PAGENO} of {nbpg}
    </span>
</div>
');

    dprWriteDprReportToMpdf(
        $mpdf,
        $html_prefix,
        $progress_images_html,
        $timeline_html,
        $tasks_html,
        $html_suffix
    );

    // Ensure folder exists
    if (!file_exists('../reports/dpr')) {
        mkdir('../reports/dpr', 0777, true);
    }

    $mpdf->Output($pdfFilePath, \Mpdf\Output\Destination::FILE);
    $mpdf->Close();
}

$response['pdfname'] = $pdf_name;

// --- Now handle mail sending ---
if ($Action == "Send") {
    $project_query = "
    SELECT 
        p.ProjectName,
        p.ProjectManager,
        p.StartDate AS ProjectStartDate,
        p.EndDate AS ProjectEndDate,
        p.TicketNumber,
        t.CustomerManagerName,
        t.CustomerManagerEmail,
        t.CustomerManagerPhone,
        t.CustomerSupervisorEmail,
        t.CustomerSupervisorPhone,
        t.TechXpertManagerEmail,
        t.TechXpertManagerPhone
    FROM projects p
    LEFT JOIN project_team_details t 
        ON p.ID = t.ProjectID 
    WHERE p.TicketID = '$NumTicketID' 
      AND p.IsActive = 1 
    LIMIT 1;
";

$project_result = mysqli_query($conn, $project_query);
$project_row = $project_result && mysqli_num_rows($project_result) > 0 ? mysqli_fetch_assoc($project_result) : [];

// --- Extract individual fields safely ---
$ProjectName       = $project_row['ProjectName'] ?? 'N.A';
$ProjectStartDate  = $project_row['ProjectStartDate'] ?? 'N.A';
$ProjectEndDate    = $project_row['ProjectEndDate'] ?? 'N.A';
$SupervisorName    = $project_row['CustomerManagerName'] ?? 'N.A';
$SupervisorEmail   = $project_row['CustomerSupervisorEmail'] ?? 'N.A';
$CustomerManagerEmail  = trim($project_row['CustomerManagerEmail'] ?? '');
$CustomerSupervisorEmail = trim($project_row['CustomerSupervisorEmail'] ?? '');
$TechXpertManagerEmail   = trim($project_row['TechXpertManagerEmail'] ?? '');

    // --- Collect recipients (remove empty/duplicate) ---
    $all_emails = array_unique(array_filter([
        $BranchEmail ?? '',
        $CompanyEmail ?? '',
        $BranchAccountManagerEmail ?? '',
        $CustomerManagerEmail,
        $CustomerSupervisorEmail,
        $TechXpertManagerEmail
    ]));

    // --- Instead of attaching PDF, send report URL ---
    $report_url = "https://techxpertindia.in/admin/corporate-tickets/action/generate-dpr-report?TicketID=" . urlencode($NumTicketID);

    // --- Prepare mail payload ---
    $mail_data = [
    'action'                     => "Daily Progress Report (View Online)",
    'SiteName'                   => $BranchSite,
    'POCName'                    => $BranchAccountManager ?? "Customer",
    'ReportID'                   => $NumTicketID,
    'TicketID'                   => $NumTicketID,
    'ClientTicketID'             => $ClientTicketID,
    'ReportURL'                  => $report_url,
    'BranchEmail'                => $BranchEmail,
    'CompanyEmail'               => $CompanyEmail,
    'BranchAccountManagerEmail'  => $BranchAccountManagerEmail,
    'ClientRepresentativeEmails' => implode(',', $all_emails),
    'ProjectName'                => $ProjectName,
    'ProjectStartDate'           => $ProjectStartDate,
    'ProjectEndDate'             => $ProjectEndDate,
    'SupervisorName'             => $SupervisorName,
    'SupervisorEmail'            => $SupervisorEmail
];


    // --- Send mail (just include URL, not attachment) ---
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://techxpertindia.in/admin/mail/send-dpr-report.php');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($mail_data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    $mail_response = curl_exec($ch);
    curl_close($ch);

    $response['mail_status'] = "Mail sent (URL shared) to: " . implode(',', $all_emails);
    $response['report_url'] = $report_url;
    $response['mail_api_response'] = $mail_response;

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($response);
    exit;
}

$disposition = ($Action === 'Download') ? 'attachment' : 'inline';

if (is_file($pdfFilePath)) {
    header('Content-Type: application/pdf');
    header('Content-Disposition: ' . $disposition . '; filename="' . basename($pdf_name) . '"');
    header('Content-Length: ' . filesize($pdfFilePath));
    readfile($pdfFilePath);
    exit;
}

$mpdf->Output(
    $pdf_name,
    $Action === 'Download' ? \Mpdf\Output\Destination::DOWNLOAD : \Mpdf\Output\Destination::INLINE
);
exit;