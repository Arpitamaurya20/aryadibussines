<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <?php
	include('../controllers/common_controllers.php');
    setNavigation($_SESSION['Roles']);
	include('controller/employee_controller.php');
    include('../city/controller/city_controller.php');
    include('../state/controller/state_controller.php');
    $UserType = SessionCheck();
    
	$conn = _connectodb();
	?>
    <meta charset="utf-8">
    <title>
        Add Employee
    </title>
    <meta name="description" content="Create CFL  ">
    <?php
	include('../includes/common_head_content.php');
	?>
    <link rel="stylesheet" media="screen, print"
        href="../css/formplugins/bootstrap-datepicker/bootstrap-datepicker.css">
</head>
<script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-6496901533964255"
     crossorigin="anonymous"></script>
<?php
$username = $_SESSION['pb_username'];
$division_array = getDivisionArray($conn);

$Citydata = getAllCity($conn);
$Citydata = json_decode($Citydata,true);
$Days_Array = array("Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday");
$StateData=getAllStates($conn);


?>

<body class="mod-bg-1 desktop chrome webkit pace-done nav-function-fixed blur">
    <?php include('../js/theme_settings.js'); ?>

    <!-- BEGIN Page Wrapper -->
    <div class="page-wrapper">
        <div class="page-inner">
            <?php
			include('../navigation/admin_navigation.php');
			?>
            <div class="page-content-wrapper">
                <!-- BEGIN Page Header -->
                <?php
				include('../includes/common_header.php');
				?>
                <!-- END Page Header -->
                <!-- BEGIN Page Content -->
                <!-- the #js-page-content id is needed for some plugins to initialize -->
                <main id="js-page-content" role="main" class="page-content">
                    <ol class="breadcrumb page-breadcrumb">
                        <li class="breadcrumb-item"><a href="../dashboard/admin_dashboard">Aryadibusiness</a></li>
                        <li class="breadcrumb-item"><a href="view-employees.php">View Employees</a></li>
                        <li class="breadcrumb-item active">Add </li>

                    </ol>

                    <!-- ═══════════════════════════════════════════════
                         Add Employee — Modern Sectioned Card Form
                    ═══════════════════════════════════════════════ -->
                    <style>
                        /* ── Google Font ── */
                        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');

                        /* ── Wrapper ── */
                        .ef-wrap { font-family:'Inter','Segoe UI',sans-serif; }

                        /* ── Progress tracker strip ── */
                        .ef-tracker {
                            display:flex; align-items:flex-start;
                            background:#fff;
                            border-radius:14px;
                            border:1px solid #e2e8f5;
                            box-shadow:0 2px 16px rgba(0,0,0,.055);
                            padding:18px 32px;
                            margin-bottom:22px;
                            gap:0;
                        }
                        .ef-ts { flex:1; display:flex; flex-direction:column; align-items:center; position:relative; }
                        .ef-ts:not(:last-child)::after {
                            content:''; position:absolute;
                            top:15px; left:50%;
                            width:100%; height:2px;
                            background:linear-gradient(90deg,#6366f1,#c7d2fe);
                            z-index:0;
                        }
                        .ef-td {
                            width:30px; height:30px; border-radius:50%;
                            background:linear-gradient(135deg,#6366f1,#8b5cf6);
                            color:#fff; font-size:12px; font-weight:700;
                            display:flex; align-items:center; justify-content:center;
                            position:relative; z-index:1;
                            box-shadow:0 3px 10px rgba(99,102,241,.38);
                        }
                        .ef-tl {
                            font-size:9.5px; font-weight:700; color:#6366f1;
                            margin-top:6px; text-transform:uppercase;
                            letter-spacing:.5px; text-align:center;
                        }

                        /* ── Section card ── */
                        .ef-card {
                            background:#fff;
                            border-radius:14px;
                            border:1px solid #e2e8f5;
                            box-shadow:0 2px 16px rgba(0,0,0,.055);
                            margin-bottom:20px;
                            overflow:hidden;
                            transition:box-shadow .25s;
                        }
                        .ef-card:hover { box-shadow:0 6px 28px rgba(0,0,0,.09); }

                        /* ── Card header ── */
                        .ef-head {
                            display:flex; align-items:center; gap:13px;
                            padding:14px 22px;
                            background:linear-gradient(135deg,#f8fafd,#eef1f8);
                            border-bottom:2px solid #edf1f8;
                        }
                        .ef-ico {
                            width:38px; height:38px; border-radius:10px;
                            display:flex; align-items:center;
                            justify-content:center; font-size:16px; flex-shrink:0;
                        }
                        .ef-ico-p { background:linear-gradient(135deg,#6366f1,#8b5cf6); color:#fff; }
                        .ef-ico-s { background:linear-gradient(135deg,#059669,#34d399); color:#fff; }
                        .ef-ico-b { background:linear-gradient(135deg,#d97706,#fcd34d); color:#fff; }
                        .ef-ico-d { background:linear-gradient(135deg,#dc2626,#fb923c); color:#fff; }
                        .ef-ico-l { background:linear-gradient(135deg,#2563eb,#60a5fa); color:#fff; }

                        .ef-ht { font-size:14.5px; font-weight:700; color:#1e2a45; margin:0; }
                        .ef-hs { font-size:11px; color:#8898b8; margin:2px 0 0; }

                        /* ── Card body ── */
                        .ef-body { padding:20px 22px 4px; }

                        /* ── Labels ── */
                        .ef-wrap .form-label {
                            font-size:11px; font-weight:700;
                            color:#5a6a8a; text-transform:uppercase;
                            letter-spacing:.6px; margin-bottom:5px; display:block;
                        }

                        /* ── Inputs ── */
                        .ef-wrap .form-control {
                            border:1.5px solid #d5ddf0;
                            border-radius:8px;
                            padding:8px 12px;
                            font-size:13.5px; color:#2d3748;
                            background:#f9fafc;
                            transition:border-color .18s,box-shadow .18s;
                            height:auto;
                        }
                        .ef-wrap .form-control:focus {
                            border-color:#6366f1;
                            box-shadow:0 0 0 3px rgba(99,102,241,.13);
                            background:#fff; outline:none;
                        }
                        .ef-wrap select.form-control {
                            -webkit-appearance:none; appearance:none;
                            background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6' viewBox='0 0 10 6'%3E%3Cpath fill='%235a6a8a' d='M5 6L0 0h10z'/%3E%3C/svg%3E");
                            background-repeat:no-repeat;
                            background-position:right 12px center;
                            padding-right:32px;
                        }
                        .ef-wrap input[type="file"].form-control { padding:6px 12px; font-size:12px; }
                        .ef-fg { margin-bottom:16px; }

                        /* ── Radio pills ── */
                        .ef-radios { display:flex; flex-wrap:wrap; gap:8px; margin-top:5px; }
                        .ef-pill {
                            display:flex; align-items:center; gap:6px;
                            padding:6px 14px;
                            border:2px solid #d5ddf0;
                            border-radius:8px;
                            background:#f9fafc;
                            cursor:pointer;
                            transition:border-color .16s,background .16s;
                            user-select:none;
                        }
                        .ef-pill:hover { border-color:#6366f1; background:#eef0ff; }
                        .ef-pill input[type="radio"] {
                            accent-color:#6366f1; width:14px; height:14px; margin:0; cursor:pointer;
                        }
                        .ef-pill label {
                            margin:0; font-size:13px; font-weight:600;
                            color:#4a5568; cursor:pointer;
                            text-transform:none; letter-spacing:0;
                        }

                        /* ── Section sub-label ── */
                        .ef-sub-lbl {
                            font-size:9.5px; font-weight:700;
                            color:#b0bcd8; text-transform:uppercase;
                            letter-spacing:1.1px; margin:6px 0 12px;
                            padding-bottom:7px;
                            border-bottom:1px dashed #e2e8f5;
                        }

                        /* ── Submit button ── */
                        .ef-btn-submit {
                            background:linear-gradient(135deg,#6366f1,#8b5cf6);
                            border:none; border-radius:10px;
                            color:#fff; font-size:14px; font-weight:700;
                            padding:11px 34px; letter-spacing:.3px;
                            cursor:pointer;
                            display:inline-flex; align-items:center; gap:8px;
                            box-shadow:0 4px 18px rgba(99,102,241,.38);
                            transition:transform .18s,box-shadow .18s;
                        }
                        .ef-btn-submit:hover {
                            transform:translateY(-2px);
                            box-shadow:0 8px 24px rgba(99,102,241,.48);
                        }
                        .ef-btn-submit:active { transform:none; }

                        /* ── Required star ── */
                        .r { color:#ef4444; }
                    </style>

                    <div class="ef-wrap">
                      <form id="add_employee_form">

                        <!-- ── Progress Tracker ── -->
                        <div class="ef-tracker">
                            <div class="ef-ts"><div class="ef-td">1</div><div class="ef-tl">Personal</div></div>
                            <div class="ef-ts"><div class="ef-td">2</div><div class="ef-tl">Salary</div></div>
                            <div class="ef-ts"><div class="ef-td">3</div><div class="ef-tl">Banking</div></div>
                            <div class="ef-ts"><div class="ef-td">4</div><div class="ef-tl">Documents</div></div>
                            <div class="ef-ts"><div class="ef-td">5</div><div class="ef-tl">Location</div></div>
                        </div>

                        <!-- ════════════════════════════════
                             SECTION 1 · Personal Information
                        ════════════════════════════════ -->
                        <div class="ef-card">
                            <div class="ef-head">
                                <div class="ef-ico ef-ico-p"><i class="fal fa-user-tie"></i></div>
                                <div>
                                    <div class="ef-ht">Personal Information</div>
                                    <div class="ef-hs">Basic employee identity, role &amp; contact details</div>
                                </div>
                            </div>
                            <div class="ef-body">

                                <div class="ef-sub-lbl">Type &amp; Identity</div>
                                <div class="row">
                                    <!-- Work Type -->
                                    <div class="col-xl-4 col-md-6">
                                        <div class="ef-fg">
                                            <label class="form-label">Work Type <span class="r">*</span></label>
                                            <div class="ef-radios">
                                                <div class="ef-pill">
                                                    <input type="radio" id="wt_emp" value="Employee" class="work_type" name="work_type" onchange="EnableDisableDivision(this.value)">
                                                    <label for="wt_emp">Employee</label>
                                                </div>
                                                <div class="ef-pill">
                                                    <input type="radio" id="wt_vnd" value="Vendor" class="work_type" name="work_type" onchange="EnableDisableDivision(this.value)">
                                                    <label for="wt_vnd">Vendor</label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- Gender -->
                                    <div class="col-xl-4 col-md-6">
                                        <div class="ef-fg">
                                            <label class="form-label">Gender <span class="r">*</span></label>
                                            <div class="ef-radios">
                                                <div class="ef-pill">
                                                    <input type="radio" id="gen_m" value="His" name="gender_type">
                                                    <label for="gen_m">Male (His)</label>
                                                </div>
                                                <div class="ef-pill">
                                                    <input type="radio" id="gen_f" value="Her" name="gender_type">
                                                    <label for="gen_f">Female (Her)</label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- Profile Photo -->
                                    <div class="col-xl-4 col-md-6">
                                        <div class="ef-fg">
                                            <label class="form-label">Profile Photo</label>
                                            <input type="file" id="employee_profile_photo" name="employee_profile_photo" class="form-control">
                                        </div>
                                    </div>
                                </div>

                                <div class="ef-sub-lbl">Name &amp; Role</div>
                                <div class="row">
                                    <div class="col-xl-3 col-md-6">
                                        <div class="ef-fg">
                                            <label class="form-label">Employee Name <span class="r">*</span></label>
                                            <input type="text" name="employee_name" id="employee_name" class="form-control" placeholder="Full name">
                                        </div>
                                    </div>
                                    <div class="col-xl-3 col-md-6">
                                        <div class="ef-fg">
                                            <label class="form-label">Father's Name</label>
                                            <input type="text" name="father_name" id="father_name" class="form-control" placeholder="Father's name">
                                        </div>
                                    </div>
                                    <div class="col-xl-3 col-md-6">
                                        <div class="ef-fg">
                                            <label class="form-label">Designation <span class="r">*</span></label>
                                            <input type="text" name="designation" id="designation" class="form-control" placeholder="e.g. Manager">
                                        </div>
                                    </div>
                                    <div class="col-xl-3 col-md-6">
                                        <div class="ef-fg">
                                            <label class="form-label">Department <span class="r">*</span></label>
                                            <select name="employee_department" id="employee_department" class="form-control">
                                                <option value="">Please Select</option>
                                                <option value="Operation">Operation</option>
                                                <option value="HR">HR</option>
                                                <option value="Accountant">Accountant</option>
                                                <option value="Finance">Finance</option>
                                                <option value="Marketing">Marketing</option>
                                                <option value="Precurment">Procurement</option>
                                                <option value="IT">IT</option>
                                                <option value="OfficeStaff">Office Staff</option>
                                            </select>
                                            <input type="text" value="NA" id="department_textbox" class="form-control" readonly style="display:none;" />
                                        </div>
                                    </div>
                                </div>

                                <div class="ef-sub-lbl">Contact &amp; Joining</div>
                                <div class="row">
                                    <div class="col-xl-3 col-md-6">
                                        <div class="ef-fg">
                                            <label class="form-label">Contact Number <span class="r">*</span></label>
                                            <input type="text" name="employee_contact" id="employee_contact" class="form-control" onkeyup="validISNumber(basic)" placeholder="Mobile number">
                                        </div>
                                    </div>
                                    <div class="col-xl-3 col-md-6">
                                        <div class="ef-fg">
                                            <label class="form-label">Contact Email <span class="r">*</span></label>
                                            <input type="email" name="employee_email" id="employee_email" class="form-control" placeholder="email@company.com">
                                        </div>
                                    </div>
                                    <div class="col-xl-3 col-md-6">
                                        <div class="ef-fg">
                                            <label class="form-label">Date of Joining</label>
                                            <input type="text" class="form-control" name="date_of_joining" id="date_of_joining" placeholder="YYYY-MM-DD" value="" />
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>

                        <!-- ════════════════════════════════
                             SECTION 2 · Salary Details
                        ════════════════════════════════ -->
                        <div class="ef-card">
                            <div class="ef-head">
                                <div class="ef-ico ef-ico-s"><i class="fal fa-rupee-sign"></i></div>
                                <div>
                                    <div class="ef-ht">Salary Details</div>
                                    <div class="ef-hs">Monthly compensation components &amp; allowances</div>
                                </div>
                            </div>
                            <div class="ef-body">
                                <div class="row">
                                    <div class="col-xl-2 col-md-4 col-sm-6">
                                        <div class="ef-fg">
                                            <label class="form-label">Basic</label>
                                            <input type="text" name="basic" id="basic" class="form-control" onkeyup="validISNumber(basic)" placeholder="₹ 0">
                                        </div>
                                    </div>
                                    <div class="col-xl-2 col-md-4 col-sm-6">
                                        <div class="ef-fg">
                                            <label class="form-label">DA</label>
                                            <input type="text" name="da" id="da" class="form-control" onkeyup="validISNumber(basic)" placeholder="₹ 0">
                                        </div>
                                    </div>
                                    <div class="col-xl-2 col-md-4 col-sm-6">
                                        <div class="ef-fg">
                                            <label class="form-label">HRA</label>
                                            <input type="text" name="hra" id="hra" class="form-control" onkeyup="validISNumber(basic)" placeholder="₹ 0">
                                        </div>
                                    </div>
                                    <div class="col-xl-2 col-md-4 col-sm-6">
                                        <div class="ef-fg">
                                            <label class="form-label">Bonus</label>
                                            <input type="text" name="bonus" id="bonus" class="form-control" onkeyup="validISNumber(basic)" placeholder="₹ 0">
                                        </div>
                                    </div>
                                    <div class="col-xl-2 col-md-4 col-sm-6">
                                        <div class="ef-fg">
                                            <label class="form-label">Health Insurance</label>
                                            <input type="text" name="health_insurance" id="health_insurance" class="form-control" onkeyup="validISNumber(basic)" placeholder="₹ 0">
                                        </div>
                                    </div>
                                    <div class="col-xl-2 col-md-4 col-sm-6">
                                        <div class="ef-fg">
                                            <label class="form-label">Others</label>
                                            <input type="text" name="others" id="others" class="form-control" onkeyup="validISNumber(basic)" placeholder="₹ 0">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ════════════════════════════════
                             SECTION 3 · Banking & Compliance
                        ════════════════════════════════ -->
                        <div class="ef-card">
                            <div class="ef-head">
                                <div class="ef-ico ef-ico-b"><i class="fal fa-university"></i></div>
                                <div>
                                    <div class="ef-ht">Banking &amp; Compliance</div>
                                    <div class="ef-hs">Bank account, UAN, EPF &amp; ESIC numbers</div>
                                </div>
                            </div>
                            <div class="ef-body">
                                <div class="row">
                                    <div class="col-xl-3 col-md-6">
                                        <div class="ef-fg">
                                            <label class="form-label">Bank Account Name</label>
                                            <input type="text" name="bank_account_name" id="bank_account_name" class="form-control" placeholder="Account holder name">
                                        </div>
                                    </div>
                                    <div class="col-xl-3 col-md-6">
                                        <div class="ef-fg">
                                            <label class="form-label">Bank Account Number</label>
                                            <input type="text" name="bank_account_number" id="bank_account_number" class="form-control" onkeyup="validISNumber(basic)" placeholder="Account number">
                                        </div>
                                    </div>
                                    <div class="col-xl-3 col-md-6">
                                        <div class="ef-fg">
                                            <label class="form-label">UAN Number</label>
                                            <input type="text" name="uan_number" id="uan_number" class="form-control" placeholder="Universal Account Number">
                                        </div>
                                    </div>
                                    <div class="col-xl-3 col-md-6">
                                        <div class="ef-fg">
                                            <label class="form-label">EPF Number</label>
                                            <input type="text" name="epf_number" id="epf_number" class="form-control" placeholder="EPF Number">
                                        </div>
                                    </div>
                                    <div class="col-xl-3 col-md-6">
                                        <div class="ef-fg">
                                            <label class="form-label">ESIC Number</label>
                                            <input type="text" name="esic_number" id="esic_number" class="form-control" placeholder="ESIC Number">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ════════════════════════════════
                             SECTION 4 · KYC Documents
                        ════════════════════════════════ -->
                        <div class="ef-card">
                            <div class="ef-head">
                                <div class="ef-ico ef-ico-d"><i class="fal fa-id-card"></i></div>
                                <div>
                                    <div class="ef-ht">KYC Documents</div>
                                    <div class="ef-hs">PAN, Aadhaar &amp; police verification uploads</div>
                                </div>
                            </div>
                            <div class="ef-body">
                                <div class="ef-sub-lbl">PAN Card</div>
                                <div class="row">
                                    <div class="col-xl-3 col-md-6">
                                        <div class="ef-fg">
                                            <label class="form-label">PAN Number</label>
                                            <input type="text" name="employee_pan_number" id="employee_pan_number" class="form-control" placeholder="ABCDE1234F">
                                        </div>
                                    </div>
                                    <div class="col-xl-3 col-md-6">
                                        <div class="ef-fg">
                                            <label class="form-label">PAN Photo</label>
                                            <input type="file" id="employee_pan_img" name="employee_pan_img" class="form-control">
                                        </div>
                                    </div>
                                </div>

                                <div class="ef-sub-lbl">Aadhaar Card</div>
                                <div class="row">
                                    <div class="col-xl-3 col-md-6">
                                        <div class="ef-fg">
                                            <label class="form-label">Aadhaar Number</label>
                                            <input type="text" name="employee_aadhar" id="employee_aadhar" class="form-control" onkeyup="validISNumber(basic)" placeholder="XXXX XXXX XXXX">
                                        </div>
                                    </div>
                                    <div class="col-xl-3 col-md-6">
                                        <div class="ef-fg">
                                            <label class="form-label">Aadhaar Photo</label>
                                            <input type="file" id="employee_addhar_image" name="employee_addhar_img" class="form-control">
                                        </div>
                                    </div>
                                </div>

                                <div class="ef-sub-lbl">Police Verification</div>
                                <div class="row">
                                    <div class="col-xl-6 col-md-8">
                                        <div class="ef-fg">
                                            <label class="form-label">Police Verification Photo</label>
                                            <input type="file" id="employee_police_verification" name="employee_police_verification" class="form-control">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ════════════════════════════════
                             SECTION 5 · Location & Schedule
                        ════════════════════════════════ -->
                        <div class="ef-card">
                            <div class="ef-head">
                                <div class="ef-ico ef-ico-l"><i class="fal fa-map-marker-alt"></i></div>
                                <div>
                                    <div class="ef-ht">Location &amp; Schedule</div>
                                    <div class="ef-hs">Work location and weekly off day</div>
                                </div>
                            </div>
                            <div class="ef-body">
                                <div class="row">
                                    <div class="col-xl-4 col-md-6">
                                        <div class="ef-fg">
                                            <label class="form-label">Weekly Off</label>
                                            <select name="weekly_off" class="select2 form-control w-100" id="weekly_off">
                                                <option value="">Please Select</option>
                                                <?php foreach($Days_Array as $day): ?>
                                                <option value="<?php echo $day; ?>"><?php echo $day; ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-xl-4 col-md-6">
                                        <div class="ef-fg">
                                            <label class="form-label">State</label>
                                            <select name="employee_state" class="select2 form-control w-100" id="statedata">
                                                <option value="">Please Select</option>
                                                <?php foreach($StateData as $Statevalue): ?>
                                                <option value="<?php echo $Statevalue['StateName']; ?>"><?php echo $Statevalue['StateName']; ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-xl-4 col-md-6">
                                        <div class="ef-fg">
                                            <label class="form-label">City</label>
                                            <select name="employee_city" class="select2 form-control w-100" id="citydata">
                                                <option value="">Please Select</option>
                                                <?php foreach($Citydata as $Cityvalue): ?>
                                                <option value="<?php echo $Cityvalue['CityName']; ?>"><?php echo $Cityvalue['CityName']; ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ── Submit Row ── -->
                        <div style="text-align:right; padding:4px 0 30px;">
                            <button type="button" id="AddEmployeeButton" class="ef-btn-submit" onclick="AddEmployee();">
                                <i class="fal fa-user-plus"></i> Add Employee
                            </button>
                        </div>

                      </form>
                    </div>
                </main>


                <!-- this overlay is activated only when mobile menu is triggered -->
                <div class="page-content-overlay" data-action="toggle" data-class="mobile-nav-on"></div>
                <!-- END Page Content -->
                <!-- BEGIN Page Footer -->
                <?php
				include('../includes/common_footer.php')
				?>
                <!-- END Page Footer -->

            </div>
        </div>
    </div>
    <!-- END Page Wrapper -->

    <?php
	include('../includes/common_modules.php');
	include('../includes/common_scripts.php');
	?>

</body>
<script src="../js/formplugins/bootstrap-datepicker/bootstrap-datepicker.js"></script>
<script src="../js/modules/employee.js"></script>

<script>
$(document).ready(function() {
    $("#js-nav-menu").addClass("active");
    $("#js-nav-menu").addClass("open");
    $("#nav_employees").addClass("active");
    $('#date_of_joining').datepicker({
        format: "yyyy-mm-dd",
        todayBtn: "linked",
        clearBtn: true,
        todayHighlight: true,
        autoclose: true
    });
    $("#weekly_off").select2();
});

$("#citydata").select2();
</script>

</html>