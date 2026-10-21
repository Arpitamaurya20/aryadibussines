<?php
/**
 * Vendor master lookups for direct and recurring expenses. Loaded by fp_requests.php.
 *
 * The ERP vendor master is vendor_registration (registered from the mobile app, approved by the State Manager and HR).
 * This module only reads it and never adds or changes vendors there. Approved, active vendors can be chosen as payees;
 * their name and GSTIN are then always taken from the master. PAN, Aadhaar, mobile, e-mail, documents and full bank
 * account numbers are never returned.
 */

const FP_VENDOR_SEARCH_LIMIT = 20;

/**
 * 'optional': a payee that is not in the vendor master (for example a landlord or an electricity board) may be typed in.
 * 'required': only approved registered vendors can be paid directly.
 */
function fp_vendor_master_mode()
{
    return (fp_access_matrix()['direct_expense_vendor_master'] ?? 'optional') === 'required' ? 'required' : 'optional';
}

function fp_vendor_columns()
{
    return 'v.ID, v.RegistrationCode, v.Name, v.BusinessName, v.GstNumber, v.VendorCategory, v.City, v.StateName, v.AccountName, v.AccountNumber';
}

function fp_vendor_display_name($row)
{
    $business = trim((string) $row['BusinessName']);
    return $business !== '' ? $business : trim((string) $row['Name']);
}

function fp_vendor_account_hint($row)
{
    $masked = fp_payment_mask_account($row['AccountNumber'] ?? '');
    $name = trim((string) ($row['AccountName'] ?? ''));
    return trim($name . ($masked !== '' ? ($name !== '' ? ', ' : '') . 'A/c ' . $masked : ''));
}

function fp_vendor_public($row)
{
    $gstin = strtoupper(trim((string) $row['GstNumber']));
    return array(
        'ID'          => (int) $row['ID'],
        'Code'        => $row['RegistrationCode'],
        'Name'        => fp_vendor_display_name($row),
        'GSTIN'       => preg_match('/^\d{2}[A-Z0-9]{13}$/', $gstin) ? $gstin : '',
        'Category'    => (string) $row['VendorCategory'],
        'Place'       => implode(', ', array_filter(array(trim((string) $row['City']), trim((string) $row['StateName'])))),
        'AccountHint' => fp_vendor_account_hint($row),
    );
}

/**
 * An approved, active registered vendor, or null.
 */
function fp_vendor_get($conn, $vendorId)
{
    if ((int) $vendorId <= 0 || !fp_table_exists($conn, 'vendor_registration')) {
        return null;
    }
    $rows = fp_db_rows(
        $conn,
        'SELECT ' . fp_vendor_columns() . ' FROM vendor_registration v WHERE v.ID = ? AND v.IsActive = 1 AND v.Status = \'Approved\'',
        'i',
        array((int) $vendorId)
    );
    return $rows ? fp_vendor_public($rows[0]) : null;
}

/**
 * Registration code and name of any registered vendor (also no longer approved), for showing stored links.
 */
function fp_vendor_labels($conn, $vendorIds)
{
    $ids = fp_unique_ints($vendorIds);
    if (!$ids || !fp_table_exists($conn, 'vendor_registration')) {
        return array();
    }
    $labels = array();
    foreach (fp_db_rows($conn, 'SELECT ' . fp_vendor_columns() . ', v.Status, v.IsActive FROM vendor_registration v WHERE v.ID IN (' . implode(',', $ids) . ')') as $row) {
        $labels[(int) $row['ID']] = array(
            'Code'        => $row['RegistrationCode'],
            'Name'        => fp_vendor_display_name($row),
            'Approved'    => $row['Status'] === 'Approved' && (int) $row['IsActive'] === 1,
            'AccountHint' => fp_vendor_account_hint($row),
        );
    }
    return $labels;
}

/**
 * Who may look vendors up: people who raise requests and Finance (who set up recurring payments).
 */
function fp_vendor_can_search($actor)
{
    return fp_is_requester($actor) || fp_can($actor, 'pay');
}

