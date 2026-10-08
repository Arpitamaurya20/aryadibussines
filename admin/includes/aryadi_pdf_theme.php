<?php
// Shared Aryadi Business PDF look (mPDF). Paths for images are relative to the
// generator's working directory (admin/<module>/action/).

if (!function_exists('apdf_text')) {

    // Escapes a value for the report; empty values render as a muted dash.
    function apdf_text($value)
    {
        $text = trim((string)$value);
        if ($text === '' || $text === '0000-00-00' || $text === '00:00:00') {
            return '<span class="muted">&mdash;</span>';
        }
        return nl2br(htmlspecialchars($text, ENT_QUOTES, 'UTF-8'));
    }

    function apdf_join($parts, $glue = ' | ')
    {
        $clean = array();
        foreach ($parts as $part) {
            $part = trim((string)$part);
            if ($part !== '' && $part !== '0000-00-00' && $part !== '00:00:00') {
                $clean[] = $part;
            }
        }
        return implode($glue, $clean);
    }

    function apdf_mpdf_config()
    {
        return array(
            'margin_left' => 12,
            'margin_right' => 12,
            'margin_top' => 12,
            'margin_bottom' => 20,
            'margin_footer' => 8,
        );
    }

    function apdf_css()
    {
        return '
        body { font-family: sans-serif; font-size: 10pt; color: #0F172A; }
        table { width: 100%; border-collapse: collapse; }
        .muted { color: #94A3B8; }

        .brand td { vertical-align: middle; }
        .brand_logo { width: 40%; }
        .brand_logo img { height: 21mm; }
        .brand_title { width: 60%; text-align: right; }
        .doc_title { font-size: 19pt; font-weight: bold; color: #0B356E; letter-spacing: 1px; }
        .doc_sub { font-size: 9pt; color: #1E8BE0; font-weight: bold; letter-spacing: 2px; margin-top: 1mm; }
        .doc_iso { font-size: 7.5pt; color: #64748B; margin-top: 1.5mm; }
        .bar_navy { height: 1.6mm; background: #0B356E; margin-top: 3mm; }
        .bar_sky { height: 0.8mm; background: #3AABF2; }

        .stats { margin-top: 4mm; border: 0.3mm solid #D6E4F2; }
        .stats td { padding: 3mm 3.5mm; background: #F3F8FD; border-right: 0.3mm solid #D6E4F2; vertical-align: top; }
        .stat_label { font-size: 7pt; color: #64748B; text-transform: uppercase; letter-spacing: 0.8px; }
        .stat_value { font-size: 10.5pt; font-weight: bold; color: #0B356E; margin-top: 1mm; }
        .stat_hint { font-size: 6.5pt; color: #94A3B8; margin-top: 0.8mm; }

        .section { margin-top: 4mm; border: 0.3mm solid #D6E4F2; }
        .section_head td { background: #0B356E; color: #FFFFFF; padding: 2.4mm 3.5mm; border: none; }
        .section_head td.section_title { font-size: 11pt; font-weight: bold; border-left: 1.2mm solid #3AABF2; }
        .section_head td.section_badge { text-align: right; padding: 1.6mm 3.5mm; border: none; }
        .badge_table { width: auto; border: none; }
        .section_head .badge_table td.badge { border: none; font-size: 8pt; font-weight: bold; padding: 1.2mm 3mm; text-transform: uppercase; letter-spacing: 0.5px; background: #E8F2FC; color: #0B356E; }

        .details_wrap td.col { width: 50%; vertical-align: top; padding: 0; }
        .details_wrap td.col_left { border-right: 0.3mm solid #D6E4F2; }
        td.col_head { background: #E8F2FC; color: #0B356E; font-weight: bold; font-size: 9.5pt; padding: 2.4mm 3.5mm; border-bottom: 0.3mm solid #D6E4F2; }

        .kv td { padding: 2.2mm 3.5mm; font-size: 9pt; border-bottom: 0.3mm solid #EEF3F9; vertical-align: top; }
        .kv td.k { width: 22%; color: #64748B; font-weight: bold; background: #FAFCFE; }
        .kv td.v { width: 28%; color: #0F172A; }
        .kv2 td { padding: 2.2mm 3.5mm; font-size: 9pt; border-bottom: 0.3mm solid #EEF3F9; vertical-align: top; }
        .kv2 td.k { width: 40%; color: #64748B; font-weight: bold; }
        .kv2 td.v { width: 60%; color: #0F172A; }

        .block { padding: 3mm 3.5mm; border-top: 0.3mm solid #EEF3F9; }
        .block_first { border-top: none; }
        .block_label { font-size: 8pt; font-weight: bold; color: #1E8BE0; text-transform: uppercase; letter-spacing: 0.8px; margin-bottom: 1.5mm; }
        .block_text { font-size: 9.5pt; line-height: 1.5; color: #0F172A; }

        .data th { background: #E8F2FC; color: #0B356E; font-size: 8pt; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px; padding: 2.2mm 3mm; text-align: left; border-bottom: 0.3mm solid #D6E4F2; }
        .data td { font-size: 8.5pt; padding: 2mm 3mm; border-bottom: 0.3mm solid #EEF3F9; vertical-align: top; }
        .data tr.alt td { background: #FAFCFE; }
        .data td.num { color: #64748B; width: 6%; }

        .img_grid td.img_cell { width: 25%; padding: 1.5mm; text-align: center; vertical-align: top; }
        .obs_img { width: 38mm; border: 0.3mm solid #D6E4F2; }
        .img_meta { font-size: 6.5pt; color: #64748B; margin-top: 1mm; }
        .check_img { width: 18mm; border: 0.3mm solid #D6E4F2; margin: 0.6mm; }

        .pill_yes { color: #059669; font-weight: bold; }
        .pill_no { color: #DC2626; font-weight: bold; }

        .tat td { text-align: center; }
        .tat td.op { width: 3%; background: #F3F8FD; color: #1E8BE0; font-weight: bold; font-size: 11pt; padding: 3mm 0; border-right: 0.3mm solid #D6E4F2; }

        .sign_table td.sign_col { width: 50%; vertical-align: top; padding: 0; }
        .sign_table td.sign_left { border-right: 0.3mm solid #D6E4F2; }
        td.sign_body { padding: 2mm 3.5mm 2.5mm 3.5mm; height: 17mm; vertical-align: bottom; }
        .sign_img { height: 13mm; }
        .sign_line { border-bottom: 0.3mm solid #94A3B8; height: 10mm; width: 60mm; }
        .sign_caption { font-size: 7.5pt; color: #64748B; margin-top: 1mm; }

        .rating { margin-top: 3mm; text-align: right; font-size: 9pt; color: #64748B; }
        .rating_stars { color: #F59E0B; font-size: 13pt; }
        .rating_off { color: #CBD5E1; font-size: 13pt; }

        .keep { page-break-inside: avoid; }

        table.box { margin-top: 4mm; border: 0.3mm solid #D6E4F2; }
        table.box td.box_title { background: #0B356E; color: #FFFFFF; padding: 2.4mm 3.5mm; font-size: 11pt; font-weight: bold; border-left: 1.2mm solid #3AABF2; }
        table.box td.box_badge { background: #0B356E; text-align: right; padding: 1.6mm 3.5mm; }
        table.box td.box_badge td.badge { background: #E8F2FC; color: #0B356E; font-size: 8pt; font-weight: bold; padding: 1.2mm 3mm; text-transform: uppercase; letter-spacing: 0.5px; border: none; }
        table.box td.box_body { padding: 0; }

        table.customer_details td { vertical-align: top; padding: 0; }
        table.customer_details td.custumer_right_border { border-right: 0.3mm solid #D6E4F2; width: 50%; vertical-align: top; padding: 0; }
        table.report_details_inside td { padding: 2mm 3.5mm; font-size: 8.5pt; border-bottom: 0.3mm solid #EEF3F9; vertical-align: top; color: #0F172A; }
        table.split-table td { width: 50%; vertical-align: top; }

        .issuer td { vertical-align: top; padding: 2.4mm 3.5mm; font-size: 9pt; }
        .issuer td.issuer_name { font-size: 10.5pt; font-weight: bold; color: #0B356E; padding: 0; }
        .issuer_meta td { padding: 0.6mm 0; font-size: 8.5pt; color: #334155; text-align: right; }
        .totals td { padding: 2mm 3.5mm; font-size: 9pt; border-bottom: 0.3mm solid #EEF3F9; text-align: right; }
        .totals td.t_label { color: #64748B; font-weight: bold; }
        .totals tr.grand td { background: #0B356E; color: #FFFFFF; font-weight: bold; font-size: 10pt; border-bottom: none; }
        .data tr.subtotal td { background: #F3F8FD; color: #0B356E; font-weight: bold; border-bottom: none; }
        ';
    }

    function apdf_header($title, $subtitle, $logo = '../../media/pdf-assets/aryadi-logo.png', $logo_style = '', $show_iso = true)
    {
        $style = $logo_style !== '' ? ' style="'.$logo_style.'"' : '';
        $iso = $show_iso ? '<div class="doc_iso">ISO 9001:2015 &nbsp;|&nbsp; ISO 14001:2015 &nbsp;|&nbsp; ISO 45001:2018 Certified</div>' : '';
        return '
    <table class="brand">
        <tr>
            <td class="brand_logo"><img src="'.$logo.'"'.$style.'></td>
            <td class="brand_title">
                <div class="doc_title">'.htmlspecialchars($title, ENT_QUOTES, 'UTF-8').'</div>
                <div class="doc_sub">'.htmlspecialchars($subtitle, ENT_QUOTES, 'UTF-8').'</div>
                '.$iso.'
            </td>
        </tr>
    </table>
    <div class="bar_navy"></div>
    <div class="bar_sky"></div>';
    }

    // $items: array of [label, valueHtml]; values must already be escaped.
    function apdf_stats($items)
    {
        $count = count($items);
        if ($count == 0) {
            return '';
        }
        $width = floor(100 / $count);
        $cells = '';
        foreach ($items as $index => $item) {
            $last = $index == $count - 1 ? ' border-right:none;' : '';
            $cells .= '
            <td style="width:'.$width.'%;'.$last.'">
                <div class="stat_label">'.$item[0].'</div>
                <div class="stat_value">'.$item[1].'</div>
            </td>';
        }
        return '<table class="stats"><tr>'.$cells.'</tr></table>';
    }

    function apdf_section_head($title, $badge = '')
    {
        $badge_html = '';
        if ($badge !== '') {
            $badge_html = '<td class="section_badge"><table class="badge_table" align="right"><tr><td class="badge">'.htmlspecialchars($badge, ENT_QUOTES, 'UTF-8').'</td></tr></table></td>';
        }
        return '<table class="section_head"><tr><td class="section_title">'.htmlspecialchars($title, ENT_QUOTES, 'UTF-8').'</td>'.$badge_html.'</tr></table>';
    }

    // A section mPDF moves to the next page as a whole instead of splitting it.
    // Only use for content that fits on one page.
    function apdf_box($title, $body_html, $badge = '')
    {
        $badge_cell = '';
        $colspan = 1;
        if ($badge !== '') {
            $badge_cell = '<td class="box_badge"><table class="badge_table" align="right"><tr><td class="badge">'.htmlspecialchars($badge, ENT_QUOTES, 'UTF-8').'</td></tr></table></td>';
            $colspan = 2;
        }
        return '
    <table class="box" autosize="1" style="page-break-inside:avoid;">
        <tr><td class="box_title">'.htmlspecialchars($title, ENT_QUOTES, 'UTF-8').'</td>'.$badge_cell.'</tr>
        <tr><td class="box_body" colspan="'.$colspan.'">'.$body_html.'</td></tr>
    </table>';
    }

    // Padded text body for apdf_box(); mPDF ignores div padding inside table cells.
    function apdf_box_text($html, $muted = false)
    {
        $color = $muted ? '#94A3B8' : '#0F172A';
        return '<table><tr><td style="padding:3mm 3.5mm; font-size:9.5pt; line-height:1.5; color:'.$color.';">'.$html.'</td></tr></table>';
    }

    // A table section that may span pages; the title and column headers repeat on each page.
    // $columns: array of [label, widthPercent].
    function apdf_data_box($title, $columns, $rows_html, $badge = '')
    {
        $count = count($columns);
        $title_html = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
        if ($badge !== '') {
            $title_html .= ' <span style="font-size:8pt; color:#BFDBFE; font-weight:normal;">&nbsp; '.htmlspecialchars($badge, ENT_QUOTES, 'UTF-8').'</span>';
        }
        $head = '';
        foreach ($columns as $column) {
            $head .= '<th style="width:'.$column[1].'%;">'.$column[0].'</th>';
        }
        return '
    <table class="box data" style="margin-top:4mm;">
        <thead>
            <tr><td class="box_title" colspan="'.$count.'">'.$title_html.'</td></tr>
            <tr>'.$head.'</tr>
        </thead>
        <tbody>'.$rows_html.'</tbody>
    </table>';
    }

    // $rows: array of [label, valueHtml]; values must already be escaped.
    function apdf_kv2($rows)
    {
        $html = '';
        foreach ($rows as $row) {
            $html .= '<tr><td class="k">'.$row[0].'</td><td class="v">'.$row[1].'</td></tr>';
        }
        return '<table class="kv2">'.$html.'</table>';
    }

    function apdf_two_col($left_title, $left_rows, $right_title, $right_rows)
    {
        return '
    <div class="section keep">
        <table class="details_wrap">
            <tr>
                <td class="col col_left">
                    <table><tr><td class="col_head">'.$left_title.'</td></tr></table>
                    '.apdf_kv2($left_rows).'
                </td>
                <td class="col">
                    <table><tr><td class="col_head">'.$right_title.'</td></tr></table>
                    '.apdf_kv2($right_rows).'
                </td>
            </tr>
        </table>
    </div>';
    }

    function apdf_acknowledgement($left_title, $left_rows, $right_title, $right_rows, $client_signature_html)
    {
        return apdf_box('Acknowledgement', '
        <table class="sign_table">
            <tr>
                <td class="sign_col sign_left">
                    <table><tr><td class="col_head">'.$left_title.'</td></tr></table>
                    '.apdf_kv2($left_rows).'
                    <table><tr><td class="sign_body">
                        <div class="sign_line"></div>
                        <div class="sign_caption">Authorised Signatory</div>
                    </td></tr></table>
                </td>
                <td class="sign_col">
                    <table><tr><td class="col_head">'.$right_title.'</td></tr></table>
                    '.apdf_kv2($right_rows).'
                    <table><tr><td class="sign_body">
                        '.$client_signature_html.'
                    </td></tr></table>
                </td>
            </tr>
        </table>');
    }

    // $cells: array of '<td class="img_cell">...</td>' strings.
    function apdf_image_grid($cells, $empty_text = 'No images attached.')
    {
        if (count($cells) == 0) {
            return '<div class="block_text muted">'.$empty_text.'</div>';
        }
        $html = '';
        foreach (array_chunk($cells, 4) as $row) {
            while (count($row) < 4) {
                $row[] = '<td class="img_cell"></td>';
            }
            $html .= '<tr>'.implode('', $row).'</tr>';
        }
        return '<table class="img_grid">'.$html.'</table>';
    }

    function apdf_image_cell($src, $meta = '')
    {
        $meta_html = trim($meta) !== '' ? '<div class="img_meta">'.htmlspecialchars($meta, ENT_QUOTES, 'UTF-8').'</div>' : '';
        return '<td class="img_cell"><img class="obs_img" src="'.$src.'" alt="">'.$meta_html.'</td>';
    }

    function apdf_turnaround_box($total, $accept, $quotation, $work, $verify)
    {
        $items = array(
            array('Total Time', $total, 'Closed - Raised'),
            array('Work Accept', $accept, 'Work Start - Raised'),
            array('Quotation Time', $quotation, 'Approved - Sent'),
            array('Work Time', $work, 'Closed - Work Start'),
            array('Verify Time', $verify, 'OTP - Closed'),
        );
        $operators = array('=', '+', '+', '+');
        $cells = '';
        foreach ($items as $index => $item) {
            $last = $index == count($items) - 1 ? ' border-right:none;' : '';
            $cells .= '
            <td style="width:17.6%;'.$last.'">
                <div class="stat_label">'.$item[0].'</div>
                <div class="stat_value">'.apdf_text($item[1] === '' ? '-' : $item[1]).'</div>
                <div class="stat_hint">'.$item[2].'</div>
            </td>';
            if (isset($operators[$index])) {
                $cells .= '<td class="op">'.$operators[$index].'</td>';
            }
        }
        return apdf_box('Turnaround Time', '<table class="stats tat" width="100%" style="width:100%; margin-top:0; border:none;"><tr>'.$cells.'</tr></table>');
    }

    function apdf_rating_html($rating)
    {
        if (empty($rating) || $rating < 1 || $rating > 5) {
            return '';
        }
        $rating = (int) $rating;
        return '
        <div class="rating">Customer Rating &nbsp;
            <span class="rating_stars">'.str_repeat('&#9733;', $rating).'</span><span class="rating_off">'.str_repeat('&#9733;', 5 - $rating).'</span>
            &nbsp;<b style="color:#0B356E;">'.$rating.'/5</b>
        </div>';
    }

    function apdf_asset_section($equipment, $make, $model, $serial, $location)
    {
        return '
    <div class="section keep">
        '.apdf_section_head('Asset Details').'
        <table class="kv">
            <tr>
                <td class="k" style="width:20%;">Equipment Name</td><td class="v" style="width:30%;">'.apdf_text($equipment).'</td>
                <td class="k" style="width:20%;">Make</td><td class="v" style="width:30%;">'.apdf_text($make).'</td>
            </tr>
            <tr>
                <td class="k">Model</td><td class="v">'.apdf_text($model).'</td>
                <td class="k">Serial Number</td><td class="v">'.apdf_text($serial).'</td>
            </tr>
            <tr>
                <td class="k">Asset Location</td><td class="v">'.apdf_text($location).'</td>
                <td class="k"></td><td class="v"></td>
            </tr>
        </table>
    </div>';
    }

    function apdf_signature_html($file_name, $date = '', $time = '')
    {
        if (trim((string)$file_name) === '') {
            return '<div class="sign_line"></div><div class="sign_caption">Signature not captured</div>';
        }
        return '
        <img class="sign_img" src="../../media/signature/'.$file_name.'">
        <div class="sign_caption">'.htmlspecialchars(apdf_join(array($date, $time), ' '), ENT_QUOTES, 'UTF-8').'</div>';
    }

    function apdf_create_mpdf($title, $footer_label, $extra_config = array(), $company = 'Aryadi Business Pvt. Ltd.', $note = '')
    {
        $mpdf = new \Mpdf\Mpdf(array_merge(apdf_mpdf_config(), $extra_config));
        $mpdf->SetTitle($title);
        $mpdf->SetAuthor($company);
        apdf_footer($mpdf, $footer_label, $company, $note);
        return $mpdf;
    }

    // Shared layout for the PPM service reports. Text values are raw (escaped here);
    // keys ending in _html are inserted as-is.
    function apdf_ppm_document($v)
    {
        $get = function ($key) use ($v) {
            return isset($v[$key]) ? $v[$key] : '';
        };
        $ticket_rows = array(
            array('Type', apdf_text($get('type'))),
            array('Category', apdf_text($get('category'))),
            array('Sub Category', apdf_text($get('subcategory'))),
            array('Status', apdf_text($get('status'))),
        );
        if (trim((string)$get('asset_condition')) !== '') {
            $ticket_rows[] = array('Asset Condition', apdf_text($get('asset_condition')));
        }
        $ticket_rows[] = array('Complaint Registered', apdf_text($get('registered')));

        $pre = is_array($get('pre_images')) ? $get('pre_images') : array();
        $post = is_array($get('post_images')) ? $get('post_images') : array();
        $service = is_array($get('service_images')) ? $get('service_images') : array();
        $images_html =
            apdf_box('Pre Image(s)', '<div class="block block_first">'.apdf_image_grid($pre, 'No pre-work images captured.').'</div>', count($pre).' Photo(s)').
            apdf_box('Post Image(s)', '<div class="block block_first">'.apdf_image_grid($post, 'No post-work images captured.').'</div>', count($post).' Photo(s)');
        if (!empty($service)) {
            $images_html .= apdf_box('Service Report', '<div class="block block_first">'.apdf_image_grid($service).'</div>', count($service).' Photo(s)');
        }

        return '
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>'.htmlspecialchars($get('title').' '.$get('report_number'), ENT_QUOTES, 'UTF-8').'</title>
    <style>'.apdf_css().'</style>
</head>
<body>
    '.apdf_header($get('title'), $get('subtitle'), $get('logo') !== '' ? $get('logo') : '../../media/pdf-assets/aryadi-logo.png').'

    '.apdf_stats(array(
        array('Report Number', apdf_text($get('report_number'))),
        array('Ticket No', apdf_text($get('ticket_id'))),
        array('Registered On', apdf_text($get('registered'))),
        array('Closed On', apdf_text($get('closed'))),
    )).'

    '.apdf_two_col(
        'Customer Details',
        array(
            array('Customer Name', apdf_text($get('company_name'))),
            array('Branch Name', apdf_text($get('branch'))),
            array('Address', apdf_text($get('address'))),
            array('Mobile No.', apdf_text($get('mobile'))),
            array('Client Ticket No.', apdf_text($get('client_ticket'))),
        ),
        'Service Ticket Details',
        $ticket_rows
    ).'

    '.apdf_asset_section($get('equipment'), $get('make'), $get('model'), $get('serial'), $get('asset_location')).'

    <div class="section">
        '.apdf_section_head('Service Details').'
        <div class="block block_first keep">
            <div class="block_label">Problem Statement</div>
            <div class="block_text">'.apdf_text($get('problem')).'</div>
        </div>
        <div class="block keep">
            <div class="block_label">Technician / Engineer Observation</div>
            <div class="block_text">'.apdf_text($get('observation')).'</div>
        </div>
        <div class="block keep">
            <div class="block_label">Action Taken</div>
            <div class="block_text">'.apdf_text($get('action_taken')).'</div>
        </div>
        <div class="block keep">
            <div class="block_label">Technician / Engineer Remarks</div>
            <div class="block_text">'.apdf_text($get('remarks')).'</div>
        </div>
    </div>

    '.$get('before_images_html').'
    '.$images_html.'
    '.$get('after_images_html').'

    '.apdf_acknowledgement(
        'Technician Details',
        array(
            array('Name', apdf_text($get('tech_name'))),
            array('Phone Number', apdf_text($get('tech_phone'))),
        ),
        'Onsite Client Representative (Verified By)',
        array(
            array('Name', apdf_text($get('client_name'))),
            array('Designation', apdf_text($get('client_designation'))),
            array('Contact Number', apdf_text($get('client_contact'))),
        ),
        $get('signature_html')
    ).'
</body>
</html>';
    }

    function apdf_footer($mpdf, $label, $company = 'Aryadi Business Pvt. Ltd.', $note = '')
    {
        $note_row = '';
        if ($note !== '') {
            $note_row = '<tr><td colspan="3" style="padding-top:1mm; text-align:center; font-size:7pt; color:#94A3B8;">'.htmlspecialchars($note, ENT_QUOTES, 'UTF-8').'</td></tr>';
        }
        $mpdf->SetHTMLFooter('
<table width="100%" style="border-top:0.3mm solid #D6E4F2; font-family:sans-serif; font-size:7.5pt; color:#64748B;">
    <tr>
        <td width="40%" style="padding-top:2mm; color:#0B356E; font-weight:bold;">'.htmlspecialchars($company, ENT_QUOTES, 'UTF-8').'</td>
        <td width="35%" style="padding-top:2mm; text-align:center;">'.htmlspecialchars($label, ENT_QUOTES, 'UTF-8').'</td>
        <td width="25%" style="padding-top:2mm; text-align:right;">Page {PAGENO} of {nbpg}</td>
    </tr>'.$note_row.'
</table>');
    }
}
