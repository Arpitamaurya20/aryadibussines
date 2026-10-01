function openRateCard_modal() 
{  
  $("#rateCardModal").modal();
}
function DeleteRateCard(RateCardID)
{
  alertify.confirm(
    "TechXpert ",
    "Do you really want to delete Rate Card?",
    function () {
      $.post(
        "action/delete_rate_card.php",
        {
          ID: RateCardID,
        },
        function (data, status) {
          var response = JSON.parse(data);
          TechXAlert(response.message);
          if (response.error == false) {
            setInterval(function () {
              location.reload();
            }, 2000);
          }
        }
      );
    },
    function () {
      alertify.error("Deletion Cancelled");
    }
  );
}

function GetFilterSubCategories(CategoryName)
{
  var selectElement = document.getElementById('filter_category');
  var selectedOption = selectElement.options[selectElement.selectedIndex];
  var categoryId = selectedOption.getAttribute('data-filter-category-id');
  $.post("action/get_subcategories_filter.php",
  {
    "CategoryID":categoryId
  },
  function(data,status)
  {
    document.getElementById("subcategory_div").innerHTML = data;
    $("#filter_subcategory").select2();
  })
}

function FilterRateCard()
{
    
    var table = $('#view-rate-card').DataTable();
    table.destroy();
    var param = "";
    var nav = document.getElementById("nav").value;
    var CorporateID = document.getElementById("CorporateID").value;
    var UserType = document.getElementById("UserType").value;
    param = "nav="+nav+"&CompanyID="+CorporateID+"&UserType="+UserType;
    var typeObject = document.getElementById("filter_type");
    if(typeObject !== null)
    {
        var filter_type = document.getElementById("filter_type").value;
        param = param+"&type="+filter_type;
    }
    
    var categoryObject = document.getElementById("filter_category");
    if(categoryObject !== null)
    {
        var filter_category = document.getElementById("filter_category").value;
        param = param+"&category="+filter_category;
    }
    
    var subcategoryObject = document.getElementById("filter_subcategory");
    if(subcategoryObject !== null)
    {
        var filter_subcategory = document.getElementById("filter_subcategory").value;
        param = param+"&subcategory="+filter_subcategory;
    }

    var i = 1;
    var columns = [
        {
            "data": "id",
            render: function(data, type, row, meta) {
                return meta.row + meta.settings._iDisplayStart + 1;
            }
        },
        { data: 'CompanyName' },
        { data: 'Type' },
        { data: 'Category_SubCategory' },
        { data: 'LineItemName' },
        { data: 'Make' },
        { data: 'HSN' },
        { data: 'ARCCode' },
        { data: 'UoM' },
        { data: 'Price' },
        { data: 'Tax' }
    ];
    if(UserType === "Admin" || UserType === "TicketManager") {
        columns.push({ data: 'Update' });
        if (UserType === "Admin") {
            columns.push({ data: 'Action' });
        }
    }
    $('#view-rate-card').dataTable({
         responsive: true,
        'processing': true,
        'serverSide': true,
        'ordering': false,
        'serverMethod': 'post',
        'ajax': {
            'url': 'action/rate-card-list-post.php?'+param
        },
        'columnDefs': [{
            "targets": [0],
            "className": "text-center"
        }],
        "order": [
            [1, 'asc']
        ],
        'columns': columns


    });
}

function findRateCardCategoryOption(selectEl, categoryName) {
    var name = (categoryName || '').toString().trim().toLowerCase();
    if (!selectEl || !name) {
        return null;
    }
    for (var i = 0; i < selectEl.options.length; i++) {
        var opt = selectEl.options[i];
        var val = (opt.value || '').toString().trim().toLowerCase();
        var text = (opt.text || '').toString().trim().toLowerCase();
        var id = (opt.getAttribute('data-id') || '').toString().trim().toLowerCase();
        if (val === name || text === name || id === name) {
            return opt;
        }
    }
    return null;
}