function fp_vendor_search($conn, $actor, $params)
{
    if (!fp_vendor_can_search($actor)) {
        return fp_result(false, 403, 'You do not have permission to look up vendors.');
    }
    $search = mb_substr(preg_replace('/\s+/u', ' ', fp_input_string($params, 'q')), 0, 60);
    if (mb_strlen($search) < 2 || !fp_table_exists($conn, 'vendor_registration')) {
        return fp_result(true, 200, 'OK', array('data' => array()));
    }
    $like = '%' . addcslashes($search, '%_\\') . '%';
    $rows = fp_db_rows(
        $conn,
        'SELECT ' . fp_vendor_columns() . '
         FROM vendor_registration v
         WHERE v.IsActive = 1 AND v.Status = \'Approved\'
           AND (v.BusinessName LIKE ? OR v.Name LIKE ? OR v.GstNumber LIKE ? OR v.RegistrationCode LIKE ?)
         ORDER BY COALESCE(NULLIF(TRIM(v.BusinessName), \'\'), v.Name), v.ID
         LIMIT ' . FP_VENDOR_SEARCH_LIMIT,
        'ssss',
        array($like, $like, $like, $like)
    );
    return fp_result(true, 200, 'OK', array('data' => array_map('fp_vendor_public', $rows)));
}

/**
 * Approved registered vendor with the same GSTIN or business name as a typed payee, or null.
 */
function fp_vendor_find_registered($conn, $name, $gstin)
{
    if (($name === '' && $gstin === '') || !fp_table_exists($conn, 'vendor_registration')) {
        return null;
    }
    $rows = fp_db_rows(
        $conn,
        'SELECT ' . fp_vendor_columns() . '
         FROM vendor_registration v
         WHERE v.IsActive = 1 AND v.Status = \'Approved\'
           AND ((? <> \'\' AND UPPER(TRIM(v.GstNumber)) = ?) OR (? <> \'\' AND LOWER(TRIM(COALESCE(NULLIF(TRIM(v.BusinessName), \'\'), v.Name))) = LOWER(?)))
         ORDER BY v.ID LIMIT 1',
        'ssss',
        array($gstin, $gstin, $name, $name)
    );
    return $rows ? fp_vendor_public($rows[0]) : null;
}

function fp_vendor_input($post)
{
    return array(
        'VendorRegistrationID' => fp_input_int($post, 'VendorRegistrationID'),
        'VendorName'           => preg_replace('/\s+/u', ' ', fp_input_string($post, 'VendorName')),
        'VendorGSTIN'          => strtoupper(fp_input_string($post, 'VendorGSTIN')),
        'VendorAccountRef'     => preg_replace('/\s+/u', ' ', fp_input_string($post, 'VendorAccountRef')),
    );
}

/**
 * Validates the payee of a direct expense or recurring rule. $requireName: a payee is needed now.
 * Returns array(errors, clean) where clean has VendorRegistrationID (int or null), VendorName, VendorGSTIN, VendorAccountRef.
 * A typed payee that matches an approved registered vendor is refused, so the same vendor is never recorded twice.
 */
function fp_vendor_resolve($conn, $in, $requireName)
{
    $errors = array();
    $clean = array('VendorRegistrationID' => null, 'VendorName' => '', 'VendorGSTIN' => '', 'VendorAccountRef' => $in['VendorAccountRef']);
    if (mb_strlen($in['VendorAccountRef']) > 60) {
        $errors['VendorAccountRef'] = 'At most 60 characters.';
    }

    if ($in['VendorRegistrationID'] > 0) {
        $vendor = fp_vendor_get($conn, $in['VendorRegistrationID']);
        if (!$vendor) {
            $errors['VendorRegistrationID'] = 'This vendor is no longer an approved, active vendor in the vendor master. Choose another vendor.';
            return array($errors, $clean);
        }
        $clean['VendorRegistrationID'] = $vendor['ID'];
        $clean['VendorName'] = mb_substr($vendor['Name'], 0, 150);
        $clean['VendorGSTIN'] = $vendor['GSTIN'];
        return array($errors, $clean);
    }

    $name = $in['VendorName'];
    $gstin = $in['VendorGSTIN'];
    if (fp_vendor_master_mode() === 'required' && ($requireName || $name !== '')) {
        $errors['VendorRegistrationID'] = 'Choose a registered vendor from the vendor master.';
    }
    if ($name === '' && $requireName) {
        $errors['VendorName'] = 'Enter the name of the vendor to be paid.';
    } elseif ($name !== '' && (mb_strlen($name) < 2 || mb_strlen($name) > 150)) {
        $errors['VendorName'] = 'Enter 2-150 characters.';
    }
    if ($gstin !== '' && !preg_match('/^\d{2}[A-Z0-9]{13}$/', $gstin)) {
        $errors['VendorGSTIN'] = 'Enter a 15-character GSTIN.';
    } elseif ($gstin !== '' && $name === '') {
        $errors['VendorName'] = 'Enter the vendor name for this GSTIN.';
    }
    if (!isset($errors['VendorName']) && !isset($errors['VendorGSTIN'])) {
        $match = fp_vendor_find_registered($conn, $name, $gstin);
        if ($match) {
            $field = $gstin !== '' && $gstin === $match['GSTIN'] ? 'VendorGSTIN' : 'VendorName';
            $errors[$field] = $match['Name'] . ' is already in the vendor master as ' . $match['Code'] . '. Choose it from the vendor list instead of typing it.';
        }
    }
    $clean['VendorName'] = $name;
    $clean['VendorGSTIN'] = $gstin;
    return array($errors, $clean);
}
