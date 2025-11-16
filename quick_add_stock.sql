-- Quick SQL Script to Add Stock
-- Run this to manually set stock quantities

USE canteen;

-- View current stock
SELECT name, quantity_available, category FROM food_items WHERE is_active = 1 ORDER BY category;

-- Set stock for specific items
UPDATE food_items SET quantity_available = 50, last_stock_update = NOW() WHERE name = 'Coffee';
UPDATE food_items SET quantity_available = 50, last_stock_update = NOW() WHERE name = 'Masala Tea';
UPDATE food_items SET quantity_available = 30, last_stock_update = NOW() WHERE name = 'Masala Dosa';
UPDATE food_items SET quantity_available = 40, last_stock_update = NOW() WHERE name = 'Idli Sambar';
UPDATE food_items SET quantity_available = 100, last_stock_update = NOW() WHERE name = 'Veg Thali';
UPDATE food_items SET quantity_available = 50, last_stock_update = NOW() WHERE name = 'Samosa';
UPDATE food_items SET quantity_available = 30, last_stock_update = NOW() WHERE name = 'Fresh Juice';

-- Verify stock was updated
SELECT name, quantity_available, last_stock_update FROM food_items WHERE is_active = 1 ORDER BY category;
