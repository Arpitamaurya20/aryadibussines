<?php
/**
 * FCM (Firebase Cloud Messaging) HTTP v1 config.
 * Required for push notifications (e.g. water reminder).
 *
 * Setup:
 * 1. Go to Firebase Console > Project Settings > Service Accounts.
 * 2. Generate new private key (JSON). Download it.
 * 3. Save as fcm-service-account.json in a folder outside web root, or here with restricted access.
 * 4. Set FCM_SERVICE_ACCOUNT_PATH below to the full path of that file.
 */
if (!defined('FCM_SERVICE_ACCOUNT_PATH')) {
    // Service account JSON from Firebase Console > Project Settings > Service Accounts > Generate new private key.
    // Place healthx-007-firebase-adminsdk-fbsvc-f588013f34.json in config/ folder.
    define('FCM_SERVICE_ACCOUNT_PATH', __DIR__ . '/../config/healthx-007-firebase-adminsdk-fbsvc-f588013f34.json');
}
