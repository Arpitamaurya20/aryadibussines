<?php
session_start();
include('../controllers/common_controllers.php');
$conn = _connectodb();

if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("Invalid Quotation ID");
}
$QuoteID = (int)$_GET['id'];

// Fetch Quote Data
$sql = "SELECT q.*, l.LeadName, l.CompanyName, l.Email, l.Phone 
        FROM crm_quotations q 
        LEFT JOIN crm_leads l ON q.LeadID = l.ID 
        WHERE q.ID = $QuoteID";
$res = mysqli_query($conn, $sql);
$quote = mysqli_fetch_assoc($res);

if (!$quote) {
    die("Quotation not found");
}

// Fetch Items
$sql_items = "SELECT qi.*, p.ProductName, p.HSN_SAC 
              FROM crm_quotation_items qi 
              LEFT JOIN crm_products p ON qi.ProductID = p.ID 
              WHERE qi.QuotationID = $QuoteID";
$res_items = mysqli_query($conn, $sql_items);
$items = [];
while ($row = mysqli_fetch_assoc($res_items)) {
    $items[] = $row;
}

// Include TCPDF
require_once($_SERVER['DOCUMENT_ROOT'] . '/projects/aryadibussines/hrms/vendor/tecnickcom/tcpdf/tcpdf.php');

// Create new PDF document
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// Set document information
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Aryadi Business');
$pdf->SetTitle('Quotation - ' . $quote['QuoteNumber']);
$pdf->SetSubject('Quotation');

// Remove default header/footer
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

// Set margins
$pdf->SetMargins(15, 15, 15);
$pdf->SetAutoPageBreak(TRUE, 15);

// Add a page
$pdf->AddPage();

// Logo (Assuming there's a logo at ../img/aryadi.png)
$logoPath = '../img/aryadi.png';
if(file_exists($logoPath)) {
    @$pdf->Image($logoPath, 15, 15, 40, '', 'PNG', '', 'T', false, 300, '', false, false, 0, false, false, false);
}

// Company Info
$pdf->SetFont('helvetica', 'B', 16);
$pdf->Cell(0, 10, 'QUOTATION', 0, 1, 'R');
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(0, 6, 'Aryadi Business Pvt. Ltd.', 0, 1, 'R');
$pdf->Cell(0, 6, 'Date: ' . date('d M Y', strtotime($quote['QuoteDate'])), 0, 1, 'R');
$pdf->Cell(0, 6, 'Quote #: ' . $quote['QuoteNumber'], 0, 1, 'R');
$pdf->Cell(0, 6, 'Valid Until: ' . date('d M Y', strtotime($quote['ValidUntil'])), 0, 1, 'R');

$pdf->Ln(10);

// Bill To
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 8, 'Bill To:', 0, 1, 'L');
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(0, 6, htmlspecialchars($quote['CompanyName'] ?: $quote['LeadName']), 0, 1, 'L');
if($quote['Email']) $pdf->Cell(0, 6, 'Email: ' . htmlspecialchars($quote['Email']), 0, 1, 'L');
if($quote['Phone'] ?? '') $pdf->Cell(0, 6, 'Phone: ' . htmlspecialchars($quote['Phone']), 0, 1, 'L');

$pdf->Ln(10);

// Items Table Header
$pdf->SetFont('helvetica', 'B', 10);
$pdf->SetFillColor(0, 63, 136); // Primary Color
$pdf->SetTextColor(255, 255, 255);
$pdf->Cell(10, 8, '#', 1, 0, 'C', true);
$pdf->Cell(60, 8, 'Product/Service', 1, 0, 'L', true);
$pdf->Cell(20, 8, 'HSN/SAC', 1, 0, 'C', true);
$pdf->Cell(15, 8, 'Qty', 1, 0, 'C', true);
$pdf->Cell(25, 8, 'Unit Price', 1, 0, 'R', true);
$pdf->Cell(15, 8, 'GST %', 1, 0, 'C', true);
$pdf->Cell(35, 8, 'Total Amount', 1, 1, 'R', true);

// Items Table Body
$pdf->SetFont('helvetica', '', 9);
$pdf->SetTextColor(0, 0, 0);
$i = 1;
foreach($items as $item) {
    $pdf->Cell(10, 8, $i++, 1, 0, 'C');
    
    // Description can be long, so we use MultiCell if needed, but for simplicity Cell is used. 
    // Truncating product name if too long to fit in 60 width.
    $productName = substr($item['ProductName'], 0, 35);
    $pdf->Cell(60, 8, $productName, 1, 0, 'L');
    
    $pdf->Cell(20, 8, $item['HSN_SAC'], 1, 0, 'C');
    $pdf->Cell(15, 8, $item['Quantity'], 1, 0, 'C');
    $pdf->Cell(25, 8, number_format($item['UnitPrice'], 2), 1, 0, 'R');
    $pdf->Cell(15, 8, $item['GST_Percent'].'%', 1, 0, 'C');
    $pdf->Cell(35, 8, number_format($item['TotalAmount'], 2), 1, 1, 'R');
}

// Totals
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(145, 8, 'Sub Total', 1, 0, 'R');
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(35, 8, number_format($quote['SubTotal'], 2), 1, 1, 'R');

$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(145, 8, 'Total GST', 1, 0, 'R');
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(35, 8, number_format($quote['TotalTax'], 2), 1, 1, 'R');

if($quote['Discount'] > 0) {
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(145, 8, 'Discount', 1, 0, 'R');
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(35, 8, '-' . number_format($quote['Discount'], 2), 1, 1, 'R');
}

$pdf->SetFont('helvetica', 'B', 11);
$pdf->SetFillColor(240, 240, 240);
$pdf->Cell(145, 10, 'Grand Total', 1, 0, 'R', true);
$pdf->Cell(35, 10, 'Rs. ' . number_format($quote['GrandTotal'], 2), 1, 1, 'R', true);

$pdf->Ln(15);

// Terms and Conditions
if(!empty($quote['TermsConditions'])) {
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(0, 6, 'Terms & Conditions:', 0, 1, 'L');
    $pdf->SetFont('helvetica', '', 9);
    $pdf->MultiCell(0, 5, htmlspecialchars($quote['TermsConditions']), 0, 'L');
}

// Output PDF
$pdf->Output($quote['QuoteNumber'] . '.pdf', 'I');
?>
