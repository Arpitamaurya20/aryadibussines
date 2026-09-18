# Auto PPM Ticket Generation Cron Job

## Overview
This cron job automatically raises PPM (Preventive Maintenance) tickets daily when the PPM date in the `temp_ppm_dates` table matches the current date.

## How It Works
1. The cron job runs daily at a scheduled time
2. It checks the `temp_ppm_dates` table for records where:
   - `PPMDate` = Current Date
   - `IsTicketRaised` = 0 (not raised yet)
   - `IsActive` = 1 (active record)
3. For each matching record, it:
   - Creates a PPM ticket in the `ppm_tickets` table
   - Updates `temp_ppm_dates` to mark `IsTicketRaised = 1`
   - Stores the created ticket ID in `temp_ppm_dates.TicketID`
   - Sends WhatsApp notifications (if configured)

## File Location
```
admin/auto-ppm/cron/auto_ppm_ticket_cron.php
```

## Setup Instructions

### Option 1: Linux/Unix Cron (Recommended)
1. Open crontab editor:
   ```bash
   crontab -e
   ```

2. Add one of the following lines:
   ```bash
   # Run daily at midnight (00:00)
   0 0 * * * /usr/bin/php /path/to/admin/auto-ppm/cron/auto_ppm_ticket_cron.php >> /path/to/logs/cron_output.log 2>&1
   
   # OR run daily at 6:00 AM
   0 6 * * * /usr/bin/php /path/to/admin/auto-ppm/cron/auto_ppm_ticket_cron.php >> /path/to/logs/cron_output.log 2>&1
   ```

3. Replace `/path/to/` with your actual project path

4. Save and exit

### Option 2: Windows Task Scheduler
1. Open Task Scheduler (search for "Task Scheduler" in Windows)
2. Click "Create Basic Task"
3. Name: "Auto PPM Ticket Generation"
4. Trigger: Daily at your preferred time (e.g., 6:00 AM)
5. Action: "Start a program"
   - Program: `C:\wamp64\bin\php\php7.x.x\php.exe` (adjust PHP version)
   - Arguments: `C:\wamp64\www\projects\techxpert\admin\auto-ppm\cron\auto_ppm_ticket_cron.php`
   - Start in: `C:\wamp64\www\projects\techxpert\admin\auto-ppm\cron`
6. Click Finish

### Option 3: Third-Party Cron Services
Use services like:
- EasyCron (https://www.easycron.com)
- Cron-Job.org (https://cron-job.org)
- setcronjob.com (https://www.setcronjob.com)

**Setup:**
1. Create account on the service
2. Add new cron job
3. Set URL: `https://yourdomain.com/admin/auto-ppm/cron/auto_ppm_ticket_cron.php`
4. Schedule: Daily at your preferred time
5. Save

## Logging
Logs are automatically created in:
```
admin/logs/auto_ppm_cron_YYYY-MM-DD.txt
```

Each log file contains:
- Start time
- Number of pending tickets found
- Success/error messages
- Number of tickets raised
- Completion time

## Manual Testing
You can test the cron job manually by:

### Via Web Browser:
```
https://yourdomain.com/admin/auto-ppm/cron/auto_ppm_ticket_cron.php
```

### Via Command Line:
```bash
php admin/auto-ppm/cron/auto_ppm_ticket_cron.php
```

## Troubleshooting

### Issue: Cron job not running
- Check if PHP path is correct
- Verify file permissions (should be executable)
- Check cron service is running: `service cron status` (Linux)
- Review system logs

### Issue: No tickets being raised
- Check if there are records in `temp_ppm_dates` with today's date
- Verify `IsTicketRaised = 0` and `IsActive = 1`
- Check log files for error messages
- Verify database connection

### Issue: Database connection errors
- Check database credentials in `common_controllers.php`
- Verify database server is running
- Check network connectivity

## Database Tables Used
- `temp_ppm_dates` - Stores scheduled PPM dates
- `temp_branch_assets_info` - Stores asset configuration
- `ppm_tickets` - Stores created PPM tickets
- `branch_assets` - Asset details
- `branch` - Branch information
- `company` - Corporate information

## Security Notes
- The cron file should be accessible only to the server
- Consider adding authentication if accessed via web
- Logs may contain sensitive information - secure appropriately
- Use HTTPS for third-party cron services

## Support
For issues or questions, check:
1. Log files in `admin/logs/`
2. Database for ticket creation status
3. System error logs

