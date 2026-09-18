(function ($) {
  'use strict';

  function toggleTimelineCustom() {
    var isCustom = $('#hc_timeline_type').val() === 'custom';
    $('#hc_timeline_custom_wrap').toggle(isCustom);
    $('#hc_timeline_custom_at').prop('required', isCustom);
  }

  function togglePurposeContact() {
    var purpose = $('#hc_purpose').val();
    var show = purpose === 'rent' || purpose === 'self';
    $('#hc_purpose_contact_wrap').toggle(show);
    var required = show;
    $('#hc_purpose_name, #hc_purpose_phone, #hc_purpose_address').prop('required', required);
    if (purpose === 'rent') {
      $('#hc_purpose_hint').text('Rent: enter tenant / property contact details.');
    } else if (purpose === 'self') {
      $('#hc_purpose_hint').text('Self: enter your own service contact at the site.');
    } else {
      $('#hc_purpose_hint').text('');
    }
  }

  function initHcStateSelect() {
    var $state = $('#hc_state_id');
    if (!$state.length) {
      return;
    }
    if ($state.data('select2')) {
      $state.select2('destroy');
    }
    $state.select2({
      placeholder: 'Search & select state',
      allowClear: true,
      width: '100%',
      dropdownParent: $('#hc_raise_ticket_modal .modal-content')
    });
  }

  function resetHcForm() {
    var form = document.getElementById('hc_raise_ticket_form');
    if (form) {
      form.reset();
    }
    $('#hc_state_id').val('').trigger('change');
    toggleTimelineCustom();
    togglePurposeContact();
  }

  function hcRaiseBtnLabel(loading) {
    if (loading) {
      return 'Raising...';
    }
    return '<span class="hc-btn-text"><i class="fal fa-paper-plane mr-1"></i> Raise ticket</span>';
  }

  $(document).ready(function () {
    initHcStateSelect();

    $('#hc_btn_raise_ticket').on('click', function () {
      resetHcForm();
      $('#hc_raise_ticket_modal').modal('show');
    });

    $('#hc_raise_ticket_modal').on('shown.bs.modal', function () {
      initHcStateSelect();
    });

    $('#hc_timeline_type').on('change', toggleTimelineCustom);
    $('#hc_purpose').on('change', togglePurposeContact);

    $('#hc_raise_ticket_form').on('submit', function (e) {
      e.preventDefault();
      var desc = $.trim($('[name="description"]', this).val());
      if (!desc) {
        if (typeof TechXAlert === 'function') {
          TechXAlert('Description is required.');
        } else {
          alert('Description is required.');
        }
        return;
      }

      var $btn = $('#hc_raise_ticket_btn');
      $btn.prop('disabled', true).html(hcRaiseBtnLabel(true));

      $.ajax({
        url: 'action/save-hc-ticket.php',
        type: 'POST',
        dataType: 'json',
        data: $(this).serialize(),
        success: function (res) {
          $btn.prop('disabled', false).html(hcRaiseBtnLabel(false));
          if (res.error) {
            if (typeof TechXAlert === 'function') {
              TechXAlert(res.message || 'Could not save ticket.');
            } else {
              alert(res.message || 'Could not save ticket.');
            }
            return;
          }
          var msg = (res.message || 'Ticket raised.') + ' Code: ' + (res.ticket_code || '');
          if (typeof TechXAlert === 'function') {
            TechXAlert(msg);
          } else {
            alert(msg);
          }
          $('#hc_raise_ticket_modal').modal('hide');
          window.location.reload();
        },
        error: function () {
          $btn.prop('disabled', false).html(hcRaiseBtnLabel(false));
          if (typeof TechXAlert === 'function') {
            TechXAlert('Server error. Please try again.');
          } else {
            alert('Server error. Please try again.');
          }
        }
      });
    });
  });
})(jQuery);
