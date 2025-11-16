-- Migration to add quantity_available and last_stock_update columns to food_items table
-- Run this if you have an existing database

USE canteen;

-- Add quantity_available column if it doesn't exist
ALTER TABLE food_items 
ADD COLUMN IF NOT EXISTS quantity_available INT DEFAULT 0;

-- Add last_stock_update column if it doesn't exist
ALTER TABLE food_items 
ADD COLUMN IF NOT EXISTS last_stock_update TIMESTAMP NULL;

-- Update existing items to have default quantity
UPDATE food_items 
SET quantity_available = 0 
WHERE quantity_available IS NULL;

-- Show success message
SELECT 'Migration completed successfully. quantity_available and last_stock_update columns added.' as Status;
