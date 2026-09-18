<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <?php
		include('../controllers/common_controllers.php');
        require_once('../includes/autoloader.inc.php');
        $UserType = SessionCheck();
		$conn = _connectodb();
        setNavigation($_SESSION['Roles']);
		//$total_projects = getTotatProjects($conn,$Enterpriseid);
        $categories_obj = new Categories($conn);
        $categories_array = $categories_obj->getAllCategories();
        $core = new Core();
        $uom_array = $core->_getTableRecords($conn,'manage_uom',' where 1');
        $nav = 0;
        if(isset($_GET['nav']))
        {
            $nav = 1;
        }

		?>
    <meta charset="utf-8">
    
    <meta name="description" content="View Rate Card">
    <?php
        include('../includes/common_head_content.php');
        ?>
    <link rel="stylesheet" media="screen, print" href="../css/datagrid/datatables/datatables.bundle.css">
    <?php
    
    $corporate_user = false;
    $corporate_account_admin = false;
    $CorporateID = -1;
    $BranchID = -1;
    $CompanyID = -1;
    $TicketManager = false;
    if($UserType == "Corporate Admin")
    {
        $corporate_user = true;
        $corporate_account_admin = true;
        $CorporateID = $CompanyID = $_SESSION['Roles']['CorporateID'];
    }
    if($UserType == "Corporate Branch User")
    {
        $corporate_user = true;
        $CorporateID = $CompanyID = $_SESSION['Roles']['CorporateID'];
        $BranchID = $_SESSION['Roles']['BranchID'];
    }
    if(isset($_SESSION['Roles']['EmployeeRoles']))
    {
        $EmployeeRoles = $_SESSION['Roles']['EmployeeRoles'];
        foreach($EmployeeRoles as $E_Role)
        {
            if($E_Role == "Ticket Manager")
            {
                $TicketManager = true;
                $UserType = "Ticket Manager";
            }
        }
    }

    if(!$corporate_user)
    {
        $CompanyID = -1;
    }
    if(isset($_SESSION['CompanyID']))
    {
        $CorporateID = $CompanyID = $_SESSION['CompanyID'];
    }
    $filter_param = "?nav=".$nav."&CompanyID=".$CompanyID."&UserType=".$UserType;
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
    <style>
    .modal_header {
        background-color: #003f88;
        color: #fff;
    }

    .modal_header button {
        opacity: 1;
        color: #fff;
    }

    .edit_header {
        background-color: #027dc1;
        color: #fff;
    }

    .form_submit {
        background-color: #2196f3;
        color: #fff;
        border: none;
        border-radius: 4px;
    }

    .edit_header .close {
        opacity: 1 !important;
        color: #fff;

    }

    .tab_modal_heading h2 {
        font-size: 18px;
        text-align: center;
        color: #fff;
        font-weight: 500;
        margin-bottom: 20px;
    }
    .select2-container--readonly .select2-selection {
      background-color: #e9ecef;
      cursor: not-allowed;
    }

    </style>
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
    <title>
        Manage Rate Card - <?=$ProductName;?>
    </title>
    
</head>


