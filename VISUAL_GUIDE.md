# Stock Management System - Visual Guide

## 🎯 Quick Overview

```
┌─────────────────────────────────────────────────────────────┐
│                 STOCK MANAGEMENT WORKFLOW                    │
└─────────────────────────────────────────────────────────────┘

TIME: 06:00 AM ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
     │
     │ ☕ Coffee & Tea: AVAILABLE (if stocked yesterday)
     │ 🍕 Other Items: Current stock
     │ 🔒 Stock Entry: DISABLED
     │
TIME: 11:00 AM ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
     │
     │ ⚡ AUTO RESET TRIGGERED!
     │ ☕ Coffee → 0
     │ 🍵 Tea → 0
     │ 🍕 Other Items: Unchanged
     │ 🔒 Stock Entry: Still DISABLED
     │
TIME: 03:00 PM ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
     │
     │ ✅ Stock Entry: NOW ALLOWED
     │ 👨‍💼 Cashier can update all items
     │ 📝 Stock entered for next day
     │ ☕ Coffee & Tea can be restocked
     │
NEXT DAY ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
     │
     └─→ Cycle repeats from 06:00 AM
```

## 📱 User Interface Changes

### Before 3 PM:
```
┌────────────────────────────────────────────────────┐
│  ⚠️  Stock Entry Status                           │
│  ─────────────────────────────────────────────────│
│  Current Time: 02:30 PM                           │
│  Status: 🔒 Stock Entry Restricted                │
│  Entry allowed after 3:00 PM                      │
└────────────────────────────────────────────────────┘

┌────────────────────────────────────────────────────┐
│  Food Items & Stock                               │
│  ─────────────────────────────────────────────────│
│  Item         | Stock | Adjust     | Manual       │
│  ────────────────────────────────────────────────│
│  Coffee       |   0   | [0][+][-]  | [  ][Update] │
│  Dosa         |  20   | [0][+][-]  | [  ][Update] │
│  ────────────────────────────────────────────────│
│  All buttons are DISABLED (grayed out)            │
└────────────────────────────────────────────────────┘
```

### After 3 PM:
```
┌────────────────────────────────────────────────────┐
│  ✅ Stock Entry Status                            │
│  ─────────────────────────────────────────────────│
│  Current Time: 03:15 PM                           │
│  Status: ✅ Stock Entry Allowed                   │
└────────────────────────────────────────────────────┘

┌────────────────────────────────────────────────────┐
│  Food Items & Stock                               │
│  ─────────────────────────────────────────────────│
│  Item         | Stock | Adjust     | Manual       │
│  ────────────────────────────────────────────────│
│  Coffee       |   0   | [5][+][-]  | [50][Update] │
│  Dosa         |  20   | [5][+][-]  | [30][Update] │
│  ────────────────────────────────────────────────│
│  All buttons are ACTIVE (can click)               │
└────────────────────────────────────────────────────┘
```

## 🔄 Real-Time Updates Flow

```
┌─────────────┐         ┌──────────────┐         ┌─────────────┐
│   CASHIER   │         │   DATABASE   │         │    USERS    │
│  Dashboard  │         │              │         │  Dashboard  │
└──────┬──────┘         └──────┬───────┘         └──────┬──────┘
       │                       │                        │
       │  1. Update Stock      │                        │
       │─────────────────────→ │                        │
       │                       │                        │
       │  2. Save + Timestamp  │                        │
       │                       │                        │
       │                       │  3. SSE Push Update    │
       │                       │──────────────────────→ │
       │                       │                        │
       │                       │  4. Update UI          │
       │                       │  (no page refresh!)    │
       │                       │                        │
```

## ☕ Coffee & Tea Auto-Reset Logic

```
┌──────────────────────────────────────────────────┐
│  RESET ALGORITHM                                 │
└──────────────────────────────────────────────────┘

START
  │
  ├─→ Check Current Time
  │   
  ├─→ Is it after 11:00 AM?
  │   │
  │   NO ─→ EXIT (no reset)
  │   │
  │   YES
  │   │
  │   ├─→ Find all Coffee/Tea items
  │   │
  │   ├─→ For each item:
  │   │   │
  │   │   ├─→ Check last_stock_update
  │   │   │
  │   │   ├─→ Already reset today?
  │   │   │   │
  │   │   │   YES ─→ SKIP
  │   │   │   │
  │   │   │   NO
  │   │   │   │
  │   │   │   ├─→ SET quantity_available = 0
  │   │   │   ├─→ SET last_stock_update = NOW()
  │   │   │   └─→ LOG reset action
  │   │   │
  │   │   └─→ Next item
  │   │
  │   └─→ DONE
  │
END
```

