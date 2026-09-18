<?php
@session_start();
include("../../controllers/common_controllers.php");
$conn = _connectodb();

// Set headers for CSV download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="auto_ppm_assets_example.csv"');
header('Pragma: no-cache');
header('Expires: 0');

// Create output stream
$output = fopen('php://output', 'w');

// Add BOM for UTF-8 to ensure Excel displays correctly
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Write header row
$headers = array(
    'Corporate',
    'Branch',
    'Equipment Name',
    'Make',
    'Model',
    'AMC Start Date',
    'AMC End Date',
    'Interval'
);
fputcsv($output, $headers);

// Write example data rows - showing format like user's example
$example_rows = array(
    array(
        'Corporate Name 1',
        'Branch Site 1',
        'AC Unit',
        'Carrier',
        'Model XYZ-123',
        '1/1/2024',
        '12/31/2024',
        'monthly'
    ),
    array(
        'Corporate Name 1',
        'Branch Site 1',
        'Generator',
        'Cummins',
        '',
        '1/1/2024',
        '12/31/2024',
        'quarterly'
    ),
    array(
        'Corporate Name 2',
        'Branch Site 2',
        'DOM',
        'HONEY WELL',
        '',
        '12/1/2025',
        '12/31/2026',
        'quarterly'
    )
);

foreach ($example_rows as $row) {
    fputcsv($output, $row);
}

fclose($output);
exit;
?>