function setEditSubCategoryOptions(html, selectedSubCategory) {
    var subSelect = document.getElementById('editSubCategory');
    if (!subSelect) {
        return;
    }
    var optionsHtml = html || '<option value="">Please Select</option>';
    var $parsed = $('<div>').html(html);
    if ($parsed.find('select').length) {
        optionsHtml = $parsed.find('select').html();
    }
    subSelect.innerHTML = optionsHtml;
    if (selectedSubCategory) {
        var matched = false;
        var want = selectedSubCategory.toString().trim().toLowerCase();
        $('#editSubCategory option').each(function () {
            if (($(this).val() || '').toString().trim().toLowerCase() === want) {
                $('#editSubCategory').val($(this).val());
                matched = true;
                return false;
            }
        });
        if (!matched) {
            $('#editSubCategory').append($('<option>', {
                value: selectedSubCategory,
                text: selectedSubCategory,
                selected: true
            }));
        }
    }
}

function UpdateRateCard(ID){
    $.ajax({
        url: './action/fetch_rate_card.php',
        type: 'POST',
        data: {ID: ID},
        dataType: 'json',
        success: function(res){
            if (!res || res.error) {
                TechXAlert((res && res.error) ? res.error : 'Unable to load rate card.');
                return;
            }

            $('#editID').val(res.ID);
            $('#editType').val(res.Type);
            $('#editLineItemName').val(res.LineItemName);
            $('#editMake').val(res.Make);
            $('#editHSN').val(res.HSN);
            $('#editARCCode').val(res.ARCCode);
            $('#editUoM').val(res.UoM);
            $('#editPrice').val(res.Price);
            $('#editTax').val(res.Tax);

            GetEditSubCategories(res.Category, res.SubCategory);
            $('#editRateCardModal').modal('show');
        },
        error: function () {
            TechXAlert('Unable to load rate card.');
        }
    });
}


function GetEditSubCategories(categoryName, selectedSubCategory){
    selectedSubCategory = selectedSubCategory || '';
    var selectElement = document.getElementById('editCategory');
    if (!selectElement) {
        return;
    }

    var selectedOption = findRateCardCategoryOption(selectElement, categoryName);
    if (!selectedOption && categoryName) {
        selectedOption = new Option(categoryName, categoryName, true, true);
        selectElement.add(selectedOption);
    }

    if (selectedOption) {
        selectedOption.selected = true;
        $('#editCategory').val(selectedOption.value);
    } else {
        selectElement.selectedIndex = 0;
    }

    var categoryId = selectedOption ? (selectedOption.getAttribute('data-id') || '') : '';
    if (!categoryId) {
        setEditSubCategoryOptions(
            '<option value="">Please Select</option><option value="Others">Others</option>',
            selectedSubCategory
        );
        return;
    }

    $.post("action/get_subcategories_filter.php", {
        "CategoryID": categoryId
    }, function(data){
        setEditSubCategoryOptions(data, selectedSubCategory);
    });
}


$(document).on('change', '#editCategory', function(){
    GetEditSubCategories($(this).val());
});


$(document).ready(function () {
    $(document).on('click', '#updateRateCardBtn', function () {
        

        let payload = {
            ID: $('#editID').val(),
            type: $('#editType').val(),
            category: $('#editCategory').val(),
            subcategory: $('#editSubCategory').val(),
            lineItemName: $('#editLineItemName').val(),
            make: $('#editMake').val(),
            hsn: $('#editHSN').val(),
            arccode: $('#editARCCode').val(),
            uom: $('#editUoM').val(),
            price: $('#editPrice').val(),
            tax: $('#editTax').val()
        };

        $.ajax({
            url: "action/update_rate_card.php",
            type: "POST",
            data: JSON.stringify(payload),
            contentType: "application/json",
            success: function (res) {
                let response = JSON.parse(res);
                if (!response.error) {
                    TechXAlert("Updated successfully!");
                    $('#editRateCardModal').modal('hide');
                    $('#view-rate-card').DataTable().ajax.reload();
                } else {
                    TechXAlert("Error: " + response.message);
                }
            },
            error: function (xhr, status, error) {
                console.error("AJAX Error:", error);
            }
        });
    });
});
