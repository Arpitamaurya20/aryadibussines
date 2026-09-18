(function ($) {
  'use strict';

  window.hcOpenAssignmentModal = function () {
    $('#hc_edit_assignment_modal').modal('show');
    if ($('#hc_assign_employee').data('select2')) {
      $('#hc_assign_employee').select2('destroy');
    }
    if ($('#hc_ticket_status').data('select2')) {
      $('#hc_ticket_status').select2('destroy');
    }
    $('#hc_assign_employee').select2({
      placeholder: 'Search technician',
      allowClear: true,
      width: '100%',
      dropdownParent: $('#hc_edit_assignment_modal .modal-content')
    });
    $('#hc_ticket_status').select2({
      width: '100%',
      dropdownParent: $('#hc_edit_assignment_modal .modal-content')
    });
    $('#hc_ticket_status').off('change.hcReassign').on('change.hcReassign', hcOnStatusChange);
    hcOnStatusChange();
    if ($('#hc_due_date').data('datepicker')) {
      $('#hc_due_date').datepicker('destroy');
    }
    $('#hc_due_date').datepicker({
      format: 'yyyy-mm-dd',
      todayBtn: 'linked',
      clearBtn: true,
      todayHighlight: true,
      autoclose: true
    });
  };

  function hcOnStatusChange() {
    var isReassign = $('#hc_ticket_status').val() === '__REASSIGN__';
    $('#hc_is_reassign').val(isReassign ? '1' : '0');
    if (isReassign) {
      $('#hc_ticket_status').next('.select2-container').css('opacity', '0.85');
    }
  }

  window.hcSaveAssignment = function () {
    var assigned = $('#hc_assign_employee').val();
    if (!assigned || assigned === '') {
      if (typeof TechXAlert === 'function') {
        TechXAlert('Please assign a technician.');
      } else {
        alert('Please assign a technician.');
      }
      return;
    }

    var isReassign = $('#hc_ticket_status').val() === '__REASSIGN__';
    var formData = $('#hc_assignment_form').serialize();
    if (isReassign) {
      formData += '&is_reassign=1';
    }

    var $btn = $('#hc_assignment_save_btn');
    $btn.prop('disabled', true).text('Please wait...');

    $.ajax({
      url: 'action/save-assignment.php',
      type: 'POST',
      dataType: 'json',
      data: formData,
      success: function (res) {
        $btn.prop('disabled', false).text('Save & Change');
        if (typeof TechXAlert === 'function') {
          TechXAlert(res.message || (res.error ? 'Update failed.' : 'Saved.'));
        } else {
          alert(res.message || (res.error ? 'Update failed.' : 'Saved.'));
        }
        if (!res.error) {
          window.location.reload();
        }
      },
      error: function () {
        $btn.prop('disabled', false).text('Save & Change');
        if (typeof TechXAlert === 'function') {
          TechXAlert('Server error. Please try again.');
        } else {
          alert('Server error. Please try again.');
        }
      }
    });
  };
})(jQuery);
