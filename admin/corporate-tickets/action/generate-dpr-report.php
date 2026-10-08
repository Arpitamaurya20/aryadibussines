<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once('../../includes/autoloader.inc.php');
require_once '../../vendor/autoload.php'; 
include '../../controllers/common_controllers.php';
require_once __DIR__ . '/../../includes/aryadi_pdf_theme.php';

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
    $TechnicianName = isset($employee_array[$AssignedTo]) ? $employee_array[$AssignedTo]['Name'] : '';
    $TechnicianPhoneNumber = isset($employee_array[$AssignedTo]) ? $employee_array[$AssignedTo]['ContactNumber'] : '';
    if($BranchAccountManager != -1 && $BranchAccountManager != "" && isset($employee_array[$BranchAccountManager]))
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
    $projectname = null;
}

        
if(mysqli_num_rows($task_result) > 0) {
    $tasks_html .= apdf_section_head('Project Task Progress Summary');

    while($task = mysqli_fetch_assoc($task_result)) {
        $TaskID = $task['ID'];
        $TaskName = htmlspecialchars($task['Description']);
        $StartDate = $task['StartDate'];
        $EndDate = $task['EndDate'];
        $ActualEndDate = $task['ActualEndDate'];
        $TaskStatus = $task['TaskStatus'];
        $Priority = $task['Prioirity'];
        $Weightage = $task['Weightage'];

        $task_badge = 'Start: '.$StartDate.'  |  End: '.$EndDate.'  |  '.$TaskStatus;

        // ---- Fetch task progress ----
        $progress_query = "SELECT * FROM project_task_date_progress WHERE TaskID = '$TaskID' AND IsActive = 1 ORDER BY TaskDate ASC";
        $progress_result = mysqli_query($conn, $progress_query);

        if(mysqli_num_rows($progress_result) > 0) {
            $progress_rows = '';
            $row_index = 0;

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
                        if (!is_file('../../media/dpr/'.$FileName)) {
                            continue;
                        }
                        $img_html .= '<img src="../../media/dpr/'.$FileName.'" alt="Evidence" class="evidence_img">';
                    }
                }
                if ($img_html === '') {
                    $img_html = '<span class="muted">No Images</span>';
                }

                $progress_rows .= '
                <tr'.($row_index % 2 ? ' class="alt"' : '').'>
                    <td>'.apdf_text($TaskDate).'</td>
                    <td class="num"><b>'.apdf_text($Status).'</b>'.($Status !== '' && $Status !== null ? ' %' : '').'</td>
                    <td>'.apdf_text($Remarks).'</td>
                    <td>'.$img_html.'</td>
                </tr>';
                $row_index++;
            }

            $tasks_html .= apdf_data_box($task['Description'], array(
                array('Date', 15),
                array('Status (In %)', 15),
                array('Remarks', 40),
                array('Images', 30),
            ), $progress_rows, $task_badge);
        } else {
            $tasks_html .= apdf_box($task['Description'], apdf_box_text('No progress updates found for this task.', true), $task_badge);
        }
    }
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

$timeline_html = '';
$timeline_parts = count($date_chunks);

foreach ($date_chunks as $chunk_index => $chunk_dates) {

    $date_width = count($chunk_dates) > 0 ? round(68 / count($chunk_dates), 2) : 68;
    $timeline_title = 'Project Task Timeline'.($timeline_parts > 1 ? '  ·  Part '.($chunk_index + 1).' of '.$timeline_parts : '');
    $timeline_html .= '<table class="box data timeline" style="margin-top:4mm;">
        <thead>
            <tr><td class="box_title" colspan="'.(count($chunk_dates) + 2).'">'.htmlspecialchars($timeline_title, ENT_QUOTES, 'UTF-8').'</td></tr>
            <tr>
                <th style="width:22%;">Task Name</th>';

    foreach ($chunk_dates as $d) {
        $timeline_html .= '<th style="width:'.$date_width.'%; text-align:center;">'.date("d-M", strtotime($d)).'</th>';
    }
    $timeline_html .= '<th style="width:10%; text-align:center;">Total</th></tr></thead><tbody>';

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
                $color = "#EAF4FD";
                if(stripos($status, "Pending") !== false) $color = "#F1F5F9";
                if(stripos($status, "Delayed") !== false) $color = "#FDECEC";
                $timeline_html .= '<td style="text-align:center; background:'.$color.'; color:#0B356E; font-weight:bold;">'.$status.' %</td>';
            } else {
                $timeline_html .= '<td style="text-align:center; color:#94A3B8;">–</td>';
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

$timeline_html .= '<td style="font-weight:bold; text-align:center; color:#0B356E; background:#F5F9FD;">' . $last_status_display . '</td></tr>';




    }
    $timeline_html .= '</tbody></table>';
}

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


