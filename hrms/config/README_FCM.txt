Push notifications (water reminder) use Firebase Cloud Messaging (FCM) HTTP v1.

1. Create a Firebase project at https://console.firebase.google.com
2. Project Settings > Service Accounts > Generate new private key (JSON). Download.
3. Save the JSON file here as: fcm-service-account.json
   (Or elsewhere and set FCM_SERVICE_ACCOUNT_PATH in include/fcm_config.php to that path.)
4. Run api/push_tokens_table.sql once to create the push_tokens table.
5. Schedule api/cron_push_water_reminder.php every 5-10 minutes (7 AM - 11:30 PM window is checked inside the script).

Water reminder: 7:00 AM to 11:30 PM (Asia/Kolkata), every 30 minutes.