<body class="mod-bg-1 desktop chrome webkit pace-done nav-function-fixed blur">
    <!-- DOC: script to save and load page settings -->
    <?php include('../js/theme_settings.js'); ?>
    <!-- BEGIN Page Wrapper -->
    <input type="hidden" id="user_access" value="<?php echo $UserType; ?>" />
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
                    <div class="d-flex justify-content-between mb-3 align-items-center">
                    <ol class="breadcrumb page-breadcrumb">
                        <li class="breadcrumb-item"><a href="../dashboard/admin_dashboard"><?=$ProductName;?></a></li>
                        <?php
                        if($CompanyID == -1)
                        {
                            ?>
                        <li class="breadcrumb-item active"><a href="../company/view-company">Manage Corporates Accounts</a>
                        </li>
                        <?php
                        }
                        ?>
                        <li class="breadcrumb-item active">Manage Rate Card</li>

                    </ol>   
                        <?php
                            if($UserType == "Admin"||$UserType == "Sub Admin"){
                        ?>
                        <!--a href="#" onclick="DownloadBranchFileFormat()" class="btn btn-success"
                            style="margin-right:20px;">Download Template for Bulk Upload</a-->
                        <?php } ?>
                    </div>

                                

                    <!-- Page Container -->
                    <div class="row">
                        <div class="col-xl-12">
                            <div id="panel-1" class="panel">
                                <div class="panel-hdr">
                                    <h2>
                                        View Rate Card
                                    </h2>
                                    <button type="button" onclick="FilterRateCard();" class="btn btn-sm btn-primary ml-3 mr-3">Search</button>
                                    <!--a href="#" onclick="OpenCSVmodal()" class="btn btn-info"
                                        style="margin-right:20px;">Upload CSV File</a-->
                                    <?php 
                                    if($UserType == "Admin" || $TicketManager || $UserType == "Corporate Admin")
                                    {
                                    ?>
                                    <!--a href="#" onclick="ExportBranchData()" class="btn btn-info"
                                        style="margin-right:20px;">Export Data</a-->
                                    <?php
                                    }
                                    if($UserType == "Admin" || $TicketManager)
                                    {
                                    ?>
                                    <a href="#" onclick="openRateCard_modal()" class="btn btn-info" style="margin-right:20px;">Add Line Item</a>
                                        
                                    <?php 
                                    } 
                                    ?>

                                </div>
                               
                                <div class="row">
                                    <div class="col-xl-12">
                                        
                                        <div class="panel-hdr">
                                            <div class="col-2" style="width:100%;z-index: 1!important;">
                                                <select class="form-control" name="filter_type" id="filter_type">
                                                    <option value="">Select Type</option>
                                                    <option value="Product">Product</option>
                                                    <option value="Service">Service</option>
                                                </select>
                                            </div>
                                            <div class="col-2" style="width:100%;z-index: 1!important;">
                                                <select class="form-control" name="filter_category" id="filter_category" onchange="GetFilterSubCategories(this.value)">
                                                    <option value="">Select Category</option>
                                                    <?php
                                                    foreach ($categories_array as $category) 
                                                    {
                                                        $CategoryName = $category['CategoriesName'];
                                                    ?>

                                                        <option value="<?php echo $CategoryName ?>" data-filter-category-id="<?php echo $category['ID']; ?>"> <?php echo $CategoryName ?></option>

                                                    <?php 
                                                    }  
                                                    ?>
                                                </select>
                                            </div>
                                            <div class="col-2" id="subcategory_div" style="width:100%;z-index: 1!important;">
                                                <select class="form-control" name="filter_subcategory" id="filter_subcategory">
                                                    <option value="">Select Sub Category</option>
                                                </select>
                                            </div>
                                            <input type="hidden" id="nav" value="<?php echo $nav; ?>" />
                                            <input type="hidden" id="CorporateID" value="<?php echo $CorporateID; ?>" />
                                            <input type="hidden" id="UserType" value="<?php echo $UserType; ?>" />
                                            
                                        </div>

                                    </div> <!-- col-xl-12 -->
                                </div> <!-- row -->
                                <!-- Filters -->
                               
                                
                                 <div class="panel-container show">
                                    <div class="panel-content">
                                        <!-- datatable start -->
                                        <table id="view-rate-card" class="table table-bordered table-hover table-striped w-100">
                                            
                                            <thead>
                                                <tr>
                                                    <th>#</th>
                                                    <th>Company Account</th>
                                                    <th>Type</th>
                                                    <th>Category / Sub Category </th>
                                                    <th>Line Item</th>
                                                    <th>Make</th>
                                                    <th>HSN</th>
                                                    <th>ARC Code</th>
                                                    <th>UoM</th>
                                                    <th>Price</th>
                                                    <th>Tax</th>
                                                    <?php

                                                    if($UserType == "Admin" || $TicketManager)
                                                    {
                                                    ?>
                                                        <th>Update </th>
                                                        <?php
                                                        if($UserType == "Admin" || $TicketManager)
                                                        {
                                                            ?>
                                                            <th>Action</th>
                                                            <?php
                                                        }
                                                        
                                                    }
                                                    ?>
                                                </tr>
                                            </thead>

                                        </table>
                                        
                                        <!-- datatable end -->
                                    </div>
                                </div>

                            </div>
                            <!-- panel-1 -->
                        </div><!-- col-xl-12 -->
                    </div> <!-- row -->
                    <!-- Datatable Container -->


                  

                     <!-- upload csv modal             -->

                    <div class="modal fade" id="upload_csv" role="dialog"
                        aria-labelledby="exampleModalLabel" aria-hidden="true">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">
                                <div class="modal-header pb-0 edit_header">
                                    <div class="tab_modal_heading">
                                        <h2>Upload Branch CSV </h2>
                                    </div>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <div class="modal-body">
                                    <div class="reset_password">
                                       <form id="uplaod_branch_csv">
                                            <input type="file" class="form-control" name="csvFile" accept=".csv">
                                            

                                            <div class="row justify-content-center mt-3">

                                                <a class="btn-info btn form_submit pl-4 pr-4 pt-2 pb-2 text-white" id="upload_csv_btn"
                                                    onclick="UploadBranch_CSV()">Upload</a>

                                            </div>
                                        </form>
                                    </div>
                                </div>

                            </div>
                        </div>
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
    <script src="../js/datagrid/datatables/datatables.bundle.js"></script>
    <script src="../js/modules/rate-card.js"></script>
       <script>
        $(document).ready(function() {
            var i = 1;
            $('#view-rate-card').dataTable({
                 responsive: true,
                'processing': true,
                'serverSide': true,
                'ordering': false,
                'serverMethod': 'post',
                'ajax': {
                    'url': 'action/rate-card-list-post.php<?=$filter_param;?>'
                },
                'columnDefs': [{
                    "targets": [0],
                    "className": "text-center"
                }],
                "order": [
                    [1, 'asc']
                ],
                'columns': [{
                        "data": "id",
                        render: function(data, type, row, meta) {
                            return meta.row + meta.settings._iDisplayStart + 1;
                        }
                    },
                    {
                        data: 'CompanyName'
                    },
                    {
                        data: 'Type'
                    },
                    {
                        data: 'Category_SubCategory'
                    },
                    {
                        data: 'LineItemName'
                    },
                    {
                        data: 'Make'
                    },
                    {
                        data: 'HSN'
                    },
                    {
                        data: 'ARCCode'
                    },
                    {
                        data: 'UoM'
                    },
                    {
                        data: 'Price'
                    },
                    {
                        data: 'Tax'
                    },
                    <?php 
                    if($UserType == "Admin" || $TicketManager)
                    {
                        ?>
                        {
                            data: 'Update'
                        },
                        <?php
                        if($UserType == "Admin" || $TicketManager)
                        { 
                        ?>
                            {
                                data: 'Action'
                            },
                        <?php
                        }

                    }
                    ?>
                ]


            });

            $("#filter_category").select2();
            <?php 
            if($nav)
            {
                ?>
                $("#js-nav-menu").addClass("active");
                $("#nav_ticket_rate_card").addClass("active");
                <?php
            }
            else
            {
                ?>
                $("#js-nav-menu").addClass("active");
                $("#nav_corporate").addClass("open");
                $("#nav_company").addClass("active");
                <?php
            }
            ?>
        });

        $(document).ready(function() {
    // Add new row
    $('#addRow').click(function() {
        var newRow = `<tr>
                        <td>
                            <select class="form-control" name="type[]">
                                <option value="">Please Select</option>
                                <option value="Product">Product</option>
                                <option value="Service">Service</option>
                            </select>
                        </td>
                        <td>
                            <select class="form-control category-dropdown" name="category[]">
                                <option value="">Please Select</option>
                                <?php 
                                foreach($categories_array as $category)
                                {
                                    $CategoryName = $category['CategoriesName'];
                                    ?>
                                    <option value="<?=$CategoryName;?>" data-id="<?php echo $category['ID']; ?>"><?=$CategoryName;?></option>
                                    <?php
                                }
                                ?>
                                <option value="Others">Others</option>
                            </select>
                        </td>
                        <td>
                            <select class="form-control subcategory-dropdown" name="subcategory[]">
                                <option value="">Please Select</option>
                                <!-- Subcategories will be dynamically populated -->
                            </select>
                        </td>
                        <td><input type="text" class="form-control" name="lineItemName[]"></td>
                        <td><input type="text" class="form-control" name="make[]"></td>
                        <td><input type="text" class="form-control" name="hsn[]"></td>
                        <td><input type="text" class="form-control" name="arccode[]"></td>
                        <td>
                            <select class="form-control" name="uom[]">
                                <option value="">Please Select</option>
                                <?php 
                                foreach($uom_array as $uom)
                                {
                                    $UOMName = $uom['UOMName'];
                                    ?>
                                    <option value="<?=$UOMName;?>"><?=$UOMName;?></option>
                                    <?php
                                }
                                ?>
                                <option value="Others">Others</option>
                            </select>
                        </td>
                        <td><input type="text" class="form-control" name="price[]"></td>
                        <td><input type="text" class="form-control" name="tax[]"></td>
                        <td><button type="button" class="btn btn-danger remove-row">Remove</button></td>
                      </tr>`;
        $('#dynamicTable tbody').append(newRow);
    });

    // Remove row
    $(document).on('click', '.remove-row', function() {
        $(this).closest('tr').remove();
    });

    // Handle category change
    $(document).on('change', '.category-dropdown', function() {
        var category_id = $(this).find('option:selected').data('id');
        var subcategoryDropdown = $(this).closest('tr').find('.subcategory-dropdown');
        subcategoryDropdown.empty();
        subcategoryDropdown.append('<option value="">Please Select</option>');
        
        $.post("action/get_subcategories_rate_card.php", {
            CategoryID: category_id
        },
        function(data, status) {
            subcategoryDropdown.append(data);
            subcategoryDropdown.append('<option value="Others">Others</option>');
        });
    });

    // Save rows
    $('#saveRows').click(function() {
        var data = [];
        var isValid = true;
        $('#dynamicTable tbody tr').each(function() {
            var row = {
                type: $(this).find('select[name="type[]"]').val(),
                category: $(this).find('select[name="category[]"]').val(),
                category_id: $(this).find('select[name="category[]"] option:selected').data('id'),
                subcategory: $(this).find('select[name="subcategory[]"]').val(),
                lineItemName: $(this).find('input[name="lineItemName[]"]').val(),
                make: $(this).find('input[name="make[]"]').val(),
                hsn: $(this).find('input[name="hsn[]"]').val(),
                arccode: $(this).find('input[name="arccode[]"]').val(),
                uom: $(this).find('select[name="uom[]"]').val(),
                price: $(this).find('input[name="price[]"]').val(),
                tax: $(this).find('input[name="tax[]"]').val()
            };
            if (row.category === "" || row.lineItemName === "" || row.make === "" || row.price === "") {
                isValid = false;
                return false; // Exit the .each() loop
            }
            data.push(row);
        });

        if (!isValid) 
        {
            alert("Please fill out all required fields (Category, Line Item Name, Make, Price) in each row.");
            return;
        }

        $.ajax({
            url: 'action/insert_rate_card.php',
            type: 'POST',
            data: JSON.stringify(data),
            contentType: 'application/json; charset=utf-8',
            success: function(response) {
                data_response = JSON.parse(response);
                TechXAlert(data_response.message);
                if(data_response.error == false)
                {
                   
                    $('#rateCardModal').modal('hide');
                    $('#view-rate-card').DataTable().ajax.reload();
                }
                else
                {

                }
                // Optionally, you can refresh the page or update the UI to reflect the new data
            },
            error: function(error) {
                console.log('Error:', error);
            }
        });
    });
});

    </script>
