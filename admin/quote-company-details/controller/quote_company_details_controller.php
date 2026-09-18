<?php

function uploadQuoteHeaderImage($file)
{
    $response = array('error' => true, 'message' => 'Invalid header image upload.');
    if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        $response['message'] = 'Header image upload failed.';
        return $response;
    }

    $allowedExtensions = array('jpg', 'jpeg', 'png', 'gif', 'webp');
    $originalName = isset($file['name']) ? strtolower((string) $file['name']) : '';
    $ext = pathinfo($originalName, PATHINFO_EXTENSION);
    if (!in_array($ext, $allowedExtensions, true)) {
        $response['message'] = 'Only image files are allowed (JPG, JPEG, PNG, WEBP, GIF).';
        return $response;
    }

    $mimeType = '';
    if (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo) {
            $mimeType = (string) finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);
        }
    }
    if ($mimeType === '' && function_exists('mime_content_type')) {
        $mimeType = (string) mime_content_type($file['tmp_name']);
    }
    if ($mimeType !== '' && strpos($mimeType, 'image/') !== 0) {
        $response['message'] = 'Only image files are allowed for header image.';
        return $response;
    }

    $targetDir = dirname(__DIR__) . '/../media/pdf-assets/';
    if (!is_dir($targetDir) && !mkdir($targetDir, 0777, true)) {
        $response['message'] = 'Unable to create upload directory.';
        return $response;
    }

    $newFileName = 'quote-header-' . date('YmdHis') . '-' . mt_rand(1000, 9999) . '.' . $ext;
    $targetPath = $targetDir . $newFileName;

    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        $response['message'] = 'Unable to save uploaded header image.';
        return $response;
    }

    return array('error' => false, 'file_name' => $newFileName);
}

function uploadQuoteStampImage($file)
{
    $response = array('error' => true, 'message' => 'Invalid stamp image upload.');
    if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        $response['message'] = 'Stamp image upload failed.';
        return $response;
    }

    $allowedExtensions = array('jpg', 'jpeg', 'png', 'gif', 'webp');
    $originalName = isset($file['name']) ? strtolower((string) $file['name']) : '';
    $ext = pathinfo($originalName, PATHINFO_EXTENSION);
    if (!in_array($ext, $allowedExtensions, true)) {
        $response['message'] = 'Only image files are allowed (JPG, JPEG, PNG, WEBP, GIF).';
        return $response;
    }

    $mimeType = '';
    if (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo) {
            $mimeType = (string) finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);
        }
    }
    if ($mimeType === '' && function_exists('mime_content_type')) {
        $mimeType = (string) mime_content_type($file['tmp_name']);
    }
    if ($mimeType !== '' && strpos($mimeType, 'image/') !== 0) {
        $response['message'] = 'Only image files are allowed for stamp image.';
        return $response;
    }

    $targetDir = dirname(__DIR__) . '/../media/pdf-assets/';
    if (!is_dir($targetDir) && !mkdir($targetDir, 0777, true)) {
        $response['message'] = 'Unable to create upload directory.';
        return $response;
    }

    $newFileName = 'quote-stamp-' . date('YmdHis') . '-' . mt_rand(1000, 9999) . '.' . $ext;
    $targetPath = $targetDir . $newFileName;

    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        $response['message'] = 'Unable to save uploaded stamp image.';
        return $response;
    }

    return array('error' => false, 'file_name' => $newFileName);
}

function getTotalQuoteCompanyDetails($conn)
{
    $sql = "SELECT COUNT(*) AS cnt FROM quote_company_details";
    $result = mysqli_query($conn, $sql);
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return (int) $row['cnt'];
    }
    return 0;
}

function getQuoteCompanyDetailsById($conn, $id)
{
    $id = (int) $id;
    if ($id <= 0) {
        return array();
    }
    $where = " WHERE ID = $id";
    return _getTableDetails($conn, 'quote_company_details', $where);
}

function insertQuoteCompanyDetails($conn, $data, $files = array())
{
    $response = array('error' => true, 'message' => 'Technical problem. Please try again.');
    $CompanyName = cleantext($data['CompanyName']);
    $CompanyAddress = cleantext($data['CompanyAddress']);
    $GstNumber = cleantext($data['GstNumber']);
    $PanNumber = cleantext($data['PanNumber']);
    $Email = cleantext($data['Email']);
    $Phone = cleantext($data['Phone']);
    if ($CompanyName === '') {
        $response['message'] = 'Company name is required.';
        return $response;
    }

    $HeaderImage = '';
    $StampImage = '';
    if (isset($files['HeaderImageFile']) && isset($files['HeaderImageFile']['error']) && (int) $files['HeaderImageFile']['error'] !== UPLOAD_ERR_NO_FILE) {
        if ((int) $files['HeaderImageFile']['error'] !== UPLOAD_ERR_OK) {
            $response['message'] = 'Header image upload failed.';
            return $response;
        }
        $uploadResult = uploadQuoteHeaderImage($files['HeaderImageFile']);
        if ($uploadResult['error']) {
            $response['message'] = $uploadResult['message'];
            return $response;
        }
        $HeaderImage = cleantext($uploadResult['file_name']);
    }
    if (isset($files['StampImageFile']) && isset($files['StampImageFile']['error']) && (int) $files['StampImageFile']['error'] !== UPLOAD_ERR_NO_FILE) {
        if ((int) $files['StampImageFile']['error'] !== UPLOAD_ERR_OK) {
            $response['message'] = 'Stamp image upload failed.';
            return $response;
        }
        $uploadResult = uploadQuoteStampImage($files['StampImageFile']);
        if ($uploadResult['error']) {
            $response['message'] = $uploadResult['message'];
            return $response;
        }
        $StampImage = cleantext($uploadResult['file_name']);
    }

    $sql = "INSERT INTO quote_company_details (CompanyName, CompanyAddress, GstNumber, PanNumber, Email, Phone, HeaderImage, StampImage, IsActive) VALUES ('$CompanyName','$CompanyAddress','$GstNumber','$PanNumber','$Email','$Phone','$HeaderImage','$StampImage', 1)";
    $r = _InsertTableRecords($conn, $sql);
    if (!$r['error']) {
        $response['error'] = false;
        $response['message'] = 'Company quote details added successfully.';
    } else {
        $response['message'] = isset($r['message']) ? $r['message'] : $response['message'];
    }
    return $response;
}

