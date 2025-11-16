# Quick Setup Guide - Stock Management System

## Step 1: Update Your Database

### Option A: New Installation
If you're setting up the system for the first time:
```bash
# Run the complete schema
mysql -u root -p canteen < database/schema.sql
```

### Option B: Existing Database (Recommended)
If you already have the system running:
```bash
# Run only the migration to add new columns
mysql -u root -p canteen < database/migration_add_stock_columns.sql
```

Or manually in MySQL:
```sql
USE canteen;

ALTER TABLE food_items 
ADD COLUMN quantity_available INT DEFAULT 0;

ALTER TABLE food_items 
ADD COLUMN last_stock_update TIMESTAMP NULL;
```

## Step 2: Verify Installation

1. Open the cashier stock update page:
   ```
   http://localhost/sdc_canteen/cashier/stock_update.php
   ```

2. Check for the warning message if before 3 PM:
   - Should see: "Stock entry is only allowed after 3:00 PM"
   - All buttons should be disabled

3. After 3 PM, buttons should be enabled

## Step 3: Test Coffee/Tea Auto Reset

### Test Before 11 AM:
1. Login as cashier (after 3 PM previous day)
2. Set Coffee stock to 100
3. Check user dashboard - Coffee should be available

### Test After 11 AM:
1. Wait until after 11:00 AM or manually change system time
2. Refresh any page (dashboard, stock_update, etc.)
3. Check Coffee stock - should be automatically 0
4. On user dashboard, Coffee order button should be disabled

## Step 4: Configure Automatic Reset (Optional but Recommended)

### Windows Task Scheduler:
1. Open Task Scheduler
2. Create Basic Task
   - Name: "Canteen Coffee Tea Reset"
   - Trigger: Daily at 11:00 AM
   - Action: Start a program
     - Program: `C:\xampp\php\php.exe`
     - Arguments: `C:\xampp\htdocs\sdc_canteen\auto_stock_reset.php`

### Linux/Mac Cron Job:
```bash
# Edit crontab
crontab -e

# Add this line (runs at 11:00 AM daily)
0 11 * * * /usr/bin/php /path/to/htdocs/sdc_canteen/auto_stock_reset.php
```

## Step 5: Test Complete Workflow

### Test Stock Entry Time Restriction:
```
Time: Before 3 PM
Action: Try to update stock
Expected: Buttons disabled, warning message shown

Time: After 3 PM  
Action: Update stock
Expected: Stock updates successfully
```

### Test Auto Reset:
```
Time: Before 11 AM
Action: Set Coffee = 50, Tea = 30
Expected: Items available for ordering

Time: After 11 AM
Action: Refresh page
Expected: Coffee = 0, Tea = 0 automatically
```

### Test Real-Time Updates:
```
Browser 1: User Dashboard
Browser 2: Cashier Stock Update

Action: Update stock in Browser 2
Expected: Stock updates instantly in Browser 1 (without refresh)
```

## Common Issues & Solutions

### Issue 1: Stock not resetting at 11 AM
**Solution:**
- The reset runs when any page loads after 11 AM
- Set up cron job for guaranteed execution
- Check server time: `<?php echo date('H:i:s'); ?>`

### Issue 2: Can't update stock after 3 PM
**Solution:**
- Check server timezone: `date_default_timezone_set('Asia/Kolkata');`
- Verify time with: `<?php echo date('H:i:s'); ?>`
- Adjust time check in `stock_update.php` if needed

### Issue 3: Real-time updates not working
**Solution:**
- Check if SSE is enabled on server
- Open browser console, look for errors
- Verify `stock_update_sse.php` is accessible

### Issue 4: Columns already exist error
**Solution:**
- If migration fails with "column exists"
- This is normal, columns are already added
- You can safely ignore this error

## File Checklist

Ensure these files exist:
- ✅ `auto_stock_reset.php` (root directory)
- ✅ `database/migration_add_stock_columns.sql`
- ✅ `STOCK_MANAGEMENT_README.md`
- ✅ Modified `cashier/stock_update.php`
- ✅ Modified `stock_update_sse.php`
- ✅ Modified `database/schema.sql`

## Testing Credentials

Default login for testing:
```
Cashier:
Roll No: 002
Password: password123

User:
Roll No: 003
Password: password123
```

## Quick Test Script

Run this to manually test the reset function:
```php
<?php
require_once 'config/database.php';
require_once 'auto_stock_reset.php';

echo "Current Time: " . date('H:i:s') . "\n";
$result = resetCoffeeTeaStock();
echo $result ? "Reset executed\n" : "Reset not needed (before 11 AM)\n";
?>
```

## Success Indicators

✅ Warning message shows before 3 PM
✅ Buttons disabled before 3 PM
✅ Buttons enabled after 3 PM
✅ Coffee/Tea stock becomes 0 after 11 AM
✅ Stock updates reflect immediately on user dashboard
✅ Order buttons disabled when stock is 0

## Need Help?

Check logs:
- PHP Error Log: `C:\xampp\apache\logs\error.log`
- Look for: "Stock reset for Coffee at..."

Enable debugging in `auto_stock_reset.php`:
```php
error_log("Stock reset executed at " . date('Y-m-d H:i:s'));
```

## Next Steps

After setup:
1. Train cashiers on new workflow
2. Set up daily stock entry schedule (after 3 PM)
3. Monitor first few days for any issues
4. Adjust time restrictions if needed
5. Consider setting up backup cron jobs