</body>


</html>


 <div class="modal fade" id="rateCardModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
        <div class="modal-dialog  modal-xl" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="myModalLabel">Add Line Items</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <table class="table" id="dynamicTable">
                        <thead>
                        <tr>
                            <th>Type</th>
                            <th>Category</th>
                            <th>SubCategory</th>
                            <th>LineItemName</th>
                            <th>Make</th>
                            <th>HSN</th>
                            <th>ARC Code</th>
                            <th>UoM</th>
                            <th>Price</th>
                            <th>Tax</th>
                            <th>Action</th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr>
                            <td>
                                <select class="form-control" name="type[]">
                                    <option value="">Please Select</option>
                                    <option value="Product">Product</option>
                                    <option value="Service">Service</option>
                                    <!-- Add more options as needed -->
                                </select>
                            </td>
                            <td>
                                <select class="form-control category-dropdown" name="category[]">
                                    <option value="">Please Select</option>
                                    <?php 
                                    foreach($categories_array as $category)
                                    {
                                        $CategoryName = $category['CategoriesName'];
                                        ?>
                                        <option value="<?=$CategoryName;?>" data-id="<?php echo $category['ID']; ?>"><?=$CategoryName;?></option>
                                        <?php
                                    }
                                    ?>
                                    <option value="Others">Others</option>
                                    <!-- Add more options as needed -->
                                </select>
                            </td>
                            <td>
                                <select class="form-control subcategory-dropdown" name="subcategory[]">
                                    <option value="">Please Select</option>
                                    
                                    <!-- Add more options as needed -->
                                </select>
                            </td>
                            <td><input type="text" class="form-control" name="lineItemName[]"></td>
                            <td><input type="text" class="form-control" name="make[]"></td>
                            <td><input type="text" class="form-control" name="hsn[]"></td>
                            <td><input type="text" class="form-control" name="arccode[]"></td>
                            <td>
                                <select class="form-control" name="uom[]">
                                    <option value="">Please Select</option>
                                    <?php 
                                    foreach($uom_array as $uom)
                                    {
                                        $UOMName = $uom['UOMName'];
                                        ?>
                                        <option value="<?=$UOMName;?>"><?=$UOMName;?></option>
                                        <?php
                                    }
                                    ?>
                                    <option value="Others">Others</option>
                                </select>
                            </td>
                            <td><input type="text" class="form-control" name="price[]"></td>
                            <td><input type="text" class="form-control" name="tax[]"></td>
                            <td><button type="button" class="btn btn-danger remove-row">Remove</button></td>
                        </tr>
                        </tbody>
                    </table>
                    <button type="button" class="btn btn-success" id="addRow">Add Row</button>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="saveRows">Save</button>
                </div>
            </div>
        </div>
    </div>



    <!-- Edit Rate Card Modal -->
