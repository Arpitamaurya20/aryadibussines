/**
 * Due-date escalation UI for corporate ticket Assignment tab.
 */
var teTicketPK = typeof teTicketPK !== 'undefined' ? teTicketPK : 0;

function teLoadEscalationPanel() {
  if (!teTicketPK || !$('#te_escalation_panel').length) {
    return;
  }
  $.ajax({
    url: 'action/get_ticket_escalation.php',
    type: 'GET',
    data: { TicketPK: teTicketPK },
    success: function (data) {
      var res;
      try {
        res = typeof data === 'string' ? JSON.parse(data) : data;
      } catch (e) {
        return;
      }
      if (res.error || !res.data) {
        return;
      }
      teRenderEscalationPanel(res.data);
    }
  });
}

function teRenderEscalationPanel(data) {
  // Manual form is rendered server-side in the Assignment tab; do not replace it here.

  var activeHtml = '<p class="text-muted mb-0">No active escalation.</p>';
  if (data.active) {
    var a = data.active;
    activeHtml = ''
      + '<div class="alert alert-warning mb-2">'
      + '<strong>Active Escalation — ' + a.level_label + '</strong><br>'
      + 'Escalated to: <b>' + (a.escalated_to_name || 'N/A') + '</b><br>'
      + 'Reason: ' + (a.trigger_reason || '') + '<br>'
      + 'Escalated at: ' + (a.escalated_at || '') + '<br>'
      + 'Response deadline: ' + (a.response_deadline || '') + ' (' + data.response_hours + 'h window)'
      + '</div>';
    if (a.can_acknowledge) {
      activeHtml += ''
        + '<div class="form-group">'
        + '<label for="te_escalation_remarks">Acknowledgement remarks (optional)</label>'
        + '<textarea id="te_escalation_remarks" class="form-control" rows="2"></textarea>'
        + '</div>'
        + '<button type="button" class="btn btn-success btn-sm" onclick="teAcknowledgeEscalation()">Acknowledge Escalation</button>';
    }
  }

  var historyHtml = '<p class="text-muted mb-0">No escalation history yet.</p>';
  if (data.history && data.history.length > 0) {
    historyHtml = '<div class="table-responsive"><table class="table table-sm table-bordered mb-0">'
      + '<thead><tr><th>Level</th><th>Action</th><th>Escalated To</th><th>Remarks</th><th>When</th><th>By</th></tr></thead><tbody>';
    data.history.forEach(function (h) {
      historyHtml += '<tr>'
        + '<td>' + h.level_label + '</td>'
        + '<td>' + h.action + '</td>'
        + '<td>' + (h.escalated_to_name || '') + '</td>'
        + '<td>' + (h.remarks || '') + '</td>'
        + '<td>' + h.created_at + '</td>'
        + '<td>' + (h.created_by || '') + '</td>'
        + '</tr>';
    });
    historyHtml += '</tbody></table></div>';
  }

  $('#te_escalation_active').html(activeHtml);
  $('#te_escalation_history').html(historyHtml);
}

function teManualEscalate() {
  var level = $('#te_manual_level').val() || '0';
  var remarks = $('#te_manual_remarks').val() || '';
  $('#te_manual_escalate_btn').prop('disabled', true).text('Escalating...');
  $.ajax({
    url: 'action/manual_escalate_ticket.php',
    type: 'POST',
    data: { TicketPK: teTicketPK, EscalationLevel: level, Remarks: remarks },
    success: function (data) {
      var res;
      try {
        res = typeof data === 'string' ? JSON.parse(data) : data;
      } catch (e) {
        TechXAlert('Server error — please refresh the page and try again.');
        $('#te_manual_escalate_btn').prop('disabled', false).text('Escalate Now');
        return;
      }
      TechXAlert(res.message);
      $('#te_manual_escalate_btn').prop('disabled', false).text('Escalate Now');
      if (!res.error) {
        setTimeout(function () {
          location.reload();
        }, 1500);
      }
    },
    error: function () {
      TechXAlert('Failed to escalate ticket');
      $('#te_manual_escalate_btn').prop('disabled', false).text('Escalate Now');
    }
  });
}

function teInitReassignTechnicianSelect2() {
  var $el = $('#te_reassign_technician_id');
  if (!$el.length) {
    return;
  }
  if ($el.data('select2')) {
    $el.select2('destroy');
  }
  $el.select2({
    width: '100%',
    placeholder: 'Search technician...',
    allowClear: true,
    minimumResultsForSearch: 0,
    dropdownParent: $('#assignment').length ? $('#assignment') : $(document.body)
  });
}

function teReassignTechnician() {
  var technicianId = $('#te_reassign_technician_id').val();
  if (!technicianId || technicianId === '') {
    TechXAlert('Please select a technician');
    return;
  }
  if (!validateDifferentTechnician(technicianId)) {
    return;
  }
  var remarks = $('#te_reassign_remarks').val() || '';
  $('#te_reassign_btn').prop('disabled', true).text('Please wait...');
  $.ajax({
    url: 'action/reassign_ticket_technician.php',
    type: 'POST',
    data: {
      TicketPK: teTicketPK,
      AssignedTo: technicianId,
      Remarks: remarks
    },
    success: function (data) {
      var res;
      try {
        res = typeof data === 'string' ? JSON.parse(data) : data;
      } catch (e) {
        TechXAlert('Unexpected server response');
        $('#te_reassign_btn').prop('disabled', false).text('Reassign');
        return;
      }
      TechXAlert(res.message);
      $('#te_reassign_btn').prop('disabled', false).text('Reassign');
      if (!res.error) {
        setTimeout(function () {
          location.reload();
        }, 1500);
      }
    },
    error: function () {
      TechXAlert('Failed to reassign ticket');
      $('#te_reassign_btn').prop('disabled', false).text('Reassign');
    }
  });
}

function teAcknowledgeEscalation() {
  var remarks = $('#te_escalation_remarks').val() || '';
  $.ajax({
    url: 'action/acknowledge_ticket_escalation.php',
    type: 'POST',
    data: { TicketPK: teTicketPK, Remarks: remarks },
    success: function (data) {
      var res;
      try {
        res = typeof data === 'string' ? JSON.parse(data) : data;
      } catch (e) {
        TechXAlert('Unexpected response');
        return;
      }
      TechXAlert(res.message);
      if (!res.error) {
        teLoadEscalationPanel();
      }
    }
  });
}

$(document).ready(function () {
  teLoadEscalationPanel();
  teInitReassignTechnicianSelect2();

  $('a[href="#assignment"]').on('shown.bs.tab', function () {
    teInitReassignTechnicianSelect2();
  });
});
