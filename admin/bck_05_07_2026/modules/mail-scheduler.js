var emailLogsTable;
var schedules = [];
var recipients = [];
var companies = [];
var branches = [];
var attachmentFilesList = []; // Store uploaded attachment files

// Load all schedules
function loadSchedules() {
    $.ajax({
        url: './action/get_schedules.php',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.error == false && response.data) {
                schedules = response.data;
                renderSchedules();
            } else {
                $('#schedules_list').html('<div class="col-12 text-center p-5 text-danger">Error loading schedules: ' + (response.message || 'Unknown error') + '</div>');
            }
        },
        error: function(xhr, status, error) {
            console.error('Error loading schedules:', error);
            $('#schedules_list').html('<div class="col-12 text-center p-5 text-danger">Error loading schedules</div>');
        }
    });
}

// Render schedules in cards
function renderSchedules() {
    var html = '';
    
    if (schedules.length === 0) {
        html = '<div class="col-12 text-center p-5"><p>No schedules found. Create your first schedule to get started.</p></div>';
    } else {
        schedules.forEach(function(schedule) {
            var statusClass = schedule.IsActive == 1 ? 'active' : 'inactive';
            var statusBadge = schedule.IsActive == 1 ? '<span class="badge badge-success">Active</span>' : '<span class="badge badge-danger">Inactive</span>';
            
            html += '<div class="col-md-6">';
            html += '<div class="schedule-card ' + statusClass + '">';
            html += '<div class="d-flex justify-content-between align-items-start mb-3">';
            html += '<h4>' + schedule.ScheduleName + '</h4>';
            html += '<div>' + statusBadge + '</div>';
            html += '</div>';
            html += '<p><strong>Type:</strong> ' + schedule.ScheduleType + '</p>';
            html += '<p><strong>Emails Per Month:</strong> ' + schedule.EmailsPerMonth + '</p>';
            html += '<p><strong>Time:</strong> ' + schedule.EmailTime + '</p>';
            if (schedule.EmailDays) {
                html += '<p><strong>Days:</strong> ' + schedule.EmailDays + '</p>';
            }
            html += '<div class="mt-3">';
            html += '<button class="btn btn-sm btn-primary" onclick="editSchedule(' + schedule.ID + ');"><i class="fa fa-edit"></i> Edit</button> ';
            html += '<button class="btn btn-sm btn-danger" onclick="deleteSchedule(' + schedule.ID + ');"><i class="fa fa-trash"></i> Delete</button> ';
            html += '<button class="btn btn-sm btn-info" onclick="viewScheduleLogs(' + schedule.ID + ');"><i class="fa fa-history"></i> View Logs</button>';
            html += '</div>';
            html += '</div>';
            html += '</div>';
        });
    }
    
    $('#schedules_list').html(html);
}

// Open schedule modal
function openScheduleModal(scheduleID) {
    $('#scheduleModal').modal('show');
    $('#scheduleForm')[0].reset();
    $('#modal_schedule_id').val(0);
    recipients = [];
    attachmentFilesList = [];
    $('#recipients_list').html('');
    $('#uploaded_files_list').html('');
    $('#modal_attachment_files').val('');
    updateScheduleTypeUI();
    
    if (scheduleID) {
        loadScheduleForEdit(scheduleID);
    }
}

