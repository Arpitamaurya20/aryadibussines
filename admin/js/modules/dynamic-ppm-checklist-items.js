function dppmBuildItemRowHtml(rowIndex, inputTypes) {
    var optionsHtml = '';
    inputTypes.forEach(function (type) {
        optionsHtml += '<option value="' + type + '"' + (type === 'text' ? ' selected' : '') + '>' + type + '</option>';
    });
    return '<tr class="dppm-item-row">' +
        '<td><input type="text" class="form-control form-control-sm item-name" placeholder="Item name"></td>' +
        '<td><input type="text" class="form-control form-control-sm item-code" placeholder="P-001"></td>' +
        '<td><select class="form-control form-control-sm item-input-type">' + optionsHtml + '</select></td>' +
        '<td><input type="text" class="form-control form-control-sm item-unit"></td>' +
        '<td><input type="text" class="form-control form-control-sm item-default-value" placeholder="Default value"></td>' +
        '<td><select class="form-control form-control-sm item-mandatory"><option value="0" selected>No</option><option value="1">Yes</option></select></td>' +
        '<td><input type="text" class="form-control form-control-sm item-options" placeholder="OK,Not OK"></td>' +
        '<td><input type="number" class="form-control form-control-sm item-sort" value="' + rowIndex + '"></td>' +
        '<td><button type="button" class="btn btn-xs btn-danger dppm-remove-item-row">&times;</button></td>' +
        '</tr>';
}

function dppmInitBulkItemForm(formSelector, inputTypes) {
    var $form = $(formSelector);
    if (!$form.length) {
        return;
    }

    function addRows(count) {
        var $tbody = $form.find('.dppm-item-rows');
        var start = $tbody.find('.dppm-item-row').length + 1;
        for (var i = 0; i < count; i++) {
            $tbody.append(dppmBuildItemRowHtml(start + i, inputTypes));
        }
    }

    if ($form.find('.dppm-item-row').length === 0) {
        addRows(3);
    }

    $form.on('click', '.dppm-add-item-row', function () {
        addRows(1);
    });
    $form.on('click', '.dppm-add-item-rows-5', function () {
        addRows(5);
    });
    $form.on('click', '.dppm-remove-item-row', function () {
        $(this).closest('.dppm-item-row').remove();
    });

    $form.on('submit', function () {
        var items = [];
        $form.find('.dppm-item-row').each(function (idx) {
            var name = $.trim($(this).find('.item-name').val());
            if (name === '') {
                return;
            }
            items.push({
                ItemName: name,
                ItemCode: $.trim($(this).find('.item-code').val()),
                InputType: $(this).find('.item-input-type').val(),
                UnitName: $.trim($(this).find('.item-unit').val()),
                DefaultValue: $.trim($(this).find('.item-default-value').val()),
                IsMandatory: $(this).find('.item-mandatory').val(),
                OptionsJson: $.trim($(this).find('.item-options').val()),
                SortOrder: $(this).find('.item-sort').val() || (idx + 1)
            });
        });
        $form.find('.dppm-items-json').val(JSON.stringify(items));
    });
}
