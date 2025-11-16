# Stock Management System Documentation

## Overview
This system implements automatic stock management with time-based restrictions for the canteen ordering system.

## Features

### 1. Manual Stock Entry by Users
- Cashiers can manually enter/update stock quantities
- Stock can be adjusted using +/- buttons or set directly
- All stock updates are tracked with timestamps

### 2. Time-Based Stock Entry Restrictions
- **Stock entry is ONLY allowed after 3:00 PM (15:00)**
- Before 3:00 PM, all stock update buttons are disabled
- A warning message is displayed when stock entry is not allowed
- This ensures stock is entered fresh for the next day

### 3. Automatic Coffee & Tea Stock Reset
- Coffee and Tea stock automatically becomes **0 after 11:00 AM**
- This happens automatically every day
- Applies to items with names: "Coffee", "Tea", "Masala Tea" (case-insensitive)
- Once reset at 11 AM, it stays at 0 until manually updated after 3 PM

### 4. Real-Time Stock Updates
- Stock levels update in real-time across all user dashboards
- Uses Server-Sent Events (SSE) for live updates
- Items with 0 stock are automatically disabled for ordering

## Database Changes

### New Columns in `food_items` table:
1. **`quantity_available`** (INT, DEFAULT 0)
   - Stores current available quantity for each item
   
2. **`last_stock_update`** (TIMESTAMP, NULL)
   - Tracks when stock was last updated
   - Used to prevent multiple resets on the same day

## Files Modified/Created

### New Files:
1. **`auto_stock_reset.php`**
   - Contains the automatic reset logic for coffee and tea
   - Can be run as a cron job or included in pages
   - Checks time and resets stock after 11 AM

2. **`database/migration_add_stock_columns.sql`**
   - SQL migration script to add new columns to existing databases
   - Safe to run multiple times (uses IF NOT EXISTS)

### Modified Files:
1. **`cashier/stock_update.php`**
   - Added time restriction (3 PM) for stock entry
   - Integrated auto-reset functionality
   - Disabled buttons before 3 PM
   - Added warning messages

2. **`stock_update_sse.php`**
   - Integrated auto-reset check
   - Ensures stock is checked during real-time updates

3. **`database/schema.sql`**
   - Added `quantity_available` and `last_stock_update` columns

## Installation/Setup

### For New Installation:
```sql
-- Just run the updated schema.sql
source database/schema.sql
```

### For Existing Database:
```sql
-- Run the migration script
source database/migration_add_stock_columns.sql
```

## Usage

### Daily Stock Management Workflow:

#### Morning (6:00 AM - 11:00 AM):
- Coffee and Tea are available with yesterday's stock (if any)
- At 11:00 AM sharp, Coffee and Tea stock automatically becomes 0
- Stock entry buttons are disabled (before 3 PM)

#### Afternoon (11:00 AM - 3:00 PM):
- All items maintain their current stock levels
- Stock entry is NOT allowed (buttons disabled)
- Orders can still be placed for items with available stock

#### Evening (3:00 PM onwards):
- Stock entry is NOW ALLOWED
- Cashier can enter fresh stock for all items
- Coffee and Tea can be restocked for the next morning
- Stock continues to be available until 11 AM next day

### How to Update Stock:

1. **Using +/- Buttons:**
   - Enter quantity in the input field (default: 0)
   - Click + to increase or - to decrease
   - Stock updates immediately

2. **Manual Update:**
   - Enter desired quantity in the "Manual Update" field
   - Click "Update" button
   - Sets stock to exact number entered

## Automatic Reset Logic

The system checks for the following conditions:
```php
- Current time >= 11:00 AM
- Item name contains "coffee" or "tea" (case-insensitive)
- Not already reset today
```

When all conditions are met:
```php
- quantity_available = 0
- last_stock_update = current timestamp
```

## Testing

### Test the Time Restrictions:
1. Before 3 PM: Try to update stock - buttons should be disabled
2. After 3 PM: Stock update should work normally

### Test Coffee/Tea Auto Reset:
1. Set Coffee stock to 50 before 11 AM
2. After 11 AM, refresh the page
3. Coffee stock should automatically be 0

### Test Real-Time Updates:
1. Open user dashboard in one browser
2. Update stock in cashier panel in another browser
3. User dashboard should update automatically

## Cron Job Setup (Optional)

For more reliable auto-reset, set up a cron job:

### Linux/Mac:
```bash
# Run every minute between 11:00 AM and 11:05 AM
0-5 11 * * * php /path/to/xampp/htdocs/sdc_canteen/auto_stock_reset.php
```

### Windows Task Scheduler:
```
Program: C:\xampp\php\php.exe
Arguments: C:\xampp\htdocs\sdc_canteen\auto_stock_reset.php
Trigger: Daily at 11:00 AM
```

## Troubleshooting

### Stock not resetting at 11 AM?
- Check if `auto_stock_reset.php` is being called
- Verify item names exactly match "Coffee", "Tea", or "Masala Tea"
- Check `last_stock_update` column in database

### Can't update stock after 3 PM?
- Verify server time with `date('H:i:s')`
- Check timezone settings in PHP

### Real-time updates not working?
- Ensure `stock_update_sse.php` is accessible
- Check browser console for SSE connection errors
- Verify database connection

## Important Notes

⚠️ **Coffee and Tea stock will ALWAYS be 0 between 11 AM and 3 PM**
- This is by design to ensure fresh stock entry daily

⚠️ **Stock can only be entered after 3 PM**
- This prevents accidental updates during service hours

⚠️ **Real-time updates require SSE support**
- Most modern browsers support this
- Ensure long-running PHP scripts are allowed on server

## Future Enhancements

Possible improvements:
1. Configurable reset times per item
2. Stock alert notifications when low
3. Automatic stock predictions based on historical data
4. Multi-day stock planning
5. Waste tracking integration
