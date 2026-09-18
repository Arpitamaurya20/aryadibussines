<?php
// Connect to the DB the user actually uses
$conn = mysqli_connect("localhost", "root", "", "aryadibussiness");
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// 1. Categories
$q_cat = "CREATE TABLE IF NOT EXISTS `crm_categories` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `CategoryName` varchar(150) NOT NULL,
  `Description` text,
  `IsActive` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
mysqli_query($conn, $q_cat);

// 2. Products
$q_prod = "CREATE TABLE IF NOT EXISTS `crm_products` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `CategoryID` int(11) DEFAULT NULL,
  `ProductName` varchar(255) NOT NULL,
  `Brand` varchar(100) DEFAULT NULL,
  `SKU` varchar(100) DEFAULT NULL,
  `HSN_SAC` varchar(50) DEFAULT NULL,
  `UnitPrice` decimal(15,2) DEFAULT '0.00',
  `GST_Percent` decimal(5,2) DEFAULT '0.00',
  `Unit` varchar(50) DEFAULT 'Nos',
  `InventoryStock` int(11) DEFAULT '0',
  `CreatedBy` int(11) DEFAULT NULL,
  `CreatedDate` date DEFAULT NULL,
  `IsActive` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
mysqli_query($conn, $q_prod);

// 3. Quotations
$q_quote = "CREATE TABLE IF NOT EXISTS `crm_quotations` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `QuoteNumber` varchar(50) NOT NULL,
  `LeadID` int(11) DEFAULT NULL,
  `AccountID` int(11) DEFAULT NULL,
  `QuoteDate` date NOT NULL,
  `ValidUntil` date DEFAULT NULL,
  `SubTotal` decimal(15,2) DEFAULT '0.00',
  `Discount` decimal(15,2) DEFAULT '0.00',
  `TotalTax` decimal(15,2) DEFAULT '0.00',
  `GrandTotal` decimal(15,2) DEFAULT '0.00',
  `TermsConditions` text,
  `Status` varchar(50) DEFAULT 'Draft', -- Draft, Pending, Approved, Rejected, Accepted
  `CreatedBy` int(11) DEFAULT NULL,
  `CreatedDate` date DEFAULT NULL,
  `IsActive` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
mysqli_query($conn, $q_quote);

// 4. Quotation Items
$q_items = "CREATE TABLE IF NOT EXISTS `crm_quotation_items` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `QuotationID` int(11) NOT NULL,
  `ProductID` int(11) NOT NULL,
  `Description` text,
  `Quantity` decimal(10,2) DEFAULT '1.00',
  `UnitPrice` decimal(15,2) DEFAULT '0.00',
  `GST_Percent` decimal(5,2) DEFAULT '0.00',
  `TotalAmount` decimal(15,2) DEFAULT '0.00',
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
mysqli_query($conn, $q_items);

// 5. Invoices
$q_inv = "CREATE TABLE IF NOT EXISTS `crm_invoices` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `InvoiceNumber` varchar(50) NOT NULL,
  `QuotationID` int(11) DEFAULT NULL,
  `AccountID` int(11) DEFAULT NULL,
  `InvoiceDate` date NOT NULL,
  `DueDate` date DEFAULT NULL,
  `GrandTotal` decimal(15,2) DEFAULT '0.00',
  `AmountPaid` decimal(15,2) DEFAULT '0.00',
  `Status` varchar(50) DEFAULT 'Draft', -- Draft, Generated, Paid, Partial, Overdue
  `CreatedBy` int(11) DEFAULT NULL,
  `CreatedDate` date DEFAULT NULL,
  `IsActive` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
mysqli_query($conn, $q_inv);

// 6. Payments
$q_pay = "CREATE TABLE IF NOT EXISTS `crm_payments` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `InvoiceID` int(11) NOT NULL,
  `PaymentDate` date NOT NULL,
  `Amount` decimal(15,2) NOT NULL,
  `PaymentMethod` varchar(50) DEFAULT 'Cash', -- Cash, UPI, Cheque, NEFT
  `TransactionID` varchar(150) DEFAULT NULL,
  `Notes` text,
  `CreatedBy` int(11) DEFAULT NULL,
  `CreatedDate` date DEFAULT NULL,
  `IsActive` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
mysqli_query($conn, $q_pay);

echo "Phase 3 Database setup complete for Products, Quotations, Invoices, and Payments!\n";
?>
