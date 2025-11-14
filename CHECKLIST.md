# 🎯 Implementation Checklist - Stock Management System

## ✅ Phase 1: Database Setup

### Option A: New Installation
- [ ] Navigate to database folder
- [ ] Run complete schema: `mysql -u root -p canteen < schema.sql`
- [ ] Verify tables created successfully
- [ ] Check `quantity_available` and `last_stock_update` columns exist

### Option B: Existing Database (RECOMMENDED)
- [ ] Backup current database first!
- [ ] Run migration: `mysql -u root -p canteen < migration_add_stock_columns.sql`
- [ ] Verify new columns added without data loss
- [ ] Set default stock values if needed

---

## ✅ Phase 2: File Verification

### New Files Created:
- [ ] `auto_stock_reset.php` (root directory)
- [ ] `database/migration_add_stock_columns.sql`
- [ ] `test_stock_system.php`
- [ ] `STOCK_MANAGEMENT_README.md`
- [ ] `SETUP_GUIDE.md`
- [ ] `IMPLEMENTATION_SUMMARY.md`
- [ ] `VISUAL_GUIDE.md`

### Modified Files:
- [ ] `cashier/stock_update.php` - time restrictions added
- [ ] `stock_update_sse.php` - auto-reset integrated
- [ ] `database/schema.sql` - new columns added

---

## ✅ Phase 3: Testing

### Basic Functionality Tests:

#### Before 3 PM:
- [ ] Open `cashier/stock_update.php`
- [ ] Verify warning message displays
- [ ] Confirm all stock update buttons are disabled
- [ ] Check status badge shows "Restricted"

#### After 3 PM:
- [ ] Refresh `cashier/stock_update.php`
- [ ] Verify warning message disappears
- [ ] Confirm all stock update buttons are enabled
- [ ] Check status badge shows "Allowed"
- [ ] Test +/- buttons work
- [ ] Test manual update works
- [ ] Verify stock numbers update in database

#### Coffee/Tea Auto-Reset (After 11 AM):
- [ ] Set Coffee stock to a non-zero value (before 11 AM if possible)
- [ ] Wait until after 11:00 AM
- [ ] Refresh any page that loads `auto_stock_reset.php`
- [ ] Verify Coffee stock is now 0
- [ ] Check `last_stock_update` timestamp is current
- [ ] Confirm only Coffee/Tea affected, other items unchanged

### Real-Time Update Tests:
- [ ] Open `user/dashboard.php` in Browser 1
- [ ] Open `cashier/stock_update.php` in Browser 2
- [ ] Update stock in Browser 2
- [ ] Verify Browser 1 updates automatically (no refresh needed)
- [ ] Check stock numbers match
- [ ] Test with multiple items

### Integration Tests:
- [ ] Order an item with stock > 0 (should work)
- [ ] Try to order item with stock = 0 (button should be disabled)
- [ ] Check order processing doesn't break
- [ ] Verify wallet/payment still works
- [ ] Test with multiple users simultaneously

---

## ✅ Phase 4: Run Test Script

```bash
cd C:\xampp\htdocs\sdc_canteen
php test_stock_system.php
```

Expected Results:
- [ ] ✅ All database columns exist
- [ ] ✅ Current time and status displayed correctly
- [ ] ✅ Coffee/Tea items found and listed
- [ ] ✅ Auto-reset status reported correctly
- [ ] ✅ All food items listed with stock levels
- [ ] ✅ No errors in output

---

## ✅ Phase 5: Optional Cron Job Setup

### Windows Task Scheduler:
- [ ] Open Task Scheduler
- [ ] Create new Basic Task
  - Name: `Canteen Coffee Tea Stock Reset`
  - Trigger: `Daily at 11:00 AM`
  - Action: Start Program
    - Program: `C:\xampp\php\php.exe`
    - Arguments: `C:\xampp\htdocs\sdc_canteen\auto_stock_reset.php`
- [ ] Test task manually
- [ ] Verify task runs daily

### Linux/Mac Cron:
```bash
# Edit crontab
crontab -e

# Add line (runs at 11 AM daily):
0 11 * * * /usr/bin/php /path/to/htdocs/sdc_canteen/auto_stock_reset.php

# Save and verify
crontab -l
```
- [ ] Cron job added
- [ ] Path verified correct
- [ ] PHP executable path correct
- [ ] Test cron execution

---

## ✅ Phase 6: Documentation Review

- [ ] Read `STOCK_MANAGEMENT_README.md` - understand full system
- [ ] Review `SETUP_GUIDE.md` - follow all setup steps
- [ ] Check `VISUAL_GUIDE.md` - understand UI changes
- [ ] Read `IMPLEMENTATION_SUMMARY.md` - technical details

---

## ✅ Phase 7: User Training

