<?php
// admin/crm/controller/crm_controller.php

function insertAuditLog($conn, $actionType, $moduleName, $recordID, $oldValue, $newValue, $employeeID) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $browser = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $actionDate = date('Y-m-d');
    $actionTime = date('H:i:s');
    
    $oldValEscaped = mysqli_real_escape_string($conn, json_encode($oldValue));
    $newValEscaped = mysqli_real_escape_string($conn, json_encode($newValue));
    
    $sql = "INSERT INTO crm_audit_logs (ActionType, ModuleName, RecordID, OldValue, NewValue, IPAddress, Browser, ActionDate, ActionTime, EmployeeID) 
            VALUES ('$actionType', '$moduleName', '$recordID', '$oldValEscaped', '$newValEscaped', '$ip', '$browser', '$actionDate', '$actionTime', '$employeeID')";
    mysqli_query($conn, $sql);
}

function getAllLeads($conn) {
    $sql = "SELECT * FROM crm_leads WHERE IsActive = 1 ORDER BY ID DESC";
    $result = mysqli_query($conn, $sql);
    $leads = [];
    if($result && mysqli_num_rows($result) > 0) {
        while($row = mysqli_fetch_assoc($result)) {
            $leads[] = $row;
        }
    }
    return $leads;
}

function insertLead($conn, $data) {
    $LeadName = mysqli_real_escape_string($conn, $data['LeadName']);
    $CompanyName = mysqli_real_escape_string($conn, $data['CompanyName']);
    $Email = mysqli_real_escape_string($conn, $data['Email']);
    $Phone = mysqli_real_escape_string($conn, $data['Phone']);
    $LeadSource = mysqli_real_escape_string($conn, $data['LeadSource']);
    $LeadStatus = mysqli_real_escape_string($conn, $data['LeadStatus']);
    $Notes = isset($data['Notes']) ? mysqli_real_escape_string($conn, $data['Notes']) : '';
    $CreatedBy = isset($data['CreatedBy']) ? (int)$data['CreatedBy'] : 0;
    $CreatedDate = date('Y-m-d');
    $CreatedTime = date('H:i:s');

    // Duplicate Check
    $dup_sql = "SELECT ID FROM crm_leads WHERE (Email = '$Email' AND Email != '') OR (Phone = '$Phone' AND Phone != '') LIMIT 1";
    $dup_res = mysqli_query($conn, $dup_sql);
    $IsDuplicate = ($dup_res && mysqli_num_rows($dup_res) > 0) ? 1 : 0;

    $sql = "INSERT INTO crm_leads (LeadName, CompanyName, Email, Phone, LeadSource, LeadStatus, Notes, IsDuplicate, CreatedBy, CreatedDate, CreatedTime, IsActive) 
            VALUES ('$LeadName', '$CompanyName', '$Email', '$Phone', '$LeadSource', '$LeadStatus', '$Notes', $IsDuplicate, $CreatedBy, '$CreatedDate', '$CreatedTime', 1)";
    
    if(mysqli_query($conn, $sql)) {
        $last_id = mysqli_insert_id($conn);
        insertAuditLog($conn, 'Insert', 'Leads', $last_id, null, $data, $CreatedBy);
        return ['error' => false, 'message' => 'Lead created successfully' . ($IsDuplicate ? ' (Marked as duplicate)' : ''), 'last_insert_id' => $last_id];
    } else {
        return ['error' => true, 'message' => 'Failed to create lead: ' . mysqli_error($conn)];
    }
}

function getAllOpportunities($conn) {
    $sql = "SELECT o.*, a.AccountName, c.FirstName, c.LastName FROM crm_opportunities o 
            LEFT JOIN crm_accounts a ON o.AccountID = a.ID 
            LEFT JOIN crm_contacts c ON o.ContactID = c.ID 
            WHERE o.IsActive = 1 ORDER BY o.ID DESC";
    $result = mysqli_query($conn, $sql);
    $opportunities = [];
    if($result && mysqli_num_rows($result) > 0) {
        while($row = mysqli_fetch_assoc($result)) {
            $opportunities[] = $row;
        }
    }
    return $opportunities;
}