// Load schedule for editing
function loadScheduleForEdit(scheduleID) {
    console.log('Loading schedule for edit:', scheduleID);
    $.ajax({
        url: './action/get_schedule_by_id.php',
        type: 'GET',
        data: { ScheduleID: scheduleID },
        dataType: 'json',
        success: function(response) {
            console.log('Schedule data response:', response);
            if (response.error == false && response.data) {
                var schedule = response.data;
                $('#modal_schedule_id').val(schedule.ID);
                $('#modal_schedule_name').val(schedule.ScheduleName || '');
                $('#modal_schedule_type').val(schedule.ScheduleType || 'Monthly');
                $('#modal_emails_per_month').val(schedule.EmailsPerMonth || 1);
                
                // Format time (remove seconds if present)
                var emailTime = schedule.EmailTime || '09:00';
                if (emailTime.length > 5) {
                    emailTime = emailTime.substring(0, 5);
                }
                $('#modal_email_time').val(emailTime);
                
                $('#modal_start_date').val(schedule.StartDate || '');
                $('#modal_end_date').val(schedule.EndDate || '');
                $('#modal_is_active').val(schedule.IsActive || 1);
                
                // Update schedule type UI first to create day checkboxes
                updateScheduleTypeUI();
                
                // Load selected days into checkboxes
                if (schedule.EmailDays) {
                    var selectedDays = schedule.EmailDays.split(',');
                    selectedDays.forEach(function(day) {
                        day = day.trim();
                        if (day) {
                            $('#day_' + day).prop('checked', true);
                        }
                    });
                }
                
                // Load recipients
                if (schedule.recipients && schedule.recipients.length > 0) {
                    recipients = schedule.recipients;
                    renderRecipients();
                } else {
                    recipients = [];
                    $('#recipients_list').html('');
                }
                
                // Load template
                if (schedule.template) {
                    $('#modal_template_name').val(schedule.template.TemplateName || 'PPM Billing Summary');
                    $('#modal_email_subject').val(schedule.template.EmailSubject || '');
                    $('#modal_email_body').val(schedule.template.EmailBody || '');
                    $('#modal_include_billing_summary').prop('checked', schedule.template.IncludeBillingSummary == 1);
                    
                    // Load attachment files
                    if (schedule.template.AttachmentFiles) {
                        var attachmentFiles = typeof schedule.template.AttachmentFiles === 'string' 
                            ? JSON.parse(schedule.template.AttachmentFiles) 
                            : schedule.template.AttachmentFiles;
                        if (Array.isArray(attachmentFiles) && attachmentFiles.length > 0) {
                            attachmentFilesList = attachmentFiles;
                            renderAttachmentFiles();
                        } else {
                            attachmentFilesList = [];
                            renderAttachmentFiles();
                        }
                    } else {
                        attachmentFilesList = [];
                        renderAttachmentFiles();
                    }
                } else {
                    // Set default template values if no template exists
                    $('#modal_template_name').val('PPM Billing Summary');
                    $('#modal_email_subject').val('PPM Billing Summary - {BillingPeriod}');
                    $('#modal_email_body').val('Dear {CompanyName},\n\nPlease find attached the billing summary for {BillingPeriod}.\n\n{BillingSummary}\n\nThank you.');
                    $('#modal_include_billing_summary').prop('checked', true);
                    attachmentFilesList = [];
                    renderAttachmentFiles();
                }
            } else {
                console.error('Error loading schedule:', response.message);
                alert('Error loading schedule: ' + (response.message || 'Unknown error'));
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX error loading schedule:', error);
            console.error('Response:', xhr.responseText);
            alert('Error loading schedule. Please check console for details.');
        }
    });
}

// Update schedule type UI
function updateScheduleTypeUI() {
    var scheduleType = $('#modal_schedule_type').val();
    var html = '';
    
    // Store current selected days before clearing
    var currentSelectedDays = [];
    $('.day-checkbox:checked').each(function() {
        currentSelectedDays.push($(this).val());
    });
    
    if (scheduleType === 'Monthly') {
        html = '<div class="day-selector">';
        for (var i = 1; i <= 31; i++) {
            var checked = currentSelectedDays.indexOf(String(i)) >= 0 ? ' checked' : '';
            html += '<input type="checkbox" class="day-checkbox" id="day_' + i + '" value="' + i + '"' + checked + '>';
            html += '<label for="day_' + i + '" class="day-label">' + i + '</label>';
        }
        html += '</div>';
    } else if (scheduleType === 'Weekly') {
        var days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
        html = '<div class="day-selector">';
        days.forEach(function(day, index) {
            var checked = currentSelectedDays.indexOf(day) >= 0 ? ' checked' : '';
            html += '<input type="checkbox" class="day-checkbox" id="day_' + day + '" value="' + day + '"' + checked + '>';
            html += '<label for="day_' + day + '" class="day-label">' + day + '</label>';
        });
        html += '</div>';
    } else if (scheduleType === 'Daily') {
        html = '<p class="text-muted">Emails will be sent daily at the specified time.</p>';
    } else {
        html = '<p class="text-muted">Custom schedule configuration.</p>';
    }
    
    $('#email_days_container').html(html);
}

// Update recipient type UI
function updateRecipientTypeUI() {
    var recipientType = $('#recipient_type').val();
    
    $('#recipient_company_container').hide();
    $('#recipient_branch_container').hide();
    $('#recipient_email_container').hide();
    
    if (recipientType === 'Company') {
        $('#recipient_company_container').show();
    } else if (recipientType === 'Branch') {
        $('#recipient_company_container').show();
        $('#recipient_branch_container').show();
    } else if (recipientType === 'Email') {
        $('#recipient_email_container').show();
    }
}

// Load companies
function loadCompanies() {
    $.ajax({
        url: './ajax/get_companies_list.php',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.error == false && response.data) {
                companies = response.data;
                var html = '<option value="">Select Company</option>';
                companies.forEach(function(company) {
                    html += '<option value="' + company.ID + '">' + company.CompanyName + '</option>';
                });
                $('#recipient_company').html(html);
            }
        }
    });
}