## 🎨 Visual Status Indicators

### Stock Entry Status Badge:

```
BEFORE 3 PM:
╔════════════════════════════════════╗
║  🔒 Stock Entry Restricted         ║
╚════════════════════════════════════╝
     (Yellow/Warning Badge)

AFTER 3 PM:
╔════════════════════════════════════╗
║  ✅ Stock Entry Allowed            ║
╚════════════════════════════════════╝
     (Green/Success Badge)
```

### Item Stock Display:

```
ITEMS WITH STOCK:
┌──────────────────────┐
│ 🍕 Dosa         ✅   │
│ Stock: 20 units      │
│ [ORDER NOW]          │
└──────────────────────┘

ITEMS WITHOUT STOCK:
┌──────────────────────┐
│ ☕ Coffee       ❌   │
│ Stock: 0 units       │
│ [OUT OF STOCK]       │
│  (button disabled)   │
└──────────────────────┘
```

## 📊 Database Schema Changes

```sql
┌──────────────────────────────────────────────────┐
│  TABLE: food_items                               │
├──────────────────────────────────────────────────┤
│  EXISTING COLUMNS:                               │
│    • id                                          │
│    • name                                        │
│    • description                                 │
│    • price                                       │
│    • category                                    │
│    • time_available                              │
│    • is_active                                   │
│    • created_at                                  │
│    • updated_at                                  │
│                                                  │
│  ⭐ NEW COLUMNS:                                 │
│    • quantity_available   (INT, DEFAULT 0)      │
│    • last_stock_update    (TIMESTAMP NULL)      │
└──────────────────────────────────────────────────┘
```

## 🚀 Quick Start Commands

### Run Database Migration:
```bash
cd C:\xampp\htdocs\sdc_canteen
mysql -u root -p food_ordering_system < database/migration_add_stock_columns.sql
```

### Test the System:
```bash
cd C:\xampp\htdocs\sdc_canteen
php test_stock_system.php
```

### Access Cashier Panel:
```
http://localhost/sdc_canteen/cashier/stock_update.php
```

### View User Dashboard:
```
http://localhost/sdc_canteen/user/dashboard.php
```

## ⚙️ Configuration Options

### Change Stock Entry Time:
Edit `cashier/stock_update.php`:
```php
// Current: 3 PM (15:00)
$stock_entry_allowed = ($current_time >= '15:00:00');

// Change to 4 PM:
$stock_entry_allowed = ($current_time >= '16:00:00');
```

### Change Reset Time:
Edit `auto_stock_reset.php`:
```php
// Current: 11 AM
if ($current_time >= '11:00:00') {

// Change to 10 AM:
if ($current_time >= '10:00:00') {
```

### Add More Auto-Reset Items:
Edit `auto_stock_reset.php`:
```php
// Current items
WHERE LOWER(name) IN ('coffee', 'tea', 'masala tea')

// Add juice:
WHERE LOWER(name) IN ('coffee', 'tea', 'masala tea', 'juice')
```

## 📞 Support & Troubleshooting

### Common Issues:

#### ❌ Buttons not enabling after 3 PM
```
Solution: Check server timezone
php -r "echo date('H:i:s');"
```

#### ❌ Coffee not resetting at 11 AM
```
Solution: Check auto_stock_reset.php is being called
Add to frequently accessed page:
require_once 'auto_stock_reset.php';
resetCoffeeTeaStock();
```

#### ❌ Real-time updates not working
```
Solution: Check SSE connection
Open browser console (F12)
Look for errors in Network tab
```

## 📈 Key Metrics to Monitor

```
Daily Checklist:
□ Stock reset at 11 AM (Coffee/Tea → 0)
□ Stock entry at 3 PM (Cashier updates)
□ Real-time updates working
□ No orders for out-of-stock items
□ Last update timestamps current

Weekly Review:
□ Check last_stock_update patterns
□ Review stock consumption rates
□ Verify no missed resets
□ Check for any errors in logs
```

## 🎉 Success Indicators

```
✅ Warning message shows before 3 PM
✅ All buttons disabled before 3 PM
✅ Buttons enable exactly at 3 PM
✅ Coffee/Tea = 0 after 11 AM daily
✅ Stock updates instantly on all dashboards
✅ Order buttons disabled for 0 stock items
✅ Timestamps update on every change
✅ No double resets on same day
```

---

**System Version:** 1.0  
**Implementation Date:** November 2025  
**Status:** ✅ Production Ready