function insertCustomer($conn, $data) {
    $AccountName = mysqli_real_escape_string($conn, $data['AccountName']);
    $ContactPerson = mysqli_real_escape_string($conn, $data['ContactPerson']);
    $Email = mysqli_real_escape_string($conn, $data['Email']);
    $Mobile = mysqli_real_escape_string($conn, $data['Mobile']);
    $Phone = mysqli_real_escape_string($conn, $data['Phone']);
    $GST = mysqli_real_escape_string($conn, $data['GST']);
    $PAN = mysqli_real_escape_string($conn, $data['PAN']);
    $CustomerType = mysqli_real_escape_string($conn, $data['CustomerType']);
    $Source = mysqli_real_escape_string($conn, $data['Source']);
    $CreatedBy = isset($data['CreatedBy']) ? (int)$data['CreatedBy'] : 0;
    $CreatedDate = date('Y-m-d');
    $CreatedTime = date('H:i:s');

    // Duplicate Check
    $dup_sql = "SELECT ID FROM crm_accounts WHERE (Email = '$Email' AND Email != '') OR (Mobile = '$Mobile' AND Mobile != '') LIMIT 1";
    $dup_res = mysqli_query($conn, $dup_sql);
    if($dup_res && mysqli_num_rows($dup_res) > 0) {
        return ['error' => true, 'message' => 'Failed: Customer with this Email or Mobile already exists.'];
    }

    $sql = "INSERT INTO crm_accounts (AccountName, ContactPerson, Email, Mobile, Phone, GST, PAN, CustomerType, Source, CreatedBy, CreatedDate, CreatedTime, IsActive) 
            VALUES ('$AccountName', '$ContactPerson', '$Email', '$Mobile', '$Phone', '$GST', '$PAN', '$CustomerType', '$Source', $CreatedBy, '$CreatedDate', '$CreatedTime', 1)";
    
    if(mysqli_query($conn, $sql)) {
        $last_id = mysqli_insert_id($conn);
        insertAuditLog($conn, 'Insert', 'Customers', $last_id, null, $data, $CreatedBy);
        return ['error' => false, 'message' => 'Customer created successfully', 'last_insert_id' => $last_id];
    } else {
        return ['error' => true, 'message' => 'Failed to create customer: ' . mysqli_error($conn)];
    }
}

function getAllProducts($conn) {
    $sql = "SELECT * FROM crm_products WHERE IsActive = 1 ORDER BY ID DESC";
    $result = mysqli_query($conn, $sql);
    $products = [];
    if($result && mysqli_num_rows($result) > 0) {
        while($row = mysqli_fetch_assoc($result)) {
            $products[] = $row;
        }
    }
    return $products;
}

function insertProduct($conn, $data) {
    $ProductName = mysqli_real_escape_string($conn, $data['ProductName']);
    $Brand = mysqli_real_escape_string($conn, $data['Brand']);
    $SKU = mysqli_real_escape_string($conn, $data['SKU']);
    $HSN_SAC = mysqli_real_escape_string($conn, $data['HSN_SAC']);
    $UnitPrice = (float)$data['UnitPrice'];
    $GST_Percent = (float)$data['GST_Percent'];
    $Unit = mysqli_real_escape_string($conn, $data['Unit']);
    $CreatedBy = isset($data['CreatedBy']) ? (int)$data['CreatedBy'] : 0;
    $CreatedDate = date('Y-m-d');

    $sql = "INSERT INTO crm_products (ProductName, Brand, SKU, HSN_SAC, UnitPrice, GST_Percent, Unit, CreatedBy, CreatedDate, IsActive) 
            VALUES ('$ProductName', '$Brand', '$SKU', '$HSN_SAC', $UnitPrice, $GST_Percent, '$Unit', $CreatedBy, '$CreatedDate', 1)";
    
    if(mysqli_query($conn, $sql)) {
        $last_id = mysqli_insert_id($conn);
        insertAuditLog($conn, 'Insert', 'Products', $last_id, null, $data, $CreatedBy);
        return ['error' => false, 'message' => 'Product created successfully', 'last_insert_id' => $last_id];
    } else {
        return ['error' => true, 'message' => 'Failed to create product: ' . mysqli_error($conn)];
    }
}

