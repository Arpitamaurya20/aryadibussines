function initTicketBillingMailLogPage() {
  var config = window.tbmsMailLogConfig || {};
  var runsTable = null;
  var itemsTable = null;
  var todaySentTable = null;
  var willSendTable = null;

  var emptyLang = {
    emptyTable: 'No records found',
    zeroRecords: 'No matching records',
    processing: 'Loading...'
  };

  function buildItemsUrl() {
    var url = config.itemsUrl || 'ajax/view-ticket-billing-mail-log-post.php?mode=items';
    var logDate = $('#tbmsLogFilterDate').val() || '';
    var status = $('#tbmsLogFilterStatus').val() || '-1';
    return url + '&log_date=' + encodeURIComponent(logDate) + '&status=' + encodeURIComponent(status);
  }

  function buildTodaySentUrl() {
    var url = config.todaySentUrl || 'ajax/view-ticket-billing-mail-log-post.php?mode=today_sent';
    var logDate = $('#tbmsTodaySentDate').val() || '';
    var trigger = $('#tbmsTodaySentTrigger').val() || '-1';
    return url + '&log_date=' + encodeURIComponent(logDate) + '&trigger=' + encodeURIComponent(trigger);
  }

  function dtAjax(url) {
    return {
      url: url,
      type: 'POST',
      dataSrc: function (json) {
        if (!json) {
          return [];
        }
        if (typeof json.recordsTotal === 'undefined' && typeof json.iTotalRecords !== 'undefined') {
          json.recordsTotal = json.iTotalRecords;
        }
        if (typeof json.recordsFiltered === 'undefined' && typeof json.iTotalDisplayRecords !== 'undefined') {
          json.recordsFiltered = json.iTotalDisplayRecords;
        }
        return json.aaData || json.data || [];
      },
      error: function () {
        console.log('Billing mail log table failed to load:', url);
      }
    };
  }

  willSendTable = $('#tbms-will-send-table').DataTable({
    processing: true,
    serverSide: true,
    autoWidth: false,
    order: [[0, 'asc']],
    scrollY: '360px',
    scrollCollapse: true,
    scrollX: true,
    language: emptyLang,
    ajax: dtAjax(config.willSendUrl || 'ajax/view-ticket-billing-mail-log-post.php?mode=will_send'),
    columns: [
      { data: 0 }, { data: 1 }, { data: 2 }, { data: 3 },
      { data: 4 }, { data: 5 }, { data: 6 }, { data: 7 }
    ],
    columnDefs: [
      { targets: [1, 2, 5, 6, 7], orderable: false },
      { targets: [6], className: 'tbms-subject-cell' }
    ]
  });

  todaySentTable = $('#tbms-today-sent-table').DataTable({
    processing: true,
    serverSide: true,
    autoWidth: false,
    order: [[0, 'desc']],
    scrollY: '360px',
    scrollCollapse: true,
    scrollX: true,
    language: emptyLang,
    ajax: dtAjax(buildTodaySentUrl()),
    columns: [
      { data: 0 }, { data: 1 }, { data: 2 }, { data: 3 }, { data: 4 }, { data: 5 }
    ],
    columnDefs: [
      { targets: [2, 4, 5], orderable: false },
      { targets: [4], className: 'tbms-subject-cell' }
    ]
  });

  $('#tbmsTodaySentDate, #tbmsTodaySentTrigger').on('change', function () {
    todaySentTable.ajax.url(buildTodaySentUrl()).load();
  });

  runsTable = $('#tbms-runs-table').DataTable({
    processing: true,
    serverSide: true,
    searching: false,
    autoWidth: false,
    order: [[0, 'desc']],
    scrollY: '360px',
    scrollCollapse: true,
    scrollX: true,
    language: emptyLang,
    ajax: dtAjax(config.runsUrl || 'ajax/view-ticket-billing-mail-log-post.php?mode=runs'),
    columns: [
      { data: 0 }, { data: 1 }, { data: 2 }, { data: 3 }, { data: 4 },
      { data: 5 }, { data: 6 }, { data: 7 }, { data: 8 }
    ]
  });

  itemsTable = $('#tbms-items-table').DataTable({
    processing: true,
    serverSide: true,
    autoWidth: false,
    order: [[0, 'desc']],
    scrollY: '360px',
    scrollCollapse: true,
    scrollX: true,
    language: emptyLang,
    ajax: dtAjax(buildItemsUrl()),
    columns: [
      { data: 0 }, { data: 1 }, { data: 2 }, { data: 3 }, { data: 4 },
      { data: 5 }, { data: 6 }, { data: 7 }, { data: 8 }
    ],
    columnDefs: [
      { targets: [4, 6, 8], orderable: false },
      { targets: [6], className: 'tbms-subject-cell' }
    ]
  });

  $('#tbmsLogFilterDate, #tbmsLogFilterStatus').on('change', function () {
    itemsTable.ajax.url(buildItemsUrl()).load();
  });

  $('#btnCopyCronUrl').on('click', function () {
    var text = config.cronUrl || $('#tbmsCronUrlText').text();
    if (navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard.writeText(text).then(function () {
        alert('Cron URL copied.');
      }).catch(function () {
        window.prompt('Copy cron URL:', text);
      });
    } else {
      window.prompt('Copy cron URL:', text);
    }
  });

  $('#btnRefreshBillingMailLog').on('click', function () {
    window.location.reload();
  });

  function loadRawLog() {
    $.getJSON(config.rawLogUrl || 'ajax/view-ticket-billing-mail-log-post.php?mode=raw_log')
      .done(function (response) {
        if (response && response.exists && response.content) {
          $('#tbmsRawLogEmpty').addClass('d-none');
          $('#tbmsRawLogBox').removeClass('d-none').text(response.content);
        } else {
          $('#tbmsRawLogBox').addClass('d-none').text('');
          $('#tbmsRawLogEmpty').removeClass('d-none');
        }
      });
  }

  $('#btnReloadRawLog').on('click', function () {
    loadRawLog();
  });

  $('#btnRunBillingMailNow').on('click', function () {
    if (!confirm('Run all due billing mail schedules now?')) {
      return;
    }
    var $btn = $(this);
    var original = $btn.html();
    $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Running...');
    $.ajax({
      url: config.runNowUrl || 'action/run_ticket_billing_mail_now.php',
      type: 'POST',
      dataType: 'json'
    }).done(function (response) {
      if (response && response.error) {
        alert(response.message || 'Run failed.');
        return;
      }
      var summary = response.summary || {};
      alert(
        'Done.\nDue: ' + (summary.due_count || 0) +
        '\nSent: ' + (summary.sent || 0) +
        '\nFailed: ' + (summary.failed || 0)
      );
      window.location.reload();
    }).fail(function () {
      alert('Unable to run billing mail cron now.');
    }).always(function () {
      $btn.prop('disabled', false).html(original);
    });
  });
}