// Load branches
function loadBranches(companyID) {
    if (!companyID) {
        $('#recipient_branch').html('<option value="">Select Branch</option>');
        return;
    }
    
    $.ajax({
        url: './ajax/get_branches_list.php',
        type: 'GET',
        data: { CompanyID: companyID },
        dataType: 'json',
        success: function(response) {
            if (response.error == false && response.data) {
                branches = response.data;
                var html = '<option value="">Select Branch</option>';
                branches.forEach(function(branch) {
                    html += '<option value="' + branch.ID + '">' + branch.BranchSite + '</option>';
                });
                $('#recipient_branch').html(html);
            }
        }
    });
}

// Add recipient
function addRecipient() {
    var recipientType = $('#recipient_type').val();
    var recipient = {
        RecipientType: recipientType
    };
    
    if (recipientType === 'Company') {
        var companyID = $('#recipient_company').val();
        if (!companyID) {
            alert('Please select a company');
            return;
        }
        var company = companies.find(c => c.ID == companyID);
        recipient.CompanyID = companyID;
        recipient.DisplayName = company ? company.CompanyName : 'Company #' + companyID;
    } else if (recipientType === 'Branch') {
        var branchID = $('#recipient_branch').val();
        if (!branchID) {
            alert('Please select a branch');
            return;
        }
        var branch = branches.find(b => b.ID == branchID);
        recipient.BranchID = branchID;
        recipient.DisplayName = branch ? branch.BranchSite : 'Branch #' + branchID;
    } else if (recipientType === 'Email') {
        var email = $('#recipient_email').val();
        if (!email) {
            alert('Please enter an email address');
            return;
        }
        recipient.EmailAddress = email;
        recipient.DisplayName = email;
    }
    
    recipients.push(recipient);
    renderRecipients();
    
    // Clear inputs
    $('#recipient_company').val('');
    $('#recipient_branch').val('');
    $('#recipient_email').val('');
}

// Render recipients
function renderRecipients() {
    var html = '';
    recipients.forEach(function(recipient, index) {
        html += '<span class="recipient-tag">';
        html += recipient.DisplayName;
        html += ' <button type="button" class="btn btn-sm btn-link p-0" onclick="removeRecipient(' + index + ');"><i class="fa fa-times"></i></button>';
        html += '</span>';
    });
    $('#recipients_list').html(html);
}

