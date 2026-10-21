<?php
/**
 * Finance & Performance access matrix.
 *
 * Logical roles are granted when the signed-in user matches any of:
 *   - user_types : users.UserType
 *   - erp_roles  : user_roles.Role (active rows for users.EmployeeID)
 *   - mapping    : an existing ERP assignment column (see fp_resolve_mapping_scope())
 *
 * Scope types:
 *   all     every record
 *   self    records of the signed-in employee
 *   team    employees.Supervisor = me, plus employees in states where I am citydata.CorporateLead
 *   branch  branch.AccountBranchManager = me
 *   state   state.StateCorporateHead = me OR state.StateHead = me
 *   region  states whose region has RegionHead / RegionCorporateHead = me
 *   company company.AccountManager = me (client companies)
 *   office  branch offices where branch_expense_office_employees.IsManager = 1 for me
 *
 * Raising expense requests additionally needs an office assignment with CanRequest = 1
 * (or a create grant over all records); see fp_requester_offices().
 */
return array(
    'permissions' => array(
        'view'      => 'View records',
        'documents' => 'View bills, receipts and invoices',
        'create'    => 'Create expense requests',
        'edit'      => 'Edit draft or returned requests',
        'submit'    => 'Submit requests for approval',
        'approve'   => 'Approve, reject or return requests',
        'pay'       => 'Record payments and settlements',
        'configure' => 'Configure categories and offices',
        'budgets'   => 'Set and adjust budgets',
        'policies'  => 'Configure approval policies and delegations',
        'export'    => 'Export data',
        'audit'     => 'See technical audit details (IP address, device, all recorded values) and export audit timelines',
    ),

    // Client portal logins must never see internal finance data.
    'blocked_user_types' => array('Corporate Admin', 'Corporate User', 'Corporate Branch User'),

    // Vendor records in employees (employees.Vendor = 1) do not get the base employee role.
    'exclude_vendor_employees' => true,

    // Approve/pay need a real employee identity for the audit trail; users.EmployeeID = -1 loses them.
    'financial_actions_require_employee' => true,

    // Nobody approves their own request. A policy stage naming the requester goes to its escalation approver,
    // is skipped when optional, or blocks submission when mandatory.
    'allow_self_approval' => false,

    // One person decides at most one stage of the same submission round (also when acting as a delegate).
    'one_stage_per_approver' => true,

    // Approval notifications use the ERP notification bell (portal_notifications); mobile_push also queues an app push.
    'notifications' => array('portal' => true, 'mobile_push' => true),

    // Days after the first advance payment within which the holder should settle it; older open advances show as overdue.
    'advance_settlement_days' => 30,

    // Direct expenses: the payee is chosen from the ERP vendor master (vendor_registration, approved vendors only).
    // 'optional' also allows typing a payee who is not a registered vendor (landlord, electricity board);
    // 'required' allows registered vendors only. A typed payee matching a registered vendor is always refused.
    'direct_expense_vendor_master' => 'optional',

    // A direct expense not yet submitted shows as "due soon" (and its owner is reminded) this many days before the due date.
    'recurring_due_soon_days' => 3,

    'roles' => array(
        'admin' => array(
            'label'       => 'Head Office Admin',
            'user_types'  => array('Admin', 'Super Admin'),
            'erp_roles'   => array('Super Admin', 'Admin'),
            'mapping'     => '',
            'scope'       => 'all',
            'permissions' => array('view', 'documents', 'create', 'edit', 'submit', 'approve', 'pay', 'configure', 'budgets', 'policies', 'export', 'audit'),
        ),
        'cfo' => array(
            'label'       => 'CFO',
            'user_types'  => array(),
            'erp_roles'   => array('CFO'),
            'mapping'     => '',
            'scope'       => 'all',
            'permissions' => array('view', 'documents', 'approve', 'pay', 'configure', 'budgets', 'policies', 'export', 'audit'),
        ),
        'finance' => array(
            'label'       => 'Head Office Finance',
            'user_types'  => array(),
            'erp_roles'   => array('Finance', 'Accounts'),
            'mapping'     => '',
            'scope'       => 'all',
            'permissions' => array('view', 'documents', 'approve', 'pay', 'budgets', 'export', 'audit'),
        ),
        'hr' => array(
            'label'       => 'HR',
            'user_types'  => array(),
            'erp_roles'   => array('HR'),
            'mapping'     => '',
            'scope'       => 'all',
            'permissions' => array('view', 'export'),
        ),
        'region_manager' => array(
            'label'       => 'Region Manager',
            'user_types'  => array(),
            'erp_roles'   => array('Region Corporate Lead', 'Region Lead'),
            'mapping'     => 'region_head',
            'scope'       => 'region',
            'permissions' => array('view', 'documents', 'approve', 'export', 'audit'),
        ),
        'state_manager' => array(
            'label'       => 'State Manager',
            'user_types'  => array(),
            'erp_roles'   => array('State Corporate Lead', 'State Lead'),
            'mapping'     => 'state_head',
            'scope'       => 'state',
            'permissions' => array('view', 'documents', 'approve', 'export', 'audit'),
        ),
        'office_manager' => array(
            'label'       => 'Branch Manager (office)',
            'user_types'  => array(),
            'erp_roles'   => array(),
            'mapping'     => 'office_manager',
            'scope'       => 'office',
            'permissions' => array('view', 'documents', 'approve', 'export', 'audit'),
        ),
        'branch_manager' => array(
            'label'       => 'Client Branch Account Manager',
            'user_types'  => array(),
            'erp_roles'   => array('Branch Account Manager'),
            'mapping'     => 'branch_account_manager',
            'scope'       => 'branch',
            'permissions' => array('view', 'documents', 'approve', 'export'),
        ),
        'team_lead' => array(
            'label'       => 'Supervisor / City Lead',
            'user_types'  => array(),
            'erp_roles'   => array('Supervisor', 'City Corporate Lead', 'City Lead', 'Manager'),
            'mapping'     => 'supervisor',
            'scope'       => 'team',
            'permissions' => array('view', 'documents', 'approve', 'export'),
        ),
        'business' => array(
            'label'       => 'Sales / Business (Account Manager)',
            'user_types'  => array(),
            'erp_roles'   => array('Account Manager'),
            'mapping'     => 'account_manager',
            'scope'       => 'company',
            'permissions' => array('view', 'export'),
        ),
        'employee' => array(
            'label'       => 'Employee (Branch User)',
            'user_types'  => array(),
            'erp_roles'   => array(),
            'mapping'     => 'employee',
            'scope'       => 'self',
            'permissions' => array('view', 'documents', 'create', 'edit', 'submit', 'export'),
        ),
    ),

    // Sidebar sub-menus; a group is shown only when at least one of its entries is visible.
    'menu_groups' => array(
        'booking'  => array('label' => 'Expense Booking', 'icon' => 'fa-solid fa-receipt'),
        'settings' => array('label' => 'Settings', 'icon' => 'fa-solid fa-gear'),
    ),

    // Sidebar entries; an entry is shown only when its file exists and the permission is granted.
    'menu' => array(
        'new_request'        => array('label' => 'New Request',            'file' => 'view-expense-request-form.php', 'icon' => 'fa-solid fa-file-circle-plus',    'permission' => 'create',    'beyond_self' => false, 'group' => 'booking', 'audience' => 'requester'),
        'my_requests'        => array('label' => 'My Requests',            'file' => 'view-expense-requests.php',    'icon' => 'fa-solid fa-list-check',          'permission' => 'view',      'beyond_self' => false, 'group' => 'booking', 'audience' => 'requester_or_scoped'),
        'approvals'          => array('label' => 'Approvals',              'file' => 'view-approvals.php',          'icon' => 'fa-solid fa-clipboard-check',     'permission' => 'view',      'beyond_self' => false, 'group' => '', 'audience' => 'approver'),
        'expense_details'    => array('label' => 'Expense Details',        'file' => 'view-expense-details.php',    'icon' => 'fa-solid fa-file-invoice-dollar',  'permission' => 'view',      'beyond_self' => false, 'group' => ''),
        'payments'           => array('label' => 'Payments',               'file' => 'view-payments.php',           'icon' => 'fa-solid fa-money-check-dollar',  'permission' => 'pay',       'beyond_self' => false, 'group' => ''),
        'settlements'        => array('label' => 'Settlements',            'file' => 'view-settlements.php',        'icon' => 'fa-solid fa-hand-holding-dollar', 'permission' => 'view',      'beyond_self' => false, 'group' => '', 'audience' => 'requester_or_scoped'),
        'direct_expenses'    => array('label' => 'Direct Expenses',        'file' => 'view-direct-expenses.php',    'icon' => 'fa-solid fa-file-invoice',        'permission' => 'view',      'beyond_self' => false, 'group' => '', 'audience' => 'requester_or_scoped'),
        'recurring_expenses' => array('label' => 'Recurring Expenses',     'file' => 'view-recurring-expenses.php', 'icon' => 'fa-solid fa-arrows-rotate',       'permission' => 'view',      'beyond_self' => false, 'group' => '', 'audience' => 'requester_or_scoped'),
        'budgets'            => array('label' => 'Budgets',                'file' => 'view-budgets.php',            'icon' => 'fa-solid fa-scale-balanced',      'permission' => 'budgets',   'beyond_self' => false, 'group' => '', 'global' => true),
        'reports'            => array('label' => 'Expense Reports',        'file' => 'view-reports.php',            'icon' => 'fa-solid fa-file-export',         'permission' => 'export',    'beyond_self' => true,  'group' => ''),
        'performance'        => array('label' => 'Branch Performance',     'file' => 'view-performance.php',        'icon' => 'fa-solid fa-chart-line',          'permission' => 'view',      'beyond_self' => true,  'group' => ''),
        'expense_categories' => array('label' => 'Expense Categories',     'file' => 'view-expense-categories.php', 'icon' => 'fa-solid fa-tags',                'permission' => 'configure', 'beyond_self' => false, 'group' => 'settings', 'global' => true),
        'offices'            => array('label' => 'Branch Offices',         'file' => 'view-branch-offices.php',      'icon' => 'fa-solid fa-building',            'permission' => 'configure', 'beyond_self' => false, 'group' => 'settings', 'global' => true),
        'approval_policies'  => array('label' => 'Approval Policies',      'file' => 'view-approval-policies.php',   'icon' => 'fa-solid fa-sitemap',             'permission' => 'policies',  'beyond_self' => false, 'group' => 'settings', 'global' => true),
        'access'             => array('label' => 'My Access',              'file' => 'view-my-access.php',           'icon' => 'fa-solid fa-user-shield',         'permission' => 'view',      'beyond_self' => false, 'group' => ''),
    ),

    // Every endpoint must be listed here; unknown actions are denied.
    // 'global' => true requires the permission with "all records" scope (global master data).
    // 'requires_employee' => true needs a login linked to an active employee record.
    'actions' => array(
        'get_my_access'   => array('permission' => 'view',      'method' => 'GET'),
        'list_requests'       => array('permission' => 'view',   'method' => 'GET'),
        'export_requests'     => array('permission' => 'export', 'method' => 'GET'),
        'download_attachment' => array('permission' => 'documents', 'method' => 'GET'),
        'get_request_form'    => array('permission' => 'create', 'method' => 'GET',  'requires_employee' => true),
        'save_request'        => array('permission' => 'create', 'method' => 'POST', 'requires_employee' => true),
        'submit_request'      => array('permission' => 'submit', 'method' => 'POST', 'requires_employee' => true),
        'cancel_request'      => array('permission' => 'edit',   'method' => 'POST', 'requires_employee' => true),
        'upload_attachment'   => array('permission' => 'edit',   'method' => 'POST', 'requires_employee' => true),
        'delete_attachment'   => array('permission' => 'edit',   'method' => 'POST', 'requires_employee' => true),
        'list_offices'            => array('permission' => 'configure', 'method' => 'GET',  'global' => true),
        'save_office'             => array('permission' => 'configure', 'method' => 'POST', 'global' => true),
        'set_office_status'       => array('permission' => 'configure', 'method' => 'POST', 'global' => true),
        'list_office_assignments' => array('permission' => 'configure', 'method' => 'GET',  'global' => true),
        'save_office_assignment'  => array('permission' => 'configure', 'method' => 'POST', 'global' => true),
        'search_employees'        => array('permission' => 'configure', 'method' => 'GET',  'global' => true),
        // Who may decide is checked per request and stage (role scope, named approver, escalation, delegation),
        // so delegates without the approve permission can act; 'view' is only the entry gate.
        'list_approvals'      => array('permission' => 'view',    'method' => 'GET',  'requires_employee' => true),
        'decide_approval'     => array('permission' => 'view',    'method' => 'POST', 'requires_employee' => true),
        'reopen_request'      => array('permission' => 'approve', 'method' => 'POST', 'requires_employee' => true, 'global' => true),
        // The audit export also checks that the request is inside the actor's audit scope.
        'export_timeline'     => array('permission' => 'audit',   'method' => 'GET'),
        // Payments: scope and the no-self-payment rule (allow_self_approval) are checked per request.
        'list_payable'          => array('permission' => 'pay', 'method' => 'GET'),
        'list_payments'         => array('permission' => 'pay', 'method' => 'GET'),
        'export_payments'       => array('permission' => 'pay', 'method' => 'GET'),
        'get_payment_form'      => array('permission' => 'pay', 'method' => 'GET',  'requires_employee' => true),
        'record_payment'        => array('permission' => 'pay', 'method' => 'POST', 'requires_employee' => true),
        'change_payment_status' => array('permission' => 'pay', 'method' => 'POST', 'requires_employee' => true),
        // Advance settlements: holder, scope, no-self and reviewer-is-not-verifier rules are checked per advance and claim.
        'get_settlement_form'          => array('permission' => 'submit',  'method' => 'GET',  'requires_employee' => true),
        'save_settlement'              => array('permission' => 'submit',  'method' => 'POST', 'requires_employee' => true),
        'submit_settlement'            => array('permission' => 'submit',  'method' => 'POST', 'requires_employee' => true),
        'cancel_settlement'            => array('permission' => 'submit',  'method' => 'POST', 'requires_employee' => true),
        'upload_settlement_document'   => array('permission' => 'submit',  'method' => 'POST', 'requires_employee' => true),
        'delete_settlement_document'   => array('permission' => 'submit',  'method' => 'POST', 'requires_employee' => true),
        'download_settlement_document' => array('permission' => 'view',    'method' => 'GET'),
        'list_settlement_claims'       => array('permission' => 'view',    'method' => 'GET'),
        'list_advances'                => array('permission' => 'view',    'method' => 'GET'),
        'export_advances'              => array('permission' => 'export',  'method' => 'GET'),
        'review_settlement'            => array('permission' => 'approve', 'method' => 'POST', 'requires_employee' => true),
        'verify_settlement'            => array('permission' => 'pay',     'method' => 'POST', 'requires_employee' => true),
        'record_settlement_txn'        => array('permission' => 'pay',     'method' => 'POST', 'requires_employee' => true),
        'change_settlement_txn_status' => array('permission' => 'pay',     'method' => 'POST', 'requires_employee' => true),
        'close_settlement'             => array('permission' => 'pay',     'method' => 'POST', 'requires_employee' => true),
        // Direct and recurring expenses: rule managers (Finance with pay scope over the office, or the owning requester)
        // and record visibility are checked per office and rule.
        'list_direct_expenses'    => array('permission' => 'view',   'method' => 'GET'),
        'export_direct_expenses'  => array('permission' => 'export', 'method' => 'GET'),
        'search_vendors'          => array('permission' => 'view',   'method' => 'GET',  'requires_employee' => true),
        'list_recurring_rules'    => array('permission' => 'view',   'method' => 'GET'),
        'get_recurring_rule'      => array('permission' => 'view',   'method' => 'GET'),
        'get_recurring_form'      => array('permission' => 'view',   'method' => 'GET',  'requires_employee' => true),
        'save_recurring_rule'     => array('permission' => 'view',   'method' => 'POST', 'requires_employee' => true),
        'set_recurring_status'    => array('permission' => 'view',   'method' => 'POST', 'requires_employee' => true),
        'generate_recurring_now'  => array('permission' => 'view',   'method' => 'POST', 'requires_employee' => true),
        'list_categories'     => array('permission' => 'configure', 'method' => 'GET',  'global' => true),
        'get_category_audit'  => array('permission' => 'configure', 'method' => 'GET',  'global' => true),
        'save_category'       => array('permission' => 'configure', 'method' => 'POST', 'global' => true),
        'set_category_status' => array('permission' => 'configure', 'method' => 'POST', 'global' => true),
        'delete_category'     => array('permission' => 'configure', 'method' => 'POST', 'global' => true),
        'list_budgets'       => array('permission' => 'budgets', 'method' => 'GET',  'global' => true),
        'save_budget'        => array('permission' => 'budgets', 'method' => 'POST', 'global' => true),
        'adjust_budget'      => array('permission' => 'budgets', 'method' => 'POST', 'global' => true),
        'set_budget_status'  => array('permission' => 'budgets', 'method' => 'POST', 'global' => true),
        'get_budget_history' => array('permission' => 'budgets', 'method' => 'GET',  'global' => true),
        'list_policies'           => array('permission' => 'policies', 'method' => 'GET',  'global' => true),
        'get_policy'              => array('permission' => 'policies', 'method' => 'GET',  'global' => true),
        'save_policy'             => array('permission' => 'policies', 'method' => 'POST', 'global' => true),
        'publish_policy'          => array('permission' => 'policies', 'method' => 'POST', 'global' => true),
        'new_policy_version'      => array('permission' => 'policies', 'method' => 'POST', 'global' => true),
        'retire_policy'           => array('permission' => 'policies', 'method' => 'POST', 'global' => true),
        'delete_policy_draft'     => array('permission' => 'policies', 'method' => 'POST', 'global' => true),
        'get_policy_history'      => array('permission' => 'policies', 'method' => 'GET',  'global' => true),
        'test_policy'             => array('permission' => 'policies', 'method' => 'GET',  'global' => true),
        'search_policy_employees' => array('permission' => 'policies', 'method' => 'GET',  'global' => true),
        'list_delegations'        => array('permission' => 'policies', 'method' => 'GET',  'global' => true),
        'save_delegation'         => array('permission' => 'policies', 'method' => 'POST', 'global' => true),
        'cancel_delegation'       => array('permission' => 'policies', 'method' => 'POST', 'global' => true),
    ),
);