if (!file_exists('../reports/dpr')) {
    mkdir('../reports/dpr', 0777, true);
}

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
            $progress_images_html .= '<pagebreak>'.apdf_box(
                'Task Progress Chart',
                '<div style="padding:3mm; text-align:center;"><img src="'.$chart_file.'" style="width:100%; max-height:200mm;"></div>',
                'Page '.($index+1).' of '.count($chart_images)
            );
        }
    }
} else {
    $progress_images_html .= apdf_box('Task Progress Chart', apdf_box_text('No chart images generated.', true));
}



$dpr_logo = ($CorporateID == 183) ? '../../img/innov_logo.jpg' : '../../media/pdf-assets/aryadi-logo.png';

$circle_html = '';
if (file_exists($circle_filename)) {
    $circle_html = apdf_box('Overall Project Progress', '
        <table width="100%">
            <tr>
                <td style="width:50%; text-align:center; padding:4mm;">
                    <img src="'.$circle_filename.'" style="width:55mm;">
                </td>
                <td style="width:50%; vertical-align:middle; padding:4mm;">
                    <div class="block_label">Overall Completion</div>
                    <div style="font-size:26pt; font-weight:bold; color:#0B356E;">'.$overall_percent.'%</div>
                    <table style="margin-top:4mm; width:auto;">
                        <tr>
                            <td style="width:5mm; height:5mm; background-color:#00A800;"></td>
                            <td style="padding:0 6mm 0 2mm; font-size:9pt; color:#334155;">Completed ('.$overall_percent.'%)</td>
                        </tr>
                        <tr><td colspan="2" style="height:2mm;"></td></tr>
                        <tr>
                            <td style="width:5mm; height:5mm; background-color:#DC0000;"></td>
                            <td style="padding:0 6mm 0 2mm; font-size:9pt; color:#334155;">Pending ('.$overall_remaining.'%)</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>');
}

$html_prefix = '
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Daily Progress Report '.$TicketID.'</title>
    <style>'.apdf_css().'
        .evidence_img { width: 22mm; height: 22mm; margin: 0.6mm; border: 0.3mm solid #D6E4F2; }
        table.timeline th, table.timeline td { font-size: 7.5pt; padding: 1.6mm 1mm; }
    </style>
</head>
<body>
    '.apdf_header('DAILY PROGRESS REPORT', 'PROJECT STATUS SUMMARY', $dpr_logo).'

    '.apdf_stats(array(
        array('Ticket No', apdf_text($TicketID)),
        array('Project Start', apdf_text($projectstartdate)),
        array('Project End', apdf_text($projectenddate)),
        array('Overall Progress', $overall_percent.'%'),
    )).'

    '.apdf_two_col(
        'Site Details',
        array(
            array('Branch Name', apdf_text($BranchSite)),
            array('Address', apdf_text($BranchAddress1)),
            array('Mobile No.', apdf_text($BranchMobile)),
        ),
        'Project Details',
        array(
            array('Project Name', apdf_text($projectname)),
            array('Type', apdf_text($Type)),
            array('Ticket No', apdf_text($TicketID)),
            array('Client Ticket Number', apdf_text($ClientTicketID)),
            array('Project Start Date', apdf_text($projectstartdate)),
            array('Project End Date', apdf_text($projectenddate)),
        )
    ).'

    '.$circle_html.'
';
$html_suffix = '
</body>
</html>';






$mpdf = apdf_create_mpdf('Daily Progress Report '.$TicketID, 'Daily Progress Report · Ticket '.$TicketID, array(), 'Aryadi Business Pvt. Ltd.', 'System Generated Report | Private & Confidential');
$pdf_name = "service-report-dpr" . $TicketID . ".pdf";
$pdfFilePath = '../reports/dpr/' . $pdf_name;

// --- Generate PDF only if not already present ---
if (!file_exists($pdfFilePath)) {
    apdf_footer($mpdf, 'Daily Progress Report · Ticket '.$TicketID, 'Aryadi Business Pvt. Ltd.', 'System Generated Report | Private & Confidential | Generated on: '.date("d-M-Y H:i"));

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