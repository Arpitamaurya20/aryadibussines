<?php

function ear_normalize_regularization_entries(array $data)
{
    if (!empty($data['entries']) && is_array($data['entries'])) {
        return $data['entries'];
    }

    $record_date = trim((string) ($data['record_date'] ?? $data['RecordDate'] ?? ''));
    $in_time = trim((string) ($data['in_time'] ?? $data['InTime'] ?? ''));
    $out_time = trim((string) ($data['out_time'] ?? $data['OutTime'] ?? ''));
    $attendance_id = (int) ($data['attendance_id'] ?? $data['ID'] ?? $data['id'] ?? 0);

    if ($attendance_id > 0 || $record_date !== '' || $in_time !== '') {
        return array(array(
            'attendance_id' => $attendance_id,
            'record_date' => $record_date,
            'in_time' => $in_time,
            'out_time' => $out_time,
        ));
    }

    return array();
}
