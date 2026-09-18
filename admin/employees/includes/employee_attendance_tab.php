<?php
require_once(dirname(__DIR__, 2) . '/attendance-list/controller/attendance_controller.php');

$currentYear = date('Y');
$currentMonth = date('m');
$dbh = new Dbh();
$conn = $dbh->_connectodb();

$month_data = fetchEmployeeAttendanceMonthData($conn, $ID, $currentYear, $currentMonth);
$rendered = renderEmployeeAttendanceReportRows($month_data['calendar_rows'], $month_data['attendance_data']);

$employee_name = isset($employee_data['Name']) ? $employee_data['Name'] : 'Employee';
$employee_number = isset($employee_data['EmployeeNumber']) ? $employee_data['EmployeeNumber'] : '';
$employee_designation = isset($employee_data['Designation']) ? $employee_data['Designation'] : '';
$report_month_label = date('F Y', mktime(0, 0, 0, (int) $currentMonth, 1, (int) $currentYear));
?>

<style>
/* 
==================================================
   PROFESSIONAL CORPORATE ATTENDANCE TABLE 
================================================== 
*/
.att-report-card {
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    background: #ffffff;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
    font-family: 'Inter', -apple-system, sans-serif;
    overflow: hidden;
}

/* Clean Professional Header */
.att-report-header {
    background: #ffffff;
    color: #1e293b;
    padding: 1.5rem;
    border-bottom: 1px solid #e2e8f0;
}

.att-report-header h4 {
    margin: 0 0 0.25rem;
    font-size: 1.15rem;
    font-weight: 600;
    color: #0f172a;
}

.att-report-header .att-report-subtitle {
    font-size: 0.85rem;
    color: #64748b;
    margin: 0;
}

.att-report-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
    margin-top: 1rem;
    font-size: 0.85rem;
}

.att-report-meta span {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 4px;
    padding: 0.25rem 0.75rem;
    color: #475569;
}

.att-report-meta span strong {
    color: #1e293b;
    font-weight: 600;
}

/* Filters Section */
.att-report-filters {
    background: #f8fafc;
    padding: 1rem 1.5rem;
    border-bottom: 1px solid #e2e8f0;
}

.att-report-filters .form-control {
    background-color: #ffffff;
    border: 1px solid #cbd5e1;
    color: #334155;
    border-radius: 4px;
    padding: 0.35rem 0.5rem;
    font-size: 0.85rem;
}

.att-report-filters .form-control:focus {
    border-color: #3b82f6;
    outline: none;
    box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.1);
}

.att-report-note {
    font-size: 0.8rem;
    color: #64748b;
    margin: 0.75rem 0 0;
}

.att-report-table-wrap {
    overflow-x: auto;
}

/* Classic Data Table */
#employee-attendance-report-table {
    margin: 0;
    width: 100%;
    border-collapse: collapse;
    font-size: 0.85rem;
    color: #334155;
}

#employee-attendance-report-table th,
#employee-attendance-report-table td {
    padding: 0.75rem 1rem !important;
    border: 1px solid #cbd5e1 !important;
    vertical-align: middle;
}

#employee-attendance-report-table thead th {
    background: #e2e8f0;
    color: #334155;
    font-weight: 700;
    text-transform: uppercase;
    font-size: 0.75rem;
    letter-spacing: 0.02em;
    text-align: center;
    position: sticky;
    top: 0;
    z-index: 4;
}

#employee-attendance-report-table tbody tr {
    background: #ffffff;
}

/* Subtle striped rows for readability */
#employee-attendance-report-table tbody tr:nth-child(even) {
    background: #f1f5f9;
}

#employee-attendance-report-table tbody tr:hover {
    background-color: #e2e8f0;
}

#employee-attendance-report-table tbody td {
    text-align: center;
}

/* Date Column */
.att-col-date {
    background: inherit !important;
    color: #1e293b !important;
    min-width: 110px;
    text-align: left !important;
}

.att-date-main {
    display: block;
    font-weight: 600;
    color: #0f172a;
}

.att-date-day {
    display: block;
    font-size: 0.75rem;
    color: #64748b;
    margin-top: 0.1rem;
}

/* Text formatting */
.att-col-time {
    color: #475569;
}

.att-col-metric {
    font-weight: 600;
    color: #3b82f6;
}

/* Row Backgrounds based on status */
.att-row-present td {
    /* No special background, let stripe handle it, just normal text */
}

.att-row-absent td {
    background-color: #fef2f2 !important;
}

.att-row-half td {
    background-color: #fffbeb !important;
}

.att-row-weekend td {
    background-color: #f8fafc !important;
    color: #94a3b8;
}

/* Approval Status Section */
.att-approval-status {
    display: flex;
    flex-direction: column;
    gap: 0.4rem;
    align-items: center;
}

.att-approval-row {
    display: flex;
    align-items: center;
    gap: 0.4rem;
    flex-wrap: wrap;
    justify-content: center;
}

.att-approval-label {
    font-size: 0.7rem;
    font-weight: 600;
    text-transform: uppercase;
    color: #64748b;
    min-width: 70px;
    text-align: right;
}

/* Subtle Status Pills */
.att-status-pill {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 4px;
    padding: 0.2rem 0.5rem;
    font-size: 0.7rem;
    font-weight: 600;
    letter-spacing: 0.02em;
    text-transform: uppercase;
    line-height: 1;
}

.att-status-approved {
    background: #dcfce7;
    color: #166534;
    border: 1px solid #bbf7d0;
}

.att-status-rejected {
    background: #fee2e2;
    color: #991b1b;
    border: 1px solid #fecaca;
}