<div class="modal fade" id="editRateCardModal" tabindex="-1" role="dialog" aria-labelledby="editModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="editModalLabel">Edit Rate Card</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <form id="editRateCardForm">
          <input type="hidden" name="ID" id="editID">
          <div class="form-row">
            <div class="form-group col-md-4">
              <label>Type</label>
              <select class="form-control" name="Type" id="editType">
                <option value="Product">Product</option>
                <option value="Service">Service</option>
              </select>
            </div>
            <div class="form-group col-md-4">
              <label>Category</label>
              <select class="form-control" name="Category" id="editCategory">
                <option value="">Please Select</option>
                <?php foreach($categories_array as $category): ?>
                  <option value="<?= $category['CategoriesName']; ?>" data-id="<?= $category['ID']; ?>">
                    <?= $category['CategoriesName']; ?>
                  </option>
                <?php endforeach; ?>
                <option value="Others">Others</option>
              </select>
            </div>
            <div class="form-group col-md-4">
              <label>SubCategory</label>
              <select class="form-control" name="SubCategory" id="editSubCategory">
                <option value="">Please Select</option>
              </select>
            </div>
          </div>

          <div class="form-row">
            <div class="form-group col-md-6">
              <label>Line Item Name</label>
              <input type="text" class="form-control" name="LineItemName" id="editLineItemName">
            </div>
            <div class="form-group col-md-6">
              <label>Make</label>
              <input type="text" class="form-control" name="Make" id="editMake">
            </div>
          </div>

          <div class="form-row">
            <div class="form-group col-md-4">
              <label>HSN</label>
              <input type="text" class="form-control" name="HSN" id="editHSN">
            </div>
            <div class="form-group col-md-4">
              <label>ARC Code</label>
              <input type="text" class="form-control" name="ARCCode" id="editARCCode">
            </div>
            <div class="form-group col-md-4">
              <label>UoM</label>
              <select class="form-control" name="UoM" id="editUoM">
                <option value="">Please Select</option>
                <?php foreach($uom_array as $uom): ?>
                  <option value="<?= $uom['UOMName']; ?>"><?= $uom['UOMName']; ?></option>
                <?php endforeach; ?>
                <option value="Others">Others</option>
              </select>
            </div>
          </div>

          <div class="form-row">
            <div class="form-group col-md-6">
              <label>Price</label>
              <input type="text" class="form-control" name="Price" id="editPrice">
            </div>
            <div class="form-group col-md-6">
              <label>Tax</label>
              <input type="text" class="form-control" name="Tax" id="editTax">
            </div>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary" id="updateRateCardBtn">Update</button>
      </div>
    </div>
  </div>
</div>