// Remove recipient
function removeRecipient(index) {
    recipients.splice(index, 1);
    renderRecipients();
}

// Render attachment files list
function renderAttachmentFiles() {
    var html = '';
    if (attachmentFilesList.length > 0) {
        html = '<div class="mt-2"><strong>Uploaded Files:</strong><ul class="list-group mt-2">';
        attachmentFilesList.forEach(function(file, index) {
            html += '<li class="list-group-item d-flex justify-content-between align-items-center">';
            html += '<span><i class="fa fa-file"></i> ' + file.name + ' <small class="text-muted">(' + formatFileSize(file.size) + ')</small></span>';
            html += '<button type="button" class="btn btn-sm btn-danger" onclick="removeAttachmentFile(' + index + ');"><i class="fa fa-times"></i></button>';
            html += '</li>';
        });
        html += '</ul></div>';
    }
    $('#uploaded_files_list').html(html);
}

// Format file size
function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    var k = 1024;
    var sizes = ['Bytes', 'KB', 'MB', 'GB'];
    var i = Math.floor(Math.log(bytes) / Math.log(k));
    return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
}

// Remove attachment file
function removeAttachmentFile(index) {
    attachmentFilesList.splice(index, 1);
    renderAttachmentFiles();
}

// Handle file selection
$(document).on('change', '#modal_attachment_files', function() {
    var files = this.files;
    if (files.length > 0) {
        // Files will be uploaded when saving
        // For now, just show preview
        Array.from(files).forEach(function(file) {
            attachmentFilesList.push({
                name: file.name,
                size: file.size,
                file: file // Store file object for upload
            });
        });
        renderAttachmentFiles();
    }
});

// Save schedule
function saveSchedule() {
    if (!$('#scheduleForm')[0].checkValidity()) {
        $('#scheduleForm')[0].reportValidity();
        return;
    }
    
    // Get selected days
    var selectedDays = [];
    $('.day-checkbox:checked').each(function() {
        selectedDays.push($(this).val());
    });
    
    // Prepare form data with file upload
    var formData = new FormData();
    formData.append('ScheduleID', $('#modal_schedule_id').val());
    formData.append('ScheduleName', $('#modal_schedule_name').val());
    formData.append('ScheduleType', $('#modal_schedule_type').val());
    formData.append('EmailsPerMonth', $('#modal_emails_per_month').val());
    formData.append('EmailDays', selectedDays.join(','));
    formData.append('EmailTime', $('#modal_email_time').val());
    formData.append('StartDate', $('#modal_start_date').val());
    formData.append('EndDate', $('#modal_end_date').val());
    formData.append('IsActive', $('#modal_is_active').val());
    
    // Add recipients as JSON
    formData.append('recipients', JSON.stringify(recipients));
    
    // Add template data
    var templateData = {
        TemplateName: $('#modal_template_name').val(),
        EmailSubject: $('#modal_email_subject').val(),
        EmailBody: $('#modal_email_body').val(),
        IncludeBillingSummary: $('#modal_include_billing_summary').is(':checked') ? 1 : 0,
        existingFiles: attachmentFilesList.filter(function(f) { return f.path; }) // Only existing files (already uploaded)
    };
    formData.append('template', JSON.stringify(templateData));
    
    // Add new files to upload
    var fileInput = document.getElementById('modal_attachment_files');
    if (fileInput && fileInput.files.length > 0) {
        for (var i = 0; i < fileInput.files.length; i++) {
            formData.append('attachment_files[]', fileInput.files[i]);
        }
    }
    
    $.ajax({
        url: './action/save_schedule.php',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function(response) {
            if (response.error == false) {
                // Update attachment files list with server response
                if (response.uploaded_files) {
                    attachmentFilesList = response.uploaded_files;
                }
                alert('Schedule saved successfully!');
                $('#scheduleModal').modal('hide');
                loadSchedules();
                // Clear file input
                $('#modal_attachment_files').val('');
                attachmentFilesList = [];
            } else {
                alert('Error: ' + (response.message || 'Unknown error'));
            }
        },
        error: function(xhr, status, error) {
            console.error('Error saving schedule:', error);
            alert('Error saving schedule. Please try again.');
        }
    });
}

