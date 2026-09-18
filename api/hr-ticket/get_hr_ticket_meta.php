<?php
require_once('../common_api_header.php');
require_once('../../admin/controllers/common_controllers.php');
require_once('hr_ticket_helpers.php');

header('Content-Type: application/json; charset=utf-8');
setTimeZone();

echo json_encode(array(
    'error' => false,
    'message' => 'HR ticket metadata.',
    'data' => array(
        'categories' => array(
            array('value' => 'general', 'label' => 'General', 'payment_reference_required' => false),
            array('value' => 'payment_related', 'label' => 'Payment Related', 'payment_reference_required' => true, 'note' => 'Mandatory for salary/payment queries'),
            array('value' => 'benefits', 'label' => 'Benefits', 'payment_reference_required' => false),
        ),
        'statuses' => array(
            array('value' => 'open', 'label' => 'Open'),
            array('value' => 'in_progress', 'label' => 'In Progress'),
            array('value' => 'pending_employee_response', 'label' => 'Pending Employee Response'),
            array('value' => 'on_hold', 'label' => 'On Hold'),
            array('value' => 'resolved', 'label' => 'Resolved'),
            array('value' => 'closed', 'label' => 'Closed'),
        ),
        'priorities' => array(
            array('value' => 'low', 'label' => 'Low'),
            array('value' => 'normal', 'label' => 'Normal'),
            array('value' => 'high', 'label' => 'High'),
        ),
        'notification_payload' => array(
            'module' => 'hr_ticket',
            'employee_screen' => 'hr_ticket',
            'hr_screen' => 'hr_ticket_manage',
        ),
    ),
));
