# Auto PPM Ticket Generation Module

This module automatically generates PPM (Preventive Maintenance) tickets for branch assets based on configured intervals (monthly, quarterly, half-yearly, yearly).

## Features

- **Temp Branch Assets Info Management**: Store asset information with AMC dates and intervals
- **Automatic PPM Date Generation**: Automatically generates all PPM dates based on interval between AMC start and end dates
- **Daily Cron Job**: Automatically raises tickets when PPM dates match current date
- **Interval Support**: 
  - Monthly (M1, M2, M3...)
  - Quarterly (Q1, Q2, Q3, Q4)
  - Half Yearly (H1, H2)
  - Yearly (Y1, Y2...)

## Database Tables

### temp_branch_assets_info
Stores asset configuration for auto PPM:
- CorporateID
- BranchID
- BranchAssetID
- AMCStartDate (DATE)
- AMCEndDate (DATE)
- Interval (monthly, quarterly, halfyearly, yearly)
- CreatedBy, CreatedDate, CreatedTime
- IsActive

### temp_ppm_dates
Stores generated PPM dates:
- TempAssetInfoID (Foreign Key to temp_branch_assets_info)
- PPMDate (DATE)
- IntervalIdentifier (M1, M2, Q1, Q2, H1, H2, Y1, etc.)
- IsTicketRaised (0/1)
- TicketID (Reference to ppm_tickets table)
- CreatedDate, CreatedTime
- IsActive

## Installation

1. **Create Database Tables**:
   ```sql
   -- Run the SQL file
   admin/auto-ppm/sql/create_temp_tables.sql
   ```

2. **Set Up Cron Job**:
   
   **Linux (crontab)**:
   ```bash
   0 0 * * * /usr/bin/php /path/to/admin/auto-ppm/cron/auto_ppm_ticket_cron.php
   ```
   
   **Windows Task Scheduler**:
   - Create a scheduled task
   - Set to run daily at midnight
   - Action: Start a program
   - Program: `php.exe`
   - Arguments: `C:\wamp64\www\projects\techxpert\admin\auto-ppm\cron\auto_ppm_ticket_cron.php`

## Usage

### Adding Asset for Auto PPM

1. Navigate to: `admin/auto-ppm/view-auto-ppm-assets.php`
2. Click "Add Asset for Auto PPM"
3. Fill in:
   - Corporate
   - Branch
   - Asset
   - Interval (Monthly/Quarterly/Half Yearly/Yearly)
   - AMC Start Date
   - AMC End Date
4. Click Save

The system will automatically:
- Create/Update temp_branch_assets_info record
- Generate all PPM dates in temp_ppm_dates table
- Mark dates with appropriate interval identifiers

### Viewing PPM Dates

Click the calendar icon on any asset row to view all generated PPM dates and their status.

### Automatic Ticket Generation

The cron job runs daily and:
1. Checks temp_ppm_dates for dates matching current date
2. For each matching date that hasn't been raised:
   - Creates a PPM ticket in ppm_tickets table
   - Updates temp_ppm_dates to mark IsTicketRaised = 1
   - Logs the activity in logs/auto_ppm_cron_YYYY-MM-DD.txt

## File Structure

```
admin/auto-ppm/
├── sql/
│   └── create_temp_tables.sql          # Database table creation
├── controller/
│   └── auto_ppm_controller.php         # Main controller functions
├── action/
│   ├── add_update_temp_assets.php      # Add/Update temp assets
│   ├── get_temp_assets.php             # Get temp assets list
│   ├── get_temp_ppm_dates.php          # Get PPM dates
│   └── delete_temp_assets.php         # Delete temp assets
├── ajax/
│   ├── get_branches_list.php           # Get branches by corporate
│   └── get_branch_assets_list.php      # Get assets by branch
├── cron/
│   └── auto_ppm_ticket_cron.php        # Daily cron job
└── view-auto-ppm-assets.php            # Main UI page
```

## API Functions

### InsertUpdateTempBranchAssetsInfo($conn, $data)
Adds or updates temp branch assets info and generates PPM dates.

### GenerateTempPPMDates($conn, $TempAssetInfoID, $AMCStartDate, $AMCEndDate, $Interval)
Generates all PPM dates based on interval.

### ProcessAutoPPMTicketGeneration($conn)
Main cron function that processes daily ticket generation.

### GetTempBranchAssetsInfo($conn, $BranchAssetID)
Gets temp assets info.

### GetTempPPMDates($conn, $TempAssetInfoID, $IsTicketRaised)
Gets PPM dates for an asset.

## Notes

- All dates in temp tables use DATE and TIME data types (not VARCHAR)
- The module doesn't modify existing PPM ticket logic
- When an asset is updated, old PPM dates are deleted and regenerated
- Tickets are created using the existing CreatePPMTicket function
- Logs are stored in `admin/logs/auto_ppm_cron_YYYY-MM-DD.txt`

## Troubleshooting

1. **Cron not running**: Check file permissions and PHP path
2. **Tickets not generating**: Check logs in `admin/logs/`
3. **Dates not generating**: Verify AMC dates are valid and end date is after start date
4. **Database errors**: Ensure tables are created and foreign key constraints are correct