function getAllQuotations($conn) {
    $sql = "SELECT q.*, l.LeadName, a.AccountName FROM crm_quotations q 
            LEFT JOIN crm_leads l ON q.LeadID = l.ID
            LEFT JOIN crm_accounts a ON q.AccountID = a.ID
            WHERE q.IsActive = 1 ORDER BY q.ID DESC";
    $result = mysqli_query($conn, $sql);
    $quotes = [];
    if($result && mysqli_num_rows($result) > 0) {
        while($row = mysqli_fetch_assoc($result)) {
            $quotes[] = $row;
        }
    }
    return $quotes;
}

function insertQuotation($conn, $data) {
    // Basic Quotation Info
    $LeadID = (int)($data['LeadID'] ?? 0);
    $AccountID = (int)($data['AccountID'] ?? 0);
    $QuoteDate = mysqli_real_escape_string($conn, $data['QuoteDate']);
    $ValidUntil = mysqli_real_escape_string($conn, $data['ValidUntil']);
    $TermsConditions = mysqli_real_escape_string($conn, $data['TermsConditions'] ?? '');
    $CreatedBy = (int)($data['CreatedBy'] ?? 0);
    $CreatedDate = date('Y-m-d');
    
    // Auto-generate QuoteNumber
    $res = mysqli_query($conn, "SELECT MAX(ID) as maxid FROM crm_quotations");
    $row = mysqli_fetch_assoc($res);
    $next_id = ($row['maxid'] ?? 0) + 1;
    $QuoteNumber = "QT-" . date('Y') . "-" . str_pad($next_id, 4, '0', STR_PAD_LEFT);

    // Totals
    $SubTotal = (float)($data['SubTotal'] ?? 0);
    $Discount = (float)($data['Discount'] ?? 0);
    $TotalTax = (float)($data['TotalTax'] ?? 0);
    $GrandTotal = (float)($data['GrandTotal'] ?? 0);

    $sql = "INSERT INTO crm_quotations (QuoteNumber, LeadID, AccountID, QuoteDate, ValidUntil, SubTotal, Discount, TotalTax, GrandTotal, TermsConditions, Status, CreatedBy, CreatedDate, IsActive) 
            VALUES ('$QuoteNumber', " . ($LeadID ? $LeadID : "NULL") . ", " . ($AccountID ? $AccountID : "NULL") . ", '$QuoteDate', '$ValidUntil', $SubTotal, $Discount, $TotalTax, $GrandTotal, '$TermsConditions', 'Draft', $CreatedBy, '$CreatedDate', 1)";
            
    if(mysqli_query($conn, $sql)) {
        $QuoteID = mysqli_insert_id($conn);
        
        // Insert Items
        if(isset($data['items']) && is_array($data['items'])) {
            foreach($data['items'] as $item) {
                $ProductID = (int)$item['ProductID'];
                $Description = mysqli_real_escape_string($conn, $item['Description'] ?? '');
                $Qty = (float)$item['Quantity'];
                $Price = (float)$item['UnitPrice'];
                $GST = (float)$item['GST_Percent'];
                $TotalAmt = (float)$item['TotalAmount'];
                
                $item_sql = "INSERT INTO crm_quotation_items (QuotationID, ProductID, Description, Quantity, UnitPrice, GST_Percent, TotalAmount) 
                             VALUES ($QuoteID, $ProductID, '$Description', $Qty, $Price, $GST, $TotalAmt)";
                mysqli_query($conn, $item_sql);
            }
        }
        
        insertAuditLog($conn, 'Insert', 'Quotations', $QuoteID, null, $data, $CreatedBy);
        return ['error' => false, 'message' => 'Quotation created successfully', 'last_insert_id' => $QuoteID];
    } else {
        return ['error' => true, 'message' => 'Failed to create quotation: ' . mysqli_error($conn)];
    }
}
?>
