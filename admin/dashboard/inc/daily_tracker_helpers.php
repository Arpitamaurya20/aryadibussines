<?php
if (!function_exists('trackerTone')) {
    function trackerTone($status) {
        $status = strtolower((string)$status);
        if (strpos($status, 'close') !== false || strpos($status, 'approved') !== false) {
            return 'ok';
        }
        if (strpos($status, 'escalat') !== false || strpos($status, 'reject') !== false || strpos($status, 'cancel') !== false) {
            return 'bad';
        }
        if (strpos($status, 'hold') !== false || strpos($status, 'pending') !== false) {
            return 'wait';
        }
        return 'info';
    }
}

if (!function_exists('trackerStatusLabel')) {
    function trackerStatusLabel($status) {
        if ($status == "Hold by Aryadibusiness") {
            return "Hold by Innov";
        }
        return $status;
    }
}

if (!function_exists('formatTrackerValue')) {
    function formatTrackerValue($count, $status) {
        $count = (int)$count;
        $tone = trackerTone($status);
        if ($count === 0) {
            return "<span class='dt-cell dt-cell-empty'>&nbsp;</span>";
        }
        return "<span class='dt-cell dt-cell-{$tone}'>{$count}</span>";
    }
}

if (!function_exists('renderDailyTrackerTable')) {
    function renderDailyTrackerTable($status_array, $daily_tracker_status, $start_date, $end_date) {
        $column_totals = array();
        foreach ($status_array as $status) {
            $column_totals[$status['Status']] = 0;
        }

        $today = date('Y-m-d');
        $rows = '';
        $currentDate = new DateTime($end_date);
        $endDate = new DateTime($start_date);
        while ($currentDate >= $endDate) {
            $date_in_process = $currentDate->format('Y-m-d');
            $dow = (int)$currentDate->format('N');
            $classes = array();
            if ($date_in_process === $today) {
                $classes[] = 'is-today';
            }
            if ($dow >= 6) {
                $classes[] = 'is-weekend';
            }
            $class_attr = $classes ? ' class="'.implode(' ', $classes).'"' : '';
            $rows .= "<tr{$class_attr}>";
            $rows .= "<td class='dt-date-cell'><span class='dt-day'>".$currentDate->format('d')."</span><span class='dt-meta'>".$currentDate->format('M')." · ".$currentDate->format('D')."</span></td>";
            foreach ($status_array as $status) {
                $count = isset($daily_tracker_status[$date_in_process][$status['Status']]) ? (int)$daily_tracker_status[$date_in_process][$status['Status']] : 0;
                $column_totals[$status['Status']] += $count;
                $rows .= "<td>".formatTrackerValue($count, $status['Status'])."</td>";
            }
            $rows .= "</tr>";
            $currentDate->modify('-1 day');
        }

        $html = '<table id="daily_tracker_html"><thead><tr>';
        $html .= '<th class="dt-date-head">Date</th>';
        foreach ($status_array as $status) {
            $html .= '<th class="tone-'.trackerTone($status['Status']).'">'.htmlspecialchars(trackerStatusLabel($status['Status'])).'</th>';
        }
        $html .= '</tr></thead><tbody>'.$rows.'</tbody><tfoot><tr>';
        $html .= '<td class="dt-date-cell">Total</td>';
        foreach ($status_array as $status) {
            $html .= '<td>'.formatTrackerValue($column_totals[$status['Status']], $status['Status']).'</td>';
        }
        $html .= '</tr></tfoot></table>';
        return $html;
    }
}