.att-status-pending {
    background: #fef9c3;
    color: #854d0e;
    border: 1px solid #fef08a;
}

.att-status-muted {
    background: #f1f5f9;
    color: #475569;
    border: 1px solid #e2e8f0;
}

.att-location-link {
    font-size: 0.75rem;
    color: #2563eb;
    text-decoration: none;
}
.att-location-link:hover {
    text-decoration: underline;
}

/* Summary Row styling */
.att-summary-row td {
    background: #f1f5f9 !important;
    border-top: 2px solid #cbd5e1 !important;
    font-weight: 600;
}

.att-summary-sub {
    font-size: 0.75rem;
    color: #64748b;
    font-weight: 500;
}

.att-ack-footer {
    padding: 1rem 1.5rem;
    font-size: 0.8rem;
    color: #64748b;
    background: #ffffff;
    border-top: 1px solid #e2e8f0;
}
</style>

<div class="row">
    <div class="col-sm-12">
        <div class="att-report-card mb-g" id="employee-attendance-report">
            <div class="att-report-header">
                <h4>Monthly Attendance Report</h4>
                <p class="att-report-subtitle"><?php echo htmlspecialchars($report_month_label); ?> &mdash; For Employee Acknowledgment</p>
                <div class="att-report-meta">
                    <span><strong>Employee:</strong> <?php echo htmlspecialchars($employee_name); ?></span>
                    <?php if ($employee_number !== '') { ?>
                        <span><strong>ID:</strong> <?php echo htmlspecialchars($employee_number); ?></span>
                    <?php } ?>
                    <?php if ($employee_designation !== '') { ?>
                        <span><strong>Designation:</strong> <?php echo htmlspecialchars($employee_designation); ?></span>
                    <?php } ?>
                </div>
            </div>

            <div class="att-report-filters">
                <div class="row align-items-end">
                    <div class="col-md-3 col-6 mb-2 mb-md-0">
                        <label class="small text-muted mb-1">Year</label>
                        <select class="form-control form-control-sm" id="attendance_select_year" onchange="GenerateEmployeeAttendanceDetails(<?php echo (int) $ID; ?>)">
                            <?php for ($year = 2024; $year <= 2030; $year++) { ?>
                                <option value="<?php echo $year; ?>" <?php echo ((string) $currentYear === (string) $year) ? 'selected' : ''; ?>><?php echo $year; ?></option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="col-md-3 col-6 mb-2 mb-md-0">
                        <label class="small text-muted mb-1">Month</label>
                        <select class="form-control form-control-sm" id="attendance_select_month" onchange="GenerateEmployeeAttendanceDetails(<?php echo (int) $ID; ?>)">
                            <option value="">Select Month</option>
                            <?php
                            for ($month = 1; $month <= 12; $month++) {
                                $monthValue = str_pad($month, 2, '0', STR_PAD_LEFT);
                                $selected = ($monthValue == $currentMonth) ? 'selected' : '';
                                echo '<option value="' . $monthValue . '" ' . $selected . '>' . date('F', mktime(0, 0, 0, $month, 1)) . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <p class="att-report-note mb-0">Employee name and month stay fixed above. Screenshot the full report card to share for acknowledgment.</p>
                    </div>
                </div>
            </div>

            <div class="att-report-table-wrap">
                <table class="table table-sm table-bordered mb-0" id="employee-attendance-report-table" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Check In</th>
                            <th>Check Out</th>
                            <th>TWH</th>
                            <th>OT</th>
                            <th>ST</th>
                            <th>Approval Status</th>
                        </tr>
                    </thead>
                    <tbody id="employee_attendance_table">
                        <?php
                        echo $rendered['rows_html'];
                        echo renderEmployeeAttendanceSummaryFooterHtml($rendered['summary']);
                        ?>
                    </tbody>
                </table>
            </div>

            <div class="att-ack-footer">
                Generated on <?php echo date('d M Y, h:i A'); ?> &bull;
                Supervisor and HR approval shown separately &bull;
                TWH = Total Working Hours, OT = Overtime, ST = Short Time
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="imageModal" tabindex="-1" aria-labelledby="imageModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="imageModalLabel">Image Preview</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body d-flex justify-content-center">
                <img id="modalImage" alt="Preview" class="modal-image">
            </div>
        </div>
    </div>
</div>

<div id="locationModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.6); z-index:9999;">
    <div style="background: rgba(30, 41, 59, 0.95); backdrop-filter: blur(10px); color: #fff; width:500px; margin:8% auto; padding:20px; border-radius:12px; border: 1px solid rgba(255, 255, 255, 0.1); position:relative; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);">
        <h4>Location Details</h4>
        <div id="locationAddress" style="margin-bottom:10px; color: #cbd5e1;">Loading...</div>
        <iframe id="mapFrame" width="100%" height="300" style="border:0;"></iframe>
        <br><br>
        <button onclick="closeLocationModal()" style="padding:6px 15px;">Close</button>
    </div>
</div>

<script>
function openLocationModal(lat, lng) {
    document.getElementById("locationModal").style.display = "block";
    document.getElementById("locationAddress").innerHTML = "Loading...";
    document.getElementById("mapFrame").src = "https://www.google.com/maps?q=" + lat + "," + lng + "&output=embed";
    fetch("./action/get_address.php?lat=" + lat + "&lng=" + lng)
        .then(response => response.text())
        .then(data => {
            document.getElementById("locationAddress").innerHTML = data;
        })
        .catch(() => {
            document.getElementById("locationAddress").innerHTML = "Unable to fetch address.";
        });
}

function closeLocationModal() {
    document.getElementById("locationModal").style.display = "none";
}
</script>
