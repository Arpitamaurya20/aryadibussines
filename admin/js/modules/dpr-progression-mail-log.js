function initDprProgressionMailLogPage() {
  var config = window.dprProgressionMailLogConfig || {};
  var runsTable = null;
  var itemsTable = null;
  var todaySentTable = null;
  var willSendTable = null;

  function buildItemsUrl() {
    var url = config.itemsUrl || 'ajax/view-dpr-progression-mail-log-post.php?mode=items';
    var logDate = $('#dprLogFilterDate').val() || '';
    var status = $('#dprLogFilterStatus').val() || '-1';
    return url + '&log_date=' + encodeURIComponent(logDate) + '&status=' + encodeURIComponent(status);
  }

  function buildTodaySentUrl() {
    var url = config.todaySentUrl || 'ajax/view-dpr-progression-mail-log-post.php?mode=today_sent';
    var logDate = $('#dprTodaySentDate').val() || '';
    var trigger = $('#dprTodaySentTrigger').val() || '-1';
    return url + '&log_date=' + encodeURIComponent(logDate) + '&trigger=' + encodeURIComponent(trigger);
  }

  runsTable = $('#dpr-progression-mail-runs-table').DataTable({
    processing: true,
    serverSide: true,
    searching: false,
    order: [[0, 'desc']],
    scrollY: '360px',
    scrollCollapse: true,
    ajax: {
      url: config.runsUrl || 'ajax/view-dpr-progression-mail-log-post.php?mode=runs',
      type: 'POST'
    },
    columns: [
      { data: 0 }, { data: 1 }, { data: 2 }, { data: 3 }, { data: 4 },
      { data: 5 }, { data: 6 }, { data: 7 }, { data: 8 }, { data: 9 }
    ]
  });

  willSendTable = $('#dpr-will-send-table').DataTable({
    processing: true,
    serverSide: true,
    order: [[0, 'asc']],
    scrollY: '420px',
    scrollCollapse: true,
    ajax: {
      url: config.willSendUrl || 'ajax/view-dpr-progression-mail-log-post.php?mode=will_send',
      type: 'POST'
    },
    columns: [
      { data: 0 },
      { data: 1 },
      { data: 2 },
      { data: 3 },
      { data: 4 },
      { data: 5 },
      { data: 6 }
    ],
    columnDefs: [
      { targets: [0, 2, 4, 6], orderable: false }
    ]
  });

  todaySentTable = $('#dpr-today-sent-table').DataTable({
    processing: true,
    serverSide: true,
    order: [[0, 'desc']],
    scrollY: '420px',
    scrollCollapse: true,
    ajax: {
      url: buildTodaySentUrl(),
      type: 'POST'
    },
    columns: [
      { data: 0 },
      { data: 1 },
      { data: 2 },
      { data: 3 },
      { data: 4 },
      { data: 5 }
    ],
    columnDefs: [
      { targets: [1, 3, 4], orderable: false }
    ]
  });

  $('#dprTodaySentDate, #dprTodaySentTrigger').on('change', function () {
    todaySentTable.ajax.url(buildTodaySentUrl()).load();
  });

  itemsTable = $('#dpr-progression-mail-log-table').DataTable({
    processing: true,
    serverSide: true,
    order: [[0, 'desc']],
    scrollY: '420px',
    scrollCollapse: true,
    ajax: {
      url: buildItemsUrl(),
      type: 'POST'
    },
    columns: [
      { data: 0 },
      { data: 1 },
      { data: 2 },
      { data: 3 },
      { data: 4 },
      { data: 5 },
      { data: 6 }
    ],
    columnDefs: [
      { targets: [4, 5], orderable: false }
    ]
  });

  $('#dprLogFilterDate, #dprLogFilterStatus').on('change', function () {
    itemsTable.ajax.url(buildItemsUrl()).load();
  });

  $('#btnRunDprProgressionMailNow').on('click', function () {
    if (!confirm('Process next DPR mail queue batch now? (small batch — safe for portal)')) {
      return;
    }

    var $btn = $(this);
    $btn.prop('disabled', true).text('Running...');

    $.ajax({
      url: config.runNowUrl || 'action/run-dpr-progression-mail-now.php',
      type: 'POST',
      dataType: 'json'
    }).done(function (response) {
      if (response && response.error) {
        TechXAlert(response.message || 'Cron run failed.');
        return;
      }
      var summary = response.summary || {};
      TechXAlert(
        'Batch done. Sent: ' + (summary.sent || 0) +
        ', Skipped: ' + (summary.skipped || 0) +
        ', Failed: ' + (summary.failed || 0) +
        ', Pending left: ' + (summary.pending || summary.remaining || 0)
      );
      if (runsTable) {
        runsTable.ajax.reload(null, false);
      }
      if (itemsTable) {
        itemsTable.ajax.reload(null, false);
      }
      if (todaySentTable) {
        todaySentTable.ajax.reload(null, false);
      }
      if (willSendTable) {
        willSendTable.ajax.reload(null, false);
      }
      window.location.reload();
    }).fail(function () {
      TechXAlert('Unable to run cron. Please check server logs.');
    }).always(function () {
      $btn.prop('disabled', false).text('Process Next Batch Now');
    });
  });
}