### Train Cashiers:
- [ ] Explain new stock entry time (3 PM only)
- [ ] Show how to use +/- buttons
- [ ] Demonstrate manual entry
- [ ] Explain Coffee/Tea auto-reset at 11 AM
- [ ] Practice stock entry workflow

### Inform Users:
- [ ] Notify about Coffee/Tea availability (morning only)
- [ ] Explain out-of-stock indicators
- [ ] Show real-time stock updates

---

## ✅ Phase 8: Production Deployment

### Pre-Deployment:
- [ ] Backup entire database
- [ ] Backup all PHP files
- [ ] Note current time for testing
- [ ] Have rollback plan ready

### Deployment:
- [ ] Run database migration
- [ ] Upload/update modified files
- [ ] Upload new files
- [ ] Clear PHP cache if applicable
- [ ] Restart Apache (if needed)

### Post-Deployment:
- [ ] Test immediately after deployment
- [ ] Monitor for first 11 AM reset
- [ ] Monitor first 3 PM stock entry
- [ ] Check logs for errors
- [ ] Verify real-time updates working

---

## ✅ Phase 9: Monitoring (First Week)

### Daily Checks:
- [ ] Day 1: Verify 11 AM reset occurred
- [ ] Day 1: Verify 3 PM entry worked
- [ ] Day 2: Check reset happened again
- [ ] Day 3: Monitor for any issues
- [ ] Day 4: Verify timestamps updating
- [ ] Day 5: Check real-time updates
- [ ] Day 6: Review any user feedback
- [ ] Day 7: Complete system health check

### Check These Daily:
```sql
-- Coffee/Tea stock levels
SELECT name, quantity_available, last_stock_update 
FROM food_items 
WHERE LOWER(name) LIKE '%coffee%' OR LOWER(name) LIKE '%tea%';

-- Today's stock updates
SELECT name, quantity_available, last_stock_update
FROM food_items 
WHERE DATE(last_stock_update) = CURDATE();

-- Items with zero stock
SELECT name, category, quantity_available
FROM food_items
WHERE quantity_available = 0 AND is_active = 1;
```

---

## ✅ Phase 10: Troubleshooting

### If Issues Found:

#### Stock not resetting:
- [ ] Check server time: `php -r "echo date('H:i:s');"`
- [ ] Verify `auto_stock_reset.php` being called
- [ ] Check item names match (Coffee, Tea, Masala Tea)
- [ ] Review `last_stock_update` timestamps
- [ ] Check PHP error logs

#### Buttons not enabling:
- [ ] Verify server timezone setting
- [ ] Check time comparison in code
- [ ] Test with different times
- [ ] Review browser console for JS errors

#### Real-time not working:
- [ ] Check SSE connection in Network tab
- [ ] Verify `stock_update_sse.php` accessible
- [ ] Check database connection
- [ ] Review PHP max_execution_time setting

---

## 🎉 Success Criteria

### System is Working When:
- [ ] ✅ Stock entry disabled before 3 PM (buttons grayed out)
- [ ] ✅ Stock entry enabled after 3 PM (buttons active)
- [ ] ✅ Coffee/Tea stock = 0 after 11 AM daily
- [ ] ✅ Other items not affected by 11 AM reset
- [ ] ✅ Stock updates save to database correctly
- [ ] ✅ Real-time updates push to all users instantly
- [ ] ✅ Order buttons disabled when stock = 0
- [ ] ✅ Timestamps update on every stock change
- [ ] ✅ No errors in PHP logs
- [ ] ✅ No user complaints
- [ ] ✅ Cashiers comfortable with new workflow

---

## 📝 Notes Section

### Issues Found:
```
Date: ___________
Issue: _________________________________________________
Solution: ______________________________________________
```

### Configuration Changes:
```
Date: ___________
Changed: _______________________________________________
Reason: ________________________________________________
```

### Performance Notes:
```
Date: ___________
Observation: ___________________________________________
```

---

## 🚨 Emergency Rollback

If critical issues occur:

1. **Database Rollback:**
```sql
ALTER TABLE food_items DROP COLUMN quantity_available;
ALTER TABLE food_items DROP COLUMN last_stock_update;
```

2. **File Rollback:**
- [ ] Restore backup of `cashier/stock_update.php`
- [ ] Restore backup of `stock_update_sse.php`
- [ ] Delete `auto_stock_reset.php`

3. **Verify System:**
- [ ] Test basic ordering
- [ ] Check cashier dashboard
- [ ] Verify no errors

---

## ✅ Sign-Off

- [ ] All tests passed
- [ ] Documentation complete
- [ ] Training completed
- [ ] Production deployed
- [ ] First week monitoring done
- [ ] System stable

**Implemented By:** ___________________  
**Date:** ___________________  
**Approved By:** ___________________  
**Status:** □ Testing  □ Production  □ Complete

---

**Version:** 1.0  
**Last Updated:** November 2025
