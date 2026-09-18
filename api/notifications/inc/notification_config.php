<?php

function cns_getWorkerSecret()
{
    $secret = getenv('PUSH_QUEUE_WORKER_SECRET');
    if (is_string($secret) && trim($secret) !== '') {
        return trim($secret);
    }

    return 'techxpert_push_worker_2026';
}

function cns_getDefaultProcessLimit()
{
    $limit = getenv('PUSH_QUEUE_BATCH_LIMIT');
    if ($limit !== false && (int) $limit > 0) {
        return (int) $limit;
    }

    return 50;
}

function cns_isValidWorkerSecret($provided)
{
    $expected = cns_getWorkerSecret();
    if ($expected === '' || !is_string($provided)) {
        return false;
    }

    return hash_equals($expected, (string) $provided);
}
