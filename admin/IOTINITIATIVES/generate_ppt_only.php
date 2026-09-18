<?php
require_once __DIR__ . '/pptx_builder.php';

$jsonFile = __DIR__ . '/iot_analysis_data.json';
$pptxOutput = __DIR__ . '/TechXpert_IoT_Facilities_Management_Initiative.pptx';

if (!file_exists($jsonFile)) {
    fwrite(STDERR, "Run analyze_and_generate_ppt.php first.\n");
    exit(1);
}

$analysis = json_decode(file_get_contents($jsonFile), true);
buildIotPresentation($analysis, $pptxOutput);
echo "Presentation saved: $pptxOutput\n";