// Delete schedule
function deleteSchedule(scheduleID) {
    if (!confirm('Are you sure you want to delete this schedule?')) {
        return;
    }
    
    $.ajax({
        url: './action/delete_schedule.php',
        type: 'POST',
        data: { ScheduleID: scheduleID },
        dataType: 'json',
        success: function(response) {
            if (response.error == false) {
                alert('Schedule deleted successfully!');
                loadSchedules();
            } else {
                alert('Error: ' + (response.message || 'Unknown error'));
            }
        }
    });
}

// Edit schedule
function editSchedule(scheduleID) {
    openScheduleModal(scheduleID);
}

// View schedule logs
function viewScheduleLogs(scheduleID) {
    // Filter logs table by schedule ID
    if (emailLogsTable) {
        emailLogsTable.column(5).search(scheduleID).draw();
    }
    $('html, body').animate({
        scrollTop: $('#panel-email-logs').offset().top
    }, 500);
}

// Load email logs
function loadEmailLogs() {
    console.log('Loading email logs...');
    $.ajax({
        url: './action/get_email_logs.php',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            console.log('Email logs response:', response);
            var tbody = $('#email_logs_tbody');
            tbody.empty();
            
            if (response.error == false && response.data) {
                if (response.data.length > 0) {
                    response.data.forEach(function(log) {
                        var statusClass = log.Status === 'Sent' ? 'badge-success' : (log.Status === 'Failed' ? 'badge-danger' : 'badge-warning');
                        var row = '<tr>';
                        row += '<td>' + (log.EmailSentDate || '') + ' ' + (log.EmailSentTime || '') + '</td>';
                        row += '<td>' + (log.RecipientEmail || '') + (log.RecipientName ? ' (' + log.RecipientName + ')' : '') + '</td>';
                        row += '<td>' + (log.EmailSubject || '-') + '</td>';
                        row += '<td><span class="badge ' + statusClass + '">' + (log.Status || 'Pending') + '</span></td>';
                        row += '<td>' + (log.AttachmentCount || 0) + '</td>';
                        row += '<td>' + (log.ScheduleID || '-') + '</td>';
                        if (log.ErrorMessage) {
                            row += '<td><small class="text-danger" title="' + log.ErrorMessage + '">' + (log.ErrorMessage.length > 50 ? log.ErrorMessage.substring(0, 50) + '...' : log.ErrorMessage) + '</small></td>';
                        } else {
                            row += '<td>-</td>';
                        }
                        row += '</tr>';
                        tbody.append(row);
                    });
                } else {
                    tbody.append('<tr><td colspan="7" class="text-center">No email logs found</td></tr>');
                }
                
                // Initialize DataTable
                if ($.fn.DataTable.isDataTable('#email_logs_table')) {
                    emailLogsTable.destroy();
                }
                emailLogsTable = $('#email_logs_table').DataTable({
                    "pageLength": 25,
                    "order": [[0, "desc"]],
                    "columnDefs": [
                        { "orderable": false, "targets": [6] } // Disable sorting on error message column
                    ]
                });
            } else {
                console.error('Error loading email logs:', response.message || 'Unknown error');
                tbody.append('<tr><td colspan="7" class="text-center text-danger">Error loading logs: ' + (response.message || 'Unknown error') + '</td></tr>');
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX error loading email logs:', error);
            console.error('Response:', xhr.responseText);
            var tbody = $('#email_logs_tbody');
            tbody.empty();
            tbody.append('<tr><td colspan="7" class="text-center text-danger">Error loading email logs. Please check console for details.</td></tr>');
        }
    });
}