function updateQuoteCompanyDetails($conn, $data, $files = array())
{
    $response = array('error' => true, 'message' => 'Technical problem. Please try again.');
    $ID = (int) $data['ID'];
    if ($ID <= 0) {
        $response['message'] = 'Invalid record.';
        return $response;
    }
    $CompanyName = cleantext($data['CompanyName']);
    $CompanyAddress = cleantext($data['CompanyAddress']);
    $GstNumber = cleantext($data['GstNumber']);
    $PanNumber = cleantext($data['PanNumber']);
    $Email = cleantext($data['Email']);
    $Phone = cleantext($data['Phone']);
    if ($CompanyName === '') {
        $response['message'] = 'Company name is required.';
        return $response;
    }

    $existing = getQuoteCompanyDetailsById($conn, $ID);
    $existingHeaderImage = '';
    $existingStampImage = '';
    if (!empty($existing) && isset($existing['HeaderImage'])) {
        $existingHeaderImage = cleantext($existing['HeaderImage']);
    }
    if (!empty($existing) && isset($existing['StampImage'])) {
        $existingStampImage = cleantext($existing['StampImage']);
    }

    $newHeaderImage = $existingHeaderImage;
    $newStampImage = $existingStampImage;
    if (isset($files['HeaderImageFile']) && isset($files['HeaderImageFile']['error']) && (int) $files['HeaderImageFile']['error'] !== UPLOAD_ERR_NO_FILE) {
        if ((int) $files['HeaderImageFile']['error'] !== UPLOAD_ERR_OK) {
            $response['message'] = 'Header image upload failed.';
            return $response;
        }
        $uploadResult = uploadQuoteHeaderImage($files['HeaderImageFile']);
        if ($uploadResult['error']) {
            $response['message'] = $uploadResult['message'];
            return $response;
        }
        $newHeaderImage = cleantext($uploadResult['file_name']);
    }
    if (isset($files['StampImageFile']) && isset($files['StampImageFile']['error']) && (int) $files['StampImageFile']['error'] !== UPLOAD_ERR_NO_FILE) {
        if ((int) $files['StampImageFile']['error'] !== UPLOAD_ERR_OK) {
            $response['message'] = 'Stamp image upload failed.';
            return $response;
        }
        $uploadResult = uploadQuoteStampImage($files['StampImageFile']);
        if ($uploadResult['error']) {
            $response['message'] = $uploadResult['message'];
            return $response;
        }
        $newStampImage = cleantext($uploadResult['file_name']);
    }

    $query_parameter = " CompanyName = '$CompanyName', CompanyAddress = '$CompanyAddress', GstNumber = '$GstNumber', PanNumber = '$PanNumber', Email = '$Email', Phone = '$Phone', HeaderImage = '$newHeaderImage', StampImage = '$newStampImage' WHERE ID = $ID";
    $r = _UpdateTableRecords($conn, 'quote_company_details', $query_parameter);
    if (!$r['error']) {
        if ($newHeaderImage !== $existingHeaderImage && $existingHeaderImage !== '') {
            $oldFilePath = dirname(__DIR__) . '/../media/pdf-assets/' . basename($existingHeaderImage);
            if (is_file($oldFilePath)) {
                @unlink($oldFilePath);
            }
        }
        if ($newStampImage !== $existingStampImage && $existingStampImage !== '') {
            $oldFilePath = dirname(__DIR__) . '/../media/pdf-assets/' . basename($existingStampImage);
            if (is_file($oldFilePath)) {
                @unlink($oldFilePath);
            }
        }
        $response['error'] = false;
        $response['message'] = 'Company quote details updated successfully.';
    } else {
        $response['message'] = isset($r['message']) ? $r['message'] : $response['message'];
    }
    return $response;
}

function toggleQuoteCompanyDetailsActive($conn, $id, $isActive)
{
    $response = array('error' => true, 'message' => 'Technical problem. Please try again.');
    $ID = (int) $id;
    $IsActive = ((int) $isActive === 1) ? 1 : 0;
    if ($ID <= 0) {
        $response['message'] = 'Invalid record.';
        return $response;
    }
    $query_parameter = " IsActive = $IsActive WHERE ID = $ID";
    $r = _UpdateTableRecords($conn, 'quote_company_details', $query_parameter);
    if (!$r['error']) {
        $response['error'] = false;
        $response['message'] = $IsActive === 1 ? 'Record activated.' : 'Record deactivated (soft delete).';
    } else {
        $response['message'] = isset($r['message']) ? $r['message'] : $response['message'];
    }
    return $response;
}
