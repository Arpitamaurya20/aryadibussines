<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    
    <meta name="description" content="Aryadibusiness Analytics Dashboard">
    <?php
    include('../includes/common_head_content.php');
    include('../includes/autoloader.inc.php');
    include('../controllers/common_controllers.php');
    $UserType = SessionCheck();
    setNavigation($_SESSION['Roles']);
    $conn = _connectodb();
    $core = new Core();
    $core->setTimeZone();
    $data = array();
    $CorporateID = -1;
    $BranchID = -1;
    $techx_admin = true;
    if($UserType == "Corporate Admin")
    {
        $CorporateID = $_SESSION['Roles']['CorporateID'];
    }
    $data['CorporateID'] = $CorporateID;
    $config_obj = new Config($conn);
    $fields_data = $config_obj->getAllConfigurableFields($data);
    $ProductName = "Aryadibusiness";
    if ($CorporateID == 183) 
    {
        $_product = "innov";
        $conf = new Config($conn);
        $product_configuration = $conf->GetConfigParametersfromURL($_product);
        $ProductName = $product_configuration['ProductName'];
    } 
    $logoImg = "tech-logo.jpg";
    if(isset($product_configuration['logo']))
    {
        $logoImg = $product_configuration['logo'];
    }   

    ?>
    <link rel="stylesheet" media="screen, print" href="../css/statistics/chartjs/chartjs.css">
    <style type="text/css">
        .page-content .panel
        {
            margin-bottom: 0px;
        }
        .panel .panel-container .panel-content
        {
            padding: 0.5rem 0.5rem;
        }
        .table-bordered thead th, .table-bordered thead td
        {
            font-size: 0.9em;
        }
    </style>
    <title>
        <?=$ProductName;?> Configuration options
    </title>
    <?php 
    if(isset($product_configuration['favicon']))
    {
        ?>
        <link rel="icon" type="image/png" sizes="32x32" href="../img/favicon/<?=$product_configuration['favicon'];?>">
        <?php
    }
    if($ProductName != "Aryadibusiness")
    {
        include("../css/client_generated_css.php");
    }
    ?>
</head>

<body class="mod-bg-1 header-function-fixed nav-function-fixed blur">
    <input type="hidden" name="UserType" id="UserType" value="<?php echo $UserType;?>">
    <!-- DOC: script to save and load page settings -->
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
                        <li class="breadcrumb-item"><a href="javascript:void(0);"><?=$ProductName;?></a></li>
                        <li class="breadcrumb-item"><a href="javascript:void(0);">Configuration</a></li>
                    </ol>
                    
                    <div class="row">
                        <div class="col-xl-12">
                            <div id="panel-10" class="panel">
                                    <div class="panel-hdr">
                                        <h2>
                                            Form <span class="fw-300"><i>Configuration</i></span>
                                        </h2>
                                        <div class="panel-toolbar">
                                          <a onclick="AddFormConfiguration()"><span class="badge badge-success badge-pill cursor-pointer">Add</span></a>
                                            
                                        </div>
                                    </div>
                                    <div class="panel-container show">
                                        <div class="panel-content">
                                            <div class="panel-tag">
                                                You can add upto 5 customizatable form elements in raise ticket form which will be applicable while raising ticket

                                            </div>
                                            <div class="card-group">
                                                    
                                                    
                                                        <?php 
                                                        if(sizeof($fields_data) > 0)
                                                        {
                                                            foreach($fields_data as $field)
                                                            {
                                                            ?>
                                                                <div class="card">
                                                                    
                                                                    <div class="card-body">
                                                                        <h5 class="card-title">Form Title - <?php echo $field['Title'];?><a onclick="EditFormConfiguration(<?php echo $field['ID'];?>)"><span class="badge badge-primary badge-pill cursor-pointer float-right">Edit</span></a></h5>
                                                                        <p class="card-text">Type - <?php echo $field['Type']; ?></p>
                                                                        <p class="card-text">Mandatory - <?php echo $field['Mandatory']; ?></p>
                                                                        <small class="text-muted">Created at <?php echo $field['CreatedDate'].' - '.$field['CreatedTime'];?></small>

                                                                    </div>
                                                                </div>
                                                               
                                                            <?php 
                                                            }
                                                        ?>
                                                            
                                                        <?php 
                                                        }
                                                        ?>
                                                    
                                                    
                                                </div>
                                                        <?php
                                                        if(sizeof($fields_data) == 0)
                                                        {
                                                            ?>
                                                             <div class="card-group">
                                                                
                                                                <div class="card">
                                                                    
                                                                    <div class="card-body">
                                                                        <h5 class="card-title">No Configurable Fields</h5>
                                                                        <p class="card-text"><a onclick="AddFormConfiguration()"><span class="badge badge-success badge-pill cursor-pointer">Add</span></a></p>
                                                                        
                                                                    </div>
                                                                </div>
                                                                
                                                            </div>
                                                            <?php 
                                                        }
                                                        ?>
                                        </div>
                                    </div>
                                </div>
                        </div>
                        <?php 
                        if($product_configuration['circle_module'] == true)
                        {
                            $config_obj = new Config($conn);
                            $circles_array = $config_obj->viewCircles($CorporateID);
                            ?>
                            <div class="col-xl-6 mt-2">
                                <div class="panel">
                                    <div class="panel-hdr">
                                        <h2>
                                            Manage <span class="fw-300"><i>Circles</i></span>
                                        </h2>
                                        <div class="panel-toolbar">
                                          <a onclick="AddCircle()"><span class="badge badge-success badge-pill cursor-pointer">Add</span></a>
                                            
                                        </div>
                                        
                                    </div>
                                    <div class="panel-container show">
                                        <div class="panel-content">
                                            <div class="panel-tag">
                                                Add <code>Circles</code> that will be added to all the branches.<br>Circle Manager can be configured using User Management
                                            </div>
                                            <table class="table table-bordered m-0">
                                                <thead>
                                                    <tr>
                                                        <th>#</th>
                                                        <th>Circle</th>
                                                        <th>Circle Head</th>
                                                        <th>Edit</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php 
                                                    $ci = 1;
                                                    foreach($circles_array as $circle)
                                                    {
                                                        ?>
                                                        <tr>
                                                            <th scope="row"><?=$ci;?></th>
                                                            <td><?=$circle['CircleName'];?></td>
                                                            <td>N.A.</td>
                                                            <td><a onclick="EditCircle(<?=$circle['ID'];?>,'<?=$circle['CircleName'];?>')"><span class="badge badge-primary badge-pill cursor-pointer">Edit</span></a></td>
                                                        </tr>
                                                        <?php
                                                        $ci++;
                                                    }
                                                    ?>
                                                    
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <?php
                        }
                        ?>
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
    <?php
        include('../includes/common_modules.php');
        include('../includes/common_scripts.php');
       ?>
    <script src="../js/dependency/moment/moment.js"></script>
    <script src="../js/modules/corporate-configuration.js"></script>
    <script>
    $(document).ready(function() {
         $("#_Nav_Corporate_Configuration").addClass("active");
    });
    </script>

    
    
