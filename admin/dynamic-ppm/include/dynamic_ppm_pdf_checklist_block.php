<?php
/**
 * Include in legacy category PPM PDF generators to append dynamic checklist block.
 * Expects: $conn, $ppmTicketDbId or $ticket_details['ticket_details']['ID']
 */
if (!isset($dynamicChecklistHTML_div)) {
    $dynamicChecklistHTML_div = '';
}

if ($dynamicChecklistHTML_div === '') {
    $ticketIdForDynamic = 0;
    if (!empty($ppmTicketDbId)) {
        $ticketIdForDynamic = (int) $ppmTicketDbId;
    } elseif (!empty($ticket_details['ticket_details']['ID'])) {
        $ticketIdForDynamic = (int) $ticket_details['ticket_details']['ID'];
    } elseif (!empty($service_reports_details['TicketID'])) {
        $ticketIdForDynamic = (int) $service_reports_details['TicketID'];
    }

    if ($ticketIdForDynamic > 0) {
        require_once __DIR__ . '/../controller/dynamic_ppm_controller.php';
        $dynamicChecklistHTML_div = dynamicPPMBuildChecklistPdfHtml($conn, $ticketIdForDynamic);
        if ($dynamicChecklistHTML_div !== '' && isset($HVACReportHTML_div)) {
            $HVACReportHTML_div = '';
        }
        if ($dynamicChecklistHTML_div !== '' && isset($AssetCondition_html)) {
            $AssetCondition_html = '';
        }
    }
}

?>