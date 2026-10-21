<?php
ob_start();
session_start();
require_once('../../controllers/common_controllers.php');
require_once('../controller/fp_access.php');
require_once('../controller/fp_audit.php');
require_once('../controller/fp_categories.php');
setTimeZone();

fp_run_action('list_categories', function ($conn, $actor) {
    $companies = array();
    foreach (fp_category_companies($conn) as $company) {
        if ((int) $company['IsActive'] === 1) {
            $companies[] = array('ID' => (int) $company['ID'], 'CompanyName' => $company['CompanyName']);
        }
    }
    return fp_result(true, 200, 'OK', array('data' => array(
        'categories' => fp_category_list($conn),
        'companies'  => $companies,
    )));
});
