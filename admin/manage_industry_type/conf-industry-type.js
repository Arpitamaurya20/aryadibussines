
function addIndustryType() {
    $('#industryTypeModalLabel').text('Add Industry Type');
    $('#add_industry_type_form')[0].reset();
    $('#industry_type_id').val('');
    $('#addIndustryTypeModal').modal('show');
}

// Open modal for Edit — called from DataTable row button
function editIndustryType(id, name, isActive) {
    $('#industryTypeModalLabel').text('Edit Industry Type');
    $('#industry_type_id').val(id);
    $('#industry_type_name').val(name);
    $('#is_active').val(isActive);
    $('#addIndustryTypeModal').modal('show');
}

// Save (Add or Edit)
function save_industry_type() {
    var name    = $.trim($('#industry_type_name').val());
    var id      = $('#industry_type_id').val();
    var isActive = $('#is_active').val();

    if (name === '') {
        alert('Please enter Industry Type Name.');
        return false;
    }

    $.ajax({
        url: 'include/industry-type-action.php',
        type: 'POST',
        data: {
            action: id === '' ? 'add' : 'edit',
            industry_type_id: id,
            industry_type_name: name,
            is_active: isActive
        },
        success: function (response) {
            var res = $.parseJSON(response);
            if (res.status === 'success') {
                $('#addIndustryTypeModal').modal('hide');
                $('#view-industry-type-data').DataTable().ajax.reload(null, false);
                alert(res.message);
            } else {
                alert(res.message);
            }
        },
        error: function () {
            alert('Something went wrong. Please try again.');
        }
    });

    return false;
}

// Delete
function deleteIndustryType(id) {
    if (!confirm('Are you sure you want to delete this Industry Type?')) return;

    $.ajax({
        url: 'include/industry-type-action.php',
        type: 'POST',
        data: {
            action: 'delete',
            industry_type_id: id
        },
        success: function (response) {
            var res = $.parseJSON(response);
            if (res.status === 'success') {
                $('#view-industry-type-data').DataTable().ajax.reload(null, false);
                alert(res.message);
            } else {
                alert(res.message);
            }
        },
        error: function () {
            alert('Something went wrong. Please try again.');
        }
    });
}

// Export
function ExportIndustryTypeData() {
    window.location.href = 'include/industry-type-export.php';
}