# PPM Billing Module

This module provides comprehensive billing management for PPM (Preventive Maintenance) tickets based on branch assets and time periods.

## Features

- **Billing Calculation**: Automatically calculates billing amounts based on asset unit rates and time periods (Monthly, Quarterly, Half-Yearly, Yearly)
- **Billing Tracking**: Tracks billing status (Billed/Unbilled) and payment status (Pending/Closed/Billed) for each PPM ticket
- **Filtering**: Filter by Company, Branch, and Date Range (defaults to current month)
- **Statistics Dashboard**: 
  - Ticket status counts (Raised, Assigned, Closed)
  - Billing status counts (Billed, Unbilled)
  - Payment status counts (Pending, Closed, Billed)
  - Visual charts for ticket and billing status
- **Billing Management**: 
  - Toggle billing status for tickets
  - Update billing details (amount, date, remarks)
  - Track payment status

## Database Tables

### ppm_billing_tracking
Tracks billing information for each PPM ticket:
- TicketID (Foreign Key to ppm_tickets)
- CorporateID, BranchID, BranchAssetID
- PPMDate, TicketStatus
- BillingStatus (Billed/Unbilled)
- PaymentStatus (Pending/Closed/Billed)
- BillingPeriod (Monthly/Quarterly/HalfYearly/Yearly)
- AssetUnitRate, CalculatedAmount, BilledAmount
- BilledDate, BilledBy
- PaymentDate, PaymentReceivedBy
- Remarks
- CreatedBy, CreatedDate, CreatedTime
- UpdatedBy, UpdatedDate, UpdatedTime
- IsActive

## Installation

1. **Create Database Table**:
   ```sql
   -- Run the SQL file
   admin/PPMBilling/sql/create_billing_tables.sql
   ```

2. **Access the Module**:
   - Navigate to: `admin/PPMBilling/view-ppm-billing.php`
   - Or add to navigation menu

## Usage

### Viewing Billing Information

1. Navigate to the PPM Billing page
2. Use filters to select:
   - Company (optional, defaults to all)
   - Branch (optional, defaults to all)
   - Date Range (defaults to current month)
3. Click "Search" or the data will auto-load

### Billing Calculation Logic

The module calculates billing amounts based on:
- **Asset Unit Rate**: From `branch_assets.UnitRate` or `branch_assets.Amount`
- **Time Period**: Determined by date range:
  - ≤ 31 days: Monthly (Yearly Amount / 12)
  - ≤ 93 days: Quarterly (Yearly Amount / 4)
  - ≤ 186 days: Half-Yearly (Yearly Amount / 2)
  - > 186 days: Yearly (Full Amount)

**Example**:
- Asset Yearly Amount: ₹12,000
- Monthly: ₹1,000 (12,000 / 12)
- Quarterly: ₹3,000 (12,000 / 4)
- Half-Yearly: ₹6,000 (12,000 / 2)
- Yearly: ₹12,000

### Managing Billing Status

1. **Toggle Billing Status**:
   - Click the toggle button (on/off icon) next to any ticket
   - This will switch between "Billed" and "Unbilled"
   - Payment status automatically updates

2. **Edit Billing Details**:
   - Click the edit button (pencil icon) next to any ticket
   - Update:
     - Billing Status
     - Payment Status
     - Billed Amount
     - Billed Date
     - Remarks
   - Click "Save"

### Statistics Dashboard

The dashboard shows:
- **Ticket Status Cards**: Count of Raised, Assigned, and Closed tickets
- **Billing Status Cards**: Count and amount of Billed and Unbilled tickets
- **Payment Status Cards**: Count and amount of Pending payments
- **Charts**: 
  - Bar chart for ticket status distribution
  - Doughnut chart for billing status distribution

### Graph Updates

The graphs dynamically update based on billing status changes:
- When a ticket is marked as "Billed":
  - Billed count increases
  - Unbilled count decreases
  - Closed ticket count may increase (if ticket is closed)
- When a ticket is marked as "Unbilled":
  - Billed count decreases
  - Unbilled count increases

## File Structure

```
admin/PPMBilling/
├── sql/
│   └── create_billing_tables.sql          # Database table creation
├── controller/
│   └── ppm_billing_controller.php         # Main controller functions
├── action/
│   ├── get_billing_tickets.php            # Get billing tickets list
│   ├── get_billing_statistics.php         # Get billing statistics
│   ├── toggle_billing_status.php          # Toggle billing status
│   └── update_billing_details.php         # Update billing details
├── ajax/
│   ├── get_companies_list.php             # Get companies list
│   └── get_branches_list.php              # Get branches list
├── view-ppm-billing.php                   # Main UI page
└── README.md                              # This file
```

## API Functions

### CalculateBillingAmount($yearlyAmount, $period)
Calculates billing amount based on yearly amount and period.

### DetermineBillingPeriod($startDate, $endDate)
Determines billing period based on date range.

### GetPPMTicketsForBilling($conn, $filters)
Gets PPM tickets with billing information based on filters.

### CreateUpdateBillingTracking($conn, $data)
Creates or updates billing tracking record.

### GetBillingStatistics($conn, $filters)
Gets billing statistics (counts and amounts).

### ToggleBillingStatus($conn, $data)
Toggles billing status between Billed and Unbilled.

## Dependencies

- jQuery
- DataTables
- Chart.js
- Bootstrap Datepicker
- Bootstrap Daterangepicker
- Select2 (if used in navigation)

## Notes

- The module follows the existing codebase structure and patterns
- All billing calculations are based on the asset's yearly unit rate
- Billing status changes are tracked with timestamps and user information
- The module integrates with existing PPM ticket system without modifying core functionality