</body>

</html>


<div class="modal fade" id="add_update_form_configuration" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header modal_header">
                    <h5 class="modal-title" id="company_modal_title"> Create/Update Form Fields </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form METHOD="POST" id="add_update_field_form" onsubmit="return false;">
                        <div class="form-group">
                            <div class="row">
                                <div class="col-12">
                                    <label>Field Title <span class="text-danger">*</span> </label>
                                    <input type="text" class="form-control" name="field_title" id="field_title" placeholder="Enter Field Title">
                                </div>
                                <div class="col-12 mt-3">
                                    <label> Field Type <span class="text-danger">*</span></label>
                                    <select class="select2 form-control w-100" id="field_type" name="field_type">
                                        <option value="">Select</option>
                                        <option value="TextBox">Text Box</option>
                                        <option value="Date">Text Box - Date</option>
                                    </select>
                                </div>
                                <div class="col-12 mt-3">
                                    <label> Mandatory <span class="text-danger">*</span></label>
                                    <select class="select2 form-control w-100" id="field_mandatory" name="field_mandatory">
                                        <option value="Yes">Yes</option>
                                        <option value="No">No</option>
                                    </select>
                                </div>
                            </div>
                           

                        </div>
                        <input type="hidden" name="CorporateID" value="<?php echo $CorporateID;?>">
                        <input type="hidden" id="form_action" name="form_action" value="add" />
                        <input type="hidden" id="form_id" name="form_id" value="-1" />
                        <button class="btn btn-primary" id="form_configuration_field_btn" onclick="return SaveFormField()">Save</button>
                    </form>
                </div>

            </div>
        </div>
    </div>


<div class="modal fade" id="add_update_form_circle" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header modal_header">
                    <h5 class="modal-title" id="company_modal_title"> Create/Update Circle </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form METHOD="POST" id="add_update_circle_form" onsubmit="return false;">
                        <div class="form-group">
                            <div class="row">
                                <div class="col-12">
                                    <label>Circle Name <span class="text-danger">*</span> </label>
                                    <input type="text" class="form-control" name="circle_name" id="circle_name" placeholder="Enter Circle Name">
                                </div>
                                
                            </div>
                           

                        </div>
                        <input type="hidden" name="CorporateID" value="<?php echo $CorporateID;?>">
                        <input type="hidden" id="form_circle_action" name="form_action" value="add" />
                        <input type="hidden" id="form_circle_id" name="form_id" value="-1" />
                        <button class="btn btn-primary" id="form_circle_btn" onclick="return SaveCircle()">Save</button>
                    </form>
                </div>

            </div>
        </div>
    </div>